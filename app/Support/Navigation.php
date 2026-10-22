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

        if ($location !== 'header') {
            return [];
        }

        $item = fn (string $label, string $url, int $id) => ['label' => $label, 'url' => $url, 'new_tab' => false, 'parent_id' => null, 'id' => $id];
        $items = [$item(__('site.home'), Lang::route('home'), 0)];

        // "About us" appears only when a published page with that slug exists, so the link never leads nowhere.
        $about = Cache::remember('nav.about', 300, fn () => DB::table('pages')->whereNull('deleted_at')->where('status', 'published')->whereIn('slug', ['about-us', 'about'])->value('slug'));
        if ($about) {
            $items[] = $item(__('site.about'), Lang::route('page.show', ['page' => $about]), 6);
        }

        return array_merge($items, [
            $item(__('site.products'), Lang::route('catalog.index'), 1),
            $item(__('site.resources'), Lang::route('resources.index'), 2),
            $item(__('site.dealers'), Lang::route('dealers.index'), 3),
            $item(__('site.blog'), Lang::route('blog.index'), 4),
            $item(__('site.contact'), Lang::route('contact.create'), 5),
        ]);
    }

    public static function forget(): void
    {
        Cache::forget('menu.header');
        Cache::forget('menu.footer');
        Cache::forget('nav.about');
    }
}
