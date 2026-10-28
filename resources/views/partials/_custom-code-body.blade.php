{{-- Settings → Custom Code → Body Code: pasted verbatim just before </body>
     on every public theme page (never the admin/auth screens), followed by the
     snippet picked in the page's SEO section. --}}
@if (filled($customBodyCode = \App\Support\CustomCode::render('body', $page ?? null)))
{!! $customBodyCode !!}
@endif
