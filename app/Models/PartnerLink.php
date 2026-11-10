<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PartnerLink extends Model
{
    use HasTranslations;

    public const STORES = ['amazon' => 'Amazon', 'aliexpress' => 'AliExpress', 'other' => 'Other store'];

    protected $fillable = ['store', 'title', 'description', 'url', 'image', 'sort_order', 'is_published'];
    public array $translatable = ['title', 'description'];
    protected $casts = ['is_published' => 'boolean'];

    public function scopePublished($q) { return $q->where('is_published', true); }
}
