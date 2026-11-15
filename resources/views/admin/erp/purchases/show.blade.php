@php use App\Support\Money; $due = $purchase->dueAmount(); $paid = $purchase->paidAmount(); $returned = $purchase->returnedAmount(); @endphp
<x-layouts.admin-shell title="Purchase {{ $purchase->number() }}">
    <div class="no-print mb-4 flex flex-wrap gap-3">
        <button type="button" onclick="window.print()" class="rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">Print</button>
        @unless ($purchase->isVoid())
            <a class="rounded-control border border-navy px-4 py-2.5 font-semibold text-navy hover:bg-white" href="{{ route('admin.purchases.edit', $purchase) }}">Edit</a>
            <a class="rounded-control border border-navy px-4 py-2.5 font-semibold text-navy hover:bg-white" href="{{ route('admin.purchases.returns.create', $purchase) }}">Return items to supplier</a>
            <form method="POST" action="{{ route('admin.purchases.void', $purchase) }}" x-on:submit="if (!confirm('Void this purchase and take the stock out again?')) $event.preventDefault()">@csrf<button class="rounded-control border border-red-300 px-4 py-2.5 font-semibold text-red-800 hover:bg-red-50">Void</button></form>
        @endunless
        <a class="self-center underline text-industrial" href="{{ route('admin.purchases.index') }}">← All purchases</a>
    </div>
    @error('void')<p role="alert" class="no-print mb-4 rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">{{ $message }}</p>@enderror

    <article class="max-w-4xl rounded-panel border border-steel bg-white p-8 print:border-0 print:p-0">
        <div class="flex flex-wrap items-start justify-between gap-6">
            @include('admin.erp._company')
            <div class="text-right text-sm"><p class="text-2xl font-extrabold tracking-wide text-navy">PURCHASE</p><p class="font-bold">{{ $purchase->number() }}</p><p>Date: {{ $purchase->purchased_on->format('d M Y') }}</p>@if ($purchase->isVoid())<p class="mt-1 inline-block rounded bg-red-100 px-2 py-0.5 font-bold text-red-800">VOID</p>@endif</div>
        </div>
        <div class="mt-6 text-sm"><p class="text-xs font-bold uppercase tracking-wider text-ink/60">Supplier</p><p class="font-bold">{{ $purchase->supplier?->name }}</p>@if ($purchase->supplier?->address)<p>{{ $purchase->supplier->address }}</p>@endif @if ($purchase->supplier?->phone)<p>{{ $purchase->supplier->phone }}</p>@endif</div>

        <table class="mt-6 w-full text-left text-sm">
            <thead class="border-y border-steel bg-surface"><tr><th class="p-2">#</th><th class="p-2">Item</th><th class="p-2 text-right">Qty</th><th class="p-2 text-right">Cost</th><th class="p-2 text-right">Amount</th></tr></thead>
            <tbody>@foreach ($purchase->items as $i)<tr class="border-b border-steel/60"><td class="p-2">{{ $loop->iteration }}</td><td class="p-2">{{ $i->stockItem->label() }}</td><td class="p-2 text-right">{{ $i->quantity }}</td><td class="p-2 text-right">{{ Money::format($i->unit_cost) }}</td><td class="p-2 text-right">{{ Money::format($i->line_total) }}</td></tr>@endforeach</tbody>
        </table>
        <dl class="ml-auto mt-4 max-w-xs space-y-1 text-sm">
            <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ Money::format($purchase->subtotal) }}</dd></div>
            @if ((float) $purchase->discount > 0)<div class="flex justify-between"><dt>Discount</dt><dd>− {{ Money::format($purchase->discount) }}</dd></div>@endif
            <div class="flex justify-between border-t border-steel pt-1 text-base font-extrabold text-navy"><dt>Total</dt><dd>{{ Money::format($purchase->total) }}</dd></div>
            @if ($returned > 0)<div class="flex justify-between"><dt>Returned to supplier</dt><dd>− {{ Money::format($returned) }}</dd></div>@endif
            @if ($paid > 0)<div class="flex justify-between"><dt>Paid</dt><dd>− {{ Money::format($paid) }}</dd></div>@endif
            <div class="flex justify-between border-t border-steel pt-1 font-bold {{ $due > 0 ? 'text-red-700' : 'text-green-700' }}"><dt>{{ $purchase->balance() < 0 ? 'Supplier owes us' : 'We still owe' }}</dt><dd>{{ Money::format(abs($purchase->balance())) }}</dd></div>
        </dl>
        @if ($purchase->notes)<p class="mt-6 text-sm"><strong>Notes:</strong> {{ $purchase->notes }}</p>@endif
    </article>

    <div class="no-print mt-8 grid max-w-4xl gap-6">
        @if (! $purchase->isVoid() && $due > 0)
            @include('admin.erp._payment_form', ['action' => route('admin.purchases.pay', $purchase), 'title' => 'Pay the supplier', 'due' => $due])
        @endif
        @if ($purchase->payments->isNotEmpty() || $purchase->returns->isNotEmpty())
            <section class="rounded-panel border border-steel bg-white p-5 text-sm"><h3 class="mb-2 font-extrabold">History</h3>
                <ul class="space-y-1">
                    @foreach ($purchase->payments as $p)<li class="flex flex-wrap items-center gap-3"><span>Payment {{ Money::format($p->amount) }} · {{ \App\Models\Payment::METHODS[$p->method] ?? $p->method }} · {{ $p->paid_on->format('d M Y') }}@if ($p->reference) · {{ $p->reference }}@endif</span><form method="POST" action="{{ route('admin.payments.destroy', $p) }}" x-on:submit="if (!confirm('Remove this payment?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Remove</button></form></li>@endforeach
                    @foreach ($purchase->returns as $r)<li>Return <a class="underline text-industrial" href="{{ route('admin.purchase-returns.show', $r) }}">{{ $r->number() }}</a> · {{ Money::format($r->credit_total) }} · {{ $r->returned_on->format('d M Y') }}</li>@endforeach
                </ul></section>
        @endif
    </div>
</x-layouts.admin-shell>
