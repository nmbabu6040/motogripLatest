<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomepageController extends Controller
{
    /**
     * mode: text = title, text, bullet points and an optional photo
     *       cta  = title and text only          stats = title and "Label | Value" lines
     *       hero = title and text               data  = only the section title; the content comes from another admin page
     * where: [label, route name] telling the editor where the real content is managed.
     */
    public const SECTIONS = [
        'hero' => ['hero', ['Hero photo: Settings → Hero image', 'admin.settings.edit']],
        'introduction' => ['text', null],
        'featured_products' => ['data', ['Products', 'admin.products.index']],
        'product_categories' => ['data', ['Categories', 'admin.categories.index']],
        'automotive_solutions' => ['text', null],
        'industrial_solutions' => ['text', null],
        'benefits' => ['text', null],
        'why_choose' => ['text', null],
        'industries' => ['text', null],
        'resources' => ['data', ['Documents', 'admin.documents.index']],
        'dealer_recruitment' => ['cta', null],
        'statistics' => ['stats', null],
        'latest_articles' => ['data', ['Articles', 'admin.blog.index']],
        'faqs' => ['data', ['FAQs', 'admin.faqs.index']],
        'inquiry_cta' => ['cta', null],
        'newsletter' => ['data', ['Subscribers', 'admin.newsletter.index']],
        'social_links' => ['data', ['Settings → Social profiles', 'admin.settings.edit']],
    ];

    public function index()
    {
        return view('admin.homepage.index', ['sections' => DB::table('homepage_sections')->orderBy('sort_order')->get(), 'info' => self::SECTIONS]);
    }

    public function toggle(Request $request, string $key)
    {
        abort_unless(isset(self::SECTIONS[$key]), 404);
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
        abort_unless(isset(self::SECTIONS[$key]), 404);
        $content = $this->content($key);
        [$mode, $where] = self::SECTIONS[$key];

        // Turn saved points back into one line per point ("Title | Text").
        $lines = ['en' => [], 'bn' => []];
        foreach ($content['items'] ?? [] as $item) {
            foreach (['en', 'bn'] as $l) {
                $t = $item['title'][$l] ?? ''; $x = $item['text'][$l] ?? '';
                if ($t !== '' || $x !== '') {
                    $lines[$l][] = $x !== '' ? "{$t} | {$x}" : $t;
                }
            }
        }

        return view('admin.homepage.edit', ['key' => $key, 'mode' => $mode, 'where' => $where, 'content' => $content, 'pointsEn' => implode("\n", $lines['en']), 'pointsBn' => implode("\n", $lines['bn'])]);
    }

    public function update(Request $request, string $key)
    {
        abort_unless(isset(self::SECTIONS[$key]), 404);
        [$mode] = self::SECTIONS[$key];

        $d = $request->validate([
            'title.en' => ['nullable', 'string', 'max:160'], 'title.bn' => ['nullable', 'string', 'max:160'],
            'body.en' => ['nullable', 'string', 'max:3000'], 'body.bn' => ['nullable', 'string', 'max:3000'],
            'points_en' => ['nullable', 'string', 'max:4000'], 'points_bn' => ['nullable', 'string', 'max:4000'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        $clean = fn ($a) => array_filter($a ?? [], fn ($v) => $v !== null && $v !== '');
        $old = $this->content($key);

        $image = $old['image'] ?? null;
        if ($mode === 'text') {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = 'homepage/'.Str::random(32).'.'.$file->extension();
                Storage::disk('public')->putFileAs('', $file, $path);
                $this->deleteImage($image);
                $image = '/storage/'.$path;
            } elseif ($request->boolean('remove_image')) {
                $this->deleteImage($image);
                $image = null;
            }
        }

        $content = [
            'title' => $clean($d['title'] ?? []),
            'body' => in_array($mode, ['data', 'stats'], true) ? [] : $clean($d['body'] ?? []),
            'items' => in_array($mode, ['text', 'stats'], true) ? $this->points($d['points_en'] ?? '', $d['points_bn'] ?? '') : [],
            'image' => $image,
        ];

        DB::table('homepage_sections')->where('key', $key)->update(['content' => json_encode($content, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        activity('admin')->causedBy($request->user())->withProperties(['section' => $key])->log('homepage.updated');

        return redirect()->route('admin.homepage.index')->with('status', 'Section saved.');
    }

    private function content(string $key): array
    {
        return json_decode((string) DB::table('homepage_sections')->where('key', $key)->value('content'), true) ?: [];
    }

    private function deleteImage(?string $url): void
    {
        if ($url && str_starts_with($url, '/storage/homepage/')) {
            Storage::disk('public')->delete(Str::after($url, '/storage/'));
        }
    }

    /** @return string[] */
    private function lines(?string $text): array
    {
        return collect(preg_split('/\R/u', (string) $text))->map(fn ($l) => trim(preg_replace('/^[\s\-\*•]+/u', '', $l)))->filter()->take(12)->values()->all();
    }

    /** English and Bengali lines are paired by position. "Title | Text" splits into the two parts. */
    private function points(string $en, string $bn): array
    {
        $a = $this->lines($en);
        $b = $this->lines($bn);
        $split = fn (string $line) => array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
        $items = [];

        for ($i = 0, $n = max(count($a), count($b)); $i < $n; $i++) {
            [$tEn, $xEn] = $split($a[$i] ?? '');
            [$tBn, $xBn] = $split($b[$i] ?? '');
            $title = array_filter(['en' => $tEn, 'bn' => $tBn], fn ($v) => $v !== '');
            // The English description is reused for Bengali only when a Bengali title was actually written (e.g. statistics values).
            $text = array_filter(['en' => $xEn, 'bn' => $xBn !== '' ? $xBn : ($tBn !== '' ? $xEn : '')], fn ($v) => $v !== '');
            if ($title || $text) {
                $items[] = ['title' => $title, 'text' => $text];
            }
        }

        return $items;
    }
}
