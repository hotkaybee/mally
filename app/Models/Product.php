<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * `quantity` and `selling_price` are read-only caches maintained from the
     * product's batches (see App\Services\InventoryService). Purchase price,
     * delivery cost, markup and website price now live on each ProductBatch.
     */
    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
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

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class)
            ->using(OrderProduct::class)
            ->withPivot(['quantity', 'price', 'total_price', 'product_batch_id', 'unit_cost']);
    }
}
