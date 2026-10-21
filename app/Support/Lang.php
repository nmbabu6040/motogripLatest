<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/** Locale-aware URLs. English has no prefix; Bengali routes are named "bn.*" and live under /bn. */
class Lang
{
    public static function route(string $name, array $params = [], bool $absolute = true): string
    {
        $name = str_starts_with($name, 'bn.') ? substr($name, 3) : $name;

        return route(app()->getLocale() === 'bn' ? 'bn.'.$name : $name, $params, $absolute);
    }

    /** hreflang alternates for the current public route, or [] when it has no counterpart. */
    public static function alternates(): array
    {
        $route = request()->route();
        $name = $route?->getName();
        if (! $name || str_starts_with($name, 'admin.')) {
            return [];
        }
        $base = str_starts_with($name, 'bn.') ? substr($name, 3) : $name;
        if (! Route::has($base) || ! Route::has('bn.'.$base)) {
            return [];
        }
        $params = $route->parameters();

        return ['en' => route($base, $params), 'bn' => route('bn.'.$base, $params), 'x-default' => route($base, $params)];
    }

    /** Pick the current-locale string from a JSON translation array, falling back to English. */
    public static function pick(mixed $value): string
    {
        $arr = is_string($value) ? (json_decode($value, true) ?: ['en' => $value]) : (array) $value;

        return (string) ($arr[app()->getLocale()] ?? $arr['en'] ?? reset($arr) ?: '');
    }
}
