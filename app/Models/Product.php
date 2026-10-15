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
        'image',
        'link',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'selling_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => StorefrontService::forgetProductsCache());
        static::deleted(fn () => StorefrontService::forgetProductsCache());

        // A product that was sold stays: deleting it would strip the name
        // from past orders. Mark it out of stock instead.
        static::deleting(fn (Product $product) => ! OrderProduct::where('product_id', $product->id)->exists());
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

    /**
     * Public URL of the product photo: an external link as is, a file
     * uploaded in the admin (public disk), or a legacy path under public/.
     */
    public function imageUrl(): string
    {
        $image = trim((string) $this->image);

        if ($image === '') {
            return self::placeholderUrl();
        }

        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }

        $path = ltrim($image, '/');

        return Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : asset($path);
    }

    public static function placeholderUrl(): string
    {
        return asset('images/product-placeholder.svg');
    }
}
