<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sale extends Model
{
    protected $fillable = ['customer_id', 'customer_name', 'sold_on', 'status', 'subtotal', 'discount', 'tax_percent', 'tax_amount', 'total', 'notes', 'created_by'];
    protected $casts = ['sold_on' => 'date', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'tax_percent' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total' => 'decimal:2'];

    public function customer(): BelongsTo { return $this->belongsTo(Party::class, 'customer_id')->withTrashed(); }
    public function items(): HasMany { return $this->hasMany(SaleItem::class); }
    public function returns(): HasMany { return $this->hasMany(SaleReturn::class); }
    public function challans(): HasMany { return $this->hasMany(Challan::class); }
    public function payments(): MorphMany { return $this->morphMany(Payment::class, 'payable'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function number(): string { return 'INV-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT); }
    public function isVoid(): bool { return $this->status === 'void'; }
    public function customerLabel(): string { return $this->customer?->name ?? ($this->customer_name ?: 'Walk-in customer'); }
    public function paidAmount(): float { return (float) $this->payments()->sum('amount'); }
    public function returnedAmount(): float { return (float) $this->returns()->sum('refund_total'); }
    /** What the customer still owes: total, minus goods returned, minus payments. Negative means we owe them a refund. */
    public function balance(): float { return round((float) $this->total - $this->returnedAmount() - $this->paidAmount(), 2); }
    public function dueAmount(): float { return max(0, $this->balance()); }
}
