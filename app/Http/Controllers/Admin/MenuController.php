<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Navigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Lets the site owner choose which links the header and footer menus show, and in which order. */
class MenuController extends Controller
{
    public const LOCATIONS = ['header' => 'Header menu (top of every page)', 'footer' => 'Footer menu ("Company" column)'];

    /** Ready-made links. Only routes that exist on this site are offered. */
    public const ROUTES = [
        'home' => 'Home',
        'catalog.index' => 'Products',
        'resources.index' => 'Resources',
        'dealers.index' => 'Dealers',
        'blog.index' => 'Blog',
        'contact.create' => 'Contact',
        'dealer.apply' => 'Become a dealer',
        'partners.index' => 'Recommended products',
        'compare' => 'Compare products',
    ];

    private function menuId(string $location): int
    {
        DB::table('menus')->updateOrInsert(['location' => $location], ['name' => self::LOCATIONS[$location], 'updated_at' => now(), 'created_at' => now()]);

        return (int) DB::table('menus')->where('location', $location)->value('id');
    }

    public function index()
    {
        $menus = [];
        foreach (self::LOCATIONS as $location => $title) {
            $id = $this->menuId($location);
            $menus[$location] = ['title' => $title, 'items' => DB::table('menu_items')->where('menu_id', $id)->orderBy('sort_order')->orderBy('id')->get()];
        }

        return view('admin.menus.index', [
            'menus' => $menus,
            'routes' => collect(self::ROUTES)->filter(fn($l, $r) => Route::has($r))->all(),
            'pages' => DB::table('pages')->whereNull('deleted_at')->where('status', 'published')->orderBy('slug')->get(['slug', 'title']),
        ]);
    }

    public function create(Request $request)
    {
        $d = $this->validated($request);
        $id = $this->menuId($d['location']);
        DB::table('menu_items')->insert($this->row($d) + [
            'menu_id' => $id,
            'sort_order' => (int) DB::table('menu_items')->where('menu_id', $id)->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Navigation::forget();
        activity('admin')->causedBy($request->user())->withProperties(['location' => $d['location']])->log('menu.item_added');

        return back()->with('status', 'Menu link added.');
    }

    public function update(Request $request, int $item)
    {
        abort_unless(DB::table('menu_items')->where('id', $item)->exists(), 404);
        $d = $this->validated($request);
        DB::table('menu_items')->where('id', $item)->update($this->row($d) + ['updated_at' => now()]);
        Navigation::forget();

        return back()->with('status', 'Menu link saved.');
    }

    public function toggle(int $item)
    {
        abort_unless(DB::table('menu_items')->where('id', $item)->exists(), 404);
        DB::table('menu_items')->where('id', $item)->update(['is_active' => DB::raw('NOT is_active'), 'updated_at' => now()]);
        Navigation::forget();

        return back();
    }

    public function move(Request $request, int $item)
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];
        $row = DB::table('menu_items')->where('id', $item)->first();
        abort_unless($row, 404);

        $ids = DB::table('menu_items')->where('menu_id', $row->menu_id)->orderBy('sort_order')->orderBy('id')->pluck('id')->all();
        $i = array_search($item, $ids, true);
        $j = $direction === 'up' ? $i - 1 : $i + 1;
        if (isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            DB::transaction(function () use ($ids) {
                foreach ($ids as $n => $id) {
                    DB::table('menu_items')->where('id', $id)->update(['sort_order' => $n]);
                }
            });
            Navigation::forget();
        }

        return back();
    }

    public function destroy(int $item)
    {
        DB::table('menu_items')->where('id', $item)->delete();
        Navigation::forget();

        return back()->with('status', 'Menu link removed.');
    }


