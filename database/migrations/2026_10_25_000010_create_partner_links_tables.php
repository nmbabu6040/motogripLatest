<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Outbound affiliate links (Amazon, AliExpress, ...). The link is stored exactly as the store gave it.
        Schema::create('partner_links', function (Blueprint $t) {
            $t->id();
            $t->string('store', 20)->index();                    // amazon | aliexpress | other
            $t->json('title');
            $t->json('description')->nullable();
            $t->string('url', 2000);
            $t->string('image')->nullable();                     // relative URL on the public disk
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_published')->default(true)->index();
            $t->timestamps();
        });

        // Daily click counts only: no IP address, no user id.
        Schema::create('partner_link_clicks', function (Blueprint $t) {
            $t->foreignId('partner_link_id')->constrained()->cascadeOnDelete();
            $t->date('clicked_on');
            $t->unsignedInteger('clicks')->default(0);
            $t->primary(['partner_link_id', 'clicked_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_link_clicks');
        Schema::dropIfExists('partner_links');
    }
};
