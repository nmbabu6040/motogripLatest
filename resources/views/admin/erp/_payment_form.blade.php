@php $input = 'rounded-control border border-steel px-3 py-2'; @endphp
<form method="POST" action="{{ $action }}" class="no-print flex flex-wrap items-end gap-3 rounded-panel border border-steel bg-white p-5">
    @csrf
    <h3 class="w-full font-extrabold">{{ $title }}</h3>
    <div><label class="mb-1 block text-sm font-semibold" for="amount">Amount</label><input id="amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $due }}" value="{{ old('amount', $due) }}" required class="w-36 {{ $input }}"></div>
    <div><label class="mb-1 block text-sm font-semibold" for="method">Method</label><select id="method" name="method" class="{{ $input }}">@foreach (\App\Models\Payment::METHODS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
    <div><label class="mb-1 block text-sm font-semibold" for="paid_on">Date</label><input id="paid_on" name="paid_on" type="date" value="{{ today()->toDateString() }}" required class="{{ $input }}"></div>
    <div><label class="mb-1 block text-sm font-semibold" for="reference">Reference (optional)</label><input id="reference" name="reference" maxlength="190" placeholder="Transaction ID, cheque no." class="{{ $input }}"></div>
    <x-button>Record payment</x-button>
</form>
