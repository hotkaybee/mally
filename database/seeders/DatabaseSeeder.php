<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Development admin only; production admins are created with
        // make:filament-user + admin:grant.
        if (app()->environment('local')) {
            User::firstOrCreate(
                ['email' => 'admin@mally.uz'],
                [
                    'name' => 'Администратор Mally',
                    'password' => 'admin12345',
                ]
            )->forceFill(['is_admin' => true])->save();
        }

        $this->call([
            BrandSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
