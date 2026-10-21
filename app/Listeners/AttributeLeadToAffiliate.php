<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Models\AffiliateConversion;
use App\Models\ReferralClick;

class AttributeLeadToAffiliate
{
    public function handle(LeadCreated $event): void
    {
        if (! config('motogrip.features.affiliate') || ! ($token = request()->cookie('mg_ref')) || ! is_string($token)) {
            return;
        }

        $click = ReferralClick::where('token_hash', hash('sha256', $token))->with('affiliate.user')->first();
        $affiliate = $click?->affiliate;
        if (! $affiliate || $affiliate->status !== 'approved') {
            return;
        }

        // Attribution window: the campaign's window if there is one, otherwise 30 days.
        $window = $click->affiliate_campaign_id ? (\App\Models\AffiliateCampaign::find($click->affiliate_campaign_id)?->attribution_window_days ?? 30) : 30;
        if ($click->clicked_at->addDays($window)->isPast()) {
            return;
        }

        // Fraud controls: no self-referrals, and a cap on pending conversions per affiliate per hour.
        if (strcasecmp((string) $affiliate->user?->email, (string) ($event->lead->email ?? '')) === 0) {
            return;
        }
        if (AffiliateConversion::where('affiliate_id', $affiliate->id)->where('created_at', '>', now()->subHour())->count() >= 20) {
            return;
        }

        $key = $event->type === 'dealer' ? 'dealer_application_id' : 'inquiry_id';
        // The unique constraint on the lead column also guarantees one conversion per lead.
        AffiliateConversion::firstOrCreate([$key => $event->lead->getKey()], [
            'affiliate_id' => $affiliate->id, 'referral_click_id' => $click->id, 'type' => $event->type === 'dealer' ? 'dealer' : 'lead', 'status' => 'pending',
        ]);
    }
}
