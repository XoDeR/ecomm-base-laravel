<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            StoreSettingsSeeder::class,
            CatalogSeeder::class,
        ]);

        $roles = UserRole::pluck('id', 'slug');

        User::updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Store Admin',
            'password' => 'password',
            'role_id' => $roles[UserRole::ADMIN],
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate(['email' => 'customer@example.com'], [
            'name' => 'Jane Customer',
            'phone' => '+1 (555) 123-4567',
            'password' => 'password',
            'role_id' => $roles[UserRole::CUSTOMER],
            'email_verified_at' => now(),
        ]);

        $this->call(DemoSalesSeeder::class);
    }
}
