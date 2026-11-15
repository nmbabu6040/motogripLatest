<?php

use App\Models\OnlinePayment;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\StockItem;
use App\Models\User;
use App\Services\Erp\ErpService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function payOn(): void
{
    config(['motogrip.payments.enabled' => true, 'motogrip.payments.sslcommerz.store_id' => 'teststore', 'motogrip.payments.sslcommerz.store_password' => 'secret', 'motogrip.payments.sslcommerz.sandbox' => true]);
}

function paySale(float $price = 1000.0, array $extra = []): Sale
{
    static $n = 0; $n++;
    $user = User::factory()->create()->assignRole('super-admin');
    $cat = ProductCategory::firstOrCreate(['slug' => 'pay-cat'], ['name' => ['en' => 'Pay category'], 'is_active' => true]);
    $p = Product::create(['product_category_id' => $cat->id, 'slug' => "pay-p-{$n}", 'sku' => "PAY-{$n}", 'name' => ['en' => "Pay Oil {$n}"], 'status' => 'published']);
    $item = StockItem::create(['product_id' => $p->id, 'pack' => '', 'sale_price' => $price, 'cost_price' => 10, 'reorder_level' => 0]);
    $erp = app(ErpService::class);
    $erp->adjustStock($item, 50, 'Opening', today()->toDateString(), $user);
    $c = Party::create(['kind' => 'customer', 'name' => 'Rahim Motors', 'phone' => '01711111111', 'email' => 'rahim@example.com']);

    return $erp->createSale(array_merge(['customer_id' => $c->id, 'sold_on' => today()->toDateString(), 'items' => [['stock_item_id' => $item->id, 'quantity' => 1, 'unit_price' => $price]]], $extra), $user);
}

function payLinkFor(Sale $sale): string
{
    test()->actingAs(User::factory()->create()->assignRole('accountant'))->post("/admin/sales/{$sale->id}/pay-link")->assertRedirect();
    auth()->logout();

    return $sale->fresh()->pay_token;
}

function fakeGateway(array $validation = []): void
{
    Http::swap(new \Illuminate\Http\Client\Factory());   // the first matching stub wins, so start from a clean client each time
    Http::fake([
        '*/gwprocess/v4/api.php' => Http::response(['status' => 'SUCCESS', 'GatewayPageURL' => 'https://sandbox.sslcommerz.com/gw/pay123', 'sessionkey' => 'abc']),
        '*/validator/api/validationserverAPI.php*' => fn ($request) => Http::response(array_merge(['status' => 'VALID', 'currency_type' => 'BDT', 'risk_level' => '0', 'bank_tran_id' => 'BANK1'], $validation)),
    ]);
}

it('keeps the whole feature off, with 404 pages, until it is switched on and configured', function () {
    $sale = paySale();
    $sale->update(['pay_token' => str_repeat('a', 40)]);

    $this->get('/pay/'.str_repeat('a', 40))->assertNotFound();
    $this->post('/pay-ipn', ['tran_id' => 'x'])->assertNotFound();
    $this->actingAs(User::factory()->create()->assignRole('accountant'))->post("/admin/sales/{$sale->id}/pay-link")->assertNotFound();

    config(['motogrip.payments.enabled' => true]);   // switched on but no store id or password
    $this->get('/pay/'.str_repeat('a', 40))->assertNotFound();
});

it('creates a secret payment link on the invoice page and shows it to the accountant', function () {
    payOn();
    $sale = paySale();

    $token = payLinkFor($sale);
    expect($token)->toHaveLength(40);
    $this->actingAs(User::factory()->create()->assignRole('accountant'))->get("/admin/sales/{$sale->id}")->assertOk()->assertSee(route('pay.show', $token));
    expect(payLinkFor($sale))->toBe($token);   // pressing the button again keeps the same link
});

it('shows the customer the invoice and what is left to pay, and never indexes the page', function () {
    payOn();
    $sale = paySale(1000);
    $token = payLinkFor($sale);

    $this->get("/pay/{$token}")->assertOk()->assertSee($sale->number())->assertSee('Rahim Motors')->assertSee('1,000.00')->assertSee('noindex', false);
    $this->get('/pay/'.str_repeat('z', 40))->assertNotFound();
});

