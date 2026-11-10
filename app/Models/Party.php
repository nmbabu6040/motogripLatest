<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    use SoftDeletes;

    public const KINDS = ['customer' => 'Customer', 'supplier' => 'Supplier'];

    protected $fillable = ['kind', 'name', 'phone', 'email', 'address', 'notes'];

    public function scopeKind($q, string $kind) { return $q->where('kind', $kind); }
}
