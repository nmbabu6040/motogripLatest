<?php

use App\Events\LeadCreated;
use App\Models\Affiliate;
use App\Models\AffiliateConversion;
use App\Models\Inquiry;
use App\Models\ReferralClick;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
    Mail::fake();
});

function approvedAffiliate(): Affiliate
{
    $user = User::factory()->create()->assignRole('affiliate');

    return Affiliate::create(['user_id' => $user->id, 'code' => 'ABCDEF1234', 'status' => 'approved', 'terms_accepted_at' => now()]);
}

// ---- Phase 7: affiliates, reports, exports ---------------------------------------------

it('keeps the affiliate module switched off by default', function () {
    $this->actingAs(User::factory()->create()->assignRole('super-admin'))->get('/admin/affiliates')->assertNotFound();
    $this->get('/?ref=ABCDEF1234');
    expect(ReferralClick::count())->toBe(0);
});

it('records a click, stores only a token hash, and attributes a later inquiry', function () {
    config(['motogrip.features.affiliate' => true]);
    $aff = approvedAffiliate();

    $res = $this->get('/?ref=ABCDEF1234');
    $cookie = collect($res->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === 'mg_ref');
    expect($cookie)->not->toBeNull()->and($cookie->isHttpOnly())->toBeTrue();
    // The cookie is encrypted in transit; decrypt it to compare with the stored hash.
    $plain = \Illuminate\Cookie\CookieValuePrefix::remove(app('encrypter')->decrypt($cookie->getValue(), false));
    $click = ReferralClick::first();
    expect($click->token_hash)->toBe(hash('sha256', $plain))->and($click->token_hash)->not->toBe($plain);

    $this->withCookie('mg_ref', $plain)->post('/contact', ['type' => 'contact', 'name' => 'Lead', 'email' => 'lead@example.com', 'message' => 'Interested in oils', 'consent' => '1']);

    expect(AffiliateConversion::where('affiliate_id', $aff->id)->where('status', 'pending')->count())->toBe(1);
});

it('ignores unknown codes, suspended affiliates and self-referrals', function () {
    config(['motogrip.features.affiliate' => true]);
    $aff = approvedAffiliate();

    $this->get('/?ref=NOSUCHCODE1');
    expect(ReferralClick::count())->toBe(0);

    $aff->update(['status' => 'suspended']);
    $this->get('/?ref=ABCDEF1234');
    expect(ReferralClick::count())->toBe(0);

    $aff->update(['status' => 'approved']);
    $this->get('/?ref=ABCDEF1234');
    $token = 'selftoken';
    ReferralClick::first()->update(['token_hash' => hash('sha256', $token)]);
    $inquiry = Inquiry::create(['type' => 'contact', 'name' => 'Self', 'email' => $aff->user->email, 'message' => 'hello there']);
    request()->cookies->set('mg_ref', $token);
    LeadCreated::dispatch($inquiry, 'inquiry');

    expect(AffiliateConversion::count())->toBe(0);
});

it('creates only one conversion per lead', function () {
    config(['motogrip.features.affiliate' => true]);
    $aff = approvedAffiliate();
    $click = ReferralClick::create(['affiliate_id' => $aff->id, 'token_hash' => hash('sha256', 'tok'), 'landing_path' => '/', 'clicked_at' => now()]);
    $inquiry = Inquiry::create(['type' => 'contact', 'name' => 'L', 'email' => 'l@e.com', 'message' => 'hello there']);

    foreach (range(1, 3) as $i) {
        request()->cookies->set('mg_ref', 'tok');
        app(\App\Listeners\AttributeLeadToAffiliate::class)->handle(new LeadCreated($inquiry, 'inquiry'));
    }

    expect(AffiliateConversion::count())->toBe(1);
});

it('respects the attribution window', function () {
    config(['motogrip.features.affiliate' => true]);
    $aff = approvedAffiliate();
    ReferralClick::create(['affiliate_id' => $aff->id, 'token_hash' => hash('sha256', 'old'), 'landing_path' => '/', 'clicked_at' => now()->subDays(40)]);
    $inquiry = Inquiry::create(['type' => 'contact', 'name' => 'L', 'email' => 'l@e.com', 'message' => 'hello there']);

    request()->cookies->set('mg_ref', 'old');
    app(\App\Listeners\AttributeLeadToAffiliate::class)->handle(new LeadCreated($inquiry, 'inquiry'));

    expect(AffiliateConversion::count())->toBe(0);
});

