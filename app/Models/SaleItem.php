<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['sale_id', 'stock_item_id', 'quantity', 'unit_price', 'unit_cost', 'line_total'];
    protected $casts = ['unit_price' => 'decimal:2', 'unit_cost' => 'decimal:2', 'line_total' => 'decimal:2'];

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function stockItem(): BelongsTo { return $this->belongsTo(StockItem::class); }
}
