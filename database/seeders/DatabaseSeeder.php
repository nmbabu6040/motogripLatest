<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Safe for production: roles, category names and section order only. No users, no demo content.
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ProductCategorySeeder::class,
            HomepageSectionSeeder::class,
        ]);
    }
}
