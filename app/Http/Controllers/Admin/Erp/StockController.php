<?php

namespace App\Http\Controllers\Admin\Erp;

use App\Http\Controllers\Controller;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Services\Erp\ErpService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $bal = '(SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE stock_movements.stock_item_id = stock_items.id)';

        $items = StockItem::with('product')->withBalance()
            ->when($request->string('q')->trim()->value(), fn ($q, $t) => $q->whereHas('product', fn ($p) => $p->where('sku', 'like', "%{$t}%")->orWhere('name', 'like', "%{$t}%")))
            ->when($status === 'out', fn ($q) => $q->whereRaw("{$bal} <= 0"))
            ->when($status === 'low', fn ($q) => $q->where('reorder_level', '>', 0)->whereRaw("{$bal} > 0 AND {$bal} <= reorder_level"))
            ->when($status === 'ok', fn ($q) => $q->whereRaw("{$bal} > 0 AND (reorder_level = 0 OR {$bal} > reorder_level)"))
            ->orderBy('product_id')->orderBy('pack')->paginate(30)->withQueryString();

        $all = StockItem::withBalance()->get();
        $summary = [
            'items' => $all->count(),
            'value' => $all->sum(fn ($i) => max(0, (int) $i->balance) * (float) $i->cost_price),
            'low' => $all->filter(fn ($i) => $i->reorder_level > 0 && $i->balance > 0 && $i->balance <= $i->reorder_level)->count(),
            'out' => $all->filter(fn ($i) => $i->balance <= 0)->count(),
        ];

        return view('admin.erp.stock.index', compact('items', 'summary'));
    }

    public function show(StockItem $stockItem)
    {
        return view('admin.erp.stock.show', [
            'item' => StockItem::with('product')->withBalance()->findOrFail($stockItem->id),
            'movements' => StockMovement::where('stock_item_id', $stockItem->id)->with('user')->latest('moved_on')->latest('id')->paginate(30),
        ]);
    }

    public function sync(Request $request, ErpService $erp)
    {
        $added = $erp->syncStockItems();
        activity('admin')->causedBy($request->user())->withProperties(['added' => $added])->log('stock.synced');

        return back()->with('status', $added ? "{$added} stock item(s) added from your products and pack sizes." : 'Everything is already in the stock list.');
    }

    public function update(Request $request, StockItem $stockItem)
    {
        $d = $request->validate(['sale_price' => ['required', 'numeric', 'min:0', 'max:99999999'], 'reorder_level' => ['required', 'integer', 'min:0', 'max:1000000']]);
        $stockItem->update($d);

        return back()->with('status', 'Saved.');
    }

    public function adjust(Request $request, StockItem $stockItem, ErpService $erp)
    {
        $d = $request->validate(['delta' => ['required', 'integer', 'between:-1000000,1000000'], 'reason' => ['required', 'string', 'max:200'], 'moved_on' => ['nullable', 'date']]);
        $erp->adjustStock($stockItem, (int) $d['delta'], $d['reason'], ($d['moved_on'] ?? null) ?: today()->toDateString(), $request->user());
        activity('admin')->causedBy($request->user())->performedOn($stockItem)->withProperties($d)->log('stock.adjusted');

        return back()->with('status', 'Stock adjusted.');
    }
}
