{{-- 429 Too Many Requests — ThrottleRequestsException. The Retry-After header still rides on the response; the page just tells the visitor to wait and try again. --}}
<x-errors.page :code="429" />
