<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunBackupJob;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{
    public function index(BackupService $service)
    {
        $records = DB::table('backup_records')->latest('id')->limit(50)->get();

        return view('admin.backups.index', [
            'records' => $records, 'configured' => $service->configured(), 'disk' => $service->disk(),
            'lastGood' => DB::table('backup_records')->where('status', 'succeeded')->latest('id')->first(),
            'lastTested' => DB::table('backup_records')->whereNotNull('restore_tested_at')->latest('restore_tested_at')->first(),
        ]);
    }

    public function store(Request $request, BackupService $service)
    {
        $type = $request->validate(['type' => ['required', 'in:database,media']])['type'];

        if (! $service->configured()) {
            return back()->withErrors(['type' => 'Backups are disabled until BACKUP_ARCHIVE_PASSWORD is set in the server environment.']);
        }

        $id = DB::table('backup_records')->insertGetId(['type' => $type, 'disk' => $service->disk(), 'status' => 'running', 'started_at' => now(), 'triggered_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        RunBackupJob::dispatch($type, $id);
        activity('admin')->causedBy($request->user())->withProperties(['type' => $type, 'record' => $id])->log('backup.requested');

        return back()->with('status', 'Backup queued. Make sure a queue worker is running.');
    }

    public function verify(Request $request, BackupService $service, int $record)
    {
        [$ok, $msg] = $service->verify($record);
        activity('admin')->causedBy($request->user())->withProperties(['record' => $record, 'ok' => $ok])->log('backup.verified');

        return back()->with($ok ? 'status' : 'verify_error', $msg);
    }
}
