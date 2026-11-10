<?php

namespace App\Http\Controllers\Admin\Erp;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\StockItem;
use App\Services\Erp\ErpService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $purchases = Purchase::with('supplier')->withSum('payments', 'amount')->withSum('returns', 'credit_total')
            ->when($request->string('q')->trim()->value(), function ($q, $t) {
                $id = (int) preg_replace('/\D/', '', $t);
                $q->where(fn ($w) => $w->whereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$t}%"))->when($id > 0, fn ($x) => $x->orWhere('id', $id)));
            })
            ->when($request->query('status') === 'void', fn ($q) => $q->where('status', 'void'))
            ->when($request->query('status') === 'due', fn ($q) => $q->where('status', 'received')->whereRaw('total - COALESCE((SELECT SUM(credit_total) FROM purchase_returns WHERE purchase_id = purchases.id),0) - COALESCE((SELECT SUM(amount) FROM payments WHERE payable_type = ? AND payable_id = purchases.id),0) > 0.004', [Purchase::class]))
            ->when($request->date('from'), fn ($q, $d) => $q->whereDate('purchased_on', '>=', $d))
            ->when($request->date('to'), fn ($q, $d) => $q->whereDate('purchased_on', '<=', $d))
            ->latest('purchased_on')->latest('id')->paginate(25)->withQueryString();

        return view('admin.erp.purchases.index', compact('purchases'));
    }

    public function create()
    {
        return view('admin.erp.purchases.create', ['suppliers' => Party::kind('supplier')->orderBy('name')->get(), 'stockItems' => StockItem::picklist()]);
    }

    public function store(Request $request, ErpService $erp)
    {
        $d = $request->validate([
            'supplier_id' => ['required', Rule::exists('parties', 'id')->where('kind', 'supplier')->whereNull('deleted_at')],
            'purchased_on' => ['required', 'date'], 'discount' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:60'],
            'items.*.stock_item_id' => ['required', Rule::exists('stock_items', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'paid' => ['nullable', 'numeric', 'min:0'], 'method' => ['nullable', Rule::in(array_keys(Payment::METHODS))],
        ]);
        $d['purchased_on'] = Carbon::parse($d['purchased_on'])->toDateString();

        $purchase = $erp->createPurchase($d, $request->user());
        activity('admin')->causedBy($request->user())->performedOn($purchase)->withProperties(['total' => $purchase->total])->log('purchase.created');

        return redirect()->route('admin.purchases.show', $purchase)->with('status', 'Purchase '.$purchase->number().' saved and stock increased.');
    }

    public function show(Purchase $purchase)
    {
        return view('admin.erp.purchases.show', ['purchase' => $purchase->load(['supplier', 'items.stockItem.product', 'payments', 'returns', 'creator'])]);
    }

    public function pay(Request $request, Purchase $purchase, ErpService $erp)
    {
        $d = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'method' => ['required', Rule::in(array_keys(Payment::METHODS))], 'paid_on' => ['required', 'date'], 'reference' => ['nullable', 'string', 'max:190']]);
        $erp->addPayment($purchase, (float) $d['amount'], $d['method'], Carbon::parse($d['paid_on'])->toDateString(), $d['reference'] ?? null, null, $request->user());
        activity('admin')->causedBy($request->user())->performedOn($purchase)->withProperties($d)->log('purchase.paid');

        return back()->with('status', 'Payment recorded.');
    }

    public function void(Request $request, Purchase $purchase, ErpService $erp)
    {
        $erp->voidPurchase($purchase, $request->user());
        activity('admin')->causedBy($request->user())->performedOn($purchase)->log('purchase.voided');

        return back()->with('status', 'Purchase voided and stock reversed.');
    }
}
