<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Product extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'product_category_id',
        'slug',
        'sku',
        'name',
        'short_description',
        'description',
        'handling_storage',
        'product_type',
        'application',
        'viscosity_grade',
        'api_classification',
        'iso_vg_grade',
        'seo_title',
        'seo_description',
        'canonical_url',
        'noindex',
        'status',
        'is_featured',
        'published_at',
    ];
    public array $translatable = [
        'name',
        'short_description',
        'description',
        'handling_storage',
        'seo_title',
        'seo_description'
    ];
    protected $casts = [
        'noindex' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order');
    }
    public function features(): HasMany
    {
        return $this->hasMany(ProductFeature::class)->orderBy('sort_order');
    }
    public function packaging(): HasMany
    {
        return $this->hasMany(PackagingOption::class)->orderBy('sort_order');
    }
    public function approvals(): HasMany
    {
        return $this->hasMany(ProductApproval::class);
    }
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function related(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_related', 'product_id', 'related_product_id')->withPivot('relation')->orderByPivot('sort_order');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published')->where(fn($w) => $w->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && (! $this->published_at || $this->published_at->isPast());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->format('webp')->width(480)->height(480)->nonQueued();
        $this->addMediaConversion('large')->format('webp')->width(1200)->nonQueued();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
