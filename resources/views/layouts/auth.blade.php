<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'Sign in' }} | {{ \App\Support\Settings::get('brand', 'site_name', config('app.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface flex items-center justify-center p-4">
    <main class="w-full max-w-md bg-white rounded-panel border border-steel p-8">
        <h1 class="text-2xl font-extrabold text-navy">{{ $title ?? 'Sign in' }}</h1>

        @if (session('status'))
            <p role="status" class="mt-4 rounded-control bg-surface p-3 text-sm">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="mt-4 rounded-control border border-red-300 bg-red-50 p-3 text-sm text-red-900">
                <p class="font-semibold">Please fix the following:</p>
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="mt-6">{{ $slot }}</div>
    </main>
</body>
</html>
