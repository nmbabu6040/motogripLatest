<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Support\Lang;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:190'], 'website' => ['nullable', 'max:0']]);

        if (! $request->filled('website')) {
            $subscriber = NewsletterSubscriber::firstOrCreate(['email' => strtolower($data['email'])], ['locale' => app()->getLocale(), 'token_hash' => hash('sha256', Str::random(40))]);

            if (! $subscriber->confirmed_at || $subscriber->unsubscribed_at) {
                $link = URL::temporarySignedRoute('newsletter.confirm', now()->addDays(7), ['subscriber' => $subscriber->id]);
                Notify::person($subscriber->email, 'Confirm your subscription', ['Please confirm that you want to receive our newsletter.'], $link);
            }
        }

        // Identical response for new, existing and bot submissions.
        return back()->with('newsletter_status', __('site.newsletter_check_email'));
    }

    public function confirm(NewsletterSubscriber $subscriber)
    {
        $subscriber->update(['confirmed_at' => now(), 'unsubscribed_at' => null]);

        return redirect(Lang::route('home'))->with('status', __('site.newsletter_confirmed'));
    }

    public function unsubscribe(NewsletterSubscriber $subscriber)
    {
        $subscriber->update(['unsubscribed_at' => now()]);

        return redirect(Lang::route('home'))->with('status', __('site.newsletter_unsubscribed'));
    }
}
