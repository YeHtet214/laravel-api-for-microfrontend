<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DeploymentSeeder extends Seeder
{
    /**
     * Run the database seeds for deployment.
     *
     * This seeder is designed to run during deployment to populate
     * demo data that will be available at runtime for users.
     */
    public function run(): void
    {
        // Seed RBAC data (roles and permissions)
        $this->call([
            RBACSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
