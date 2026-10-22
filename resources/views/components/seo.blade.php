@props(['title' => null, 'description' => null, 'canonical' => null, 'image' => null, 'noindex' => false, 'type' => 'website', 'alternates' => []])
@php
    $siteName = \App\Support\Settings::get('brand', 'site_name', config('app.name'));
    $fullTitle = $title ? $title.' | '.$siteName : $siteName;
    $description = $description ?: \App\Support\Settings::get('seo', 'default_description');
    $canonical = $canonical ?: url()->current();
    $image = $image ?: \App\Support\Settings::get('brand', 'social_image_url');
@endphp
<title>{{ $fullTitle }}</title>
@if ($description)<meta name="description" content="{{ $description }}">@endif
<link rel="canonical" href="{{ $canonical }}">
@foreach ($alternates as $lang => $href)<link rel="alternate" hreflang="{{ $lang }}" href="{{ $href }}">@endforeach
@if ($noindex)<meta name="robots" content="noindex,follow">@endif
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title ?: $siteName }}">
@if ($description)<meta property="og:description" content="{{ $description }}">@endif
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'bn' ? 'bn_BD' : 'en_US' }}">
@if ($image)<meta property="og:image" content="{{ $image }}">@endif
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
