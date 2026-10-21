<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateCampaign extends Model
{
    protected $fillable = ['name', 'attribution_window_days', 'commission_rate', 'commission_flat', 'currency', 'is_active', 'starts_on', 'ends_on'];
    protected $casts = ['is_active' => 'boolean', 'starts_on' => 'date', 'ends_on' => 'date'];

    public function isRunning(): bool
    {
        return $this->is_active && (! $this->starts_on || $this->starts_on->lte(today())) && (! $this->ends_on || $this->ends_on->gte(today()));
    }
}
