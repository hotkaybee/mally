<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'city',
        'address',
        'telegram',
        'instagram',
        'source',
    ];

    /** The storefront login this customer belongs to (null for admin/API-only customers). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Match an Uzbek phone number in any of the formats it may have been
     * saved in (admin input, API, storefront).
     */
    public function scopeWherePhone(Builder $query, string $phone): Builder
    {
        return $query->whereIn('phone_number', PhoneNumber::variants($phone));
    }
}
