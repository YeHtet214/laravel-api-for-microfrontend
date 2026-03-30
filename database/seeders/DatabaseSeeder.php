<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SsoClient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@test.com'], [
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        SsoClient::updateOrCreate(['client_id' => 'mfe-sso-auth'], [
            'name' => 'SSO Auth',
            'client_id' => 'mfe-sso-auth',
            'client_secret' => 'secret123',
            'redirect_uris' => [
                'http://mfe-sso-auth.test',
                'http://auth.mfe-server.test:5173',
                'http://user.mfe-server.test:5174',
                'http://product.mfe-server.test:5175',
                'http://order.mfe-server.test:5176',
                // Production Vercel URLs
                'https://mfe-sso-auth.vercel.app',
                'https://mfe-user-management-portal-app.vercel.app',
                'https://mfe-product-management.vercel.app',
                'https://mfe-order-management.vercel.app',
            ],
            'is_active' => true,
        ]);

        $this->call([
            RBACSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
