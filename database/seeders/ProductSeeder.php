<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Maps the short brand codes used in sklad.pdf to the canonical brand
     * names stored in the brands table (see BrandSeeder).
     */
    private array $brandCodeMap = [
        'esteelauder' => 'Estée Lauder',
        'vs' => "Victoria's Secret",
        'cht' => 'Charlotte Tilbury',
        'Onesize' => 'One/Size',
        'Gisou' => 'Gisou',
        'lancome' => 'Lancome',
        'beauty creations' => 'Beauty Creations',
        'hourglass' => 'Hourglass',
        'make up by mario' => 'Makeup by Mario',
        'huda beauty' => 'Huda Beauty',
        'fenty' => 'Fenty Beauty',
        'natasha denona' => 'Natasha Denona',
        'nars' => 'NARS Cosmetics',
        'dior' => 'Dior',
        'patrick ta' => 'Patrick Ta',
        'anastasia beverly hills' => 'Anastasia Beverly Hills',
        'laura mercier' => 'Laura Mercier',
        'ysl' => 'Yves Saint Laurent',
        'too faced' => 'Too Faced',
        'grande mascara' => 'Grande Cosmetics',
        'pat magrat' => 'Pat McGrath Labs',
        'urban decay' => 'Urban Decay',
        'tarte' => 'Tarte',
        'sephora' => 'Sephora Collection',
        'sheglam' => 'SHEGLAM',
        'makeup forever' => 'Make Up For Ever',
        'caudalie' => 'Caudalie',
        'rhode' => 'Rhode',
        'rare beauty' => 'Rare Beauty',
        'sephora favorites' => 'Sephora Favorites',
        'clinique' => 'Clinique',
        'bbw' => 'Bath and Body Works',
        'sol ded janerio' => 'Sol de Janeiro',
        'fresh' => 'Fresh',
        'glow recipe' => 'Glow Recipe',
        'bobbi brown' => 'Bobbi Brown',
        'khiels' => "Kiehl's",
        'jessup' => 'Jessup',
    ];

    /**
     * Product rows taken from sklad.pdf.
     * Columns: [name, brand code, quantity, purchase price, delivery cost (карго), website price (цена на сайте), selling price (продажа цена)].
     * A selling price of 0 means the продажа column was blank in the PDF.
     * Large selling prices (>= 1000) are UZS soum values recorded as-is; their markup is left at 0 to be filled in manually later.
     */
    private array $rows = [
        // esteelauder
        ['Блокбастер нкд', 'esteelauder', 1, 90.0, 20.0, 90.0, 150.0],
        ['blokbaster fioletoviy', 'esteelauder', 3, 85.3, 19.9, 90.0, 150.0],
        ['beautiful magnolia 3 tali atir nabor', 'esteelauder', 2, 89.1, 9.0, 112.0, 120.0],
        ['daywear oq gift nabor', 'esteelauder', 1, 54.4, 7.0, 68.0, 0],
        ['kotta sivorotkali 50 ml oq gift nabor', 'esteelauder', 1, 93.8, 12.0, 134.0, 0],
        ['kotta kremli oq gift nabor', 'esteelauder', 1, 73.3, 11.0, 124.0, 0],
        ['4 tali nabor vozrastnoy', 'esteelauder', 1, 45.0, 3.0, 45.0, 55.0],
        ['4 tali daywear nabor', 'esteelauder', 1, 40.0, 4.0, 40.0, 50.0],
        ['soft clean toner & cleanser nabor', 'esteelauder', 1, 46.0, 11.5, 46.0, 0],
        ['mini 4 tali gift nabor', 'esteelauder', 1, 23.4, 5.5, 48.0, 0],
        ['розовая косметика набор', 'esteelauder', 1, 0.0, 3.0, 0.0, 0],
        ['Косметичка ок синий', 'esteelauder', 1, 0.0, 3.0, 0.0, 0],
        ['косметичка набор синий', 'esteelauder', 1, 0.0, 3.0, 0.0, 0],
        ['косметичка набор розово голубой', 'esteelauder', 1, 0.0, 3.0, 0.0, 0],
        ['futurist foundation', 'esteelauder', 1, 41.3, 2.0, 55.0, 0],
        ['double wear foundation', 'esteelauder', 1, 41.6, 1.0, 52.0, 0],
        ['futurist tint foundation', 'esteelauder', 1, 44.0, 2.0, 55.0, 0],
        ['futurist base', 'esteelauder', 2, 23.0, 1.0, 46.0, 0],
        ['mascara full size', 'esteelauder', 1, 0.0, 2.0, 35.0, 0],
        ['perfectly clean cleanser 150 ml', 'esteelauder', 2, 19.0, 2.0, 32.0, 0],
        ['pefectly clean toner 200 ml', 'esteelauder', 1, 19.0, 2.0, 32.0, 0],
        ['nutritious lotion 100 ml', 'esteelauder', 2, 24.0, 4.0, 39.0, 0],
        ['nutritious cleanser 125 ml', 'esteelauder', 1, 0.0, 2.0, 28.0, 0],
        ['nutritious mini set', 'esteelauder', 1, 12.0, 2.0, 15.0, 0],
        ['advanced night repair eye cream 15 ml', 'esteelauder', 2, 0.0, 2.0, 76.0, 0],
        ['kichkina podarochnaya korobka nabor', 'esteelauder', 2, 0.0, 5.0, 0.0, 0],
        ['kotta podarochnaya korobka', 'esteelauder', 9, 0.0, 6.0, 0.0, 0],
        // vs
        ['bardovaya sumochka brelok', 'vs', 1, 9.9, 2.0, 25.0, 0],
        ['bantik brelok', 'vs', 1, 9.9, 2.0, 20.0, 0],
        ['qora sumochka brelok', 'vs', 2, 6.8, 2.0, 25.0, 0],
        ['vs strazali brelok', 'vs', 1, 11.4, 2.0, 25.0, 0],
        ['bomshell atir 50ml', 'vs', 1, 20.1, 5.0, 60.0, 60.0],
        ['tease atir 50 ml', 'vs', 1, 26.0, 5.0, 60.0, 60.0],
        ['bomshell midnight atir 100 ml', 'vs', 1, 24.8, 8.0, 80.0, 75.0],
        ['very sexy orchid atir100 ml', 'vs', 1, 27.5, 7.6, 80.0, 70.0],
        ['bomshell soiree atir 50 ml', 'vs', 1, 20.4, 5.0, 60.0, 0],
        ['bomshell amalfi atir 50 ml', 'vs', 1, 20.6, 5.5, 60.0, 0],
        ['bomshell midnight atir 7 ml', 'vs', 2, 8.3, 2.0, 20.0, 0],
        ['bomshell amalfi atir 7 ml', 'vs', 2, 8.3, 2.0, 20.0, 0],
        ['bomshell glamour atir 7 ml', 'vs', 1, 7.6, 2.0, 20.0, 0],
        ['bomshell soiree aitr 7 ml', 'vs', 3, 8.1, 2.0, 20.0, 0],
        ['atir nabor 3 tali bomsheell', 'vs', 1, 42.5, 13.0, 85.0, 80.0],
        ['atir nabor 3 tali tease', 'vs', 1, 42.5, 13.0, 85.0, 80.0],
        ['hibiscus 3 tali mini nabor', 'vs', 1, 15.2, 5.0, 20.0, 0],
        ['4 tali mist nabor shimmer', 'vs', 3, 12.2, 8.0, 35.0, 0],
        ['4 tali mist nabor', 'vs', 1, 10.5, 8.0, 35.0, 0],
        ['bomshell parfume & lotion nabor', 'vs', 2, 13.3, 3.5, 38.0, 0],
        ['tease parfume & lotion nabor', 'vs', 1, 18.5, 4.0, 38.0, 0],
        ['very sexy parfume & lotion nabor', 'vs', 3, 13.3, 4.3, 38.0, 0],
        ['bare parfume & lotion nabor', 'vs', 1, 18.5, 4.0, 38.0, 0],
        ['cocunt milk & rose 4 tali nabor', 'vs', 1, 18.0, 4.0, 35.0, 0],
        ['pure seduction mini nabor', 'vs', 1, 6.2, 4.0, 20.0, 0],
        ['love spell mini nabor', 'vs', 1, 6.2, 4.0, 20.0, 0],
        ['raskladnoy vs nadpis kosmetichka', 'vs', 1, 20.6, 5.5, 45.0, 0],
        ['qora 4 pcs kosmeticka', 'vs', 1, 27.0, 5.0, 55.0, 0],
        ['qora angel 2 pcs kosmeticka', 'vs', 3, 14.1, 2.0, 35.0, 0],
        ['oq qizil poloska pijama s dlinnim rukavom', 'vs', 2, 30.0, 4.0, 70.0, 0],
        ['rozoviy qizil poloska pijama s dlinnim rukavom', 'vs', 2, 28.7, 4.6, 70.0, 0],
        ['zelyonaya pijama s dlinnim rukavom', 'vs', 1, 27.5, 5.2, 70.0, 0],
        ['qizil futbolkali pijama', 'vs', 1, 33.8, 3.0, 65.0, 0],
        ['oq futbolkali pijama', 'vs', 2, 20.4, 5.0, 70.0, 0],
        ['oq vs nadpis s dlinnim rukavom pijama', 'vs', 1, 33.8, 5.0, 70.0, 0],
        ['rozoviy poloska s dlinnim rukavom pijama', 'vs', 2, 33.8, 5.0, 70.0, 0],
        ['qizil longsleevel pijama', 'vs', 1, 13.4, 5.0, 50.0, 0],
        ['goluvoy futbolka shortinli pijama', 'vs', 1, 28.3, 3.0, 50.0, 0],
        ['qizil odamchali s dlinnim rukavom pijama', 'vs', 1, 20.6, 5.0, 70.0, 0],
        ['qizil poloskali pijama s dlinnim rukavom', 'vs', 1, 20.6, 5.0, 70.0, 0],
        ['rozoviy obtyashka s dlinnim rukavom pijama', 'vs', 2, 24.3, 5.5, 65.0, 0],
        ['qizil banniy xalat', 'vs', 1, 17.1, 9.0, 70.0, 55.0],
        ['oq pink tapochka', 'vs', 1, 18.8, 4.5, 45.0, 0],
        ['rozoviy pink tapochka', 'vs', 2, 14.5, 3.5, 45.0, 0],
        ['rozoviy vs tapochka', 'vs', 2, 17.9, 4.0, 45.0, 0],
        ['qizil tapochka', 'vs', 3, 18.1, 4.3, 45.0, 0],
        ['oq teddy sumka', 'vs', 1, 0.0, 4.0, 65.0, 35.0],
        ['leopardoviy strazali trusi', 'vs', 3, 5.0, 1.0, 120000.0, 0],
        ['siniy trusi', 'vs', 1, 3.8, 0.5, 10.5, 0],
        ['gryazno rozoviy trusi', 'vs', 1, 3.8, 0.5, 10.5, 66.0],
        ['qora bakali trusi', 'vs', 1, 3.8, 0.5, 10.5, 0],
        ['fioletoviy trusi', 'vs', 1, 3.8, 0.5, 10.5, 0],
        ['oq strazali trusi', 'vs', 1, 5.0, 1.0, 14.5, 120000.0],
        ['rozoviy strazali trusi', 'vs', 1, 5.0, 1.0, 14.5, 120000.0],
        ['rozoviy poloskali trusi', 'vs', 1, 3.8, 0.5, 10.5, 0],
        ['fioletoviy vishnyali trusi', 'vs', 2, 3.8, 0.5, 10.5, 0],
        ['5 pack logo cotton Hip huger pantie L', 'vs', 1, 15.0, 3.0, 28.0, 35.0],
        ['5 pack logo cotton Hip huger pantie M', 'vs', 1, 15.0, 3.0, 28.0, 35.0],
        ['5 pack seamless high leg M', 'vs', 1, 15.0, 3.0, 28.0, 35.0],
        ['5 pack seamless high leg L', 'vs', 1, 15.0, 3.0, 28.0, 35.0],
        ['5 pack qizil stringi S', 'vs', 1, 13.5, 1.5, 28.0, 35.0],
        ['5 pack qizil stringi M', 'vs', 1, 13.5, 1.5, 28.0, 35.0],
        ['5 pack goluboy stringi S', 'vs', 1, 16.6, 3.0, 28.0, 35.0],
        ['5 pack goluboy stringi M', 'vs', 1, 16.6, 3.0, 28.0, 35.0],
        ['5 pack cvetochkali S', 'vs', 1, 16.6, 3.0, 28.0, 35.0],
        ['love spell joy shimmer mist', 'vs', 1, 4.8, 3.0, 20.0, 210000.0],
        ['love spell joy lotion', 'vs', 1, 4.8, 3.0, 20.0, 210000.0],
        ['pear glace mist', 'vs', 1, 4.8, 3.0, 20.0, 210000.0],
        ['pear glace lotion', 'vs', 1, 4.3, 3.0, 20.0, 210000.0],
        ['velvet petals joy shimmer mist', 'vs', 1, 4.8, 3.0, 20.0, 210000.0],
        ['velvet petals joy lotion', 'vs', 1, 4.8, 3.0, 20.0, 210000.0],
        ['seaside surf mist', 'vs', 1, 4.3, 3.0, 20.0, 210000.0],
        ['seaside surf lotion', 'vs', 1, 4.3, 3.0, 20.0, 210000.0],
        ['midnight bloom mist', 'vs', 1, 7.1, 3.0, 20.0, 210000.0],
        ['midnight blom lotion', 'vs', 1, 7.1, 3.0, 20.0, 210000.0],
        ['hibiscus mist', 'vs', 1, 4.8, 4.0, 23.0, 230000.0],
        ['hibiscus lotion', 'vs', 1, 4.8, 4.0, 23.0, 230000.0],
        ['bomshell midnight mist', 'vs', 1, 8.7, 4.0, 25.0, 310000.0],
        ['bomshell midnight lotion', 'vs', 1, 8.7, 4.0, 25.0, 310000.0],
        ['bomshell mist', 'vs', 2, 10.4, 3.5, 25.0, 310000.0],
        ['mango templation mist', 'vs', 1, 4.8, 3.0, 20.0, 210000.0],
        ['mango templation lotion', 'vs', 1, 4.8, 3.0, 20.0, 210000.0],
        ['vanilla orchid feel comfor mist', 'vs', 1, 10.0, 3.0, 23.0, 230000.0],
        ['vanilla orchid feel comfort body wash', 'vs', 1, 10.0, 3.0, 23.0, 230000.0],
        ['velvet petals lotion', 'vs', 1, 5.0, 3.0, 20.0, 210000.0],
        ['velvet petals body wash', 'vs', 1, 6.0, 4.0, 20.0, 230000.0],
        ['daring mist', 'vs', 1, 8.5, 3.5, 25.0, 310000.0],
        ['daring lotion', 'vs', 1, 8.5, 3.5, 25.0, 310000.0],
        ['bomshell soiree mist', 'vs', 1, 6.8, 3.0, 25.0, 310000.0],
        ['lotus mist', 'vs', 1, 5.5, 3.7, 20.0, 210000.0],
        ['lotus skrab', 'vs', 1, 6.0, 3.0, 20.0, 0],
        ['lavander lotion', 'vs', 1, 7.0, 3.0, 20.0, 210000.0],
        ['lavander body wash', 'vs', 1, 5.3, 3.0, 20.0, 210000.0],
        ['Coastal bliss lotion', 'vs', 2, 4.3, 3.0, 20.0, 210000.0],
        ['cocunt milk & rose lotion', 'vs', 1, 7.0, 3.0, 20.0, 210000.0],
        ['macadamia lotion', 'vs', 1, 7.0, 3.0, 20.0, 210000.0],
        ['Bomshell amalfi lotion', 'vs', 2, 6.3, 3.3, 25.0, 310000.0],
        ['pure seduction daydream lotion', 'vs', 1, 5.9, 3.0, 20.0, 210000.0],
        ['velvet petals daydream lotion', 'vs', 1, 5.9, 3.0, 20.0, 210000.0],
        ['Vibrant breeze lotion', 'vs', 1, 4.3, 3.0, 20.0, 210000.0],
        ['Island rush lotion', 'vs', 1, 4.3, 3.0, 20.0, 210000.0],
        ['nectar pulse mist', 'vs', 1, 6.0, 3.0, 20.0, 210000.0],
        ['cucumber body wash', 'vs', 1, 6.0, 4.0, 20.0, 0],
        ['almond blossom body wash', 'vs', 2, 4.0, 3.0, 20.0, 0],
        ['intimate wash', 'vs', 1, 5.6, 2.0, 15.0, 0],
        ['coco chill mini scrub', 'vs', 1, 3.0, 2.0, 10.0, 0],
        ['pineapple scrub', 'vs', 1, 7.0, 4.0, 20.0, 235000.0],
        ['merry pom butter', 'vs', 1, 5.5, 2.0, 20.0, 180000.0],
        ['supper berry scrub', 'vs', 1, 5.1, 5.0, 20.0, 0],
        ['coco passion butter', 'vs', 1, 4.3, 3.0, 20.0, 0],
        // cht
        ['collagen blesk i karandash nabor pillow talk', 'cht', 2, 18.8, 2.0, 25.0, 470000.0],
        ['collagen blesk i karandash nabor pillow talk medium', 'cht', 2, 18.8, 2.0, 25.0, 470000.0],
        ['Konfetka rozoviy', 'cht', 3, 30.0, 2.0, 35.0, 0],
        ['Kollagen trio blesk nabor', 'cht', 2, 15.0, 2.0, 25.0, 0],
        ['Umivalka & krem nabor', 'cht', 1, 23.8, 2.0, 34.0, 0],
        ['Magic cream 15 ml', 'cht', 2, 15.0, 1.0, 32.0, 0],
        ['Magic water cream 30 ml', 'cht', 4, 32.5, 2.0, 69.0, 0],
        ['Lip nabor 4 pcs pillow talk', 'cht', 1, 37.8, 3.0, 54.0, 0],
        ['Lip nabor 4 pcs pillow talk medium', 'cht', 1, 37.8, 3.0, 54.0, 0],
        ['Pillow talk nabor eye set', 'cht', 1, 36.0, 1.0, 59.0, 0],
        ['Fiksator zolotisty nabor', 'cht', 1, 44.0, 3.5, 54.0, 0],
        ['Fiksator nabor 34 ml', 'cht', 1, 24.0, 3.0, 32.0, 0],
        ['Fiksator 34 ml', 'cht', 1, 12.0, 1.5, 25.0, 0],
        ['Super star nabor', 'cht', 2, 23.2, 2.0, 32.0, 0],
        ['Umivalka 124 ml', 'cht', 1, 15.0, 2.0, 32.0, 0],
        ['Matte liquid blush', 'cht', 3, 22.1, 1.0, 42.0, 0],
        ['Blush highlighter 2 in 1', 'cht', 2, 22.1, 1.0, 42.0, 0],
        ['Highlighter liqiuid', 'cht', 4, 22.1, 1.0, 42.0, 0],
        ['contourwand', 'cht', 4, 22.1, 1.0, 42.0, 0],
        ['super star mascara', 'cht', 3, 15.0, 1.0, 29.0, 0],
        ['push up lashes mascara', 'cht', 3, 15.0, 1.0, 29.0, 0],
        ['liquid matte lipstick', 'cht', 3, 0.0, 2.0, 37.0, 0],
        ['collagen blesk & lip liner nabor', 'cht', 2, 0.0, 2.0, 25.0, 0],
        ['hyaluronic balm', 'cht', 3, 8.8, 1.0, 35.0, 0],
        ['blush stick', 'cht', 1, 0.0, 2.0, 44.0, 0],
        ['bronzer', 'cht', 1, 29.0, 2.0, 50.0, 0],
        ['love pallete', 'cht', 2, 34.5, 3.0, 69.0, 0],
        ['suxoy highlighter', 'cht', 4, 0.0, 2.0, 50.0, 0],
        ['powder', 'cht', 4, 28.4, 2.0, 49.0, 0],
        ['blush & highlighter pallete', 'cht', 1, 14.5, 1.0, 29.0, 0],
        ['pigment', 'cht', 1, 17.0, 1.0, 34.0, 0],
        ['eyeshadow pallete pillow talk', 'cht', 2, 27.5, 2.0, 58.0, 0],
        ['eyeshadow pallete bella sofia', 'cht', 3, 0.0, 2.0, 58.0, 0],
        ['eyes to mesmerise', 'cht', 4, 20.3, 1.0, 35.0, 0],
        ['rozvaya kosmetichka', 'cht', 3, 7.9, 3.0, 45.0, 0],
        // Onesize
        ['mini fixing spray', 'Onesize', 3, 14.4, 1.0, 18.0, 0],
        ['fixing spray', 'Onesize', 3, 27.2, 1.5, 34.0, 0],
        ['powder duo set', 'Onesize', 1, 25.0, 2.0, 38.0, 0],
        ['Lip liner', 'Onesize', 1, 14.0, 1.0, 14.0, 19.0],
        // Gisou
        ['Hair oil & blesk set', 'Gisou', 2, 31.2, 2.5, 36.0, 0],
        ['Blesk', 'Gisou', 7, 14.9, 1.5, 28.0, 0],
        ['Kotta set 4 tali', 'Gisou', 3, 69.0, 8.0, 75.0, 0],
        // lancome
        ['lancome boks', 'lancome', 1, 84.0, 22.0, 89.0, 140.0],
        // beauty creations
        ['mini rozovie teni', 'beauty creations', 2, 8.8, 2.0, 10.0, 0],
        ['mini nyud teni', 'beauty creations', 2, 8.8, 2.0, 10.0, 0],
        ['mini persikoviy teni', 'beauty creations', 2, 8.8, 2.0, 10.0, 0],
        ['lip liner set', 'beauty creations', 1, 0.0, 4.5, 20.0, 0],
        // hourglass
        ['konsiler', 'hourglass', 4, 30.4, 1.0, 38.0, 0],
        ['powder', 'hourglass', 1, 41.6, 2.0, 52.0, 0],
        // make up by mario
        ['nature pallete', 'make up by mario', 6, 51.0, 2.0, 69.0, 0],
        ['nude pallete', 'make up by mario', 1, 42.0, 2.0, 56.0, 0],
        ['cream blush', 'make up by mario', 1, 0.0, 2.0, 34.0, 0],
        ['blush & contour set', 'make up by mario', 1, 24.0, 1.0, 30.0, 0],
        ['lip balm', 'make up by mario', 4, 14.5, 1.0, 26.0, 0],
        // huda beauty
        ['fixing spray', 'huda beauty', 4, 19.8, 2.0, 31.0, 0],
        ['creamy eyeshadow', 'huda beauty', 1, 0.0, 2.0, 0.0, 0],
        ['pressed powder', 'huda beauty', 4, 15.2, 2.0, 40.0, 0],
        ['powder', 'huda beauty', 3, 33.2, 3.0, 39.0, 0],
        // fenty
        ['mini eyeshadow', 'fenty', 2, 15.0, 2.0, 30.0, 0],
        ['fenty mini set', 'fenty', 2, 31.8, 2.5, 35.0, 42.0],
        ['foundation', 'fenty', 1, 20.0, 2.0, 40.0, 0],
        // natasha denona
        ['nude pallete', 'natasha denona', 1, 58.7, 4.0, 69.0, 0],
        ['retro pallete', 'natasha denona', 1, 43.8, 5.0, 69.0, 0],
        // nars
        ['mini blush & blesk', 'nars', 3, 24.0, 3.0, 30.0, 0],
        ['luqiud blush & blesk', 'nars', 1, 25.6, 2.0, 32.0, 0],
        ['concelear', 'nars', 1, 25.6, 1.0, 32.0, 0],
        // dior
        ['highlighter', 'dior', 1, 43.2, 2.0, 54.0, 0],
        // patrick ta
        ['blush', 'patrick ta', 1, 30.4, 1.0, 38.0, 0],
        // anastasia beverly hills
        ['mini eyeshadow & mascara', 'anastasia beverly hills', 1, 24.7, 3.0, 38.0, 0],
        ['stick blush & blesk', 'anastasia beverly hills', 1, 20.4, 2.0, 34.0, 0],
        // laura mercier
        ['mini set powder & stick eyeshadow', 'laura mercier', 2, 21.0, 1.0, 30.0, 0],
        ['powder rozoviy krishka', 'laura mercier', 1, 33.6, 2.0, 48.0, 0],
        ['powder qora krishka', 'laura mercier', 1, 30.1, 2.0, 43.0, 0],
        ['powder set', 'laura mercier', 1, 30.1, 5.4, 43.0, 0],
        ['blush & stick set', 'laura mercier', 1, 33.0, 2.0, 37.0, 0],
        // lancome
        ['mascara set', 'lancome', 1, 24.0, 3.0, 30.0, 0],
        // ysl
        ['mascara duo', 'ysl', 2, 40.6, 2.0, 46.0, 0],
        ['parfume libre duo set', 'ysl', 1, 31.0, 3.0, 40.0, 0],
        ['parfume libre gift set', 'ysl', 2, 83.2, 5.5, 115.0, 0],
        // too faced
        ['chocolate pallete', 'too faced', 1, 15.2, 3.0, 54.0, 0],
        ['contour stick', 'too faced', 1, 17.5, 2.0, 35.0, 0],
        // grande mascara
        ['mascara set', 'grande mascara', 1, 17.7, 2.0, 23.0, 0],
        // pat magrat
        ['blesk duo', 'pat magrat', 1, 17.6, 1.0, 29.0, 0],
        // urban decay
        ['mini fixing duo', 'urban decay', 2, 19.4, 2.0, 25.0, 0],
        ['fixing spray', 'urban decay', 1, 27.2, 2.0, 34.0, 0],
        // tarte
        ['juicy pallete', 'tarte', 1, 20.0, 3.0, 49.0, 0],
        ['trippinwithtarte pallete', 'tarte', 1, 31.8, 2.0, 49.0, 0],
        ['in bloom pallete', 'tarte', 1, 31.8, 5.4, 49.0, 0],
        ['matte pallete', 'tarte', 1, 31.8, 4.5, 49.0, 0],
        ['plumper & lip liner set', 'tarte', 3, 20.2, 1.0, 27.0, 0],
        ['plumper set of 5', 'tarte', 1, 31.8, 2.0, 49.0, 0],
        ['tarte big set of 2', 'tarte', 3, 46.2, 4.0, 59.0, 0],
        ['plumper big set', 'tarte', 1, 79.2, 6.0, 99.0, 0],
        ['bej eyeliner', 'tarte', 1, 9.0, 1.0, 22.0, 0],
        ['plumper', 'tarte', 1, 9.5, 0.0, 27.0, 0],
        ['bronzer', 'tarte', 1, 16.0, 1.0, 32.0, 0],
        ['blush', 'tarte', 1, 15.0, 1.0, 30.0, 0],
        // sephora
        ['brush22', 'sephora', 1, 15.2, 1.0, 19.0, 0],
        ['brush 57', 'sephora', 1, 19.2, 1.0, 24.0, 0],
        ['brush 27', 'sephora', 1, 18.4, 1.0, 23.0, 0],
        ['brush 64', 'sephora', 1, 28.0, 1.0, 35.0, 0],
        ['blush & brush set', 'sephora', 1, 23.2, 2.0, 29.0, 0],
        // sheglam
        ['blesk', 'sheglam', 2, 5.0, 1.0, 5.5, 0],
        ['liquid contour', 'sheglam', 1, 5.0, 1.0, 5.5, 0],
        // makeup forever
        ['pressed powder', 'makeup forever', 2, 36.0, 2.0, 45.0, 0],
        // caudalie
        ['mini clay mask', 'caudalie', 2, 17.6, 1.0, 22.0, 0],
        // rhode
        ['blush', 'rhode', 3, 21.6, 2.0, 25.0, 0],
        // rare beauty
        ['mist', 'rare beauty', 4, 14.0, 2.5, 28.0, 0],
        ['lotion', 'rare beauty', 4, 14.0, 4.0, 28.0, 0],
        ['blush', 'rare beauty', 3, 20.0, 2.0, 25.0, 0],
        ['liquid highlighter', 'rare beauty', 1, 23.0, 2.0, 27.0, 0],
        ['liquid contour', 'rare beauty', 1, 23.8, 2.0, 28.0, 0],
        ['lip oil', 'rare beauty', 1, 15.4, 1.0, 22.0, 0],
        ['eyeshadow stick', 'rare beauty', 1, 11.0, 1.0, 22.0, 0],
        ['blush mini trio set', 'rare beauty', 1, 28.8, 3.0, 36.0, 0],
        ['blush & parfume set', 'rare beauty', 1, 24.0, 2.0, 30.0, 0],
        // sephora favorites
        ['mascara set', 'sephora favorites', 1, 23.8, 1.0, 28.0, 0],
        // clinique
        ['mini cream duo set', 'clinique', 2, 14.0, 2.0, 14.0, 0],
        ['rozovaya kosmetichka nabor', 'clinique', 1, 0.0, 3.0, 0.0, 0],
        ['black honey', 'clinique', 2, 18.8, 1.0, 20.0, 0],
        ['mosturizing lotion125 ml', 'clinique', 2, 17.0, 3.0, 34.0, 0],
        ['4 pcs eye liner', 'clinique', 4, 19.0, 2.0, 45.0, 0],
        ['mini black honey & mascara set', 'clinique', 4, 9.8, 2.0, 20.0, 0],
        ['clinique 5 pcs gift set', 'clinique', 1, 0.0, 2.0, 65.0, 0],
        ['moisturise surge 30 ml', 'clinique', 1, 25.5, 1.0, 30.0, 0],
        ['moisturise surge 30 ml bez korobki', 'clinique', 1, 0.0, 1.0, 30.0, 0],
        ['moisturise surge 50 ml', 'clinique', 1, 40.0, 2.0, 48.0, 0],
        ['all about eyes 15ml', 'clinique', 1, 42.0, 2.0, 42.0, 0],
        ['light reflecting primer', 'clinique', 1, 0.0, 1.0, 40.0, 0],
        ['wrinkle correcting cream 5 ml', 'clinique', 1, 0.0, 1.0, 0.0, 0],
        ['mascara & lipstick', 'clinique', 1, 12.8, 1.0, 0.0, 0],
        ['mascara & blesk', 'clinique', 1, 12.8, 1.0, 0.0, 0],
        // bbw
        ['in the stars gift set', 'bbw', 1, 0.0, 2.0, 16.0, 0],
        ['mini gingham rozovy mist', 'bbw', 1, 3.0, 2.0, 9.0, 0],
        ['gingham rozovly mist', 'bbw', 2, 6.0, 3.0, 19.0, 0],
        ['gingham rozovly body cream', 'bbw', 2, 6.0, 3.0, 19.0, 0],
        ['gingham mist', 'bbw', 2, 6.0, 3.0, 19.0, 0],
        ['gingham body cream', 'bbw', 2, 6.0, 3.0, 19.0, 0],
        ['japanese cherry blossom mist', 'bbw', 2, 6.0, 3.0, 19.0, 0],
        ['japanese ccherry blossom butter', 'bbw', 1, 4.5, 3.0, 21.0, 0],
        ['you are the one mist', 'bbw', 1, 6.0, 3.0, 19.0, 0],
        ['you are the once body cream', 'bbw', 1, 6.0, 3.0, 19.0, 0],
        ['thousand wishesw mist', 'bbw', 1, 6.0, 3.0, 19.0, 0],
        ['thousand wishes body cream', 'bbw', 1, 6.0, 3.0, 19.0, 0],
        ['pure wonderlotion', 'bbw', 2, 6.0, 3.0, 19.0, 0],
        ['pure wonder body wash', 'bbw', 1, 6.0, 3.0, 19.0, 0],
        ['butterfly body cream', 'bbw', 1, 6.0, 3.0, 19.0, 0],
        ['in the stars body cream', 'bbw', 1, 6.0, 3.0, 19.0, 0],
        ['white jasmine mist', 'bbw', 1, 6.0, 3.0, 19.0, 0],
        ['champange body wash', 'bbw', 1, 6.0, 3.0, 19.0, 0],
        ['into the night body butter', 'bbw', 1, 4.5, 3.0, 21.0, 0],
        // sol ded janerio
        ['4 tali kotta 62 nabor', 'sol ded janerio', 2, 48.0, 18.0, 69.0, 0],
        ['3 tali kotta 40 nabor', 'sol ded janerio', 1, 34.0, 14.7, 0.0, 0],
        ['mini 62 nabor', 'sol ded janerio', 1, 27.2, 2.0, 34.0, 0],
        ['mini 5 mists nabor', 'sol ded janerio', 1, 31.2, 5.0, 39.0, 0],
        ['butter 62 500ml', 'sol ded janerio', 1, 62.4, 10.0, 85.0, 0],
        // fresh
        ['cleanser & cream set', 'fresh', 1, 20.0, 3.0, 33.0, 0],
        ['toner, serum, cream set', 'fresh', 2, 42.3, 6.5, 59.0, 0],
        // glow recipe
        ['cream & serum set', 'glow recipe', 2, 33.6, 3.0, 42.0, 0],
        // bobbi brown
        ['mini trio set', 'bobbi brown', 2, 0.0, 1.0, 0.0, 0],
        ['base 100 ml', 'bobbi brown', 1, 79.1, 4.0, 113.0, 0],
        ['base 50 ml', 'bobbi brown', 1, 48.3, 2.0, 69.0, 0],
        ['base 15 ml', 'bobbi brown', 1, 17.5, 1.0, 25.0, 0],
        ['face cream 50 ml', 'bobbi brown', 1, 0.0, 1.0, 69.0, 0],
        ['set of two base 50 ml', 'bobbi brown', 2, 69.7, 4.0, 82.0, 0],
        // khiels
        ['mini set', 'khiels', 1, 0.0, 2.0, 0.0, 0],
        ['duo cream set', 'khiels', 1, 31.2, 3.0, 39.0, 0],
        // jessup
        ['persikoviy 14 in 7 kisti', 'jessup', 1, 36.5, 2.0, 45.7, 0],
        ['goluboy 14 in 10 kisti', 'jessup', 1, 38.1, 2.0, 44.9, 0],
        ['goluboy 10 in 6 kisti', 'jessup', 1, 26.1, 2.0, 34.8, 0],
        ['goluboy 5 in 3 kisti', 'jessup', 1, 20.0, 2.0, 26.6, 0],
        ['chernie kisti', 'jessup', 1, 21.6, 3.0, 24.6, 0],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->delete();

        // Build a lower-cased brand name => id lookup so we can resolve
        // the canonical names produced by $brandCodeMap.
        $brandIds = Brand::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower($name) => $id])
            ->all();

        $defaultCategory = Category::first();

        if (! $defaultCategory) {
            $this->command->error('No categories found. Please run CategorySeeder first.');

            return;
        }

        $created = 0;
        $skippedBrands = [];

        foreach ($this->rows as [$name, $brandCode, $quantity, $purchasePrice, $deliveryCost, $websitePrice, $sellingPrice]) {
            $brandName = $this->brandCodeMap[$brandCode] ?? null;
            $brandId = $brandName ? ($brandIds[mb_strtolower($brandName)] ?? null) : null;

            if (! $brandId) {
                $skippedBrands[$brandCode] = true;
            }

            // Catalog product. quantity and selling_price are caches that the
            // ProductBatch observer fills in once the initial batch is created.
            $product = Product::create([
                'brand_id' => $brandId,
                'category_id' => $defaultCategory->id,
                'name' => $name,
                'description' => null,
                'comment' => null,
                'quantity' => 0,
                'selling_price' => 0,
                'image' => null,
                'link' => null,
                'status' => 'in_stock',
            ]);

            // Initial procurement batch holding this row's pricing from sklad.pdf.
            $product->batches()->create([
                'purchase_price' => $purchasePrice,
                'delivery_cost' => $deliveryCost,
                'markup' => $this->calculateMarkup($purchasePrice, $deliveryCost, $sellingPrice),
                'selling_price' => $sellingPrice,
                'website_price' => $websitePrice,
                'quantity' => $quantity,
                'received_at' => now(),
            ]);

            $created++;
        }

        $this->command->info("Created {$created} products from sklad.pdf.");

        if ($skippedBrands) {
            $this->command->warn('Brands not found (products left unlinked): '.implode(', ', array_keys($skippedBrands)));
        }
    }

    /**
     * Derive the markup percentage from the app's own selling-price formula:
     *   selling = purchase + purchase * markup/100 + delivery
     * => markup = (selling - delivery - purchase) / purchase * 100
     *
     * Returns 0 when it cannot be computed meaningfully: no purchase price,
     * a blank selling price (0), a soum-valued selling price (>= 1000, filled
     * in manually later), or a negative result (markup is an unsigned column).
     */
    private function calculateMarkup(float $purchasePrice, float $deliveryCost, float $sellingPrice): int
    {
        if ($purchasePrice <= 0 || $sellingPrice <= 0 || $sellingPrice >= 1000) {
            return 0;
        }

        $markup = (int) round((($sellingPrice - $deliveryCost - $purchasePrice) / $purchasePrice) * 100);

        return max(0, $markup);
    }
}
