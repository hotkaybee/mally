<?php

namespace App\Models;

use App\Observers\OrderProductObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

/**
 * order_product has its own auto-incrementing `id` and is edited directly via
 * the Order form's HasMany repeater, so it must behave as a normal model.
 * The AsPivot trait keeps it usable as the `using()` pivot on the
 * Order <-> Product belongsToMany relationships.
 */
#[ObservedBy(OrderProductObserver::class)]
class OrderProduct extends Model
{
    use AsPivot;

    protected $table = 'order_product';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_receipt_id',
        'quantity',
        'price',
        'unit_cost',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(ProductReceipt::class, 'product_receipt_id');
    }
}
