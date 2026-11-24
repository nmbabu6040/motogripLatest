@php
    $input = 'w-full rounded-control border border-steel px-3 py-2';
    $uid = 'f'.substr(md5($action), 0, 8);
    $current = ! $item ? 'route:home' : ($item->route_name ? 'route:'.$item->route_name : (preg_match('~^/([A-Za-z0-9-]+)$~', (string) $item->url, $m) && $pages->contains('slug', $m[1]) ? 'page:'.$m[1] : 'custom'));
@endphp
<form method="POST" action="{{ $action }}" class="mt-3 grid max-w-3xl gap-3 sm:grid-cols-2" x-data="{ target: '{{ $current }}' }">
    @csrf @if ($method !== 'POST') @method($method) @endif
    <input type="hidden" name="location" value="{{ $location }}">
    <div><label class="mb-1 block text-sm font-semibold" for="{{ $uid }}en">Name (English)</label><input id="{{ $uid }}en" name="label[en]" required maxlength="60" value="{{ $names['en'] ?? '' }}" class="{{ $input }}"></div>
    <div><label class="mb-1 block text-sm font-semibold" for="{{ $uid }}bn">Name (বাংলা, optional)</label><input id="{{ $uid }}bn" name="label[bn]" maxlength="60" value="{{ $names['bn'] ?? '' }}" class="{{ $input }}"></div>
    <div class="sm:col-span-2"><label class="mb-1 block text-sm font-semibold" for="{{ $uid }}t">Goes to</label>
        <select id="{{ $uid }}t" name="target" x-model="target" class="{{ $input }}">
            <optgroup label="Site sections">@foreach ($routes as $r => $label)<option value="route:{{ $r }}">{{ $label }}</option>@endforeach</optgroup>
            @if ($pages->isNotEmpty())<optgroup label="Pages you made">@foreach ($pages as $p)<option value="page:{{ $p->slug }}">{{ \App\Support\Lang::pick($p->title) }}</option>@endforeach</optgroup>@endif
            <option value="custom">Another address…</option>
        </select></div>
    <div class="sm:col-span-2" x-show="target === 'custom'" x-cloak><label class="mb-1 block text-sm font-semibold" for="{{ $uid }}u">Address</label><input id="{{ $uid }}u" name="custom_url" value="{{ ($item && ! $item->route_name && $current === 'custom') ? $item->url : '' }}" placeholder="/warranty  or  https://example.com" class="{{ $input }}"></div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="new_tab" value="1" @checked($item && $item->opens_in_new_tab)> Open in a new tab</label>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(! $item || $item->is_active)> Show on the website</label>
    <div class="sm:col-span-2"><x-button>{{ $button }}</x-button></div>
</form>
