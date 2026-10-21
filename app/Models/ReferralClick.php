<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralClick extends Model
{
    public $timestamps = false;
    protected $fillable = ['affiliate_id', 'affiliate_campaign_id', 'token_hash', 'landing_path', 'visitor_hash', 'clicked_at'];
    protected $casts = ['clicked_at' => 'datetime'];

    public function affiliate(): BelongsTo { return $this->belongsTo(Affiliate::class); }
}
