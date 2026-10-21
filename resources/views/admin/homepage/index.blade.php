<x-layouts.admin-shell title="Homepage sections">
    <p class="mb-4 max-w-3xl text-sm">Reorder, enable or disable sections and edit their text. Sections that need content
        (such as statistics) stay hidden on the site until you enter real, verified text.</p>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface">
                <tr>
                    <th class="p-3">Section</th>
                    <th class="p-3">Shown</th>
                    <th class="p-3">Order</th>
                    <th class="p-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sections as $s)
                    <tr class="border-t border-steel">
                        <td class="p-3 font-semibold">{{ ucfirst(str_replace('_', ' ', $s->key)) }}</td>
                        <td class="p-3">
                            <form method="POST" action="{{ route('admin.homepage.toggle', $s->key) }}">
                                @csrf
                                <button
                                    class="underline text-industrial">{{ $s->is_enabled ? 'Enabled (disable)' : 'Disabled (enable)' }}</button>
                            </form>
                        </td>
                        <td class="p-3">
                            <form method="POST" action="{{ route('admin.homepage.move', $s->key) }}"
                                class="flex gap-2">@csrf<button name="direction" value="up" class="underline"
                                    @if ($loop->first) disabled @endif
                                    aria-label="Move {{ $s->key }} up">Up</button><button name="direction"
                                    value="down" class="underline" @if ($loop->last) disabled @endif
                                    aria-label="Move {{ $s->key }} down">Down</button></form>
                        </td>
                        <td class="p-3"><a class="underline text-industrial"
                                href="{{ route('admin.homepage.edit', $s->key) }}">Edit text<span class="sr-only">
                                    {{ $s->key }}</span></a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin-shell>
