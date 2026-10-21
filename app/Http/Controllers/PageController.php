<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /** The catch-all slug route. Unknown slugs fall through to admin-managed redirects, then 404. */
    public function show(Request $request, string $page)
    {
        $model = Page::where('slug', $page)->first();

        if (! $model) {
            return app(RedirectFallbackController::class)($request);
        }
        abort_unless($model->status === 'published' || $request->user()?->can('pages.manage'), 404);

        return view('pages.show', ['page' => $model]);
    }
}
