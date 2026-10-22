<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
     * The one stored format, so the same number can't be saved twice in
     * different spellings: Uzbek numbers become "+998 90 123 45 67", anything
     * else is kept as typed (trimmed), and empty becomes null.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 9) {
            $digits = '998'.$digits;
        }

        if (strlen($digits) !== 12 || ! str_starts_with($digits, '998')) {
            return $phone;
        }

        $s = substr($digits, 3);

        return sprintf('+998 %s %s %s %s', substr($s, 0, 2), substr($s, 2, 3), substr($s, 5, 2), substr($s, 7, 2));
    }

    protected function phoneNumber(): Attribute
    {
        return Attribute::set(fn (?string $value) => self::normalizePhone($value));
    }

    /** Find a customer by a phone number typed in any format. */
    public function scopeWherePhone(Builder $query, string $phone): Builder
    {
        return $query->where('phone_number', self::normalizePhone($phone));
    }
}
