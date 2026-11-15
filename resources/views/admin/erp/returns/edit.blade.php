@php $isSale = $kind === 'sale'; $input = 'w-full rounded-control border border-steel px-3 py-2'; @endphp
<x-layouts.admin-shell title="Edit return {{ $return->number() }}">
    @error('delete')<p role="alert" class="mb-4 max-w-3xl rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">{{ $message }}</p>@enderror
    <form method="POST" action="{{ $action }}" class="grid max-w-3xl gap-4 rounded-panel border border-steel bg-white p-6 sm:grid-cols-2">
        @csrf @method('PUT')
        <div><label class="mb-1 block text-sm font-semibold" for="returned_on">Date</label><input id="returned_on" type="date" name="returned_on" value="{{ old('returned_on', $return->returned_on->toDateString()) }}" required class="{{ $input }}"></div>
        <div><label class="mb-1 block text-sm font-semibold" for="reason">Reason</label><input id="reason" name="reason" maxlength="255" value="{{ old('reason', $return->reason) }}" class="{{ $input }}"></div>
        <p class="text-sm sm:col-span-2">পরিমাণ বদলাতে হলে এই রিটার্ন মুছে নতুন রিটার্ন বানান।</p>
        <div class="sm:col-span-2"><x-button>Save</x-button> <a class="ml-3 underline text-industrial" href="{{ route($isSale ? 'admin.sale-returns.show' : 'admin.purchase-returns.show', $return) }}">Cancel</a></div>
    </form>
    <div class="mt-6 max-w-3xl rounded-panel border border-red-200 bg-white p-5">
        <h2 class="font-extrabold text-red-800">Delete this return</h2>
        <p class="mt-1 text-sm">{{ $isSale ? 'ফেরত আসা পণ্য আবার স্টক থেকে বাদ যাবে এবং ইনভয়েসের বকেয়া আগের অবস্থায় ফিরবে। স্টকে পণ্য না থাকলে মোছা যাবে না।' : 'সাপ্লায়ারকে ফেরত পাঠানো পণ্য আবার স্টকে যোগ হবে এবং আপনার দেনা আগের অবস্থায় ফিরবে।' }}</p>
        <form method="POST" action="{{ $delete }}" class="mt-3" x-on:submit="if (!confirm('Delete this return?')) $event.preventDefault()">@csrf @method('DELETE')<button class="rounded-control border border-red-300 px-4 py-2 font-semibold text-red-800 hover:bg-red-50">Delete return</button></form>
    </div>
</x-layouts.admin-shell>
