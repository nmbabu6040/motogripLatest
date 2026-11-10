<?php

namespace App\Http\Controllers;

use App\Models\PartnerLink;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{
    public function index()
    {
        $links = PartnerLink::published()->orderBy('sort_order')->orderByDesc('id')->get();
        abort_if($links->isEmpty(), 404);

        // The store statements are shown only for stores that actually have a link on the page.
        $notices = collect([
            'general' => Settings::get('partners', 'disclosure', 'Some links on this page are affiliate links. If you buy through them we may earn a commission, at no extra cost to you.'),
            'amazon' => $links->contains('store', 'amazon') ? Settings::get('partners', 'amazon_statement', 'As an Amazon Associate I earn from qualifying purchases.') : null,
        ])->filter();

        return view('partners.index', compact('links', 'notices'));
    }

    /** Counts a click. The visitor goes straight to the store (no redirect through this site); this runs in the background. */
    public function click(Request $request, PartnerLink $partnerLink)
    {
        abort_unless($partnerLink->is_published, 404);

        DB::table('partner_link_clicks')->upsert([['partner_link_id' => $partnerLink->id, 'clicked_on' => today()->toDateString(), 'clicks' => 1]], ['partner_link_id', 'clicked_on'], ['clicks' => DB::raw('clicks + 1')]);

        return response()->noContent();
    }
}
