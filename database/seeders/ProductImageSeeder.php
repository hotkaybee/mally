<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

/**
 * Attaches the bundled photos in database/seeders/images/products to the
 * catalogue products, matched by brand and product name (see ProductSeeder).
 *
 * Only products that have never had photos are touched, so it is safe to run
 * on a live database: php artisan db:seed --class=ProductImageSeeder
 */
class ProductImageSeeder extends Seeder
{
    /**
     * "brand|product name" (lowercase) => photo file. Each photo is a
     * manufacturer or retailer product shot; sources.csv in the same folder
     * lists where it was taken from.
     */
    private const IMAGES = [
        'estée lauder|concelear double wear' => 'estee-lauder-concelear-double-wear.jpg',
        'estée lauder|futurist foundation' => 'estee-lauder-futurist-foundation.jpg',
        'estée lauder|double wear foundation' => 'estee-lauder-double-wear-foundation.jpg',
        'estée lauder|futurist base' => 'estee-lauder-futurist-base.jpg',
        'estée lauder|soft clean cleanser' => 'estee-lauder-soft-clean-cleanser.jpg',
        'estée lauder|double wear foundation pump' => 'estee-lauder-double-wear-foundation-pump.jpg',
        'urban decay|glow fixing spray' => 'urban-decay-glow-fixing-spray.jpg',
        'urban decay|natural fixing spray mini' => 'urban-decay-natural-fixing-spray-mini.jpg',
        'natasha denona|nude pallete' => 'natasha-denona-nude-pallete.jpg',
        'natasha denona|face pallete' => 'natasha-denona-face-pallete.jpg',
        'too faced|contour stick' => 'too-faced-contour-stick.jpg',
        'glow recipe|watermelon lotion' => 'glow-recipe-watermelon-lotion.jpg',
        'glow recipe|spf' => 'glow-recipe-spf.jpg',
        'makeup by mario|highlighter' => 'makeup-by-mario-highlighter.jpg',
        'makeup by mario|original pallete' => 'makeup-by-mario-original-pallete.jpg',
        'makeup by mario|cream blush' => 'makeup-by-mario-cream-blush.jpg',
        'makeup by mario|lip balm' => 'makeup-by-mario-lip-balm.jpg',
        'rhode|toner' => 'rhode-toner.jpg',
        'rhode|mini toner' => 'rhode-mini-toner.jpg',
        'rhode|blush' => 'rhode-blush.jpg',
        'gisou|blesk' => 'gisou-blesk.jpg',
        'bobbi brown|set of two base 50 ml' => 'bobbi-brown-set-of-two-base-50-ml.jpg',
        'bobbi brown|base 100 ml' => 'bobbi-brown-base-100-ml.jpg',
        'bobbi brown|base 50 ml' => 'bobbi-brown-base-50-ml.jpg',
        'bobbi brown|base 15 ml' => 'bobbi-brown-base-15-ml.jpg',
        'sol de janeiro|butter 62 500ml' => 'sol-de-janeiro-butter-62-500ml.jpg',
        'sol de janeiro|dedozorant 40' => 'sol-de-janeiro-dedozorant-40.jpg',
        'sol de janeiro|mini nabor 62' => 'sol-de-janeiro-mini-nabor-62.jpg',
        'sol de janeiro|mini mist 62' => 'sol-de-janeiro-mini-mist-62.jpg',
        'sol de janeiro|mini mist 40' => 'sol-de-janeiro-mini-mist-40.jpg',
        'sol de janeiro|kotta mist 62' => 'sol-de-janeiro-kotta-mist-62.jpg',
        'sol de janeiro|kotta mist 68' => 'sol-de-janeiro-kotta-mist-68.jpg',
        'salt & stone|bergamot' => 'salt-stone-bergamot.jpg',
        'salt & stone|santal' => 'salt-stone-santal.jpg',
        'hourglass|concelear' => 'hourglass-concelear.jpg',
        'one/size|fixing spray' => 'one-size-fixing-spray.jpg',
        'one/size|lip liner' => 'one-size-lip-liner.jpg',
        'one/size|mini fixing spray' => 'one-size-mini-fixing-spray.jpg',
        'make up for ever|pressed powder' => 'make-up-for-ever-pressed-powder.jpg',
        'make up for ever|hyaluronic foundation' => 'make-up-for-ever-hyaluronic-foundation.jpg',
        'make up for ever|hyaluronic foundation mini' => 'make-up-for-ever-hyaluronic-foundation-mini.jpg',
        'make up for ever|concelear' => 'make-up-for-ever-concelear.jpg',
        'lys beauty|powder' => 'lys-beauty-powder.jpg',
        'haus labs|blesk' => 'haus-labs-blesk.jpg',
        'kosas|pomada' => 'kosas-pomada.jpg',
        'laneige|blesk' => 'laneige-blesk.jpg',
        'lancome|sivorotka genefique 30 ml' => 'lancome-sivorotka-genefique-30-ml.jpg',
        'lancome|mascara idole' => 'lancome-mascara-idole.jpg',
        'saie|glow foundation' => 'saie-glow-foundation.jpg',
        'fenty beauty|foundation' => 'fenty-beauty-foundation.jpg',
        'fenty beauty|concelear mini' => 'fenty-beauty-concelear-mini.jpg',
        'fenty beauty|pomada' => 'fenty-beauty-pomada.jpg',
        'fenty beauty|blesk' => 'fenty-beauty-blesk.jpg',
        'dior|skin glow forever' => 'dior-skin-glow-forever.jpg',
        'dior|backstage eyeshadow 001' => 'dior-backstage-eyeshadow-001.jpg',
        'dior|backstage eyeshadow 002' => 'dior-backstage-eyeshadow-002.jpg',
        'victoria\'s secret|bomshell atir 50ml' => 'victoria-s-secret-bomshell-atir-50ml.jpg',
        'victoria\'s secret|tease atir 50 ml' => 'victoria-s-secret-tease-atir-50-ml.jpg',
        'victoria\'s secret|bomshell seduction 7ml' => 'victoria-s-secret-bomshell-seduction-7ml.jpg',
        'victoria\'s secret|bomshell 7ml' => 'victoria-s-secret-bomshell-7ml.jpg',
        'victoria\'s secret|atir nabor 3 tali tease' => 'victoria-s-secret-atir-nabor-3-tali-tease.jpg',
        'victoria\'s secret|5 pack logo cotton hip huger pantie l' => 'victoria-s-secret-5-pack-logo-cotton-hip-huger-pantie-l.jpg',
        'victoria\'s secret|5 pack logo cotton hip huger pantie m' => 'victoria-s-secret-5-pack-logo-cotton-hip-huger-pantie-m.jpg',
        'victoria\'s secret|5 pack seamless high leg m' => 'victoria-s-secret-5-pack-seamless-high-leg-m.jpg',
        'victoria\'s secret|5 pack seamless high leg l' => 'victoria-s-secret-5-pack-seamless-high-leg-l.jpg',
        'victoria\'s secret|tease mini mist' => 'victoria-s-secret-tease-mini-mist.jpg',
        'victoria\'s secret|bare rose lotion' => 'victoria-s-secret-bare-rose-lotion.jpg',
        'victoria\'s secret|bare rose mist' => 'victoria-s-secret-bare-rose-mist.jpg',
        'victoria\'s secret|very sexy mist' => 'victoria-s-secret-very-sexy-mist.jpg',
        'victoria\'s secret|very sexy lotion' => 'victoria-s-secret-very-sexy-lotion.jpg',
        'victoria\'s secret|bomshell mist' => 'victoria-s-secret-bomshell-mist.jpg',
        'victoria\'s secret|bomshell lotion' => 'victoria-s-secret-bomshell-lotion.jpg',
        'victoria\'s secret|bomshell seduction lotion' => 'victoria-s-secret-bomshell-seduction-lotion.jpg',
        'victoria\'s secret|pure seduction lotion' => 'victoria-s-secret-pure-seduction-lotion.jpg',
        'victoria\'s secret|pure seduction mist' => 'victoria-s-secret-pure-seduction-mist.jpg',
        'victoria\'s secret|love spell mist' => 'victoria-s-secret-love-spell-mist.jpg',
        'victoria\'s secret|love spell lotion' => 'victoria-s-secret-love-spell-lotion.jpg',
        'victoria\'s secret|aqua kiss mist' => 'victoria-s-secret-aqua-kiss-mist.jpg',
        'victoria\'s secret|aqua kiss lotion' => 'victoria-s-secret-aqua-kiss-lotion.jpg',
        'victoria\'s secret|love spell joy shimmer mist' => 'victoria-s-secret-love-spell-joy-shimmer-mist.jpg',
        'victoria\'s secret|love spell joy lotion' => 'victoria-s-secret-love-spell-joy-lotion.jpg',
        'victoria\'s secret|velvet petals joy shimmer mist' => 'victoria-s-secret-velvet-petals-joy-shimmer-mist.jpg',
        'victoria\'s secret|velvet petals joy lotion' => 'victoria-s-secret-velvet-petals-joy-lotion.jpg',
        'victoria\'s secret|sheer love mist' => 'victoria-s-secret-sheer-love-mist.jpg',
        'victoria\'s secret|cocount passion mist' => 'victoria-s-secret-cocount-passion-mist.jpg',
        'charlotte tilbury|magic cream 15 ml' => 'charlotte-tilbury-magic-cream-15-ml.jpg',
        'charlotte tilbury|magic water cream 30 ml' => 'charlotte-tilbury-magic-water-cream-30-ml.jpg',
        'charlotte tilbury|fiksator 34 ml' => 'charlotte-tilbury-fiksator-34-ml.jpg',
        'charlotte tilbury|fiksator 100 ml' => 'charlotte-tilbury-fiksator-100-ml.jpg',
        'charlotte tilbury|blesk peach' => 'charlotte-tilbury-blesk-peach.jpg',
        'charlotte tilbury|primer spf' => 'charlotte-tilbury-primer-spf.jpg',
        'charlotte tilbury|exagger mascara' => 'charlotte-tilbury-exagger-mascara.jpg',
        'charlotte tilbury|exagger mascara mini' => 'charlotte-tilbury-exagger-mascara-mini.jpg',
        'charlotte tilbury|pomada matte pillow talk' => 'charlotte-tilbury-pomada-matte-pillow-talk.jpg',
        'charlotte tilbury|yengi pomada pillow tak' => 'charlotte-tilbury-yengi-pomada-pillow-tak.jpg',
        'charlotte tilbury|blesk blondie' => 'charlotte-tilbury-blesk-blondie.jpg',
        'charlotte tilbury|collagen blesk refresh rose' => 'charlotte-tilbury-collagen-blesk-refresh-rose.jpg',
        'charlotte tilbury|karandash dlya gub' => 'charlotte-tilbury-karandash-dlya-gub.jpg',
        'charlotte tilbury|karandash dlya glaz' => 'charlotte-tilbury-karandash-dlya-glaz.jpg',
        'charlotte tilbury|matte liquid blush' => 'charlotte-tilbury-matte-liquid-blush.jpg',
        'charlotte tilbury|highlighter liqiuid' => 'charlotte-tilbury-highlighter-liqiuid.jpg',
        'charlotte tilbury|contourwand' => 'charlotte-tilbury-contourwand.jpg',
        'charlotte tilbury|push up lashes mascara' => 'charlotte-tilbury-push-up-lashes-mascara.jpg',
        'charlotte tilbury|liquid matte lipstick' => 'charlotte-tilbury-liquid-matte-lipstick.jpg',
        'charlotte tilbury|suxoy blush' => 'charlotte-tilbury-suxoy-blush.jpg',
        'charlotte tilbury|powder' => 'charlotte-tilbury-powder.jpg',
        'charlotte tilbury|magic vanish' => 'charlotte-tilbury-magic-vanish.jpg',
        'charlotte tilbury|eyeshadow pallete pillow talk' => 'charlotte-tilbury-eyeshadow-pallete-pillow-talk.jpg',
        'charlotte tilbury|eyeshadow the goodness' => 'charlotte-tilbury-eyeshadow-the-goodness.jpg',
        'charlotte tilbury|eyes to mesmerise' => 'charlotte-tilbury-eyes-to-mesmerise.jpg',
        'beauty creations|mini nyud teni' => 'beauty-creations-mini-nyud-teni.jpg',
        'beauty creations|powder' => 'beauty-creations-powder.jpg',
        'beauty creations|powder set' => 'beauty-creations-powder-set.jpg',
        'beauty creations|liquid blush' => 'beauty-creations-liquid-blush.jpg',
        'beauty creations|liquid bronzer' => 'beauty-creations-liquid-bronzer.jpg',
        'huda beauty|fixing spray' => 'huda-beauty-fixing-spray.jpg',
        'huda beauty|pressed powder' => 'huda-beauty-pressed-powder.jpg',
        'huda beauty|powder' => 'huda-beauty-powder.jpg',
        'huda beauty|matte lipstick' => 'huda-beauty-matte-lipstick.jpg',
        'nars cosmetics|concelear liquid' => 'nars-cosmetics-concelear-liquid.jpg',
        'nars cosmetics|concelear' => 'nars-cosmetics-concelear.jpg',
        'patrick ta|blush' => 'patrick-ta-blush.jpg',
        'laura mercier|powder rozoviy krishka' => 'laura-mercier-powder-rozoviy-krishka.jpg',
        'yves saint laurent|mascara duo' => 'yves-saint-laurent-mascara-duo.jpg',
        'tarte|in bloom pallete' => 'tarte-in-bloom-pallete.jpg',
        'tarte|bronzer' => 'tarte-bronzer.jpg',
        'tarte|blush' => 'tarte-blush.jpg',
        'tarte|concealer' => 'tarte-concealer.jpg',
        'sephora collection|mascara' => 'sephora-collection-mascara.jpg',
        'rare beauty|mist' => 'rare-beauty-mist.jpg',
        'rare beauty|lotion' => 'rare-beauty-lotion.jpg',
        'rare beauty|blush' => 'rare-beauty-blush.jpg',
        'rare beauty|liquid highlighter' => 'rare-beauty-liquid-highlighter.jpg',
        'rare beauty|liquid contour' => 'rare-beauty-liquid-contour.jpg',
        'rare beauty|lip oil' => 'rare-beauty-lip-oil.jpg',
        'clinique|mosturizing lotion125 ml' => 'clinique-mosturizing-lotion125-ml.jpg',
        'clinique|moisturise surge 30 ml' => 'clinique-moisturise-surge-30-ml.jpg',
        'clinique|all about eyes 15ml' => 'clinique-all-about-eyes-15ml.jpg',
        'clinique|light reflecting primer' => 'clinique-light-reflecting-primer.jpg',
        'clinique|wrinkle correcting cream 5 ml' => 'clinique-wrinkle-correcting-cream-5-ml.jpg',
        'clinique|wrinkle correcting eye cream 15 ml' => 'clinique-wrinkle-correcting-eye-cream-15-ml.jpg',
    ];

    public function run(): void
    {
        $attached = 0;

        Product::with('brand')->whereNull('images')->orderBy('id')->each(function (Product $product) use (&$attached) {
            $file = self::IMAGES[self::key((string) $product->brand?->name, $product->name)] ?? null;
            $source = $file ? database_path('seeders/images/products/'.$file) : null;

            if (! $source || ! is_file($source)) {
                return;
            }

            // A copy per product, so deleting one product's photo never removes another's.
            $path = Storage::disk(Product::IMAGE_DISK)->putFileAs(
                Product::IMAGE_DIRECTORY,
                new File($source),
                pathinfo($file, PATHINFO_FILENAME).'-'.$product->id.'.jpg',
            );

            $product->update(['images' => [$path]]);
            $attached++;
        });

        $this->command?->info("Product photos attached: {$attached}");
    }

    public static function key(string $brand, string $name): string
    {
        return mb_strtolower(trim($brand)).'|'.mb_strtolower(trim($name));
    }

    /** @return array<string, string> */
    public static function images(): array
    {
        return self::IMAGES;
    }
}
