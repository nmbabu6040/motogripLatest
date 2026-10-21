<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public const PERMISSIONS = [
        'dashboard.view',
        'users.manage', 'roles.manage',
        'products.view', 'products.create', 'products.update', 'products.publish', 'products.delete',
        'specifications.manage',
        'documents.manage', 'documents.view-restricted',
        'blog.manage', 'blog.publish', 'comments.moderate',
        'dealers.manage', 'inquiries.manage', 'inquiries.view-all', 'inquiries.export', 'pages.manage', 'homepage.manage', 'newsletter.manage',
        'analytics.view', 'reports.export',
        'settings.manage', 'seo.manage',
        'affiliates.manage',
        'backups.create', 'backups.restore',
        'audit.view',
    ];

    public const ROLES = [
        'administrator'     => ['dashboard.view', 'users.manage', 'products.view', 'products.create', 'products.update', 'products.publish', 'products.delete', 'specifications.manage', 'documents.manage', 'documents.view-restricted', 'blog.manage', 'blog.publish', 'comments.moderate', 'dealers.manage', 'inquiries.manage', 'inquiries.view-all', 'inquiries.export', 'pages.manage', 'homepage.manage', 'newsletter.manage', 'analytics.view', 'reports.export', 'settings.manage', 'seo.manage', 'audit.view'],
        'content-manager'   => ['dashboard.view', 'blog.manage', 'blog.publish', 'comments.moderate', 'seo.manage', 'pages.manage', 'homepage.manage'],
        'product-manager'   => ['dashboard.view', 'products.view', 'products.create', 'products.update', 'products.publish', 'specifications.manage', 'documents.manage'],
        'marketing-manager' => ['dashboard.view', 'blog.manage', 'seo.manage', 'analytics.view', 'reports.export', 'affiliates.manage'],
        'sales-manager'     => ['dashboard.view', 'dealers.manage', 'inquiries.manage', 'inquiries.view-all', 'inquiries.export', 'pages.manage', 'homepage.manage', 'newsletter.manage', 'analytics.view', 'reports.export'],
        'sales-representative' => ['dashboard.view', 'inquiries.manage'],
        'dealer'            => ['documents.view-restricted'],
        'customer'          => [],
        'affiliate'         => [],
    ];

    public static function protectedRoles(): array
    {
        return array_merge(['super-admin'], array_keys(self::ROLES));
    }

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // super-admin gets everything through Gate::before (Phase 2), so it needs no permission rows.
        Role::findOrCreate('super-admin', 'web');

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }
}
