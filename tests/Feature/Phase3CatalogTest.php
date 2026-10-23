<?php

use App\Models\Document;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSpecification;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

function category(string $name = 'Gear Oils'): ProductCategory
{
    return ProductCategory::create(['slug' => str($name)->slug()->toString(), 'name' => ['en' => $name], 'is_active' => true]);
}

function product(array $attrs = [], ?ProductCategory $cat = null): Product
{
    static $n = 0;
    $n++;

    return Product::create(array_merge([
        'product_category_id' => ($cat ?? category("Cat {$n}"))->id, 'slug' => "prod-{$n}", 'sku' => "SKU-{$n}",
        'name' => ['en' => "Product {$n}"], 'status' => 'published', 'published_at' => now()->subDay(),
    ], $attrs));
}

function staff(string $role): User
{
    return User::factory()->create()->assignRole($role);
}

it('lets a product manager create a product with specs, and a content manager not', function () {
    $cat = category();
    $payload = ['product_category_id' => $cat->id, 'sku' => 'MG-1', 'name' => ['en' => 'Test Oil'], 'status' => 'draft',
        'specs_text' => 'Flash point: 220 °C'];

    $this->actingAs(staff('content-manager'))->post('/admin/products', $payload)->assertForbidden();

    $this->actingAs(staff('product-manager'))->post('/admin/products', $payload)->assertRedirect();
    expect(Product::where('sku', 'MG-1')->exists())->toBeTrue();
    expect(ProductSpecification::where('value', '220 °C')->first()->is_verified)->toBeFalse();
});

it('forbids publishing without products.publish', function () {
    $cat = category();
    $role = \Spatie\Permission\Models\Role::create(['name' => 'editor-only']);
    $role->givePermissionTo(['dashboard.view', 'products.view', 'products.create', 'products.update']);
    $user = User::factory()->create()->assignRole('editor-only');

    $this->actingAs($user)->post('/admin/products', ['product_category_id' => $cat->id, 'sku' => 'X-1', 'name' => ['en' => 'X'], 'status' => 'published'])
        ->assertSessionHasErrors('status');
});

it('validates product input', function () {
    $this->actingAs(staff('product-manager'))->post('/admin/products', ['sku' => '', 'status' => 'nonsense'])
        ->assertSessionHasErrors(['sku', 'status', 'product_category_id', 'name.en']);
});

it('shows only published products to the public', function () {
    $live = product(['name' => ['en' => 'Visible Oil']]);
    $draft = product(['status' => 'draft', 'name' => ['en' => 'Secret Draft']]);
    $future = product(['published_at' => now()->addWeek(), 'name' => ['en' => 'Future Oil']]);

    $this->get('/products')->assertOk()->assertSee('Visible Oil')->assertDontSee('Secret Draft')->assertDontSee('Future Oil');
    $this->get("/products/{$live->slug}")->assertOk();
    $this->get("/products/{$draft->slug}")->assertNotFound();
});

it('never shows unverified specifications or approvals', function () {
    $p = product();
    $p->specifications()->create(['name' => ['en' => 'Pour point'], 'value' => '-33', 'is_verified' => false]);
    $p->specifications()->create(['name' => ['en' => 'Flash point'], 'value' => '220', 'is_verified' => true]);
    $p->approvals()->create(['type' => 'api', 'name' => 'API SN Plus', 'verified_at' => null]);

    $this->get("/products/{$p->slug}")->assertOk()->assertSee('Flash point')->assertDontSee('Pour point')->assertDontSee('API SN Plus');
});

it('filters the catalog by category and viscosity and searches by sku', function () {
    $gear = category('Gear Oils');
    product(['name' => ['en' => 'Alpha'], 'viscosity_grade' => '80W-90', 'sku' => 'AAA-1'], $gear);
    product(['name' => ['en' => 'Beta'], 'viscosity_grade' => '10W-40', 'sku' => 'BBB-2']);

    $this->get('/products?category=gear-oils')->assertSee('Alpha')->assertDontSee('Beta');
    $this->get('/products?viscosity=10W-40')->assertSee('Beta')->assertDontSee('Alpha');
    $this->get('/products?q=AAA-1')->assertSee('Alpha')->assertDontSee('Beta');
    $this->get('/products?q=nothingmatches')->assertSee('No results');
});

it('compares products and highlights differing rows', function () {
    $a = product(['viscosity_grade' => '10W-40', 'application' => 'Diesel']);
    $b = product(['viscosity_grade' => '15W-40', 'application' => 'Diesel']);

    $res = $this->get("/compare?p[]={$a->slug}&p[]={$b->slug}")->assertOk()->assertSee('10W-40')->assertSee('15W-40');
    expect($res->getContent())->toContain('bg-amber/15');
});

