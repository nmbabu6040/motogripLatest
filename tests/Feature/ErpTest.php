<?php

use App\Models\Challan;
use App\Models\Party;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Erp\ErpService;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function erpUser(string $role = 'super-admin'): User
{
    return User::factory()->create()->assignRole($role);
}

function erpItem(string $pack = '4 L', float $price = 100.0): StockItem
{
    static $n = 0;
    $n++;
    $cat = ProductCategory::firstOrCreate(['slug' => 'erp-cat'], ['name' => ['en' => 'ERP category'], 'is_active' => true]);
    $p = Product::create(['product_category_id' => $cat->id, 'slug' => "erp-p-{$n}", 'sku' => "ERP-{$n}", 'name' => ['en' => "Titan Oil {$n}"], 'status' => 'published', 'published_at' => now()->subDay()]);

    return StockItem::create(['product_id' => $p->id, 'pack' => $pack, 'sale_price' => $price, 'cost_price' => 0, 'reorder_level' => 0]);
}

function erpSupplier(): Party { return Party::create(['kind' => 'supplier', 'name' => 'Supplier Ltd']); }
function erpCustomer(): Party { return Party::create(['kind' => 'customer', 'name' => 'Rahim Motors', 'phone' => '01711111111', 'address' => 'Dhaka']); }

function erpBuy(StockItem $item, int $qty, float $cost, array $extra = []): Purchase
{
    return app(ErpService::class)->createPurchase(array_merge([
        'supplier_id' => erpSupplier()->id, 'purchased_on' => today()->toDateString(),
        'items' => [['stock_item_id' => $item->id, 'quantity' => $qty, 'unit_cost' => $cost]],
    ], $extra), erpUser());
}

function erpSell(StockItem $item, int $qty, float $price, array $extra = []): Sale
{
    return app(ErpService::class)->createSale(array_merge([
        'customer_id' => erpCustomer()->id, 'sold_on' => today()->toDateString(),
        'items' => [['stock_item_id' => $item->id, 'quantity' => $qty, 'unit_price' => $price]],
    ], $extra), erpUser());
}

function stockOf(StockItem $item): int { return app(ErpService::class)->balance($item->id); }

// ---------------------------------------------------------------- stock items

it('adds a stock item for every product and active pack size, once', function () {
    $cat = ProductCategory::create(['slug' => 'c', 'name' => ['en' => 'C'], 'is_active' => true]);
    $p = Product::create(['product_category_id' => $cat->id, 'slug' => 'oil', 'sku' => 'OIL-1', 'name' => ['en' => 'Oil'], 'status' => 'published']);
    $p->packaging()->create(['label' => '1 L', 'size_value' => 1, 'size_unit' => 'l', 'is_active' => true]);
    $p->packaging()->create(['label' => '4 L', 'size_value' => 4, 'size_unit' => 'l', 'is_active' => true]);
    Product::create(['product_category_id' => $cat->id, 'slug' => 'grease', 'sku' => 'GR-1', 'name' => ['en' => 'Grease'], 'status' => 'published']);

    $this->actingAs(erpUser('storekeeper'))->post('/admin/stock/sync')->assertRedirect();
    expect(StockItem::count())->toBe(3);

    $this->post('/admin/stock/sync');
    expect(StockItem::count())->toBe(3);
});

it('lets the storekeeper set the sale price and reorder level', function () {
    $item = erpItem();
    $this->actingAs(erpUser('storekeeper'))->put("/admin/stock/{$item->id}", ['sale_price' => '250.50', 'reorder_level' => 12])->assertRedirect();

    expect((float) $item->fresh()->sale_price)->toBe(250.5)->and($item->fresh()->reorder_level)->toBe(12);
});

