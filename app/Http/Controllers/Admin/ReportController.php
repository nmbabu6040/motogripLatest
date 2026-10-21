<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Csv;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /** metric => [table, date column, value expression]. All first-party database data. */
    private const METRICS = [
        'product_views' => ['product_views', 'viewed_on', 'SUM(views)', 'Product views'],
        'article_views' => ['blog_post_views', 'viewed_on', 'SUM(views)', 'Article views'],
        'downloads' => ['document_downloads', 'downloaded_on', 'SUM(downloads)', 'Document downloads'],
        'inquiries' => ['inquiries', 'created_at', 'COUNT(*)', 'Inquiries'],
        'dealer_applications' => ['dealer_applications', 'created_at', 'COUNT(*)', 'Dealer applications'],
        'comments' => ['comments', 'created_at', 'COUNT(*)', 'Comments'],
        'likes' => ['likes', 'created_at', 'COUNT(*)', 'Likes'],
    ];

    public function data(Request $request)
    {
        abort_unless($request->user()->can('analytics.view'), 403);
        [$metric, $from, $to] = $this->params($request);

        return response()->json($this->series($metric, $from, $to));
    }

    public function export(Request $request)
    {
        abort_unless($request->user()->can('reports.export'), 403);
        [$metric, $from, $to] = $this->params($request);
        $s = $this->series($metric, $from, $to);
        activity('admin')->causedBy($request->user())->withProperties(['metric' => $metric])->log('report.exported');

        return Csv::download("{$metric}-{$from->toDateString()}-{$to->toDateString()}.csv", ['Date', $s['label']], array_map(null, $s['labels'], $s['values']));
    }

    private function params(Request $request): array
    {
        $d = $request->validate(['metric' => ['required', 'in:'.implode(',', array_keys(self::METRICS))], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $to = isset($d['to']) ? Carbon::parse($d['to'])->endOfDay() : now()->endOfDay();
        $from = isset($d['from']) ? Carbon::parse($d['from'])->startOfDay() : $to->copy()->subDays(29)->startOfDay();
        if ($from->gt($to) || $from->diffInDays($to) > 365) {
            $from = $to->copy()->subDays(29)->startOfDay();
        }

        return [$d['metric'], $from, $to];
    }

    private function series(string $metric, Carbon $from, Carbon $to): array
    {
        [$table, $col, $expr, $label] = self::METRICS[$metric];

        $rows = DB::table($table)->selectRaw("DATE({$col}) as d, {$expr} as v")->whereBetween($col, [$from->toDateString().' 00:00:00', $to->toDateString().' 23:59:59'])->groupBy('d')->pluck('v', 'd');

        $labels = $values = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $labels[] = $day->toDateString();
            $values[] = (int) ($rows[$day->toDateString()] ?? 0);
        }

        return ['label' => $label, 'labels' => $labels, 'values' => $values, 'total' => array_sum($values), 'source' => 'First-party database'];
    }
}
