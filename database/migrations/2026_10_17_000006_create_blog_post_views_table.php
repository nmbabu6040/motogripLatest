<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Daily aggregate only: no IPs, no user ids. Feeds the "most read articles" report.
    public function up(): void
    {
        Schema::create('blog_post_views', function (Blueprint $t) {
            $t->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $t->date('viewed_on');
            $t->unsignedInteger('views')->default(0);
            $t->primary(['blog_post_id', 'viewed_on']);
        });
    }

    public function down(): void { Schema::dropIfExists('blog_post_views'); }
};
