@props(['place' => 'header'])
@php
    use App\Support\Settings;
    $enabled = config('motogrip.features.adsense') && Settings::get('ads', 'enabled', false);
    $client = $enabled ? Settings::get('ads', 'publisher_id') : null;
    $unit = $client ? Settings::get('ads', 'slot_'.$place) : null;
@endphp
{{-- Ads render only when the feature flag, the admin toggle, a real publisher ID and a real ad unit are all set. --}}
@if ($client && $unit)
    <aside class="mx-auto my-4 max-w-4xl px-4" aria-label="Advertisement" style="min-height:100px">
        <ins class="adsbygoogle block" data-ad-client="{{ $client }}" data-ad-slot="{{ $unit }}" data-ad-format="auto" data-full-width-responsive="true"></ins>
    </aside>
@endif
