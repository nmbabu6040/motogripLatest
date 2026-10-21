<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupMarkTested extends Command
{
    protected $signature = 'motogrip:backup-tested {id}';
    protected $description = 'Record that a restore of this backup was successfully tested in a safe environment';

    public function handle(): int
    {
        if (! $this->confirm('Confirm you restored this backup into a test environment and checked the result.')) {
            return self::SUCCESS;
        }
        DB::table('backup_records')->where('id', $this->argument('id'))->update(['restore_tested_at' => now(), 'updated_at' => now()]);
        $this->info('Recorded.');

        return self::SUCCESS;
    }
}
