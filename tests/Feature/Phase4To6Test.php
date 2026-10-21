<?php

use App\Console\Commands\PublishScheduledPosts;
use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\DealerApplication;
use App\Models\Inquiry;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
    Mail::fake();
});

function makePost(array $a = []): BlogPost
{
    static $n = 0; $n++;
    $author = User::factory()->create();

    return BlogPost::create(array_merge(['author_id' => $author->id, 'slug' => "post-{$n}", 'title' => ['en' => "Post {$n}"], 'body' => ['en' => '<p>Body</p>'], 'status' => 'published', 'published_at' => now()->subHour()], $a));
}

// ---- Phase 4: forms, dealers, homepage ------------------------------------------------

it('stores a valid contact inquiry and shows a success message', function () {
    $this->post('/contact', ['type' => 'quotation', 'name' => 'Rahim', 'email' => 'rahim@example.com', 'message' => 'Need 200 litres of hydraulic oil.', 'consent' => '1'])
        ->assertRedirect()->assertSessionHas('status');

    expect(Inquiry::where('email', 'rahim@example.com')->first())->not->toBeNull()->ip_hash->not->toBe('127.0.0.1');
});

it('validates the contact form and requires consent', function () {
    $this->post('/contact', ['type' => 'nope', 'name' => '', 'email' => 'bad', 'message' => 'short'])->assertSessionHasErrors(['type', 'name', 'email', 'message', 'consent']);
});

it('silently drops honeypot submissions', function () {
    $this->post('/contact', ['type' => 'contact', 'name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'Buy cheap stuff now please', 'consent' => '1', 'website' => 'http://spam'])->assertSessionHasErrors('website');
    expect(Inquiry::count())->toBe(0);
});

it('rate limits the contact form', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/contact', ['type' => 'contact']);
    }
    $this->post('/contact', ['type' => 'contact'])->assertStatus(429);
});

it('stores a dealer application and lets the applicant track it with the code only', function () {
    $res = $this->post('/become-a-dealer', ['business_name' => 'Rahim Motors', 'contact_name' => 'Rahim', 'email' => 'r@example.com', 'phone' => '01700000000', 'district' => 'Dhaka', 'business_type' => 'dealer', 'consent' => '1']);
    $res->assertRedirect('/become-a-dealer/received');

    $tracking = session('tracking');
    $app = DealerApplication::first();
    expect($app->tracking_token_hash)->not->toBe($tracking['token']);

    $this->post('/dealer-application-status', ['reference' => $tracking['reference'], 'token' => 'wrong'])->assertSessionHasErrors('reference');
    $this->post('/dealer-application-status', $tracking)->assertOk()->assertSee('Rahim Motors');
});

it('keeps unpublished dealers and private contact details off the locator', function () {
    \App\Models\Dealer::create(['slug' => 'a', 'name' => 'Public Dealer', 'district' => 'Dhaka', 'phone' => '01711111111', 'show_phone' => false, 'is_published' => true]);
    \App\Models\Dealer::create(['slug' => 'b', 'name' => 'Hidden Dealer', 'district' => 'Dhaka', 'is_published' => false]);

    $this->get('/dealers?q=Dhaka')->assertSee('Public Dealer')->assertDontSee('Hidden Dealer')->assertDontSee('01711111111');
});

it('restricts inquiry access: reps see only assigned, exports need permission', function () {
    $rep = User::factory()->create()->assignRole('sales-representative');
    $manager = User::factory()->create()->assignRole('sales-manager');
    $mine = Inquiry::create(['type' => 'contact', 'name' => 'Mine', 'email' => 'm@e.com', 'message' => 'hello there', 'assigned_to' => $rep->id]);
    $other = Inquiry::create(['type' => 'contact', 'name' => 'Other', 'email' => 'o@e.com', 'message' => 'hello there']);

    $this->actingAs($rep)->get('/admin/inquiries')->assertSee('Mine')->assertDontSee('Other');
    $this->actingAs($rep)->get("/admin/inquiries/{$other->id}")->assertForbidden();
    $this->actingAs($rep)->get('/admin/inquiries/export')->assertForbidden();
    $this->actingAs($manager)->get('/admin/inquiries/export')->assertOk();
});

