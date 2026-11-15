<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Support\Csv;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class NewsletterAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $subscribers = NewsletterSubscriber::query()
            ->when($request->string('q')->trim()->value(), fn ($q, $t) => $q->where('email', 'like', "%{$t}%"))
            ->when($status === 'pending', fn ($q) => $q->whereNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when($status === 'confirmed', fn ($q) => $q->whereNotNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when($status === 'unsubscribed', fn ($q) => $q->whereNotNull('unsubscribed_at'))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.newsletter.index', [
            'confirmed' => NewsletterSubscriber::whereNotNull('confirmed_at')->whereNull('unsubscribed_at')->count(),
            'pending' => NewsletterSubscriber::whereNull('confirmed_at')->whereNull('unsubscribed_at')->count(),
            'unsubscribed' => NewsletterSubscriber::whereNotNull('unsubscribed_at')->count(),
            'subscribers' => $subscribers,
        ]);
    }

    /** The person normally confirms by e-mail. The admin may confirm for them, for example after they asked by phone or in person. */
    public function confirm(Request $request, NewsletterSubscriber $subscriber)
    {
        $subscriber->update(['confirmed_at' => now(), 'unsubscribed_at' => null]);
        activity('admin')->causedBy($request->user())->withProperties(['subscriber' => $subscriber->id])->log('newsletter.confirmed_by_admin');

        return back()->with('status', 'Subscriber confirmed.');
    }

    public function resend(Request $request, NewsletterSubscriber $subscriber)
    {
        abort_if($subscriber->confirmed_at && ! $subscriber->unsubscribed_at, 404);

        $link = URL::temporarySignedRoute('newsletter.confirm', now()->addDays(7), ['subscriber' => $subscriber->id]);
        Notify::person($subscriber->email, 'Confirm your subscription', ['Please confirm that you want to receive our newsletter.'], $link);
        activity('admin')->causedBy($request->user())->withProperties(['subscriber' => $subscriber->id])->log('newsletter.confirmation_resent');

        return back()->with('status', 'Confirmation e-mail sent again.');
    }

    public function unsubscribe(Request $request, NewsletterSubscriber $subscriber)
    {
        $subscriber->update(['unsubscribed_at' => now()]);
        activity('admin')->causedBy($request->user())->withProperties(['subscriber' => $subscriber->id])->log('newsletter.unsubscribed_by_admin');

        return back()->with('status', 'Subscriber unsubscribed. They will not receive newsletters.');
    }

    /** Permanent removal, for example when a person asks for their data to be erased. */
    public function destroy(Request $request, NewsletterSubscriber $subscriber)
    {
        activity('admin')->causedBy($request->user())->withProperties(['subscriber' => $subscriber->id])->log('newsletter.deleted');
        $subscriber->delete();

        return back()->with('status', 'Subscriber deleted.');
    }

    public function export(Request $request)
    {
        activity('admin')->causedBy($request->user())->log('newsletter.exported');

        $rows = NewsletterSubscriber::whereNotNull('confirmed_at')->whereNull('unsubscribed_at')->lazy()->map(fn ($s) => [$s->email, $s->locale, $s->confirmed_at->toDateString()]);

        return Csv::download('newsletter-'.now()->format('Ymd').'.csv', ['Email', 'Language', 'Confirmed'], $rows);
    }
}
