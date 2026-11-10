<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    protected $fillable = ['sale_id', 'returned_on', 'reason', 'refund_total', 'created_by'];
    protected $casts = ['returned_on' => 'date', 'refund_total' => 'decimal:2'];

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function items(): HasMany { return $this->hasMany(SaleReturnItem::class); }
    public function number(): string { return 'SRN-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT); }
}
