<x-layouts.admin-shell title="Recommended links">
    <div class="mb-6 max-w-4xl rounded-panel border border-steel bg-white p-5 text-sm leading-relaxed">
        <p class="font-extrabold">এখানে Amazon বা AliExpress-এর আপনার নিজের অ্যাফিলিয়েট লিংক যোগ করুন। সাইটে <a class="underline text-industrial" target="_blank" rel="noopener" href="{{ route('partners.index') }}">/recommended</a> পাতায় এগুলো দেখাবে।</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>লিংকটি আপনার অ্যাফিলিয়েট অ্যাকাউন্ট থেকে কপি করুন। আমরা লিংক বদলাই না, সরাসরি দোকানে পাঠাই (কোনো রিডিরেক্ট নেই, Amazon এটাই চায়)।</li>
            <li>দাম এখানে দেখানো হয় না। Amazon নিজস্ব টুল ছাড়া দাম দেখাতে দেয় না, আর দাম প্রায়ই বদলায়।</li>
            <li>ছবি শুধু সেটাই দিন যা ব্যবহারের অধিকার আপনার আছে বা দোকানের নিয়মে অনুমোদিত।</li>
            <li>প্রকাশের আগে Settings → "Recommended products" এ ঘোষণার লেখা ঠিক আছে কি না দেখুন।</li>
        </ul>
    </div>
    <a href="{{ route('admin.partner-links.create') }}" class="mb-4 inline-flex rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">Add link</a>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Title</th><th class="p-3">Store</th><th class="p-3 text-right">Clicks</th><th class="p-3">Shown</th><th class="p-3"><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>@forelse ($links as $l)<tr class="border-t border-steel"><td class="p-3 font-semibold">{{ $l->title }}</td><td class="p-3">{{ \App\Models\PartnerLink::STORES[$l->store] ?? $l->store }}</td><td class="p-3 text-right">{{ number_format((int) ($clicks[$l->id] ?? 0)) }}</td><td class="p-3">{{ $l->is_published ? 'Yes' : 'Hidden' }}</td>
            <td class="flex gap-3 p-3"><a class="underline text-industrial" href="{{ route('admin.partner-links.edit', $l) }}">Edit<span class="sr-only"> {{ $l->title }}</span></a>
                <form method="POST" action="{{ route('admin.partner-links.destroy', $l) }}" x-on:submit="if (!confirm('Delete this link?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Delete<span class="sr-only"> {{ $l->title }}</span></button></form></td></tr>
        @empty<tr><td colspan="5" class="p-8 text-center">No links yet.</td></tr>@endforelse</tbody></table></div>
</x-layouts.admin-shell>
