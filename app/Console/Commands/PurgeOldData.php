<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeOldData extends Command
{
    protected $signature = 'motogrip:purge-old-data';
    protected $description = 'Apply data-retention rules (inquiries, applications, newsletter, hashed IPs)';

    public function handle(): int
    {
        $r = config('motogrip.retention');
        $n = [];

        $n['inquiries'] = DB::table('inquiries')->where('created_at', '<', now()->subMonths($r['inquiries']))->whereIn('status', ['closed', 'responded'])->delete();
        $n['spam'] = DB::table('inquiries')->where('status', 'spam')->where('created_at', '<', now()->subMonths($r['spam']))->delete();
        $n['dealer_applications'] = DB::table('dealer_applications')->where('status', 'rejected')->where('created_at', '<', now()->subMonths($r['dealer_rejected']))->delete();
        $n['unsubscribed'] = DB::table('newsletter_subscribers')->whereNotNull('unsubscribed_at')->where('unsubscribed_at', '<', now()->subMonths($r['unsubscribed']))->delete();

        $cut = now()->subDays($r['ip_hash_days']);
        foreach (['inquiries', 'dealer_applications', 'comments', 'comment_reports'] as $table) {
            $n["ip:$table"] = DB::table($table)->whereNotNull('ip_hash')->where('created_at', '<', $cut)->update(['ip_hash' => null]);
        }
        $n['referral_clicks'] = DB::table('referral_clicks')->whereNotNull('visitor_hash')->where('clicked_at', '<', $cut)->update(['visitor_hash' => null]);

        foreach ($n as $k => $v) {
            $this->line("{$k}: {$v}");
        }

        return self::SUCCESS;
    }
}
