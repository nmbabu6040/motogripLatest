<?php

use App\Models\Page;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function mfUser(string $role = 'content-manager'): User { return User::factory()->create()->assignRole($role); }

function mfHeader(string $html): string
{
    preg_match('/aria-label="Main".*?<\/nav>/s', $html, $m);

    return $m[0] ?? '';
}

function mfFooter(string $html): string
{
    preg_match('/<footer.*?<\/footer>/s', $html, $m);

    return $m[0] ?? '';
}

function mfAdd(array $over = []): void
{
    test()->post('/admin/menus', array_merge(['location' => 'header', 'label' => ['en' => 'Home'], 'target' => 'route:home', 'is_active' => '1'], $over))->assertRedirect();
}

it('opens the menu manager with both menus on automatic at first', function () {
    $this->actingAs(mfUser())->get('/admin/menus')->assertOk()->assertSee('Header menu')->assertSee('Footer menu')->assertSee('স্বয়ংক্রিয়');
});

it('adds links to the header menu and then shows only those, in that order', function () {
    $this->actingAs(mfUser());
    mfAdd(['label' => ['en' => 'Start']]);
    mfAdd(['label' => ['en' => 'Our products'], 'target' => 'route:catalog.index']);
    mfAdd(['label' => ['en' => 'Warranty'], 'target' => 'custom', 'custom_url' => '/warranty']);

    $header = mfHeader($this->get('/')->getContent());
    expect($header)->toContain('>Start<')->toContain('>Our products<')->toContain('>Warranty<')->toContain('/warranty')->not->toContain('>Dealers<')->not->toContain('>Blog<');
    expect(strpos($header, '>Start<'))->toBeLessThan(strpos($header, '>Our products<'))->and(strpos($header, '>Our products<'))->toBeLessThan(strpos($header, '>Warranty<'));
});

it('shows Bengali names on the Bengali site and keeps the site\'s own paths inside /bn', function () {
    $this->actingAs(mfUser());
    mfAdd(['label' => ['en' => 'Warranty', 'bn' => 'ওয়ারেন্টি'], 'target' => 'custom', 'custom_url' => '/warranty']);

    expect(mfHeader($this->get('/bn')->getContent()))->toContain('ওয়ারেন্টি')->toContain('/bn/warranty');
    expect(mfHeader($this->get('/')->getContent()))->toContain('Warranty')->not->toContain('/bn/warranty');
});

it('links to a page you made and to another website in a new tab', function () {
    Page::create(['slug' => 'about-us', 'title' => ['en' => 'About Us'], 'status' => 'published']);
    $this->actingAs(mfUser());
    mfAdd(['label' => ['en' => 'About'], 'target' => 'page:about-us']);
    mfAdd(['label' => ['en' => 'Partner'], 'target' => 'custom', 'custom_url' => 'https://example.com/shop', 'new_tab' => '1']);

    $header = mfHeader($this->get('/')->getContent());
    expect($header)->toContain('/about-us')->toContain('https://example.com/shop')->toContain('target="_blank"');
});

it('rejects unsafe or unknown menu links', function () {
    $this->actingAs(mfUser());
    $base = ['location' => 'header', 'label' => ['en' => 'X']];

    $this->post('/admin/menus', $base + ['target' => 'custom', 'custom_url' => 'javascript:alert(1)'])->assertSessionHasErrors('custom_url');
    $this->post('/admin/menus', $base + ['target' => 'custom', 'custom_url' => 'http://insecure.example'])->assertSessionHasErrors('custom_url');
    $this->post('/admin/menus', $base + ['target' => 'custom', 'custom_url' => '//evil.example'])->assertSessionHasErrors('custom_url');
    $this->post('/admin/menus', $base + ['target' => 'route:does.not.exist'])->assertSessionHasErrors('target');
    $this->post('/admin/menus', $base + ['target' => 'page:missing'])->assertSessionHasErrors('target');
    $this->post('/admin/menus', ['location' => 'sidebar', 'label' => ['en' => 'X'], 'target' => 'route:home'])->assertSessionHasErrors('location');
    $this->post('/admin/menus', ['location' => 'header', 'label' => ['en' => ''], 'target' => 'route:home'])->assertSessionHasErrors('label.en');
    expect(DB::table('menu_items')->count())->toBe(0);
});

