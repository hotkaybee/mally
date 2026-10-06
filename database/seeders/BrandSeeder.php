<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            // Brands referenced by sklad.pdf that were previously missing
            ['name' => 'Estée Lauder', 'website' => 'https://www.esteelauder.com/', 'logo' => null],
            ['name' => 'One/Size', 'website' => 'https://onesizebeauty.com/', 'logo' => null],
            ['name' => 'Beauty Creations', 'website' => 'https://www.beautycreationscosmetics.com/', 'logo' => null],
            ['name' => 'Hourglass', 'website' => 'https://www.hourglasscosmetics.com/', 'logo' => null],
            ['name' => 'Huda Beauty', 'website' => 'https://hudabeauty.com/', 'logo' => null],
            ['name' => 'Natasha Denona', 'website' => 'https://natashadenona.com/', 'logo' => null],
            ['name' => 'Yves Saint Laurent', 'website' => 'https://www.yslbeautyus.com/', 'logo' => null],
            ['name' => 'Too Faced', 'website' => 'https://www.toofaced.com/', 'logo' => null],
            ['name' => 'Grande Cosmetics', 'website' => 'https://grandecosmetics.com/', 'logo' => null],
            ['name' => 'Pat McGrath Labs', 'website' => 'https://www.patmcgrath.com/', 'logo' => null],
            ['name' => 'Sephora Collection', 'website' => 'https://www.sephora.com/', 'logo' => null],
            ['name' => 'SHEGLAM', 'website' => 'https://www.sheglam.com/', 'logo' => null],
            ['name' => 'Make Up For Ever', 'website' => 'https://www.makeupforever.com/', 'logo' => null],
            ['name' => 'Caudalie', 'website' => 'https://www.caudalie.com/', 'logo' => null],
            ['name' => 'Rhode', 'website' => 'https://www.rhodeskin.com/', 'logo' => null],
            ['name' => 'Sephora Favorites', 'website' => 'https://www.sephora.com/', 'logo' => null],
            ['name' => 'Clinique', 'website' => 'https://www.clinique.com/', 'logo' => null],
            ['name' => 'Sol de Janeiro', 'website' => 'https://soldejaneiro.com/', 'logo' => null],
            ['name' => 'Fresh', 'website' => 'https://www.fresh.com/', 'logo' => null],
            ['name' => 'Glow Recipe', 'website' => 'https://www.glowrecipe.com/', 'logo' => null],
            ['name' => 'Kiehl\'s', 'website' => 'https://www.kiehls.com/', 'logo' => null],
            ['name' => 'Jessup', 'website' => 'https://jessupbeauty.com/', 'logo' => null],
            // Brands first stocked in the 06 Oct 2026 warehouse snapshot
            ['name' => 'Buxom', 'website' => 'https://www.buxomcosmetics.com/', 'logo' => null],
            ['name' => 'Haus Labs', 'website' => 'https://hauslabs.com/', 'logo' => null],
            ['name' => 'Kosas', 'website' => 'https://kosas.com/', 'logo' => null],
            ['name' => 'Laneige', 'website' => 'https://us.laneige.com/', 'logo' => null],
            ['name' => 'Lys Beauty', 'website' => 'https://lysbeauty.com/', 'logo' => null],
            ['name' => 'Saie', 'website' => 'https://saiehello.com/', 'logo' => null],
            ['name' => 'Salt & Stone', 'website' => 'https://saltandstone.com/', 'logo' => null],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}
