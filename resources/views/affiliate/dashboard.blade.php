<x-layouts.admin-shell title="Affiliate dashboard">
    @if (!$affiliate)
        <form method="POST" action="{{ route('affiliate.apply') }}"
            class="max-w-xl rounded-panel border border-steel bg-white p-6">
            @csrf
            <p class="mb-3">Apply to join the referral programme. We review every application.</p>
            <label class="mb-4 flex items-start gap-2 text-sm">
                <input type="checkbox" name="terms" value="1" required class="mt-1"> I accept the affiliate terms
                and conditions published on this site.</label>
            <x-button>Apply</x-button>
        </form>
    @elseif ($affiliate->status !== 'approved')
        <p class="rounded-panel border border-steel bg-white p-6">Your application is {{ $affiliate->status }}. You will
            be able to use your referral link once it is approved.</p>
    @else
        <section class="max-w-3xl rounded-panel border border-steel bg-white p-6">
            <h2 class="font-extrabold">Your referral link</h2>
            <p class="mt-2 break-all rounded-control bg-surface p-3 font-mono text-sm">{{ $affiliate->link() }}</p>
            @foreach ($campaigns as $c)
                <p class="mt-2 break-all text-sm"><strong>{{ $c->name }}:</strong> <span
                        class="font-mono">{{ $affiliate->link($c) }}</span></p>
            @endforeach
            <p class="mt-3 text-sm">Referrals are credited when a visitor you send submits an inquiry or dealer
                application within the campaign window. Self-referrals are not credited.</p>
        </section>
        <dl class="mt-6 grid max-w-3xl gap-4 sm:grid-cols-3">
            @foreach (['clicks' => 'Clicks', 'conversions' => 'Conversions', 'approved' => 'Approved', 'commission_pending' => 'Commission pending', 'commission_paid' => 'Commission paid'] as $k => $l)
                <div class="rounded-panel border border-steel bg-white p-4">
                    <dt class="text-sm">{{ $l }}</dt>
                    <dd class="text-2xl font-extrabold text-navy">{{ $stats[$k] }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</x-layouts.admin-shell>
