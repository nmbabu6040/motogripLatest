<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogEngagementController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DealerApplicationController;
use App\Http\Controllers\DealerLocatorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ResourceCenterController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Registered twice by routes/web.php: once for English (no prefix) and once under /bn with "bn." names.
Route::get('/', HomeController::class)->name('home');

Route::get('products', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('products/{product}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('compare', CompareController::class)->name('compare');
Route::get('resources', ResourceCenterController::class)->name('resources.index');

Route::get('search', [SearchController::class, 'index'])->name('search');
Route::get('search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:60,1')->name('search.suggest');

Route::get('contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:5,10')->name('contact.store');

Route::get('dealers', DealerLocatorController::class)->name('dealers.index');
Route::get('become-a-dealer', [DealerApplicationController::class, 'create'])->name('dealer.apply');
Route::post('become-a-dealer', [DealerApplicationController::class, 'store'])->middleware('throttle:4,10')->name('dealer.apply.store');
Route::get('become-a-dealer/received', [DealerApplicationController::class, 'done'])->name('dealer.done');
Route::get('dealer-application-status', [DealerApplicationController::class, 'statusForm'])->name('dealer.status');
Route::post('dealer-application-status', [DealerApplicationController::class, 'status'])->middleware('throttle:10,5')->name('dealer.status.check');

Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/{post}', [BlogController::class, 'show'])->name('blog.show');
Route::post('blog/{post}/like', [BlogEngagementController::class, 'like'])->middleware('throttle:20,1')->name('blog.like');
Route::post('blog/{post}/comments', [BlogEngagementController::class, 'comment'])->middleware('throttle:5,5')->name('blog.comment');
Route::post('comments/{comment}/report', [BlogEngagementController::class, 'report'])->middleware('throttle:5,10')->name('blog.comment.report');

// CMS pages (privacy policy, about, terms). Must stay last so it never shadows a real route.
Route::get('{page}', [PageController::class, 'show'])->where('page', '(?!bn$)[a-z0-9]+(-[a-z0-9]+)*')->name('page.show');
