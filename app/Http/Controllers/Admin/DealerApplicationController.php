<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\DealerApplication;
use App\Models\User;
use App\Support\Csv;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DealerApplicationController extends Controller
{
    public function index(Request $request)
    {
        $applications = DealerApplication::with('assignee')
            ->when($request->string('status')->value(), fn ($q, $s) => $q->where('status', $s))
            ->when($request->string('district')->trim()->value(), fn ($q, $d) => $q->where('district', 'like', "%{$d}%"))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.dealer-applications.index', compact('applications'));
    }

    public function show(DealerApplication $application)
    {
        return view('admin.dealer-applications.show', ['application' => $application->load('notes.author'), 'staff' => User::permission('dealers.manage')->orWhereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->orderBy('name')->get()]);
    }

    public function update(Request $request, DealerApplication $application)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(DealerApplication::STATUSES)],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')],
            'follow_up_on' => ['nullable', 'date'],
        ]);

        $before = $application->only(['status', 'assigned_to']);
        $decided = in_array($data['status'], ['approved', 'rejected'], true) && $application->status !== $data['status'];

        $application->update([
            'status' => $data['status'], 'assigned_to' => $data['assigned_to'] ?? null, 'follow_up_on' => $data['follow_up_on'] ?? null,
        ] + ($decided ? ['reviewed_by' => $request->user()->id, 'reviewed_at' => now()] : []));

        if ($application->wasChanged('status')) {
            Notify::person($application->email, 'Your dealer application status', ['Your application status is now: '.str_replace('_', ' ', $application->status).'.']);
        }

        activity('admin')->causedBy($request->user())->performedOn($application)->withProperties(['before' => $before, 'after' => $application->only(['status', 'assigned_to'])])->log('dealer_application.updated');

        return back()->with('status', 'Application updated.');
    }

    public function note(Request $request, DealerApplication $application)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $application->notes()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        return back()->with('status', 'Note added.');
    }

    /** Creates an unpublished dealer listing from an approved application. Publishing stays a separate, deliberate step. */
    public function convert(Request $request, DealerApplication $application)
    {
        if ($application->status !== 'approved') {
            return back()->withErrors(['status' => 'Approve the application first.']);
        }
        if (Dealer::where('dealer_application_id', $application->id)->exists()) {
            return back()->withErrors(['status' => 'A dealer listing already exists for this application.']);
        }

        $dealer = Dealer::create([
            'dealer_application_id' => $application->id, 'slug' => Str::slug($application->business_name).'-'.Str::lower(Str::random(5)),
            'name' => $application->business_name, 'district' => $application->district, 'city' => $application->city, 'address' => $application->address,
            'phone' => $application->phone, 'email' => $application->email, 'is_published' => false,
        ]);

        activity('admin')->causedBy($request->user())->performedOn($dealer)->log('dealer.created_from_application');

        return redirect()->route('admin.dealers.edit', $dealer)->with('status', 'Dealer listing created. Review it, then publish.');
    }

    public function export(Request $request)
    {
        abort_unless($request->user()->can('inquiries.export'), 403);
        activity('admin')->causedBy($request->user())->log('dealer_applications.exported');

        $rows = DealerApplication::latest()->lazy()->map(fn ($a) => [$a->reference, $a->created_at->toDateTimeString(), $a->status, $a->business_name, $a->contact_name, $a->email, $a->phone, $a->district, $a->city, $a->business_type]);

        return Csv::download('dealer-applications-'.now()->format('Ymd').'.csv', ['Reference', 'Received', 'Status', 'Business', 'Contact', 'Email', 'Phone', 'District', 'City', 'Type'], $rows);
    }
}
