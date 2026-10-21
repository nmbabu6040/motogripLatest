<x-layouts.admin-shell title="Dashboard">
    @if ($cards->isEmpty())
        <p>Your account has no dashboard data to show. Ask an administrator for access.</p>
    @else
        <dl class="grid gap-4 grid-cols-2 lg:grid-cols-5">
            @foreach ($cards as $card)
                <div class="rounded-panel bg-white border border-steel p-4">
                    <dt class="text-sm">{{ $card['label'] }}</dt>
                    <dd class="text-3xl font-extrabold text-navy">{{ number_format($card['value']) }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    @can('analytics.view')
        <section class="mt-8" aria-labelledby="reports">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <h2 id="reports" class="text-xl font-extrabold text-navy">Activity on this website</h2>
                <form method="GET" class="flex flex-wrap items-end gap-2 text-sm">
                    <div>
                        <label class="block" for="from">From</label>
                        <input id="from" type="date" name="from" value="{{ $from }}"
                            class="rounded-control border border-steel px-2 py-1.5">
                    </div>
                    <div>
                        <label class="block" for="to">To</label>
                        <input id="to" type="date" name="to" value="{{ $to }}"
                            class="rounded-control border border-steel px-2 py-1.5">
                    </div>
                    <x-button>Apply</x-button>
                </form>
            </div>
            <p class="mt-1 text-sm">These charts use this site's own database only. Visitor, source and conversion figures
                from Google Analytics appear in Google Analytics, not here.</p>
            <div class="mt-4 grid gap-6 lg:grid-cols-2">
                @foreach (['product_views' => 'Product views', 'inquiries' => 'Inquiries', 'dealer_applications' => 'Dealer applications', 'downloads' => 'Document downloads', 'article_views' => 'Article views', 'comments' => 'Comments'] as $metric => $label)
                    <figure class="rounded-panel border border-steel bg-white p-4" data-chart-box>
                        <figcaption class="flex items-center justify-between"><span
                                class="font-semibold">{{ $label }}</span>
                            @can('reports.export')
                                <a class="text-sm underline text-industrial"
                                    href="{{ route('admin.reports.export', ['metric' => $metric, 'from' => $from, 'to' => $to]) }}">CSV</a>
                            @endcan
                        </figcaption>
                        <div class="relative mt-2 h-56"><canvas
                                data-chart-url="{{ route('admin.reports.data', ['metric' => $metric, 'from' => $from, 'to' => $to]) }}"
                                role="img" aria-label="{{ $label }} over time"></canvas></div>
                        <p class="mt-2 text-sm" data-chart-status role="status">Loading…</p>
                    </figure>
                @endforeach
            </div>
        </section>
    @endcan

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        @if ($recentInquiries->isNotEmpty())
            <section class="rounded-panel border border-steel bg-white p-5">
                <h2 class="font-extrabold">Recent inquiries</h2>
                <ul class="mt-2 divide-y divide-steel text-sm">
                    @foreach ($recentInquiries as $i)
                        <li class="py-2"><a class="underline text-industrial"
                                href="{{ route('admin.inquiries.show', $i->id) }}">{{ $i->name }}</a> ·
                            {{ $i->type }} · {{ ucfirst($i->status) }}</li>
                    @endforeach
                </ul>
            </section>
        @endif
        @if ($popularProducts->isNotEmpty())
            <section class="rounded-panel border border-steel bg-white p-5">
                <h2 class="font-extrabold">Most viewed products (30 days)</h2>
                <ul class="mt-2 divide-y divide-steel text-sm">
                    @foreach ($popularProducts as $p)
                        <li class="flex justify-between py-2">
                            <span>{{ $p->sku }}</span><span>{{ number_format($p->views) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
        @if ($popularPosts->isNotEmpty())
            <section class="rounded-panel border border-steel bg-white p-5">
                <h2 class="font-extrabold">Most read articles (30 days)</h2>
                <ul class="mt-2 divide-y divide-steel text-sm">
                    @foreach ($popularPosts as $p)
                        <li class="flex justify-between py-2">
                            <span>{{ $p->slug }}</span><span>{{ number_format($p->views) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
        @if ($recentActivity->isNotEmpty())
            <section class="rounded-panel border border-steel bg-white p-5">
                <h2 class="font-extrabold">Recent activity</h2>
                <ul class="mt-2 divide-y divide-steel text-sm">
                    @foreach ($recentActivity as $a)
                        <li class="py-2">{{ $a->causer?->name ?? 'System' }} · {{ $a->description }} ·
                            {{ $a->created_at->diffForHumans() }}</li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.admin-shell>
