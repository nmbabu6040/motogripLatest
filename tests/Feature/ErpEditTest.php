<?php

use App\Models\Party;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StockItem;
use App\Models\User;
use App\Services\Erp\ErpService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function edUser(string $role = 'super-admin'): User { return User::factory()->create()->assignRole($role); }

function edItem(float $price = 100.0): StockItem
{
    static $n = 0; $n++;
    $cat = ProductCategory::firstOrCreate(['slug' => 'ed-cat'], ['name' => ['en' => 'Edit category'], 'is_active' => true]);
    $p = Product::create(['product_category_id' => $cat->id, 'slug' => "ed-p-{$n}", 'sku' => "ED-{$n}", 'name' => ['en' => "Edit Oil {$n}"], 'status' => 'published', 'published_at' => now()->subDay()]);

    return StockItem::create(['product_id' => $p->id, 'pack' => '4 L', 'sale_price' => $price, 'cost_price' => 0, 'reorder_level' => 0]);
}

function edBuy(StockItem $item, int $qty, float $cost): Purchase
{
    $s = Party::create(['kind' => 'supplier', 'name' => 'Supplier']);

    return app(ErpService::class)->createPurchase(['supplier_id' => $s->id, 'purchased_on' => today()->toDateString(), 'items' => [['stock_item_id' => $item->id, 'quantity' => $qty, 'unit_cost' => $cost]]], edUser());
}

function edSell(StockItem $item, int $qty, float $price, array $extra = []): Sale
{
    $c = Party::create(['kind' => 'customer', 'name' => 'Customer']);

    return app(ErpService::class)->createSale(array_merge(['customer_id' => $c->id, 'sold_on' => today()->toDateString(), 'items' => [['stock_item_id' => $item->id, 'quantity' => $qty, 'unit_price' => $price]]], $extra), edUser());
}

function edStock(StockItem $i): int { return app(ErpService::class)->balance($i->id); }

// ---------------------------------------------------------------- sales

it('edits the items of an invoice and corrects stock and totals', function () {
    $a = edItem(); $b = edItem();
    edBuy($a, 20, 50); edBuy($b, 20, 50);
    $sale = edSell($a, 5, 100);
    $user = edUser('sales-manager');

    $this->actingAs($user)->get("/admin/sales/{$sale->id}/edit")->assertOk()->assertSee('Edit invoice');
    $this->put("/admin/sales/{$sale->id}", ['customer_id' => $sale->customer_id, 'sold_on' => today()->toDateString(), 'discount' => 0, 'tax_percent' => 0,
        'items' => [['stock_item_id' => $a->id, 'quantity' => 8, 'unit_price' => 110], ['stock_item_id' => $b->id, 'quantity' => 2, 'unit_price' => 200]]])->assertRedirect(route('admin.sales.show', $sale));

    $sale->refresh();
    expect(edStock($a))->toBe(12)->and(edStock($b))->toBe(18)->and((float) $sale->total)->toBe(1280.0)->and($sale->items()->count())->toBe(2);
});

it('refuses an invoice edit that needs more stock than there is, and changes nothing', function () {
    $a = edItem();
    edBuy($a, 10, 50);
    $sale = edSell($a, 4, 100);

    $this->actingAs(edUser())->put("/admin/sales/{$sale->id}", ['sold_on' => today()->toDateString(), 'items' => [['stock_item_id' => $a->id, 'quantity' => 11, 'unit_price' => 100]]])->assertSessionHasErrors('items');
    expect(edStock($a))->toBe(6)->and((float) $sale->fresh()->total)->toBe(400.0);

    $this->put("/admin/sales/{$sale->id}", ['sold_on' => today()->toDateString(), 'items' => [['stock_item_id' => $a->id, 'quantity' => 10, 'unit_price' => 100]]])->assertRedirect();
    expect(edStock($a))->toBe(0);
});

