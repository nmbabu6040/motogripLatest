<x-layouts.admin-shell title="Homepage sections">
    <p class="mb-4 max-w-3xl text-sm">হোমপেজের প্রতিটি অংশ এখানে সাজান: ওপর-নিচ করুন, চালু/বন্ধ করুন, আর লেখা বদলান। যে সেকশনের তথ্য অন্য জায়গা থেকে আসে (যেমন পণ্য বা FAQ), সেখানে সঠিক জায়গার লিংক দেওয়া আছে। লেখা না থাকলে কিছু সেকশন সাইটে দেখায় না।</p>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Section</th><th class="p-3">Shown</th><th class="p-3">Order</th><th class="p-3">Where to edit the content</th></tr></thead>
        <tbody>
        @foreach ($sections as $s)
            @php [$mode, $where] = $info[$s->key] ?? ['data', null]; @endphp
            <tr class="border-t border-steel align-top">
                <td class="p-3 font-semibold">{{ ucfirst(str_replace('_', ' ', $s->key)) }}</td>
                <td class="p-3"><form method="POST" action="{{ route('admin.homepage.toggle', $s->key) }}">@csrf<button class="underline text-industrial">{{ $s->is_enabled ? 'Enabled (disable)' : 'Disabled (enable)' }}</button></form></td>
                <td class="p-3"><form method="POST" action="{{ route('admin.homepage.move', $s->key) }}" class="flex gap-2">@csrf<button name="direction" value="up" class="underline" @if ($loop->first) disabled @endif aria-label="Move {{ $s->key }} up">Up</button><button name="direction" value="down" class="underline" @if ($loop->last) disabled @endif aria-label="Move {{ $s->key }} down">Down</button></form></td>
                <td class="space-y-1 p-3">
                    <a class="block underline text-industrial" href="{{ route('admin.homepage.edit', $s->key) }}">{{ in_array($mode, ['data'], true) ? 'Edit section title' : 'Edit text' }}<span class="sr-only"> {{ $s->key }}</span></a>
                    @if ($where)<a class="block text-ink/80 underline" href="{{ route($where[1]) }}">{{ $mode === 'hero' ? $where[0] : 'Content: '.$where[0] }}</a>@endif
                </td>
            </tr>
        @endforeach
        </tbody></table></div>
    <p class="mt-4 max-w-3xl text-sm"><strong>About Us</strong> হোমপেজের সেকশন নয়, এটি আলাদা পেজ: <a class="underline text-industrial" href="{{ route('admin.pages.index') }}">Pages</a> থেকে বানান (URL slug: <code>about-us</code>)।</p>
</x-layouts.admin-shell>
