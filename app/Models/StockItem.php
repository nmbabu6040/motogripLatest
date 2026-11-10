<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    protected $fillable = ['product_id', 'pack', 'sale_price', 'cost_price', 'reorder_level'];
    protected $casts = ['sale_price' => 'decimal:2', 'cost_price' => 'decimal:2', 'reorder_level' => 'integer'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function movements(): HasMany { return $this->hasMany(StockMovement::class); }

    /** Adds a `balance` column: the sum of every stock movement for the item. */
    public function scopeWithBalance($q)
    {
        return $q->select('stock_items.*')->addSelect(['balance' => StockMovement::query()->selectRaw('COALESCE(SUM(quantity), 0)')->whereColumn('stock_item_id', 'stock_items.id')]);
    }

    public function balance(): int
    {
        return (int) $this->movements()->sum('quantity');
    }

    public function label(): string
    {
        return $this->product?->name.($this->pack !== '' ? ' — '.$this->pack : '');
    }

    /** Items for the sale and purchase forms (loaded into the browser as JSON). */
    public static function picklist(): array
    {
        return static::with('product')->withBalance()->get()
            ->sortBy(fn ($i) => mb_strtolower($i->label()))->values()
            ->map(fn ($i) => ['id' => $i->id, 'label' => $i->label(), 'price' => (float) $i->sale_price, 'cost' => (float) $i->cost_price, 'stock' => (int) $i->balance])->all();
    }
}
