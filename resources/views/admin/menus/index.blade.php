@php $input = 'w-full rounded-control border border-steel px-3 py-2'; @endphp
<x-layouts.admin-shell title="Menus">
    <p class="mb-6 max-w-3xl text-sm">এখানে ঠিক করুন সাইটের ওপরের মেনু ও নিচের ফুটারে কোন কোন লিংক, কী নামে, কোন ক্রমে দেখাবে। কোনো মেনুতে একটিও লিংক না থাকলে সাইট নিজে থেকে একটি মেনু বানিয়ে নেয় (আগের মতো)।</p>

    @foreach ($menus as $location => $menu)
        <section class="mb-10 max-w-5xl" aria-labelledby="m-{{ $location }}">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="m-{{ $location }}" class="text-xl font-extrabold text-navy">{{ $menu['title'] }}</h2>
                <div class="flex gap-3 text-sm">
                    <form method="POST" action="{{ route('admin.menus.defaults', $location) }}" x-on:submit="if (!confirm('Replace this menu with the standard links?')) $event.preventDefault()">@csrf<button class="underline text-industrial">Fill with standard links</button></form>
                    @if (count($menu['items']))<form method="POST" action="{{ route('admin.menus.reset', $location) }}" x-on:submit="if (!confirm('Remove all links and let the site choose automatically?')) $event.preventDefault()">@csrf<button class="underline text-red-800">Back to automatic</button></form>@endif
                </div>
            </div>

            @if (count($menu['items']) === 0)
                <p class="mt-3 rounded-control bg-surface p-3 text-sm">এই মেনু এখন <strong>স্বয়ংক্রিয়</strong>। নিজে সাজাতে নিচে লিংক যোগ করুন বা "Fill with standard links" চাপুন।</p>
            @else
                <div class="mt-3 overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
                    <thead class="bg-surface"><tr><th class="p-3">Name</th><th class="p-3">Goes to</th><th class="p-3">Shown</th><th class="p-3">Order</th><th class="p-3"><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                    @foreach ($menu['items'] as $item)
                        @php $names = json_decode($item->label, true) ?: ['en' => $item->label]; @endphp
                        <tr class="border-t border-steel align-top {{ $item->is_active ? '' : 'opacity-60' }}">
                            <td class="p-3 font-semibold">{{ $names['en'] ?? reset($names) }}@if (! empty($names['bn']))<br><span class="text-xs font-normal">{{ $names['bn'] }}</span>@endif</td>
                            <td class="p-3">{{ $item->route_name ? (\App\Http\Controllers\Admin\MenuController::ROUTES[$item->route_name] ?? $item->route_name) : $item->url }}@if ($item->opens_in_new_tab) <span class="text-xs">(new tab)</span>@endif</td>
                            <td class="p-3"><form method="POST" action="{{ route('admin.menus.toggle', $item->id) }}">@csrf<button class="underline text-industrial">{{ $item->is_active ? 'Shown (hide)' : 'Hidden (show)' }}</button></form></td>
                            <td class="p-3"><form method="POST" action="{{ route('admin.menus.move', $item->id) }}" class="flex gap-3">@csrf<button name="direction" value="up" class="underline" @if ($loop->first) disabled @endif aria-label="Move {{ $names['en'] ?? '' }} up">Up</button><button name="direction" value="down" class="underline" @if ($loop->last) disabled @endif aria-label="Move {{ $names['en'] ?? '' }} down">Down</button></form></td>
                            <td class="p-3">
                                <details><summary class="cursor-pointer underline text-industrial">Edit</summary>
                                    @include('admin.menus._form', ['action' => route('admin.menus.update', $item->id), 'method' => 'PUT', 'location' => $location, 'item' => $item, 'names' => $names, 'routes' => $routes, 'pages' => $pages, 'button' => 'Save'])
                                    <form method="POST" action="{{ route('admin.menus.destroy', $item->id) }}" class="mt-2" x-on:submit="if (!confirm('Remove this link?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Remove this link</button></form>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                    </tbody></table></div>
            @endif

            <details class="mt-4 rounded-panel border border-steel bg-white p-4"><summary class="cursor-pointer font-bold text-navy">+ Add a link to this menu</summary>
                @include('admin.menus._form', ['action' => route('admin.menus.create'), 'method' => 'POST', 'location' => $location, 'item' => null, 'names' => [], 'routes' => $routes, 'pages' => $pages, 'button' => 'Add link'])
            </details>
        </section>
    @endforeach
</x-layouts.admin-shell>
