<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    public $timestamps = false;
    protected $fillable = ['blog_post_id', 'user_id', 'visitor_hash'];
}
