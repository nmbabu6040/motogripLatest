<?php

use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('creates every expected role and no unauthenticated super admin', function () {
    $this->seed(DatabaseSeeder::class);

    foreach (['super-admin', 'administrator', 'content-manager', 'product-manager', 'marketing-manager', 'sales-manager', 'sales-representative', 'dealer', 'customer', 'affiliate'] as $role) {
        expect(Role::where('name', $role)->exists())->toBeTrue("missing role {$role}");
    }

    expect(\App\Models\User::count())->toBe(0);
});

it('does not give ordinary administrators role or backup-restore permissions', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = Role::findByName('administrator');
    expect($admin->hasPermissionTo('roles.manage'))->toBeFalse()
        ->and($admin->hasPermissionTo('backups.restore'))->toBeFalse();
});

it('seeds categories but never products, specifications or approvals', function () {
    $this->seed(DatabaseSeeder::class);

    expect(\DB::table('product_categories')->count())->toBeGreaterThan(0)
        ->and(\DB::table('products')->count())->toBe(0)
        ->and(\DB::table('product_specifications')->count())->toBe(0)
        ->and(\DB::table('product_approvals')->count())->toBe(0);
});

it('keeps the statistics section disabled until real figures exist', function () {
    $this->seed(DatabaseSeeder::class);

    expect((bool) \DB::table('homepage_sections')->where('key', 'statistics')->value('is_enabled'))->toBeFalse();
});

it('stores and reads cached settings', function () {
    expect(Settings::get('brand', 'site_name', 'fallback'))->toBe('fallback');

    Settings::set('brand', 'site_name', 'Test Brand');
    expect(Settings::get('brand', 'site_name'))->toBe('Test Brand');
});

it('enforces one like per user per post at the database level', function () {
    expect(Schema::hasTable('likes'))->toBeTrue();

    $user = \App\Models\User::factory()->create();
    $author = \App\Models\User::factory()->create();
    $postId = \DB::table('blog_posts')->insertGetId([
        'author_id' => $author->id, 'slug' => 'x', 'title' => json_encode(['en' => 'X']),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    \DB::table('likes')->insert(['blog_post_id' => $postId, 'user_id' => $user->id]);

    expect(fn () => \DB::table('likes')->insert(['blog_post_id' => $postId, 'user_id' => $user->id]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
