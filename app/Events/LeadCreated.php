<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired when an inquiry or dealer application is saved. The affiliate module listens for it. */
class LeadCreated
{
    use Dispatchable;

    public function __construct(public Model $lead, public string $type) {}
}
