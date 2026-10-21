<x-layouts.auth-shell title="Reset your password">
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <x-field name="email" label="Email" type="email" autocomplete="email" required autofocus />
        <x-button class="w-full">Email reset link</x-button>
    </form>
    <a href="{{ route('login') }}" class="mt-4 inline-block text-sm text-industrial underline">Back to sign in</a>
</x-layouts.auth-shell>
