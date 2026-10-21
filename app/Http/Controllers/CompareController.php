<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    public function __invoke(Request $request)
    {
        $slugs = collect($request->query('p', []))->filter(fn ($s) => is_string($s))->unique()->take(4)->values();

        $products = Product::published()->with(['category', 'packaging', 'specifications' => fn ($q) => $q->verified()])
            ->whereIn('slug', $slugs)->get()->sortBy(fn ($p) => $slugs->search($p->slug))->values();

        $rows = [
            __('site.category') => fn ($p) => $p->category?->name,
            __('site.application') => fn ($p) => $p->application,
            __('site.viscosity') => fn ($p) => $p->viscosity_grade,
            __('site.api') => fn ($p) => $p->api_classification,
            __('site.iso_vg') => fn ($p) => $p->iso_vg_grade,
            __('site.packaging') => fn ($p) => $p->packaging->where('is_active', true)->pluck('label')->join(', '),
        ];
        foreach ($products->flatMap(fn ($p) => $p->specifications)->map(fn ($s) => $s->name)->unique() as $specName) {
            $rows[$specName] = fn ($p) => ($s = $p->specifications->first(fn ($x) => $x->name === $specName)) ? trim($s->value.' '.$s->unit) : null;
        }

        $table = collect($rows)->map(function ($fn, $label) use ($products) {
            $values = $products->map($fn)->map(fn ($v) => $v ?: null);
            return ['label' => $label, 'values' => $values, 'differs' => $values->unique()->count() > 1];
        })->filter(fn ($r) => $r['values']->filter()->isNotEmpty())->values();

        return view('catalog.compare', compact('products', 'table'));
    }
}
