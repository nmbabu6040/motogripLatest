@php
    use App\Support\Settings;
    $logo = Settings::get('brand', 'logo_url');
    $name = Settings::get('brand', 'site_name', config('app.name'));
@endphp
<div class="flex items-start gap-4">
    @if ($logo)<img src="{{ $logo }}" alt="" class="h-14 w-auto">@endif
    <div class="text-sm leading-snug">
        <p class="text-lg font-extrabold text-navy">{{ $name }}</p>
        @if ($v = Settings::get('contact', 'address'))<p>{{ $v }}</p>@endif
        @if ($v = Settings::get('contact', 'phone'))<p>{{ $v }}</p>@endif
        @if ($v = Settings::get('contact', 'email'))<p>{{ $v }}</p>@endif
        @if ($v = Settings::get('erp', 'tax_id'))<p>VAT / BIN: {{ $v }}</p>@endif
    </div>
</div>
