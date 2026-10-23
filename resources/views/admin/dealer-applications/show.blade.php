<x-layouts.admin-shell title="Dealer application">
    <div class="grid gap-6 lg:grid-cols-[1fr_22rem] max-w-6xl">
        <section class="rounded-panel border border-steel bg-white p-6">
            <h2 class="text-lg font-extrabold">{{ $application->business_name }}</h2>
            <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                <dt class="font-semibold">Contact</dt><dd>{{ $application->contact_name }}</dd>
                <dt class="font-semibold">Email</dt><dd>{{ $application->email }}</dd><dt class="font-semibold">Phone</dt><dd>{{ $application->phone }}</dd>
                <dt class="font-semibold">Location</dt><dd>{{ collect([$application->address, $application->city, $application->district])->filter()->join(', ') }}</dd>
                <dt class="font-semibold">Type</dt><dd>{{ \App\Models\DealerApplication::TYPES[$application->business_type] ?? $application->business_type }}</dd>
                <dt class="font-semibold">Years in business</dt><dd>{{ $application->years_in_business ?? '—' }}</dd>
            </dl>
            @if ($application->message)<p class="mt-4 whitespace-pre-line">{{ $application->message }}</p>@endif

            @if ($application->status === 'approved')
                <form method="POST" action="{{ route('admin.dealer-applications.convert', $application) }}" class="mt-6">@csrf<x-button>Create dealer listing</x-button></form>
            @endif

            <h3 class="mt-8 font-bold">Internal notes</h3>
            <form method="POST" action="{{ route('admin.dealer-applications.note', $application) }}" class="mt-2">@csrf<label for="body" class="sr-only">Note</label><textarea id="body" name="body" rows="3" required class="w-full rounded-control border border-steel px-3 py-2"></textarea><div class="mt-2"><x-button>Add note</x-button></div></form>
            <ul class="mt-4 space-y-3 text-sm">@foreach ($application->notes as $n)<li class="rounded-control bg-surface p-3"><p class="whitespace-pre-line">{{ $n->body }}</p><p class="mt-1 text-xs">{{ $n->author?->name ?? 'Unknown' }} · {{ $n->created_at->format('Y-m-d H:i') }}</p></li>@endforeach</ul>
        </section>
        <form method="POST" action="{{ route('admin.dealer-applications.update', $application) }}" class="rounded-panel border border-steel bg-white p-6 self-start">
            @csrf @method('PUT')
            <label class="block text-sm font-semibold mb-1" for="status">Status</label>
            <select id="status" name="status" class="mb-4 w-full rounded-control border border-steel px-3 py-2">@foreach (\App\Models\DealerApplication::STATUSES as $s)<option value="{{ $s }}" @selected($application->status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>@endforeach</select>
            <label class="block text-sm font-semibold mb-1" for="assigned_to">Assigned to</label>
            <select id="assigned_to" name="assigned_to" class="mb-4 w-full rounded-control border border-steel px-3 py-2"><option value="">Unassigned</option>@foreach ($staff as $u)<option value="{{ $u->id }}" @selected($application->assigned_to === $u->id)>{{ $u->name }}</option>@endforeach</select>
            <label class="block text-sm font-semibold mb-1" for="follow_up_on">Follow-up date</label>
            <input id="follow_up_on" type="date" name="follow_up_on" value="{{ $application->follow_up_on?->format('Y-m-d') }}" class="mb-4 w-full rounded-control border border-steel px-3 py-2">
            <x-button>Save</x-button>
            <p class="mt-3 text-xs">Changing the status emails the applicant.</p>
        </form>
    </div>
</x-layouts.admin-shell>
