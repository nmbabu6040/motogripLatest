@php use App\Support\Money; use App\Support\Settings; $due = $sale->dueAmount(); $paid = $sale->paidAmount(); $returned = $sale->returnedAmount(); @endphp
<x-layouts.admin-shell title="Invoice {{ $sale->number() }}">
    <div class="no-print mb-4 flex flex-wrap gap-3">
        <button type="button" onclick="window.print()" class="rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">Print invoice</button>
        @unless ($sale->isVoid())
            <a class="rounded-control border border-navy px-4 py-2.5 font-semibold text-navy hover:bg-white" href="{{ route('admin.sales.edit', $sale) }}">Edit</a>
            <a class="rounded-control border border-navy px-4 py-2.5 font-semibold text-navy hover:bg-white" href="{{ route('admin.sales.challans.create', $sale) }}">Create challan</a>
            <a class="rounded-control border border-navy px-4 py-2.5 font-semibold text-navy hover:bg-white" href="{{ route('admin.sales.returns.create', $sale) }}">Return items</a>
            <form method="POST" action="{{ route('admin.sales.void', $sale) }}" x-on:submit="if (!confirm('Void this invoice and put the stock back?')) $event.preventDefault()">@csrf<button class="rounded-control border border-red-300 px-4 py-2.5 font-semibold text-red-800 hover:bg-red-50">Void</button></form>
        @endunless
        <a class="self-center underline text-industrial" href="{{ route('admin.sales.index') }}">← All sales</a>
    </div>
    @error('void')<p role="alert" class="no-print mb-4 rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">{{ $message }}</p>@enderror

    <article class="max-w-4xl rounded-panel border border-steel bg-white p-8 print:border-0 print:p-0">
        <div class="flex flex-wrap items-start justify-between gap-6">
            @include('admin.erp._company')
            <div class="text-right text-sm"><p class="text-2xl font-extrabold tracking-wide text-navy">INVOICE</p><p class="font-bold">{{ $sale->number() }}</p><p>Date: {{ $sale->sold_on->format('d M Y') }}</p>@if ($sale->isVoid())<p class="mt-1 inline-block rounded bg-red-100 px-2 py-0.5 font-bold text-red-800">VOID</p>@endif</div>
        </div>
        <div class="mt-6 text-sm"><p class="text-xs font-bold uppercase tracking-wider text-ink/60">Bill to</p><p class="font-bold">{{ $sale->customerLabel() }}</p>@if ($sale->customer?->address)<p>{{ $sale->customer->address }}</p>@endif @if ($sale->customer?->phone)<p>{{ $sale->customer->phone }}</p>@endif</div>

        <table class="mt-6 w-full text-left text-sm">
            <thead class="border-y border-steel bg-surface"><tr><th class="p-2">#</th><th class="p-2">Item</th><th class="p-2 text-right">Qty</th><th class="p-2 text-right">Price</th><th class="p-2 text-right">Amount</th></tr></thead>
            <tbody>@foreach ($sale->items as $i)<tr class="border-b border-steel/60"><td class="p-2">{{ $loop->iteration }}</td><td class="p-2">{{ $i->stockItem->label() }}</td><td class="p-2 text-right">{{ $i->quantity }}</td><td class="p-2 text-right">{{ Money::format($i->unit_price) }}</td><td class="p-2 text-right">{{ Money::format($i->line_total) }}</td></tr>@endforeach</tbody>
        </table>

        <dl class="ml-auto mt-4 max-w-xs space-y-1 text-sm">
            <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ Money::format($sale->subtotal) }}</dd></div>
            @if ((float) $sale->discount > 0)<div class="flex justify-between"><dt>Discount</dt><dd>− {{ Money::format($sale->discount) }}</dd></div>@endif
            @if ((float) $sale->tax_amount > 0)<div class="flex justify-between"><dt>VAT / tax ({{ rtrim(rtrim((string) $sale->tax_percent, '0'), '.') }}%)</dt><dd>{{ Money::format($sale->tax_amount) }}</dd></div>@endif
            <div class="flex justify-between border-t border-steel pt-1 text-base font-extrabold text-navy"><dt>Total</dt><dd>{{ Money::format($sale->total) }}</dd></div>
            @if ($returned > 0)<div class="flex justify-between"><dt>Returned goods</dt><dd>− {{ Money::format($returned) }}</dd></div>@endif
            @if ($paid > 0)<div class="flex justify-between"><dt>Paid</dt><dd>− {{ Money::format($paid) }}</dd></div>@endif
            <div class="flex justify-between border-t border-steel pt-1 font-bold {{ $due > 0 ? 'text-red-700' : 'text-green-700' }}"><dt>{{ $sale->balance() < 0 ? 'Refund due to customer' : 'Balance due' }}</dt><dd>{{ Money::format(abs($sale->balance())) }}</dd></div>
        </dl>

        @if ($sale->notes)<p class="mt-6 text-sm"><strong>Notes:</strong> {{ $sale->notes }}</p>@endif
        @if ($note = Settings::get('erp', 'invoice_note'))<p class="mt-6 border-t border-steel pt-3 text-xs text-ink/70">{{ $note }}</p>@endif
    </article>

    <div class="no-print mt-8 grid max-w-4xl gap-6">
        @if (! $sale->isVoid() && $due > 0)
            @include('admin.erp._payment_form', ['action' => route('admin.sales.pay', $sale), 'title' => 'Receive a payment', 'due' => $due])
        @endif
        @if (! $sale->isVoid())
            <section class="rounded-panel border border-steel bg-white p-5 text-sm"><h3 class="mb-2 font-extrabold">Online payment</h3>
                @if (! $onlineEnabled)
                    <p>অনলাইন পেমেন্ট এখন বন্ধ। চালু করার নিয়ম <code>docs/BUSINESS.md</code> ফাইলে আছে (SSLCommerz-এর স্টোর আইডি ও পাসওয়ার্ড লাগবে)।</p>
                @elseif ($sale->pay_token)
                    <label class="mb-1 block font-semibold" for="paylink">Link to send to the customer</label>
                    <input id="paylink" readonly onclick="this.select()" value="{{ route('pay.show', $sale->pay_token) }}" class="w-full rounded-control border border-steel bg-surface px-3 py-2">
                @elseif ($due > 0)
                    <form method="POST" action="{{ route('admin.sales.paylink', $sale) }}">@csrf<button class="rounded-control border border-navy px-4 py-2 font-semibold text-navy hover:bg-surface">Create online payment link</button></form>
                @endif
                @if ($sale->onlinePayments->isNotEmpty())
                    <ul class="mt-3 space-y-1">@foreach ($sale->onlinePayments as $o)<li>{{ $o->created_at->format('d M Y H:i') }} · {{ Money::format($o->amount) }} · <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $o->status === 'paid' ? 'bg-green-100 text-green-900' : ($o->status === 'review' ? 'bg-amber/30' : 'bg-surface') }}">{{ ucfirst($o->status) }}</span> <span class="text-xs">{{ $o->tran_id }}</span>@if ($o->note)<br><span class="text-xs text-red-800">{{ $o->note }}</span>@endif</li>@endforeach</ul>
                @endif
            </section>
        @endif
        @if ($sale->payments->isNotEmpty() || $sale->returns->isNotEmpty() || $sale->challans->isNotEmpty())
            <section class="rounded-panel border border-steel bg-white p-5 text-sm"><h3 class="mb-2 font-extrabold">History</h3>
                <ul class="space-y-1">
                    @foreach ($sale->payments as $p)<li class="flex flex-wrap items-center gap-3"><span>Payment {{ Money::format($p->amount) }} · {{ \App\Models\Payment::METHODS[$p->method] ?? $p->method }} · {{ $p->paid_on->format('d M Y') }}@if ($p->reference) · {{ $p->reference }}@endif</span><form method="POST" action="{{ route('admin.payments.destroy', $p) }}" x-on:submit="if (!confirm('Remove this payment?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-800 underline">Remove</button></form></li>@endforeach
                    @foreach ($sale->returns as $r)<li>Return <a class="underline text-industrial" href="{{ route('admin.sale-returns.show', $r) }}">{{ $r->number() }}</a> · {{ Money::format($r->refund_total) }} · {{ $r->returned_on->format('d M Y') }}</li>@endforeach
                    @foreach ($sale->challans as $c)<li>Challan <a class="underline text-industrial" href="{{ route('admin.challans.show', $c) }}">{{ $c->number() }}</a> · {{ $c->delivered_on->format('d M Y') }}</li>@endforeach
                </ul></section>
        @endif
    </div>
</x-layouts.admin-shell>
