{{-- Settings → Custom Code → Head Code: pasted verbatim before </head> on
     every public theme page (never the admin/auth screens). --}}
@if (filled($customHeadCode = \App\Models\Setting::get('custom_head_code')))
{!! $customHeadCode !!}
@endif
