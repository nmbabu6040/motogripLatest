<x-layouts.admin-shell title="Affiliates">
    <p class="mb-4 max-w-3xl text-sm">The affiliate module is {{ config('motogrip.features.affiliate') ? 'enabled' : 'disabled' }}. This site is inquiry-based, so conversions are <strong>leads</strong> (inquiries and dealer applications), not online sales. Commissions are created only when a campaign defines a flat amount and currency; confirm your legal terms and payout method before enabling it.</p>

    <section class="mb-8 max-w-5xl rounded-panel border border-steel bg-white p-6"><h2 class="text-lg font-extrabold">Add an affiliate</h2>
        <p class="mt-1 text-sm">যাকে অ্যাফিলিয়েট করতে চান, তার একটি অ্যাকাউন্ট আগে থেকে থাকতে হবে (Admin → Users → Add user)। এখান থেকে বাছলেই সে অনুমোদিত অ্যাফিলিয়েট হয়ে নিজের লিংক পাবে। শর্তাবলী ও পেমেন্টের নিয়ম আপনি আলাদাভাবে জানিয়ে দেবেন।</p>
        @if ($candidates->isEmpty())
            <p class="mt-3 rounded-control bg-surface p-3 text-sm">সবাই ইতিমধ্যে অ্যাফিলিয়েট, অথবা কোনো ইউজার নেই। <a class="underline text-industrial" href="{{ route('admin.users.create') }}">নতুন ইউজার যোগ করুন</a>।</p>
        @else
            <form method="POST" action="{{ route('admin.affiliates.store') }}" class="mt-3 flex flex-wrap items-end gap-3">@csrf
                <div><label class="mb-1 block text-sm font-semibold" for="user_id">Person</label>
                    <select id="user_id" name="user_id" required class="min-w-64 rounded-control border border-steel px-3 py-2"><option value="">Choose…</option>@foreach ($candidates as $u)<option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>@endforeach</select></div>
                <x-button>Add affiliate</x-button></form>
        @endif
    </section>

    <section class="mb-8 rounded-panel border border-steel bg-white p-6 max-w-5xl"><h2 class="text-lg font-extrabold">Affiliates</h2>
        <table class="mt-3 w-full text-left text-sm"><thead class="bg-surface"><tr><th class="p-2">Name</th><th class="p-2">Code</th><th class="p-2">Status</th><th class="p-2">Clicks</th><th class="p-2">Conversions</th><th class="p-2"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>@forelse ($affiliates as $a)<tr class="border-t border-steel"><td class="p-2">{{ $a->user?->name }}</td><td class="p-2 font-mono">{{ $a->code }}</td><td class="p-2">{{ ucfirst($a->status) }}</td><td class="p-2">{{ $a->clicks_count }}</td><td class="p-2">{{ $a->conversions_count }}</td>
                <td class="p-2"><form method="POST" action="{{ route('admin.affiliates.status', $a) }}" class="flex gap-2">@csrf @method('PUT')@if ($a->status !== 'approved')<button name="status" value="approved" class="underline text-industrial">Approve</button>@endif @if ($a->status !== 'suspended')<button name="status" value="suspended" class="underline text-red-800">Suspend</button>@endif</form></td></tr>
            @empty<tr><td colspan="6" class="p-4 text-center">No affiliates yet.</td></tr>@endforelse</tbody></table></section>

    <section class="mb-8 rounded-panel border border-steel bg-white p-6 max-w-5xl"><h2 class="text-lg font-extrabold">Campaigns</h2>
        <form method="POST" action="{{ route('admin.affiliates.campaigns.store') }}" class="mt-3 grid gap-3 sm:grid-cols-5 items-end">@csrf
            <x-field name="name" label="Name" required /><x-field name="attribution_window_days" label="Window (days)" type="number" value="30" required /><x-field name="commission_flat" label="Flat commission" type="number" step="0.01" /><x-field name="currency" label="Currency (e.g. BDT)" />
            <div class="mb-4 flex items-center gap-3"><label class="flex items-center gap-1 text-sm"><input type="checkbox" name="is_active" value="1"> Active</label><x-button>Add</x-button></div></form>
        <ul class="divide-y divide-steel text-sm">@foreach ($campaigns as $c)<li class="flex items-center justify-between py-2"><span>{{ $c->name }} · {{ $c->attribution_window_days }} days @if ($c->commission_flat)· {{ $c->commission_flat }} {{ $c->currency }}@endif · {{ $c->is_active ? 'Active' : 'Paused' }}</span><form method="POST" action="{{ route('admin.affiliates.campaigns.toggle', $c) }}">@csrf<button class="underline text-industrial">{{ $c->is_active ? 'Pause' : 'Activate' }}</button></form></li>@endforeach</ul></section>

    <section class="rounded-panel border border-steel bg-white p-6 max-w-5xl"><div class="flex justify-between"><h2 class="text-lg font-extrabold">Recent conversions</h2>@can('reports.export')<a class="underline text-industrial" href="{{ route('admin.affiliates.export') }}">Export CSV</a>@endcan</div>
        <table class="mt-3 w-full text-left text-sm"><thead class="bg-surface"><tr><th class="p-2">Date</th><th class="p-2">Affiliate</th><th class="p-2">Type</th><th class="p-2">Status</th><th class="p-2">Commission</th><th class="p-2"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>@forelse ($conversions as $c)<tr class="border-t border-steel"><td class="p-2">{{ $c->created_at->format('Y-m-d') }}</td><td class="p-2">{{ $c->affiliate?->user?->name }}</td><td class="p-2">{{ $c->type }}</td><td class="p-2">{{ ucfirst($c->status) }}</td>
                <td class="p-2">@if ($c->commission){{ $c->commission->amount }} {{ $c->commission->currency }} ({{ $c->commission->status }})@if ($c->commission->status === 'approved')<form method="POST" action="{{ route('admin.affiliates.paid', $c->commission) }}" class="inline">@csrf<button class="ml-2 underline text-industrial">Mark paid</button></form>@endif @else — @endif</td>
                <td class="p-2"><form method="POST" action="{{ route('admin.affiliates.decide', $c) }}" class="flex gap-2">@csrf @method('PUT')@if ($c->status === 'pending')<button name="status" value="approved" class="underline text-industrial">Approve</button><button name="status" value="rejected" class="underline text-red-800">Reject</button>@elseif ($c->status === 'approved')<button name="status" value="reversed" class="underline text-red-800">Reverse</button>@endif</form></td></tr>
            @empty<tr><td colspan="6" class="p-4 text-center">No conversions yet.</td></tr>@endforelse</tbody></table></section>
</x-layouts.admin-shell>
