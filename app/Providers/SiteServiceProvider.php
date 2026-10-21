<?php

namespace App\Providers;

use App\Support\Lang;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class SiteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureBackups();
        \Illuminate\Support\Facades\Event::listen(\App\Events\LeadCreated::class, \App\Listeners\AttributeLeadToAffiliate::class);

        // {{ $lr('catalog.index') }} gives a locale-aware URL in any view.
        View::share('lr', fn (string $name, array $params = []) => Lang::route($name, $params));

        View::composer('components.layouts.public-shell', function ($view) {
            $categories = DB::table('product_categories')->whereNull('deleted_at')->whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->limit(8)->get()
                ->map(fn ($c) => ['name' => Lang::pick($c->name), 'slug' => $c->slug]);
            $pages = DB::table('pages')->whereNull('deleted_at')->where('status', 'published')->orderBy('slug')->get(['slug', 'title'])
                ->map(fn ($p) => ['slug' => $p->slug, 'title' => Lang::pick($p->title)]);

            $view->with([
                'navItems' => \App\Support\Navigation::items('header'),
                'footerCategories' => $categories,
                'footerPages' => $pages,
                'siteName' => Settings::get('brand', 'site_name', config('app.name')),
                'alternates' => Lang::alternates(),
            ]);
        });
    }

    /** Applies project backup settings on top of spatie/laravel-backup's published config. */
    private function configureBackups(): void
    {
        $disk = config('motogrip.backups.disk');

        config([
            'filesystems.disks.backups' => ['driver' => 'local', 'root' => storage_path('app/backups'), 'throw' => true],
            'backup.backup.name' => 'motogrip',
            'backup.backup.destination.disks' => [$disk],
            'backup.backup.password' => env('BACKUP_ARCHIVE_PASSWORD'),
            'backup.backup.encryption' => 'default',
            'backup.backup.database_dump_compressor' => null,
            'backup.backup.source.files.include' => [storage_path('app/public'), storage_path('app/private')],
            'backup.backup.source.files.exclude' => [base_path('vendor'), base_path('node_modules'), storage_path('app/backups')],
            'backup.cleanup.default_strategy.keep_all_backups_for_days' => 7,
            'backup.cleanup.default_strategy.keep_daily_backups_for_days' => config('motogrip.backups.keep_days'),
        ]);
    }
}
