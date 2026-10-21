<x-layouts.auth-shell title="Confirm your password">
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <x-field name="password" label="Password" type="password" autocomplete="current-password" required autofocus />
        <x-button class="w-full">Confirm</x-button>
    </form>
</x-layouts.auth-shell>
