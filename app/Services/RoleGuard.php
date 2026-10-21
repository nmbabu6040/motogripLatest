<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Privilege-escalation rules. Non-super-admins can never hand out more power than they hold.
 */
class RoleGuard
{
    public function canModify(User $actor, User $target): bool
    {
        if ($actor->hasRole('super-admin')) {
            return true;
        }
        if ($actor->is($target) || $target->hasRole('super-admin')) {
            return false;
        }
        return $this->permissionsOf($target)->diff($this->permissionsOf($actor))->isEmpty();
    }

    /** @param string[] $roleNames */
    public function canAssign(User $actor, array $roleNames): bool
    {
        if ($actor->hasRole('super-admin')) {
            return true;
        }
        if (in_array('super-admin', $roleNames, true)) {
            return false;
        }
        $own = $this->permissionsOf($actor);

        return Role::whereIn('name', $roleNames)->with('permissions')->get()
            ->every(fn (Role $r) => $r->permissions->pluck('name')->diff($own)->isEmpty());
    }

    /** @param string[] $permissionNames */
    public function canGrant(User $actor, array $permissionNames): bool
    {
        return $actor->hasRole('super-admin')
            || collect($permissionNames)->diff($this->permissionsOf($actor))->isEmpty();
    }

    /** Permissions the actor may place on a role. */
    public function grantable(User $actor): Collection
    {
        return $actor->hasRole('super-admin')
            ? Permission::orderBy('name')->pluck('name')
            : $this->permissionsOf($actor)->sort()->values();
    }

    /** Roles the actor may assign to someone else. */
    public function assignableRoles(User $actor): Collection
    {
        return Role::with('permissions')->orderBy('name')->get()
            ->filter(fn (Role $r) => $this->canAssign($actor, [$r->name]))->values();
    }

    private function permissionsOf(User $user): Collection
    {
        return $user->getAllPermissions()->pluck('name');
    }
}
