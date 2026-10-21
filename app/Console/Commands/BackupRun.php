<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupRun extends Command
{
    protected $signature = 'motogrip:backup {type=database : database|media}';
    protected $description = 'Create an encrypted backup and record it';

    public function handle(BackupService $service): int
    {
        $id = $service->run($this->argument('type'));
        $r = \DB::table('backup_records')->find($id);
        $this->line("Backup #{$id}: {$r->status}".($r->error ? " ({$r->error})" : ''));

        return $r->status === 'succeeded' ? self::SUCCESS : self::FAILURE;
    }
}
