<?php

namespace Tests\Feature;

use App\Domains\Administration\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthAndRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_active_user_can_login_fetch_profile_with_permissions_and_logout(): void
    {
        $user = User::factory()->withRole('warehouse_manager')->create([
            'email' => 'wm@erlass.test',
            'password' => 'secret123',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'wm@erlass.test',
            'password' => 'secret123',
            'device_name' => 'phpunit',
        ]);

        $login->assertOk()
            ->assertJsonPath('data.user.email', 'wm@erlass.test')
            ->assertJsonFragment(['inventory.transaction.post']);

        $token = (string) $login->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertNotNull($user->fresh()?->last_login_at);

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login.succeeded', 'user_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.logout', 'user_id' => $user->id]);
    }

    public function test_inactive_user_cannot_login_or_use_existing_token(): void
    {
        $user = User::factory()->inactive()->withRole('super_admin')->create([
            'email' => 'inactive@erlass.test',
            'password' => 'secret123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@erlass.test',
            'password' => 'secret123',
        ])->assertStatus(403)->assertJsonPath('code', 'AUTH_USER_INACTIVE');

        $token = $user->createToken('test')->plainTextToken;
        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_auditor_role_is_strictly_read_only(): void
    {
        $auditor = User::factory()->withRole('auditor')->create();
        $role = Role::query()->where('code', 'warehouse_staff')->firstOrFail();

        // Can read users, roles, audit logs, and dashboard.
        $this->actingAs($auditor, 'sanctum')->getJson('/api/v1/users')->assertOk();
        $this->actingAs($auditor, 'sanctum')->getJson('/api/v1/roles')->assertOk();
        $this->actingAs($auditor, 'sanctum')->getJson('/api/v1/audit-logs')->assertOk();
        $this->actingAs($auditor, 'sanctum')->getJson('/api/v1/dashboard/summary')->assertOk();

        // Cannot create or update users or roles (09_RBAC_MATRIX.md "Separation of duties").
        $this->actingAs($auditor, 'sanctum')->postJson('/api/v1/users', [
            'name' => 'Illegal User',
            'email' => 'illegal@erlass.test',
            'password' => 'password123',
            'is_active' => true,
            'role_ids' => [$role->id],
        ])->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN');

        $this->actingAs($auditor, 'sanctum')->postJson('/api/v1/roles', [
            'code' => 'custom_role',
            'name' => 'Custom',
            'permission_codes' => ['dashboard.view'],
        ])->assertStatus(403);
    }

    public function test_user_with_manage_permission_can_create_and_update_users_with_audit_trail(): void
    {
        $admin = User::factory()->withRole('super_admin')->create();
        $staffRole = Role::query()->where('code', 'production_staff')->firstOrFail();

        $created = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/users', [
            'name' => 'Budi Produksi',
            'email' => 'budi@erlass.test',
            'password' => 'password123',
            'is_active' => true,
            'role_ids' => [$staffRole->id],
        ]);

        $created->assertCreated()
            ->assertJsonPath('data.name', 'Budi Produksi')
            ->assertJsonPath('data.roles.0.code', 'production_staff');

        $targetId = (int) $created->json('data.id');

        // Self-deactivation is blocked.
        $this->actingAs($admin, 'sanctum')->putJson("/api/v1/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'is_active' => false,
            'role_ids' => [],
        ])->assertStatus(409)->assertJsonPath('code', 'USER_CANNOT_DEACTIVATE_SELF');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'administration.user.created',
            'entity_type' => 'users',
            'entity_id' => $targetId,
        ]);
    }

    public function test_authenticated_user_can_update_email_and_password_from_profile(): void
    {
        $user = User::factory()->withRole('super_admin')->create([
            'name' => 'Demo Super Admin',
            'email' => 'old_email@erlass.test',
            'password' => 'secret1234',
        ]);

        // Wrong current password should fail
        $this->actingAs($user, 'sanctum')->patchJson('/api/v1/auth/profile', [
            'email' => 'new_email@erlass.test',
            'current_password' => 'wrongpass',
            'password' => 'newsecret1234',
            'password_confirmation' => 'newsecret1234',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);

        // Correct update
        $res = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/auth/profile', [
            'email' => 'new_email@erlass.test',
            'current_password' => 'secret1234',
            'password' => 'newsecret1234',
            'password_confirmation' => 'newsecret1234',
        ]);

        $res->assertOk()
            ->assertJsonPath('data.email', 'new_email@erlass.test')
            ->assertJsonPath('data.name', 'Demo Super Admin');

        $user->refresh();
        $this->assertSame('new_email@erlass.test', $user->email);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newsecret1234', $user->password));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.profile_updated',
            'entity_type' => 'users',
            'entity_id' => $user->id,
        ]);
    }
}