it('only lets the date, customer and notes change once an invoice has a return', function () {
    $a = edItem();
    edBuy($a, 10, 50);
    $sale = edSell($a, 4, 100);
    app(ErpService::class)->createSaleReturn($sale, [$sale->items()->first()->id => 1], null, today()->toDateString(), edUser());
    $this->actingAs(edUser());

    $this->get("/admin/sales/{$sale->id}/edit")->assertOk()->assertSee('রিটার্ন বা চালান আছে');
    $this->put("/admin/sales/{$sale->id}", ['sold_on' => '2026-01-05', 'notes' => 'Corrected date', 'items' => [['stock_item_id' => $a->id, 'quantity' => 1, 'unit_price' => 1]]])->assertRedirect();

    $sale->refresh();
    expect($sale->sold_on->toDateString())->toBe('2026-01-05')->and($sale->notes)->toBe('Corrected date')->and((float) $sale->total)->toBe(400.0)->and($sale->items()->first()->quantity)->toBe(4);
});

it('will not let an invoice total drop below what has been paid', function () {
    $a = edItem();
    edBuy($a, 10, 50);
    $sale = edSell($a, 4, 100, ['paid' => 300]);

    $this->actingAs(edUser())->put("/admin/sales/{$sale->id}", ['sold_on' => today()->toDateString(), 'items' => [['stock_item_id' => $a->id, 'quantity' => 1, 'unit_price' => 100]]])->assertSessionHasErrors('items');
    expect((float) $sale->fresh()->total)->toBe(400.0);
});

it('removes a payment that was entered by mistake', function () {
    $a = edItem();
    edBuy($a, 10, 50);
    $sale = edSell($a, 2, 100, ['paid' => 200]);
    $payment = Payment::first();

    $this->actingAs(edUser('accountant'))->delete("/admin/payments/{$payment->id}")->assertRedirect();
    expect(Payment::count())->toBe(0)->and($sale->fresh()->dueAmount())->toBe(200.0);
});

it('checks the permission of the document before removing a payment', function () {
    $a = edItem();
    edBuy($a, 10, 50, );
    edSell($a, 2, 100, ['paid' => 100]);

    $this->actingAs(edUser('storekeeper'))->delete('/admin/payments/'.Payment::first()->id)->assertForbidden();
    expect(Payment::count())->toBe(1);
});

// ---------------------------------------------------------------- purchases

it('edits the items of a purchase, corrects stock and recalculates the average cost', function () {
    $a = edItem();
    $p = edBuy($a, 10, 100);
    $this->actingAs(edUser('storekeeper'));

    $this->get("/admin/purchases/{$p->id}/edit")->assertOk()->assertSee('Edit purchase');
    $this->put("/admin/purchases/{$p->id}", ['supplier_id' => $p->supplier_id, 'purchased_on' => today()->toDateString(), 'items' => [['stock_item_id' => $a->id, 'quantity' => 15, 'unit_cost' => 80]]])->assertRedirect();

    expect(edStock($a))->toBe(15)->and((float) $p->fresh()->total)->toBe(1200.0)->and((float) $a->fresh()->cost_price)->toBe(80.0);
});

it('will not let a purchase edit take away stock that has already been sold', function () {
    $a = edItem();
    $p = edBuy($a, 10, 100);
    edSell($a, 8, 150);

    $this->actingAs(edUser())->put("/admin/purchases/{$p->id}", ['supplier_id' => $p->supplier_id, 'purchased_on' => today()->toDateString(), 'items' => [['stock_item_id' => $a->id, 'quantity' => 5, 'unit_cost' => 100]]])->assertSessionHasErrors('items');
    expect(edStock($a))->toBe(2);
});

// ---------------------------------------------------------------- returns and challans

it('deletes a purchase return and puts the goods back in stock', function () {
    $a = edItem();
    $p = edBuy($a, 10, 100);
    $r = app(ErpService::class)->createPurchaseReturn($p, [$p->items()->first()->id => 4], null, today()->toDateString(), edUser());
    expect(edStock($a))->toBe(6);

    $this->actingAs(edUser())->get("/admin/purchase-returns/{$r->id}/edit")->assertOk();
    $this->put("/admin/purchase-returns/{$r->id}", ['returned_on' => '2026-02-01', 'reason' => 'Fixed reason'])->assertRedirect();
    expect($r->fresh()->reason)->toBe('Fixed reason');

    $this->delete("/admin/purchase-returns/{$r->id}")->assertRedirect(route('admin.purchase-returns.index'));
    expect(PurchaseReturn::count())->toBe(0)->and(edStock($a))->toBe(10);
});