it('returns report data only to users with analytics permission and never invents numbers', function () {
    $this->actingAs(User::factory()->create()->assignRole('sales-representative'))->getJson('/admin/reports/data?metric=inquiries')->assertForbidden();

    $json = $this->actingAs(User::factory()->create()->assignRole('sales-manager'))->getJson('/admin/reports/data?metric=product_views')->assertOk()->json();
    expect($json['total'])->toBe(0)->and($json['source'])->toBe('First-party database');
});

it('restricts report exports', function () {
    $this->actingAs(User::factory()->create()->assignRole('content-manager'))->get('/admin/reports/export?metric=inquiries')->assertForbidden();
    $this->actingAs(User::factory()->create()->assignRole('sales-manager'))->get('/admin/reports/export?metric=inquiries')->assertOk();
});

// ---- Phase 8: backups, security, privacy -----------------------------------------------

it('forbids the backup area without backups.create', function () {
    $this->actingAs(User::factory()->create()->assignRole('administrator'))->get('/admin/backups')->assertForbidden();
    $this->actingAs(User::factory()->create()->assignRole('super-admin'))->get('/admin/backups')->assertOk();
});

it('refuses to back up without an archive password and records the failure', function () {
    $this->actingAs(User::factory()->create()->assignRole('super-admin'))->post('/admin/backups', ['type' => 'database'])->assertSessionHasErrors('type');
    expect(\DB::table('backup_records')->count())->toBe(0);

    $id = app(\App\Services\BackupService::class)->run('database');
    expect(\DB::table('backup_records')->find($id)->status)->toBe('failed');
});

it('refuses to restore unverified backups or without typing the database name', function () {
    $id = \DB::table('backup_records')->insertGetId(['type' => 'database', 'disk' => 'backups', 'status' => 'succeeded', 'created_at' => now(), 'updated_at' => now()]);

    $this->artisan('motogrip:restore', ['id' => $id, '--confirm-db' => 'x'])->assertFailed();

    \DB::table('backup_records')->where('id', $id)->update(['verified_at' => now()]);
    $this->artisan('motogrip:restore', ['id' => $id, '--confirm-db' => 'wrong-name'])->assertFailed();
});

it('does not verify a backup whose file is missing', function () {
    $id = \DB::table('backup_records')->insertGetId(['type' => 'database', 'disk' => 'backups', 'status' => 'failed', 'created_at' => now(), 'updated_at' => now()]);

    [$ok] = app(\App\Services\BackupService::class)->verify($id);
    expect($ok)->toBeFalse();
});

it('sends security headers on public and admin pages', function () {
    $res = $this->get('/');
    $res->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('Referrer-Policy');
    expect($res->headers->get('Content-Security-Policy'))->toContain("object-src 'none'");
});

it('exposes a minimal health check', function () {
    $this->getJson('/health')->assertOk()->assertJson(['status' => 'ok'])->assertJsonMissing(['version']);
});

it('applies retention rules and erases a person on request', function () {
    $old = Inquiry::create(['type' => 'contact', 'name' => 'Old', 'email' => 'old@e.com', 'message' => 'hello there', 'status' => 'closed']);
    $old->forceFill(['created_at' => now()->subMonths(30)])->save();
    Inquiry::create(['type' => 'contact', 'name' => 'Recent', 'email' => 'recent@e.com', 'message' => 'hello there', 'status' => 'closed', 'ip_hash' => 'h']);

    $this->artisan('motogrip:purge-old-data')->assertSuccessful();
    expect(Inquiry::where('name', 'Old')->exists())->toBeFalse()->and(Inquiry::where('name', 'Recent')->exists())->toBeTrue();

    $this->artisan('motogrip:erase-person', ['email' => 'recent@e.com'])->expectsConfirmation('Permanently erase personal data for recent@e.com?', 'yes')->assertSuccessful();
    expect(Inquiry::count())->toBe(0);
});

it('rejects public registration and unauthenticated admin writes', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/admin/products', [])->assertRedirect('/login');
    $this->post('/admin/settings/maintenance')->assertRedirect('/login');
});

it('stops non-super-admins from toggling maintenance mode', function () {
    $this->actingAs(User::factory()->create()->assignRole('administrator'))->post('/admin/settings/maintenance', ['confirm' => '1'])->assertForbidden();
});
