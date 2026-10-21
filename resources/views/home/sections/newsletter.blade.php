<section class="py-20" data-reveal>
    <div class="container-x max-w-5xl">
        <div
            class="relative grid items-center gap-8 overflow-hidden rounded-panel bg-gradient-to-br from-navy-deep via-navy to-industrial p-8 text-white shadow-lift md:grid-cols-2 md:p-12">
            <div class="bg-grid absolute inset-0 opacity-50" aria-hidden="true"></div>
            <div class="relative">
                <h2 class="text-3xl font-extrabold tracking-tight">{{ __('site.newsletter') }}</h2>
                <p class="mt-3 text-white/75">{{ __('site.newsletter_text') }}</p>
            </div>
            <div class="relative">
                @if (session('newsletter_status'))
                    <p role="status" class="mb-3 rounded-control bg-white/10 p-3 text-sm font-semibold">
                        {{ session('newsletter_status') }}</p>
                @endif
                <form method="POST" action="{{ route('newsletter.subscribe') }}"
                    class="flex flex-col gap-2 sm:flex-row">
                    @csrf
                    <label for="nl_email" class="sr-only">{{ __('site.email') }}</label>
                    <input id="nl_email" type="email" name="email" required autocomplete="email"
                        placeholder="{{ __('site.email') }}"
                        class="flex-1 rounded-control border-0 bg-white px-4 py-3 text-ink placeholder:text-ink/60">
                    <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1"
                            autocomplete="off"></div>
                    <button class="btn btn-primary">{{ __('site.subscribe') }}</button>
                </form>
            </div>
        </div>
    </div>
</section>
