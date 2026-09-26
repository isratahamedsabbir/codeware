{{-- 503 Service Unavailable — scheduled maintenance and the "be right back" gate. --}}
@php
    $retryAfter = $exception->getHeaders()['Retry-After'] ?? null;
    $reference = strtoupper(substr(md5('503|'.request()->getMethod().'|'.request()->path()), 0, 8));
@endphp

<x-errors.page
    :code="503"
    :title="__('We will be back shortly')"
    :message="__('We are carrying out scheduled maintenance and will be back online shortly. Thanks for your patience.')"
    :reference="$reference"
>
    @if ($retryAfter)
        <span class="err-note">
            {{ __('Estimated downtime') }} <code>{{ (int) ceil($retryAfter / 60) }}m</code>
        </span>
    @endif
</x-errors.page>
