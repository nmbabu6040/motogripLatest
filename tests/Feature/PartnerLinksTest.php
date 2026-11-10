<?php

use App\Models\PartnerLink;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function partnerUser(string $role = 'marketing-manager'): User
{
    return User::factory()->create()->assignRole($role);
}

function makeLink(array $a = []): PartnerLink
{
    static $n = 0; $n++;

    return PartnerLink::create(array_merge(['store' => 'amazon', 'title' => ['en' => "Gadget {$n}"], 'url' => "https://amzn.to/abc{$n}", 'is_published' => true, 'sort_order' => 0], $a));
}

it('adds an Amazon and an AliExpress link from the admin page', function () {
    $user = partnerUser();

    $this->actingAs($user)->post('/admin/partner-links', ['store' => 'amazon', 'title' => ['en' => 'Oil filter wrench'], 'url' => 'https://www.amazon.com/dp/B000TEST?tag=mysite-20', 'is_published' => '1'])->assertRedirect(route('admin.partner-links.index'));
    $this->post('/admin/partner-links', ['store' => 'aliexpress', 'title' => ['en' => 'Funnel set'], 'url' => 'https://s.click.aliexpress.com/e/_abc123', 'is_published' => '1'])->assertRedirect();

    expect(PartnerLink::count())->toBe(2)->and(PartnerLink::where('store', 'amazon')->first()->url)->toContain('tag=mysite-20');
});

it('rejects links that do not belong to the chosen store or are not https', function () {
    $this->actingAs(partnerUser());

    $this->post('/admin/partner-links', ['store' => 'amazon', 'title' => ['en' => 'X'], 'url' => 'https://evil.example.com/amazon.com'])->assertSessionHasErrors('url');
    $this->post('/admin/partner-links', ['store' => 'aliexpress', 'title' => ['en' => 'X'], 'url' => 'https://www.amazon.com/dp/B1'])->assertSessionHasErrors('url');
    $this->post('/admin/partner-links', ['store' => 'amazon', 'title' => ['en' => 'X'], 'url' => 'http://www.amazon.com/dp/B1'])->assertSessionHasErrors('url');
    $this->post('/admin/partner-links', ['store' => 'amazon', 'title' => ['en' => ''], 'url' => 'not a url'])->assertSessionHasErrors(['title.en', 'url']);
    expect(PartnerLink::count())->toBe(0);
});

it('hides the recommended page until there is a published link', function () {
    $this->get('/recommended')->assertNotFound();

    makeLink(['is_published' => false]);
    $this->get('/recommended')->assertNotFound();

    makeLink(['title' => ['en' => 'Visible Gadget']]);
    $this->get('/recommended')->assertOk()->assertSee('Visible Gadget');
});

it('links straight to the store with sponsored attributes and no redirect through this site', function () {
    $link = makeLink(['url' => 'https://www.amazon.com/dp/B000TEST?tag=mysite-20']);

    $html = $this->get('/recommended')->assertOk()->getContent();
    expect($html)->toContain('href="https://www.amazon.com/dp/B000TEST?tag=mysite-20"')->toContain('rel="sponsored nofollow noopener"')->toContain('data-partner-click="'.route('partners.click', $link).'"')->not->toContain('/go/');
});

it('shows the disclosure, and the Amazon statement only when an Amazon link is on the page', function () {
    makeLink(['store' => 'aliexpress', 'url' => 'https://s.click.aliexpress.com/e/_x1']);
    $this->get('/recommended')->assertSee('affiliate links')->assertDontSee('Amazon Associate');

    makeLink(['store' => 'amazon']);
    $this->get('/recommended')->assertSee('As an Amazon Associate I earn from qualifying purchases.');

    Settings::set('partners', 'amazon_statement', 'Custom statement from my agreement.');
    $this->get('/recommended')->assertSee('Custom statement from my agreement.');
});

