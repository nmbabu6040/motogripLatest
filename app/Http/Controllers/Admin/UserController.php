<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RoleGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private RoleGuard $guard) {}

    public function index(Request $request)
    {
        $users = User::query()->with('roles')
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
            })
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(Request $request)
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.create', ['assignableRoles' => $this->guard->assignableRoles($request->user())]);
    }

    public function store(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'roles' => ['array', 'min:1'], 'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);
        abort_unless($this->guard->canAssign($request->user(), $data['roles']), 403);

        // The person sets their own password through an emailed reset link; no password is ever chosen or shown here.
        $user = DB::transaction(function () use ($data) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(40))]);
            $user->forceFill(['status' => 'active', 'approved_at' => now()])->save();
            $user->syncRoles($data['roles']);
            return $user;
        });

        \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);
        activity('admin')->causedBy($request->user())->performedOn($user)->withProperties(['roles' => $data['roles']])->log('user.created');

        return redirect()->route('admin.users.index')->with('status', 'User created. A link to set their password was emailed to them.');
    }

    public function edit(Request $request, User $user)
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'assignableRoles' => $this->guard->assignableRoles($request->user()),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'pending', 'suspended'])],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);
        $roles = $data['roles'] ?? [];

        abort_unless($this->guard->canAssign($request->user(), $roles), 403);

        // The platform must always keep at least one super administrator.
        $losingSuperAdmin = $user->hasRole('super-admin') && ! in_array('super-admin', $roles, true);
        if (($losingSuperAdmin || ($user->hasRole('super-admin') && $data['status'] !== 'active'))
            && User::role('super-admin')->where('status', 'active')->count() <= 1) {
            return back()->withErrors(['roles' => 'At least one active super administrator is required.'])->withInput();
        }

        $before = ['status' => $user->status, 'roles' => $user->getRoleNames()->sort()->values()->all()];

        DB::transaction(function () use ($user, $data, $roles) {
            $user->syncRoles($roles);
            $user->forceFill(['status' => $data['status'], 'approved_at' => $user->approved_at ?? ($data['status'] === 'active' ? now() : null)])->save();

            if ($data['status'] !== 'active') {
                DB::table('sessions')->where('user_id', $user->id)->delete();   // sign the account out everywhere
            }
        });

        activity('admin')->causedBy($request->user())->performedOn($user)
            ->withProperties(['before' => $before, 'after' => ['status' => $data['status'], 'roles' => collect($roles)->sort()->values()->all()]])
            ->log('user.updated');

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }
}
