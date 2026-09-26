{{-- Catch-all for any 4xx without a dedicated view (405, 409, 451, …). --}}
<x-errors.page :code="$exception->getStatusCode()" />
