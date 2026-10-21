<x-layouts.admin-shell title="SEO checklist">
    <div class="grid gap-6 lg:grid-cols-2 max-w-6xl">
        <section class="rounded-panel border border-steel bg-white p-6">
            <h2 class="text-lg font-extrabold">Site</h2>
            <ul class="mt-3 space-y-1 text-sm">
                @foreach ($site as [$label, $ok])
                    <li><span aria-hidden="true">{{ $ok ? '✔' : '✖' }}</span><span
                            class="sr-only">{{ $ok ? 'Done:' : 'To do:' }}</span> {{ $label }}</li>
                @endforeach
            </ul>
            <p class="mt-4 text-sm">Sitemap: <a class="underline text-industrial" href="{{ url('/sitemap.xml') }}"
                    target="_blank" rel="noopener">{{ url('/sitemap.xml') }}</a> · <a class="underline text-industrial"
                    href="{{ url('/robots.txt') }}" target="_blank" rel="noopener">robots.txt</a></p>
        </section>
        <section class="rounded-panel border border-steel bg-white p-6">
            <h2 class="text-lg font-extrabold">Content</h2>
            @foreach ($content as [$label, $items])
                <details class="mt-3 text-sm" @if ($items->isNotEmpty()) open @endif>
                    <summary class="cursor-pointer font-semibold">{{ $items->isEmpty() ? '✔' : '✖' }}
                        {{ $label }} ({{ $items->count() }})</summary>
                    <ul class="mt-1 list-disc pl-6">
                        @foreach ($items as [$name, $url])
                            <li><a class="underline text-industrial" href="{{ $url }}">{{ $name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endforeach
        </section>
    </div>

    <section class="mt-8 max-w-4xl rounded-panel border border-steel bg-white p-6">
        <h2 class="text-lg font-extrabold">Redirects</h2>
        <form method="POST" action="{{ route('admin.seo.redirects.store') }}"
            class="mt-3 grid gap-3 sm:grid-cols-[1fr_1fr_6rem_auto] items-end">
            @csrf
            <x-field name="from_path" label="From path (e.g. /old-page)" required /><x-field name="to_url"
                label="To (path or full URL)" required />
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1" for="sc">Type</label><select id="sc"
                    name="status_code" class="w-full rounded-control border border-steel px-2 py-2">
                    <option value="301">301</option>
                    <option value="302">302</option>
                </select>
            </div>
            <div class="mb-4">
                <x-button>Add</x-button>
            </div>
        </form>
        <table class="mt-2 w-full text-left text-sm">
            <thead class="bg-surface">
                <tr>
                    <th class="p-2">From</th>
                    <th class="p-2">To</th>
                    <th class="p-2">Hits</th>
                    <th class="p-2"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($redirects as $r)
                    <tr class="border-t border-steel">
                        <td class="p-2">{{ $r->from_path }}</td>
                        <td class="p-2 break-all">{{ $r->to_url }}</td>
                        <td class="p-2">{{ $r->hits }}</td>
                        <td class="p-2">
                            <form method="POST" action="{{ route('admin.seo.redirects.destroy', $r->id) }}"
                                x-on:submit="if (!confirm('Delete this redirect?')) $event.preventDefault()">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-800 underline">Delete<span class="sr-only">
                                        {{ $r->from_path }}</span></button>
                            </form>
                        </td>
                    </tr>
                @empty<tr>
                        <td colspan="4" class="p-4 text-center">No redirects yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</x-layouts.admin-shell>
