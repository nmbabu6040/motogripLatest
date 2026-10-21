<?php

namespace App\Http\Controllers;

use App\Events\LeadCreated;
use App\Models\Inquiry;
use App\Models\Product;
use App\Support\Lang;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function create(Request $request)
    {
        $product = $request->query('product') ? Product::published()->where('slug', $request->query('product'))->first() : null;

        return view('contact.create', ['product' => $product, 'types' => Inquiry::TYPES]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Inquiry::TYPES))],
            'product' => ['nullable', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s]+$/'],
            'company' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'max:0'],   // honeypot: real visitors leave it empty
        ]);

        // Bots that fill the hidden field get the same success screen and nothing is stored.
        if ($request->filled('website')) {
            return redirect(Lang::route('contact.create'))->with('status', __('site.inquiry_sent'));
        }

        $product = ! empty($data['product']) ? Product::published()->where('slug', $data['product'])->first() : null;

        $inquiry = Inquiry::create([
            'type' => $data['type'], 'product_id' => $product?->id, 'name' => $data['name'], 'email' => $data['email'],
            'phone' => $data['phone'] ?? null, 'company' => $data['company'] ?? null, 'message' => $data['message'],
            'locale' => app()->getLocale(), 'consent_at' => now(), 'ip_hash' => Notify::ipHash($request->ip()),
        ]);

        LeadCreated::dispatch($inquiry, 'inquiry');

        Notify::staff('New '.strtolower(Inquiry::TYPES[$inquiry->type]).' from '.$inquiry->name, [
            "Type: ".Inquiry::TYPES[$inquiry->type], "Name: {$inquiry->name}", "Email: {$inquiry->email}",
            'Product: '.($product?->sku ?? 'n/a'), 'Open it in the admin panel to respond.',
        ], route('admin.inquiries.show', $inquiry));

        return redirect(Lang::route('contact.create'))->with('status', __('site.inquiry_sent'));
    }
}