it('adjusts stock up and down but never below zero', function () {
    $item = erpItem();
    $user = erpUser('storekeeper');
    $this->actingAs($user)->post("/admin/stock/{$item->id}/adjust", ['delta' => 3, 'reason' => 'Opening stock'])->assertRedirect();
    expect(stockOf($item))->toBe(3);

    $this->post("/admin/stock/{$item->id}/adjust", ['delta' => -5, 'reason' => 'Count'])->assertSessionHasErrors('delta');
    $this->post("/admin/stock/{$item->id}/adjust", ['delta' => 0, 'reason' => 'x'])->assertSessionHasErrors('delta');
    $this->post("/admin/stock/{$item->id}/adjust", ['delta' => 2])->assertSessionHasErrors('reason');
    $this->post("/admin/stock/{$item->id}/adjust", ['delta' => -1, 'reason' => 'Damaged'])->assertRedirect();
    expect(stockOf($item))->toBe(2)->and(StockMovement::where('stock_item_id', $item->id)->count())->toBe(2);
});

it('filters the stock list by status', function () {
    $out = erpItem(); $low = erpItem(); $ok = erpItem();
    $low->update(['reorder_level' => 10]); erpBuy($low, 5, 10);
    erpBuy($ok, 50, 10);
    $user = erpUser();

    $page = fn ($status) => $this->actingAs($user)->get('/admin/stock?status='.$status)->getContent();
    expect($page('out'))->toContain($out->product->name)->not->toContain($ok->product->name)
        ->and($page('low'))->toContain($low->product->name)->not->toContain($ok->product->name)
        ->and($page('ok'))->toContain($ok->product->name)->not->toContain($out->product->name);
});

it('shows the movement history of a stock item', function () {
    $item = erpItem();
    erpBuy($item, 7, 10);

    $this->actingAs(erpUser())->get("/admin/stock/{$item->id}")->assertOk()->assertSee('Purchase')->assertSee('+7');
});

// ---------------------------------------------------------------- purchases

it('increases stock when a purchase is saved and numbers the purchase', function () {
    $item = erpItem();
    $p = erpBuy($item, 10, 100, ['discount' => 50]);

    expect(stockOf($item))->toBe(10)->and($p->number())->toBe('PUR-000001')->and((float) $p->subtotal)->toBe(1000.0)->and((float) $p->total)->toBe(950.0);
});

it('keeps a weighted average cost across purchases', function () {
    $item = erpItem();
    erpBuy($item, 10, 100);
    erpBuy($item, 10, 200);

    expect((float) $item->fresh()->cost_price)->toBe(150.0)->and(stockOf($item))->toBe(20);
});

it('saves a purchase through the form and rejects bad input', function () {
    $item = erpItem();
    $user = erpUser('storekeeper');
    $supplier = erpSupplier();

    $this->actingAs($user)->post('/admin/purchases', ['supplier_id' => $supplier->id, 'purchased_on' => today()->toDateString(), 'items' => [['stock_item_id' => $item->id, 'quantity' => 5, 'unit_cost' => 40]], 'paid' => 100, 'method' => 'cash'])
        ->assertRedirect(route('admin.purchases.show', 1));
    expect(stockOf($item))->toBe(5)->and(Purchase::first()->paidAmount())->toBe(100.0)->and(Purchase::first()->dueAmount())->toBe(100.0);

    $this->post('/admin/purchases', ['supplier_id' => '', 'items' => []])->assertSessionHasErrors(['supplier_id', 'items', 'purchased_on']);
    $this->post('/admin/purchases', ['supplier_id' => $supplier->id, 'purchased_on' => today()->toDateString(), 'items' => [['stock_item_id' => $item->id, 'quantity' => 0, 'unit_cost' => -1]]])->assertSessionHasErrors(['items.0.quantity', 'items.0.unit_cost']);
});

it('rolls the whole purchase back when the first payment is too big', function () {
    $item = erpItem();

    expect(fn () => erpBuy($item, 2, 100, ['paid' => 5000]))->toThrow(ValidationException::class);
    expect(Purchase::count())->toBe(0)->and(stockOf($item))->toBe(0);
});

