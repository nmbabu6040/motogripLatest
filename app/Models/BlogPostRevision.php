<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPostRevision extends Model
{
    public $timestamps = false;
    protected $fillable = ['blog_post_id', 'user_id', 'snapshot'];
    protected $casts = ['snapshot' => 'array', 'created_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
