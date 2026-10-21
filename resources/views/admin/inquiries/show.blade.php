<x-layouts.admin-shell title="Inquiry">
    <div class="grid gap-6 lg:grid-cols-[1fr_22rem] max-w-6xl">
        <section class="rounded-panel border border-steel bg-white p-6">
            <h2 class="text-lg font-extrabold">{{ $inquiry->name }}</h2>
            <p class="text-sm">{{ $inquiry->email }}
                @if ($inquiry->phone)
                    · {{ $inquiry->phone }}
                @endif
                @if ($inquiry->company)
                    · {{ $inquiry->company }}
                @endif
            </p>
            <p class="mt-1 text-sm">{{ \App\Models\Inquiry::TYPES[$inquiry->type] ?? $inquiry->type }} ·
                {{ $inquiry->created_at->format('Y-m-d H:i') }}
                @if ($inquiry->product)
                    · {{ $inquiry->product->sku }}
                @endif
            </p>
            <p class="mt-4 whitespace-pre-line">{{ $inquiry->message }}</p>
            <a class="mt-4 inline-block underline text-industrial" href="mailto:{{ $inquiry->email }}">Reply by email</a>

            <h3 class="mt-8 font-bold">Internal notes</h3>
            <form method="POST" action="{{ route('admin.inquiries.note', $inquiry) }}" class="mt-2">
                @csrf
                <label for="body" class="sr-only">Note</label>
                <textarea id="body" name="body" rows="3" required
                    class="w-full rounded-control border border-steel px-3 py-2"></textarea>
                <div class="mt-2">
                    <x-button>Add note</x-button>
                </div>
            </form>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach ($inquiry->notes as $n)
                    <li class="rounded-control bg-surface p-3">
                        <p class="whitespace-pre-line">{{ $n->body }}</p>
                        <p class="mt-1 text-xs">{{ $n->author?->name ?? 'Unknown' }} ·
                            {{ $n->created_at->format('Y-m-d H:i') }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
        <form method="POST" action="{{ route('admin.inquiries.update', $inquiry) }}"
            class="rounded-panel border border-steel bg-white p-6 self-start">
            @csrf
            @method('PUT')
            <label class="block text-sm font-semibold mb-1" for="status">Status</label>
            <select id="status" name="status" class="mb-4 w-full rounded-control border border-steel px-3 py-2">
                @foreach (\App\Models\Inquiry::STATUSES as $s)
                    <option value="{{ $s }}" @selected($inquiry->status === $s)>
                        {{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
            @can('inquiries.view-all')
                <label class="block text-sm font-semibold mb-1" for="assigned_to">Assigned to</label>
                <select id="assigned_to" name="assigned_to"
                    class="mb-4 w-full rounded-control border border-steel px-3 py-2">
                    <option value="">Unassigned</option>
                    @foreach ($staff as $u)
                        <option value="{{ $u->id }}" @selected($inquiry->assigned_to === $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            @endcan
            <label class="block text-sm font-semibold mb-1" for="follow_up_on">Follow-up date</label>
            <input id="follow_up_on" type="date" name="follow_up_on"
                value="{{ $inquiry->follow_up_on?->format('Y-m-d') }}"
                class="mb-4 w-full rounded-control border border-steel px-3 py-2">
            <x-button>Save</x-button>
        </form>
    </div>
</x-layouts.admin-shell>
