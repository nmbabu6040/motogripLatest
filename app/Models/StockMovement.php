<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPES = ['purchase' => 'Purchase', 'sale' => 'Sale', 'sale_return' => 'Sale return', 'purchase_return' => 'Purchase return', 'adjustment' => 'Adjustment', 'void' => 'Voided document'];

    public $timestamps = false;
    protected $fillable = ['stock_item_id', 'quantity', 'type', 'reference_type', 'reference_id', 'moved_on', 'note', 'user_id'];
    protected $casts = ['moved_on' => 'date', 'created_at' => 'datetime'];

    public function stockItem(): BelongsTo { return $this->belongsTo(StockItem::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