it('starts a payment for the full balance and sends the customer to the gateway', function () {
    payOn();
    fakeGateway();
    $sale = paySale(1000, ['paid' => 250]);
    $token = payLinkFor($sale);

    $this->post("/pay/{$token}/start", ['name' => 'Rahim', 'email' => 'rahim@example.com', 'phone' => '01711111111'])->assertRedirect('https://sandbox.sslcommerz.com/gw/pay123');

    $row = OnlinePayment::first();
    expect($row->status)->toBe('initiated')->and((float) $row->amount)->toBe(750.0)->and($row->tran_id)->toStartWith('MG'.$sale->id.'-');
    Http::assertSent(fn ($r) => str_contains($r->url(), 'gwprocess') && $r['store_id'] === 'teststore' && $r['total_amount'] === '750.00' && $r['currency'] === 'BDT' && $r['tran_id'] === $row->tran_id
        && str_ends_with($r['success_url'], '/pay-callback/success') && str_ends_with($r['ipn_url'], '/pay-ipn'));
});

it('asks for name, email and phone before it goes to the gateway', function () {
    payOn();
    fakeGateway();
    $token = payLinkFor(paySale());

    $this->post("/pay/{$token}/start", ['name' => '', 'email' => 'nope', 'phone' => 'abc'])->assertSessionHasErrors(['name', 'email', 'phone']);
    Http::assertNothingSent();
});

it('shows a friendly error and records a failed attempt when the gateway cannot open a page', function () {
    payOn();
    Http::fake(['*/gwprocess/v4/api.php' => Http::response(['status' => 'FAILED', 'failedreason' => 'Invalid Store ID'], 200)]);
    $token = payLinkFor(paySale());

    $this->from("/pay/{$token}")->post("/pay/{$token}/start", ['name' => 'R', 'email' => 'r@example.com', 'phone' => '01711111111'])->assertSessionHasErrors('pay');
    expect(OnlinePayment::first()->status)->toBe('failed')->and(OnlinePayment::first()->note)->toContain('Invalid Store ID');
});

it('records the payment once the gateway confirms it, and only once', function () {
    payOn();
    $sale = paySale(1000);
    $token = payLinkFor($sale);
    fakeGateway();
    $this->post("/pay/{$token}/start", ['name' => 'R', 'email' => 'r@example.com', 'phone' => '01711111111']);
    $row = OnlinePayment::first();
    fakeGateway(['tran_id' => $row->tran_id, 'amount' => '1000.00']);

    $reply = ['status' => 'VALID', 'tran_id' => $row->tran_id, 'val_id' => 'VAL1', 'amount' => '1000.00'];
    $this->post('/pay-callback/success', $reply)->assertRedirect(route('pay.show', $token));
    $this->post('/pay-ipn', $reply)->assertOk();
    $this->post('/pay-callback/success', $reply)->assertRedirect();   // the browser coming back a second time

    expect(Payment::count())->toBe(1)->and($sale->fresh()->dueAmount())->toBe(0.0)->and($row->fresh()->status)->toBe('paid')->and($row->fresh()->paid_at)->not->toBeNull();
    $p = Payment::first();
    expect($p->method)->toBe('online')->and($p->reference)->toBe($row->tran_id)->and($p->created_by)->toBeNull();
    $this->get("/pay/{$token}")->assertSee('paid in full');
});

it('does not trust what the browser says: the gateway must confirm the transaction and the amount', function () {
    payOn();
    $sale = paySale(1000);
    $token = payLinkFor($sale);
    fakeGateway();
    $this->post("/pay/{$token}/start", ['name' => 'R', 'email' => 'r@example.com', 'phone' => '01711111111']);
    $row = OnlinePayment::first();

    // the gateway says the money is for a different transaction
    fakeGateway(['tran_id' => 'SOMETHING-ELSE', 'amount' => '1000.00']);
    $this->post('/pay-callback/success', ['status' => 'VALID', 'tran_id' => $row->tran_id, 'val_id' => 'V1', 'amount' => '1000.00']);
    expect(Payment::count())->toBe(0)->and($row->fresh()->status)->toBe('failed');

    // the gateway confirms a smaller amount than was asked for
    $row->update(['status' => 'initiated']);
    fakeGateway(['tran_id' => $row->tran_id, 'amount' => '10.00']);
    $this->post('/pay-callback/success', ['status' => 'VALID', 'tran_id' => $row->tran_id, 'val_id' => 'V2', 'amount' => '1000.00']);
    expect(Payment::count())->toBe(0)->and($row->fresh()->status)->toBe('failed');

    // the gateway does not know the payment at all
    $row->update(['status' => 'initiated']);
    fakeGateway(['status' => 'INVALID_TRANSACTION']);
    $this->post('/pay-callback/success', ['status' => 'VALID', 'tran_id' => $row->tran_id, 'val_id' => 'V3']);
    expect(Payment::count())->toBe(0);
});

