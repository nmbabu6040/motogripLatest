<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Document;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __invoke()
    {
        $sections = DB::table('homepage_sections')->where('is_enabled', true)->orderBy('sort_order')->get()
            ->map(fn ($s) => ['key' => $s->key, 'content' => json_decode($s->content ?? '[]', true) ?: []]);

        $data = [
            'featured' => $this->featuredProducts(),
            'categories' => ProductCategory::active()->whereNull('parent_id')->orderBy('sort_order')->limit(8)->get(),
            'posts' => BlogPost::published()->with('category')->latest('published_at')->limit(3)->get(),
            'documents' => Document::available()->where('visibility', 'public')->latest('published_at')->limit(4)->get(),
            'productCount' => Product::published()->count(),
            'categoryCount' => ProductCategory::active()->whereNull('parent_id')->count(),
            'faqs' => DB::table('faqs')->whereNull('faqable_type')->where('is_published', true)->orderBy('sort_order')->get(),
        ];

        return view('home.index', ['sections' => $sections, ...$data]);
    }

    /** Featured products first; if none are marked, show the newest published ones so the section is never empty. */
    private function featuredProducts()
    {
        $with = ['category', 'media', 'packaging'];
        $featured = Product::published()->where('is_featured', true)->with($with)->latest('published_at')->limit(8)->get();

        return $featured->isNotEmpty() ? $featured : Product::published()->with($with)->latest('id')->limit(8)->get();
    }
}
