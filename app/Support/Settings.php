<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Admin-editable, non-secret site settings (brand, contact, SEO defaults, toggles).
 * Secrets (SMTP, API keys, backup passwords) belong in .env, never here.
 */
class Settings
{
    private const CACHE_KEY = 'settings.all';

    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        return self::all()[$group][$key] ?? $default;
    }

    public static function set(string $group, string $key, mixed $value, string $type = 'string'): void
    {
        DB::table('settings')->updateOrInsert(
            ['group' => $group, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value, 'type' => $type, 'updated_at' => now(), 'created_at' => now()]
        );
        Cache::forget(self::CACHE_KEY);
    }

    private static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $out = [];
            foreach (DB::table('settings')->get() as $row) {
                $out[$row->group][$row->key] = match ($row->type) {
                    'bool' => (bool) $row->value,
                    'int' => (int) $row->value,
                    'json' => json_decode($row->value, true),
                    default => $row->value,
                };
            }
            return $out;
        });
    }
}
