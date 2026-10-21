<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class AuthorProfile extends Model
{
    use HasTranslations;

    protected $fillable = ['user_id', 'bio', 'social_links'];
    public array $translatable = ['bio'];
    protected $casts = ['social_links' => 'array'];
}
