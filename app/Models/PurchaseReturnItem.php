<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['purchase_return_id', 'purchase_item_id', 'stock_item_id', 'quantity', 'unit_cost', 'line_total'];
    protected $casts = ['unit_cost' => 'decimal:2', 'line_total' => 'decimal:2'];

    public function stockItem(): BelongsTo { return $this->belongsTo(StockItem::class); }
}
