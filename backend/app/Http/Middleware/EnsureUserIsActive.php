<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Blocks deactivated accounts even if they still hold a previously issued Sanctum token. */
final class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user !== null && ! $user->is_active) {
            $user->currentAccessToken()?->delete();
            throw new AuthenticationException('Akun Anda telah dinonaktifkan.');
        }

        return $next($request);
    }
}
