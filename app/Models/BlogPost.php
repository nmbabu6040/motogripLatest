<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class BlogPost extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia, SoftDeletes;

    public const STATUSES = ['draft', 'review', 'scheduled', 'published'];

    protected $fillable = ['author_id', 'blog_category_id', 'slug', 'title', 'excerpt', 'body', 'seo_title', 'seo_description', 'canonical_url', 'noindex', 'status', 'is_featured', 'reading_time_minutes', 'published_at'];
    public array $translatable = ['title', 'excerpt', 'body', 'seo_title', 'seo_description'];
    protected $casts = ['noindex' => 'boolean', 'is_featured' => 'boolean', 'published_at' => 'datetime'];

    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
    public function category(): BelongsTo { return $this->belongsTo(BlogCategory::class, 'blog_category_id'); }
    public function tags(): BelongsToMany { return $this->belongsToMany(BlogTag::class, 'blog_post_tag'); }
    public function comments(): HasMany { return $this->hasMany(Comment::class); }
    public function likes(): HasMany { return $this->hasMany(Like::class); }
    public function revisions(): HasMany { return $this->hasMany(BlogPostRevision::class)->latest('created_at'); }

    public function scopePublished($q)
    {
        return $q->where('status', 'published')->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at && $this->published_at->isPast();
    }

    public static function readingTime(?string $html): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $html)) / 200));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured')->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->format('webp')->width(640)->nonQueued();
        $this->addMediaConversion('large')->format('webp')->width(1400)->nonQueued();
    }

    public function getRouteKeyName(): string { return 'slug'; }
}
