<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function __invoke()
    {
        return view('admin.audit', ['entries' => Activity::with('causer')->latest()->paginate(30)]);
    }
}
