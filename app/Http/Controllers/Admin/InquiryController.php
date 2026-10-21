<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\User;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    public function index(Request $request)
    {
        $inquiries = $this->scoped($request)->with(['assignee', 'product'])
            ->when($request->string('status')->value(), fn ($q, $s) => $q->where('status', $s))
            ->when($request->string('type')->value(), fn ($q, $t) => $q->where('type', $t))
            ->when($request->string('q')->trim()->value(), fn ($q, $t) => $q->where(fn ($w) => $w->where('name', 'like', "%{$t}%")->orWhere('email', 'like', "%{$t}%")->orWhere('company', 'like', "%{$t}%")))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.inquiries.index', ['inquiries' => $inquiries, 'types' => Inquiry::TYPES]);
    }

    public function show(Request $request, Inquiry $inquiry)
    {
        $this->ensureAccess($request, $inquiry);

        return view('admin.inquiries.show', ['inquiry' => $inquiry->load(['assignee', 'product', 'notes.author']), 'staff' => $this->staff($request)]);
    }

    public function update(Request $request, Inquiry $inquiry)
    {
        $this->ensureAccess($request, $inquiry);
        $canAssign = $request->user()->can('inquiries.view-all');

        $data = $request->validate([
            'status' => ['required', Rule::in(Inquiry::STATUSES)],
            'follow_up_on' => ['nullable', 'date'],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')],
        ]);

        if ($canAssign && ! empty($data['assigned_to']) && ! User::find($data['assigned_to'])?->can('inquiries.manage')) {
            return back()->withErrors(['assigned_to' => 'That user cannot handle inquiries.']);
        }

        $before = $inquiry->only(['status', 'assigned_to', 'follow_up_on']);
        $inquiry->update(['status' => $data['status'], 'follow_up_on' => $data['follow_up_on'] ?? null] + ($canAssign ? ['assigned_to' => $data['assigned_to'] ?? null] : []));

        activity('admin')->causedBy($request->user())->performedOn($inquiry)->withProperties(['before' => $before, 'after' => $inquiry->only(['status', 'assigned_to', 'follow_up_on'])])->log('inquiry.updated');

        return back()->with('status', 'Inquiry updated.');
    }

    public function note(Request $request, Inquiry $inquiry)
    {
        $this->ensureAccess($request, $inquiry);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);

        $inquiry->notes()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        return back()->with('status', 'Note added.');
    }

    public function export(Request $request)
    {
        abort_unless($request->user()->can('inquiries.export'), 403);

        activity('admin')->causedBy($request->user())->log('inquiries.exported');

        $rows = Inquiry::with(['assignee', 'product'])->latest()->lazy()->map(fn ($i) => [
            $i->id, $i->created_at->toDateTimeString(), $i->type, $i->status, $i->name, $i->email, $i->phone, $i->company,
            $i->product?->sku, $i->assignee?->name, $i->follow_up_on?->toDateString(), $i->message,
        ]);

        return Csv::download('inquiries-'.now()->format('Ymd').'.csv', ['ID', 'Received', 'Type', 'Status', 'Name', 'Email', 'Phone', 'Company', 'Product SKU', 'Assigned to', 'Follow-up', 'Message'], $rows);
    }

    private function scoped(Request $request)
    {
        return Inquiry::query()->when(! $request->user()->can('inquiries.view-all'), fn ($q) => $q->where('assigned_to', $request->user()->id));
    }

    private function ensureAccess(Request $request, Inquiry $inquiry): void
    {
        abort_unless($request->user()->can('inquiries.view-all') || $inquiry->assigned_to === $request->user()->id, 403);
    }

    private function staff(Request $request)
    {
        return $request->user()->can('inquiries.view-all') ? User::permission('inquiries.manage')->orWhereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->orderBy('name')->get() : collect();
    }
}
