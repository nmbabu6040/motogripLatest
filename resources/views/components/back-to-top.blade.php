{{-- Appears after the visitor has scrolled down; sits above the WhatsApp button. Respects "reduce motion". --}}
<button type="button" x-data="{ show: false }" x-on:scroll.window.passive="show = window.scrollY > 600" x-show="show" x-cloak x-transition.opacity
        x-on:click="window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })"
        aria-label="{{ __('site.back_to_top') }}"
        class="fixed bottom-44 right-5 z-40 flex h-11 w-11 items-center justify-center rounded-full bg-navy text-white shadow-lift transition hover:bg-amber hover:text-navy md:bottom-24">
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
</button>
