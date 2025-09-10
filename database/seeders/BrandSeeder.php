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
            ['name' => 'Charlotte Tilbury', 'website' => 'https://www.charlottetilbury.com/us', 'logo' => 'https://logos-world.net/wp-content/uploads/2021/02/Charlotte-Tilbury-Logo.png'],
            ['name' => 'Victoria\'s Secret', 'website' => 'https://www.victoriassecret.com/us/', 'logo' => 'https://logos-world.net/wp-content/uploads/2020/12/Victorias-Secret-Logo.png'],
            ['name' => 'Bath and Body Works', 'website' => 'https://www.bathandbodyworks.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2020/11/Bath-Body-Works-Logo.png'],
            ['name' => 'Dior', 'website' => 'https://www.dior.com/en_us', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Dior_Logo.svg/300px-Dior_Logo.svg.png'],
            ['name' => 'Fenty Beauty', 'website' => 'https://fentybeauty.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2021/02/Fenty-Beauty-Logo.png'],
            ['name' => 'Byredo', 'website' => 'https://www.byredo.com/us_en', 'logo' => 'https://logos-world.net/wp-content/uploads/2021/02/Byredo-Logo.png'],
            ['name' => 'Shiseido', 'website' => 'https://www.shiseido.com/us/en/', 'logo' => 'https://logos-world.net/wp-content/uploads/2020/12/Shiseido-Logo.png'],
            ['name' => 'Drunk Elephant', 'website' => 'https://www.drunkelephant.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2021/02/Drunk-Elephant-Logo.png'],
            ['name' => 'The Ordinary', 'website' => 'https://theordinary.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2021/02/The-Ordinary-Logo.png'],
            ['name' => 'CeraVe', 'website' => 'https://www.cerave.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2021/02/CeraVe-Logo.png'],
            ['name' => 'Urban Decay', 'website' => 'https://www.urbandecay.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2020/12/Urban-Decay-Logo.png'],
            ['name' => 'Rare Beauty', 'website' => 'https://www.rarebeauty.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2021/02/Rare-Beauty-Logo.png'],
            ['name' => 'NARS Cosmetics', 'website' => 'https://www.narscosmetics.com', 'logo' => 'https://logos-world.net/wp-content/uploads/2020/12/NARS-Logo.png'],
            ['name' => 'Tarte', 'website' => 'https://tartecosmetics.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2020/12/Tarte-Logo.png'],
            ['name' => 'Patrick Ta', 'website' => 'https://patrickta.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2021/02/Patrick-Ta-Logo.png'],
            ['name' => 'Laura Mercier', 'website' => 'https://www.lauramercier.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2020/12/Laura-Mercier-Logo.png'],
            ['name' => 'Gisou', 'website' => 'https://gisou.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2021/02/Gisou-Logo.png'],
            ['name' => 'Anastasia Beverly Hills', 'website' => 'https://www.anastasiabeverlyhills.com', 'logo' => 'https://logos-world.net/wp-content/uploads/2020/12/Anastasia-Beverly-Hills-Logo.png'],
            ['name' => 'Makeup by Mario', 'website' => 'https://www.makeupbymario.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2021/02/Makeup-by-Mario-Logo.png'],
            ['name' => 'Lancome', 'website' => 'https://www.lancome-usa.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2020/12/Lancome-Logo.png'],
            ['name' => 'Bobbi Brown', 'website' => 'https://www.bobbibrowncosmetics.com/', 'logo' => 'https://logos-world.net/wp-content/uploads/2020/12/Bobbi-Brown-Logo.png'],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}
