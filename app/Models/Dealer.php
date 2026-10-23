<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dealer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'dealer_application_id',
        'slug',
        'name',
        'district',
        'city',
        'address',
        'phone',
        'email',
        'show_phone',
        'show_email',
        'latitude',
        'longitude',
        'business_hours',
        'is_published'
    ];
    protected $casts = [
        'show_phone' => 'boolean',
        'show_email' => 'boolean',
        'is_published' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float'
    ];

    public function scopePublished($q)
    {
        return $q->where('is_published', true);
    }

    public function mapUrl(): ?string
    {
        return $this->latitude && $this->longitude ? "https://www.openstreetmap.org/?mlat={$this->latitude}&mlon={$this->longitude}#map=16/{$this->latitude}/{$this->longitude}" : null;
    }
}
