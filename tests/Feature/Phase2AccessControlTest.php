<?php

use App\Models\User;
use App\Support\Settings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

function userWithRole(string $role, array $attrs = []): User
{
    return User::factory()->create($attrs)->assignRole($role);
}

it('sends guests to the login page', function () {
    $this->get('/admin')->assertRedirect('/login');
});

it('sends customers to the public site instead of the dashboard', function () {
    $this->actingAs(userWithRole('customer'))->get('/admin')->assertRedirect('/');
});

it('shows the dashboard to staff with dashboard access', function () {
    $this->actingAs(userWithRole('administrator'))->get('/admin')->assertOk();
});

it('blocks staff without settings permission from settings', function () {
    $this->actingAs(userWithRole('content-manager'))->get('/admin/settings')->assertForbidden();
    $this->actingAs(userWithRole('content-manager'))->put('/admin/settings', ['brand_site_name' => 'X'])->assertForbidden();
});

it('saves settings, caches them and writes an audit entry', function () {
    $admin = userWithRole('administrator');

    $this->actingAs($admin)->put('/admin/settings', ['brand_site_name' => 'Motogrip Test'])->assertRedirect();

    expect(Settings::get('brand', 'site_name'))->toBe('Motogrip Test');
    expect(Activity::where('description', 'settings.updated')->where('causer_id', $admin->id)->exists())->toBeTrue();
});

it('rejects invalid settings input', function () {
    $this->actingAs(userWithRole('administrator'))
        ->put('/admin/settings', ['brand_site_name' => '', 'contact_email' => 'not-an-email'])
        ->assertSessionHasErrors(['brand_site_name', 'contact_email']);
});

it('stops an administrator from assigning the super-admin role', function () {
    $admin = userWithRole('administrator');
    $target = userWithRole('customer');

    $this->actingAs($admin)->put("/admin/users/{$target->id}", ['status' => 'active', 'roles' => ['super-admin']])->assertForbidden();

    expect($target->fresh()->hasRole('super-admin'))->toBeFalse();
});

it('stops an administrator from editing a super administrator', function () {
    $admin = userWithRole('administrator');
    $super = userWithRole('super-admin');

    $this->actingAs($admin)->get("/admin/users/{$super->id}/edit")->assertForbidden();
    $this->actingAs($admin)->put("/admin/users/{$super->id}", ['status' => 'suspended', 'roles' => []])->assertForbidden();
});

it('stops an administrator from editing their own roles', function () {
    $admin = userWithRole('administrator');

    $this->actingAs($admin)->put("/admin/users/{$admin->id}", ['status' => 'active', 'roles' => ['administrator']])->assertForbidden();
});

it('refuses to remove the last active super administrator', function () {
    $super = userWithRole('super-admin');

    $this->actingAs($super)->put("/admin/users/{$super->id}", ['status' => 'active', 'roles' => []])->assertSessionHasErrors('roles');

    expect($super->fresh()->hasRole('super-admin'))->toBeTrue();
});

it('lets a super administrator change a role and suspension signs the user out everywhere', function () {
    $super = userWithRole('super-admin');
    $target = userWithRole('customer');
    DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $target->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'x', 'payload' => '', 'last_activity' => time()]);

    $this->actingAs($super)->put("/admin/users/{$target->id}", ['status' => 'suspended', 'roles' => ['sales-representative']])->assertRedirect();

    expect($target->fresh()->status)->toBe('suspended')
        ->and($target->fresh()->hasRole('sales-representative'))->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(0)
        ->and(Activity::where('description', 'user.updated')->exists())->toBeTrue();
});

it('forbids role management without roles.manage', function () {
    $admin = userWithRole('administrator');

    $this->actingAs($admin)->get('/admin/roles')->assertForbidden();
    $this->actingAs($admin)->post('/admin/roles', ['name' => 'x-role', 'permissions' => []])->assertForbidden();
});

it('stops a limited role manager from granting permissions they do not hold', function () {
    $limited = Role::create(['name' => 'role-editor']);
    $limited->givePermissionTo(['roles.manage', 'dashboard.view']);
    $actor = userWithRole('role-editor');

    $this->actingAs($actor)->post('/admin/roles', ['name' => 'sneaky', 'permissions' => ['backups.restore']])->assertForbidden();
    $this->actingAs($actor)->post('/admin/roles', ['name' => 'harmless', 'permissions' => ['dashboard.view']])->assertRedirect();

    expect(Role::where('name', 'sneaky')->exists())->toBeFalse()
        ->and(Role::where('name', 'harmless')->exists())->toBeTrue();
});

it('never deletes built-in roles', function () {
    $super = userWithRole('super-admin');
    $role = Role::findByName('content-manager');

    $this->actingAs($super)->delete("/admin/roles/{$role->id}")->assertForbidden();

    expect(Role::where('name', 'content-manager')->exists())->toBeTrue();
});

it('blocks suspended accounts from signing in', function () {
    $user = User::factory()->create(['status' => 'suspended', 'password' => Hash::make('Correct-Horse-Battery-9')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'Correct-Horse-Battery-9'])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('signs active accounts in and records the login time', function () {
    $user = User::factory()->create(['password' => Hash::make('Correct-Horse-Battery-9')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'Correct-Horse-Battery-9']);

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rate limits repeated failed logins', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(429);
});

it('requires the current password to revoke other sessions', function () {
    $user = userWithRole('customer', ['password' => Hash::make('Correct-Horse-Battery-9')]);

    $this->actingAs($user)->delete('/admin/security/sessions', ['password' => 'wrong'])->assertSessionHasErrors('password');
});
