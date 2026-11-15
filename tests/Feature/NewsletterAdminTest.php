<?php

use App\Models\NewsletterSubscriber;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function nlUser(string $role = 'sales-manager'): User { return User::factory()->create()->assignRole($role); }
function nlPending(string $email = 'pending@example.com'): NewsletterSubscriber { return NewsletterSubscriber::create(['email' => $email, 'locale' => 'en', 'token_hash' => hash('sha256', $email)]); }

it('shows pending, confirmed and unsubscribed subscribers with counts and filters', function () {
    nlPending('wait@example.com');
    NewsletterSubscriber::create(['email' => 'ok@example.com', 'locale' => 'en', 'token_hash' => 'x', 'confirmed_at' => now()]);
    NewsletterSubscriber::create(['email' => 'gone@example.com', 'locale' => 'en', 'token_hash' => 'y', 'confirmed_at' => now(), 'unsubscribed_at' => now()]);
    $this->actingAs(nlUser());

    $this->get('/admin/newsletter')->assertOk()->assertSee('wait@example.com')->assertSee('ok@example.com')->assertSee('gone@example.com');
    $this->get('/admin/newsletter?status=pending')->assertSee('wait@example.com')->assertDontSee('ok@example.com')->assertDontSee('gone@example.com');
    $this->get('/admin/newsletter?status=confirmed')->assertSee('ok@example.com')->assertDontSee('wait@example.com');
    $this->get('/admin/newsletter?status=unsubscribed')->assertSee('gone@example.com')->assertDontSee('ok@example.com');
    $this->get('/admin/newsletter?q=wait')->assertSee('wait@example.com')->assertDontSee('ok@example.com');
});

it('confirms a pending subscriber on the admin side', function () {
    $s = nlPending();

    $this->actingAs(nlUser())->post("/admin/newsletter/{$s->id}/confirm")->assertRedirect();
    expect($s->fresh()->confirmed_at)->not->toBeNull()->and($s->fresh()->unsubscribed_at)->toBeNull();
});

it('unsubscribes and deletes subscribers', function () {
    $s = nlPending();
    $this->actingAs(nlUser());

    $this->post("/admin/newsletter/{$s->id}/unsubscribe")->assertRedirect();
    expect($s->fresh()->unsubscribed_at)->not->toBeNull();

    $this->delete("/admin/newsletter/{$s->id}")->assertRedirect();
    expect(NewsletterSubscriber::count())->toBe(0);
});

it('sends the confirmation e-mail again for a pending subscriber only', function () {
    $pending = nlPending();
    $done = NewsletterSubscriber::create(['email' => 'done@example.com', 'locale' => 'en', 'token_hash' => 'z', 'confirmed_at' => now()]);
    $this->actingAs(nlUser());

    $this->post("/admin/newsletter/{$pending->id}/resend")->assertRedirect();
    $this->post("/admin/newsletter/{$done->id}/resend")->assertNotFound();
});

it('keeps newsletter management to roles with that permission', function () {
    $s = nlPending();

    $this->actingAs(nlUser('content-manager'))->get('/admin/newsletter')->assertForbidden();
    $this->post("/admin/newsletter/{$s->id}/confirm")->assertForbidden();
    $this->delete("/admin/newsletter/{$s->id}")->assertForbidden();
    expect(NewsletterSubscriber::count())->toBe(1)->and($s->fresh()->confirmed_at)->toBeNull();
});

it('records who confirmed or deleted a subscriber in the activity log without the e-mail address', function () {
    $s = nlPending('private@example.com');
    $this->actingAs(nlUser())->post("/admin/newsletter/{$s->id}/confirm");

    $log = \Spatie\Activitylog\Models\Activity::where('description', 'newsletter.confirmed_by_admin')->first();
    expect($log)->not->toBeNull()->and(json_encode($log->properties))->not->toContain('private@example.com');
});