it('voids a purchase only while its stock is untouched', function () {
    $item = erpItem();
    $erp = app(ErpService::class);
    $user = erpUser();

    $p1 = erpBuy($item, 10, 10);
    $erp->voidPurchase($p1, $user);
    expect($p1->fresh()->status)->toBe('void')->and(stockOf($item))->toBe(0);

    $p2 = erpBuy($item, 10, 10);
    erpSell($item, 6, 20);
    expect(fn () => $erp->voidPurchase($p2, $user))->toThrow(ValidationException::class);
    expect($p2->fresh()->status)->toBe('received')->and(stockOf($item))->toBe(4);
});

it('returns goods to the supplier, reducing stock and what we owe', function () {
    $item = erpItem();
    $p = erpBuy($item, 10, 100, ['discount' => 100]);   // total 900
    $erp = app(ErpService::class);
    $line = $p->items()->first();

    $r = $erp->createPurchaseReturn($p, [$line->id => 4], 'Wrong grade', today()->toDateString(), erpUser());

    expect((float) $r->credit_total)->toBe(360.0)->and(stockOf($item))->toBe(6)->and($p->fresh()->balance())->toBe(540.0);
    expect(fn () => $erp->createPurchaseReturn($p, [$line->id => 7], null, today()->toDateString(), erpUser()))->toThrow(ValidationException::class);
});

it('will not send back stock that has already been sold', function () {
    $item = erpItem();
    $p = erpBuy($item, 10, 10);
    erpSell($item, 8, 20);

    expect(fn () => app(ErpService::class)->createPurchaseReturn($p, [$p->items()->first()->id => 5], null, today()->toDateString(), erpUser()))->toThrow(ValidationException::class);
    expect(stockOf($item))->toBe(2);
});

// ---------------------------------------------------------------- sales

it('reduces stock when a sale is saved, with discount and tax worked out on the server', function () {
    $item = erpItem();
    erpBuy($item, 20, 60);
    $sale = erpSell($item, 10, 100, ['discount' => 100, 'tax_percent' => 10]);

    expect(stockOf($item))->toBe(10)->and($sale->number())->toStartWith('INV-')
        ->and((float) $sale->subtotal)->toBe(1000.0)->and((float) $sale->tax_amount)->toBe(90.0)->and((float) $sale->total)->toBe(990.0)
        ->and((float) $sale->items()->first()->unit_cost)->toBe(60.0);
});

it('refuses to sell more than is in stock and saves nothing', function () {
    $item = erpItem();
    erpBuy($item, 5, 10);

    expect(fn () => erpSell($item, 6, 20))->toThrow(ValidationException::class);
    expect(Sale::count())->toBe(0)->and(stockOf($item))->toBe(5);
});

it('counts the same item on two lines together when checking stock', function () {
    $item = erpItem();
    erpBuy($item, 5, 10);

    expect(fn () => app(ErpService::class)->createSale(['sold_on' => today()->toDateString(), 'items' => [
        ['stock_item_id' => $item->id, 'quantity' => 3, 'unit_price' => 20], ['stock_item_id' => $item->id, 'quantity' => 3, 'unit_price' => 20],
    ]], erpUser()))->toThrow(ValidationException::class);
    expect(stockOf($item))->toBe(5);
});

it('creates an invoice through the form and shows a printable invoice', function () {
    $item = erpItem();
    erpBuy($item, 10, 50);
    Settings::set('erp', 'currency', 'Tk');
    Settings::set('erp', 'invoice_note', 'Pay within 15 days');
    $customer = erpCustomer();
    $user = erpUser('sales-manager');

    $this->actingAs($user)->post('/admin/sales', ['customer_id' => $customer->id, 'sold_on' => today()->toDateString(), 'discount' => 0, 'tax_percent' => 0,
        'items' => [['stock_item_id' => $item->id, 'quantity' => 3, 'unit_price' => 120]], 'paid' => 100, 'method' => 'bkash'])->assertRedirect();

    $sale = Sale::first();
    expect(stockOf($item))->toBe(7)->and($sale->paidAmount())->toBe(100.0);
    $this->get("/admin/sales/{$sale->id}")->assertOk()->assertSee('INVOICE')->assertSee($sale->number())->assertSee('Rahim Motors')->assertSee('Tk 360.00')->assertSee('Balance due')->assertSee('Pay within 15 days')->assertSee('Print invoice');
});

