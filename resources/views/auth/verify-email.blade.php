<x-layouts.auth-shell title="Verify your email">
    <p class="mb-4 text-sm">We sent a verification link to your email address. Open it to continue.</p>
    <form method="POST" action="{{ route('verification.send') }}" class="mb-3">@csrf<x-button>Resend verification email</x-button></form>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm text-industrial underline">Sign out</button></form>
</x-layouts.auth-shell>
