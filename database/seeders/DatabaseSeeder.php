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
        User::firstOrCreate(
            ['email' => 'admin@mally.uz'],
            [
                'name' => 'Администратор Mally',
                'password' => 'admin12345',
            ]
        );

        $this->call([
            BrandSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
