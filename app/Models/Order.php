<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(OrderObserver::class)]
class Order extends Model
{
    use HasFactory;

    /** Customer-facing order number = id + NUMBER_OFFSET (not stored). */
    public const NUMBER_OFFSET = 1000;

    protected $fillable = [
        'customer_id',
        'secure_hash',
        'customer_name',
        'customer_phone',
        'city',
        'address',
        'comment',
        'promo_code',
        'discount_amount',
        'amount',
        'amount_paid',
        'exchange_rate',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
        ];
    }

    protected function orderNumber(): Attribute
    {
        return Attribute::get(fn () => $this->id ? $this->id + self::NUMBER_OFFSET : null);
    }

    /** So'm total at the rate saved with the order (not stored). */
    protected function amountUzs(): Attribute
    {
        return Attribute::get(fn () => (float) $this->exchange_rate > 0
            ? (int) round((float) $this->amount * (float) $this->exchange_rate)
            : null);
    }

    /**
     * Sets the USD total from the order's own lines minus the discount saved
     * with it. The admin form never sends a total of its own.
     */
    public function recalculateAmount(): void
    {
        $subtotal = (float) $this->orderProducts()->sum('total_price');

        $this->forceFill(['amount' => max(0, round($subtotal - (float) $this->discount_amount, 2))])->save();
    }

    public function isPaid(): bool
    {
        return (float) $this->amount > 0 && (float) $this->amount_paid >= (float) $this->amount;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    /**
     * Where the order is for the customer: placed, paid, packing, shipped,
     * delivered or cancelled. "Paid" is a placed order whose payment covers it.
     */
    public function progressStep(): string
    {
        return match ($this->status) {
            OrderStatus::Processing => $this->isPaid() ? 'paid' : 'placed',
            default => $this->status->value,
        };
    }

    public function progressLabel(): string
    {
        return __('order_status.'.$this->progressStep());
    }

    /** A cancelled order can be brought back only while its receipts still have its units. */
    public function hasStockToRestore(): bool
    {
        return $this->orderProducts()
            ->whereNotNull('product_receipt_id')
            ->selectRaw('product_receipt_id, SUM(quantity) as needed')
            ->groupBy('product_receipt_id')
            ->get()
            ->every(function ($row) {
                $receipt = ProductReceipt::find($row->product_receipt_id);

                // A deleted receipt is skipped by InventoryService::consume() too.
                return ! $receipt || $receipt->quantity_remaining >= (int) $row->needed;
            });
    }

    /** Find an order by its customer-facing number. */
    #[Scope]
    protected function withNumber(Builder $query, int $number): void
    {
        $query->whereKey($number - self::NUMBER_OFFSET);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->using(OrderProduct::class)
            ->withPivot(['quantity', 'price', 'total_price', 'product_receipt_id', 'unit_cost']);
    }

    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }
}
