<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use Illuminate\Http\Request;

class DealerLocatorController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(config('motogrip.features.dealer_locator'), 404);

        $term = trim((string) $request->query('q', ''));
        $dealers = Dealer::published()
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('district', 'like', $like)->orWhere('city', 'like', $like)->orWhere('name', 'like', $like)->orWhere('address', 'like', $like));
            })
            ->orderBy('district')->orderBy('name')->paginate(15)->withQueryString();

        return view('dealers.index', compact('dealers', 'term'));
    }
}