it('deletes a sale return only while the returned goods are still in stock', function () {
    $a = edItem();
    edBuy($a, 10, 50);
    $sale = edSell($a, 4, 100);
    $r = app(ErpService::class)->createSaleReturn($sale, [$sale->items()->first()->id => 3], null, today()->toDateString(), edUser());   // stock 9
    $this->actingAs(edUser());

    $other = edSell($a, 9, 100);   // sells everything that is left
    $this->delete("/admin/sale-returns/{$r->id}")->assertSessionHasErrors('delete');
    expect(SaleReturn::count())->toBe(1);

    app(ErpService::class)->voidSale($other, edUser());
    $this->delete("/admin/sale-returns/{$r->id}")->assertRedirect();
    expect(SaleReturn::count())->toBe(0)->and(edStock($a))->toBe(6);
});

it('edits and deletes a challan, freeing its items for a new one', function () {
    $a = edItem();
    edBuy($a, 10, 50);
    $sale = edSell($a, 5, 100);
    $erp = app(ErpService::class);
    $ch = $erp->createChallan($sale, [$sale->items()->first()->id => 5], ['delivered_on' => today()->toDateString()], edUser());
    $this->actingAs(edUser());

    $this->get("/admin/challans/{$ch->id}/edit")->assertOk();
    $this->put("/admin/challans/{$ch->id}", ['delivered_on' => today()->toDateString(), 'vehicle_no' => 'DHAKA-99', 'receiver_name' => 'Karim'])->assertRedirect();
    expect($ch->fresh()->vehicle_no)->toBe('DHAKA-99');

    expect(fn () => $erp->createChallan($sale, [$sale->items()->first()->id => 1], ['delivered_on' => today()->toDateString()], edUser()))->toThrow(ValidationException::class);
    $this->delete("/admin/challans/{$ch->id}")->assertRedirect();
    expect($erp->createChallan($sale, [$sale->items()->first()->id => 5], ['delivered_on' => today()->toDateString()], edUser())->items()->first()->quantity)->toBe(5);
});

// ---------------------------------------------------------------- stock items

it('adds a stock item by hand, edits its details and deletes it while unused', function () {
    $cat = ProductCategory::firstOrCreate(['slug' => 'ed-cat'], ['name' => ['en' => 'Edit category'], 'is_active' => true]);
    $p = Product::create(['product_category_id' => $cat->id, 'slug' => 'manual-oil', 'sku' => 'MAN-1', 'name' => ['en' => 'Manual Oil'], 'status' => 'published']);
    $this->actingAs(edUser('storekeeper'));

    $this->get('/admin/stock/create')->assertOk()->assertSee('Manual Oil');
    $this->post('/admin/stock', ['product_id' => $p->id, 'pack' => '5 L', 'sale_price' => 500, 'cost_price' => 350, 'reorder_level' => 3])->assertRedirect();
    $item = StockItem::where('product_id', $p->id)->first();
    expect($item->pack)->toBe('5 L')->and((float) $item->cost_price)->toBe(350.0);

    $this->post('/admin/stock', ['product_id' => $p->id, 'pack' => '5 L', 'sale_price' => 1, 'cost_price' => 1, 'reorder_level' => 0])->assertSessionHasErrors('pack');

    $this->get("/admin/stock/{$item->id}/edit")->assertOk()->assertSee('Delete this stock item');
    $this->put("/admin/stock/{$item->id}/details", ['pack' => '5 Litre', 'sale_price' => 520, 'cost_price' => 360, 'reorder_level' => 4])->assertRedirect();
    expect($item->fresh()->pack)->toBe('5 Litre')->and((float) $item->fresh()->sale_price)->toBe(520.0);

    $this->delete("/admin/stock/{$item->id}")->assertRedirect(route('admin.stock.index'));
    expect(StockItem::count())->toBe(0);
});

it('does not delete a stock item that has history', function () {
    $a = edItem();
    edBuy($a, 5, 10);

    $this->actingAs(edUser())->delete("/admin/stock/{$a->id}")->assertSessionHasErrors('delete');
    expect(StockItem::count())->toBe(1);
});

it('keeps stock editing to people who may manage stock', function () {
    $a = edItem();
    $this->actingAs(edUser('product-manager'))->get("/admin/stock/{$a->id}/edit")->assertForbidden();
    $this->post('/admin/stock', ['product_id' => 1])->assertForbidden();
    $this->delete("/admin/stock/{$a->id}")->assertForbidden();
});
