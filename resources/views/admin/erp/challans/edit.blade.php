@php $input = 'w-full rounded-control border border-steel px-3 py-2'; @endphp
<x-layouts.admin-shell title="Edit challan {{ $challan->number() }}">
    <form method="POST" action="{{ route('admin.challans.update', $challan) }}" class="grid max-w-3xl gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-2">
        @csrf @method('PUT')
        <div><label class="mb-1 block text-sm font-semibold" for="delivered_on">Delivery date</label><input id="delivered_on" type="date" name="delivered_on" value="{{ old('delivered_on', $challan->delivered_on->toDateString()) }}" required class="{{ $input }}"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="receiver_name">Receiver name</label><input id="receiver_name" name="receiver_name" value="{{ old('receiver_name', $challan->receiver_name) }}" class="{{ $input }}"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="receiver_phone">Receiver phone</label><input id="receiver_phone" name="receiver_phone" value="{{ old('receiver_phone', $challan->receiver_phone) }}" class="{{ $input }}"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="vehicle_no">Vehicle no.</label><input id="vehicle_no" name="vehicle_no" value="{{ old('vehicle_no', $challan->vehicle_no) }}" class="{{ $input }}"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="driver_name">Driver / carrier</label><input id="driver_name" name="driver_name" value="{{ old('driver_name', $challan->driver_name) }}" class="{{ $input }}"></div>
        <div class="sm:col-span-2"><label class="mb-1 block text-sm font-semibold" for="delivery_address">Delivery address</label><textarea id="delivery_address" name="delivery_address" rows="2" class="{{ $input }}">{{ old('delivery_address', $challan->delivery_address) }}</textarea></div>
        <div class="sm:col-span-2"><label class="mb-1 block text-sm font-semibold" for="notes">Notes</label><input id="notes" name="notes" value="{{ old('notes', $challan->notes) }}" class="{{ $input }}"></div>
        <div class="sm:col-span-2"><x-button>Save</x-button> <a class="ml-3 underline text-industrial" href="{{ route('admin.challans.show', $challan) }}">Cancel</a></div>
    </form>
    <div class="mt-6 max-w-3xl rounded-panel border border-red-200 bg-white p-5">
        <h2 class="font-extrabold text-red-800">Delete this challan</h2>
        <p class="mt-1 text-sm">চালান স্টকে প্রভাব ফেলে না। মুছলে ওই পণ্যগুলো আবার "not yet delivered" হয়ে যায় এবং নতুন চালানে দেওয়া যায়।</p>
        <form method="POST" action="{{ route('admin.challans.destroy', $challan) }}" class="mt-3" x-on:submit="if (!confirm('Delete this challan?')) $event.preventDefault()">@csrf @method('DELETE')<button class="rounded-control border border-red-300 px-4 py-2 font-semibold text-red-800 hover:bg-red-50">Delete challan</button></form>
    </div>
</x-layouts.admin-shell>
