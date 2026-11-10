<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challan extends Model
{
    protected $fillable = ['sale_id', 'delivered_on', 'receiver_name', 'receiver_phone', 'delivery_address', 'vehicle_no', 'driver_name', 'notes', 'created_by'];
    protected $casts = ['delivered_on' => 'date'];

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function items(): HasMany { return $this->hasMany(ChallanItem::class); }
    public function number(): string { return 'CH-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT); }
}
