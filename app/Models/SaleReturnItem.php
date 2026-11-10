<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['sale_return_id', 'sale_item_id', 'stock_item_id', 'quantity', 'unit_price', 'line_total'];
    protected $casts = ['unit_price' => 'decimal:2', 'line_total' => 'decimal:2'];

    public function stockItem(): BelongsTo { return $this->belongsTo(StockItem::class); }
}
