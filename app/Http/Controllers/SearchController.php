<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Product;
use App\Support\Lang;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        return view('search.index', [
            'q' => $q,
            'products' => $q === '' ? collect() : $this->products($q)->limit(12)->get(),
            'posts' => $q === '' ? collect() : $this->posts($q)->limit(12)->get(),
        ]);
    }

    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $items = $this->products($q)->limit(4)->get()->map(fn ($p) => ['label' => $p->name.' ('.$p->sku.')', 'url' => Lang::route('catalog.show', ['product' => $p])])
            ->concat($this->posts($q)->limit(3)->get()->map(fn ($p) => ['label' => $p->title, 'url' => Lang::route('blog.show', ['post' => $p])]));

        return response()->json($items->values());
    }

    private function like(string $q): string
    {
        return '%'.addcslashes($q, '%_\\').'%';
    }

    private function products(string $q)
    {
        $like = $this->like($q);

        return Product::published()->with('category')->where(fn ($w) => $w->where('sku', 'like', $like)->orWhere('name', 'like', $like)->orWhere('application', 'like', $like)->orWhere('viscosity_grade', 'like', $like));
    }

    private function posts(string $q)
    {
        $like = $this->like($q);

        return BlogPost::published()->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like));
    }
}
