<?php

namespace App\Http\Controllers\Admin\Erp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Services\Erp\ErpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

    public function create()
    {
        return view('admin.erp.stock.form', ['item' => new StockItem(['pack' => '', 'sale_price' => 0, 'cost_price' => 0, 'reorder_level' => 0]), 'products' => Product::orderBy('sku')->get(['id', 'sku', 'name'])]);
    }

    public function store(Request $request)
    {
        $d = $request->validate(['product_id' => ['required', Rule::exists('products', 'id')]] + $this->detailRules($request));
        $d['pack'] = trim((string) ($d['pack'] ?? ''));
        if (StockItem::where('product_id', $d['product_id'])->where('pack', $d['pack'])->exists()) {
            return back()->withInput()->withErrors(['pack' => 'This product already has a stock item with that pack size.']);
        }
        $item = StockItem::create($d);
        activity('admin')->causedBy($request->user())->performedOn($item)->log('stock_item.created');

        return redirect()->route('admin.stock.show', $item)->with('status', 'Stock item added. Use "Adjust stock" to enter the opening quantity.');
    }

    public function edit(StockItem $stockItem)
    {
        return view('admin.erp.stock.form', ['item' => $stockItem->load('product'), 'products' => collect(), 'used' => $this->isUsed($stockItem)]);
    }

    public function save(Request $request, StockItem $stockItem)
    {
        $d = $request->validate($this->detailRules($request));
        $d['pack'] = trim((string) ($d['pack'] ?? ''));
        if ($d['pack'] !== $stockItem->pack && StockItem::where('product_id', $stockItem->product_id)->where('pack', $d['pack'])->where('id', '!=', $stockItem->id)->exists()) {
            return back()->withInput()->withErrors(['pack' => 'This product already has a stock item with that pack size.']);
        }
        $stockItem->update($d);
        activity('admin')->causedBy($request->user())->performedOn($stockItem)->log('stock_item.updated');

        return redirect()->route('admin.stock.show', $stockItem)->with('status', 'Stock item saved.');
    }

    public function destroy(Request $request, StockItem $stockItem)
    {
        if ($this->isUsed($stockItem)) {
            return back()->withErrors(['delete' => 'This stock item already has stock movements or appears on documents, so it cannot be deleted. Set its stock to zero and stop using it instead.']);
        }
        activity('admin')->causedBy($request->user())->withProperties(['label' => $stockItem->label()])->log('stock_item.deleted');
        $stockItem->delete();

        return redirect()->route('admin.stock.index')->with('status', 'Stock item deleted.');
    }

    private function detailRules(Request $request): array
    {
        return [
            'pack' => ['nullable', 'string', 'max:60'], 'sale_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:99999999'], 'reorder_level' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    private function isUsed(StockItem $item): bool
    {
        return $item->movements()->exists()
            || DB::table('sale_items')->where('stock_item_id', $item->id)->exists()
            || DB::table('purchase_items')->where('stock_item_id', $item->id)->exists();
    }
}
