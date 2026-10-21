<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductApproval extends Model
{
    protected $fillable = ['product_id', 'type', 'name', 'reference', 'evidence_document_id', 'verified_at'];
    protected $casts = ['verified_at' => 'datetime'];

    /** An approval or OEM claim is public only after an admin marks it verified. */
    public function scopeVerified($q) { return $q->whereNotNull('verified_at'); }
}
