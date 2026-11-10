<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    public const METHODS = ['cash' => 'Cash', 'bank' => 'Bank transfer', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'cheque' => 'Cheque', 'other' => 'Other'];

    protected $fillable = ['payable_type', 'payable_id', 'amount', 'method', 'paid_on', 'reference', 'note', 'created_by'];
    protected $casts = ['paid_on' => 'date', 'amount' => 'decimal:2'];

    public function payable(): MorphTo { return $this->morphTo(); }
}
