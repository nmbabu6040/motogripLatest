<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $actor): bool { return $actor->can('roles.manage'); }
    public function create(User $actor): bool { return $actor->can('roles.manage'); }

    public function update(User $actor, Role $role): bool
    {
        return $actor->can('roles.manage') && $role->name !== 'super-admin';
    }

    public function delete(User $actor, Role $role): bool
    {
        // Built-in roles can never be deleted, even by a super administrator.
        return $actor->can('roles.manage') && ! in_array($role->name, \Database\Seeders\RolesAndPermissionsSeeder::protectedRoles(), true);
    }
}
