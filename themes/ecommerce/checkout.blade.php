<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
    @include('partials.seo-meta')
    @include('partials.custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('theme-ecommerce::partials.header')

@php
    $crumbs = [
        ['label' => __('Home'), 'url' => route('home')],
        ['label' => __('My cart'), 'url' => route('cart')],
        ['label' => __('Checkout'), 'url' => null],
    ];
@endphp

@include('theme-ecommerce::partials.breadcrumbs', ['crumbs' => $crumbs])

<livewire:frontend.checkout />

@include('theme-ecommerce::partials.footer')

@include('frontend.partials.chat-widget')
@include('partials.custom-code-body')
</body>
</html>