it('validates the sale form', function () {
    $this->actingAs(erpUser())->post('/admin/sales', ['sold_on' => '', 'items' => [], 'tax_percent' => 400])->assertSessionHasErrors(['sold_on', 'items', 'tax_percent']);
});

it('takes payments up to the balance and no further', function () {
    $item = erpItem();
    erpBuy($item, 10, 50);
    $sale = erpSell($item, 10, 100);
    $this->actingAs(erpUser());

    $this->post("/admin/sales/{$sale->id}/payments", ['amount' => 400, 'method' => 'cash', 'paid_on' => today()->toDateString()])->assertRedirect();
    expect($sale->fresh()->dueAmount())->toBe(600.0);

    $this->post("/admin/sales/{$sale->id}/payments", ['amount' => 700, 'method' => 'cash', 'paid_on' => today()->toDateString()])->assertSessionHasErrors('amount');
    $this->post("/admin/sales/{$sale->id}/payments", ['amount' => 600, 'method' => 'bank', 'paid_on' => today()->toDateString()])->assertRedirect();
    expect($sale->fresh()->dueAmount())->toBe(0.0);
});

it('puts returned goods back in stock and refunds a fair share of discount and tax', function () {
    $item = erpItem();
    erpBuy($item, 20, 50);
    $sale = erpSell($item, 10, 100, ['discount' => 100, 'tax_percent' => 10]);   // total 990
    $line = $sale->items()->first();
    $erp = app(ErpService::class);

    $r = $erp->createSaleReturn($sale, [$line->id => 5], 'Damaged', today()->toDateString(), erpUser());

    expect((float) $r->refund_total)->toBe(495.0)->and(stockOf($item))->toBe(15)->and($r->number())->toBe('SRN-000001');
    expect(fn () => $erp->createSaleReturn($sale, [$line->id => 6], null, today()->toDateString(), erpUser()))->toThrow(ValidationException::class);
    $erp->createSaleReturn($sale, [$line->id => 5], null, today()->toDateString(), erpUser());
    expect(stockOf($item))->toBe(20)->and($sale->fresh()->returnedAmount())->toBe(990.0)->and($sale->fresh()->dueAmount())->toBe(0.0);
});

it('needs a quantity before a return is saved', function () {
    $item = erpItem();
    erpBuy($item, 5, 10);
    $sale = erpSell($item, 2, 20);

    $this->actingAs(erpUser())->post("/admin/sales/{$sale->id}/returns", ['returned_on' => today()->toDateString(), 'qty' => [$sale->items()->first()->id => 0]])->assertSessionHasErrors('returns');
});

it('voids an invoice and puts the stock back, unless money or returns are attached', function () {
    $item = erpItem();
    erpBuy($item, 10, 10);
    $erp = app(ErpService::class);
    $user = erpUser();

    $a = erpSell($item, 4, 20);
    $erp->voidSale($a, $user);
    expect($a->fresh()->status)->toBe('void')->and(stockOf($item))->toBe(10);

    $b = erpSell($item, 4, 20, ['paid' => 10]);
    expect(fn () => $erp->voidSale($b, $user))->toThrow(ValidationException::class);
    expect($b->fresh()->status)->toBe('confirmed')->and(stockOf($item))->toBe(6);
    $this->actingAs($user)->post("/admin/sales/{$a->id}/payments", ['amount' => 1, 'method' => 'cash', 'paid_on' => today()->toDateString()])->assertSessionHasErrors('amount');
});

// ---------------------------------------------------------------- challans

