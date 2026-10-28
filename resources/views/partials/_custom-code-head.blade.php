{{-- Settings → Custom Code → Head Code: pasted verbatim before </head> on
     every public theme page (never the admin/auth screens), followed by the
     snippet picked in the page's SEO section. --}}
@if (filled($customHeadCode = \App\Support\CustomCode::render('head', $page ?? null)))
{!! $customHeadCode !!}
@endif
