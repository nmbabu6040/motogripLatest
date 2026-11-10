<?php

namespace App\Http\Controllers\Admin\Erp;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\Erp\ErpService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Sale returns (customer sends goods back) and purchase returns (we send goods back to the supplier). */
class ReturnController extends Controller
{
    public function saleIndex()
    {
        return view('admin.erp.returns.index', ['kind' => 'sale', 'returns' => SaleReturn::with('sale.customer')->latest('returned_on')->latest('id')->paginate(25)]);
    }

    public function purchaseIndex()
    {
        return view('admin.erp.returns.index', ['kind' => 'purchase', 'returns' => PurchaseReturn::with('purchase.supplier')->latest('returned_on')->latest('id')->paginate(25)]);
    }

    public function saleCreate(Sale $sale)
    {
        abort_if($sale->isVoid(), 404);
        $sale->load('items.stockItem.product');
        $done = SaleReturnItem::whereIn('sale_item_id', $sale->items->pluck('id'))->selectRaw('sale_item_id, SUM(quantity) as q')->groupBy('sale_item_id')->pluck('q', 'sale_item_id');

        return view('admin.erp.returns.form', [
            'kind' => 'sale', 'doc' => $sale, 'action' => route('admin.sales.returns.store', $sale),
            'lines' => $sale->items->map(fn ($i) => ['id' => $i->id, 'label' => $i->stockItem->label(), 'sold' => $i->quantity, 'left' => $i->quantity - (int) ($done[$i->id] ?? 0), 'price' => (float) $i->unit_price]),
        ]);
    }

    public function saleStore(Request $request, Sale $sale, ErpService $erp)
    {
        $d = $this->validated($request);
        $return = $erp->createSaleReturn($sale, $d['qty'], $d['reason'] ?? null, Carbon::parse($d['returned_on'])->toDateString(), $request->user());
        activity('admin')->causedBy($request->user())->performedOn($return)->withProperties(['refund' => $return->refund_total])->log('sale_return.created');

        return redirect()->route('admin.sale-returns.show', $return)->with('status', 'Return '.$return->number().' saved and stock put back.');
    }

    public function saleShow(SaleReturn $saleReturn)
    {
        return view('admin.erp.returns.show', ['kind' => 'sale', 'return' => $saleReturn->load(['sale.customer', 'items.stockItem.product'])]);
    }

    public function purchaseCreate(Purchase $purchase)
    {
        abort_if($purchase->isVoid(), 404);
        $purchase->load('items.stockItem.product');
        $done = PurchaseReturnItem::whereIn('purchase_item_id', $purchase->items->pluck('id'))->selectRaw('purchase_item_id, SUM(quantity) as q')->groupBy('purchase_item_id')->pluck('q', 'purchase_item_id');

        return view('admin.erp.returns.form', [
            'kind' => 'purchase', 'doc' => $purchase, 'action' => route('admin.purchases.returns.store', $purchase),
            'lines' => $purchase->items->map(fn ($i) => ['id' => $i->id, 'label' => $i->stockItem->label(), 'sold' => $i->quantity, 'left' => $i->quantity - (int) ($done[$i->id] ?? 0), 'price' => (float) $i->unit_cost]),
        ]);
    }

    public function purchaseStore(Request $request, Purchase $purchase, ErpService $erp)
    {
        $d = $this->validated($request);
        $return = $erp->createPurchaseReturn($purchase, $d['qty'], $d['reason'] ?? null, Carbon::parse($d['returned_on'])->toDateString(), $request->user());
        activity('admin')->causedBy($request->user())->performedOn($return)->withProperties(['credit' => $return->credit_total])->log('purchase_return.created');

        return redirect()->route('admin.purchase-returns.show', $return)->with('status', 'Return '.$return->number().' saved and stock reduced.');
    }

    public function purchaseShow(PurchaseReturn $purchaseReturn)
    {
        return view('admin.erp.returns.show', ['kind' => 'purchase', 'return' => $purchaseReturn->load(['purchase.supplier', 'items.stockItem.product'])]);
    }

    private function validated(Request $request): array
    {
        return $request->validate(['returned_on' => ['required', 'date'], 'reason' => ['nullable', 'string', 'max:255'], 'qty' => ['required', 'array'], 'qty.*' => ['nullable', 'integer', 'min:0', 'max:1000000']]);
    }
}
