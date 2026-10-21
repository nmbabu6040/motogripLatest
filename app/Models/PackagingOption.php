<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagingOption extends Model
{
    protected $fillable = ['product_id', 'label', 'size_value', 'size_unit', 'sku_suffix', 'is_active', 'sort_order'];
    protected $casts = ['is_active' => 'boolean', 'size_value' => 'decimal:2'];
}