it('hides, shows, reorders, edits and removes links', function () {
    $this->actingAs(mfUser());
    mfAdd(['label' => ['en' => 'Alpha']]);
    mfAdd(['label' => ['en' => 'Beta'], 'target' => 'route:blog.index']);
    [$a, $b] = DB::table('menu_items')->orderBy('id')->pluck('id')->all();

    $this->post("/admin/menus/{$a}/toggle");
    expect(mfHeader($this->get('/')->getContent()))->not->toContain('>Alpha<')->toContain('>Beta<');
    $this->post("/admin/menus/{$a}/toggle");

    $this->post("/admin/menus/{$b}/move", ['direction' => 'up']);
    $h = mfHeader($this->get('/')->getContent());
    expect(strpos($h, '>Beta<'))->toBeLessThan(strpos($h, '>Alpha<'));

    $this->put("/admin/menus/{$a}", ['location' => 'header', 'label' => ['en' => 'Alpha renamed'], 'target' => 'route:home'])->assertRedirect();
    expect(mfHeader($this->get('/')->getContent()))->toContain('>Alpha renamed<');

    $this->delete("/admin/menus/{$b}");
    expect(mfHeader($this->get('/')->getContent()))->not->toContain('>Beta<');
});

it('goes back to the automatic menu when asked', function () {
    $this->actingAs(mfUser());
    mfAdd(['label' => ['en' => 'Only one']]);
    expect(mfHeader($this->get('/')->getContent()))->not->toContain('>Dealers<');

    $this->post('/admin/menus/reset/header')->assertRedirect();
    expect(DB::table('menu_items')->count())->toBe(0)->and(mfHeader($this->get('/')->getContent()))->toContain('Dealers');
});

it('fills a menu with the standard links so they can be edited', function () {
    $this->actingAs(mfUser())->post('/admin/menus/defaults/header')->assertRedirect();

    expect(DB::table('menu_items')->count())->toBeGreaterThanOrEqual(6)
        ->and(mfHeader($this->get('/')->getContent()))->toContain('Products')->toContain('Contact');
    $this->post('/admin/menus/defaults/footer');
    $this->post('/admin/menus/defaults/nowhere')->assertNotFound();
});

it('lets the footer menu replace the "Company" links', function () {
    $this->actingAs(mfUser());
    expect(mfFooter($this->get('/')->getContent()))->toContain('>Dealers<');

    mfAdd(['location' => 'footer', 'label' => ['en' => 'Warranty'], 'target' => 'custom', 'custom_url' => '/warranty']);
    $footer = mfFooter($this->get('/')->getContent());
    expect($footer)->toContain('>Warranty<')->not->toContain('>Dealers<');
});

it('limits the menu manager to people who may edit the homepage', function () {
    $this->actingAs(mfUser('sales-manager'))->get('/admin/menus')->assertForbidden();
    $this->post('/admin/menus', ['location' => 'header', 'label' => ['en' => 'X'], 'target' => 'route:home'])->assertForbidden();
    $this->post('/admin/menus/reset/header')->assertForbidden();
    $this->actingAs(mfUser('content-manager'))->get('/admin/menus')->assertOk();
    expect(DB::table('menu_items')->count())->toBe(0);
});

it('shows the About text, round social icons and side-by-side contact details in the footer', function () {
    Settings::set('footer', 'about', 'We make good lubricants.');
    Settings::set('footer', 'about_bn', 'আমরা ভালো লুব্রিকেন্ট বানাই।');
    Settings::set('social', 'facebook', 'https://facebook.com/motogrip');
    Settings::set('contact', 'phone', '01700000000');
    Settings::set('contact', 'address', 'Dhaka');

    $en = mfFooter($this->get('/')->getContent());
    expect($en)->toContain('We make good lubricants.')->toContain('aria-label="Facebook"')->toContain('https://facebook.com/motogrip')->toContain('grid-cols-[5.5rem_1fr]')->toContain('01700000000');
    expect(strpos($en, 'We make good lubricants.'))->toBeLessThan(strpos($en, 'aria-label="Facebook"'));
    expect(mfFooter($this->get('/bn')->getContent()))->toContain('আমরা ভালো লুব্রিকেন্ট বানাই।');
});

it('falls back to the English About text when there is no Bengali one', function () {
    Settings::set('footer', 'about', 'English only text.');

    expect(mfFooter($this->get('/bn')->getContent()))->toContain('English only text.');
});

it('offers a back-to-top button on public pages', function () {
    $this->get('/')->assertOk()->assertSee('aria-label="Back to top"', false);
    $this->get('/bn')->assertOk()->assertSee('ওপরে ফিরুন');
});

it('has settings for the footer text and the live chat', function () {
    $this->actingAs(User::factory()->create()->assignRole('super-admin'))->get('/admin/settings')->assertOk()->assertSee('under the footer logo')->assertSee('Show the live chat button on the website');
});

it('shows the Menus link in the admin sidebar to people who may use it', function () {
    $this->actingAs(mfUser('content-manager'))->get('/admin')->assertOk()->assertSee('/admin/menus', false);
    $this->actingAs(mfUser('sales-manager'))->get('/admin')->assertOk()->assertDontSee('/admin/menus', false);
});
