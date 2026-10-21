<x-layouts.admin-shell title="Add user">
    <form method="POST" action="{{ route('admin.users.store') }}"
        class="max-w-xl rounded-panel border border-steel bg-white p-6">
        @csrf
        <x-field name="name" label="Full name" required />
        <x-field name="email" label="Email" type="email" required />
        <fieldset class="mb-5">
            <legend class="text-sm font-semibold mb-2">Roles</legend>
            @foreach ($assignableRoles as $role)
                <label class="flex items-center gap-2 py-1"><input type="checkbox" name="roles[]"
                        value="{{ $role->name }}" @checked(in_array($role->name, old('roles', []), true))> {{ $role->name }}</label>
            @endforeach
        </fieldset>
        <p class="mb-4 text-sm">They receive an email with a link to choose their own password. Mail must be configured
            for this to work.</p>
        <x-button>Create user</x-button> <a class="ml-3 underline text-industrial"
            href="{{ route('admin.users.index') }}">Cancel</a>
    </form>
</x-layouts.admin-shell>
