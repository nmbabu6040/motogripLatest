@php
    use App\Support\Money;
    $isSale = $kind === 'sale';
    $doc = $isSale ? $return->sale : $return->purchase;
    $total = $isSale ? $return->refund_total : $return->credit_total;
@endphp
<x-layouts.admin-shell title="{{ $isSale ? 'Sale return' : 'Purchase return' }} {{ $return->number() }}">
    <div class="no-print mb-4 flex gap-3"><button type="button" onclick="window.print()" class="rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">Print</button>
        <a class="self-center underline text-industrial" href="{{ route($isSale ? 'admin.sales.show' : 'admin.purchases.show', $doc) }}">← {{ $doc->number() }}</a></div>
    <article class="max-w-4xl rounded-panel border border-steel bg-white p-8 print:border-0 print:p-0">
        <div class="flex flex-wrap items-start justify-between gap-6">
            @include('admin.erp._company')
            <div class="text-right text-sm"><p class="text-2xl font-extrabold tracking-wide text-navy">{{ $isSale ? 'SALE RETURN' : 'PURCHASE RETURN' }}</p><p class="font-bold">{{ $return->number() }}</p><p>Date: {{ $return->returned_on->format('d M Y') }}</p><p>Against {{ $doc->number() }}</p></div>
        </div>
        <p class="mt-6 text-sm"><span class="text-xs font-bold uppercase tracking-wider text-ink/60">{{ $isSale ? 'Customer' : 'Supplier' }}</span><br><strong>{{ $isSale ? $doc->customerLabel() : $doc->supplier?->name }}</strong></p>
        @if ($return->reason)<p class="mt-2 text-sm"><strong>Reason:</strong> {{ $return->reason }}</p>@endif
        <table class="mt-6 w-full text-left text-sm">
            <thead class="border-y border-steel bg-surface"><tr><th class="p-2">#</th><th class="p-2">Item</th><th class="p-2 text-right">Qty</th><th class="p-2 text-right">{{ $isSale ? 'Price' : 'Cost' }}</th><th class="p-2 text-right">Amount</th></tr></thead>
            <tbody>@foreach ($return->items as $i)<tr class="border-b border-steel/60"><td class="p-2">{{ $loop->iteration }}</td><td class="p-2">{{ $i->stockItem->label() }}</td><td class="p-2 text-right">{{ $i->quantity }}</td><td class="p-2 text-right">{{ Money::format($isSale ? $i->unit_price : $i->unit_cost) }}</td><td class="p-2 text-right">{{ Money::format($i->line_total) }}</td></tr>@endforeach</tbody>
        </table>
        <p class="mt-4 text-right text-base font-extrabold text-navy">{{ $isSale ? 'Refund / credit to customer' : 'Credit from supplier' }}: {{ Money::format($total) }}</p>
        <p class="mt-1 text-right text-xs text-ink/70">{{ $isSale ? 'Includes the share of discount and tax for these items.' : 'Includes the share of the purchase discount.' }}</p>
    </article>
</x-layouts.admin-shell>
