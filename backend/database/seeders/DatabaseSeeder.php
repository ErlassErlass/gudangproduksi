<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RbacSeeder::class);

        if (! app()->isProduction()) {
            $this->call([
                DemoUserSeeder::class,
                DemoMasterAndInventorySeeder::class,
            ]);
        }
    }
}
