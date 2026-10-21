<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class BlogTag extends Model
{
    use HasTranslations;

    protected $fillable = ['slug', 'name'];
    public array $translatable = ['name'];

    public function getRouteKeyName(): string { return 'slug'; }
}
