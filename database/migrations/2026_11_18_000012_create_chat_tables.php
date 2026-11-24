<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $t) {
            $t->id();
            $t->char('token_hash', 64)->unique();            // the visitor keeps the secret token; only its hash is stored
            $t->string('visitor_name', 120)->nullable();
            $t->string('visitor_email')->nullable();
            $t->string('locale', 5)->default('en');
            $t->string('status', 10)->default('open')->index();   // open | closed
            $t->unsignedInteger('staff_unread')->default(0);
            $t->unsignedInteger('visitor_unread')->default(0);
            $t->timestamp('last_message_at')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('chat_conversation_id')->constrained()->cascadeOnDelete();
            $t->string('sender', 8);                          // visitor | staff
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->text('body');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['chat_conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
};
