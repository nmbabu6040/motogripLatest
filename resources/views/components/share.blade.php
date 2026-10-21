@props(['url', 'title'])
@php
    $u = urlencode($url);
    $t = urlencode($title);
@endphp
<div class="flex flex-wrap items-center gap-2 text-sm" x-data="share(@js($url), @js($title))">
    <span class="font-semibold">{{ __('site.share') }}:</span>
    <a class="rounded-control border border-steel px-3 py-1.5 hover:bg-surface" target="_blank" rel="noopener"
        href="https://www.facebook.com/sharer/sharer.php?u={{ $u }}">Facebook</a>
    <a class="rounded-control border border-steel px-3 py-1.5 hover:bg-surface" target="_blank" rel="noopener"
        href="https://wa.me/?text={{ $t }}%20{{ $u }}">WhatsApp</a>
    <a class="rounded-control border border-steel px-3 py-1.5 hover:bg-surface" target="_blank" rel="noopener"
        href="https://www.linkedin.com/sharing/share-offsite/?url={{ $u }}">LinkedIn</a>
    <a class="rounded-control border border-steel px-3 py-1.5 hover:bg-surface"
        href="mailto:?subject={{ $t }}&body={{ $u }}">Email</a>
    <button type="button" class="rounded-control border border-steel px-3 py-1.5 hover:bg-surface"
        x-on:click="native()" x-text="copied ? @js(__('site.copied')) : @js(__('site.copy_link'))"></button>
    <span class="sr-only" role="status" aria-live="polite" x-text="copied ? @js(__('site.copied')) : ''"></span>
</div>
