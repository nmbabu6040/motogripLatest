<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    public $timestamps = false;
    protected $fillable = ['chat_conversation_id', 'sender', 'user_id', 'body'];
    protected $casts = ['created_at' => 'datetime'];

    public function conversation(): BelongsTo { return $this->belongsTo(ChatConversation::class, 'chat_conversation_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
