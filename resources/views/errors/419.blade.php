{{-- 419 Page Expired — CSRF token mismatch, i.e. TokenMismatchException. Usually a stale tab. --}}
<x-errors.page
    :code="419"
    :title="__('Your session has expired')"
    :message="__('For your security the page expired because the tab was left open too long. Refresh the page and try again.')"
/>
