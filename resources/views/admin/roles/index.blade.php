<x-layouts.admin-shell title="Roles">
    <a href="{{ route('admin.roles.create') }}" class="mb-4 inline-flex rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial">Create role</a>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface"><tr><th class="p-3">Role</th><th class="p-3">Permissions</th><th class="p-3">Users</th><th class="p-3"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @foreach ($roles as $role)
                <tr class="border-t border-steel">
                    <td class="p-3">{{ $role->name }} @if (in_array($role->name, $protected, true))<span class="text-xs">(built-in)</span>@endif</td>
                    <td class="p-3">{{ $role->name === 'super-admin' ? 'All' : $role->permissions_count }}</td>
                    <td class="p-3">{{ $role->users_count }}</td>
                    <td class="p-3 flex gap-3">
                        @can('update', $role)<a class="text-industrial underline" href="{{ route('admin.roles.edit', $role) }}">Edit<span class="sr-only"> {{ $role->name }}</span></a>@endcan
                        @can('delete', $role)
                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" x-on:submit="if (!confirm('Delete this role?')) $event.preventDefault()">@csrf @method('DELETE')
                                <button class="text-red-800 underline">Delete<span class="sr-only"> {{ $role->name }}</span></button></form>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin-shell>
