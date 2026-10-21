<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use SoftDeletes;

    protected $fillable = ['blog_post_id', 'parent_id', 'user_id', 'guest_name', 'guest_email', 'body', 'status', 'ip_hash'];
    protected $hidden = ['guest_email', 'ip_hash'];

    public function post(): BelongsTo { return $this->belongsTo(BlogPost::class, 'blog_post_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function replies(): HasMany { return $this->hasMany(self::class, 'parent_id')->where('status', 'approved')->oldest(); }
    public function reports(): HasMany { return $this->hasMany(CommentReport::class); }

    public function authorName(): string { return $this->user?->name ?? $this->guest_name ?? 'Guest'; }
}
