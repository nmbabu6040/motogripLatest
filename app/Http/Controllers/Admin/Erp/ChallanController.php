<?php

namespace App\Http\Controllers\Admin\Erp;

use App\Http\Controllers\Controller;
use App\Models\Challan;
use App\Models\ChallanItem;
use App\Models\Sale;
use App\Models\SaleReturnItem;
use App\Services\Erp\ErpService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChallanController extends Controller
{
    public function index()
    {
        return view('admin.erp.challans.index', [
            'challans' => Challan::with('sale.customer')->latest('delivered_on')->latest('id')->paginate(25),
            'choices' => Sale::with('customer')->where('status', 'confirmed')->latest('id')->limit(200)->get(),
        ]);
    }

    public function create(Sale $sale)
    {
        abort_if($sale->isVoid(), 404);
        $sale->load('items.stockItem.product', 'customer');
        $ids = $sale->items->pluck('id');
        $returned = SaleReturnItem::whereIn('sale_item_id', $ids)->selectRaw('sale_item_id, SUM(quantity) as q')->groupBy('sale_item_id')->pluck('q', 'sale_item_id');
        $delivered = ChallanItem::whereIn('sale_item_id', $ids)->selectRaw('sale_item_id, SUM(quantity) as q')->groupBy('sale_item_id')->pluck('q', 'sale_item_id');

        return view('admin.erp.challans.create', [
            'sale' => $sale,
            'lines' => $sale->items->map(fn ($i) => ['id' => $i->id, 'label' => $i->stockItem->label(), 'sold' => $i->quantity, 'left' => $i->quantity - (int) ($returned[$i->id] ?? 0) - (int) ($delivered[$i->id] ?? 0)]),
        ]);
    }

    public function store(Request $request, Sale $sale, ErpService $erp)
    {
        $d = $request->validate([
            'delivered_on' => ['required', 'date'], 'receiver_name' => ['nullable', 'string', 'max:160'], 'receiver_phone' => ['nullable', 'string', 'max:32'],
            'delivery_address' => ['nullable', 'string', 'max:1000'], 'vehicle_no' => ['nullable', 'string', 'max:40'], 'driver_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'], 'qty' => ['required', 'array'], 'qty.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);
        $d['delivered_on'] = Carbon::parse($d['delivered_on'])->toDateString();

        $challan = $erp->createChallan($sale, $d['qty'], $d, $request->user());
        activity('admin')->causedBy($request->user())->performedOn($challan)->log('challan.created');

        return redirect()->route('admin.challans.show', $challan)->with('status', 'Challan '.$challan->number().' created.');
    }

    public function show(Challan $challan)
    {
        return view('admin.erp.challans.show', ['challan' => $challan->load(['sale.customer', 'items.saleItem.stockItem.product'])]);
    }

    public function edit(Challan $challan)
    {
        return view('admin.erp.challans.edit', ['challan' => $challan->load('sale')]);
    }

    public function update(Request $request, Challan $challan)
    {
        $d = $request->validate([
            'delivered_on' => ['required', 'date'], 'receiver_name' => ['nullable', 'string', 'max:160'], 'receiver_phone' => ['nullable', 'string', 'max:32'],
            'delivery_address' => ['nullable', 'string', 'max:1000'], 'vehicle_no' => ['nullable', 'string', 'max:40'], 'driver_name' => ['nullable', 'string', 'max:120'], 'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $d['delivered_on'] = Carbon::parse($d['delivered_on'])->toDateString();
        $challan->update($d);
        activity('admin')->causedBy($request->user())->performedOn($challan)->log('challan.updated');

        return redirect()->route('admin.challans.show', $challan)->with('status', 'Challan saved.');
    }

    public function destroy(Request $request, Challan $challan)
    {
        activity('admin')->causedBy($request->user())->withProperties(['number' => $challan->number()])->log('challan.deleted');
        $sale = $challan->sale;
        $challan->delete();   // a challan does not move stock, so nothing else changes

        return redirect()->route('admin.sales.show', $sale)->with('status', 'Challan deleted. Those items can be put on a new challan.');
    }

    /** "Make a new challan" on the challan list: pick the invoice, then go to its challan form. */
    public function start(Request $request)
    {
        $d = $request->validate(['sale' => ['required', 'integer', Rule::exists('sales', 'id')->where('status', 'confirmed')]], ['sale.*' => 'Choose an invoice from the list.']);

        return redirect()->route('admin.sales.challans.create', $d['sale']);
    }
}
