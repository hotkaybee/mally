<?php

namespace App\Models;

use App\Observers\ProductBatchObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(ProductBatchObserver::class)]
class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'purchase_price',
        'delivery_cost',
        'markup',
        'selling_price',
        'website_price',
        'quantity',
        'quantity_remaining',
        'received_at',
        'comment',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'delivery_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'website_price' => 'decimal:2',
        'markup' => 'integer',
        'quantity' => 'integer',
        'quantity_remaining' => 'integer',
        'received_at' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    /**
     * Landed cost of a single unit from this batch (закуп + карго).
     */
    public function getUnitCostAttribute(): float
    {
        return (float) $this->purchase_price + (float) $this->delivery_cost;
    }
}
