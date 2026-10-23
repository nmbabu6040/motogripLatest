<x-layouts.admin-shell title="Pages">
    <p class="mb-4 max-w-3xl text-sm">এখানে যত খুশি পেজ বানানো যায় (About Us, Privacy Policy, Terms, Warranty...) এবং কোনো কোড লাগে না। প্রকাশিত পেজ ফুটারে আসে, আর "Show in main menu" টিক দিলে ওপরের মেনুতেও আসে। <strong>About Us</strong>-এর জন্য slug দিন <code>about-us</code>, তাহলে নিজে থেকেই মেনুতে আসবে।</p>
    <a href="{{ route('admin.pages.create') }}" class="mb-4 inline-flex rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">Add page</a>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Title</th><th class="p-3">URL</th><th class="p-3">Layout</th><th class="p-3">In menu</th><th class="p-3">Status</th><th class="p-3"><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>@forelse ($pages as $p)<tr class="border-t border-steel">
            <td class="p-3">{{ $p->title }}</td><td class="p-3">/{{ $p->slug }}</td>
            <td class="p-3">{{ \App\Models\Page::LAYOUTS[$p->layout] ?? $p->layout }}@if ($p->image) · image @endif</td>
            <td class="p-3">{{ ($p->show_in_menu || in_array($p->slug, ['about-us', 'about'], true)) ? 'Yes' : '—' }}</td>
            <td class="p-3">{{ ucfirst($p->status) }}</td>
            <td class="flex gap-3 p-3"><a class="underline text-industrial" href="{{ route('admin.pages.edit', $p) }}">Edit<span class="sr-only"> {{ $p->title }}</span></a>
                <a class="underline text-industrial" target="_blank" rel="noopener" href="{{ route('page.show', ['page' => $p->slug]) }}">View</a>
                <form method="POST" action="{{ route('admin.pages.destroy', $p) }}" x-on:submit="if (!confirm('Delete this page?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Delete<span class="sr-only"> {{ $p->title }}</span></button></form></td></tr>
        @empty<tr><td colspan="6" class="p-6 text-center">No pages yet. Add your About Us and Privacy Policy here.</td></tr>@endforelse</tbody></table></div>
</x-layouts.admin-shell>
