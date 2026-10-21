<x-layouts.auth-shell title="Sign in">
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <x-field name="email" label="Email" type="email" autocomplete="username" required autofocus />
        <x-field name="password" label="Password" type="password" autocomplete="current-password" required />
        <label class="flex items-center gap-2 text-sm mb-5"><input type="checkbox" name="remember"> Keep me signed in on
            this device</label>
        <x-button class="w-full">Sign in</x-button>
    </form>
    <a href="{{ route('password.request') }}" class="mt-4 inline-block text-sm text-industrial underline">Forgot your
        password?</a>
</x-layouts.auth-shell>
