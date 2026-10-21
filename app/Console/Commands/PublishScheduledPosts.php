<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    protected $signature = 'blog:publish-scheduled';
    protected $description = 'Publish scheduled blog posts whose publish time has arrived';

    public function handle(): int
    {
        $count = BlogPost::where('status', 'scheduled')->where('published_at', '<=', now())->update(['status' => 'published']);
        $this->info("Published {$count} scheduled post(s).");

        return self::SUCCESS;
    }
}
