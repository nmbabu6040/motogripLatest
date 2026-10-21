<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ErasePerson extends Command
{
    protected $signature = 'motogrip:erase-person {email : Email address of the person who requested erasure}';
    protected $description = 'Erase or anonymise personal data held for an email address (data-subject request)';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        if (! $this->confirm("Permanently erase personal data for {$email}?")) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($email) {
            $this->line('inquiries deleted: '.DB::table('inquiries')->whereRaw('LOWER(email) = ?', [$email])->delete());
            $this->line('dealer applications deleted: '.DB::table('dealer_applications')->whereRaw('LOWER(email) = ?', [$email])->delete());
            $this->line('newsletter rows deleted: '.DB::table('newsletter_subscribers')->whereRaw('LOWER(email) = ?', [$email])->delete());
            $this->line('guest comments anonymised: '.DB::table('comments')->whereRaw('LOWER(guest_email) = ?', [$email])->update(['guest_name' => 'Deleted', 'guest_email' => null, 'ip_hash' => null]));
        });

        activity('admin')->withProperties(['note' => 'personal data erased on request'])->log('privacy.erasure');
        $this->info('Done. Staff and customer user accounts are removed separately from the admin panel.');

        return self::SUCCESS;
    }
}
