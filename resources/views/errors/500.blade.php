{{--
    500 Internal Server Error. The reference is derived from the request, not
    from the exception, so it is safe to show and still traceable in the log
    via the URL it was generated from.
--}}
@php
    // Derived from the request rather than the exception, so it is safe to show
    // and still traceable in the log. Note $code is a component prop and does
    // not exist at this point, so the status is spelled out.
    $reference = strtoupper(substr(md5('500|'.request()->getMethod().'|'.request()->path()), 0, 8));
@endphp

<x-errors.page
    :code="500"
    :title="__('Something went wrong')"
    :message="__('An unexpected error occurred on our side. Our team has been notified — please try again in a moment.')"
    :reference="$reference"
/>
