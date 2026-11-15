<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlinePayment extends Model
{
    protected $fillable = ['sale_id', 'tran_id', 'amount', 'currency', 'status', 'val_id', 'bank_tran_id', 'payer_name', 'payer_email', 'payer_phone', 'note', 'paid_at'];
    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime'];

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
}
