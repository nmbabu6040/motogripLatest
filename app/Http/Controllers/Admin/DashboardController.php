<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        // Every figure is a live database count; a card is shown only if the viewer may see that data.
        $definitions = [
            ['Products', 'products.view', fn () => DB::table('products')->whereNull('deleted_at')->count()],
            ['Published products', 'products.view', fn () => DB::table('products')->whereNull('deleted_at')->where('status', 'published')->count()],
            ['Draft products', 'products.view', fn () => DB::table('products')->whereNull('deleted_at')->where('status', 'draft')->count()],
            ['Product categories', 'products.view', fn () => DB::table('product_categories')->whereNull('deleted_at')->count()],
            ['Registered users', 'users.manage', fn () => DB::table('users')->count()],
            ['Dealer applications', 'dealers.manage', fn () => DB::table('dealer_applications')->whereNull('deleted_at')->count()],
            ['New inquiries', 'inquiries.manage', fn () => DB::table('inquiries')->whereNull('deleted_at')->where('status', 'new')->count()],
            ['Published articles', 'blog.manage', fn () => DB::table('blog_posts')->whereNull('deleted_at')->where('status', 'published')->count()],
            ['Comments awaiting review', 'comments.moderate', fn () => DB::table('comments')->whereNull('deleted_at')->where('status', 'pending')->count()],
            ['Document downloads', 'documents.manage', fn () => (int) DB::table('document_downloads')->sum('downloads')],
        ];

        $cards = collect($definitions)
            ->filter(fn ($d) => $user->can($d[1]))
            ->map(fn ($d) => ['label' => $d[0], 'value' => ($d[2])()])
            ->values();

        $from = $request->date('from') ?? now()->subDays(29);
        $to = $request->date('to') ?? now();
        $lists = [
            'recentInquiries' => $user->can('inquiries.view-all') ? DB::table('inquiries')->whereNull('deleted_at')->latest()->limit(5)->get(['id', 'name', 'type', 'status', 'created_at']) : collect(),
            'popularProducts' => $user->can('analytics.view') ? DB::table('product_views')->join('products', 'products.id', '=', 'product_views.product_id')->where('viewed_on', '>=', now()->subDays(30)->toDateString())
                ->groupBy('products.id', 'products.sku')->orderByRaw('SUM(views) DESC')->limit(5)->get(['products.sku', DB::raw('SUM(views) as views')]) : collect(),
            'popularPosts' => $user->can('analytics.view') ? DB::table('blog_post_views')->join('blog_posts', 'blog_posts.id', '=', 'blog_post_views.blog_post_id')->where('viewed_on', '>=', now()->subDays(30)->toDateString())
                ->groupBy('blog_posts.id', 'blog_posts.slug')->orderByRaw('SUM(views) DESC')->limit(5)->get(['blog_posts.slug', DB::raw('SUM(views) as views')]) : collect(),
            'recentActivity' => $user->can('audit.view') ? \Spatie\Activitylog\Models\Activity::with('causer')->latest()->limit(8)->get() : collect(),
        ];

        return view('admin.dashboard', ['cards' => $cards, 'from' => $from->toDateString(), 'to' => $to->toDateString()] + $lists);
    }
}
