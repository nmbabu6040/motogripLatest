<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ProductFeature extends Model
{
    use HasTranslations;

    protected $fillable = ['product_id', 'kind', 'text', 'sort_order'];
    public array $translatable = ['text'];
}
