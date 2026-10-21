<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class ProductCategory extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = ['parent_id', 'slug', 'name', 'description', 'seo_title', 'seo_description', 'sort_order', 'is_active'];
    public array $translatable = ['name', 'description', 'seo_title', 'seo_description'];
    protected $casts = ['is_active' => 'boolean'];

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order'); }
    public function products(): HasMany { return $this->hasMany(Product::class); }

    public function scopeActive($q) { return $q->where('is_active', true); }

    /** This category's id plus all descendant ids (two levels is the supported depth). */
    public function selfAndDescendantIds(): array
    {
        return $this->children()->pluck('id')->prepend($this->id)->all();
    }

    public function getRouteKeyName(): string { return 'slug'; }
}
