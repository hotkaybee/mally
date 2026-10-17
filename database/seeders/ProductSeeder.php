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
     * The warehouse snapshot this catalogue was taken from. Receipts are dated
     * with it rather than "today", so re-seeding stays deterministic.
     */
    private const SNAPSHOT_DATE = '2026-10-06';

    /**
     * Maps the short brand codes used in the warehouse spreadsheet to the
     * canonical brand names stored in the brands table (see BrandSeeder).
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
        // Codes introduced by the 06 Oct 2026 snapshot
        'kiehls' => "Kiehl's",
        'Shiseido' => 'Shiseido',
        'buxom' => 'Buxom',
        'haus labs' => 'Haus Labs',
        'kosas' => 'Kosas',
        'laneige' => 'Laneige',
        'lys' => 'Lys Beauty',
        'saie' => 'Saie',
        'salt and stone' => 'Salt & Stone',
    ];

    /**
     * Columns: [name, brand code, quantity, purchase price (закуп), delivery
     * cost (карго), website price (цена на сайте), selling price (цена
     * продажи), comment (комментарий)].
     *
     * A selling price of 0 means the spreadsheet left it blank (or held "?").
     * Rows repeating the same brand and name become separate receipts of one
     * product, which is how a product bought twice at different prices is
     * meant to be recorded.
     *
     * @var array<int, array{0: string, 1: string, 2: int, 3: float, 4: float, 5: float, 6: float, 7: ?string}>
     */
    private array $rows = [
        ['concelear double wear', 'esteelauder', 3, 19, 1, 38, 40, '1N, 2W, 2C'],
        ['beautiful magnolia 3 tali atir nabor', 'esteelauder', 2, 89.12, 9, 112, 120, '7556493050'],
        ['mini 4 tali gift nabor', 'esteelauder', 1, 28.39, 5.5, 48, 55, '98$ value'],
        ['Косметичка набор ок синий', 'esteelauder', 1, 0, 3, 0, 40, '7364004131'],
        ['futurist foundation', 'esteelauder', 1, 55, 2, 55, 57, '2n1'],
        ['double wear foundation', 'esteelauder', 1, 44.2, 2, 52, 55, '1n2'],
        ['futurist base', 'esteelauder', 1, 23, 1, 46, 50, null],
        ['nutritious lotion 100 ml', 'esteelauder', 1, 24, 4, 39, 39, null],
        ['soft clean cleanser', 'esteelauder', 2, 0, 2, 32, 35, null],
        ['double wear foundation pump', 'esteelauder', 1, 7, 1.5, 10, 12, null],
        ['day wear cream + advanced night repair 15ml each set', 'esteelauder', 2, 0, 2, 0, 24, null],
        ['multi effect cream + advanced night repair 15 ml each set', 'esteelauder', 1, 0, 2, 0, 24, null],
        ['revitalising supreme cream + advanced night repair 15 ml each set', 'esteelauder', 1, 0, 2, 0, 24, null],
        ['glow fixing spray', 'urban decay', 1, 17, 2, 34, 41, null],
        ['natural fixing spray mini', 'urban decay', 1, 10, 1, 18, 23, null],
        ['nude pallete', 'natasha denona', 1, 58.65, 4, 69, 77, null],
        ['face pallete', 'natasha denona', 3, 30, 2, 62, 47, null],
        ['contour stick', 'too faced', 1, 17.5, 2, 35, 25, 'chocolate mousse'],
        ['blush duo set', 'too faced', 3, 12, 2, 42, 25, null],
        ['cloudberry cream', 'glow recipe', 2, 15, 2, 42, 35, null],
        ['night cream', 'glow recipe', 1, 9, 2, 20, 20, null],
        ['watermelon lotion', 'glow recipe', 2, 15, 2, 42, 35, null],
        ['spf', 'glow recipe', 3, 12.5, 2, 35, 30, null],
        ['cream & serum set', 'glow recipe', 2, 33.6, 3, 42, 0, null],
        ['highlighter', 'make up by mario', 2, 15, 2, 34, 25, 'pearl'],
        ['original pallete', 'make up by mario', 1, 55.2, 2, 69, 77, null],
        ['cream blush', 'make up by mario', 1, 0, 2, 34, 30, 'barely blushing'],
        ['lip balm', 'make up by mario', 4, 14.5, 1, 26, 24, 'blush glow 2sht, cinamon glow 2 sht'],
        ['cleanser & cream set', 'fresh', 1, 20, 3, 33, 40, null],
        ['toner', 'rhode', 2, 25.6, 3, 32, 40, null],
        ['mini toner', 'rhode', 1, 16, 2, 20, 27, null],
        ['blush', 'rhode', 4, 21, 2, 25, 32, 'toasted teddy 2 ta, tan line, sleepy girl'],
        ['mini clay mask', 'caudalie', 2, 17.6, 1, 22, 0, null],
        ['Blesk', 'Gisou', 7, 14.9, 1.5, 28, 0, 'Honey gold, watermelon sugar 3sht., raspberry glow, glazed plum, peach'],
        ['Kotta set 4 tali', 'Gisou', 2, 69, 8, 75, 0, null],
        ['set of two base 50 ml', 'bobbi brown', 2, 69.7, 4, 82, 0, '36.85$ bittasi'],
        ['base 100 ml', 'bobbi brown', 1, 79.1, 4, 113, 0, null],
        ['mini trio set', 'bobbi brown', 2, 0, 1, 0, 0, null],
        ['base 50 ml', 'bobbi brown', 1, 48.3, 2, 69, 0, null],
        ['base 15 ml', 'bobbi brown', 1, 17.5, 1, 25, 0, null],
        ['face cream 50 ml', 'bobbi brown', 1, 0, 1, 69, 0, null],
        ['4 tali kotta 62 nabor', 'sol ded janerio', 2, 48, 18, 69, 0, null],
        ['3 tali kotta 40 nabor', 'sol ded janerio', 1, 34, 14.65, 0, 0, null],
        ['sumochkali 62 nabor', 'sol ded janerio', 1, 29.4, 7, 49, 0, null],
        ['butter 62 500ml', 'sol ded janerio', 1, 62.4, 10, 85, 0, null],
        ['lotion 62 400 ml', 'sol ded janerio', 1, 16, 6.6, 28, 0, null],
        ['dedozorant 40', 'sol ded janerio', 1, 16.2, 2, 18, 0, null],
        ['dedozorant 59', 'sol ded janerio', 1, 16.2, 2, 18, 0, null],
        ['mini nabor 62', 'sol ded janerio', 4, 20.4, 5, 34, 0, null],
        ['mini nabor 59', 'sol ded janerio', 1, 20.4, 5, 34, 0, null],
        ['mini nabor 40', 'sol ded janerio', 1, 20.4, 5, 34, 0, null],
        ['mini mist 62', 'sol ded janerio', 1, 18.2, 2, 26, 0, null],
        ['mini mist 68', 'sol ded janerio', 2, 18.2, 2, 26, 0, null],
        ['mini mist 76', 'sol ded janerio', 1, 18.2, 2, 26, 0, null],
        ['mini mist 40', 'sol ded janerio', 1, 18.2, 2, 26, 0, null],
        ['kotta mist 62', 'sol ded janerio', 3, 22.1, 4, 39, 0, null],
        ['kotta mist 68', 'sol ded janerio', 1, 27.3, 4, 39, 0, null],
        ['neroli dedozorant', 'salt and stone', 1, 18, 2, 20, 0, null],
        ['bergamot', 'salt and stone', 1, 18, 2, 20, 0, null],
        ['santal', 'salt and stone', 1, 18, 2, 20, 0, null],
        ['concelear', 'hourglass', 6, 30.4, 1, 38, 0, '1, 1.3 , 1.8, 2, 2.5, 3.3'],
        ['fixing spray', 'Onesize', 2, 27.2, 1.5, 34, 0, null],
        ['powder duo set', 'Onesize', 1, 25, 2, 38, 0, null],
        ['Lip liner', 'Onesize', 1, 14, 1, 14, 19, 'Shy but fly'],
        ['mini fixing spray', 'Onesize', 2, 14.4, 1, 18, 0, null],
        ['pressed powder', 'makeup forever', 1, 36, 2, 45, 0, '1n06, 1n10'],
        ['hyaluronic foundation', 'makeup forever', 1, 34.3, 2, 49, 0, null],
        ['hyaluronic foundation mini', 'makeup forever', 1, 16.1, 2, 23, 0, null],
        ['concelear', 'makeup forever', 1, 8, 1, 33, 29, '2,1(Y)'],
        ['powder', 'lys', 1, 12, 2, 26, 25, null],
        ['uxodoviy nabor', 'kiehls', 1, 31.5, 5, 45, 0, null],
        ['blesk', 'haus labs', 2, 18.2, 1, 26, 25, 'guava, cocoa'],
        ['pomada', 'kosas', 1, 9, 1, 26, 17, 'high cut'],
        ['full on blesk', 'buxom', 1, 9, 1, 26, 19, 'erin'],
        ['plump shot blesk', 'buxom', 1, 10, 1, 19, 19, 'Spellbound Pink'],
        ['blesk', 'laneige', 1, 17.6, 1, 22, 25, 'maple glaze'],
        ['mascara set', 'grande mascara', 1, 17.68, 2, 23, 25, null],
        ['sivorotka genefique 30 ml', 'lancome', 1, 0, 10, 65, 45, null],
        ['mascara set', 'lancome', 1, 24, 3, 30, 35, null],
        ['mascara idole', 'lancome', 2, 15, 2, 30, 30, null],
        ['mini eyeshadow & mascara', 'anastasia beverly hills', 1, 24.7, 3, 38, 35, null],
        ['stick blush & blesk', 'anastasia beverly hills', 1, 20.4, 2, 34, 30, null],
        ['glow foundation', 'saie', 1, 28, 2, 40, 35, '1'],
        ['mini eyeshadow', 'fenty', 1, 15, 2, 30, 24, '1 nature'],
        ['foundation', 'fenty', 1, 20, 2, 40, 37, '150'],
        ['concelear mini', 'fenty', 1, 10, 1, 16, 16, '120N'],
        ['pomada', 'fenty', 3, 13, 1, 27, 30, 'hibiscus, fenty glow, it\'s fussy'],
        ['Blesk', 'fenty', 1, 10, 1, 26, 20, 'Melon Chillz'],
        ['skin glow forever', 'dior', 2, 48, 2, 60, 67, '1N, 1,5N'],
        ['backstage eyeshadow 001', 'dior', 1, 44, 2, 55, 65, null],
        ['backstage eyeshadow 002', 'dior', 1, 44, 2, 55, 65, null],
        ['bardovaya sumochka brelok', 'vs', 1, 9.87, 2, 25, 20, null],
        ['vs strazali brelok', 'vs', 1, 11.39, 2, 25, 25, null],
        ['bomshell atir 50ml', 'vs', 1, 20.1, 5, 60, 60, 'W151076965'],
        ['tease atir 50 ml', 'vs', 1, 25.97, 5, 60, 60, 'W148588193'],
        ['very sexy orchid atir100 ml', 'vs', 1, 27.49, 7.6, 80, 70, 'W155830271'],
        ['bomshell wild flower 50 ml', 'vs', 2, 15.06, 5, 80, 70, null],
        ['bomshell soiree atir 50 ml', 'vs', 1, 20.37, 5, 60, 60, null],
        ['bomshell boquet 50 ml', 'vs', 3, 19.2, 5, 60, 60, null],
        ['bomshell amalfi atir 50 ml', 'vs', 1, 20.55, 5.5, 60, 60, null],
        ['bomshell midnight atir 7 ml', 'vs', 1, 8.32, 2, 20, 17, null],
        ['bomshell amalfi atir 7 ml', 'vs', 1, 8.32, 2, 20, 17, null],
        ['bomshell bronze atir 7 ml', 'vs', 1, 7.58, 2, 20, 17, null],
        ['bomshell soiree aitr 7 ml', 'vs', 1, 8.07, 2, 20, 17, null],
        ['bomshell boquet 7 ml', 'vs', 3, 9.9, 2, 20, 17, null],
        ['bomshell seduction 7ml', 'vs', 1, 9.9, 2, 20, 17, null],
        ['bomshell 7ml', 'vs', 1, 9.97, 2, 20, 17, null],
        ['blesk nabor', 'vs', 2, 9.44, 3, 25, 24, null],
        ['3 tali svechka nabor', 'vs', 1, 15.11, 5, 40, 40, null],
        ['peach mango 4 tali nabor', 'vs', 1, 14.76, 10.5, 40, 50, null],
        ['3 tali kokos nabor', 'vs', 1, 9.73, 4, 35, 39, null],
        ['3 tali klubnika nabor', 'vs', 1, 9.73, 4, 35, 39, null],
        ['3 tali vanila nabor', 'vs', 1, 12.92, 7.14, 35, 39, null],
        ['atir nabor 3 tali tease', 'vs', 1, 42.5, 13, 85, 80, 'W151997804'],
        ['atir nabor kotta korobka 3 tali tease', 'vs', 1, 30, 14.76, 85, 80, null],
        ['hibiscus 3 tali mini nabor', 'vs', 2, 24.1, 5, 20, 35, null],
        ['4 tali mist nabor shimmer', 'vs', 1, 12.22, 8, 35, 37, null],
        ['4 tali mist nabor', 'vs', 1, 10.53, 8, 35, 37, null],
        ['4 tali mist nabor organic', 'vs', 1, 12.05, 5, 38, 38, null],
        ['bomshell parfume & lotion nabor', 'vs', 2, 13.28, 3.5, 38, 35, null],
        ['tease parfume & lotion nabor', 'vs', 1, 18.5, 4, 38, 35, null],
        ['bare parfume & lotion nabor', 'vs', 1, 18.5, 4, 38, 35, null],
        ['qora 4 pcs kosmeticka', 'vs', 1, 27, 5, 55, 52, null],
        ['qora angel 2 pcs kosmeticka', 'vs', 1, 14.1, 2, 35, 31.5, null],
        ['oq bride kosmetichka', 'vs', 1, 16.91, 3, 40, 35, null],
        ['rozoviy poloska kosmetichka', 'vs', 1, 19.03, 3, 45, 40, null],
        ['oq qizil poloska pijama s dlinnim rukavom', 'vs', 2, 30, 4, 70, 55, 's regular, m regular'],
        ['rozoviy qizil poloska pijama s dlinnim rukavom', 'vs', 2, 28.72, 4.58, 70, 55, 'm long, l regular'],
        ['zelyonaya pijama s dlinnim rukavom', 'vs', 1, 27.45, 5.16, 70, 55, 'm short'],
        ['qizil futbolkali pijama', 'vs', 1, 31.75, 5, 65, 55, 'm regular'],
        ['oq futbolkali pijama', 'vs', 2, 20.38, 5, 70, 55, 'm regular, l regular'],
        ['oq vs nadpis s dlinnim rukavom pijama', 'vs', 1, 33.75, 5, 70, 55, 's regular'],
        ['rozoviy poloska s dlinnim rukavom pijama', 'vs', 2, 33.75, 5, 70, 55, 's regular, m regular'],
        ['qizil longsleeveli pijama', 'vs', 1, 13.36, 5, 50, 55, 's regular'],
        ['qizil odamchali s dlinnim rukavom pijama', 'vs', 1, 20.63, 5, 70, 55, 'm short'],
        ['qizil poloskali pijama s dlinnim rukavom', 'vs', 1, 20.62, 5, 70, 55, 'm regular'],
        ['rozoviy obtyashka s dlinnim rukavom pijama', 'vs', 2, 24.3, 5.5, 65, 55, 's short, m short'],
        ['qizil banniy xalat', 'vs', 1, 17.08, 9, 70, 55, 'm/l'],
        ['oq pink tapochka', 'vs', 1, 18.75, 4.5, 45, 35, 'm'],
        ['rozoviy pink tapochka', 'vs', 1, 14.5, 3.5, 45, 35, 's'],
        ['rozoviy vs tapochka', 'vs', 2, 17.86, 4, 45, 35, 'm, l'],
        ['qizil tapochka', 'vs', 2, 18.12, 4.3, 45, 35, '2 ta M, 1 L'],
        ['oq teddy sumka', 'vs', 1, 0, 4, 65, 35, null],
        ['oq rozoviy poloska sumka', 'vs', 1, 0, 5, 65, 35, null],
        ['5 pack logo cotton Hip huger pantie L', 'vs', 1, 15, 3, 28, 35, 'W152743832'],
        ['5 pack logo cotton Hip huger pantie M', 'vs', 1, 15, 3, 28, 35, 'W152743832'],
        ['5 pack seamless high leg M', 'vs', 1, 15, 3, 28, 35, 'W152743832'],
        ['5 pack seamless high leg L', 'vs', 1, 15, 3, 28, 35, 'W152743832'],
        ['5 pack qizil stringi S', 'vs', 1, 13.5, 1.5, 28, 35, null],
        ['5 pack qizil stringi M', 'vs', 1, 13.5, 1.5, 28, 35, null],
        ['5 pack goluboy stringi S', 'vs', 1, 16.62, 3, 28, 35, null],
        ['5 pack goluboy stringi M', 'vs', 1, 16.62, 3, 28, 35, null],
        ['tease mini mist', 'vs', 1, 4.27, 2, 15, 17.5, null],
        ['love lotion', 'vs', 1, 5.85, 4, 25, 25, null],
        ['bare rose lotion', 'vs', 1, 7.47, 4, 25, 25, null],
        ['bare rose mist', 'vs', 1, 7.47, 4, 25, 25, null],
        ['very sexy mist', 'vs', 2, 6.6, 4, 25, 25, null],
        ['very sexy lotion', 'vs', 2, 6.6, 4, 25, 25, null],
        ['bomshell mist', 'vs', 2, 10.43, 3.5, 25, 25, null],
        ['bomshell lotion', 'vs', 1, 5.85, 4, 25, 25, null],
        ['bomshell seduction lotion', 'vs', 1, 5.85, 4, 25, 25, null],
        ['pure seduction lotion', 'vs', 1, 5.5, 4, 20, 25, null],
        ['pure seduction mist', 'vs', 1, 5.5, 4, 20, 17, null],
        ['love spell mist', 'vs', 1, 5.5, 4, 20, 17, null],
        ['love spell lotion', 'vs', 1, 5.5, 4, 20, 17, null],
        ['aqua kiss mist', 'vs', 1, 0, 4, 20, 17, null],
        ['aqua kiss lotion', 'vs', 1, 0, 4, 20, 17, null],
        ['love spell joy shimmer mist', 'vs', 1, 4.79, 3, 20, 17, null],
        ['love spell joy lotion', 'vs', 1, 4.79, 3, 20, 17, null],
        ['velvet petals joy shimmer mist', 'vs', 1, 4.79, 3, 20, 17, null],
        ['velvet petals joy lotion', 'vs', 1, 4.79, 3, 20, 17, null],
        ['seaside surf mist', 'vs', 1, 4.3, 3, 20, 17, null],
        ['seaside surf lotion', 'vs', 1, 4.3, 3, 20, 17, null],
        ['bomshell midnight mist', 'vs', 1, 8.65, 4, 25, 25, null],
        ['bomshell midnight lotion', 'vs', 1, 8.65, 4, 25, 25, null],
        ['mango templation mist', 'vs', 1, 4.79, 3, 20, 17, null],
        ['mango templation lotion', 'vs', 1, 4.79, 3, 20, 17, null],
        ['vanilla orchid feel comfor mist', 'vs', 1, 10.02, 3, 23, 19, null],
        ['vanilla orchid feel comfort body wash', 'vs', 1, 10.02, 3, 23, 19, null],
        ['vanilla orchid feel comfort lotion', 'vs', 1, 4.26, 5, 23, 19, null],
        ['vanilla orchid shimmer oil', 'vs', 1, 4.3, 4, 23, 19, null],
        ['velvet petals shimmer lotion', 'vs', 1, 5, 3, 20, 17, null],
        ['velvet petals body wash', 'vs', 1, 5.99, 4, 20, 19, null],
        ['daring mist', 'vs', 1, 8.5, 3.5, 25, 25, null],
        ['daring lotion', 'vs', 1, 8.5, 3.5, 25, 25, null],
        ['bomshell soiree mist', 'vs', 1, 6.79, 3, 25, 25, null],
        ['lotus skrab', 'vs', 1, 6, 3, 20, 17, null],
        ['Bomshell amalfi lotion', 'vs', 1, 6.31, 3.25, 25, 25, null],
        ['Vibrant breeze lotion', 'vs', 1, 4.3, 3, 20, 17, null],
        ['nectur pulse mist', 'vs', 1, 6, 3, 20, 17, null],
        ['cucumber body wash', 'vs', 1, 6, 4, 20, 19, null],
        ['sheer love mist', 'vs', 1, 0, 4, 20, 19, null],
        ['cocount passion mist', 'vs', 1, 0, 5, 20, 17, null],
        ['coco chill mini scrub', 'vs', 1, 3, 2, 10, 10, null],
        ['pineapple highlighting body oil', 'vs', 2, 5.7, 3, 20, 17, null],
        ['cocount highlighting doy oil', 'vs', 2, 5.7, 3, 20, 17, null],
        ['collagen blesk i karandash nabor pillow talk medium', 'cht', 1, 18.75, 2, 25, 37, null],
        ['Kollagen trio blesk nabor', 'cht', 1, 15, 2, 25, 35, null],
        ['Magic cream 15 ml', 'cht', 2, 15, 1, 32, 32, null],
        ['Magic water cream 30 ml', 'cht', 2, 32.5, 2, 69, 55, null],
        ['Lip nabor 4 pcs pillow talk medium', 'cht', 1, 37.8, 3, 54, 57, null],
        ['Fiksator 34 ml', 'cht', 1, 0, 1.5, 25, 27, null],
        ['fiksator 100 ml', 'cht', 2, 39, 2, 39, 49, null],
        ['icon baby set pomada blesk', 'cht', 1, 22.4, 3, 28, 35, null],
        ['plumper lip set', 'cht', 3, 36.4, 3, 52, 68, null],
        ['blesk peach', 'cht', 2, 0, 2, 26, 20, null],
        ['primer spf', 'cht', 1, 0, 2, 55, 0, null],
        ['exagger mascara', 'cht', 1, 29, 2, 29, 35, null],
        ['exagger mascara mini', 'cht', 1, 0, 1, 16, 10, null],
        ['pomada matte pillow talk', 'cht', 1, 25, 2, 32, 38, null],
        ['yengi pomada pillow tak', 'cht', 1, 0, 2, 32, 32, null],
        ['mini set pomada i karandash pillow talk', 'cht', 1, 20.8, 2, 26, 35, null],
        ['blesk blondie', 'cht', 1, 0, 2, 28, 0, null],
        ['collagen blesk refresh rose', 'cht', 1, 18.5, 2, 37, 0, null],
        ['karandash dlya gub', 'cht', 3, 0, 1, 28, 0, 'pillow talk 2 ta, iconic nude'],
        ['karandash dlya glaz', 'cht', 1, 0, 1, 28, 0, 'pillow talk'],
        ['Matte liquid blush', 'cht', 2, 22.12, 1, 44, 0, 'Pillow talk, pink pop'],
        ['Blush highlighter 2 in 1', 'cht', 1, 21, 2, 44, 0, 'Peachgasm'],
        ['Highlighter liqiuid', 'cht', 4, 11, 2, 44, 0, 'Pillow talk 2 sht., spotlight 2'],
        ['contourwand', 'cht', 1, 22.12, 1, 42, 0, 'Fair-medium'],
        ['ledendary mascara', 'cht', 1, 14.5, 2, 29, 25, null],
        ['super star mascara', 'cht', 2, 15, 1, 29, 25, null],
        ['push up lashes mascara', 'cht', 3, 0, 2, 29, 35, '2 ta qora, 1 korichneviy'],
        ['liquid matte lipstick', 'cht', 2, 0, 2, 37, 0, '2 ta pillow talk'],
        ['suxoy blush', 'cht', 4, 16.5, 2, 43, 0, '2 ta pillow talk, first love, ecstasy'],
        ['suxoy highlighter', 'cht', 2, 0, 2, 50, 0, 'pillow talk, moonlight glow'],
        ['powder', 'cht', 2, 28.37, 2, 49, 0, 'oq'],
        ['blush & highlighter pallete', 'cht', 1, 14.5, 1, 29, 0, 'tan to deep'],
        ['pigment', 'cht', 1, 17, 1, 34, 0, 'diamond eyes, sunlit diamond'],
        ['magic vanish', 'cht', 3, 18, 2, 36, 0, 'fair'],
        ['eyeshadow pallete pillow talk', 'cht', 2, 31.9, 2, 58, 0, null],
        ['eyeshadow the goodness', 'cht', 1, 31.9, 2, 58, 0, null],
        ['eyes to mesmerise', 'cht', 7, 20.25, 1, 35, 0, '3 ta pillow talk, 1 champangne'],
        ['rozvaya kosmetichka', 'cht', 1, 7.91, 3, 45, 0, null],
        ['mini rozovie teni', 'beauty creations', 1, 8.78, 2, 10, 0, null],
        ['mini nyud teni', 'beauty creations', 2, 8.78, 2, 10, 0, null],
        ['powder', 'beauty creations', 1, 8, 2, 10, 16.2, null],
        ['powder set', 'beauty creations', 1, 9.6, 3, 12, 18.3, null],
        ['brush set', 'beauty creations', 1, 8, 2, 10, 12, null],
        ['liquid blush', 'beauty creations', 2, 6.4, 1, 8, 10, 'coral cup, pinky primrose'],
        ['liquid bronzer', 'beauty creations', 1, 6.4, 1, 8, 0, 'toffee butter'],
        ['fixing spray', 'huda beauty', 4, 19.76, 2, 31, 0, null],
        ['pressed powder', 'huda beauty', 2, 15.2, 2, 40, 0, 'cupcake'],
        ['powder', 'huda beauty', 3, 33.15, 3, 39, 0, 'cupcake, poundcake 2 ta'],
        ['matte lipstick', 'huda beauty', 3, 16.1, 1, 25, 25, null],
        ['mini blush & balm', 'nars', 3, 24, 3, 30, 0, null],
        ['luqiud blush & blesk', 'nars', 1, 25.6, 2, 32, 0, null],
        ['concelear liquid', 'nars', 3, 25.6, 1, 32, 0, 'creme brulee, 2ta vanilla'],
        ['concelear', 'nars', 2, 27.7, 1, 36, 0, 'vanilla'],
        ['blush', 'patrick ta', 2, 32, 2, 40, 0, 'she goes to the gym, she is that girl'],
        ['mini set powder & stick eyeshadow', 'laura mercier', 2, 21, 1, 30, 0, null],
        ['powder rozoviy krishka', 'laura mercier', 1, 33.6, 2, 48, 0, null],
        ['powder set', 'laura mercier', 1, 30.1, 5.4, 43, 0, 'qora krishkali'],
        ['mascara duo', 'ysl', 2, 40.55, 2, 46, 0, '36$ each'],
        ['parfume libre duo set', 'ysl', 1, 31, 3, 40, 0, null],
        ['parfume libre gift set', 'ysl', 1, 83.22, 5.5, 115, 0, null],
        ['juicy pallete', 'tarte', 1, 20, 3, 49, 0, null],
        ['in bloom pallete', 'tarte', 1, 39.2, 2, 49, 0, null],
        ['mascara set', 'tarte', 1, 24.81, 3, 45, 0, null],
        ['plumper & lip liner set', 'tarte', 2, 20.23, 1, 27, 0, 'primrose in bloom, primrose full bloom'],
        ['plumper set of 5', 'tarte', 1, 31.79, 2, 49, 0, '5'],
        ['tarte big set of 2', 'tarte', 2, 46.19, 4, 59, 0, '1 ta naborni ichida 1 tasi sotilgan, 1 tasida mascara sotilgan'],
        ['plumper big set', 'tarte', 1, 79.2, 6, 99, 0, '8ta plumper, 2 lip liner'],
        ['bej eyeliner', 'tarte', 1, 9, 1, 22, 0, null],
        ['bronzer', 'tarte', 1, 16, 1, 32, 0, 'hotel heirees'],
        ['blush', 'tarte', 1, 15, 1, 30, 0, 'rose'],
        ['concealer', 'tarte', 2, 25.6, 2, 32, 0, '12N, 20B'],
        ['mascara', 'sephora', 2, 8, 1, 16, 10, null],
        ['blush & brush set', 'sephora', 1, 23.2, 2, 29, 0, null],
        ['mascara set', 'sephora favorites', 1, 23.8, 1, 28, 0, '5 mascaras'],
        ['prep set', 'sephora favorites', 1, 37.8, 9, 54, 0, null],
        ['lip set', 'sephora favorites', 1, 29.4, 4, 42, 50, null],
        ['spf set', 'sephora favorites', 1, 31.2, 8, 40, 55.8, null],
        ['mist', 'rare beauty', 3, 14, 2.5, 28, 35.8, null],
        ['lotion', 'rare beauty', 4, 14, 4, 28, 36.6, null],
        ['blush', 'rare beauty', 2, 20, 2, 25, 32.9, 'happy, worth'],
        ['liquid highlighter', 'rare beauty', 1, 22.95, 2, 27, 36.6, 'mesmerize'],
        ['liquid contour', 'rare beauty', 1, 23.8, 2, 28, 37.5, 'solace'],
        ['lip oil', 'rare beauty', 1, 15.4, 1, 22, 25, 'happy'],
        ['eyeshadow stick', 'rare beauty', 1, 11, 1, 22, 20, 'well-being'],
        ['blush mini trio set', 'rare beauty', 1, 28.8, 3, 36, 42, 'worth'],
        ['mini cream duo set', 'clinique', 1, 14, 2, 14, 25.5, null],
        ['rozovaya kosmetichka nabor', 'clinique', 1, 0, 3, 45, 37.5, null],
        ['mosturizing lotion125 ml', 'clinique', 1, 17, 3, 34, 37.5, null],
        ['4 pcs eye liner', 'clinique', 2, 18.98, 2, 45, 56, '1 ta pachkada faqat korichneviy qogan, 83$ value'],
        ['clinique 5 pcs gift set', 'clinique', 1, 0, 2, 65, 37, null],
        ['moisturise surge 30 ml', 'clinique', 1, 25.5, 1, 30, 35, null],
        ['all about eyes 15ml', 'clinique', 1, 42, 2, 42, 39, null],
        ['light reflecting primer', 'clinique', 1, 0, 1, 40, 30, null],
        ['wrinkle correcting cream 5 ml', 'clinique', 1, 0, 1, 15, 0, null],
        ['wrinkle correcting eye cream 15 ml', 'clinique', 1, 0, 1, 66, 0, null],
        ['Your the one mist', 'bbw', 1, 6.9, 3.9, 20, 17, null],
        ['Your the one body cream', 'bbw', 1, 6.9, 3.9, 20, 17, null],
        ['Gingham gorgeous body cream', 'bbw', 1, 6.9, 3.9, 20, 17, null],
        ['In the stars body cream', 'bbw', 1, 6.9, 3.9, 20, 17, null],
        ['Covered in roses body cream', 'bbw', 1, 6.9, 3.9, 20, 17, null],
        ['Japanese cherry blossom body cream', 'bbw', 1, 6.9, 3.9, 20, 17, null],
        ['Thousand wishes body cream', 'bbw', 1, 6.9, 3.9, 20, 17, null],
        ['Champagne toast body wash', 'bbw', 1, 6.9, 3.9, 20, 17, null],
        ['Strawberry poundcake body lotion', 'bbw', 1, 6.9, 3.9, 20, 17, null],
        ['At the beach body cream', 'bbw', 1, 6.9, 3.9, 20, 17, null],
        ['Rapunzel body cream', 'bbw', 1, 6.9, 3.9, 20, 17, null],
        ['Belle body cream', 'bbw', 1, 6.9, 3.9, 20, 17, null],
        ['Japanese cherry blossom conditioner', 'bbw', 1, 4.2, 6.4, 25, 25, null],
        ['Thousand wishes conditioner', 'bbw', 1, 4.2, 6.4, 25, 25, null],
        ['Thousand wishes conditioner', 'bbw', 1, 4.2, 6.4, 25, 25, null],
        ['Skin glow', 'Shiseido', 1, 49, 2, 60, 0, null],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('product_receipts')->delete();
        DB::table('products')->delete();

        // Lower-cased brand name => id, to resolve the canonical names that
        // $brandCodeMap produces.
        $brandIds = Brand::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower($name) => $id])
            ->all();

        $categoryIds = Category::pluck('id', 'name_en')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower($name) => $id])
            ->all();

        if (! $categoryIds) {
            $this->command->error('No categories found. Please run CategorySeeder first.');

            return;
        }

        $skippedBrands = [];
        $uncategorized = [];
        $receiptCount = 0;

        foreach ($this->groupedRows() as $rows) {
            [$name, $brandCode] = $rows[0];

            $brandName = $this->brandCodeMap[$brandCode] ?? null;
            $brandId = $brandName ? ($brandIds[mb_strtolower($brandName)] ?? null) : null;

            if (! $brandId) {
                $skippedBrands[$brandCode] = true;
            }

            $categoryId = $this->resolveCategoryId($name, $brandCode, $categoryIds);

            if (! $categoryId) {
                $uncategorized[] = "{$name} ({$brandCode})";
            }

            // Catalog product. quantity and selling_price are caches that the
            // ProductReceipt observer fills in from the receipts below.
            $product = Product::create([
                'brand_id' => $brandId,
                'category_id' => $categoryId,
                'name' => $name,
                'sku' => null,
                'description' => null,
                'comment' => $this->mergeComments($rows),
                'quantity' => 0,
                'selling_price' => 0,
                'image' => null,
                'link' => null,
                'status' => 'in_stock',
            ]);

            foreach ($rows as [, , $quantity, $purchasePrice, $deliveryCost, $websitePrice, $sellingPrice]) {
                $product->receipts()->create([
                    'purchase_price' => $purchasePrice,
                    'delivery_cost' => $deliveryCost,
                    'markup' => $this->calculateMarkup($purchasePrice, $deliveryCost, $sellingPrice),
                    'selling_price' => $sellingPrice,
                    'website_price' => $websitePrice,
                    'quantity' => $quantity,
                    'received_at' => self::SNAPSHOT_DATE,
                ]);

                $receiptCount++;
            }
        }

        $products = count($this->groupedRows());
        $this->command->info("Created {$products} products and {$receiptCount} receipts from the 06 Oct snapshot.");

        if ($skippedBrands) {
            $this->command->warn('Brands not found (products left unlinked): '.implode(', ', array_keys($skippedBrands)));
        }

        if ($uncategorized) {
            $this->command->warn('No category: '.implode(', ', $uncategorized));
        }
    }

    /**
     * Group the rows by brand and name, so one product collects every receipt
     * that was recorded for it.
     *
     * @return array<string, array<int, array{0: string, 1: string, 2: int, 3: float, 4: float, 5: float, 6: float, 7: ?string}>>
     */
    private function groupedRows(): array
    {
        static $grouped = null;

        if ($grouped !== null) {
            return $grouped;
        }

        $grouped = [];

        foreach ($this->rows as $row) {
            $grouped[mb_strtolower($row[1].'|'.$row[0])][] = $row;
        }

        return $grouped;
    }

    /**
     * Keep every distinct comment recorded for a product's rows.
     *
     * @param  array<int, array{0: string, 1: string, 2: int, 3: float, 4: float, 5: float, 6: float, 7: ?string}>  $rows
     */
    private function mergeComments(array $rows): ?string
    {
        $comments = array_values(array_unique(array_filter(array_column($rows, 7))));

        return $comments ? implode(' | ', $comments) : null;
    }

    /**
     * Derive the markup percentage from the app's own selling-price formula:
     *   selling = purchase + purchase * markup/100 + delivery
     * => markup = (selling - delivery - purchase) / purchase * 100
     *
     * Returns 0 when it cannot be computed meaningfully: no purchase price, a
     * blank selling price, or a negative result (markup is an unsigned column).
     */
    private function calculateMarkup(float $purchasePrice, float $deliveryCost, float $sellingPrice): int
    {
        if ($purchasePrice <= 0 || $sellingPrice <= 0) {
            return 0;
        }

        $markup = (int) round((($sellingPrice - $deliveryCost - $purchasePrice) / $purchasePrice) * 100);

        return max(0, $markup);
    }

    /**
     * Pick a category from the product name. Exact `name|brand code`
     * exceptions are checked first; a product no rule matches gets no
     * category, so it can be assigned in the admin.
     */
    private function resolveCategoryId(string $name, string $brandCode, array $categoryIds): ?int
    {
        $n = mb_strtolower($name);

        $exceptions = [
            'mist|rare beauty' => null,          // setting spray: no category yet
            'primer spf|cht' => null,            // primer: no category yet
            'face pallete|natasha denona' => 'blush',
            'skin glow|shiseido' => null,        // unclear product, assigned in the admin
        ];

        $key = $n.'|'.mb_strtolower($brandCode);

        if (array_key_exists($key, $exceptions)) {
            return $categoryIds[$exceptions[$key]] ?? null;
        }

        $rules = [
            'lip liner' => 'lip pencil',
            'karandash dlya gub' => 'lip pencil',
            'eyeliner' => 'eyeliner',
            'eye liner' => 'eyeliner',
            'karandash dlya glaz' => 'eyeliner',
            'mascara' => 'mascara',
            'concelear' => 'concealer',
            'concealer' => 'concealer',
            'foundation' => 'foundation',
            'powder' => 'powder',
            'highlighter' => 'highlighter',
            'bronzer' => 'bronzer',
            'contour' => 'bronzer',
            'blush' => 'blush',
            'eyeshadow' => 'eyeshadow',
            'pallete' => 'eyeshadow',
            'teni' => 'eyeshadow',
            'blesk' => 'lip gloss',
            'plump' => 'lip gloss',
            'lip oil' => 'lip gloss',
            'lip balm' => 'lip gloss',
            'pomada' => 'lipstick',
            'lipstick' => 'lipstick',
            'sivorotka' => 'serum',
            'serum' => 'serum',
            'toner' => 'toner',
            'cleanser' => 'cleanser',
            'spf' => 'sunscreen',
            'mask' => 'face mask',
            'conditioner' => 'conditioner',
            'body wash' => 'body wash',
            'scrub' => 'body wash',
            'skrab' => 'body wash',
            'lotion' => 'body lotion',
            'butter' => 'body lotion',
            'body cream' => 'body lotion',
            'body oil' => 'body lotion',
            'doy oil' => 'body lotion',
            'atir' => 'perfume',
            'aitr' => 'perfume',
            'parfume' => 'perfume',
            'mist' => 'perfume',
            'shimmer oil' => 'body lotion',
            'moisturise' => 'moisturizer',
            'all about eyes' => 'moisturizer',
            'cream' => 'moisturizer',
            'eyes to mesmerise' => 'eyeshadow',
            'skin glow' => 'foundation',
            'brush' => 'wand',
            'wand' => 'wand',
            // Last, so "bomshell lotion" / "bomshell mist" match their own rules first.
            'bomshell' => 'perfume',
        ];

        foreach ($rules as $keyword => $catEn) {
            if (str_contains($n, $keyword) && isset($categoryIds[$catEn])) {
                return $categoryIds[$catEn];
            }
        }

        return null;
    }
}