it('creates partial delivery challans that never exceed what is left', function () {
    $item = erpItem();
    erpBuy($item, 10, 10);
    $sale = erpSell($item, 10, 20);
    $line = $sale->items()->first();
    $erp = app(ErpService::class);
    $d = ['delivered_on' => today()->toDateString(), 'receiver_name' => 'Rahim'];

    $c1 = $erp->createChallan($sale, [$line->id => 6], $d, erpUser());
    expect($c1->number())->toBe('CH-000001')->and($c1->items()->first()->quantity)->toBe(6);
    expect(fn () => $erp->createChallan($sale, [$line->id => 5], $d, erpUser()))->toThrow(ValidationException::class);

    $erp->createSaleReturn($sale, [$line->id => 2], null, today()->toDateString(), erpUser());
    expect(fn () => $erp->createChallan($sale, [$line->id => 3], $d, erpUser()))->toThrow(ValidationException::class);
    $erp->createChallan($sale, [$line->id => 2], $d, erpUser());
    expect(Challan::count())->toBe(2);
});

it('shows a printable challan with signature boxes and no prices', function () {
    $item = erpItem();
    erpBuy($item, 10, 10);
    $sale = erpSell($item, 3, 987.65);
    $this->actingAs(erpUser())->post("/admin/sales/{$sale->id}/challans", ['delivered_on' => today()->toDateString(), 'receiver_name' => 'Karim', 'vehicle_no' => 'DHAKA-1234', 'qty' => [$sale->items()->first()->id => 3]])->assertRedirect();

    $this->get('/admin/challans/'.Challan::first()->id)->assertOk()->assertSee('DELIVERY CHALLAN')->assertSee('Karim')->assertSee('DHAKA-1234')->assertSee('Received by')->assertDontSee('987.65');
});

// ---------------------------------------------------------------- parties and permissions

it('manages customers and suppliers and keeps their names on old documents', function () {
    $user = erpUser('accountant');
    $this->actingAs($user)->post('/admin/parties/customer', ['name' => 'New Customer', 'phone' => '017'])->assertRedirect();
    $this->post('/admin/parties/supplier', ['name' => 'New Supplier'])->assertRedirect();
    $this->post('/admin/parties/customer', ['name' => ''])->assertSessionHasErrors('name');
    $this->get('/admin/parties/robot')->assertNotFound();

    $c = Party::where('name', 'New Customer')->first();
    $this->put("/admin/parties/{$c->id}", ['name' => 'Renamed'])->assertRedirect();
    expect($c->fresh()->name)->toBe('Renamed')->and($c->fresh()->kind)->toBe('customer');

    $item = erpItem();
    erpBuy($item, 5, 10);
    $sale = erpSell($item, 1, 20, ['customer_id' => $c->id]);
    $this->delete("/admin/parties/{$c->id}")->assertRedirect();
    expect($sale->fresh()->customerLabel())->toBe('Renamed');
});

it('keeps each role to its own part of the business screens', function () {
    $paths = ['stock' => '/admin/stock', 'sales' => '/admin/sales', 'purchases' => '/admin/purchases', 'customers' => '/admin/parties/customer', 'challans' => '/admin/challans'];
    $expect = [
        'content-manager' => ['stock' => 403, 'sales' => 403, 'purchases' => 403, 'customers' => 403, 'challans' => 403],
        'storekeeper'     => ['stock' => 200, 'sales' => 403, 'purchases' => 200, 'customers' => 200, 'challans' => 403],
        'sales-manager'   => ['stock' => 200, 'sales' => 200, 'purchases' => 403, 'customers' => 200, 'challans' => 200],
        'accountant'      => ['stock' => 200, 'sales' => 200, 'purchases' => 200, 'customers' => 200, 'challans' => 200],
    ];
    foreach ($expect as $role => $codes) {
        foreach ($paths as $name => $path) {
            $this->actingAs(erpUser($role))->get($path)->assertStatus($codes[$name]);
        }
    }
});

it('lets only the storekeeper side change stock, not people who may merely view it', function () {
    $item = erpItem();
    $this->actingAs(erpUser('product-manager'))->get('/admin/stock')->assertOk();
    $this->post("/admin/stock/{$item->id}/adjust", ['delta' => 5, 'reason' => 'x'])->assertForbidden();
    $this->post('/admin/stock/sync')->assertForbidden();
});

// ---------------------------------------------------------------- dashboard, charts, branding

