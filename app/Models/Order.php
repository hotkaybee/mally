<?php

namespace App\Models;

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

    public function isPaid(): bool
    {
        return (float) $this->amount > 0 && (float) $this->amount_paid >= (float) $this->amount;
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