it('neutralises spreadsheet formulas in CSV exports', function () {
    Inquiry::create(['type' => 'contact', 'name' => '=HYPERLINK("http://evil")', 'email' => 'x@e.com', 'message' => 'hello there']);
    $csv = $this->actingAs(User::factory()->create()->assignRole('sales-manager'))->get('/admin/inquiries/export')->streamedContent();

    expect($csv)->toContain("'=HYPERLINK");
});

it('renders only homepage sections with real content', function () {
    $this->get('/')->assertOk()->assertDontSee('Company statistics');

    \DB::table('homepage_sections')->where('key', 'introduction')->update(['content' => json_encode(['title' => ['en' => 'About our company']])]);
    $this->get('/')->assertSee('About our company');
});

it('confirms newsletter subscriptions only through a signed link', function () {
    $this->post('/newsletter', ['email' => 'a@example.com'])->assertSessionHas('newsletter_status');
    $sub = NewsletterSubscriber::first();

    $this->get("/newsletter/confirm/{$sub->id}")->assertForbidden();
    $this->get(\URL::temporarySignedRoute('newsletter.confirm', now()->addDay(), ['subscriber' => $sub->id]))->assertRedirect();
    expect($sub->fresh()->confirmed_at)->not->toBeNull();
});

// ---- Phase 5: blog ---------------------------------------------------------------------

it('publishes scheduled posts only when their time arrives', function () {
    $due = makePost(['status' => 'scheduled', 'published_at' => now()->subMinute()]);
    $later = makePost(['status' => 'scheduled', 'published_at' => now()->addDay()]);

    $this->artisan('blog:publish-scheduled')->assertSuccessful();

    expect($due->fresh()->status)->toBe('published')->and($later->fresh()->status)->toBe('scheduled');
});

it('hides drafts and scheduled posts from the public', function () {
    $draft = makePost(['status' => 'draft']);
    $this->get("/blog/{$draft->slug}")->assertNotFound();
});

it('sanitises article HTML on save', function () {
    $this->actingAs(User::factory()->create()->assignRole('content-manager'))->post('/admin/blog', [
        'title' => ['en' => 'XSS test'], 'status' => 'draft', 'body' => ['en' => '<p>Hi</p><script>alert(1)</script><img src=x onerror=alert(2)>'],
    ])->assertRedirect();

    $body = BlogPost::first()->getTranslation('body', 'en');
    expect($body)->not->toContain('<script')->not->toContain('onerror');
});

it('blocks marketing managers from publishing articles', function () {
    $this->actingAs(User::factory()->create()->assignRole('marketing-manager'))->post('/admin/blog', ['title' => ['en' => 'T'], 'status' => 'published'])->assertSessionHasErrors('status');
});

it('keeps guest comments pending until approved, and counts only approved ones', function () {
    $post = makePost();

    $this->post("/blog/{$post->slug}/comments", ['body' => 'Great article', 'guest_name' => 'Sam', 'guest_email' => 's@e.com'])->assertRedirect();
    $c = Comment::first();
    expect($c->status)->toBe('pending')->and($post->fresh()->comments_count)->toBe(0);
    $this->get("/blog/{$post->slug}")->assertDontSee('Great article');

    $this->actingAs(User::factory()->create()->assignRole('content-manager'))->put("/admin/comments/{$c->id}", ['status' => 'approved'])->assertRedirect();
    expect($post->fresh()->comments_count)->toBe(1);
    $this->get("/blog/{$post->slug}")->assertSee('Great article');
});

it('marks link-stuffed comments as spam', function () {
    $post = makePost();
    $this->post("/blog/{$post->slug}/comments", ['body' => 'http://a.com http://b.com http://c.com', 'guest_name' => 'Spam', 'guest_email' => 's@e.com']);

    expect(Comment::first()->status)->toBe('spam');
});

it('flattens deep replies to the top-level thread', function () {
    $post = makePost();
    $top = Comment::create(['blog_post_id' => $post->id, 'guest_name' => 'A', 'body' => 'top', 'status' => 'approved']);
    $reply = Comment::create(['blog_post_id' => $post->id, 'parent_id' => $top->id, 'guest_name' => 'B', 'body' => 'reply', 'status' => 'approved']);

    $this->post("/blog/{$post->slug}/comments", ['body' => 'reply to reply', 'parent_id' => $reply->id, 'guest_name' => 'C', 'guest_email' => 'c@e.com']);

    expect(Comment::where('body', 'reply to reply')->first()->parent_id)->toBe($top->id);
});

