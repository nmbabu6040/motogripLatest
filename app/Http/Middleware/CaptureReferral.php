<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use App\Models\AffiliateCampaign;
use App\Models\ReferralClick;
use App\Support\Notify;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records a referral click when a visitor arrives with ?ref=CODE and stores an opaque token in a cookie.
 * The cookie holds only a random token; the database holds only its hash. Last click wins within the window.
 */
class CaptureReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('motogrip.features.affiliate') && $request->isMethod('GET') && is_string($code = $request->query('ref')) && preg_match('/^[A-Za-z0-9]{6,40}$/', $code)) {
            $affiliate = Affiliate::where('code', $code)->where('status', 'approved')->first();

            if ($affiliate) {
                $campaign = is_numeric($request->query('camp')) ? AffiliateCampaign::find($request->query('camp')) : null;
                $campaign = $campaign?->isRunning() ? $campaign : null;
                $window = $campaign?->attribution_window_days ?? 30;

                $token = Str::random(40);
                ReferralClick::create([
                    'affiliate_id' => $affiliate->id, 'affiliate_campaign_id' => $campaign?->id, 'token_hash' => hash('sha256', $token),
                    'landing_path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 255), 'visitor_hash' => Notify::ipHash($request->ip().'|'.substr((string) $request->userAgent(), 0, 120)),
                    'clicked_at' => now(),
                ]);

                Cookie::queue(Cookie::make('mg_ref', $token, $window * 1440, '/', null, $request->isSecure(), true, false, 'lax'));
            }
        }

        return $next($request);
    }
}
