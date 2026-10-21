<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCampaign;
use App\Models\AffiliateCommission;
use App\Models\AffiliateConversion;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AffiliateController extends Controller
{
    public function index()
    {
        return view('admin.affiliates.index', [
            'affiliates' => Affiliate::with('user')->withCount(['clicks', 'conversions'])->latest()->get(),
            'campaigns' => AffiliateCampaign::latest()->get(),
            'conversions' => AffiliateConversion::with(['affiliate.user', 'commission'])->latest()->limit(50)->get(),
        ]);
    }

    public function setStatus(Request $request, Affiliate $affiliate)
    {
        $status = $request->validate(['status' => ['required', Rule::in(['approved', 'suspended'])]])['status'];
        $affiliate->update(['status' => $status]);
        activity('admin')->causedBy($request->user())->performedOn($affiliate)->withProperties(['status' => $status])->log('affiliate.status');

        return back()->with('status', 'Affiliate '.$status.'.');
    }

    public function storeCampaign(Request $request)
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'attribution_window_days' => ['required', 'integer', 'min:1', 'max:365'],
            'commission_flat' => ['nullable', 'numeric', 'min:0', 'max:1000000'], 'currency' => ['nullable', 'alpha', 'size:3'],
        ]);
        $campaign = AffiliateCampaign::create($d + ['is_active' => $request->boolean('is_active')]);
        activity('admin')->causedBy($request->user())->performedOn($campaign)->log('affiliate_campaign.created');

        return back()->with('status', 'Campaign saved.');
    }

    public function toggleCampaign(Request $request, AffiliateCampaign $campaign)
    {
        $campaign->update(['is_active' => ! $campaign->is_active]);
        activity('admin')->causedBy($request->user())->performedOn($campaign)->log('affiliate_campaign.toggled');

        return back();
    }

    /** Approving a conversion creates a commission only if its campaign defines a flat amount; otherwise payout terms stay manual. */
    public function decide(Request $request, AffiliateConversion $conversion)
    {
        $d = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected', 'reversed'])], 'reason' => ['nullable', 'string', 'max:255']]);

        DB::transaction(function () use ($conversion, $d, $request) {
            $conversion->update(['status' => $d['status']]);

            if ($d['status'] === 'approved' && ! $conversion->commission) {
                $campaign = $conversion->click?->affiliate_campaign_id ? AffiliateCampaign::find($conversion->click->affiliate_campaign_id) : null;
                if ($campaign?->commission_flat && $campaign->currency) {
                    AffiliateCommission::create(['affiliate_conversion_id' => $conversion->id, 'amount' => $campaign->commission_flat, 'currency' => strtoupper($campaign->currency), 'status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
                }
            }
            if (in_array($d['status'], ['rejected', 'reversed'], true) && $conversion->commission && $conversion->commission->status !== 'paid') {
                $conversion->commission->update(['status' => 'cancelled', 'adjustment_reason' => $d['reason'] ?? $d['status']]);
            }
        });

        activity('admin')->causedBy($request->user())->performedOn($conversion)->withProperties($d)->log('affiliate_conversion.decided');

        return back()->with('status', 'Conversion '.$d['status'].'.');
    }

    public function markPaid(Request $request, AffiliateCommission $commission)
    {
        abort_unless($commission->status === 'approved', 422);
        $commission->update(['status' => 'paid', 'paid_at' => now()]);
        activity('admin')->causedBy($request->user())->performedOn($commission)->log('affiliate_commission.paid');

        return back()->with('status', 'Marked as paid.');
    }

    public function export(Request $request)
    {
        abort_unless($request->user()->can('reports.export'), 403);
        activity('admin')->causedBy($request->user())->log('affiliate_report.exported');

        $rows = AffiliateConversion::with(['affiliate.user', 'commission'])->latest()->lazy()->map(fn ($c) => [
            $c->created_at->toDateTimeString(), $c->affiliate?->code, $c->affiliate?->user?->name, $c->type, $c->status, $c->commission?->amount, $c->commission?->currency, $c->commission?->status,
        ]);

        return Csv::download('affiliate-report-'.now()->format('Ymd').'.csv', ['Date', 'Code', 'Affiliate', 'Type', 'Status', 'Commission', 'Currency', 'Commission status'], $rows);
    }
}
