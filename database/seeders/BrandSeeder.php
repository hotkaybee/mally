<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('brands')->delete();

        $brands = [
            ['name' => 'Charlotte Tilbury'],
            ['name' => 'Victoria\'s Secret'],
            ['name' => 'Bath and Body Works'],
            ['name' => 'Dior'],
            ['name' => 'Fenty'],
            ['name' => 'Byredo'],
            ['name' => 'Shiseido'],
            ['name' => 'Drunk Elephant'],
            ['name' => 'The Ordinary'],
            ['name' => 'CeraVe'],
            ['name' => 'Urban Decay'],
            ['name' => 'Rare Beauty'],
            ['name' => 'Nars'],
            ['name' => 'Fenty Beauty'],
            ['name' => 'Tarte'],
            ['name' => 'Patrick Ta'],
            ['name' => 'Laura Mercier'],
            ['name' => 'Gisou'],
            ['name' => 'Anastasia Beverly Hills'],
            ['name' => 'Makeup by Mario'],
            ['name' => 'Lancome'],
            ['name' => 'Bobbi Brown'],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}
