<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\Erp;
use App\Http\Controllers\AffiliateDashboardController;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureAffiliateEnabled;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', SecurityHeaders::class, 'auth', 'verified', EnsureAccountActive::class])->group(function () {

    // Affiliate area (feature-flagged, 404 while the module is off)
    Route::middleware(EnsureAffiliateEnabled::class)->prefix('affiliate')->name('affiliate.')->group(function () {
        Route::get('/', [AffiliateDashboardController::class, 'show'])->name('dashboard');
        Route::post('apply', [AffiliateDashboardController::class, 'apply'])->middleware('throttle:5,1')->name('apply');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        // My Profile
        Route::get('profile', [Admin\ProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::put('profile', [Admin\ProfileController::class, 'update'])
            ->middleware('throttle:5,1')
            ->name('profile.update');

        Route::get('security', [Admin\SecurityController::class, 'show'])->name('security');
        Route::delete('security/sessions', [Admin\SecurityController::class, 'destroyOtherSessions'])->middleware('throttle:5,1')->name('security.sessions.destroy');

        Route::middleware('can:users.manage')->group(function () {
            Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
            Route::get('users/create', [Admin\UserController::class, 'create'])->name('users.create');
            Route::post('users', [Admin\UserController::class, 'store'])->name('users.store');
            Route::get('users/{user}/edit', [Admin\UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        });

        Route::middleware('can:roles.manage')->group(fn() => Route::resource('roles', Admin\RoleController::class)->except('show'));

        Route::middleware('can:settings.manage')->group(function () {
            Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
            Route::post('settings/cache', [Admin\SettingsController::class, 'clearCache'])->name('settings.cache');
            Route::post('settings/maintenance', [Admin\SettingsController::class, 'maintenance'])->name('settings.maintenance');
        });

        Route::get('audit-log', Admin\AuditLogController::class)->middleware('can:audit.view')->name('audit.index');

        // Catalog (policies authorise each action; the permission middleware gates the area)
        Route::middleware('can:products.view')->group(function () {
            Route::resource('products', Admin\ProductController::class)->except('show');
            Route::resource('categories', Admin\CategoryController::class)->except('show');
        });
        Route::middleware('can:documents.manage')->group(fn() => Route::resource('documents', Admin\DocumentController::class)->only(['index', 'create', 'store', 'update', 'destroy']));

        // Sales
        Route::middleware('can:inquiries.manage')->group(function () {
            Route::get('inquiries', [Admin\InquiryController::class, 'index'])->name('inquiries.index');
            Route::get('inquiries/export', [Admin\InquiryController::class, 'export'])->name('inquiries.export');
            Route::get('inquiries/{inquiry}', [Admin\InquiryController::class, 'show'])->name('inquiries.show');
            Route::put('inquiries/{inquiry}', [Admin\InquiryController::class, 'update'])->name('inquiries.update');
            Route::post('inquiries/{inquiry}/notes', [Admin\InquiryController::class, 'note'])->name('inquiries.note');
        });
        Route::middleware('can:dealers.manage')->group(function () {
            Route::get('dealer-applications', [Admin\DealerApplicationController::class, 'index'])->name('dealer-applications.index');
            Route::get('dealer-applications/export', [Admin\DealerApplicationController::class, 'export'])->name('dealer-applications.export');
            Route::get('dealer-applications/{application}', [Admin\DealerApplicationController::class, 'show'])->name('dealer-applications.show');
            Route::put('dealer-applications/{application}', [Admin\DealerApplicationController::class, 'update'])->name('dealer-applications.update');
            Route::post('dealer-applications/{application}/notes', [Admin\DealerApplicationController::class, 'note'])->name('dealer-applications.note');
            Route::post('dealer-applications/{application}/convert', [Admin\DealerApplicationController::class, 'convert'])->name('dealer-applications.convert');
            Route::resource('dealers', Admin\DealerController::class)->except('show');
        });
        Route::middleware('can:newsletter.manage')->group(function () {
            Route::get('newsletter', [Admin\NewsletterAdminController::class, 'index'])->name('newsletter.index');
            Route::get('newsletter/export', [Admin\NewsletterAdminController::class, 'export'])->name('newsletter.export');
            Route::post('newsletter/{subscriber}/confirm', [Admin\NewsletterAdminController::class, 'confirm'])->whereNumber('subscriber')->name('newsletter.confirm');
            Route::post('newsletter/{subscriber}/resend', [Admin\NewsletterAdminController::class, 'resend'])->whereNumber('subscriber')->name('newsletter.resend');
            Route::post('newsletter/{subscriber}/unsubscribe', [Admin\NewsletterAdminController::class, 'unsubscribe'])->whereNumber('subscriber')->name('newsletter.unsubscribe');
            Route::delete('newsletter/{subscriber}', [Admin\NewsletterAdminController::class, 'destroy'])->whereNumber('subscriber')->name('newsletter.destroy');
        });

        // Content
        Route::middleware('can:blog.manage')->group(function () {
            Route::get('blog/taxonomy', [Admin\BlogTaxonomyController::class, 'index'])->name('blog.taxonomy');
            Route::post('blog/categories', [Admin\BlogTaxonomyController::class, 'storeCategory'])->name('blog.categories.store');
            Route::delete('blog/categories/{category}', [Admin\BlogTaxonomyController::class, 'destroyCategory'])->name('blog.categories.destroy');
            Route::delete('blog/tags/{tag}', [Admin\BlogTaxonomyController::class, 'destroyTag'])->name('blog.tags.destroy');
            Route::post('blog/{post}/revisions/{revision}/restore', [Admin\BlogPostController::class, 'restore'])->name('blog.restore');
            Route::resource('blog', Admin\BlogPostController::class)->parameters(['blog' => 'post'])->except('show');
        });
        Route::middleware('can:comments.moderate')->group(function () {
            Route::get('comments', [Admin\CommentModerationController::class, 'index'])->name('comments.index');
            Route::put('comments/{comment}', [Admin\CommentModerationController::class, 'update'])->name('comments.update');
            Route::delete('comments/{comment}', [Admin\CommentModerationController::class, 'destroy'])->name('comments.destroy');
        });
        Route::middleware('can:pages.manage')->group(fn() => Route::resource('pages', Admin\PageController::class)->except('show'));
        Route::middleware('can:homepage.manage')->group(fn() => Route::resource('faqs', Admin\FaqController::class)->except('show'));
        Route::middleware('can:homepage.manage')->prefix('homepage')->name('homepage.')->group(function () {
            Route::get('/', [Admin\HomepageController::class, 'index'])->name('index');
            Route::post('{key}/toggle', [Admin\HomepageController::class, 'toggle'])->name('toggle');
            Route::post('{key}/move', [Admin\HomepageController::class, 'move'])->name('move');
            Route::get('{key}/edit', [Admin\HomepageController::class, 'edit'])->name('edit');
            Route::put('{key}', [Admin\HomepageController::class, 'update'])->name('update');
        });
        Route::middleware('can:seo.manage')->prefix('seo')->name('seo.')->group(function () {
            Route::get('/', [Admin\SeoController::class, 'index'])->name('index');
            Route::post('redirects', [Admin\SeoController::class, 'storeRedirect'])->name('redirects.store');
            Route::delete('redirects/{redirect}', [Admin\SeoController::class, 'destroyRedirect'])->name('redirects.destroy');
        });

        // Stock, purchases, sales, returns, challans (business accounts)
        Route::middleware('can:inventory.view')->group(function () {
            Route::get('stock', [Erp\StockController::class, 'index'])->name('stock.index');
            Route::get('stock/{stockItem}', [Erp\StockController::class, 'show'])->whereNumber('stockItem')->name('stock.show');
        });
        Route::middleware('can:inventory.manage')->group(function () {
            Route::get('stock/create', [Erp\StockController::class, 'create'])->name('stock.create');
            Route::post('stock', [Erp\StockController::class, 'store'])->name('stock.store');
            Route::post('stock/sync', [Erp\StockController::class, 'sync'])->name('stock.sync');
            Route::get('stock/{stockItem}/edit', [Erp\StockController::class, 'edit'])->whereNumber('stockItem')->name('stock.edit');
            Route::put('stock/{stockItem}/details', [Erp\StockController::class, 'save'])->whereNumber('stockItem')->name('stock.save');
            Route::delete('stock/{stockItem}', [Erp\StockController::class, 'destroy'])->whereNumber('stockItem')->name('stock.destroy');
            Route::put('stock/{stockItem}', [Erp\StockController::class, 'update'])->whereNumber('stockItem')->name('stock.update');
            Route::post('stock/{stockItem}/adjust', [Erp\StockController::class, 'adjust'])->whereNumber('stockItem')->name('stock.adjust');
        });
        Route::middleware('can:parties.manage')->prefix('parties')->name('parties.')->group(function () {
            Route::get('{kind}', [Erp\PartyController::class, 'index'])->where('kind', 'customer|supplier')->name('index');
            Route::post('{kind}', [Erp\PartyController::class, 'store'])->where('kind', 'customer|supplier')->name('store');
            Route::get('{party}/edit', [Erp\PartyController::class, 'edit'])->whereNumber('party')->name('edit');
            Route::put('{party}', [Erp\PartyController::class, 'update'])->whereNumber('party')->name('update');
            Route::delete('{party}', [Erp\PartyController::class, 'destroy'])->whereNumber('party')->name('destroy');
        });
        Route::middleware('can:purchases.manage')->group(function () {
            Route::get('purchases', [Erp\PurchaseController::class, 'index'])->name('purchases.index');
            Route::get('purchases/create', [Erp\PurchaseController::class, 'create'])->name('purchases.create');
            Route::post('purchases', [Erp\PurchaseController::class, 'store'])->name('purchases.store');
            Route::get('purchases/{purchase}', [Erp\PurchaseController::class, 'show'])->whereNumber('purchase')->name('purchases.show');
            Route::post('purchases/{purchase}/payments', [Erp\PurchaseController::class, 'pay'])->whereNumber('purchase')->name('purchases.pay');
            Route::get('purchases/{purchase}/edit', [Erp\PurchaseController::class, 'edit'])->whereNumber('purchase')->name('purchases.edit');
            Route::put('purchases/{purchase}', [Erp\PurchaseController::class, 'update'])->whereNumber('purchase')->name('purchases.update');
            Route::post('purchases/{purchase}/void', [Erp\PurchaseController::class, 'void'])->whereNumber('purchase')->name('purchases.void');
            Route::get('purchases/{purchase}/returns/create', [Erp\ReturnController::class, 'purchaseCreate'])->whereNumber('purchase')->name('purchases.returns.create');
            Route::post('purchases/{purchase}/returns', [Erp\ReturnController::class, 'purchaseStore'])->whereNumber('purchase')->name('purchases.returns.store');
            Route::get('purchase-returns', [Erp\ReturnController::class, 'purchaseIndex'])->name('purchase-returns.index');
            Route::get('purchase-returns/{purchaseReturn}', [Erp\ReturnController::class, 'purchaseShow'])->whereNumber('purchaseReturn')->name('purchase-returns.show');
            Route::get('purchase-returns/{purchaseReturn}/edit', [Erp\ReturnController::class, 'purchaseEdit'])->whereNumber('purchaseReturn')->name('purchase-returns.edit');
            Route::put('purchase-returns/{purchaseReturn}', [Erp\ReturnController::class, 'purchaseUpdate'])->whereNumber('purchaseReturn')->name('purchase-returns.update');
            Route::delete('purchase-returns/{purchaseReturn}', [Erp\ReturnController::class, 'purchaseDestroy'])->whereNumber('purchaseReturn')->name('purchase-returns.destroy');
        });
        Route::middleware('can:sales.manage')->group(function () {
            Route::get('sales', [Erp\SaleController::class, 'index'])->name('sales.index');
            Route::get('sales/create', [Erp\SaleController::class, 'create'])->name('sales.create');
            Route::post('sales', [Erp\SaleController::class, 'store'])->name('sales.store');
            Route::get('sales/{sale}', [Erp\SaleController::class, 'show'])->whereNumber('sale')->name('sales.show');
            Route::post('sales/{sale}/payments', [Erp\SaleController::class, 'pay'])->whereNumber('sale')->name('sales.pay');
            Route::get('sales/{sale}/edit', [Erp\SaleController::class, 'edit'])->whereNumber('sale')->name('sales.edit');
            Route::put('sales/{sale}', [Erp\SaleController::class, 'update'])->whereNumber('sale')->name('sales.update');
            Route::post('sales/{sale}/pay-link', [Erp\SaleController::class, 'payLink'])->whereNumber('sale')->name('sales.paylink');
            Route::post('sales/{sale}/void', [Erp\SaleController::class, 'void'])->whereNumber('sale')->name('sales.void');
            Route::get('sales/{sale}/returns/create', [Erp\ReturnController::class, 'saleCreate'])->whereNumber('sale')->name('sales.returns.create');
            Route::post('sales/{sale}/returns', [Erp\ReturnController::class, 'saleStore'])->whereNumber('sale')->name('sales.returns.store');
            Route::get('sale-returns', [Erp\ReturnController::class, 'saleIndex'])->name('sale-returns.index');
            Route::get('sale-returns/{saleReturn}', [Erp\ReturnController::class, 'saleShow'])->whereNumber('saleReturn')->name('sale-returns.show');
            Route::get('sale-returns/{saleReturn}/edit', [Erp\ReturnController::class, 'saleEdit'])->whereNumber('saleReturn')->name('sale-returns.edit');
            Route::put('sale-returns/{saleReturn}', [Erp\ReturnController::class, 'saleUpdate'])->whereNumber('saleReturn')->name('sale-returns.update');
            Route::delete('sale-returns/{saleReturn}', [Erp\ReturnController::class, 'saleDestroy'])->whereNumber('saleReturn')->name('sale-returns.destroy');
            Route::get('challans', [Erp\ChallanController::class, 'index'])->name('challans.index');
            Route::get('sales/{sale}/challans/create', [Erp\ChallanController::class, 'create'])->whereNumber('sale')->name('sales.challans.create');
            Route::post('sales/{sale}/challans', [Erp\ChallanController::class, 'store'])->whereNumber('sale')->name('sales.challans.store');
            Route::get('challans/{challan}', [Erp\ChallanController::class, 'show'])->whereNumber('challan')->name('challans.show');
            Route::get('challans/{challan}/edit', [Erp\ChallanController::class, 'edit'])->whereNumber('challan')->name('challans.edit');
            Route::put('challans/{challan}', [Erp\ChallanController::class, 'update'])->whereNumber('challan')->name('challans.update');
            Route::delete('challans/{challan}', [Erp\ChallanController::class, 'destroy'])->whereNumber('challan')->name('challans.destroy');
        });
        Route::delete('payments/{payment}', [Erp\PaymentController::class, 'destroy'])->whereNumber('payment')->name('payments.destroy');
        Route::get('charts/{metric}', Erp\ChartController::class)->name('charts');

        // Recommended products (outbound Amazon / AliExpress links)
        Route::middleware('can:partners.manage')->group(fn() => Route::resource('partner-links', Admin\PartnerLinkController::class)->except('show'));

        // Reports
        Route::get('reports/data', [Admin\ReportController::class, 'data'])->name('reports.data');
        Route::get('reports/export', [Admin\ReportController::class, 'export'])->name('reports.export');

        // Affiliates: the page always opens (it explains how to switch the module on); every action needs the module enabled.
        Route::middleware('can:affiliates.manage')->prefix('affiliates')->name('affiliates.')->group(function () {
            Route::get('/', [Admin\AffiliateController::class, 'index'])->name('index');
            Route::middleware(EnsureAffiliateEnabled::class)->group(function () {
                Route::post('/', [Admin\AffiliateController::class, 'store'])->name('store');
                Route::get('export', [Admin\AffiliateController::class, 'export'])->name('export');
                Route::put('{affiliate}/status', [Admin\AffiliateController::class, 'setStatus'])->name('status');
                Route::post('campaigns', [Admin\AffiliateController::class, 'storeCampaign'])->name('campaigns.store');
                Route::post('campaigns/{campaign}/toggle', [Admin\AffiliateController::class, 'toggleCampaign'])->name('campaigns.toggle');
                Route::put('conversions/{conversion}', [Admin\AffiliateController::class, 'decide'])->name('decide');
                Route::post('commissions/{commission}/paid', [Admin\AffiliateController::class, 'markPaid'])->name('paid');
            });
        });

        // Backups: viewing and creating need backups.create. Restore is CLI-only by design.
        Route::middleware('can:backups.create')->prefix('backups')->name('backups.')->group(function () {
            Route::get('/', [Admin\BackupController::class, 'index'])->name('index');
            Route::post('/', [Admin\BackupController::class, 'store'])->middleware('throttle:5,10')->name('store');
            Route::post('{record}/verify', [Admin\BackupController::class, 'verify'])->name('verify');
        });
    });
});
