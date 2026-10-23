<x-layouts.admin-shell title="My security">
    <section class="mb-8 max-w-2xl rounded-panel border border-steel bg-white p-6" aria-labelledby="tfa">
        <h2 id="tfa" class="text-lg font-extrabold mb-3">Two-factor authentication</h2>

        @if ($twoFactorEnabled)
            <p class="mb-4">Two-factor authentication is on.</p>
            <details class="mb-4">
                <summary class="cursor-pointer font-semibold">Show recovery codes</summary>
                <ul class="mt-2 font-mono text-sm">@foreach ($user->recoveryCodes() as $code)<li>{{ $code }}</li>@endforeach</ul>
            </details>
            <form method="POST" action="{{ url('/user/two-factor-authentication') }}" x-on:submit="if (!confirm('Turn off two-factor authentication?')) $event.preventDefault()">@csrf @method('DELETE')<x-button>Turn off</x-button></form>
        @elseif ($twoFactorPending)
            <p class="mb-3">Scan this code with an authenticator app, then enter the 6-digit code to finish.</p>
            <div class="mb-3 w-48 bg-white p-2 border border-steel">{!! $user->twoFactorQrCodeSvg() !!}</div>
            <form method="POST" action="{{ url('/user/confirmed-two-factor-authentication') }}" class="max-w-xs">
                @csrf
                <x-field name="code" label="Authentication code" inputmode="numeric" autocomplete="one-time-code" required />
                <x-button>Confirm</x-button>
            </form>
        @else
            <p class="mb-4">Add a second step to sign-in. You will be asked to confirm your password first.</p>
            <form method="POST" action="{{ url('/user/two-factor-authentication') }}">@csrf<x-button>Set up</x-button></form>
        @endif
    </section>

    <section class="max-w-2xl rounded-panel border border-steel bg-white p-6" aria-labelledby="sess">
        <h2 id="sess" class="text-lg font-extrabold mb-3">Active sessions</h2>
        <ul class="mb-4 divide-y divide-steel text-sm">
            @foreach ($sessions as $s)
                <li class="py-2">{{ $s['agent'] ?: 'Unknown device' }} · {{ $s['ip'] }} · {{ $s['last_active']->diffForHumans() }} @if ($s['current'])<strong>(this device)</strong>@endif</li>
            @endforeach
        </ul>
        <form method="POST" action="{{ route('admin.security.sessions.destroy') }}" class="max-w-xs">
            @csrf @method('DELETE')
            <x-field name="password" label="Confirm password to sign out other devices" type="password" autocomplete="current-password" required />
            <x-button>Sign out other devices</x-button>
        </form>
    </section>
</x-layouts.admin-shell>
