<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = ['email', 'locale', 'token_hash', 'confirmed_at', 'unsubscribed_at'];
    protected $casts = ['confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
}
