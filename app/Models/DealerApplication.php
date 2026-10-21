<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DealerApplication extends Model
{
    use SoftDeletes;

    public const STATUSES = ['submitted', 'under_review', 'more_info', 'approved', 'rejected'];
    public const TYPES = ['dealer' => 'Dealer', 'distributor' => 'Distributor', 'workshop' => 'Workshop', 'fleet' => 'Fleet operator', 'other' => 'Other'];

    protected $fillable = ['reference', 'tracking_token_hash', 'business_name', 'contact_name', 'email', 'phone', 'district', 'city', 'address', 'business_type', 'years_in_business', 'message', 'status', 'assigned_to', 'reviewed_by', 'reviewed_at', 'follow_up_on', 'consent_at', 'ip_hash'];
    protected $hidden = ['tracking_token_hash'];
    protected $casts = ['reviewed_at' => 'datetime', 'follow_up_on' => 'date', 'consent_at' => 'datetime'];

    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function notes(): MorphMany { return $this->morphMany(InternalNote::class, 'noteable')->latest(); }
}
