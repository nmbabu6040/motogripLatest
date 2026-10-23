<x-layouts.admin-shell title="Users">
    <form method="GET" class="mb-4 flex gap-2" role="search">
        <label for="q" class="sr-only">Search users</label>
        <input id="q" name="q" value="{{ request('q') }}" placeholder="Name or email" class="rounded-control border border-steel px-3 py-2 w-72 max-w-full">
        <x-button>Search</x-button>
        <a href="{{ route('admin.users.create') }}" class="rounded-control border border-navy px-4 py-2 font-semibold text-navy hover:bg-white">Add user</a>
    </form>
    <div class="overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface"><tr><th class="p-3">Name</th><th class="p-3">Email</th><th class="p-3">Status</th><th class="p-3">Roles</th><th class="p-3"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @forelse ($users as $user)
                <tr class="border-t border-steel">
                    <td class="p-3">{{ $user->name }}</td><td class="p-3">{{ $user->email }}</td>
                    <td class="p-3">{{ ucfirst($user->status) }}</td><td class="p-3">{{ $user->roles->pluck('name')->join(', ') ?: 'None' }}</td>
                    <td class="p-3">@can('update', $user)<a class="text-industrial underline" href="{{ route('admin.users.edit', $user) }}">Edit<span class="sr-only"> {{ $user->name }}</span></a>@endcan</td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center">No users match your search.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.admin-shell>
