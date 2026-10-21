<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Navigation
{
    /** Items from the admin-managed menu, or a default set that only links to pages that exist. */
    public static function items(string $location): array
    {
        $rows = Cache::remember("menu.{$location}", 300, function () use ($location) {
            $menu = DB::table('menus')->where('location', $location)->first();

            return $menu
                ? DB::table('menu_items')->where('menu_id', $menu->id)->where('is_active', true)->orderBy('sort_order')->get()->all()
                : [];
        });

        if ($rows) {
            return collect($rows)->map(fn ($r) => [
                'label' => Lang::pick($r->label),
                'url' => $r->route_name ? Lang::route($r->route_name) : (string) $r->url,
                'new_tab' => (bool) $r->opens_in_new_tab,
                'parent_id' => $r->parent_id,
                'id' => $r->id,
            ])->all();
        }

        return $location === 'header' ? [
            ['label' => __('site.products'), 'url' => Lang::route('catalog.index'), 'new_tab' => false, 'parent_id' => null, 'id' => 1],
            ['label' => __('site.resources'), 'url' => Lang::route('resources.index'), 'new_tab' => false, 'parent_id' => null, 'id' => 2],
            ['label' => __('site.dealers'), 'url' => Lang::route('dealers.index'), 'new_tab' => false, 'parent_id' => null, 'id' => 3],
            ['label' => __('site.blog'), 'url' => Lang::route('blog.index'), 'new_tab' => false, 'parent_id' => null, 'id' => 4],
            ['label' => __('site.contact'), 'url' => Lang::route('contact.create'), 'new_tab' => false, 'parent_id' => null, 'id' => 5],
        ] : [];
    }

    public static function forget(): void
    {
        Cache::forget('menu.header');
        Cache::forget('menu.footer');
    }
}
