<x-layouts.admin-shell title="Edit user">
    <form method="POST" action="{{ route('admin.users.update', $user) }}"
        class="max-w-xl rounded-panel border border-steel bg-white p-6">
        @csrf
        @method('PUT')
        <p class="mb-1 font-semibold">{{ $user->name }}</p>
        <p class="mb-4 text-sm">{{ $user->email }}</p>

        <label for="status" class="block text-sm font-semibold mb-1">Account status</label>
        <select id="status" name="status" class="mb-5 rounded-control border border-steel px-3 py-2">
            @foreach (['active', 'pending', 'suspended'] as $status)
                <option value="{{ $status }}" @selected(old('status', $user->status) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>

        <fieldset class="mb-5">
            <legend class="text-sm font-semibold mb-2">Roles</legend>
            @foreach ($assignableRoles as $role)
                <label class="flex items-center gap-2 py-1">
                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, old('roles', $user->roles->pluck('name')->all()), true))>
                    {{ $role->name }}
                </label>
            @endforeach
        </fieldset>
        <x-button>Save changes</x-button>
        <a href="{{ route('admin.users.index') }}" class="ml-3 text-industrial underline">Cancel</a>
    </form>
</x-layouts.admin-shell>
