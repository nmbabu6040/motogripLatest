<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inquiry extends Model
{
    use SoftDeletes;

    public const TYPES = ['contact' => 'General contact', 'product' => 'Product inquiry', 'quotation' => 'Quotation request', 'corporate' => 'Corporate sales', 'bulk' => 'Bulk purchase', 'workshop_fleet' => 'Workshop or fleet'];
    public const STATUSES = ['new', 'in_progress', 'responded', 'closed', 'spam'];

    protected $fillable = ['type', 'product_id', 'name', 'email', 'phone', 'company', 'message', 'status', 'assigned_to', 'follow_up_on', 'locale', 'consent_at', 'ip_hash'];
    protected $casts = ['follow_up_on' => 'date', 'consent_at' => 'datetime'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function notes(): MorphMany { return $this->morphMany(InternalNote::class, 'noteable')->latest(); }
}
