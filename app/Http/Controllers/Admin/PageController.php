<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\Navigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Mews\Purifier\Facades\Purifier;

class PageController extends Controller
{
    public function index()
    {
        return view('admin.pages.index', ['pages' => Page::orderBy('slug')->get()]);
    }

    public function create()
    {
        return view('admin.pages.form', ['page' => new Page(['status' => 'published', 'layout' => 'plain'])]);
    }

    public function store(Request $request)
    {
        $page = Page::create($this->data($request, new Page));
        activity('admin')->causedBy($request->user())->performedOn($page)->log('page.created');
        Navigation::forget();

        return redirect()->route('admin.pages.index')->with('status', 'Page saved.');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $page->update($this->data($request, $page));
        activity('admin')->causedBy($request->user())->performedOn($page)->log('page.updated');
        Navigation::forget();

        return redirect()->route('admin.pages.index')->with('status', 'Page saved.');
    }

    public function destroy(Request $request, Page $page)
    {
        activity('admin')->causedBy($request->user())->performedOn($page)->log('page.deleted');
        $page->delete();
        Navigation::forget();

        return redirect()->route('admin.pages.index')->with('status', 'Page deleted.');
    }

    private function data(Request $request, Page $page): array
    {
        $d = $request->validate([
            'slug' => ['nullable', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', 'max:120', Rule::unique('pages', 'slug')->ignore($page->id), Rule::notIn(['admin', 'login', 'logout', 'user', 'documents', 'newsletter', 'up', 'bn', 'sitemap', 'robots', 'ads', 'health', 'affiliate'])],
            'title.en' => ['required', 'string', 'max:160'], 'title.bn' => ['nullable', 'string', 'max:160'],
            'subtitle.en' => ['nullable', 'string', 'max:200'], 'subtitle.bn' => ['nullable', 'string', 'max:200'],
            'body.en' => ['nullable', 'string', 'max:60000'], 'body.bn' => ['nullable', 'string', 'max:60000'],
            'seo_title.en' => ['nullable', 'string', 'max:70'], 'seo_description.en' => ['nullable', 'string', 'max:170'], 'seo_description.bn' => ['nullable', 'string', 'max:170'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'layout' => ['required', Rule::in(array_keys(Page::LAYOUTS))],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        $clean = fn ($a) => array_filter($a ?? [], fn ($v) => $v !== null && $v !== '');

        // Image: keep the current one, replace it, or remove it.
        $image = $page->image;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = 'pages/'.Str::random(32).'.'.$file->extension();
            Storage::disk('public')->putFileAs('', $file, $path);
            $this->deleteImage($image);
            $image = '/storage/'.$path;
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($image);
            $image = null;
        }

        return [
            'slug' => $d['slug'] ?? $page->slug ?? Str::slug($d['title']['en']),
            'title' => $clean($d['title']),
            'subtitle' => $clean($d['subtitle'] ?? []),
            'body' => $clean(array_map(fn ($h) => $h === null ? null : Purifier::clean($h), $d['body'] ?? [])),   // HTML is sanitised on save
            'seo_title' => $clean($d['seo_title'] ?? []), 'seo_description' => $clean($d['seo_description'] ?? []),
            'noindex' => $request->boolean('noindex'), 'status' => $d['status'],
            'layout' => $d['layout'], 'image' => $image, 'show_in_menu' => $request->boolean('show_in_menu'),
            'published_at' => $d['status'] === 'published' ? ($page->published_at ?? now()) : null,
        ];
    }

    private function deleteImage(?string $url): void
    {
        if ($url && str_starts_with($url, '/storage/pages/')) {
            Storage::disk('public')->delete(Str::after($url, '/storage/'));
        }
    }
}
