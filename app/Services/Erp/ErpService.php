<?php

namespace App\Services\Erp;

use App\Models\Challan;
use App\Models\ChallanItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * All stock and money rules live here. Stock is never edited directly: every change is a row in stock_movements,
 * and every public method runs in one database transaction so a half-finished document can never exist.
 */
class ErpService
{
    public function balance(int $stockItemId): int
    {
        return (int) StockMovement::where('stock_item_id', $stockItemId)->sum('quantity');
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    private function move(StockItem $item, int $qty, string $type, ?Model $ref, string $date, ?string $note, ?int $userId): void
    {
        StockMovement::create([
            'stock_item_id' => $item->id, 'quantity' => $qty, 'type' => $type,
            'reference_type' => $ref ? $ref::class : null, 'reference_id' => $ref?->getKey(),
            'moved_on' => $date, 'note' => $note, 'user_id' => $userId,
        ]);
    }

    private function lock(array $ids)
    {
        return StockItem::with('product')->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
    }

    /** @return array{subtotal: float, discount: float, tax: float, total: float} */
    public function totals(float $subtotal, float $discount, float $taxPercent): array
    {
        $subtotal = round($subtotal, 2);
        $discount = round(min(max($discount, 0), $subtotal), 2);
        $tax = round(($subtotal - $discount) * min(max($taxPercent, 0), 100) / 100, 2);

        return ['subtotal' => $subtotal, 'discount' => $discount, 'tax' => $tax, 'total' => round($subtotal - $discount + $tax, 2)];
    }

    // ------------------------------------------------------------------ stock items

    /** Creates a stock item for every product and active pack size that does not have one yet. Returns how many were added. */
    public function syncStockItems(): int
    {
        $added = 0;
        foreach (Product::with('packaging')->get() as $product) {
            $packs = $product->packaging->where('is_active', true)->pluck('label')->map(fn ($l) => mb_substr(trim($l), 0, 60))->filter()->unique()->values();
            foreach ($packs->isEmpty() ? [''] : $packs->all() as $pack) {
                $item = StockItem::firstOrCreate(['product_id' => $product->id, 'pack' => $pack], ['sale_price' => 0, 'cost_price' => 0, 'reorder_level' => 0]);
                $added += $item->wasRecentlyCreated ? 1 : 0;
            }
        }

        return $added;
    }

    public function adjustStock(StockItem $item, int $delta, string $reason, string $date, User $user): void
    {
        if ($delta === 0) {
            $this->fail('delta', 'Enter a quantity other than zero.');
        }
        DB::transaction(function () use ($item, $delta, $reason, $date, $user) {
            $locked = $this->lock([$item->id])->first();
            if ($this->balance($locked->id) + $delta < 0) {
                $this->fail('delta', 'This would make the stock negative.');
            }
            $this->move($locked, $delta, 'adjustment', null, $date, $reason, $user->id);
        });
    }

    // ------------------------------------------------------------------ purchases

    /** @param array{supplier_id:int, purchased_on:string, discount?:float, notes?:?string, items:array<int,array{stock_item_id:int,quantity:int,unit_cost:float}>, paid?:float, method?:string} $d */
    public function createPurchase(array $d, User $user): Purchase
    {
        return DB::transaction(function () use ($d, $user) {
            $lines = collect($d['items']);
            $items = $this->lock($lines->pluck('stock_item_id')->unique()->all());
            $t = $this->totals($lines->sum(fn ($l) => $l['quantity'] * $l['unit_cost']), (float) ($d['discount'] ?? 0), 0);

            $purchase = Purchase::create([
                'supplier_id' => $d['supplier_id'], 'purchased_on' => $d['purchased_on'], 'status' => 'received',
                'subtotal' => $t['subtotal'], 'discount' => $t['discount'], 'total' => $t['total'], 'notes' => $d['notes'] ?? null, 'created_by' => $user->id,
            ]);

            foreach ($lines as $l) {
                $item = $items[$l['stock_item_id']];
                $onHand = max(0, $this->balance($item->id));
                $qty = (int) $l['quantity'];
                // Weighted-average cost: used later to estimate profit on each sale.
                $avg = round((($onHand * (float) $item->cost_price) + ($qty * (float) $l['unit_cost'])) / ($onHand + $qty), 2);

                PurchaseItem::create(['purchase_id' => $purchase->id, 'stock_item_id' => $item->id, 'quantity' => $qty, 'unit_cost' => $l['unit_cost'], 'line_total' => round($qty * $l['unit_cost'], 2)]);
                $item->update(['cost_price' => $avg]);
                $this->move($item, $qty, 'purchase', $purchase, $d['purchased_on'], null, $user->id);
            }

            if (($d['paid'] ?? 0) > 0) {
                $this->addPayment($purchase, (float) $d['paid'], $d['method'] ?? 'cash', $d['purchased_on'], null, 'Paid at purchase', $user);
            }

            return $purchase;
        });
    }

    public function voidPurchase(Purchase $purchase, User $user): void
    {
        DB::transaction(function () use ($purchase, $user) {
            $p = Purchase::lockForUpdate()->with('items')->findOrFail($purchase->id);
            if ($p->isVoid()) {
                return;
            }
            if ($p->returns()->exists() || $p->payments()->exists()) {
                $this->fail('void', 'A purchase with returns or payments cannot be voided. Use a purchase return instead.');
            }
            $items = $this->lock($p->items->pluck('stock_item_id')->unique()->all());
            foreach ($p->items->groupBy('stock_item_id') as $id => $group) {
                if ($this->balance($id) < $group->sum('quantity')) {
                    $this->fail('void', 'Some of this stock has already been sold, so the purchase cannot be voided. Use a purchase return instead.');
                }
            }
            foreach ($p->items as $line) {
                $this->move($items[$line->stock_item_id], -$line->quantity, 'void', $p, now()->toDateString(), 'Purchase voided', $user->id);
            }
            $p->update(['status' => 'void']);
        });
    }

    // ------------------------------------------------------------------ sales

    /** @param array{customer_id?:?int, customer_name?:?string, sold_on:string, discount?:float, tax_percent?:float, notes?:?string, items:array<int,array{stock_item_id:int,quantity:int,unit_price:float}>, paid?:float, method?:string} $d */
    public function createSale(array $d, User $user): Sale
    {
        return DB::transaction(function () use ($d, $user) {
            $lines = collect($d['items']);
            $items = $this->lock($lines->pluck('stock_item_id')->unique()->all());

            foreach ($lines->groupBy('stock_item_id') as $id => $group) {
                $need = (int) $group->sum('quantity');
                $have = $this->balance($id);
                if ($need > $have) {
                    $this->fail('items', "Not enough stock for {$items[$id]->label()}: {$have} in stock, {$need} requested.");
                }
            }

            $t = $this->totals($lines->sum(fn ($l) => $l['quantity'] * $l['unit_price']), (float) ($d['discount'] ?? 0), (float) ($d['tax_percent'] ?? 0));

            $sale = Sale::create([
                'customer_id' => $d['customer_id'] ?? null, 'customer_name' => $d['customer_name'] ?? null, 'sold_on' => $d['sold_on'], 'status' => 'confirmed',
                'subtotal' => $t['subtotal'], 'discount' => $t['discount'], 'tax_percent' => min(max((float) ($d['tax_percent'] ?? 0), 0), 100),
                'tax_amount' => $t['tax'], 'total' => $t['total'], 'notes' => $d['notes'] ?? null, 'created_by' => $user->id,
            ]);

            foreach ($lines as $l) {
                $item = $items[$l['stock_item_id']];
                $qty = (int) $l['quantity'];
                SaleItem::create(['sale_id' => $sale->id, 'stock_item_id' => $item->id, 'quantity' => $qty, 'unit_price' => $l['unit_price'], 'unit_cost' => $item->cost_price, 'line_total' => round($qty * $l['unit_price'], 2)]);
                $this->move($item, -$qty, 'sale', $sale, $d['sold_on'], null, $user->id);
            }

            if (($d['paid'] ?? 0) > 0) {
                $this->addPayment($sale, (float) $d['paid'], $d['method'] ?? 'cash', $d['sold_on'], null, 'Paid at sale', $user);
            }

            return $sale;
        });
    }

    public function voidSale(Sale $sale, User $user): void
    {
        DB::transaction(function () use ($sale, $user) {
            $s = Sale::lockForUpdate()->with('items')->findOrFail($sale->id);
            if ($s->isVoid()) {
                return;
            }
            if ($s->returns()->exists() || $s->challans()->exists() || $s->payments()->exists()) {
                $this->fail('void', 'A sale with payments, returns or challans cannot be voided. Use a sale return instead.');
            }
            $items = $this->lock($s->items->pluck('stock_item_id')->unique()->all());
            foreach ($s->items as $line) {
                $this->move($items[$line->stock_item_id], $line->quantity, 'void', $s, now()->toDateString(), 'Sale voided', $user->id);
            }
            $s->update(['status' => 'void']);
        });
    }

    // ------------------------------------------------------------------ returns

    /** @param array<int,int> $qtyBySaleItem sale_item_id => quantity */
    public function createSaleReturn(Sale $sale, array $qtyBySaleItem, ?string $reason, string $date, User $user): SaleReturn
    {
        return DB::transaction(function () use ($sale, $qtyBySaleItem, $reason, $date, $user) {
            $s = Sale::lockForUpdate()->with('items.stockItem.product')->findOrFail($sale->id);
            if ($s->isVoid()) {
                $this->fail('returns', 'A voided sale cannot have returns.');
            }

            $already = SaleReturnItem::whereIn('sale_item_id', $s->items->pluck('id'))->selectRaw('sale_item_id, SUM(quantity) as q')->groupBy('sale_item_id')->pluck('q', 'sale_item_id');
            $rows = [];
            foreach ($qtyBySaleItem as $itemId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) {
                    continue;
                }
                $line = $s->items->firstWhere('id', (int) $itemId) ?? $this->fail('returns', 'That line does not belong to this sale.');
                $left = $line->quantity - (int) ($already[$line->id] ?? 0);
                if ($qty > $left) {
                    $this->fail('returns', "Cannot return {$qty} of {$line->stockItem->label()}: only {$left} left to return.");
                }
                $rows[] = [$line, $qty];
            }
            if (! $rows) {
                $this->fail('returns', 'Enter a quantity for at least one item.');
            }

            // A returned item gets back its share of the sale discount and of the tax.
            $ratio = (float) $s->subtotal > 0 ? ((float) $s->subtotal - (float) $s->discount) / (float) $s->subtotal : 1;
            $goods = collect($rows)->sum(fn ($r) => $r[1] * (float) $r[0]->unit_price);
            $refund = round($goods * $ratio * (1 + (float) $s->tax_percent / 100), 2);

            $return = SaleReturn::create(['sale_id' => $s->id, 'returned_on' => $date, 'reason' => $reason, 'refund_total' => $refund, 'created_by' => $user->id]);
            $locked = $this->lock(collect($rows)->map(fn ($r) => $r[0]->stock_item_id)->unique()->all());
            foreach ($rows as [$line, $qty]) {
                SaleReturnItem::create(['sale_return_id' => $return->id, 'sale_item_id' => $line->id, 'stock_item_id' => $line->stock_item_id, 'quantity' => $qty, 'unit_price' => $line->unit_price, 'line_total' => round($qty * (float) $line->unit_price, 2)]);
                $this->move($locked[$line->stock_item_id], $qty, 'sale_return', $return, $date, $reason, $user->id);
            }

            return $return;
        });
    }

