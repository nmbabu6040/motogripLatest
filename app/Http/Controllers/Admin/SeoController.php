<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Product;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SeoController extends Controller
{
    public function index()
    {
        $site = [
            ['Site name set', (bool) Settings::get('brand', 'site_name')],
            ['Default meta description set', (bool) Settings::get('seo', 'default_description')],
            ['Header logo uploaded', (bool) Settings::get('brand', 'logo_url')],
            ['Favicon uploaded', (bool) Settings::get('brand', 'favicon_url')],
            ['Default social sharing image uploaded', (bool) Settings::get('brand', 'social_image_url')],
            ['Support email set', (bool) Settings::get('contact', 'email')],
            ['Site is open to search engines', ! Settings::get('seo', 'noindex_site', false)],
            ['Google Analytics ID set (optional)', (bool) Settings::get('integrations', 'ga_measurement_id')],
            ['Search Console token set (optional)', (bool) Settings::get('integrations', 'search_console_token')],
        ];

        $products = Product::published()->with('media')->get();
        $posts = BlogPost::published()->with('media')->get();

        $content = [
            ['Published products without an SEO description', $products->filter(fn ($p) => ! $p->getTranslation('seo_description', 'en', false) && ! $p->short_description)->map(fn ($p) => [$p->sku, route('admin.products.edit', $p)])],
            ['Published products without an image', $products->filter(fn ($p) => $p->getMedia('images')->isEmpty())->map(fn ($p) => [$p->sku, route('admin.products.edit', $p)])],
            ['Product images without alt text', $products->filter(fn ($p) => $p->getMedia('images')->contains(fn ($m) => ! $m->getCustomProperty('alt')))->map(fn ($p) => [$p->sku, route('admin.products.edit', $p)])],
            ['Published articles without an SEO description or excerpt', $posts->filter(fn ($p) => ! $p->getTranslation('seo_description', 'en', false) && ! $p->excerpt)->map(fn ($p) => [$p->title, route('admin.blog.edit', $p)])],
            ['Published articles without a featured image', $posts->filter(fn ($p) => $p->getMedia('featured')->isEmpty())->map(fn ($p) => [$p->title, route('admin.blog.edit', $p)])],
            ['Published pages without an SEO description', Page::published()->get()->filter(fn ($p) => ! $p->getTranslation('seo_description', 'en', false))->map(fn ($p) => [$p->title, route('admin.pages.edit', $p)])],
        ];

        return view('admin.seo.index', [
            'site' => $site, 'content' => $content,
            'redirects' => DB::table('redirects')->orderByDesc('id')->limit(100)->get(),
        ]);
    }

    public function storeRedirect(Request $request)
    {
        $d = $request->validate([
            'from_path' => ['required', 'string', 'max:255', 'regex:~^/[^\s?#]*$~', Rule::unique('redirects', 'from_path')],
            'to_url' => ['required', 'string', 'max:2000', 'regex:~^(/|https?://)~'],
            'status_code' => ['required', Rule::in([301, 302])],
        ]);
        if (rtrim($d['from_path'], '/') === rtrim($d['to_url'], '/')) {
            return back()->withErrors(['to_url' => 'A redirect cannot point to itself.']);
        }
        if (str_starts_with($d['from_path'], '/admin')) {
            return back()->withErrors(['from_path' => 'Admin paths cannot be redirected.']);
        }

        DB::table('redirects')->insert($d + ['is_active' => true, 'hits' => 0, 'created_at' => now(), 'updated_at' => now()]);
        activity('admin')->causedBy($request->user())->withProperties($d)->log('redirect.created');

        return back()->with('status', 'Redirect added.');
    }

    public function destroyRedirect(Request $request, int $redirect)
    {
        DB::table('redirects')->where('id', $redirect)->delete();
        activity('admin')->causedBy($request->user())->withProperties(['id' => $redirect])->log('redirect.deleted');

        return back()->with('status', 'Redirect deleted.');
    }
}
