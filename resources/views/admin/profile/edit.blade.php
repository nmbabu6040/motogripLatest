<x-layouts.admin-shell title="My Profile">

    <div class="max-w-2xl space-y-6">

        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Personal Information --}}
        <section class="rounded-panel border border-steel bg-white p-6">

            <h2 class="mb-1 text-lg font-extrabold">Personal Information</h2>

            <p class="mb-6 text-sm text-gray-500">
                Update your personal account information.
            </p>

            <form method="POST" action="{{ route('admin.profile.update') }}">
                @csrf
                @method('PUT')

                <div class="space-y-4">

                    <x-field name="name" label="Full name" :value="old('name', $user->name)" required />

                    <x-field name="email" label="Email address" type="email" :value="old('email', $user->email)" required />

                    <x-field name="phone" label="Phone number" :value="old('phone', $user->phone)" />

                </div>

                {{-- Change Password --}}
                <div class="mt-6 border-t border-steel pt-5">

                    <h3 class="mb-1 font-bold">Change Password</h3>

                    <p class="mb-4 text-sm text-gray-500">
                        Leave these fields empty if you do not want to change your password.
                    </p>

                    <div class="space-y-4">

                        {{-- Current Password --}}
                        <div>
                            <label for="current_password" class="mb-1.5 block text-sm font-medium text-gray-700">
                                Current password
                            </label>

                            <div class="relative">
                                <input type="password" name="current_password" id="current_password"
                                    autocomplete="current-password"
                                    class="w-full rounded-lg border border-steel bg-white px-4 py-3 pr-12 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">

                                <button type="button" data-password-toggle
                                    class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-900"
                                    aria-label="Show password" aria-pressed="false">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </button>
                            </div>

                            @error('current_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- New Password --}}
                        <div>
                            <label for="new_password" class="mb-1.5 block text-sm font-medium text-gray-700">
                                New password
                            </label>

                            <div class="relative">
                                <input type="password" name="new_password" id="new_password" autocomplete="new-password"
                                    class="w-full rounded-lg border border-steel bg-white px-4 py-3 pr-12 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">

                                <button type="button" data-password-toggle
                                    class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-900"
                                    aria-label="Show password" aria-pressed="false">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </button>
                            </div>

                            @error('new_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Confirm New Password --}}
                        <div>
                            <label for="new_password_confirmation"
                                class="mb-1.5 block text-sm font-medium text-gray-700">
                                Confirm new password
                            </label>

                            <div class="relative">
                                <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                                    autocomplete="new-password"
                                    class="w-full rounded-lg border border-steel bg-white px-4 py-3 pr-12 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">

                                <button type="button" data-password-toggle
                                    class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-900"
                                    aria-label="Show password" aria-pressed="false">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </button>
                            </div>

                            @error('new_password_confirmation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Save Button --}}
                <div class="mt-6">
                    <x-button>Save Changes</x-button>
                </div>

            </form>

        </section>

        {{-- Account Information --}}
        <section class="rounded-panel border border-steel bg-white p-6">

            <h2 class="mb-3 font-extrabold">Account Information</h2>

            <dl class="space-y-3 text-sm">

                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Account status</dt>
                    <dd class="font-semibold">{{ ucfirst($user->status) }}</dd>
                </div>

                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Email verification</dt>
                    <dd class="font-semibold">
                        {{ $user->hasVerifiedEmail() ? 'Verified' : 'Not verified' }}
                    </dd>
                </div>

                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Account created</dt>
                    <dd class="font-semibold">
                        {{ $user->created_at?->format('d M Y') }}
                    </dd>
                </div>

            </dl>

            <p class="mt-4 text-xs text-gray-500">
                Account status, roles and permissions cannot be changed from this page.
            </p>

        </section>

    </div>

    {{-- Password Show / Hide --}}
    <script>
        document.addEventListener('click', function(event) {

            const button = event.target.closest('[data-password-toggle]');

            if (!button) return;

            const input = button.parentElement.querySelector('input');

            if (!input) return;

            const showPassword = input.type === 'password';

            input.type = showPassword ? 'text' : 'password';

            button.setAttribute('aria-pressed', String(showPassword));

            button.setAttribute(
                'aria-label',
                showPassword ? 'Hide password' : 'Show password'
            );

            button.innerHTML = showPassword ?
                `<svg xmlns="http://www.w3.org/2000/svg"
                       width="20" height="20"
                       viewBox="0 0 24 24"
                       fill="none" stroke="currentColor"
                       stroke-width="2" stroke-linecap="round"
                       stroke-linejoin="round">
                       <path d="M3 3l18 18"/>
                       <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                       <path d="M9.9 5.2A11 11 0 0 1 12 5c6.5 0 10 7 10 7a14 14 0 0 1-3 3.8"/>
                       <path d="M6.6 6.6C3.6 8.4 2 12 2 12s3.5 7 10 7c1.2 0 2.3-.2 3.3-.6"/>
                   </svg>` :
                `<svg xmlns="http://www.w3.org/2000/svg"
                       width="20" height="20"
                       viewBox="0 0 24 24"
                       fill="none" stroke="currentColor"
                       stroke-width="2" stroke-linecap="round"
                       stroke-linejoin="round">
                       <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                       <circle cx="12" cy="12" r="3"/>
                   </svg>`;
        });
    </script>

</x-layouts.admin-shell>
