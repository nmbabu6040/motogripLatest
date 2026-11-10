<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallanItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['challan_id', 'sale_item_id', 'quantity'];

    public function saleItem(): BelongsTo { return $this->belongsTo(SaleItem::class); }
}
