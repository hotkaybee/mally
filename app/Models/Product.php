<?php

namespace App\Models;

use App\Services\StorefrontService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    /**
     * `quantity` and `selling_price` are read-only caches maintained from the
     * product's receipts (see App\Services\InventoryService). Purchase price,
     * delivery cost, markup and website price now live on each ProductReceipt.
     */
    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'sku',
        'description',
        'comment',
        'quantity',
        'selling_price',
        'images',
        'link',
        'status',
    ];

    /** Product photos live on this disk under products/. */
    public const IMAGE_DISK = 'public';

    public const IMAGE_DIRECTORY = 'products';

    protected $casts = [
        'quantity' => 'integer',
        'selling_price' => 'decimal:2',
        'images' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => StorefrontService::forgetProductsCache());
        static::deleted(fn () => StorefrontService::forgetProductsCache());

        // Photos removed in the admin, or of a deleted product, are deleted from the disk.
        static::updated(function (Product $product) {
            if ($product->wasChanged('images')) {
                $old = json_decode((string) $product->getRawOriginal('images'), true) ?: [];
                self::deleteImageFiles(array_diff($old, $product->images ?? []));
            }
        });
        static::deleted(fn (Product $product) => self::deleteImageFiles($product->images ?? []));
    }

    /**
     * Public URLs of the product's photos, main photo first.
     *
     * @return array<int, string>
     */
    public function imageUrls(): array
    {
        return array_values(array_map(
            fn (string $path) => str_starts_with($path, 'http') ? $path : Storage::disk(self::IMAGE_DISK)->url($path),
            array_filter($this->images ?? [], fn ($path) => is_string($path) && $path !== ''),
        ));
    }

    public function mainImageUrl(): string
    {
        return $this->imageUrls()[0] ?? self::placeholderUrl();
    }

    /** @param  array<int, string>  $paths */
    private static function deleteImageFiles(array $paths): void
    {
        $paths = array_filter($paths, fn ($path) => is_string($path) && $path !== '' && ! str_starts_with($path, 'http'));

        if ($paths !== []) {
            Storage::disk(self::IMAGE_DISK)->delete(array_values($paths));
        }
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(ProductReceipt::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class)
            ->using(OrderProduct::class)
            ->withPivot(['quantity', 'price', 'total_price', 'product_receipt_id', 'unit_cost']);
    }

    public static function placeholderUrl(): string
    {
        return asset('images/product-placeholder.svg');
    }
}
