<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogTaxonomyController extends Controller
{
    public function index()
    {
        return view('admin.blog.taxonomy', ['categories' => BlogCategory::orderBy('slug')->get(), 'tags' => BlogTag::orderBy('slug')->get()]);
    }

    public function storeCategory(Request $request)
    {
        $d = $request->validate(['name.en' => ['required', 'string', 'max:80'], 'name.bn' => ['nullable', 'string', 'max:80']]);
        $slug = Str::slug($d['name']['en']) ?: Str::random(6);
        abort_if(BlogCategory::where('slug', $slug)->exists(), 422, 'That category already exists.');
        BlogCategory::create(['slug' => $slug, 'name' => array_filter($d['name'])]);

        return back()->with('status', 'Category added.');
    }

    public function destroyCategory(BlogCategory $category)
    {
        $category->delete();   // posts keep existing; the FK sets their category to null

        return back()->with('status', 'Category deleted.');
    }

    public function destroyTag(BlogTag $tag)
    {
        $tag->delete();

        return back()->with('status', 'Tag deleted.');
    }
}
