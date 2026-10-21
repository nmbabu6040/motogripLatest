<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentReport extends Model
{
    protected $fillable = ['comment_id', 'user_id', 'ip_hash', 'reason', 'resolved_at'];
    protected $casts = ['resolved_at' => 'datetime'];
}
