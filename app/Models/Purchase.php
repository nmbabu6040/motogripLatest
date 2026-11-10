<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    protected $fillable = ['supplier_id', 'purchased_on', 'status', 'subtotal', 'discount', 'total', 'notes', 'created_by'];
    protected $casts = ['purchased_on' => 'date', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2'];

    public function supplier(): BelongsTo { return $this->belongsTo(Party::class, 'supplier_id')->withTrashed(); }
    public function items(): HasMany { return $this->hasMany(PurchaseItem::class); }
    public function returns(): HasMany { return $this->hasMany(PurchaseReturn::class); }
    public function payments(): MorphMany { return $this->morphMany(Payment::class, 'payable'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function number(): string { return 'PUR-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT); }
    public function isVoid(): bool { return $this->status === 'void'; }
    public function paidAmount(): float { return (float) $this->payments()->sum('amount'); }
    public function returnedAmount(): float { return (float) $this->returns()->sum('credit_total'); }
    /** What we still owe the supplier: total, minus goods returned, minus what has been paid. Negative means the supplier owes us. */
    public function balance(): float { return round((float) $this->total - $this->returnedAmount() - $this->paidAmount(), 2); }
    public function dueAmount(): float { return max(0, $this->balance()); }
}
