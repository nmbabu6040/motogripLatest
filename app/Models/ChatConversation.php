<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    protected $fillable = ['token_hash', 'visitor_name', 'visitor_email', 'locale', 'status', 'staff_unread', 'visitor_unread', 'last_message_at'];
    protected $casts = ['last_message_at' => 'datetime'];

    public function messages(): HasMany { return $this->hasMany(ChatMessage::class); }
    public function label(): string { return $this->visitor_name ?: 'Visitor #'.$this->id; }
}
