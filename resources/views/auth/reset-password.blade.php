<x-layouts.auth-shell title="Choose a new password">
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-field name="email" label="Email" type="email" :value="$request->email" autocomplete="username" required />
        <x-field name="password" label="New password (14+ characters, mixed case, with a number)" type="password"
            autocomplete="new-password" required />
        <x-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password"
            required />
        <x-button class="w-full">Save password</x-button>
    </form>
</x-layouts.auth-shell>
