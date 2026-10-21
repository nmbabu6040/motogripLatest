<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Database restore. Command-line only, on purpose: it needs shell access, a verified archive and the database name typed back.
 * It takes a fresh safety backup first and keeps the site in maintenance mode while importing.
 */
class BackupRestore extends Command
{
    protected $signature = 'motogrip:restore {id : Backup record id} {--confirm-db= : Type the exact database name to proceed}';
    protected $description = 'Restore the database from a verified backup (destructive)';

    public function handle(BackupService $service): int
    {
        $r = DB::table('backup_records')->find($this->argument('id'));
        $db = config('database.connections.'.config('database.default').'.database');

        if (! $r || ! $r->verified_at) {
            $this->error('Only verified backups can be restored. Run motogrip:backup-verify first.');
            return self::FAILURE;
        }
        if ($this->option('confirm-db') !== $db) {
            $this->error("Refusing to continue. Re-run with --confirm-db={$db} to confirm you intend to overwrite this database.");
            return self::FAILURE;
        }
        if (config('database.default') !== 'mysql') {
            $this->error('This command supports MySQL only.');
            return self::FAILURE;
        }
        if (! $this->confirm("This OVERWRITES database '{$db}' with backup #{$r->id} from {$r->finished_at}. Continue?")) {
            return self::SUCCESS;
        }

        $this->info('Taking a safety backup of the current state first…');
        $safetyId = $service->run('database');
        if (DB::table('backup_records')->where('id', $safetyId)->value('status') !== 'succeeded') {
            $this->error('The safety backup failed, so the restore was cancelled.');
            return self::FAILURE;
        }

        Artisan::call('down');
        $tmp = tempnam(sys_get_temp_dir(), 'mgrs');
        $sql = tempnam(sys_get_temp_dir(), 'mgsql');
        try {
            $in = Storage::disk($r->disk)->readStream($r->path);
            $out = fopen($tmp, 'wb');
            stream_copy_to_stream($in, $out);
            fclose($out);
            $zip = new ZipArchive;
            $zip->open($tmp);
            $zip->setPassword((string) env('BACKUP_ARCHIVE_PASSWORD'));
            $dump = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (str_starts_with($zip->getNameIndex($i), 'db-dumps/')) { $dump = $zip->getNameIndex($i); break; }
            }
            if (! $dump) {
                throw new \RuntimeException('No database dump in archive.');
            }
            file_put_contents($sql, $zip->getFromName($dump));

            $c = config('database.connections.mysql');
            $p = Process::fromShellCommandline('mysql --host="$H" --port="$P" --user="$U" "$D" < "$F"', null, ['H' => $c['host'], 'P' => $c['port'], 'U' => $c['username'], 'D' => $db, 'F' => $sql, 'MYSQL_PWD' => $c['password']]);
            $p->setTimeout(3600)->mustRun();
            $this->info('Database restored.');
        } catch (\Throwable $e) {
            $this->error('Restore failed: '.$e->getMessage());
            $this->warn("A safety backup (#{$safetyId}) of the previous state exists.");
            return self::FAILURE;
        } finally {
            @unlink($tmp); @unlink($sql);
            Artisan::call('up');
        }

        return self::SUCCESS;
    }
}
