<x-layouts.admin-shell title="Live chat">
    @unless (\App\Support\Settings::get('chat', 'enabled'))
        <p class="mb-4 max-w-3xl rounded-panel border border-amber/60 bg-amber/10 p-4 text-sm">লাইভ চ্যাট এখন <strong>বন্ধ</strong>, সাইটে চ্যাট বোতাম দেখা যাচ্ছে না। চালু করতে <a class="underline text-industrial" href="{{ route('admin.settings.edit') }}">Settings → Live chat</a> এ "Show the live chat button" টিক দিন।</p>
    @endunless
    <div class="mb-4 flex flex-wrap gap-3 text-sm">
        @foreach (['open' => 'Open ('.$openCount.')', 'closed' => 'Closed ('.$closedCount.')', 'all' => 'All'] as $k => $l)<a href="{{ route('admin.chats.index', ['status' => $k]) }}" class="rounded-full border px-4 py-1.5 font-semibold {{ $status === $k ? 'border-navy bg-navy text-white' : 'border-steel bg-white text-navy hover:bg-surface' }}">{{ $l }}</a>@endforeach
    </div>
    <div class="max-w-4xl overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Visitor</th><th class="p-3">Messages</th><th class="p-3">Last message</th><th class="p-3">Status</th></tr></thead>
        <tbody>@forelse ($chats as $c)
            <tr class="border-t border-steel"><td class="p-3"><a class="font-semibold text-industrial underline" href="{{ route('admin.chats.show', $c) }}">{{ $c->label() }}</a>@if ($c->staff_unread > 0) <span class="ml-2 rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold text-white">{{ $c->staff_unread }} new</span>@endif @if ($c->visitor_email)<br><span class="text-xs">{{ $c->visitor_email }}</span>@endif</td>
                <td class="p-3">{{ $c->messages_count }}</td><td class="p-3 whitespace-nowrap">{{ $c->last_message_at?->diffForHumans() }}</td>
                <td class="p-3">{{ ucfirst($c->status) }}</td></tr>
        @empty<tr><td colspan="4" class="p-8 text-center">কোনো চ্যাট নেই।</td></tr>@endforelse</tbody></table></div>
    <div class="mt-4">{{ $chats->links() }}</div>
</x-layouts.admin-shell>
