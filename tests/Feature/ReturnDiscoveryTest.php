<?php

use App\Models\Party;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockItem;
use App\Models\User;
use App\Services\Erp\ErpService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function rdUser(string $role = 'super-admin'): User { return User::factory()->create()->assignRole($role); }

function rdStock(): StockItem
{
    static $n = 0; $n++;
    $cat = ProductCategory::firstOrCreate(['slug' => 'rd-cat'], ['name' => ['en' => 'RD category'], 'is_active' => true]);
    $p = Product::create(['product_category_id' => $cat->id, 'slug' => "rd-p-{$n}", 'sku' => "RD-{$n}", 'name' => ['en' => "RD Oil {$n}"], 'status' => 'published']);

    return StockItem::create(['product_id' => $p->id, 'pack' => '', 'sale_price' => 100, 'cost_price' => 0, 'reorder_level' => 0]);
}

function rdPurchase(StockItem $item, int $qty = 10): Purchase
{
    $s = Party::create(['kind' => 'supplier', 'name' => 'RD Supplier']);

    return app(ErpService::class)->createPurchase(['supplier_id' => $s->id, 'purchased_on' => today()->toDateString(), 'items' => [['stock_item_id' => $item->id, 'quantity' => $qty, 'unit_cost' => 50]]], rdUser());
}

function rdSale(StockItem $item, int $qty = 2): Sale
{
    $c = Party::create(['kind' => 'customer', 'name' => 'RD Customer']);

    return app(ErpService::class)->createSale(['customer_id' => $c->id, 'sold_on' => today()->toDateString(), 'items' => [['stock_item_id' => $item->id, 'quantity' => $qty, 'unit_price' => 100]]], rdUser());
}

it('shows Return, Edit and View links on every purchase row, except for voided ones', function () {
    $item = rdStock();
    $live = rdPurchase($item);
    $void = rdPurchase($item);
    app(ErpService::class)->voidPurchase($void, rdUser());

    $html = $this->actingAs(rdUser('storekeeper'))->get('/admin/purchases')->assertOk()->getContent();
    expect($html)->toContain(route('admin.purchases.returns.create', $live))->toContain(route('admin.purchases.edit', $live))
        ->not->toContain(route('admin.purchases.returns.create', $void))->toContain(route('admin.purchases.show', $void));
});

it('shows Return, Edit and Challan links on every invoice row', function () {
    $item = rdStock();
    rdPurchase($item);
    $sale = rdSale($item);

    $html = $this->actingAs(rdUser('sales-manager'))->get('/admin/sales')->assertOk()->getContent();
    expect($html)->toContain(route('admin.sales.returns.create', $sale))->toContain(route('admin.sales.edit', $sale))->toContain(route('admin.sales.challans.create', $sale));
});

it('lets a purchase return be started from the returns list by choosing the purchase', function () {
    $item = rdStock();
    $p = rdPurchase($item);
    $void = rdPurchase($item);
    app(ErpService::class)->voidPurchase($void, rdUser());
    $this->actingAs(rdUser('storekeeper'));

    $this->get('/admin/purchase-returns')->assertOk()->assertSee('Make a new purchase return')->assertSee($p->number())->assertDontSee($void->number());
    $this->get('/admin/purchase-returns/start?purchase='.$p->id)->assertRedirect(route('admin.purchases.returns.create', $p));
    $this->get('/admin/purchase-returns/start?purchase=9999')->assertSessionHasErrors('purchase');
    $this->get('/admin/purchase-returns/start?purchase='.$void->id)->assertSessionHasErrors('purchase');
    $this->get('/admin/purchase-returns/start')->assertSessionHasErrors('purchase');
});

it('lets a sale return and a challan be started by choosing the invoice', function () {
    $item = rdStock();
    rdPurchase($item);
    $sale = rdSale($item);
    $this->actingAs(rdUser('sales-manager'));

    $this->get('/admin/sale-returns')->assertOk()->assertSee('Make a new sale return')->assertSee($sale->number());
    $this->get('/admin/sale-returns/start?sale='.$sale->id)->assertRedirect(route('admin.sales.returns.create', $sale));
    $this->get('/admin/sale-returns/start?sale=0')->assertSessionHasErrors('sale');

    $this->get('/admin/challans')->assertOk()->assertSee('Make a new challan')->assertSee($sale->number());
    $this->get('/admin/challans/start?sale='.$sale->id)->assertRedirect(route('admin.sales.challans.create', $sale));
    $this->get('/admin/challans/start?sale=abc')->assertSessionHasErrors('sale');
});

it('keeps the start-a-return pages to the roles that may use them', function () {
    $item = rdStock();
    $p = rdPurchase($item);

    $this->actingAs(rdUser('sales-manager'))->get('/admin/purchase-returns/start?purchase='.$p->id)->assertForbidden();
    $this->actingAs(rdUser('storekeeper'))->get('/admin/sale-returns/start?sale=1')->assertForbidden();
});

it('shows the Return to supplier button on the purchase page', function () {
    $item = rdStock();
    $p = rdPurchase($item);

    $this->actingAs(rdUser('storekeeper'))->get("/admin/purchases/{$p->id}")->assertOk()->assertSee('Return items to supplier')->assertSee('Edit');
});
