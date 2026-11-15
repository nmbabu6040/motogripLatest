<x-layouts.admin-shell title="Newsletter">
    <dl class="grid max-w-3xl gap-4 sm:grid-cols-3">@foreach (['confirmed' => 'Confirmed', 'pending' => 'Awaiting confirmation', 'unsubscribed' => 'Unsubscribed'] as $k => $l)<a href="{{ route('admin.newsletter.index', ['status' => $k]) }}" class="rounded-panel border border-steel bg-white p-4 transition hover:border-industrial"><dt class="text-sm">{{ $l }}</dt><dd class="text-2xl font-extrabold text-navy">{{ $$k }}</dd></a>@endforeach</dl>

    <div class="mt-6 flex max-w-5xl flex-wrap items-end justify-between gap-3">
        <form method="GET" class="flex flex-wrap gap-2" role="search">
            <label class="sr-only" for="q">Search</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Email address" class="rounded-control border border-steel px-3 py-2">
            <label class="sr-only" for="status">Status</label><select id="status" name="status" class="rounded-control border border-steel px-3 py-2"><option value="">All</option>@foreach (['pending' => 'Awaiting confirmation', 'confirmed' => 'Confirmed', 'unsubscribed' => 'Unsubscribed'] as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select>
            <x-button>Filter</x-button>
        </form>
        <a href="{{ route('admin.newsletter.export') }}" class="underline text-industrial">Export confirmed subscribers (CSV)</a>
    </div>

    <p class="mt-3 max-w-5xl text-sm">"Awaiting confirmation" মানে ব্যক্তিটি এখনো ইমেইলের লিংকে চাপেনি। আপনার কম্পিউটারে ইমেইল পাঠানো ঠিক না থাকলে লিংক তাদের কাছে পৌঁছায় না। যার সম্মতি আপনি নিজে নিয়েছেন (ফোনে বা সামনাসামনি) শুধু তাকেই "Confirm" করুন।</p>

    <div class="mt-4 max-w-5xl overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Email</th><th class="p-3">Status</th><th class="p-3">Joined</th><th class="p-3"><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>
        @forelse ($subscribers as $s)
            @php $state = $s->unsubscribed_at ? 'Unsubscribed' : ($s->confirmed_at ? 'Confirmed' : 'Pending'); @endphp
            <tr class="border-t border-steel align-top">
                <td class="p-3">{{ $s->email }}</td>
                <td class="p-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $state === 'Confirmed' ? 'bg-green-100 text-green-900' : ($state === 'Pending' ? 'bg-amber/30' : 'bg-red-100 text-red-800') }}">{{ $state }}</span></td>
                <td class="p-3 whitespace-nowrap">{{ $s->created_at->format('Y-m-d') }}</td>
                <td class="flex flex-wrap gap-3 p-3">
                    @if ($state !== 'Confirmed')
                        <form method="POST" action="{{ route('admin.newsletter.confirm', $s) }}" x-on:submit="if (!confirm('Confirm this subscriber? Only do this if you have their consent.')) $event.preventDefault()">@csrf<button class="underline text-industrial">Confirm<span class="sr-only"> {{ $s->email }}</span></button></form>
                    @endif
                    @if ($state === 'Pending')
                        <form method="POST" action="{{ route('admin.newsletter.resend', $s) }}">@csrf<button class="underline text-industrial">Resend email<span class="sr-only"> to {{ $s->email }}</span></button></form>
                    @endif
                    @if ($state !== 'Unsubscribed')
                        <form method="POST" action="{{ route('admin.newsletter.unsubscribe', $s) }}" x-on:submit="if (!confirm('Unsubscribe this person?')) $event.preventDefault()">@csrf<button class="underline">Unsubscribe<span class="sr-only"> {{ $s->email }}</span></button></form>
                    @endif
                    <form method="POST" action="{{ route('admin.newsletter.destroy', $s) }}" x-on:submit="if (!confirm('Delete this subscriber permanently?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Delete<span class="sr-only"> {{ $s->email }}</span></button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="p-8 text-center">No subscribers found.</td></tr>
        @endforelse
        </tbody></table></div>
    <div class="mt-4">{{ $subscribers->links() }}</div>
</x-layouts.admin-shell>
