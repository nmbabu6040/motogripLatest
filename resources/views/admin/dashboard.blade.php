@php use App\Support\Money; $sym = Money::symbol(); @endphp
<x-layouts.admin-shell title="Dashboard">
    {{-- Welcome --}}
    <section class="anim-rise relative isolate overflow-hidden rounded-panel bg-gradient-to-br from-navy-deep via-navy to-industrial p-6 text-white shadow-card sm:p-8">
        <div class="bg-grid absolute inset-0 -z-10 opacity-50" aria-hidden="true"></div>
        <div class="hero-glow absolute -right-10 -top-16 -z-10 h-56 w-56 rounded-full bg-amber/30 blur-3xl" aria-hidden="true"></div>
        <p class="text-sm text-white/70">{{ now()->format('l, j F Y') }}</p>
        <h2 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $greeting }}, {{ strtok(auth()->user()->name, ' ') }}</h2>
        @if ($quick)
            <div class="mt-5 flex flex-wrap gap-2">@foreach ($quick as [$label, $url])<a href="{{ $url }}" class="rounded-full border border-white/30 bg-white/10 px-4 py-1.5 text-sm font-semibold backdrop-blur transition hover:border-amber hover:bg-amber hover:text-navy">{{ $label }}</a>@endforeach</div>
        @endif
    </section>

    {{-- Business figures --}}
    @if ($erp && count($erp['cards']))
        <h2 class="mb-3 mt-8 text-lg font-extrabold text-navy">Business</h2>
        <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($erp['cards'] as $c)
                <a href="{{ $c['href'] }}" class="kpi anim-rise rounded-panel border border-steel bg-white p-5 pt-6" style="--accent: {{ $c['accent'] }}; --i: {{ $loop->index }}">
                    <dt class="text-sm font-semibold text-ink/70">{{ $c['label'] }}</dt>
                    <dd class="mt-1 text-2xl font-extrabold text-navy" data-countup data-value="{{ $c['value'] }}" data-format="{{ $c['format'] }}" data-symbol="{{ $sym }}">{{ $c['format'] === 'money' ? Money::format($c['value']) : number_format($c['value']) }}</dd>
                    @if ($c['hint'])<p class="mt-1 text-xs text-ink/60">{{ $c['hint'] }}</p>@endif
                </a>
            @endforeach
        </dl>
    @endif

    {{-- Charts --}}
    @if ($charts->isNotEmpty())
        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            @foreach ($charts as $c)
                <figure class="card anim-rise p-5 {{ $c['wide'] ? 'lg:col-span-2' : '' }}" data-chart-box style="--i: {{ $loop->index }}">
                    <figcaption class="font-extrabold text-navy">{{ $c['title'] }}</figcaption>
                    <div class="relative mt-3 h-72"><canvas data-chart-url="{{ route('admin.charts', $c['key']) }}" data-symbol="{{ $sym }}" role="img" aria-label="{{ $c['title'] }}"></canvas></div>
                    <p class="mt-2 text-sm" data-chart-status role="status"></p>
                </figure>
            @endforeach
        </div>
    @endif

    {{-- Business lists --}}
    @if ($erp)
        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            @if ($erp['lowStock']->isNotEmpty())
                <section class="card anim-rise p-5"><h2 class="font-extrabold text-navy">Reorder soon</h2>
                    <ul class="mt-3 divide-y divide-steel/60 text-sm">@foreach ($erp['lowStock'] as $i)<li class="flex items-center justify-between gap-3 py-2"><a class="underline text-industrial" href="{{ route('admin.stock.show', $i) }}">{{ $i->label() }}</a><span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $i->balance <= 0 ? 'bg-red-100 text-red-800' : 'bg-amber/30' }}">{{ $i->balance }} left</span></li>@endforeach</ul></section>
            @endif
            @if ($erp['recentSales']->isNotEmpty())
                <section class="card anim-rise p-5"><h2 class="font-extrabold text-navy">Recent sales</h2>
                    <ul class="mt-3 divide-y divide-steel/60 text-sm">@foreach ($erp['recentSales'] as $s)<li class="flex justify-between gap-3 py-2"><a class="underline text-industrial" href="{{ route('admin.sales.show', $s) }}">{{ $s->number() }}</a><span class="truncate">{{ $s->customerLabel() }}</span><span class="font-semibold">{{ Money::format($s->total) }}</span></li>@endforeach</ul></section>
            @endif
            @if ($erp['recentPurchases']->isNotEmpty())
                <section class="card anim-rise p-5"><h2 class="font-extrabold text-navy">Recent purchases</h2>
                    <ul class="mt-3 divide-y divide-steel/60 text-sm">@foreach ($erp['recentPurchases'] as $p)<li class="flex justify-between gap-3 py-2"><a class="underline text-industrial" href="{{ route('admin.purchases.show', $p) }}">{{ $p->number() }}</a><span class="truncate">{{ $p->supplier?->name }}</span><span class="font-semibold">{{ Money::format($p->total) }}</span></li>@endforeach</ul></section>
            @endif
        </div>
    @endif

    {{-- Website --}}
    @if ($siteCards->isNotEmpty())
        <h2 class="mb-3 mt-10 text-lg font-extrabold text-navy">Website</h2>
        <dl class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5">
            @foreach ($siteCards as $c)
                <a href="{{ $c['href'] }}" class="kpi anim-rise rounded-panel border border-steel bg-white p-4 pt-5" style="--accent: {{ $c['accent'] }}; --i: {{ $loop->index }}">
                    <dt class="text-sm font-semibold text-ink/70">{{ $c['label'] }}</dt>
                    <dd class="mt-1 text-3xl font-extrabold text-navy" data-countup data-value="{{ $c['value'] }}" data-format="number">{{ number_format($c['value']) }}</dd>
                </a>
            @endforeach
        </dl>
    @endif

    @can('analytics.view')
        <section class="mt-8" aria-labelledby="reports">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <h2 id="reports" class="text-lg font-extrabold text-navy">Website activity</h2>
                <form method="GET" class="flex flex-wrap items-end gap-2 text-sm"><div><label class="block" for="from">From</label><input id="from" type="date" name="from" value="{{ $from }}" class="rounded-control border border-steel px-2 py-1.5"></div><div><label class="block" for="to">To</label><input id="to" type="date" name="to" value="{{ $to }}" class="rounded-control border border-steel px-2 py-1.5"></div><x-button>Apply</x-button></form>
            </div>
            <p class="mt-1 text-sm">These charts use this site's own database only. Visitor and source figures from Google Analytics stay in Google Analytics.</p>
            <div class="mt-4 grid gap-6 lg:grid-cols-2">
                @foreach (['product_views' => 'Product views', 'inquiries' => 'Inquiries', 'dealer_applications' => 'Dealer applications', 'downloads' => 'Document downloads', 'article_views' => 'Article views', 'comments' => 'Comments'] as $metric => $label)
                    <figure class="card anim-rise p-5" data-chart-box>
                        <figcaption class="flex items-center justify-between"><span class="font-extrabold text-navy">{{ $label }}</span>@can('reports.export')<a class="text-sm underline text-industrial" href="{{ route('admin.reports.export', ['metric' => $metric, 'from' => $from, 'to' => $to]) }}">CSV</a>@endcan</figcaption>
                        <div class="relative mt-3 h-56"><canvas data-chart-url="{{ route('admin.reports.data', ['metric' => $metric, 'from' => $from, 'to' => $to]) }}" role="img" aria-label="{{ $label }} over time"></canvas></div>
                        <p class="mt-2 text-sm" data-chart-status role="status"></p>
                    </figure>
                @endforeach
            </div>
        </section>
    @endcan

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        @if ($recentInquiries->isNotEmpty())<section class="card p-5"><h2 class="font-extrabold text-navy">Recent inquiries</h2><ul class="mt-3 divide-y divide-steel/60 text-sm">@foreach ($recentInquiries as $i)<li class="py-2"><a class="underline text-industrial" href="{{ route('admin.inquiries.show', $i->id) }}">{{ $i->name }}</a> · {{ $i->type }} · {{ ucfirst($i->status) }}</li>@endforeach</ul></section>@endif
        @if ($popularProducts->isNotEmpty())<section class="card p-5"><h2 class="font-extrabold text-navy">Most viewed products (30 days)</h2><ul class="mt-3 divide-y divide-steel/60 text-sm">@foreach ($popularProducts as $p)<li class="flex justify-between py-2"><span>{{ $p->sku }}</span><span class="font-semibold">{{ number_format($p->views) }}</span></li>@endforeach</ul></section>@endif
        @if ($popularPosts->isNotEmpty())<section class="card p-5"><h2 class="font-extrabold text-navy">Most read articles (30 days)</h2><ul class="mt-3 divide-y divide-steel/60 text-sm">@foreach ($popularPosts as $p)<li class="flex justify-between py-2"><span>{{ $p->slug }}</span><span class="font-semibold">{{ number_format($p->views) }}</span></li>@endforeach</ul></section>@endif
        @if ($topLinks->isNotEmpty())<section class="card p-5"><h2 class="font-extrabold text-navy">Top recommended links (30 days)</h2><ul class="mt-3 divide-y divide-steel/60 text-sm">@foreach ($topLinks as $l)<li class="flex justify-between gap-3 py-2"><span class="truncate">{{ $l->title }} <span class="text-xs text-ink/60">({{ \App\Models\PartnerLink::STORES[$l->store] ?? $l->store }})</span></span><span class="font-semibold">{{ number_format($l->recent_clicks) }} clicks</span></li>@endforeach</ul></section>@endif
        @if ($recentActivity->isNotEmpty())<section class="card p-5"><h2 class="font-extrabold text-navy">Recent activity</h2><ul class="mt-3 divide-y divide-steel/60 text-sm">@foreach ($recentActivity as $a)<li class="py-2">{{ $a->causer?->name ?? 'System' }} · {{ $a->description }} · {{ $a->created_at->diffForHumans() }}</li>@endforeach</ul></section>@endif
    </div>

    @if (! $erp && $siteCards->isEmpty() && $charts->isEmpty())
        <p class="mt-6">Your account has no dashboard data to show. Ask an administrator for access.</p>
    @endif
</x-layouts.admin-shell>
