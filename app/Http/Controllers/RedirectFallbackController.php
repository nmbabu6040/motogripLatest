<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RedirectFallbackController extends Controller
{
    /** Runs only for URLs that match no route: looks for an admin-managed redirect, otherwise 404. */
    public function __invoke(Request $request)
    {
        $path = '/'.ltrim($request->path(), '/');
        $row = DB::table('redirects')->where('from_path', $path)->where('is_active', true)->first();

        abort_unless($row, 404);

        DB::table('redirects')->where('id', $row->id)->increment('hits');

        return redirect()->to($row->to_url, $row->status_code);
    }
}
