{{-- Catch-all for any 5xx without a dedicated view (502, 504, …). --}}
<x-errors.page :code="$exception->getStatusCode()" />