it('toggles likes once per visitor and never fakes counts', function () {
    $post = makePost();

    $this->post("/blog/{$post->slug}/like")->assertRedirect();
    expect($post->fresh()->likes_count)->toBe(1);
    $this->post("/blog/{$post->slug}/like");
    expect($post->fresh()->likes_count)->toBe(0);

    $user = User::factory()->create();
    $this->actingAs($user)->post("/blog/{$post->slug}/like");
    $this->actingAs($user)->post("/blog/{$post->slug}/like");
    $this->actingAs($user)->post("/blog/{$post->slug}/like");
    expect($post->fresh()->likes_count)->toBe(1);
});

it('allows only moderators to moderate comments', function () {
    $c = Comment::create(['blog_post_id' => makePost()->id, 'guest_name' => 'A', 'body' => 'x', 'status' => 'pending']);

    $this->actingAs(User::factory()->create()->assignRole('sales-manager'))->put("/admin/comments/{$c->id}", ['status' => 'approved'])->assertForbidden();
});

// ---- Phase 6: SEO and ads --------------------------------------------------------------

it('puts a canonical URL, hreflang alternates and JSON-LD on article pages', function () {
    $post = makePost(['title' => ['en' => 'Engine oil basics', 'bn' => 'ইঞ্জিন অয়েলের মূল কথা']]);

    $html = $this->get("/blog/{$post->slug}")->assertOk()->getContent();

    expect($html)->toContain('rel="canonical"')->toContain('hreflang="bn"')->toContain('"@type":"Article"')->toContain('"@type":"BreadcrumbList"');
    $this->get("/bn/blog/{$post->slug}")->assertOk()->assertSee('ইঞ্জিন অয়েলের মূল কথা');
});

it('builds a sitemap from published, indexable content only', function () {
    $live = makePost(['slug' => 'live-post']);
    makePost(['slug' => 'draft-post', 'status' => 'draft']);
    makePost(['slug' => 'noindex-post', 'noindex' => true]);
    Page::create(['slug' => 'privacy', 'title' => ['en' => 'Privacy'], 'status' => 'published']);

    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain('live-post')->toContain('/privacy')->not->toContain('draft-post')->not->toContain('noindex-post');
});

it('serves robots.txt and can block the whole site on staging', function () {
    $this->get('/robots.txt')->assertSee('Disallow: /admin')->assertSee('Sitemap:');

    Settings::set('seo', 'noindex_site', '1', 'bool');
    $this->get('/robots.txt')->assertSee('Disallow: /');
});

it('never renders ads or ads.txt without real configuration', function () {
    $this->get('/')->assertDontSee('adsbygoogle');
    $this->get('/ads.txt')->assertNotFound();

    config(['motogrip.features.adsense' => true]);
    Settings::set('ads', 'enabled', '1', 'bool');
    $this->get('/ads.txt')->assertNotFound();   // still no publisher ID
});

it('follows admin-managed redirects and 404s otherwise', function () {
    \DB::table('redirects')->insert(['from_path' => '/old-oil', 'to_url' => '/products', 'status_code' => 301, 'is_active' => true, 'hits' => 0, 'created_at' => now(), 'updated_at' => now()]);

    $this->get('/old-oil/deep')->assertNotFound();
    $this->get('/old-oil')->assertRedirect('/products')->assertStatus(301);
    $this->get('/definitely/not/here')->assertNotFound();
});

it('limits settings and SEO tools to permitted roles', function () {
    $this->actingAs(User::factory()->create()->assignRole('sales-manager'))->get('/admin/seo')->assertForbidden();
    $this->actingAs(User::factory()->create()->assignRole('content-manager'))->get('/admin/seo')->assertOk();
});

it('shows a cookie consent banner and loads no analytics tags before consent', function () {
    Settings::set('integrations', 'ga_measurement_id', 'G-TEST1234');
    $html = $this->get('/')->getContent();

    expect($html)->toContain('role="region" aria-label="Cookies"')->not->toContain('googletagmanager.com');
});
