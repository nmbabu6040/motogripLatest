<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomepageController extends Controller
{
    private const EDITABLE = ['hero', 'introduction', 'featured_products', 'product_categories', 'automotive_solutions', 'industrial_solutions', 'benefits', 'why_choose', 'industries', 'resources', 'dealer_recruitment', 'statistics', 'latest_articles', 'faqs', 'inquiry_cta', 'newsletter', 'social_links'];

    public function index()
    {
        return view('admin.homepage.index', ['sections' => DB::table('homepage_sections')->orderBy('sort_order')->get()]);
    }

    public function toggle(Request $request, string $key)
    {
        abort_unless(in_array($key, self::EDITABLE, true), 404);
        DB::table('homepage_sections')->where('key', $key)->update(['is_enabled' => DB::raw('NOT is_enabled'), 'updated_at' => now()]);
        activity('admin')->causedBy($request->user())->withProperties(['section' => $key])->log('homepage.toggled');

        return back();
    }

    public function move(Request $request, string $key)
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];
        $rows = DB::table('homepage_sections')->orderBy('sort_order')->orderBy('id')->pluck('key')->all();
        $i = array_search($key, $rows, true);
        abort_if($i === false, 404);
        $j = $direction === 'up' ? $i - 1 : $i + 1;

        if (isset($rows[$j])) {
            [$rows[$i], $rows[$j]] = [$rows[$j], $rows[$i]];
            DB::transaction(function () use ($rows) {
                foreach ($rows as $n => $k) {
                    DB::table('homepage_sections')->where('key', $k)->update(['sort_order' => $n]);
                }
            });
        }

        return back();
    }

    public function edit(string $key)
    {
        abort_unless(in_array($key, self::EDITABLE, true), 404);
        $row = DB::table('homepage_sections')->where('key', $key)->firstOrFail();

        return view('admin.homepage.edit', ['key' => $key, 'content' => json_decode($row->content ?? '[]', true) ?: []]);
    }

    public function update(Request $request, string $key)
    {
        abort_unless(in_array($key, self::EDITABLE, true), 404);

        $d = $request->validate([
            'title.en' => ['nullable', 'string', 'max:160'], 'title.bn' => ['nullable', 'string', 'max:160'],
            'body.en' => ['nullable', 'string', 'max:3000'], 'body.bn' => ['nullable', 'string', 'max:3000'],
            'items' => ['array', 'max:12'],
            'items.*.title_en' => ['nullable', 'string', 'max:160'], 'items.*.title_bn' => ['nullable', 'string', 'max:160'],
            'items.*.text_en' => ['nullable', 'string', 'max:500'], 'items.*.text_bn' => ['nullable', 'string', 'max:500'],
        ]);
        $clean = fn ($a) => array_filter($a ?? [], fn ($v) => $v !== null && $v !== '');

        $content = [
            'title' => $clean($d['title'] ?? []), 'body' => $clean($d['body'] ?? []),
            'items' => collect($d['items'] ?? [])->map(fn ($i) => ['title' => $clean(['en' => $i['title_en'] ?? null, 'bn' => $i['title_bn'] ?? null]), 'text' => $clean(['en' => $i['text_en'] ?? null, 'bn' => $i['text_bn'] ?? null])])
                ->filter(fn ($i) => $i['title'] || $i['text'])->values()->all(),
        ];

        DB::table('homepage_sections')->where('key', $key)->update(['content' => json_encode($content, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        activity('admin')->causedBy($request->user())->withProperties(['section' => $key])->log('homepage.updated');

        return redirect()->route('admin.homepage.index')->with('status', 'Section saved.');
    }
}
