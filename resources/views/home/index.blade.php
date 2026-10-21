@php
    $textKeys = [
        'introduction',
        'automotive_solutions',
        'industrial_solutions',
        'benefits',
        'why_choose',
        'industries',
        'statistics',
        'inquiry_cta',
        'dealer_recruitment',
    ];
    $ld = [];
@endphp
<x-layouts.public-shell :description="\App\Support\Settings::get('brand', 'tagline')" :jsonld="$ld" :canonical="$lr('home')">
    @foreach ($sections as $section)
        @php
            $view = in_array($section['key'], $textKeys, true)
                ? 'home.sections.text'
                : 'home.sections.' . $section['key'];
        @endphp
        @includeIf($view, ['content' => $section['content'], 'key' => $section['key']])
    @endforeach
</x-layouts.public-shell>
