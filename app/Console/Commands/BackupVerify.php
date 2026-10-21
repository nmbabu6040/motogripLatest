<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupVerify extends Command
{
    protected $signature = 'motogrip:backup-verify {id? : Backup record id (default: latest succeeded)}';
    protected $description = 'Verify a backup archive: checksum, password and content';

    public function handle(BackupService $service): int
    {
        $id = $this->argument('id') ?? DB::table('backup_records')->where('status', 'succeeded')->latest('id')->value('id');
        if (! $id) {
            $this->warn('No succeeded backup to verify.');
            return self::SUCCESS;
        }
        [$ok, $msg] = $service->verify((int) $id);
        $ok ? $this->info($msg) : $this->error($msg);
        if (! $ok) {
            \App\Support\Notify::staff('Backup verification FAILED', ["Backup #{$id}: {$msg}"], route('admin.backups.index'));
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
