<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    protected $fillable = ['affiliate_conversion_id', 'amount', 'currency', 'status', 'approved_by', 'approved_at', 'paid_at', 'adjustment_reason'];
    protected $casts = ['approved_at' => 'datetime', 'paid_at' => 'datetime', 'amount' => 'decimal:2'];

    public function conversion(): BelongsTo { return $this->belongsTo(AffiliateConversion::class, 'affiliate_conversion_id'); }
}
