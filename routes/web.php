<?php

use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\RedirectFallbackController;
use App\Http\Controllers\SeoFilesController;
use App\Http\Middleware\CaptureReferral;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

// Authenticated area first, so /admin and /affiliate are never treated as CMS page slugs.
require __DIR__.'/admin.php';

Route::get('health', HealthController::class)->name('health');
Route::get('robots.txt', [SeoFilesController::class, 'robots']);
Route::get('ads.txt', [SeoFilesController::class, 'adsTxt']);
Route::get('sitemap.xml', [SeoFilesController::class, 'sitemap']);

Route::middleware(['web', SecurityHeaders::class])->group(function () {
    Route::get('documents/{document}', DocumentDownloadController::class)->middleware('throttle:60,1')->name('documents.download');

    Route::post('newsletter', [NewsletterController::class, 'subscribe'])->middleware('throttle:5,10')->name('newsletter.subscribe');
    Route::get('newsletter/confirm/{subscriber}', [NewsletterController::class, 'confirm'])->middleware('signed')->name('newsletter.confirm');
    Route::get('newsletter/unsubscribe/{subscriber}', [NewsletterController::class, 'unsubscribe'])->middleware('signed')->name('newsletter.unsubscribe');
});

// Public site: English at "/", Bengali at "/bn".
foreach (['en' => ['', ''], 'bn' => ['bn', 'bn.']] as $locale => [$prefix, $name]) {
    Route::middleware(['web', SecurityHeaders::class, SetLocale::class.':'.$locale, CaptureReferral::class])
        ->prefix($prefix)->name($name)->group(base_path('routes/public.php'));
}

// Anything unmatched: admin-managed redirects, otherwise 404.
Route::fallback(RedirectFallbackController::class)->middleware(['web', SecurityHeaders::class]);
