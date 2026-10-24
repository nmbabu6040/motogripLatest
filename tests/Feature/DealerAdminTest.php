<?php

use App\Models\Dealer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function salesAdmin(string $role = 'sales-manager'): User
{
    return User::factory()->create()->assignRole($role);
}

it('adds a dealer with opening hours and shows the hours in the edit form', function () {
    $user = salesAdmin();

    $this->actingAs($user)->post('/admin/dealers', ['name' => 'Jahanara Enterprise', 'district' => 'Dhaka', 'city' => 'Dhaka', 'address' => 'Boro Beraid, Notun Bazar, Dhaka.', 'hours' => '9AM-9PM'])
        ->assertRedirect(route('admin.dealers.index'));

    $dealer = Dealer::first();
    expect($dealer->name)->toBe('Jahanara Enterprise')->and($dealer->business_hours)->toBe(['text' => '9AM-9PM'])->and($dealer->is_published)->toBeFalse();

    $this->get("/admin/dealers/{$dealer->id}/edit")->assertOk()->assertSee('9AM-9PM');
});

it('adds a dealer without hours, phone or email', function () {
    $this->actingAs(salesAdmin())->post('/admin/dealers', ['name' => 'Minimal Dealer', 'district' => 'Khulna'])->assertRedirect();

    expect(Dealer::where('name', 'Minimal Dealer')->first()->business_hours)->toBeNull();
});

it('shows a published dealer and its hours on the public locator, but keeps phone and email private by default', function () {
    $user = salesAdmin();
    $this->actingAs($user)->post('/admin/dealers', ['name' => 'Public Motors', 'district' => 'Dhaka', 'phone' => '01711111111', 'email' => 'd@example.com', 'hours' => '10AM-8PM', 'is_published' => '1']);

    $this->get('/dealers')->assertOk()->assertSee('Public Motors')->assertSee('10AM-8PM')->assertDontSee('01711111111')->assertDontSee('d@example.com');
});

it('shows phone and email on the locator only when the admin ticks them', function () {
    $this->actingAs(salesAdmin())->post('/admin/dealers', ['name' => 'Open Motors', 'district' => 'Dhaka', 'phone' => '01722222222', 'email' => 'o@example.com', 'is_published' => '1', 'show_phone' => '1', 'show_email' => '1']);

    $this->get('/dealers')->assertSee('01722222222')->assertSee('o@example.com');
});

it('updates and clears the opening hours', function () {
    $user = salesAdmin();
    $this->actingAs($user)->post('/admin/dealers', ['name' => 'Hours Motors', 'district' => 'Dhaka', 'hours' => '9AM-5PM']);
    $dealer = Dealer::first();

    $this->actingAs($user)->put("/admin/dealers/{$dealer->id}", ['name' => 'Hours Motors', 'district' => 'Dhaka', 'hours' => '8AM-6PM'])->assertRedirect();
    expect($dealer->fresh()->business_hours)->toBe(['text' => '8AM-6PM']);

    $this->actingAs($user)->put("/admin/dealers/{$dealer->id}", ['name' => 'Hours Motors', 'district' => 'Dhaka'])->assertRedirect();
    expect($dealer->fresh()->business_hours)->toBeNull();
});

it('validates the dealer form and limits it to sales roles', function () {
    $this->actingAs(salesAdmin())->post('/admin/dealers', ['name' => '', 'district' => '', 'email' => 'not-an-email', 'latitude' => '999'])->assertSessionHasErrors(['name', 'district', 'email', 'latitude']);
    $this->actingAs(salesAdmin('content-manager'))->post('/admin/dealers', ['name' => 'X', 'district' => 'Y'])->assertForbidden();
});

it('removes a dealer', function () {
    $user = salesAdmin();
    $this->actingAs($user)->post('/admin/dealers', ['name' => 'Gone Motors', 'district' => 'Dhaka']);
    $dealer = Dealer::first();

    $this->actingAs($user)->delete("/admin/dealers/{$dealer->id}")->assertRedirect();
    expect(Dealer::count())->toBe(0);
});
