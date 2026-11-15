<x-layouts.admin-shell title="Challan {{ $challan->number() }}">
    <div class="no-print mb-4 flex gap-3"><button type="button" onclick="window.print()" class="rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">Print challan</button>
        <a class="self-center underline text-industrial" href="{{ route('admin.sales.show', $challan->sale) }}">← {{ $challan->sale->number() }}</a>
        <a class="self-center underline text-industrial" href="{{ route('admin.challans.edit', $challan) }}">Edit or delete</a></div>
    <article class="max-w-4xl rounded-panel border border-steel bg-white p-8 print:border-0 print:p-0">
        <div class="flex flex-wrap items-start justify-between gap-6">
            @include('admin.erp._company')
            <div class="text-right text-sm"><p class="text-2xl font-extrabold tracking-wide text-navy">DELIVERY CHALLAN</p><p class="font-bold">{{ $challan->number() }}</p><p>Date: {{ $challan->delivered_on->format('d M Y') }}</p><p>Invoice: {{ $challan->sale->number() }}</p></div>
        </div>
        <div class="mt-6 grid gap-6 text-sm sm:grid-cols-2">
            <div><p class="text-xs font-bold uppercase tracking-wider text-ink/60">Deliver to</p><p class="font-bold">{{ $challan->receiver_name ?: $challan->sale->customerLabel() }}</p>@if ($challan->delivery_address)<p>{{ $challan->delivery_address }}</p>@endif @if ($challan->receiver_phone)<p>{{ $challan->receiver_phone }}</p>@endif</div>
            <div><p class="text-xs font-bold uppercase tracking-wider text-ink/60">Transport</p>@if ($challan->vehicle_no)<p>Vehicle: {{ $challan->vehicle_no }}</p>@endif @if ($challan->driver_name)<p>Driver: {{ $challan->driver_name }}</p>@endif</div>
        </div>
        <table class="mt-6 w-full text-left text-sm">
            <thead class="border-y border-steel bg-surface"><tr><th class="p-2">#</th><th class="p-2">Item</th><th class="p-2 text-right">Quantity delivered</th></tr></thead>
            <tbody>@foreach ($challan->items as $i)<tr class="border-b border-steel/60"><td class="p-2">{{ $loop->iteration }}</td><td class="p-2">{{ $i->saleItem->stockItem->label() }}</td><td class="p-2 text-right font-bold">{{ $i->quantity }}</td></tr>@endforeach</tbody>
        </table>
        @if ($challan->notes)<p class="mt-4 text-sm"><strong>Notes:</strong> {{ $challan->notes }}</p>@endif
        <p class="mt-4 text-xs text-ink/70">Goods received in good condition unless noted above.</p>
        <div class="mt-16 grid grid-cols-3 gap-8 text-center text-xs"><div class="border-t border-ink pt-2">Prepared by</div><div class="border-t border-ink pt-2">Driver / carrier</div><div class="border-t border-ink pt-2">Received by (name, signature, date)</div></div>
    </article>
</x-layouts.admin-shell>
