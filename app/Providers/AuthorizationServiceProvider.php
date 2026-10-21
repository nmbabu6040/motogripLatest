<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AuthorizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Super administrators pass every check. Everyone else needs an explicit permission.
        Gate::before(fn (User $user) => $user->hasRole('super-admin') ? true : null);

        Gate::policy(Role::class, RolePolicy::class);
    }
}
