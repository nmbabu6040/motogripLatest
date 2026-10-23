<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasTranslations, SoftDeletes;

    public const LAYOUTS = [
        'plain' => 'Text only',
        'image_right' => 'Text on the left, image on the right',
        'image_left' => 'Image on the left, text on the right',
        'banner' => 'Wide image on top, text below',
    ];

    protected $fillable = ['slug', 'title', 'body', 'seo_title', 'seo_description', 'canonical_url', 'noindex', 'status', 'published_at', 'image', 'layout', 'show_in_menu', 'subtitle'];
    public array $translatable = ['title', 'subtitle', 'body', 'seo_title', 'seo_description'];
    protected $casts = ['noindex' => 'boolean', 'published_at' => 'datetime', 'show_in_menu' => 'boolean'];

    public function scopePublished($q) { return $q->where('status', 'published'); }
    public function getRouteKeyName(): string { return 'slug'; }
}
