{{-- Settings → Custom Code → Body Code: pasted verbatim just before </body>
     on every public theme page (never the admin/auth screens). --}}
@if (filled($customBodyCode = \App\Models\Setting::get('custom_body_code')))
{!! $customBodyCode !!}
@endif
