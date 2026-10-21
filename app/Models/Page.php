<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasTranslations, SoftDeletes;

    protected $fillable = ['slug', 'title', 'body', 'seo_title', 'seo_description', 'canonical_url', 'noindex', 'status', 'published_at'];
    public array $translatable = ['title', 'body', 'seo_title', 'seo_description'];
    protected $casts = ['noindex' => 'boolean', 'published_at' => 'datetime'];

    public function scopePublished($q) { return $q->where('status', 'published'); }
    public function getRouteKeyName(): string { return 'slug'; }
}
