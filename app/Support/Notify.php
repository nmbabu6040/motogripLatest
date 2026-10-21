<?php

namespace App\Support;

use App\Mail\StaffNotice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Notify
{
    /** Staff mailbox from settings. Returns false (and logs) when none is configured, so the caller can still persist the record. */
    public static function staff(string $subject, array $lines, ?string $url = null): bool
    {
        $to = Settings::get('contact', 'email');
        if (! $to) {
            Log::warning('Staff notification skipped: no support email configured in settings.', ['subject' => $subject]);
            return false;
        }
        try {
            Mail::to($to)->queue(new StaffNotice($subject, $lines, $url));
            return true;
        } catch (\Throwable $e) {
            Log::error('Staff notification could not be queued.', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public static function person(string $to, string $subject, array $lines, ?string $url = null): void
    {
        try {
            Mail::to($to)->queue(new StaffNotice($subject, $lines, $url));
        } catch (\Throwable $e) {
            Log::error('Email could not be queued.', ['error' => $e->getMessage()]);
        }
    }

    public static function ipHash(?string $ip): string
    {
        return hash_hmac('sha256', (string) $ip, config('app.key'));
    }
}
