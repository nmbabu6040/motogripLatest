<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class BlogCategory extends Model
{
    use HasTranslations;

    protected $fillable = ['slug', 'name', 'description'];
    public array $translatable = ['name', 'description'];

    public function getRouteKeyName(): string { return 'slug'; }
}
