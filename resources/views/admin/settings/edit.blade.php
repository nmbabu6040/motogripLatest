<x-layouts.admin-shell title="Settings">
    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.settings.update') }}"
        class="max-w-3xl space-y-6">
        @csrf
        @method('PUT')
        @foreach ($groups as $group => $fields)
            <fieldset class="rounded-panel border border-steel bg-white p-6">
                <legend class="px-2 text-lg font-extrabold">{{ $group }}</legend>
                @if ($group === 'Brand')
                    <div class="mb-4 grid gap-4 sm:grid-cols-2">
                        @foreach ($images as $key => [$label, $ext])
                            <div>
                                <label class="block text-sm font-semibold mb-1"
                                    for="img_{{ $key }}">{{ $label }}</label>
                                @if ($imageValues[$key])
                                    <img src="{{ $imageValues[$key] }}" alt=""
                                        class="mb-2 h-12 w-auto rounded-control border border-steel bg-surface p-1">
                                @endif
                                <input id="img_{{ $key }}" type="file" name="img_{{ $key }}"
                                    accept=".{{ str_replace(',', ',.', $ext) }}">
                            </div>
                        @endforeach
                    </div>
                @endif
                @foreach ($fields as $name => $f)
                    @if ($f[3] === 'bool')
                        <label class="mb-3 flex items-center gap-2">
                            <input type="checkbox" name="{{ $name }}" value="1"
                                @checked(old($name, $values[$name]))> {{ $f[2] }}</label>
                    @else
                        <x-field :name="$name" :label="$f[2]" :value="$values[$name]" />
                    @endif
                @endforeach
            </fieldset>
        @endforeach
        <p class="text-sm">Passwords, API keys, SMTP and backup credentials are never stored here. Set them in the
            server environment file.</p>
        <x-button>Save settings</x-button>
    </form>

    <section class="mt-10 max-w-3xl rounded-panel border border-steel bg-white p-6">
        <h2 class="text-lg font-extrabold">Server configuration (read-only)</h2>
        <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
            @foreach ($env as $k => $v)
                <dt class="font-semibold">{{ $k }}</dt>
                <dd>{{ $v }}</dd>
            @endforeach
        </dl>
        @if (in_array($env['Mail driver'], ['log', 'array'], true))
            <p role="alert" class="mt-3 rounded-control bg-amber/30 p-3 text-sm font-semibold">Mail is not configured:
                emails are only written to the log. Set MAIL_* in the server environment to send real email.</p>
        @endif
        <form method="POST" action="{{ route('admin.settings.cache') }}" class="mt-4">
            @csrf
            <x-button>Clear
                caches</x-button>
        </form>
        @if (auth()->user()->hasRole('super-admin'))
            <form method="POST" action="{{ route('admin.settings.maintenance') }}"
                class="mt-6 border-t border-steel pt-4"
                x-on:submit="if (!confirm('Toggle maintenance mode?')) $event.preventDefault()">
                @csrf
                @unless ($maintenance)
                    <label class="mb-2 flex items-center gap-2 text-sm"><input type="checkbox" name="confirm" value="1"
                            required> I understand visitors will see a maintenance page</label>
                @endunless
                <x-button>{{ $maintenance ? 'Turn maintenance mode off' : 'Turn maintenance mode on' }}</x-button>
            </form>
        @endif
    </section>
</x-layouts.admin-shell>
