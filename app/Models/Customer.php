<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
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

    /**
     * Match an Uzbek phone number in any of the formats it may have been
     * saved in (admin input, API, storefront).
     */
    public function scopeWherePhone(Builder $query, string $phone): Builder
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 9) {
            $digits = '998'.$digits;
        }

        if (strlen($digits) !== 12) {
            return $query->where('phone_number', trim($phone));
        }

        $s = substr($digits, 3);
        [$op, $a, $b, $c] = [substr($s, 0, 2), substr($s, 2, 3), substr($s, 5, 2), substr($s, 7, 2)];

        return $query->whereIn('phone_number', [
            "+998 {$op} {$a} {$b} {$c}",
            "+998{$s}",
            "998{$s}",
            $s,
            "+998 {$op} {$a}-{$b}-{$c}",
            "+998 ({$op}) {$a}-{$b}-{$c}",
            "+998-{$op}-{$a}-{$b}-{$c}",
            "998 {$op} {$a} {$b} {$c}",
        ]);
    }
}
