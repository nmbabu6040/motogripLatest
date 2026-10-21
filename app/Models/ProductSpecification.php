<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ProductSpecification extends Model
{
    use HasTranslations;

    protected $fillable = ['product_id', 'name', 'value', 'unit', 'test_method', 'is_typical', 'is_verified', 'sort_order'];
    public array $translatable = ['name'];
    protected $casts = ['is_typical' => 'boolean', 'is_verified' => 'boolean'];

    /** Only verified rows are ever shown on the public site. */
    public function scopeVerified($q) { return $q->where('is_verified', true); }
}
