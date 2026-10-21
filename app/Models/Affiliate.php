<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    protected $fillable = ['user_id', 'code', 'status', 'terms_accepted_at'];
    protected $casts = ['terms_accepted_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function clicks(): HasMany { return $this->hasMany(ReferralClick::class); }
    public function conversions(): HasMany { return $this->hasMany(AffiliateConversion::class); }

    public function link(?AffiliateCampaign $campaign = null): string
    {
        return url('/').'?ref='.$this->code.($campaign ? '&camp='.$campaign->id : '');
    }
}
