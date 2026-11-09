<?php

namespace Database\Seeders;

use App\Domains\Administration\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Development / testing demo accounts ONLY (agent_docs/11_TESTING_STRATEGY.md,
 * AGENT_INSTRUCTIONS.md §6 and §9). Never run in production.
 */
final class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $this->call(RbacSeeder::class);

        $accounts = [
            ['email' => 'admin@erlass.test', 'name' => 'Superadmin ERLASS', 'role' => 'superadmin'],
            ['email' => 'gudang@erlass.test', 'name' => 'PIC Gudang ERLASS', 'role' => 'pic_gudang'],
            ['email' => 'produksi@erlass.test', 'name' => 'Kepala Produksi ERLASS', 'role' => 'kepala_produksi'],
            ['email' => 'staff@erlass.test', 'name' => 'Staff Operasional', 'role' => 'staff'],
            // Backwards-compatible aliases for existing automated tests
            ['email' => 'warehouse@erlass.test', 'name' => 'Demo PIC Gudang', 'role' => 'pic_gudang'],
            ['email' => 'production@erlass.test', 'name' => 'Demo Kepala Produksi', 'role' => 'kepala_produksi'],
            ['email' => 'qc@erlass.test', 'name' => 'Demo QC Inspector', 'role' => 'staff'],
            ['email' => 'rental@erlass.test', 'name' => 'Demo Staff Rental', 'role' => 'rental_staff'],
            ['email' => 'auditor@erlass.test', 'name' => 'Demo Auditor', 'role' => 'auditor'],
        ];

        $defaultPassword = (string) env('PWMS_DEMO_PASSWORD', 'password');
        $roleIds = Role::query()->pluck('id', 'code');

        foreach ($accounts as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => $defaultPassword,
                    'is_active' => true,
                ],
            );

            if (isset($roleIds[$account['role']])) {
                $user->roles()->sync([$roleIds[$account['role']]]);
            }
        }
    }
}
