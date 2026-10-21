<x-layouts.auth-shell title="Two-factor check">
    <div x-data="{ recovery: false }">
        <form method="POST" action="{{ route('two-factor.login') }}">
            @csrf
            <div x-show="!recovery">
                <x-field name="code" label="Authentication code" inputmode="numeric" autocomplete="one-time-code" />
            </div>
            <div x-show="recovery" x-cloak>
                <x-field name="recovery_code" label="Recovery code" autocomplete="one-time-code" />
            </div>
            <x-button class="w-full">Continue</x-button>
        </form>
        <button type="button" class="mt-4 text-sm text-industrial underline" x-on:click="recovery = !recovery"
            x-text="recovery ? 'Use an authentication code' : 'Use a recovery code'"></button>
    </div>
</x-layouts.auth-shell>
