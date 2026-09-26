{{--
    429 Too Many Requests — ThrottleRequestsException. Laravel sends the
    "Retry-After" header with the response, so surface the wait in the page too
    rather than making people guess.
--}}
@php
    $retryAfter = $exception->getHeaders()['Retry-After'] ?? null;
@endphp

<x-errors.page
    :code="429"
    :title="__('Too many requests')"
    :message="__('You have made too many requests in a short time. Please wait a moment and try again.')"
>
    @if ($retryAfter)
        <span class="err-note">
            {{ __('Try again in') }} <code>{{ $retryAfter }}s</code>
        </span>
    @endif
</x-errors.page>
