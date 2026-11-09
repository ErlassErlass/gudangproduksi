<?php

namespace App\Domains\Administration\Actions;

use App\Models\User;
use App\Shared\Audit\AuditLogger;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\Hash;

final readonly class AuthenticateUserAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @return array{token: string, token_type: string, user: User}
     */
    public function execute(string $email, string $password, string $deviceName = 'web'): array
    {
        $user = User::query()
            ->with('roles')
            ->where('email', $email)
            ->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            $this->audit->record('auth.login.failed', null, null, ['email' => $email]);
            throw new DomainException('Email atau kata sandi tidak valid.', 'AUTH_INVALID_CREDENTIALS', 422, [
                'email' => ['Email atau kata sandi tidak valid.'],
            ]);
        }

        if (! $user->is_active) {
            $this->audit->record('auth.login.blocked_inactive', $user, null, null, $user);
            throw new DomainException('Akun Anda tidak aktif. Hubungi administrator.', 'AUTH_USER_INACTIVE', 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken($deviceName)->plainTextToken;

        $this->audit->record('auth.login.succeeded', $user, null, ['device_name' => $deviceName], $user);

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ];
    }
}
