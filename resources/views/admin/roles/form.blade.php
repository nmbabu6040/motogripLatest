<x-layouts.admin-shell title="Role">
    <form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
        class="max-w-2xl rounded-panel border border-steel bg-white p-6">
        @csrf
        @if ($role->exists)
            @method('PUT')
        @endif

        @if ($role->exists)
            <p class="mb-4 font-semibold">{{ $role->name }}</p>
        @else
            <x-field name="name" label="Role name (lowercase letters, numbers, hyphens)" required />
        @endif

        <fieldset class="mb-5">
            <legend class="text-sm font-semibold mb-2">Permissions you can grant</legend>
            <div class="grid sm:grid-cols-2 gap-x-6">
                @foreach ($grantable as $permission)
                    <label class="flex items-center gap-2 py-1">
                        <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                            @checked(in_array($permission, old('permissions', $selected), true))>
                        {{ $permission }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <x-button>Save role</x-button>
        <a href="{{ route('admin.roles.index') }}" class="ml-3 text-industrial underline">Cancel</a>
    </form>
</x-layouts.admin-shell>
