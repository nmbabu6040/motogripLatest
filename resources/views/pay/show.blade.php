@php use App\Support\Money; $result = session('pay_result'); $input = 'w-full rounded-control border border-steel px-3 py-2'; @endphp
<x-layouts.public-shell :title="'Pay invoice '.$sale->number()" :noindex="true">
    <x-page-header :title="'Invoice '.$sale->number()" :eyebrow="\App\Support\Settings::get('brand', 'site_name', config('app.name'))" subtitle="Pay securely online" />
    <div class="container-x py-12">
        <div class="mx-auto max-w-3xl space-y-6">
            @if ($result === 'paid')
                <p role="status" class="rounded-panel border border-green-300 bg-green-50 p-5 font-semibold text-green-900">Payment received. Thank you! A receipt will be shared by the seller.</p>
            @elseif ($result === 'review')
                <p role="status" class="rounded-panel border border-amber/60 bg-amber/20 p-5 font-semibold">Your payment is being checked by the seller. If money left your account, please keep the transaction ID from your bank or wallet and contact us.</p>
            @elseif ($result === 'cancelled')
                <p role="status" class="rounded-panel border border-steel bg-surface p-5">You cancelled the payment. Nothing was charged.</p>
            @elseif ($result === 'failed')
                <p role="alert" class="rounded-panel border border-red-300 bg-red-50 p-5 text-red-900">The payment did not go through and nothing was recorded. You can try again below.</p>
            @endif
            @error('pay')<p role="alert" class="rounded-panel border border-red-300 bg-red-50 p-5 text-red-900">{{ $message }}</p>@enderror

            <section class="card p-6">
                <p class="text-sm">Billed to: <strong>{{ $sale->customerLabel() }}</strong> · {{ $sale->sold_on->format('d M Y') }}</p>
                <table class="mt-4 w-full text-left text-sm"><thead class="border-b border-steel"><tr><th class="py-2">Item</th><th class="py-2 text-right">Qty</th><th class="py-2 text-right">Amount</th></tr></thead>
                    <tbody>@foreach ($sale->items as $i)<tr class="border-b border-steel/50"><td class="py-2">{{ $i->stockItem->label() }}</td><td class="py-2 text-right">{{ $i->quantity }}</td><td class="py-2 text-right">{{ Money::format($i->line_total) }}</td></tr>@endforeach</tbody></table>
                <dl class="ml-auto mt-4 max-w-xs space-y-1 text-sm">
                    <div class="flex justify-between"><dt>Invoice total</dt><dd>{{ Money::format($sale->total) }}</dd></div>
                    <div class="flex justify-between"><dt>Already paid or returned</dt><dd>{{ Money::format((float) $sale->total - $due) }}</dd></div>
                    <div class="flex justify-between border-t border-steel pt-1 text-base font-extrabold text-navy"><dt>To pay now</dt><dd>{{ Money::format($due) }}</dd></div>
                </dl>
            </section>

            @if ($due > 0)
                <form method="POST" action="{{ route('pay.start', $token) }}" class="card grid gap-4 p-6 sm:grid-cols-2">
                    @csrf
                    <h2 class="text-lg font-extrabold text-navy sm:col-span-2">Your details</h2>
                    <div><label class="mb-1 block text-sm font-semibold" for="name">Name</label><input id="name" name="name" required value="{{ old('name', $sale->customer?->name ?? $sale->customer_name) }}" class="{{ $input }}"></div>
                    <div><label class="mb-1 block text-sm font-semibold" for="phone">Mobile number</label><input id="phone" name="phone" required value="{{ old('phone', $sale->customer?->phone) }}" class="{{ $input }}">@error('phone')<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-2"><label class="mb-1 block text-sm font-semibold" for="email">Email</label><input id="email" type="email" name="email" required value="{{ old('email', $sale->customer?->email) }}" class="{{ $input }}">@error('email')<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-2"><button class="btn btn-primary">Pay {{ Money::format($due) }} securely</button>
                        <p class="mt-3 text-xs text-ink/70">You will be taken to the SSLCommerz payment page (cards, bKash, Nagad and other options). Your card or wallet details are entered there; this website never sees them.</p></div>
                </form>
            @else
                <p class="rounded-panel border border-green-300 bg-green-50 p-5 font-semibold text-green-900">This invoice is paid in full.</p>
            @endif
        </div>
    </div>
</x-layouts.public-shell>
