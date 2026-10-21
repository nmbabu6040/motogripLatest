<?php

namespace App\Http\Controllers;

use App\Events\LeadCreated;
use App\Models\DealerApplication;
use App\Support\Lang;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DealerApplicationController extends Controller
{
    public function create()
    {
        return view('dealers.apply', ['types' => DealerApplication::TYPES]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:160'], 'contact_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'], 'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+()\-\s]+$/'],
            'district' => ['required', 'string', 'max:80'], 'city' => ['nullable', 'string', 'max:80'], 'address' => ['nullable', 'string', 'max:255'],
            'business_type' => ['required', Rule::in(array_keys(DealerApplication::TYPES))],
            'years_in_business' => ['nullable', 'integer', 'min:0', 'max:150'], 'message' => ['nullable', 'string', 'max:3000'],
            'consent' => ['accepted'], 'website' => ['nullable', 'max:0'],
        ]);

        if ($request->filled('website')) {
            return redirect(Lang::route('dealer.apply'));
        }

        $token = Str::random(32);
        $application = DealerApplication::create([
            ...collect($data)->except(['consent', 'website'])->all(),
            'reference' => (string) Str::uuid(), 'tracking_token_hash' => hash('sha256', $token),
            'consent_at' => now(), 'ip_hash' => Notify::ipHash($request->ip()),
        ]);

        LeadCreated::dispatch($application, 'dealer');

        Notify::staff('New dealer application: '.$application->business_name, ["Business: {$application->business_name}", "Contact: {$application->contact_name}", "District: {$application->district}"], route('admin.dealer-applications.show', $application));
        Notify::person($application->email, 'We received your dealer application', ["Reference: {$application->reference}", "Tracking code: {$token}", 'Keep these to check the status of your application.'], Lang::route('dealer.status'));

        // The tracking code is shown once, on this redirect only.
        return redirect(Lang::route('dealer.done'))->with('tracking', ['reference' => $application->reference, 'token' => $token]);
    }

    public function done()
    {
        abort_unless(session('tracking'), 404);

        return view('dealers.done', ['tracking' => session('tracking')]);
    }

    public function statusForm()
    {
        return view('dealers.status');
    }

    public function status(Request $request)
    {
        $data = $request->validate(['reference' => ['required', 'uuid'], 'token' => ['required', 'string', 'max:64']]);

        $application = DealerApplication::where('reference', $data['reference'])->first();
        // Same message whether the reference or the code is wrong.
        if (! $application || ! hash_equals($application->tracking_token_hash, hash('sha256', $data['token']))) {
            return back()->withErrors(['reference' => __('site.status_not_found')])->withInput($request->only('reference'));
        }

        return view('dealers.status', ['application' => $application]);
    }
}
