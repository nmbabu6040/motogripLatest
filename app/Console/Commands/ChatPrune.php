<?php

namespace App\Console\Commands;

use App\Models\ChatConversation;
use Illuminate\Console\Command;

class ChatPrune extends Command
{
    protected $signature = 'chat:prune {--days=90 : Delete conversations with no message for this many days}';
    protected $description = 'Delete old live chat conversations (a privacy retention rule)';

    public function handle(): int
    {
        $n = ChatConversation::where('last_message_at', '<', now()->subDays((int) $this->option('days')))->delete();
        $this->info("Deleted {$n} old conversation(s).");

        return self::SUCCESS;
    }
}