it('ignores replies for unknown transactions', function () {
    payOn();
    fakeGateway();

    $this->post('/pay-ipn', ['status' => 'VALID', 'tran_id' => 'NOPE', 'val_id' => 'V'])->assertOk();
    expect(Payment::count())->toBe(0);
    Http::assertNothingSent();
});

it('marks a risky payment for review instead of recording it', function () {
    payOn();
    $sale = paySale(1000);
    $token = payLinkFor($sale);
    fakeGateway();
    $this->post("/pay/{$token}/start", ['name' => 'R', 'email' => 'r@example.com', 'phone' => '01711111111']);
    $row = OnlinePayment::first();
    fakeGateway(['tran_id' => $row->tran_id, 'amount' => '1000.00', 'risk_level' => '1']);

    $this->post('/pay-callback/success', ['status' => 'VALID', 'tran_id' => $row->tran_id, 'val_id' => 'V1']);
    expect(Payment::count())->toBe(0)->and($row->fresh()->status)->toBe('review');
    $this->actingAs(User::factory()->create()->assignRole('accountant'))->get("/admin/sales/{$sale->id}")->assertSee('Review')->assertSee('marked this payment as risky');
});

it('puts money that arrives after the balance changed into review rather than losing it', function () {
    payOn();
    $sale = paySale(1000);
    $token = payLinkFor($sale);
    fakeGateway();
    $this->post("/pay/{$token}/start", ['name' => 'R', 'email' => 'r@example.com', 'phone' => '01711111111']);
    $row = OnlinePayment::first();
    app(ErpService::class)->addPayment($sale, 1000, 'cash', today()->toDateString(), null, null, User::factory()->create());   // paid in cash meanwhile
    fakeGateway(['tran_id' => $row->tran_id, 'amount' => '1000.00']);

    $this->post('/pay-callback/success', ['status' => 'VALID', 'tran_id' => $row->tran_id, 'val_id' => 'V1']);
    expect(Payment::count())->toBe(1)->and($row->fresh()->status)->toBe('review')->and($row->fresh()->note)->toContain('could not be applied');
});

it('records a cancelled or failed visit without touching the invoice, and a late confirmation still counts', function () {
    payOn();
    $sale = paySale(1000);
    $token = payLinkFor($sale);
    fakeGateway();
    $this->post("/pay/{$token}/start", ['name' => 'R', 'email' => 'r@example.com', 'phone' => '01711111111']);
    $row = OnlinePayment::first();

    $this->post('/pay-callback/cancel', ['tran_id' => $row->tran_id])->assertRedirect(route('pay.show', $token));
    expect($row->fresh()->status)->toBe('cancelled')->and(Payment::count())->toBe(0);
    $this->get("/pay/{$token}")->assertSee('cancelled the payment');

    fakeGateway(['tran_id' => $row->tran_id, 'amount' => '1000.00']);
    $this->post('/pay-ipn', ['status' => 'VALID', 'tran_id' => $row->tran_id, 'val_id' => 'LATE']);
    expect($row->fresh()->status)->toBe('paid')->and(Payment::count())->toBe(1);
});

it('does not take online payments for a voided invoice or one that is paid in full', function () {
    payOn();
    fakeGateway();
    $paid = paySale(500, ['paid' => 500]);
    $paid->update(['pay_token' => str_repeat('p', 40)]);
    $this->get('/pay/'.str_repeat('p', 40))->assertOk()->assertSee('paid in full');
    $this->post('/pay/'.str_repeat('p', 40).'/start', ['name' => 'R', 'email' => 'r@example.com', 'phone' => '01711111111'])->assertSessionHasErrors('pay');

    $void = paySale(500);
    $void->update(['pay_token' => str_repeat('v', 40), 'status' => 'void']);
    $this->get('/pay/'.str_repeat('v', 40))->assertNotFound();
});

it('exempts only the gateway return addresses from the CSRF check', function () {
    $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

    expect($bootstrap)->toContain("'pay-callback/*'")->toContain("'pay-ipn'")->not->toContain("'pay/*'")->not->toContain("'*'");
});

it('lets the content security policy follow the redirect to the gateway', function () {
    $csp = $this->get('/')->headers->get('Content-Security-Policy');

    expect($csp)->toContain('form-action')->toContain('sslcommerz.com');
});
