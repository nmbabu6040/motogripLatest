<?php

namespace App\Http\Controllers\Admin\Erp;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\StockItem;
use App\Services\Erp\ErpService;
use App\Support\Settings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $sales = Sale::with('customer')->withSum('payments', 'amount')->withSum('returns', 'refund_total')
            ->when($request->string('q')->trim()->value(), function ($q, $t) {
                $id = (int) preg_replace('/\D/', '', $t);
                $q->where(fn ($w) => $w->where('customer_name', 'like', "%{$t}%")->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$t}%"))->when($id > 0, fn ($x) => $x->orWhere('id', $id)));
            })
            ->when($request->query('status') === 'void', fn ($q) => $q->where('status', 'void'))
            ->when($request->query('status') === 'due', fn ($q) => $q->where('status', 'confirmed')->whereRaw('total - COALESCE((SELECT SUM(refund_total) FROM sale_returns WHERE sale_id = sales.id),0) - COALESCE((SELECT SUM(amount) FROM payments WHERE payable_type = ? AND payable_id = sales.id),0) > 0.004', [Sale::class]))
            ->when($request->date('from'), fn ($q, $d) => $q->whereDate('sold_on', '>=', $d))
            ->when($request->date('to'), fn ($q, $d) => $q->whereDate('sold_on', '<=', $d))
            ->latest('sold_on')->latest('id')->paginate(25)->withQueryString();

        return view('admin.erp.sales.index', compact('sales'));
    }

    public function create()
    {
        return view('admin.erp.sales.create', [
            'customers' => Party::kind('customer')->orderBy('name')->get(), 'stockItems' => StockItem::picklist(),
            'defaultTax' => (float) Settings::get('erp', 'tax_percent', 0),
        ]);
    }

    public function store(Request $request, ErpService $erp)
    {
        $d = $request->validate([
            'customer_id' => ['nullable', Rule::exists('parties', 'id')->where('kind', 'customer')->whereNull('deleted_at')],
            'customer_name' => ['nullable', 'string', 'max:160'],
            'sold_on' => ['required', 'date'], 'discount' => ['nullable', 'numeric', 'min:0'], 'tax_percent' => ['nullable', 'numeric', 'between:0,100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:60'],
            'items.*.stock_item_id' => ['required', Rule::exists('stock_items', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'paid' => ['nullable', 'numeric', 'min:0'], 'method' => ['nullable', Rule::in(array_keys(Payment::METHODS))],
        ]);
        $d['sold_on'] = Carbon::parse($d['sold_on'])->toDateString();

        $sale = $erp->createSale($d, $request->user());
        activity('admin')->causedBy($request->user())->performedOn($sale)->withProperties(['total' => $sale->total])->log('sale.created');

        return redirect()->route('admin.sales.show', $sale)->with('status', 'Invoice '.$sale->number().' created and stock reduced.');
    }

    public function show(Sale $sale)
    {
        return view('admin.erp.sales.show', [
            'sale' => $sale->load(['customer', 'items.stockItem.product', 'payments', 'returns', 'challans', 'creator', 'onlinePayments']),
            'onlineEnabled' => app(\App\Services\Payments\SslCommerz::class)->enabled(),
        ]);
    }

    public function pay(Request $request, Sale $sale, ErpService $erp)
    {
        $d = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'method' => ['required', Rule::in(array_keys(Payment::METHODS))], 'paid_on' => ['required', 'date'], 'reference' => ['nullable', 'string', 'max:190']]);
        $erp->addPayment($sale, (float) $d['amount'], $d['method'], Carbon::parse($d['paid_on'])->toDateString(), $d['reference'] ?? null, null, $request->user());
        activity('admin')->causedBy($request->user())->performedOn($sale)->withProperties($d)->log('sale.paid');

        return back()->with('status', 'Payment recorded.');
    }

    public function void(Request $request, Sale $sale, ErpService $erp)
    {
        $erp->voidSale($sale, $request->user());
        activity('admin')->causedBy($request->user())->performedOn($sale)->log('sale.voided');

        return back()->with('status', 'Invoice voided and stock put back.');
    }

    public function edit(Sale $sale)
    {
        abort_if($sale->isVoid(), 404);
        $sale->load('items');
        $locked = $sale->returns()->exists() || $sale->challans()->exists();
        $old = $sale->items->groupBy('stock_item_id')->map(fn ($g) => (int) $g->sum('quantity'));

        return view('admin.erp.sales.edit', [
            'sale' => $sale, 'locked' => $locked, 'customers' => Party::kind('customer')->orderBy('name')->get(),
            // the stock figure shown beside each item includes what this invoice is already holding
            'stockItems' => collect(StockItem::picklist())->map(fn ($i) => array_merge($i, ['stock' => $i['stock'] + (int) ($old[$i['id']] ?? 0)]))->all(),
            'rows' => $sale->items->map(fn ($l) => ['stock_item_id' => $l->stock_item_id, 'quantity' => $l->quantity, 'price' => (float) $l->unit_price])->values()->all(),
        ]);
    }

    public function update(Request $request, Sale $sale, ErpService $erp)
    {
        $locked = $sale->returns()->exists() || $sale->challans()->exists();
        $rules = [
            'customer_id' => ['nullable', Rule::exists('parties', 'id')->where('kind', 'customer')->whereNull('deleted_at')],
            'customer_name' => ['nullable', 'string', 'max:160'], 'sold_on' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:1000'],
        ];
        if (! $locked) {
            $rules += [
                'discount' => ['nullable', 'numeric', 'min:0'], 'tax_percent' => ['nullable', 'numeric', 'between:0,100'],
                'items' => ['required', 'array', 'min:1', 'max:60'], 'items.*.stock_item_id' => ['required', Rule::exists('stock_items', 'id')],
                'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'], 'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            ];
        }
        $d = $request->validate($rules);
        $d['sold_on'] = Carbon::parse($d['sold_on'])->toDateString();

        $erp->updateSale($sale, $d, $request->user());
        activity('admin')->causedBy($request->user())->performedOn($sale)->log('sale.updated');

        return redirect()->route('admin.sales.show', $sale)->with('status', 'Invoice '.$sale->number().' updated.');
    }

    /** Creates (once) the secret link the customer uses to pay this invoice online. */
    public function payLink(Request $request, Sale $sale)
    {
        abort_unless(app(\App\Services\Payments\SslCommerz::class)->enabled(), 404);
        abort_if($sale->isVoid(), 404);

        if (! $sale->pay_token) {
            $sale->update(['pay_token' => \Illuminate\Support\Str::random(40)]);
            activity('admin')->causedBy($request->user())->performedOn($sale)->log('sale.pay_link_created');
        }

        return back()->with('status', 'Payment link is ready. Copy it below and send it to the customer.');
    }
}
