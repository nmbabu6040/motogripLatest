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

    // Pay an invoice online. The token in the address is the secret; the gateway's replies are always checked with the gateway.
    Route::get('pay/{token}', [\App\Http\Controllers\PayController::class, 'show'])->where('token', '[A-Za-z0-9]{40}')->name('pay.show');
    Route::post('pay/{token}/start', [\App\Http\Controllers\PayController::class, 'start'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:6,1')->name('pay.start');
    Route::match(['get', 'post'], 'pay-callback/{outcome}', [\App\Http\Controllers\PayController::class, 'callback'])->where('outcome', 'success|fail|cancel')->name('pay.callback');
    Route::post('pay-ipn', [\App\Http\Controllers\PayController::class, 'ipn'])->name('pay.ipn');
    Route::post('recommended/{partnerLink}/click', [\App\Http\Controllers\PartnerController::class, 'click'])->middleware('throttle:30,1')->name('partners.click');
    // Live chat, visitor side. The visitor is recognised only by a secret token kept in their own browser.
    Route::post('chat/start', [\App\Http\Controllers\ChatController::class, 'start'])->middleware('throttle:5,1')->name('chat.start');
    Route::post('chat/send', [\App\Http\Controllers\ChatController::class, 'send'])->middleware('throttle:20,1')->name('chat.send');
    Route::get('chat/messages', [\App\Http\Controllers\ChatController::class, 'poll'])->middleware('throttle:90,1')->name('chat.poll');
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
