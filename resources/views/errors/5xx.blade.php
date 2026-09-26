{{-- Catch-all for any 5xx without a dedicated view (502, 504, …). --}}
@php
    $reference = strtoupper(substr(md5($exception->getStatusCode().'|'.request()->getMethod().'|'.request()->path()), 0, 8));
@endphp

<x-errors.page
    :code="$exception->getStatusCode()"
    :title="__('Something went wrong')"
    :message="__('We hit an unexpected problem while loading this page. Please try again in a moment.')"
    :reference="$reference"
/>
