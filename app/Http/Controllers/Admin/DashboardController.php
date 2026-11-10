<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerLink;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Accounts without dashboard access (customers, dealers) go to the public site.
        if (! $user->can('dashboard.view')) {
            return redirect('/');
        }

        $from = $request->date('from') ?? now()->subDays(29);
        $to = $request->date('to') ?? now();

        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        return view('admin.dashboard', [
            'greeting' => $greeting, 'from' => $from->toDateString(), 'to' => $to->toDateString(),
            'siteCards' => $this->siteCards($user),
            'erp' => $this->erp($user),
            'charts' => $this->charts($user),
            'quick' => $this->quickActions($user),
            'recentInquiries' => $user->can('inquiries.view-all') ? DB::table('inquiries')->whereNull('deleted_at')->latest()->limit(5)->get(['id', 'name', 'type', 'status', 'created_at']) : collect(),
            'popularProducts' => $user->can('analytics.view') ? DB::table('product_views')->join('products', 'products.id', '=', 'product_views.product_id')->where('viewed_on', '>=', now()->subDays(30)->toDateString())
                ->groupBy('products.id', 'products.sku')->orderByRaw('SUM(views) DESC')->limit(5)->get(['products.sku', DB::raw('SUM(views) as views')]) : collect(),
            'popularPosts' => $user->can('analytics.view') ? DB::table('blog_post_views')->join('blog_posts', 'blog_posts.id', '=', 'blog_post_views.blog_post_id')->where('viewed_on', '>=', now()->subDays(30)->toDateString())
                ->groupBy('blog_posts.id', 'blog_posts.slug')->orderByRaw('SUM(views) DESC')->limit(5)->get(['blog_posts.slug', DB::raw('SUM(views) as views')]) : collect(),
            'recentActivity' => $user->can('audit.view') ? \Spatie\Activitylog\Models\Activity::with('causer')->latest()->limit(8)->get() : collect(),
            'topLinks' => $user->can('partners.manage') ? PartnerLink::published()->get()->map(function ($l) {
                $l->recent_clicks = (int) DB::table('partner_link_clicks')->where('partner_link_id', $l->id)->where('clicked_on', '>=', now()->subDays(30)->toDateString())->sum('clicks');
                return $l;
            })->sortByDesc('recent_clicks')->take(5)->values() : collect(),
        ]);
    }

    /** Website figures, each a live database count. A card is shown only if the viewer may see that data. */
    private function siteCards($user)
    {
        $defs = [
            ['Products', 'products.view', fn () => DB::table('products')->whereNull('deleted_at')->count(), '#174A7E', 'admin.products.index'],
            ['Published products', 'products.view', fn () => DB::table('products')->whereNull('deleted_at')->where('status', 'published')->count(), '#2BB673', 'admin.products.index'],
            ['Draft products', 'products.view', fn () => DB::table('products')->whereNull('deleted_at')->where('status', 'draft')->count(), '#F5B82E', 'admin.products.index'],
            ['Registered users', 'users.manage', fn () => DB::table('users')->count(), '#7B61FF', 'admin.users.index'],
            ['Dealer applications', 'dealers.manage', fn () => DB::table('dealer_applications')->whereNull('deleted_at')->count(), '#17BEBB', 'admin.dealer-applications.index'],
            ['New inquiries', 'inquiries.manage', fn () => DB::table('inquiries')->whereNull('deleted_at')->where('status', 'new')->count(), '#E4572E', 'admin.inquiries.index'],
            ['Published articles', 'blog.manage', fn () => DB::table('blog_posts')->whereNull('deleted_at')->where('status', 'published')->count(), '#174A7E', 'admin.blog.index'],
            ['Comments to review', 'comments.moderate', fn () => DB::table('comments')->whereNull('deleted_at')->where('status', 'pending')->count(), '#FF8FA3', 'admin.comments.index'],
            ['Document downloads', 'documents.manage', fn () => (int) DB::table('document_downloads')->sum('downloads'), '#4E5D78', 'admin.documents.index'],
        ];

        return collect($defs)->filter(fn ($d) => $user->can($d[1]))->map(fn ($d) => ['label' => $d[0], 'value' => $d[2](), 'format' => 'number', 'accent' => $d[3], 'href' => route($d[4]), 'hint' => null])->values();
    }

    /** Business figures: sales, purchases, stock, money owed. Null when the viewer has no business permission. */
    private function erp($user): ?array
    {
        $sales = $user->can('sales.manage');
        $purchases = $user->can('purchases.manage');
        $stock = $user->can('inventory.view');
        if (! $sales && ! $purchases && ! $stock) {
            return null;
        }

        $m1 = today()->startOfMonth()->toDateString();
        $m2 = today()->endOfMonth()->toDateString();
        $cards = [];
        $out = ['cards' => [], 'lowStock' => collect(), 'recentSales' => collect(), 'recentPurchases' => collect()];

        if ($sales) {
            $today = Sale::where('status', 'confirmed')->whereDate('sold_on', today()->toDateString())->sum('total');
            $month = Sale::where('status', 'confirmed')->whereBetween('sold_on', [$m1, $m2])->sum('total');
            $returned = SaleReturn::whereBetween('returned_on', [$m1, $m2])->sum('refund_total');

            $gross = (float) DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')->where('sales.status', 'confirmed')->whereBetween('sales.sold_on', [$m1, $m2])
                ->selectRaw('COALESCE(SUM((sale_items.unit_price - sale_items.unit_cost) * sale_items.quantity), 0) as m')->value('m');
            $lost = (float) DB::table('sale_return_items as r')->join('sale_returns as sr', 'sr.id', '=', 'r.sale_return_id')->join('sale_items as si', 'si.id', '=', 'r.sale_item_id')->whereBetween('sr.returned_on', [$m1, $m2])
                ->selectRaw('COALESCE(SUM((r.unit_price - si.unit_cost) * r.quantity), 0) as m')->value('m');

            $receivable = Sale::where('status', 'confirmed')->withSum('returns', 'refund_total')->withSum('payments', 'amount')->get()
                ->sum(fn ($s) => max(0, (float) $s->total - (float) $s->returns_sum_refund_total - (float) $s->payments_sum_amount));

            $cards[] = ['label' => 'Sales today', 'value' => (float) $today, 'format' => 'money', 'accent' => '#174A7E', 'href' => route('admin.sales.index'), 'hint' => null];
            $cards[] = ['label' => 'Sales this month', 'value' => (float) $month - (float) $returned, 'format' => 'money', 'accent' => '#2BB673', 'href' => route('admin.sales.index'), 'hint' => 'after returns'];
            $cards[] = ['label' => 'Estimated profit this month', 'value' => $gross - $lost, 'format' => 'money', 'accent' => '#7B61FF', 'href' => route('admin.sales.index'), 'hint' => 'item price minus average cost; before discount, tax and expenses'];
            $cards[] = ['label' => 'Customers owe you', 'value' => (float) $receivable, 'format' => 'money', 'accent' => '#E4572E', 'href' => route('admin.sales.index', ['status' => 'due']), 'hint' => null];
            $out['recentSales'] = Sale::with('customer')->latest('sold_on')->latest('id')->limit(6)->get();
        }

        if ($purchases) {
            $month = Purchase::where('status', 'received')->whereBetween('purchased_on', [$m1, $m2])->sum('total');
            $payable = Purchase::where('status', 'received')->withSum('returns', 'credit_total')->withSum('payments', 'amount')->get()
                ->sum(fn ($p) => max(0, (float) $p->total - (float) $p->returns_sum_credit_total - (float) $p->payments_sum_amount));

            $cards[] = ['label' => 'Purchases this month', 'value' => (float) $month, 'format' => 'money', 'accent' => '#F5B82E', 'href' => route('admin.purchases.index'), 'hint' => null];
            $cards[] = ['label' => 'You owe suppliers', 'value' => (float) $payable, 'format' => 'money', 'accent' => '#FF8FA3', 'href' => route('admin.purchases.index', ['status' => 'due']), 'hint' => null];
            $out['recentPurchases'] = Purchase::with('supplier')->latest('purchased_on')->latest('id')->limit(6)->get();
        }

        if ($stock) {
            $items = StockItem::with('product')->withBalance()->get();
            $value = $items->sum(fn ($i) => max(0, (int) $i->balance) * (float) $i->cost_price);
            $low = $items->filter(fn ($i) => $i->reorder_level > 0 && $i->balance <= $i->reorder_level);

            $cards[] = ['label' => 'Stock value (at cost)', 'value' => (float) $value, 'format' => 'money', 'accent' => '#17BEBB', 'href' => route('admin.stock.index'), 'hint' => null];
            $cards[] = ['label' => 'Items to reorder', 'value' => $low->count(), 'format' => 'number', 'accent' => '#E4572E', 'href' => route('admin.stock.index', ['status' => 'low']), 'hint' => 'at or below reorder level'];
            $out['lowStock'] = $low->sortBy('balance')->take(8)->values();
        }

        $out['cards'] = $cards;

        return $out;
    }

    private function charts($user)
    {
        return collect([
            ['key' => 'sales_purchases', 'title' => 'Sales and purchases, last 12 months', 'perm' => 'sales.manage', 'wide' => true],
            ['key' => 'top_products', 'title' => 'Best sellers, last 90 days', 'perm' => 'sales.manage', 'wide' => false],
            ['key' => 'sales_daily', 'title' => 'Daily sales, last 30 days', 'perm' => 'sales.manage', 'wide' => true],
            ['key' => 'stock_status', 'title' => 'Stock health', 'perm' => 'inventory.view', 'wide' => false],
            ['key' => 'partner_clicks', 'title' => 'Clicks on recommended links, last 30 days', 'perm' => 'partners.manage', 'wide' => true],
        ])->filter(fn ($c) => $user->can($c['perm']))->values();
    }

    private function quickActions($user): array
    {
        return array_values(array_filter([
            $user->can('sales.manage') ? ['New sale', route('admin.sales.create')] : null,
            $user->can('purchases.manage') ? ['New purchase', route('admin.purchases.create')] : null,
            $user->can('products.create') ? ['Add product', route('admin.products.create')] : null,
            $user->can('blog.manage') ? ['Write article', route('admin.blog.create')] : null,
            $user->can('inventory.view') ? ['Check stock', route('admin.stock.index')] : null,
            $user->can('inquiries.manage') ? ['Inquiries', route('admin.inquiries.index')] : null,
        ]));
    }
}
