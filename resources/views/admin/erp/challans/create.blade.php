@php $input = 'w-full rounded-control border border-steel px-3 py-2'; @endphp
<x-layouts.admin-shell title="Challan for {{ $sale->number() }}">
    @error('challan')<p role="alert" class="mb-4 max-w-4xl rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">{{ $message }}</p>@enderror
    <form method="POST" action="{{ route('admin.sales.challans.store', $sale) }}" class="max-w-4xl space-y-6">
        @csrf
        <div class="overflow-x-auto rounded-panel border border-steel bg-white"><table class="w-full text-left text-sm">
            <thead class="bg-surface"><tr><th class="p-3">Item</th><th class="p-3 text-right">On invoice</th><th class="p-3 text-right">Not yet delivered</th><th class="p-3">Deliver now</th></tr></thead>
            <tbody>@foreach ($lines as $l)<tr class="border-t border-steel"><td class="p-3">{{ $l['label'] }}</td><td class="p-3 text-right">{{ $l['sold'] }}</td><td class="p-3 text-right">{{ $l['left'] }}</td>
                <td class="p-3">@if ($l['left'] > 0)<input type="number" min="0" max="{{ $l['left'] }}" name="qty[{{ $l['id'] }}]" value="{{ old('qty.'.$l['id'], $l['left']) }}" aria-label="Quantity for {{ $l['label'] }}" class="w-24 rounded-control border border-steel px-3 py-2">@else<span class="text-xs">delivered</span>@endif</td></tr>@endforeach</tbody></table></div>
        <div class="grid gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-2">
            <div><label class="mb-1 block text-sm font-semibold" for="delivered_on">Delivery date</label><input id="delivered_on" type="date" name="delivered_on" value="{{ old('delivered_on', today()->toDateString()) }}" required class="{{ $input }}"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="receiver_name">Receiver name</label><input id="receiver_name" name="receiver_name" value="{{ old('receiver_name', $sale->customerLabel()) }}" class="{{ $input }}"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="receiver_phone">Receiver phone</label><input id="receiver_phone" name="receiver_phone" value="{{ old('receiver_phone', $sale->customer?->phone) }}" class="{{ $input }}"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="vehicle_no">Vehicle no.</label><input id="vehicle_no" name="vehicle_no" value="{{ old('vehicle_no') }}" class="{{ $input }}"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="driver_name">Driver / carrier</label><input id="driver_name" name="driver_name" value="{{ old('driver_name') }}" class="{{ $input }}"></div>
            <div class="sm:col-span-2"><label class="mb-1 block text-sm font-semibold" for="delivery_address">Delivery address</label><textarea id="delivery_address" name="delivery_address" rows="2" class="{{ $input }}">{{ old('delivery_address', $sale->customer?->address) }}</textarea></div>
            <div class="sm:col-span-2"><label class="mb-1 block text-sm font-semibold" for="notes">Notes</label><input id="notes" name="notes" value="{{ old('notes') }}" class="{{ $input }}"></div>
        </div>
        <div><x-button>Create challan</x-button> <a class="ml-3 underline text-industrial" href="{{ route('admin.sales.show', $sale) }}">Cancel</a></div>
    </form>
</x-layouts.admin-shell>
