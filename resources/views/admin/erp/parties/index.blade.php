@php $label = \App\Models\Party::KINDS[$kind]; $input = 'w-full rounded-control border border-steel px-3 py-2'; @endphp
<x-layouts.admin-shell title="{{ $label }}s">
    <form method="POST" action="{{ route('admin.parties.store', $kind) }}" class="mb-8 grid max-w-4xl gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-2">
        @csrf
        <h2 class="sm:col-span-2 text-lg font-extrabold">Add a {{ strtolower($label) }}</h2>
        <div><label class="mb-1 block text-sm font-semibold" for="name">Name</label><input id="name" name="name" required value="{{ old('name') }}" class="{{ $input }}"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone') }}" class="{{ $input }}"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" class="{{ $input }}"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="address">Address</label><input id="address" name="address" value="{{ old('address') }}" class="{{ $input }}"></div>
        <div class="sm:col-span-2"><x-button>Add {{ strtolower($label) }}</x-button></div>
    </form>
    <form method="GET" class="mb-4 flex gap-2" role="search"><label class="sr-only" for="q">Search</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Name, phone or email" class="rounded-control border border-steel px-3 py-2"><x-button>Search</x-button></form>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
        <thead class="bg-surface"><tr><th class="p-3">Name</th><th class="p-3">Phone</th><th class="p-3">Email</th><th class="p-3">Address</th><th class="p-3"><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>@forelse ($parties as $p)<tr class="border-t border-steel"><td class="p-3 font-semibold">{{ $p->name }}</td><td class="p-3">{{ $p->phone }}</td><td class="p-3">{{ $p->email }}</td><td class="p-3">{{ $p->address }}</td>
            <td class="flex gap-3 p-3"><a class="underline text-industrial" href="{{ route('admin.parties.edit', $p) }}">Edit<span class="sr-only"> {{ $p->name }}</span></a>
                <form method="POST" action="{{ route('admin.parties.destroy', $p) }}" x-on:submit="if (!confirm('Remove this {{ strtolower($label) }}? Old invoices keep the name.')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Remove<span class="sr-only"> {{ $p->name }}</span></button></form></td></tr>
        @empty<tr><td colspan="5" class="p-6 text-center">None yet.</td></tr>@endforelse</tbody></table></div>
    <div class="mt-4">{{ $parties->links() }}</div>
</x-layouts.admin-shell>