it('shows business figures and colourful charts on the dashboard to people who may see them', function () {
    $item = erpItem();
    erpBuy($item, 10, 50);
    erpSell($item, 4, 100);

    $this->actingAs(erpUser('accountant'))->get('/admin')->assertOk()->assertSee('Sales this month')->assertSee('Customers owe you')->assertSee('Stock value')->assertSee('Best sellers')->assertSee('data-chart-url', false)->assertSee('data-countup', false);
    $this->actingAs(erpUser('content-manager'))->get('/admin')->assertOk()->assertDontSee('Customers owe you')->assertDontSee('Best sellers');
});

it('serves chart data only to permitted roles, built from real records', function () {
    $item = erpItem();
    erpBuy($item, 10, 50);
    erpSell($item, 4, 100);

    $this->actingAs(erpUser('accountant'));
    $sp = $this->getJson('/admin/charts/sales_purchases')->assertOk()->json();
    expect($sp['type'])->toBe('bar')->and($sp['labels'])->toHaveCount(12)->and((float) array_sum($sp['datasets'][0]['data']))->toBe(400.0)->and((float) array_sum($sp['datasets'][1]['data']))->toBe(500.0);
    expect($this->getJson('/admin/charts/sales_daily')->json('labels'))->toHaveCount(30);
    expect(array_map('floatval', $this->getJson('/admin/charts/top_products')->json('datasets.0.data')))->toBe([400.0]);
    expect($this->getJson('/admin/charts/stock_status')->json('datasets.0.data'))->toBe([1, 0, 0]);
    $this->getJson('/admin/charts/partner_clicks')->assertForbidden();
    $this->getJson('/admin/charts/nonsense')->assertNotFound();

    $this->actingAs(erpUser('content-manager'))->getJson('/admin/charts/sales_purchases')->assertForbidden();
});

it('never invents numbers: empty books give empty charts', function () {
    $json = $this->actingAs(erpUser())->getJson('/admin/charts/sales_purchases')->json();

    expect((float) array_sum($json['datasets'][0]['data']))->toBe(0.0)->and((float) array_sum($json['datasets'][1]['data']))->toBe(0.0);
});

it('uses the admin logo and icon from settings in the admin panel and on the sign-in page', function () {
    Storage::fake('public');
    $user = erpUser();

    $this->actingAs($user)->put('/admin/settings', [
        'brand_site_name' => 'Motogrip', 'img_admin_logo_url' => UploadedFile::fake()->image('adminlogo.png', 300, 100), 'img_admin_icon_url' => UploadedFile::fake()->image('icon.png', 64, 64),
    ])->assertRedirect();

    $logo = Settings::get('brand', 'admin_logo_url'); $icon = Settings::get('brand', 'admin_icon_url');
    expect($logo)->toStartWith('/storage/branding/')->and($icon)->toStartWith('/storage/branding/');
    $this->get('/admin')->assertOk()->assertSee($logo)->assertSee('rel="icon" href="'.$icon.'"', false);
    auth()->logout();
    $this->get('/login')->assertOk()->assertSee($logo)->assertSee('rel="icon" href="'.$icon.'"', false);
});

it('falls back to the website logo when no separate admin logo is set', function () {
    Settings::set('brand', 'logo_url', '/storage/branding/site-logo.png');

    $this->actingAs(erpUser())->get('/admin')->assertOk()->assertSee('/storage/branding/site-logo.png');
});

it('shows every new business screen without errors on an empty database', function () {
    $this->actingAs(erpUser());
    foreach (['/admin/stock', '/admin/sales', '/admin/sales/create', '/admin/purchases', '/admin/purchases/create', '/admin/sale-returns', '/admin/purchase-returns', '/admin/challans', '/admin/parties/customer', '/admin/parties/supplier'] as $path) {
        $this->get($path)->assertOk();
    }
});

it('keeps sales managers away from page and homepage editing', function () {
    $this->actingAs(erpUser('sales-manager'))->get('/admin/pages')->assertForbidden();
    $this->get('/admin/homepage')->assertForbidden();
});
