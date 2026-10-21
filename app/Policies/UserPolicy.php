<?php

namespace App\Policies;

use App\Models\User;
use App\Services\RoleGuard;

class UserPolicy
{
    public function __construct(private RoleGuard $guard) {}

    public function viewAny(User $actor): bool
    {
        return $actor->can('users.manage');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->can('users.manage') && $this->guard->canModify($actor, $target);
    }
}
