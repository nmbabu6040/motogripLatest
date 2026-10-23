<?php

use App\Models\Page;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function pageEditor(string $role = 'content-manager'): User
{
    return User::factory()->create()->assignRole($role);
}

function mainMenu(string $html): string
{
    preg_match('/aria-label="Main".*?<\/nav>/s', $html, $m);

    return $m[0] ?? '';
}

it('creates a page with an image and layout, and shows it on its own address', function () {
    Storage::fake('public');

    $this->actingAs(pageEditor())->post('/admin/pages', [
        'title' => ['en' => 'About Us'], 'slug' => 'about-us', 'status' => 'published', 'layout' => 'image_right',
        'body' => ['en' => '<p>We make lubricants.</p>'], 'image' => UploadedFile::fake()->image('team.jpg', 900, 600),
    ])->assertRedirect(route('admin.pages.index'));

    $page = Page::where('slug', 'about-us')->first();
    expect($page->image)->toStartWith('/storage/pages/')->and($page->layout)->toBe('image_right');
    Storage::disk('public')->assertExists(Str::after($page->image, '/storage/'));

    $html = $this->get('/about-us')->assertOk()->assertSee('We make lubricants.')->assertSee($page->image)->getContent();
    expect($html)->toContain('data-layout="image_right"');
});

it('falls back to a plain layout when a page has no image', function () {
    $this->actingAs(pageEditor())->post('/admin/pages', ['title' => ['en' => 'Terms'], 'status' => 'published', 'layout' => 'image_right', 'body' => ['en' => '<p>Terms text.</p>']]);

    $html = $this->get('/terms')->assertOk()->assertSee('Terms text.')->getContent();
    expect($html)->toContain('data-layout="plain"')->not->toContain('data-layout="image_right"');
});

it('supports the wide banner layout', function () {
    Storage::fake('public');
    $this->actingAs(pageEditor())->post('/admin/pages', ['title' => ['en' => 'Our Plant'], 'status' => 'published', 'layout' => 'banner', 'body' => ['en' => '<p>Plant text.</p>'], 'image' => UploadedFile::fake()->image('plant.png', 1200, 500)]);

    $this->get('/our-plant')->assertOk()->assertSee('max-h-[30rem]', false);
});

it('replaces and removes the page image and deletes the old file', function () {
    Storage::fake('public');
    $user = pageEditor();
    $this->actingAs($user)->post('/admin/pages', ['title' => ['en' => 'Story'], 'status' => 'published', 'layout' => 'banner', 'image' => UploadedFile::fake()->image('a.jpg')]);
    $page = Page::where('slug', 'story')->first();
    $first = $page->image;

    $this->actingAs($user)->put("/admin/pages/{$page->slug}", ['title' => ['en' => 'Story'], 'status' => 'published', 'layout' => 'banner', 'image' => UploadedFile::fake()->image('b.png')]);
    $second = $page->fresh()->image;
    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing(Str::after($first, '/storage/'));

    $this->actingAs($user)->put("/admin/pages/{$page->slug}", ['title' => ['en' => 'Story'], 'status' => 'published', 'layout' => 'banner', 'remove_image' => '1']);
    expect($page->fresh()->image)->toBeNull();
    Storage::disk('public')->assertMissing(Str::after($second, '/storage/'));
});

it('rejects non-images and unknown layouts', function () {
    Storage::fake('public');
    $user = pageEditor();

    $this->actingAs($user)->post('/admin/pages', ['title' => ['en' => 'X'], 'status' => 'published', 'layout' => 'plain', 'image' => UploadedFile::fake()->create('evil.php', 5, 'application/x-php')])->assertSessionHasErrors('image');
    $this->actingAs($user)->post('/admin/pages', ['title' => ['en' => 'X'], 'status' => 'published', 'layout' => 'weird'])->assertSessionHasErrors('layout');
});

it('removes scripts from page text on save', function () {
    $this->actingAs(pageEditor())->post('/admin/pages', ['title' => ['en' => 'Safe'], 'status' => 'published', 'layout' => 'plain', 'body' => ['en' => '<p>Hi</p><script>alert(1)</script>']]);

    expect(Page::where('slug', 'safe')->first()->getTranslation('body', 'en'))->not->toContain('<script');
});

