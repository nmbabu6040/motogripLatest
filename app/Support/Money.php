<?php

namespace App\Support;

class Money
{
    public static function symbol(): string
    {
        return (string) Settings::get('erp', 'currency', '৳');
    }

    public static function format(float|int|string|null $amount): string
    {
        return self::symbol().' '.number_format((float) $amount, 2);
    }

    /** Whole number with thousands separators, for quantities. */
    public static function qty(int|float|null $n): string
    {
        return number_format((float) $n);
    }
}
