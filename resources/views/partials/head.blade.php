<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

@php($siteIcon = \App\Models\Setting::get('site_icon'))
@php($favicon = \App\Models\Setting::get('favicon'))
@if ($favicon)
    <link rel="icon" href="{{ $favicon }}" sizes="any">
@else
    <link rel="icon" href="/favicon/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="/favicon/favicon-16x16.png" type="image/png" sizes="16x16">
@endif
<link rel="apple-touch-icon" href="{{ $siteIcon ?: '/favicon/apple-touch-icon.png' }}">
<link rel="manifest" href="/favicon/site.webmanifest">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
