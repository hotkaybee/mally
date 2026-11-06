<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ExchangeRateService;
use App\Services\StorefrontService;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * All catalogue cards must look the same: the "in stock" / "sold out" status
 * sits on the photo, never in the text block (that made in-stock cards taller
 * and pushed the "sold out" button up), and the action row has one height.
 */
class ProductCardLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app()->setLocale('ru');
        session(['mally_locale' => 'ru']);
        Cache::put(ExchangeRateService::CACHE_KEY, ['rate' => 12000, 'source' => 'cbu.uz', 'isLive' => true, 'updatedAt' => now()->toIso8601String()], 3600);
    }

    private function card(array $override = []): DOMXPath
    {
        $product = array_merge([
            'id' => 1, 'sku' => 'ML-CARD-1', 'title' => 'Lip balm', 'price' => 20.0,
            'brand' => 'Rhode', 'inStock' => true, 'image' => 'https://example.test/p.jpg',
        ], $override);

        $html = (string) $this->blade('<x-storefront.product-card :product="$p" />', ['p' => $product]);

        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>');

        return new DOMXPath($dom);
    }

    private function realProduct(string $sku, int $qty): Product
    {
        $product = Product::create([
            'brand_id' => Brand::firstOrCreate(['name' => 'Rhode'])->id,
            'category_id' => Category::firstOrCreate(['name_ru' => 'Макияж'], ['name_en' => 'Makeup', 'name_uz' => 'Makiyaj'])->id,
            'name' => 'Peptide lip tint '.$sku, 'sku' => $sku, 'quantity' => 0, 'selling_price' => 0, 'status' => 'in_stock',
        ]);
        $product->receipts()->create([
            'purchase_price' => 10, 'delivery_cost' => 2, 'markup' => 0, 'selling_price' => 25, 'website_price' => 0,
            'quantity' => $qty, 'received_at' => now()->subDay(),
        ]);

        return $product->fresh();
    }

    public function test_the_stock_status_is_on_the_photo_for_both_in_stock_and_sold_out_cards(): void
    {
        foreach ([true, false] as $inStock) {
            $x = $this->card(['inStock' => $inStock]);

            $badges = $x->query('//*[@data-stock-status]');
            $this->assertSame(1, $badges->length, 'exactly one status per card');
            $this->assertSame($inStock ? 'in_stock' : 'sold_out', $badges->item(0)->getAttribute('data-stock-status'));
            $this->assertSame(1, $x->query('//*[@data-stock-status]/ancestor::div[contains(@class, "aspect-square")]')->length, 'the status sits on the photo');
        }
    }

    public function test_the_text_block_does_not_repeat_the_status(): void
    {
        foreach ([true, false] as $inStock) {
            $x = $this->card(['inStock' => $inStock]);

            $this->assertSame(1, $x->query('//*[@data-card-info]')->length, 'the text block exists');
            $this->assertSame(0, $x->query('//*[@data-card-info]//*[@data-stock-status]')->length);
            $this->assertSame(0, $x->query('//*[@data-card-info]//*[contains(@class, "bg-emerald-50")]')->length);
        }
    }

    public function test_in_stock_and_sold_out_cards_have_the_same_structure(): void
    {
        $signature = function (DOMXPath $x): array {
            $info = $x->query('//*[@data-card-info]')->item(0);
            $tags = [];
            foreach ($info->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $tags[] = $child->nodeName;
                }
            }

            return $tags;
        };

        $this->assertSame($signature($this->card(['inStock' => true])), $signature($this->card(['inStock' => false])));
    }

    public function test_the_action_row_has_one_height_in_every_state(): void
    {
        $heights = [];

        $inStock = $this->card(['inStock' => true]);
        $heights['add'] = $inStock->query('//*[@data-card-action]')->item(0)->getAttribute('class');
        $soldOut = $this->card(['inStock' => false]);
        $heights['sold_out'] = $soldOut->query('//*[@data-card-action]')->item(0)->getAttribute('class');

        // In the cart: the counter row.
        $product = $this->realProduct('ML-CARD-CART', 5);
        app(StorefrontService::class)->addToCart('ML-CARD-CART');
        $inCart = $this->card(['sku' => 'ML-CARD-CART', 'id' => $product->id]);
        $heights['in_cart'] = $inCart->query('//*[@data-card-action]')->item(0)->getAttribute('class');

        foreach ($heights as $state => $class) {
            $this->assertMatchesRegularExpression('/\bh-10\b/', $class, "$state action row must be h-10");
        }
        $this->assertSame(3, count($heights));
    }

    public function test_the_discount_and_new_badges_stay_in_the_top_corner(): void
    {
        $x = $this->card(['isNew' => true, 'price' => 15.0, 'oldPrice' => 20.0]);

        $this->assertSame(2, $x->query('//*[@data-card-flags]//span')->length);
        $this->assertSame(0, $x->query('//*[@data-card-flags]//*[@data-stock-status]')->length);
    }
}
