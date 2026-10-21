<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /** Minimal status for uptime monitors. No versions, paths or credentials are exposed. */
    public function __invoke()
    {
        $checks = [];
        try { DB::select('select 1'); $checks['database'] = true; } catch (\Throwable) { $checks['database'] = false; }
        try { Cache::put('health', 1, 10); $checks['cache'] = Cache::get('health') === 1; } catch (\Throwable) { $checks['cache'] = false; }
        $checks['storage'] = is_writable(storage_path('app'));

        $ok = ! in_array(false, $checks, true);

        return response()->json(['status' => $ok ? 'ok' : 'degraded', 'checks' => $checks], $ok ? 200 : 503, ['Cache-Control' => 'no-store']);
    }
}
