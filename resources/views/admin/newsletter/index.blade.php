<x-layouts.admin-shell title="Newsletter">
    <dl class="grid max-w-3xl gap-4 sm:grid-cols-3">@foreach (['confirmed' => 'Confirmed', 'pending' => 'Awaiting confirmation', 'unsubscribed' => 'Unsubscribed'] as $k => $l)<div class="rounded-panel border border-steel bg-white p-4"><dt class="text-sm">{{ $l }}</dt><dd class="text-2xl font-extrabold text-navy">{{ $$k }}</dd></div>@endforeach</dl>
    <a href="{{ route('admin.newsletter.export') }}" class="mt-4 inline-block underline text-industrial">Export confirmed subscribers (CSV)</a>
    <ul class="mt-4 max-w-3xl divide-y divide-steel rounded-panel border border-steel bg-white text-sm">@forelse ($recent as $s)<li class="flex justify-between p-3"><span>{{ $s->email }}</span><span>{{ $s->unsubscribed_at ? 'Unsubscribed' : ($s->confirmed_at ? 'Confirmed' : 'Pending') }}</span></li>@empty<li class="p-4 text-center">No subscribers yet.</li>@endforelse</ul>
</x-layouts.admin-shell>