it('puts About Us and pages marked for the menu into the main menu, and nothing else', function () {
    $user = pageEditor();
    $this->actingAs($user)->post('/admin/pages', ['title' => ['en' => 'About Us'], 'slug' => 'about-us', 'status' => 'published', 'layout' => 'plain']);
    $this->actingAs($user)->post('/admin/pages', ['title' => ['en' => 'Warranty'], 'status' => 'published', 'layout' => 'plain', 'show_in_menu' => '1']);
    $this->actingAs($user)->post('/admin/pages', ['title' => ['en' => 'Terms'], 'status' => 'published', 'layout' => 'plain']);
    $this->actingAs($user)->post('/admin/pages', ['title' => ['en' => 'Secret'], 'status' => 'draft', 'layout' => 'plain', 'show_in_menu' => '1']);

    $menu = mainMenu($this->get('/')->getContent());
    expect($menu)->toContain('/about-us')->toContain('/warranty')->not->toContain('/terms')->not->toContain('/secret');
});

it('removes a page from the menu when it is deleted or unpublished', function () {
    $user = pageEditor();
    $this->actingAs($user)->post('/admin/pages', ['title' => ['en' => 'About Us'], 'slug' => 'about-us', 'status' => 'published', 'layout' => 'plain']);
    expect(mainMenu($this->get('/')->getContent()))->toContain('/about-us');

    $this->actingAs($user)->put('/admin/pages/about-us', ['title' => ['en' => 'About Us'], 'status' => 'draft', 'layout' => 'plain']);
    expect(mainMenu($this->get('/')->getContent()))->not->toContain('/about-us');
});

it('shows an edit button on the page only to people who may edit pages', function () {
    Page::create(['slug' => 'privacy', 'title' => ['en' => 'Privacy'], 'status' => 'published']);

    $this->get('/privacy')->assertOk()->assertDontSee('Edit this page');
    $this->actingAs(pageEditor())->get('/privacy')->assertOk()->assertSee('Edit this page');
    $this->actingAs(pageEditor('sales-manager'))->get('/privacy')->assertOk()->assertDontSee('Edit this page');
});

it('limits page management to permitted roles and keeps drafts private', function () {
    $this->actingAs(pageEditor('sales-manager'))->get('/admin/pages')->assertForbidden();
    $this->actingAs(pageEditor('sales-manager'))->post('/admin/pages', ['title' => ['en' => 'X'], 'status' => 'published', 'layout' => 'plain'])->assertForbidden();

    Page::create(['slug' => 'draft-page', 'title' => ['en' => 'Draft'], 'status' => 'draft']);
    auth()->logout();
    $this->get('/draft-page')->assertNotFound();
});

it('shows the Pages screen with the guidance text and each page layout', function () {
    Page::create(['slug' => 'about-us', 'title' => ['en' => 'About Us'], 'status' => 'published', 'layout' => 'banner']);

    $this->actingAs(pageEditor())->get('/admin/pages')->assertOk()->assertSee('about-us')->assertSee('Wide image on top');
});

it('gives pages the same heading bar as the other pages: a small label, the title and a short line', function () {
    $this->actingAs(pageEditor())->post('/admin/pages', ['title' => ['en' => 'About Us'], 'slug' => 'about-us', 'status' => 'published', 'layout' => 'plain', 'seo_description' => ['en' => 'Who we are and what we do.']]);

    $html = $this->get('/about-us')->assertOk()->assertSee('Who we are and what we do.')->getContent();
    expect($html)->toContain('tracking-[0.2em]')->toContain('from-navy-deep');
});

it('saves the Bengali short line too', function () {
    $this->actingAs(pageEditor())->post('/admin/pages', ['title' => ['en' => 'About Us', 'bn' => 'আমাদের সম্পর্কে'], 'slug' => 'about-us', 'status' => 'published', 'layout' => 'plain', 'seo_description' => ['en' => 'English line.', 'bn' => 'বাংলা লাইন।']]);

    $this->get('/bn/about-us')->assertOk()->assertSee('বাংলা লাইন।');
});

it('shows a page intro line and the site name in the page banner, like the other pages', function () {
    $this->actingAs(pageEditor())->post('/admin/pages', ['title' => ['en' => 'About Us'], 'subtitle' => ['en' => 'Who we are and what we do'], 'slug' => 'about-us', 'status' => 'published', 'layout' => 'plain']);

    $this->get('/about-us')->assertOk()->assertSee('Who we are and what we do');
    expect(Page::where('slug', 'about-us')->first()->getTranslation('subtitle', 'en'))->toBe('Who we are and what we do');
});

it('shows the intro field in the page editor and keeps it on edit', function () {
    $user = pageEditor();
    $this->actingAs($user)->post('/admin/pages', ['title' => ['en' => 'Story'], 'subtitle' => ['en' => 'Our story'], 'status' => 'published', 'layout' => 'plain']);

    $this->get('/admin/pages/story/edit')->assertOk()->assertSee('Short intro under the title')->assertSee('Our story');
});

it('gives every page banner the same minimum height', function () {
    $this->get('/contact')->assertOk()->assertSee('min-h-[12rem]', false);
});
