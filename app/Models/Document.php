<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Document extends Model
{
    use HasTranslations, SoftDeletes;

    public const TYPES = ['tds' => 'Technical Data Sheet', 'sds' => 'Safety Data Sheet', 'catalog' => 'Catalog', 'brochure' => 'Brochure', 'guide' => 'Application guide', 'presentation' => 'Presentation', 'other' => 'Other'];

    protected $fillable = ['product_id', 'type', 'title', 'disk', 'path', 'original_name', 'mime', 'size', 'checksum', 'visibility', 'language', 'version', 'published_at'];
    public array $translatable = ['title'];
    protected $casts = ['published_at' => 'datetime'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function scopeAvailable($q) { return $q->whereNotNull('published_at')->where('published_at', '<=', now()); }

    public function canBeDownloadedBy(?User $user): bool
    {
        return match ($this->visibility) {
            'public' => true,
            'authenticated' => $user !== null,
            'restricted' => $user?->can('documents.view-restricted') ?? false,
            default => false,
        };
    }
}
