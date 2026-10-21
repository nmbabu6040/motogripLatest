<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ProductCategory::class);

        return view('admin.categories.index', ['categories' => ProductCategory::with('parent')->withCount('products')->orderBy('sort_order')->get()]);
    }

    public function create()
    {
        $this->authorize('create', ProductCategory::class);

        return view('admin.categories.form', ['category' => new ProductCategory(['is_active' => true]), 'parents' => ProductCategory::whereNull('parent_id')->get()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', ProductCategory::class);

        $category = ProductCategory::create($this->data($request, new ProductCategory));
        activity('admin')->causedBy($request->user())->performedOn($category)->log('category.created');

        return redirect()->route('admin.categories.index')->with('status', 'Category saved.');
    }

    public function edit(ProductCategory $category)
    {
        $this->authorize('update', $category);

        return view('admin.categories.form', ['category' => $category, 'parents' => ProductCategory::whereNull('parent_id')->where('id', '!=', $category->id)->get()]);
    }

    public function update(Request $request, ProductCategory $category)
    {
        $this->authorize('update', $category);

        $category->update($this->data($request, $category));
        activity('admin')->causedBy($request->user())->performedOn($category)->log('category.updated');

        return redirect()->route('admin.categories.index')->with('status', 'Category saved.');
    }

    public function destroy(Request $request, ProductCategory $category)
    {
        $this->authorize('delete', $category);

        if ($category->products()->exists() || $category->children()->exists()) {
            return back()->withErrors(['category' => 'Move or delete its products and subcategories first.']);
        }
        activity('admin')->causedBy($request->user())->performedOn($category)->log('category.deleted');
        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted.');
    }

    private function data(Request $request, ProductCategory $category): array
    {
        $data = $request->validate([
            'parent_id' => ['nullable', Rule::exists('product_categories', 'id')->whereNull('parent_id'), Rule::notIn([$category->id])],
            'slug' => ['nullable', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', 'max:120', Rule::unique('product_categories', 'slug')->ignore($category->id)],
            'name.en' => ['required', 'string', 'max:120'], 'name.bn' => ['nullable', 'string', 'max:120'],
            'description.en' => ['nullable', 'string', 'max:2000'], 'description.bn' => ['nullable', 'string', 'max:2000'],
            'seo_title.en' => ['nullable', 'string', 'max:70'], 'seo_description.en' => ['nullable', 'string', 'max:170'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $clean = fn ($a) => array_filter($a ?? [], fn ($v) => $v !== null && $v !== '');

        return [
            'parent_id' => $data['parent_id'] ?? null,
            'slug' => $data['slug'] ?? $category->slug ?? Str::slug($data['name']['en']),
            'name' => $clean($data['name']), 'description' => $clean($data['description'] ?? []),
            'seo_title' => $clean($data['seo_title'] ?? []), 'seo_description' => $clean($data['seo_description'] ?? []),
            'sort_order' => $data['sort_order'] ?? 0, 'is_active' => $request->boolean('is_active'),
        ];
    }
}