it('enforces document download permissions', function () {
    Storage::fake('local');
    Storage::disk('local')->put('documents/a.pdf', '%PDF-1.4 test');
    $mk = fn (string $vis) => Document::create(['type' => 'tds', 'title' => ['en' => "Doc {$vis}"], 'disk' => 'local', 'path' => 'documents/a.pdf', 'original_name' => 'a.pdf', 'mime' => 'application/pdf', 'size' => 12, 'checksum' => hash('sha256', $vis), 'visibility' => $vis, 'published_at' => now()->subDay()]);
    $public = $mk('public'); $auth = $mk('authenticated'); $restricted = $mk('restricted');

    $this->get(route('documents.download', $public))->assertOk();
    $this->get(route('documents.download', $auth))->assertRedirect(route('login'));
    $this->get(route('documents.download', $restricted))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create()->assignRole('customer'));
    $this->get(route('documents.download', $auth))->assertOk();
    $this->get(route('documents.download', $restricted))->assertForbidden();

    $this->actingAs(User::factory()->create()->assignRole('dealer'))->get(route('documents.download', $restricted))->assertOk();
    expect(\DB::table('document_downloads')->sum('downloads'))->toBe(3);
});

it('hides unpublished documents from the public', function () {
    Storage::fake('local');
    Storage::disk('local')->put('documents/b.pdf', 'x');
    $d = Document::create(['type' => 'sds', 'title' => ['en' => 'Draft doc'], 'disk' => 'local', 'path' => 'documents/b.pdf', 'original_name' => 'b.pdf', 'mime' => 'application/pdf', 'size' => 1, 'checksum' => 'c', 'visibility' => 'public', 'published_at' => null]);

    $this->get(route('documents.download', $d))->assertNotFound();
});

it('rejects non-pdf and duplicate document uploads', function () {
    Storage::fake('local');
    $user = staff('product-manager');
    $base = ['type' => 'tds', 'title' => ['en' => 'TDS'], 'visibility' => 'public', 'language' => 'en'];

    $this->actingAs($user)->post('/admin/documents', $base + ['file' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php')])->assertSessionHasErrors('file');

    $pdf = UploadedFile::fake()->createWithContent('a.pdf', '%PDF-1.4 same');
    $this->actingAs($user)->post('/admin/documents', $base + ['file' => $pdf])->assertRedirect();
    $again = UploadedFile::fake()->createWithContent('b.pdf', '%PDF-1.4 same');
    $this->actingAs($user)->post('/admin/documents', $base + ['file' => $again])->assertSessionHasErrors('file');
    expect(Document::count())->toBe(1);
});

it('saves the simple product form and shows the product on the products page and the home page', function () {
    $cat = category();
    $payload = ['product_category_id' => $cat->id, 'sku' => 'MG-2', 'name' => ['en' => 'Simple Oil'], 'status' => 'published', 'is_featured' => '1',
        'features_text' => "Good protection\nLong life", 'benefits_text' => 'Saves fuel', 'applications_text' => 'Trucks',
        'packaging_text' => '1 L, 4 L, 208 L', 'specs_text' => "Flash point: 220 °C\nPour point: -33 °C", 'specs_verified' => '1',
        'approvals_text' => 'API CK-4', 'approvals_verified' => '1'];

    $this->actingAs(staff('product-manager'))->post('/admin/products', $payload)->assertRedirect(route('admin.products.index'));

    $p = Product::where('sku', 'MG-2')->first();
    expect($p->features()->count())->toBe(4)->and($p->packaging()->count())->toBe(3)->and($p->specifications()->where('is_verified', true)->count())->toBe(2)
        ->and($p->approvals()->whereNotNull('verified_at')->count())->toBe(1)->and($p->status)->toBe('published');

    $this->get('/products')->assertSee('Simple Oil');
    $this->get('/')->assertSee('Simple Oil');
    $this->get("/products/{$p->slug}")->assertSee('Flash point')->assertSee('API CK-4')->assertSee('208 L');
});

it('reloads the saved lists into the edit form and rejects malformed specification lines', function () {
    $cat = category();
    $user = staff('product-manager');
    $this->actingAs($user)->post('/admin/products', ['product_category_id' => $cat->id, 'sku' => 'MG-3', 'name' => ['en' => 'Edit Oil'], 'status' => 'draft', 'packaging_text' => '4 L, 20 L', 'specs_text' => 'Flash point: 220 °C']);
    $p = Product::where('sku', 'MG-3')->first();

    $this->get("/admin/products/{$p->slug}/edit")->assertOk()->assertSee('4 L, 20 L')->assertSee('Flash point: 220 °C');
    $this->put("/admin/products/{$p->slug}", ['product_category_id' => $cat->id, 'sku' => 'MG-3', 'name' => ['en' => 'Edit Oil'], 'status' => 'draft', 'specs_text' => 'just a name with no value'])->assertSessionHasErrors('specs_text');
});
