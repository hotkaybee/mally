<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'product_id',
        'order_id',
        'rating',
        'comment',
        'photos',
        'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'photos' => 'array',
            'is_approved' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Public name: "Aziza K." */
    protected function authorName(): Attribute
    {
        return Attribute::get(function () {
            $customer = $this->order?->customer;
            $first = trim((string) $customer?->first_name);
            $last = trim((string) $customer?->last_name);

            if ($first === '') {
                return __('Mally customer');
            }

            return $last === '' ? $first : $first.' '.mb_substr($last, 0, 1).'.';
        });
    }

    /** The author's own city, if they have one (no default). */
    protected function authorCity(): Attribute
    {
        return Attribute::get(fn () => trim((string) $this->order?->customer?->city) ?: null);
    }
}