    /** Puts the standard links in, so they can be edited one by one. */
    public function defaults(Request $request, string $location)
    {
        abort_unless(array_key_exists($location, self::LOCATIONS), 404);

        $id = $this->menuId($location);
        DB::table('menu_items')->where('menu_id', $id)->delete();

        $about = DB::table('pages')
            ->whereNull('deleted_at')
            ->where('status', 'published')
            ->whereIn('slug', ['about-us', 'about'])
            ->first(['slug', 'title']);

        $links = [['route', 'home', 'home']];

        if ($about) {
            $links[] = ['page', $about->slug, $about->title];
        }

        foreach (
            [
                'catalog.index' => 'products',
                'resources.index' => 'resources',
                'dealers.index' => 'dealers',
                'blog.index' => 'blog',
                'contact.create' => 'contact',
            ] as $route => $key
        ) {
            if ($location === 'footer' && $route === 'catalog.index') {
                continue;
            }

            $links[] = ['route', $route, $key];
        }

        foreach ($links as $n => [$kind, $value, $label]) {
            DB::table('menu_items')->insert([
                'menu_id' => $id,
                'sort_order' => $n,
                'is_active' => true,
                'opens_in_new_tab' => false,
                'created_at' => now(),
                'updated_at' => now(),
                'label' => $kind === 'page'
                    ? $label
                    : json_encode([
                        'en' => __('site.' . $label, [], 'en'),
                        'bn' => __('site.' . $label, [], 'bn'),
                    ], JSON_UNESCAPED_UNICODE),
                'route_name' => $kind === 'route' ? $value : null,
                'url' => $kind === 'page' ? '/' . $value : null,
            ]);
        }

        Navigation::forget();

        activity('admin')
            ->causedBy($request->user())
            ->withProperties(['location' => $location])
            ->log('menu.defaults_loaded');

        return back()->with(
            'status',
            'Standard links added. Change, hide, remove or reorder them below.'
        );
    }

    /** Back to the automatic menu (the site decides what to show). */
    public function reset(Request $request, string $location)
    {
        abort_unless(array_key_exists($location, self::LOCATIONS), 404);

        DB::table('menu_items')
            ->where('menu_id', $this->menuId($location))
            ->delete();

        Navigation::forget();

        activity('admin')
            ->causedBy($request->user())
            ->withProperties(['location' => $location])
            ->log('menu.reset');

        return back()->with('status', 'This menu is automatic again.');
    }


    private function validated(Request $request): array
    {
        $d = $request->validate([
            'location' => ['required', Rule::in(array_keys(self::LOCATIONS))],
            'label.en' => ['required', 'string', 'max:60'],
            'label.bn' => ['nullable', 'string', 'max:60'],
            'target' => ['required', 'string', 'max:200'],
            'custom_url' => ['nullable', 'string', 'max:300'],
        ]);

        [$kind, $value] = array_pad(explode(':', $d['target'], 2), 2, null);
        if ($kind === 'route' && ! isset(self::ROUTES[$value])) {
            throw ValidationException::withMessages(['target' => 'Choose a link from the list.']);
        }
        if ($kind === 'page' && ! DB::table('pages')->where('slug', $value)->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages(['target' => 'That page does not exist.']);
        }

        if ($d['target'] === 'custom') {
            $url = trim((string) ($d['custom_url'] ?? ''));

            $isLocalPath = preg_match(
                '#^/(?!/)[A-Za-z0-9._~!$&\'()*+,;=:@%/?\#-]*$#',
                $url
            );

            $isHttpsUrl = preg_match(
                '#^https://[^\s]+$#i',
                $url
            );

            if (! $isLocalPath && ! $isHttpsUrl) {
                throw ValidationException::withMessages([
                    'custom_url' => 'Write a path that starts with / (for example /warranty) or a full link that starts with https://',
                ]);
            }

            $d['custom_url'] = $url;
        } elseif (! in_array($kind, ['route', 'page'], true)) {
            throw ValidationException::withMessages(['target' => 'Choose a link from the list.']);
        }

        $d['kind'] = $kind;
        $d['value'] = $value;
        $d['new_tab'] = $request->boolean('new_tab');
        $d['active'] = $request->boolean('is_active', true);

        return $d;
    }

    private function row(array $d): array
    {
        return [
            'label' => json_encode(array_filter($d['label'], fn($v) => $v !== null && $v !== ''), JSON_UNESCAPED_UNICODE),
            'route_name' => $d['kind'] === 'route' ? $d['value'] : null,
            'url' => $d['kind'] === 'page' ? '/' . $d['value'] : ($d['target'] === 'custom' ? $d['custom_url'] : null),
            'opens_in_new_tab' => $d['new_tab'],
            'is_active' => $d['active'],
        ];
    }
}
