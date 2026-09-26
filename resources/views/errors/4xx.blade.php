{{-- Catch-all for any 4xx without a dedicated view (405, 409, 451, …). --}}
<x-errors.page
    :code="$exception->getStatusCode()"
    :title="__('Request could not be completed')"
    :message="__('Something about this request was not valid. Check the address and try again.')"
/>
