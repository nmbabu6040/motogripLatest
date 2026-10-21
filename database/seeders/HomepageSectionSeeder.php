<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HomepageSectionSeeder extends Seeder
{
    public const SECTIONS = [
        'hero', 'introduction', 'featured_products', 'product_categories', 'automotive_solutions',
        'industrial_solutions', 'benefits', 'why_choose', 'industries', 'resources',
        'dealer_recruitment', 'statistics', 'latest_articles', 'faqs', 'inquiry_cta',
        'newsletter', 'social_links',
    ];

    public function run(): void
    {
        foreach (self::SECTIONS as $i => $key) {
            DB::table('homepage_sections')->updateOrInsert(
                ['key' => $key],
                // statistics stays disabled until verified figures are entered by an admin
                ['sort_order' => $i, 'is_enabled' => $key !== 'statistics', 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
