<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateCampaign;
use App\Models\AffiliateCommission;
use App\Models\AffiliateConversion;
use App\Models\ReferralClick;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AffiliateDashboardController extends Controller
{
    public function show(Request $request)
    {
        $affiliate = Affiliate::where('user_id', $request->user()->id)->first();
        abort_unless($affiliate || $request->user()->hasRole('affiliate'), 403);

        $stats = null;
        if ($affiliate?->status === 'approved') {
            $conversionIds = AffiliateConversion::where('affiliate_id', $affiliate->id)->pluck('id');
            $stats = [
                'clicks' => ReferralClick::where('affiliate_id', $affiliate->id)->count(),
                'conversions' => $conversionIds->count(),
                'approved' => AffiliateConversion::where('affiliate_id', $affiliate->id)->where('status', 'approved')->count(),
                'commission_pending' => AffiliateCommission::whereIn('affiliate_conversion_id', $conversionIds)->whereIn('status', ['pending', 'approved'])->sum('amount'),
                'commission_paid' => AffiliateCommission::whereIn('affiliate_conversion_id', $conversionIds)->where('status', 'paid')->sum('amount'),
            ];
        }

        return view('affiliate.dashboard', ['affiliate' => $affiliate, 'stats' => $stats, 'campaigns' => AffiliateCampaign::where('is_active', true)->get()]);
    }

    public function apply(Request $request)
    {
        $request->validate(['terms' => ['accepted']]);

        Affiliate::firstOrCreate(['user_id' => $request->user()->id], ['code' => Str::upper(Str::random(10)), 'status' => 'pending', 'terms_accepted_at' => now()]);

        return back()->with('status', 'Application received. We will review it shortly.');
    }
}
