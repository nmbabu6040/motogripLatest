<?php

namespace App\Http\Controllers\Admin\Erp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** JSON for the colourful dashboard charts. Every figure is read from this site's own tables. */
class ChartController extends Controller
{
    private const PERMISSION = [
        'sales_purchases' => 'sales.manage', 'sales_daily' => 'sales.manage', 'top_products' => 'sales.manage',
        'stock_status' => 'inventory.view', 'partner_clicks' => 'partners.manage',
    ];

    public function __invoke(Request $request, string $metric)
    {
        abort_unless(isset(self::PERMISSION[$metric]), 404);
        abort_unless($request->user()->can(self::PERMISSION[$metric]), 403);

        return response()->json(match ($metric) {
            'sales_purchases' => $this->salesPurchases(),
            'sales_daily' => $this->salesDaily(),
            'top_products' => $this->topProducts(),
            'stock_status' => $this->stockStatus(),
            'partner_clicks' => $this->partnerClicks(),
        }, 200, ['Cache-Control' => 'no-store']);
    }

    /** Last 12 months, sales against purchases. */
    private function salesPurchases(): array
    {
        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $from = $months->first()->toDateString();

        $sum = fn ($rows, string $col, string $date) => $rows->groupBy(fn ($r) => Carbon::parse($r->$date)->format('Y-m'))->map(fn ($g) => round($g->sum($col), 2));
        $sales = $sum(Sale::where('status', 'confirmed')->whereDate('sold_on', '>=', $from)->get(['sold_on', 'total']), 'total', 'sold_on');
        $purchases = $sum(Purchase::where('status', 'received')->whereDate('purchased_on', '>=', $from)->get(['purchased_on', 'total']), 'total', 'purchased_on');

        return [
            'type' => 'bar', 'money' => true, 'labels' => $months->map(fn ($m) => $m->format('M Y'))->all(),
            'datasets' => [
                ['label' => 'Sales', 'data' => $months->map(fn ($m) => (float) ($sales[$m->format('Y-m')] ?? 0))->all()],
                ['label' => 'Purchases', 'data' => $months->map(fn ($m) => (float) ($purchases[$m->format('Y-m')] ?? 0))->all()],
            ],
        ];
    }

    /** Last 30 days of sales. */
    private function salesDaily(): array
    {
        $days = collect(range(29, 0))->map(fn ($i) => today()->subDays($i));
        $rows = Sale::where('status', 'confirmed')->whereDate('sold_on', '>=', $days->first()->toDateString())->get(['sold_on', 'total'])
            ->groupBy(fn ($r) => Carbon::parse($r->sold_on)->toDateString())->map(fn ($g) => round($g->sum('total'), 2));

        return [
            'type' => 'line', 'money' => true, 'labels' => $days->map(fn ($d) => $d->format('d M'))->all(),
            'datasets' => [['label' => 'Daily sales', 'data' => $days->map(fn ($d) => (float) ($rows[$d->toDateString()] ?? 0))->all()]],
        ];
    }

    /** Best sellers by amount over the last 90 days. */
    private function topProducts(): array
    {
        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')->join('stock_items', 'stock_items.id', '=', 'sale_items.stock_item_id')
            ->where('sales.status', 'confirmed')->whereDate('sales.sold_on', '>=', today()->subDays(90)->toDateString())
            ->groupBy('stock_items.product_id')->selectRaw('stock_items.product_id as product_id, SUM(sale_items.line_total) as amount')
            ->orderByDesc('amount')->limit(6)->get();

        $names = Product::withTrashed()->whereIn('id', $rows->pluck('product_id'))->get()->keyBy('id');

        return [
            'type' => 'doughnut', 'money' => true,
            'labels' => $rows->map(fn ($r) => $names->get($r->product_id)?->name ?? 'Product')->all(),
            'datasets' => [['label' => 'Sales (last 90 days)', 'data' => $rows->map(fn ($r) => round((float) $r->amount, 2))->all()]],
        ];
    }

    private function stockStatus(): array
    {
        $items = StockItem::withBalance()->get();
        $out = $items->filter(fn ($i) => $i->balance <= 0)->count();
        $low = $items->filter(fn ($i) => $i->balance > 0 && $i->reorder_level > 0 && $i->balance <= $i->reorder_level)->count();

        return [
            'type' => 'doughnut', 'money' => false, 'labels' => ['In stock', 'Running low', 'Out of stock'],
            'datasets' => [['label' => 'Items', 'data' => [$items->count() - $out - $low, $low, $out]]],
            'colors' => ['#2BB673', '#F5B82E', '#E4572E'],
        ];
    }

    private function partnerClicks(): array
    {
        $days = collect(range(29, 0))->map(fn ($i) => today()->subDays($i));
        $rows = DB::table('partner_link_clicks')->whereDate('clicked_on', '>=', $days->first()->toDateString())->selectRaw('clicked_on, SUM(clicks) as c')->groupBy('clicked_on')->get()
            ->mapWithKeys(fn ($r) => [Carbon::parse($r->clicked_on)->toDateString() => (int) $r->c]);

        return [
            'type' => 'line', 'money' => false, 'labels' => $days->map(fn ($d) => $d->format('d M'))->all(),
            'datasets' => [['label' => 'Clicks on recommended links', 'data' => $days->map(fn ($d) => (int) ($rows[$d->toDateString()] ?? 0))->all()]],
        ];
    }
}
