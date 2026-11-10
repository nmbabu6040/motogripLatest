@php $input = 'w-full rounded-control border border-steel px-3 py-2'; $isCustomer = $party->kind === 'customer'; @endphp
<x-layouts.admin-shell title="Edit {{ $party->kind }}">
    <p class="mb-4 max-w-4xl rounded-panel border border-steel bg-white p-4 text-sm">{{ $isCustomer ? 'Customer owes you' : 'You owe this supplier' }}: <strong class="text-lg text-navy">{{ \App\Support\Money::format(max(0, $balance)) }}</strong>@if ($balance < 0) <span class="ml-2 text-xs">(credit of {{ \App\Support\Money::format(abs($balance)) }} in their favour)</span>@endif</p>
    <form method="POST" action="{{ route('admin.parties.update', $party) }}" class="grid max-w-4xl gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-2">
        @csrf @method('PUT')
        <div><label class="mb-1 block text-sm font-semibold" for="name">Name</label><input id="name" name="name" required value="{{ old('name', $party->name) }}" class="{{ $input }}"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $party->phone) }}" class="{{ $input }}"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $party->email) }}" class="{{ $input }}"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="address">Address</label><input id="address" name="address" value="{{ old('address', $party->address) }}" class="{{ $input }}"></div>
        <div class="sm:col-span-2"><label class="mb-1 block text-sm font-semibold" for="notes">Notes</label><textarea id="notes" name="notes" rows="3" class="{{ $input }}">{{ old('notes', $party->notes) }}</textarea></div>
        <div class="sm:col-span-2"><x-button>Save</x-button> <a class="ml-3 underline text-industrial" href="{{ route('admin.parties.index', $party->kind) }}">Cancel</a></div>
    </form>
</x-layouts.admin-shell>
