<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Support\Csv;
use Illuminate\Http\Request;

class NewsletterAdminController extends Controller
{
    public function index()
    {
        return view('admin.newsletter.index', [
            'confirmed' => NewsletterSubscriber::whereNotNull('confirmed_at')->whereNull('unsubscribed_at')->count(),
            'pending' => NewsletterSubscriber::whereNull('confirmed_at')->count(),
            'unsubscribed' => NewsletterSubscriber::whereNotNull('unsubscribed_at')->count(),
            'recent' => NewsletterSubscriber::latest()->limit(25)->get(),
        ]);
    }

    public function export(Request $request)
    {
        activity('admin')->causedBy($request->user())->log('newsletter.exported');

        $rows = NewsletterSubscriber::whereNotNull('confirmed_at')->whereNull('unsubscribed_at')->lazy()->map(fn ($s) => [$s->email, $s->locale, $s->confirmed_at->toDateString()]);

        return Csv::download('newsletter-'.now()->format('Ymd').'.csv', ['Email', 'Language', 'Confirmed'], $rows);
    }
}
