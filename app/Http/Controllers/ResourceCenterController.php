<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class ResourceCenterController extends Controller
{
    public function __invoke(Request $request)
    {
        $type = $request->query('type');

        $documents = Document::available()->with('product')
            ->when(is_string($type) && array_key_exists($type, Document::TYPES), fn ($q) => $q->where('type', $type))
            ->when($request->string('q')->trim()->value(), fn ($q, $t) => $q->where('title', 'like', '%'.addcslashes($t, '%_\\').'%'))
            ->orderBy('type')->latest('published_at')->paginate(20)->withQueryString();

        return view('resources.index', ['documents' => $documents, 'types' => Document::TYPES, 'activeType' => $type]);
    }
}