    /** @param array<int,int> $qtyByPurchaseItem purchase_item_id => quantity */
    public function createPurchaseReturn(Purchase $purchase, array $qtyByPurchaseItem, ?string $reason, string $date, User $user): PurchaseReturn
    {
        return DB::transaction(function () use ($purchase, $qtyByPurchaseItem, $reason, $date, $user) {
            $p = Purchase::lockForUpdate()->with('items.stockItem.product')->findOrFail($purchase->id);
            if ($p->isVoid()) {
                $this->fail('returns', 'A voided purchase cannot have returns.');
            }

            $already = PurchaseReturnItem::whereIn('purchase_item_id', $p->items->pluck('id'))->selectRaw('purchase_item_id, SUM(quantity) as q')->groupBy('purchase_item_id')->pluck('q', 'purchase_item_id');
            $rows = [];
            foreach ($qtyByPurchaseItem as $itemId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) {
                    continue;
                }
                $line = $p->items->firstWhere('id', (int) $itemId) ?? $this->fail('returns', 'That line does not belong to this purchase.');
                $left = $line->quantity - (int) ($already[$line->id] ?? 0);
                if ($qty > $left) {
                    $this->fail('returns', "Cannot return {$qty} of {$line->stockItem->label()}: only {$left} left to return.");
                }
                $rows[] = [$line, $qty];
            }
            if (! $rows) {
                $this->fail('returns', 'Enter a quantity for at least one item.');
            }

            $locked = $this->lock(collect($rows)->map(fn ($r) => $r[0]->stock_item_id)->unique()->all());
            foreach (collect($rows)->groupBy(fn ($r) => $r[0]->stock_item_id) as $id => $group) {
                $need = $group->sum(fn ($r) => $r[1]);
                if ($this->balance($id) < $need) {
                    $this->fail('returns', "Not enough stock of {$locked[$id]->label()} to send back: {$this->balance($id)} in stock, {$need} to return.");
                }
            }

            $ratio = (float) $p->subtotal > 0 ? ((float) $p->subtotal - (float) $p->discount) / (float) $p->subtotal : 1;
            $credit = round(collect($rows)->sum(fn ($r) => $r[1] * (float) $r[0]->unit_cost) * $ratio, 2);

            $return = PurchaseReturn::create(['purchase_id' => $p->id, 'returned_on' => $date, 'reason' => $reason, 'credit_total' => $credit, 'created_by' => $user->id]);
            foreach ($rows as [$line, $qty]) {
                PurchaseReturnItem::create(['purchase_return_id' => $return->id, 'purchase_item_id' => $line->id, 'stock_item_id' => $line->stock_item_id, 'quantity' => $qty, 'unit_cost' => $line->unit_cost, 'line_total' => round($qty * (float) $line->unit_cost, 2)]);
                $this->move($locked[$line->stock_item_id], -$qty, 'purchase_return', $return, $date, $reason, $user->id);
            }

            return $return;
        });
    }

    // ------------------------------------------------------------------ challan and payments

    /** @param array<int,int> $qtyBySaleItem sale_item_id => quantity delivered */
    public function createChallan(Sale $sale, array $qtyBySaleItem, array $d, User $user): Challan
    {
        return DB::transaction(function () use ($sale, $qtyBySaleItem, $d, $user) {
            $s = Sale::lockForUpdate()->with('items.stockItem.product')->findOrFail($sale->id);
            if ($s->isVoid()) {
                $this->fail('challan', 'A voided sale cannot have a challan.');
            }

            $returned = SaleReturnItem::whereIn('sale_item_id', $s->items->pluck('id'))->selectRaw('sale_item_id, SUM(quantity) as q')->groupBy('sale_item_id')->pluck('q', 'sale_item_id');
            $delivered = ChallanItem::whereIn('sale_item_id', $s->items->pluck('id'))->selectRaw('sale_item_id, SUM(quantity) as q')->groupBy('sale_item_id')->pluck('q', 'sale_item_id');

            $rows = [];
            foreach ($qtyBySaleItem as $itemId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) {
                    continue;
                }
                $line = $s->items->firstWhere('id', (int) $itemId) ?? $this->fail('challan', 'That line does not belong to this sale.');
                $left = $line->quantity - (int) ($returned[$line->id] ?? 0) - (int) ($delivered[$line->id] ?? 0);
                if ($qty > $left) {
                    $this->fail('challan', "Cannot deliver {$qty} of {$line->stockItem->label()}: only {$left} not yet delivered.");
                }
                $rows[] = [$line, $qty];
            }
            if (! $rows) {
                $this->fail('challan', 'Enter a quantity for at least one item.');
            }

            $challan = Challan::create([
                'sale_id' => $s->id, 'delivered_on' => $d['delivered_on'], 'receiver_name' => $d['receiver_name'] ?? null, 'receiver_phone' => $d['receiver_phone'] ?? null,
                'delivery_address' => $d['delivery_address'] ?? null, 'vehicle_no' => $d['vehicle_no'] ?? null, 'driver_name' => $d['driver_name'] ?? null, 'notes' => $d['notes'] ?? null, 'created_by' => $user->id,
            ]);
            foreach ($rows as [$line, $qty]) {
                ChallanItem::create(['challan_id' => $challan->id, 'sale_item_id' => $line->id, 'quantity' => $qty]);
            }

            return $challan;
        });
    }

    public function addPayment(Model $payable, float $amount, string $method, string $date, ?string $reference, ?string $note, User $user): Payment
    {
        if ($amount <= 0) {
            $this->fail('amount', 'Enter an amount greater than zero.');
        }
        if ($payable->isVoid()) {
            $this->fail('amount', 'A voided document cannot take payments.');
        }
        $due = $payable->dueAmount();
        if ($amount > $due + 0.005) {
            $this->fail('amount', 'The amount is more than the balance due ('.number_format($due, 2).').');
        }

        return $payable->payments()->create(['amount' => round($amount, 2), 'method' => $method, 'paid_on' => $date, 'reference' => $reference, 'note' => $note, 'created_by' => $user->id]);
    }
}
