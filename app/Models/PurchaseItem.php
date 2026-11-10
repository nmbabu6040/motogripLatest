<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['purchase_id', 'stock_item_id', 'quantity', 'unit_cost', 'line_total'];
    protected $casts = ['unit_cost' => 'decimal:2', 'line_total' => 'decimal:2'];

    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
    public function stockItem(): BelongsTo { return $this->belongsTo(StockItem::class); }
}
