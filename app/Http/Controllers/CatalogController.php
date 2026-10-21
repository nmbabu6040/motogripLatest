<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'], 'category' => ['nullable', 'string', 'max:120'],
            'application' => ['nullable', 'string', 'max:120'], 'viscosity' => ['nullable', 'string', 'max:40'],
            'api' => ['nullable', 'string', 'max:40'], 'packaging' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'in:newest,sku'], 'view' => ['nullable', 'in:grid,list'],
        ]);

        $category = isset($filters['category']) ? ProductCategory::active()->where('slug', $filters['category'])->first() : null;

        $products = Product::published()->with(['category', 'media', 'packaging'])
            ->when($category, fn ($q) => $q->whereIn('product_category_id', $category->selfAndDescendantIds()))
            ->when($filters['application'] ?? null, fn ($q, $v) => $q->where('application', $v))
            ->when($filters['viscosity'] ?? null, fn ($q, $v) => $q->where('viscosity_grade', $v))
            ->when($filters['api'] ?? null, fn ($q, $v) => $q->where('api_classification', $v))
            ->when($filters['packaging'] ?? null, fn ($q, $v) => $q->whereHas('packaging', fn ($p) => $p->where('label', $v)->where('is_active', true)))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $term = addcslashes($term, '%_\\');
                $q->where(fn ($w) => $w->where('sku', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%")->orWhere('application', 'like', "%{$term}%"));
            })
            ->when(($filters['sort'] ?? 'newest') === 'sku', fn ($q) => $q->orderBy('sku'), fn ($q) => $q->latest('published_at')->latest('id'))
            ->paginate(12)->withQueryString();

        $facet = fn (string $col) => Product::published()->whereNotNull($col)->distinct()->orderBy($col)->pluck($col);

        return view('catalog.index', [
            'products' => $products, 'filters' => $filters, 'activeCategory' => $category,
            'categories' => ProductCategory::active()->whereNull('parent_id')->with('children')->orderBy('sort_order')->get(),
            'applications' => $facet('application'), 'viscosities' => $facet('viscosity_grade'), 'apis' => $facet('api_classification'),
            'packs' => DB::table('packaging_options')->where('is_active', true)->distinct()->orderBy('label')->pluck('label'),
        ]);
    }

    public function show(Product $product)
    {
        // Unpublished products are invisible to the public; the admin preview is a staff-only convenience.
        abort_unless($product->isPublished() || auth()->user()?->can('products.view'), 404);

        $product->load(['category', 'media', 'packaging', 'features', 'documents' => fn ($q) => $q->available(), 'approvals' => fn ($q) => $q->verified(), 'specifications' => fn ($q) => $q->verified(), 'related' => fn ($q) => $q->published()->with('media')]);

        if ($product->isPublished()) {
            // Daily aggregate only: no IP, no user id.
            DB::table('product_views')->upsert([['product_id' => $product->id, 'viewed_on' => today()->toDateString(), 'views' => 1]], ['product_id', 'viewed_on'], ['views' => DB::raw('views + 1')]);
        }

        return view('catalog.show', compact('product'));
    }
}
