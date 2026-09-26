{{-- 404 Not Found — also the target of any ModelNotFoundException on a bound route parameter. --}}
<x-errors.page
    :code="404"
    :title="__('Page not found')"
    :message="__('We could not find the page you are looking for. It may have been moved, renamed, or removed.')"
/>
