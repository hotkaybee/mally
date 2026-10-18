<?php

namespace App\Support;

/**
 * Uzbek phone numbers: one canonical format and the other formats the same
 * number may have been stored in (admin input, API, older storefront code).
 */
final class PhoneNumber
{
    /** "+998 90 123 45 67", or null when it isn't a +998 number. */
    public static function normalize(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);
        if (strlen($digits) === 9) {
            $digits = '998'.$digits;
        }

        if (strlen($digits) !== 12 || ! str_starts_with($digits, '998')) {
            return null;
        }

        $s = substr($digits, 3);

        return sprintf('+998 %s %s %s %s', substr($s, 0, 2), substr($s, 2, 3), substr($s, 5, 2), substr($s, 7, 2));
    }

    /**
     * @return list<string>
     */
    public static function variants(string $phone): array
    {
        $normalized = self::normalize($phone);
        if ($normalized === null) {
            return [trim($phone)];
        }

        $s = substr(preg_replace('/\D/', '', $normalized), 3);
        [$op, $a, $b, $c] = [substr($s, 0, 2), substr($s, 2, 3), substr($s, 5, 2), substr($s, 7, 2)];

        return [
            $normalized,
            "+998{$s}",
            "998{$s}",
            $s,
            "+998 {$op} {$a}-{$b}-{$c}",
            "+998 ({$op}) {$a}-{$b}-{$c}",
            "+998-{$op}-{$a}-{$b}-{$c}",
            "998 {$op} {$a} {$b} {$c}",
        ];
    }
}
