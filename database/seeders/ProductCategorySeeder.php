<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Category names only. No product, specification or approval data is ever seeded.
class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Diesel Engine Oils', 'Petrol Engine Oils', 'Motorcycle Engine Oils', 'Gear Oils',
            'Transmission Fluids', 'Hydraulic Oils', 'Industrial Lubricants', 'Greases', 'Automotive Fluids',
        ];

        foreach ($names as $i => $name) {
            DB::table('product_categories')->updateOrInsert(
                ['slug' => Str::slug($name)],
                ['name' => json_encode(['en' => $name]), 'sort_order' => $i, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
