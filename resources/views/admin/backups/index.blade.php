<x-layouts.admin-shell title="Backups">
    @unless ($configured)
        <p role="alert"
            class="mb-4 max-w-3xl rounded-control border border-red-300 bg-red-50 p-3 text-sm font-semibold text-red-900">
            Backups are disabled: BACKUP_ARCHIVE_PASSWORD is not set, and this system never writes unencrypted backups.</p>
    @endunless
    @if (session('verify_error'))
        <p role="alert" class="mb-4 max-w-3xl rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">
            {{ session('verify_error') }}</p>
    @endif

    <dl class="grid max-w-4xl gap-4 sm:grid-cols-3">
        <div class="rounded-panel border border-steel bg-white p-4">
            <dt class="text-sm">Last successful backup</dt>
            <dd class="font-bold">{{ $lastGood?->finished_at ?? 'None yet' }}</dd>
        </div>
        <div class="rounded-panel border border-steel bg-white p-4">
            <dt class="text-sm">Last restore test</dt>
            <dd class="font-bold">{{ $lastTested?->restore_tested_at ?? 'Never tested' }}</dd>
        </div>
        <div class="rounded-panel border border-steel bg-white p-4">
            <dt class="text-sm">Storage disk</dt>
            <dd class="font-bold">{{ $disk }}</dd>
        </div>
    </dl>
    @unless ($lastTested)
        <p class="mt-3 max-w-3xl text-sm font-semibold">No restore has been tested. Until one is, treat these backups as
            unproven.</p>
    @endunless

    @can('backups.create')
        <form method="POST" action="{{ route('admin.backups.store') }}" class="mt-6 flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="block text-sm font-semibold mb-1" for="type">Create a backup now</label>
                <select id="type" name="type" class="rounded-control border border-steel px-3 py-2">
                    <option value="database">Database</option>
                    <option value="media">Uploaded files</option>
                </select>
            </div>
            <x-button>Queue backup</x-button>
        </form>
    @endcan

    <div class="mt-6 overflow-x-auto rounded-panel border border-steel bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface">
                <tr>
                    <th class="p-3">Started</th>
                    <th class="p-3">Type</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Size</th>
                    <th class="p-3">Verified</th>
                    <th class="p-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $r)
                    <tr class="border-t border-steel align-top">
                        <td class="p-3 whitespace-nowrap">{{ $r->started_at }}</td>
                        <td class="p-3">{{ $r->type }}</td>
                        <td class="p-3">{{ ucfirst($r->status) }}
                            @if ($r->error)
                                <br><span
                                    class="text-xs text-red-800">{{ \Illuminate\Support\Str::limit($r->error, 160) }}</span>
                            @endif
                        </td>
                        <td class="p-3">{{ $r->size ? number_format($r->size / 1048576, 1) . ' MB' : '—' }}</td>
                        <td class="p-3">{{ $r->verified_at ?? '—' }}</td>
                        <td class="p-3">
                            @if ($r->status === 'succeeded')
                                @can('backups.create')
                                    <form method="POST" action="{{ route('admin.backups.verify', $r->id) }}">
                                        @csrf
                                        <button class="underline text-industrial">Verify</button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty<tr>
                        <td colspan="6" class="p-6 text-center">No backups recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <section class="mt-8 max-w-3xl rounded-panel border border-steel bg-white p-6 text-sm">
        <h2 class="font-extrabold">Restoring</h2>
        <p class="mt-2">Restore is deliberately not a button. It overwrites the live database, so it runs from the
            server command line by an authorised engineer:</p>
        <ol class="mt-2 list-decimal pl-5 space-y-1">
            <li>Verify the backup above (or <code>php artisan motogrip:backup-verify ID</code>).</li>
            <li><code>php artisan motogrip:restore ID --confirm-db=DATABASE_NAME</code></li>
            <li>The command takes a safety backup first, puts the site in maintenance mode, imports, then brings the
                site back.</li>
            <li>After a drill in a test environment, record it with <code>php artisan motogrip:backup-tested ID</code>.
            </li>
        </ol>
    </section>
</x-layouts.admin-shell>
