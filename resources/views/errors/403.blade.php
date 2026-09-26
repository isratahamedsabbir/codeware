{{-- 403 Forbidden — thrown by Gate::authorize() / abort(403) and by the can: middleware. --}}
<x-errors.page
    :code="403"
    :title="__('Access denied')"
    :message="__('You do not have permission to view this page. If you believe this is a mistake, ask an administrator to review your access.')"
/>