it('counts clicks per day without storing anything about the visitor', function () {
    $link = makeLink();

    $this->postJson(route('partners.click', $link))->assertNoContent();
    $this->postJson(route('partners.click', $link))->assertNoContent();

    $row = DB::table('partner_link_clicks')->where('partner_link_id', $link->id)->first();
    expect((int) $row->clicks)->toBe(2)->and(array_keys((array) $row))->toBe(['partner_link_id', 'clicked_on', 'clicks']);
});

it('does not count clicks on hidden links', function () {
    $link = makeLink(['is_published' => false]);

    $this->postJson(route('partners.click', $link))->assertNotFound();
    expect(DB::table('partner_link_clicks')->count())->toBe(0);
});

it('shows a footer link only while recommended links exist', function () {
    $this->get('/')->assertDontSee('/recommended');

    $link = makeLink();
    $this->actingAs(partnerUser())->put("/admin/partner-links/{$link->id}", ['store' => 'amazon', 'title' => ['en' => 'G'], 'url' => $link->url, 'is_published' => '1'])->assertRedirect();
    auth()->logout();
    $this->get('/')->assertSee('/recommended');
});

it('uploads and removes the link image and deletes the file', function () {
    Storage::fake('public');
    $user = partnerUser();
    $base = ['store' => 'amazon', 'title' => ['en' => 'With image'], 'url' => 'https://amzn.to/img1', 'is_published' => '1'];

    $this->actingAs($user)->post('/admin/partner-links', $base + ['image' => UploadedFile::fake()->image('p.jpg', 600, 600)])->assertRedirect();
    $link = PartnerLink::first();
    expect($link->image)->toStartWith('/storage/partners/');
    Storage::disk('public')->assertExists(Str::after($link->image, '/storage/'));

    $this->put("/admin/partner-links/{$link->id}", $base + ['remove_image' => '1'])->assertRedirect();
    expect($link->fresh()->image)->toBeNull();
    Storage::disk('public')->assertMissing(Str::after($link->image, '/storage/'));

    $this->post('/admin/partner-links', $base + ['image' => UploadedFile::fake()->create('x.php', 5, 'application/x-php')])->assertSessionHasErrors('image');
});

it('limits link management to marketing and content roles', function () {
    $this->actingAs(partnerUser('sales-manager'))->get('/admin/partner-links')->assertForbidden();
    $this->post('/admin/partner-links', ['store' => 'amazon', 'title' => ['en' => 'X'], 'url' => 'https://amzn.to/x'])->assertForbidden();
    $this->actingAs(partnerUser('content-manager'))->get('/admin/partner-links')->assertOk();
});

it('edits and deletes a link', function () {
    $user = partnerUser();
    $link = makeLink();

    $this->actingAs($user)->put("/admin/partner-links/{$link->id}", ['store' => 'amazon', 'title' => ['en' => 'Renamed'], 'url' => 'https://amzn.to/new', 'is_published' => '1', 'sort_order' => 3])->assertRedirect();
    expect($link->fresh()->getTranslation('title', 'en'))->toBe('Renamed')->and($link->fresh()->sort_order)->toBe(3);

    $this->delete("/admin/partner-links/{$link->id}")->assertRedirect();
    expect(PartnerLink::count())->toBe(0);
});

it('shows clicks per link in the admin list and on the dashboard chart', function () {
    $link = makeLink(['title' => ['en' => 'Popular Gadget']]);
    $this->postJson(route('partners.click', $link));
    $this->postJson(route('partners.click', $link));

    $user = partnerUser();
    $this->actingAs($user)->get('/admin/partner-links')->assertOk()->assertSee('Popular Gadget');
    $this->get('/admin')->assertOk()->assertSee('Clicks on recommended links')->assertSee('Top recommended links');
    expect(array_sum($this->getJson('/admin/charts/partner_clicks')->assertOk()->json('datasets.0.data')))->toBe(2);
});

it('explains the Amazon and AliExpress rules on the admin page', function () {
    $this->actingAs(partnerUser())->get('/admin/partner-links')->assertOk()->assertSee('/recommended')->assertSee('Amazon');
});
