<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RoleGuard;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private RoleGuard $guard) {}

    public function index()
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index', [
            'roles' => Role::withCount('permissions', 'users')->orderBy('name')->get(),
            'protected' => RolesAndPermissionsSeeder::protectedRoles(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.form', ['role' => new Role, 'grantable' => $this->guard->grantable($request->user()), 'selected' => []]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Role::class);

        $data = $this->validated($request, null);
        abort_unless($this->guard->canGrant($request->user(), $data['permissions']), 403);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions']);

        activity('admin')->causedBy($request->user())->performedOn($role)->withProperties(['permissions' => $data['permissions']])->log('role.created');

        return redirect()->route('admin.roles.index')->with('status', 'Role created.');
    }

    public function edit(Request $request, Role $role)
    {
        abort_if($role->name === 'super-admin', 403, 'The super-admin role is fixed.');
        $this->authorize('update', $role);

        return view('admin.roles.form', [
            'role' => $role,
            'grantable' => $this->guard->grantable($request->user()),
            'selected' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        abort_if($role->name === 'super-admin', 403, 'The super-admin role is fixed.');
        $this->authorize('update', $role);

        $data = $this->validated($request, $role);
        abort_unless($this->guard->canGrant($request->user(), $data['permissions']), 403);

        // Permissions the actor does not hold stay untouched, so a limited editor cannot strip or add them silently.
        $actorGrantable = $this->guard->grantable($request->user());
        $kept = $role->permissions->pluck('name')->reject(fn ($p) => $actorGrantable->contains($p));
        $before = $role->permissions->pluck('name')->sort()->values()->all();

        $role->syncPermissions($kept->merge($data['permissions'])->unique()->all());

        activity('admin')->causedBy($request->user())->performedOn($role)
            ->withProperties(['before' => $before, 'after' => $role->fresh()->permissions->pluck('name')->sort()->values()->all()])
            ->log('role.updated');

        return redirect()->route('admin.roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Request $request, Role $role)
    {
        // Gate::before lets super administrators past every policy, so the built-in role rule is enforced here too.
        abort_if(in_array($role->name, RolesAndPermissionsSeeder::protectedRoles(), true), 403, 'Built-in roles cannot be deleted.');
        $this->authorize('delete', $role);

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'This role is still assigned to users.']);
        }

        activity('admin')->causedBy($request->user())->withProperties(['name' => $role->name])->log('role.deleted');
        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Role deleted.');
    }

    private function validated(Request $request, ?Role $role): array
    {
        $data = $request->validate([
            'name' => [$role ? 'prohibited' : 'required', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', 'max:60', Rule::unique('roles', 'name')],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);
        $data['permissions'] = $data['permissions'] ?? [];
        $data['name'] = $data['name'] ?? $role?->name;

        return $data;
    }
}
