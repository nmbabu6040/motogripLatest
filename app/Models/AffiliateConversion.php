<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AffiliateConversion extends Model
{
    protected $fillable = ['affiliate_id', 'referral_click_id', 'inquiry_id', 'dealer_application_id', 'type', 'status'];

    public function affiliate(): BelongsTo { return $this->belongsTo(Affiliate::class); }
    public function click(): BelongsTo { return $this->belongsTo(ReferralClick::class, 'referral_click_id'); }
    public function commission(): HasOne { return $this->hasOne(AffiliateCommission::class); }
}
