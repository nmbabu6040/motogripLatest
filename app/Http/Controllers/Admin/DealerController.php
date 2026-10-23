<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DealerController extends Controller
{
    public function index()
    {
        return view('admin.dealers.index', ['dealers' => Dealer::orderBy('district')->orderBy('name')->paginate(25)]);
    }

    public function create()
    {
        return view('admin.dealers.form', ['dealer' => new Dealer]);
    }

    public function store(Request $request)
    {
        $dealer = Dealer::create($this->data($request, new Dealer));
        activity('admin')->causedBy($request->user())->performedOn($dealer)->log('dealer.created');

        return redirect()->route('admin.dealers.index')->with('status', 'Dealer saved.');
    }

    public function edit(Dealer $dealer)
    {
        return view('admin.dealers.form', compact('dealer'));
    }

    public function update(Request $request, Dealer $dealer)
    {
        $dealer->update($this->data($request, $dealer));
        activity('admin')->causedBy($request->user())->performedOn($dealer)->withProperties(['published' => $dealer->is_published])->log('dealer.updated');

        return redirect()->route('admin.dealers.index')->with('status', 'Dealer saved.');
    }

    public function destroy(Request $request, Dealer $dealer)
    {
        activity('admin')->causedBy($request->user())->performedOn($dealer)->log('dealer.deleted');
        $dealer->delete();

        return redirect()->route('admin.dealers.index')->with('status', 'Dealer removed.');
    }

    private function data(Request $request, Dealer $dealer): array
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'district' => ['required', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:190'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'hours' => ['nullable', 'string', 'max:200'],
        ]);

        return [
            'slug' => $dealer->slug ?: Str::slug($d['name']) . '-' . Str::lower(Str::random(5)),
            'name' => $d['name'],
            'district' => $d['district'],
            'city' => $d['city'] ?? null,
            'address' => $d['address'] ?? null,
            'phone' => $d['phone'] ?? null,
            'email' => $d['email'] ?? null,
            'latitude' => $d['latitude'] ?? null,
            'longitude' => $d['longitude'] ?? null,
            'business_hours' => ($d['hours'] ?? null) ? ['text' => $d['hours']] : null,
            'show_phone' => $request->boolean('show_phone'),
            'show_email' => $request->boolean('show_email'),
            'is_published' => $request->boolean('is_published'),
        ];
    }
}
