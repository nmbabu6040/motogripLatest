<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/** Wraps spatie/laravel-backup and keeps an auditable record of every run. */
class BackupService
{
    public function disk(): string
    {
        return config('motogrip.backups.disk');
    }

    // public function configured(): bool
    // {
    //     return filled(env('BACKUP_ARCHIVE_PASSWORD'));
    // }

    public function configured(): bool
    {
        return filled(config('backup.backup.password'));
    }

    public function run(string $type, ?int $userId = null, ?int $recordId = null): int
    {
        $recordId ??= DB::table('backup_records')->insertGetId(['type' => $type, 'disk' => $this->disk(), 'status' => 'running', 'started_at' => now(), 'triggered_by' => $userId, 'created_at' => now(), 'updated_at' => now()]);

        try {
            if (! $this->configured()) {
                throw new \RuntimeException('BACKUP_ARCHIVE_PASSWORD is not set, so archives would be unencrypted. Backup refused.');
            }

            $disk = Storage::disk($this->disk());
            $before = collect($disk->allFiles(config('backup.backup.name')))->all();

            $code = Artisan::call('backup:run', ['--only-db' => $type === 'database', '--only-files' => $type === 'media', '--disable-notifications' => true]);
            if ($code !== 0) {
                throw new \RuntimeException('backup:run failed: ' . trim(Artisan::output()));
            }

            $new = collect($disk->allFiles(config('backup.backup.name')))->diff($before)->sortByDesc(fn($f) => $disk->lastModified($f))->first();
            if (! $new) {
                throw new \RuntimeException('Backup finished but no new archive was found.');
            }

            DB::table('backup_records')->where('id', $recordId)->update([
                'path' => $new,
                'size' => $disk->size($new),
                'checksum' => $this->checksum($new),
                'status' => 'succeeded',
                'finished_at' => now(),
                'updated_at' => now(),
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            DB::table('backup_records')->where('id', $recordId)->update(['status' => 'failed', 'finished_at' => now(), 'updated_at' => now(), 'error' => mb_substr($e->getMessage(), 0, 2000)]);
            \App\Support\Notify::staff('Backup FAILED', ['A ' . $type . ' backup failed.', mb_substr($e->getMessage(), 0, 500)], route('admin.backups.index'));
            report($e);
        }

        return $recordId;
    }

    /** Integrity check: checksum matches, the archive opens with the password, and the expected content is inside. */
    public function verify(int $recordId): array
    {
        $r = DB::table('backup_records')->find($recordId);
        if (! $r || $r->status !== 'succeeded' || ! $r->path) {
            return [false, 'Only succeeded backups can be verified.'];
        }
        if ($this->checksum($r->path) !== $r->checksum) {
            return [false, 'Checksum mismatch: the archive changed after it was created.'];
        }

        $tmp = tempnam(sys_get_temp_dir(), 'mgbk');
        try {
            $in = Storage::disk($r->disk)->readStream($r->path);
            $out = fopen($tmp, 'wb');
            stream_copy_to_stream($in, $out);
            fclose($out);
            $zip = new ZipArchive;
            if ($zip->open($tmp) !== true) {
                return [false, 'The archive could not be opened.'];
            }
            $zip->setPassword((string) env('BACKUP_ARCHIVE_PASSWORD'));
            $hasDump = false;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'db-dumps/')) {
                    $hasDump = true;
                    $stream = $zip->getStream($name);
                    if (! $stream || strlen((string) fread($stream, 1024)) === 0) {
                        return [false, 'The database dump could not be read (wrong password or corrupt archive).'];
                    }
                    fclose($stream);
                }
            }
            $zip->close();
            if ($r->type !== 'media' && ! $hasDump) {
                return [false, 'No database dump was found in the archive.'];
            }
        } finally {
            @unlink($tmp);
        }

        DB::table('backup_records')->where('id', $recordId)->update(['verified_at' => now(), 'updated_at' => now()]);

        return [true, 'Backup verified.'];
    }

    private function checksum(string $path): string
    {
        $ctx = hash_init('sha256');
        hash_update_stream($ctx, Storage::disk($this->disk())->readStream($path));

        return hash_final($ctx);
    }
}
