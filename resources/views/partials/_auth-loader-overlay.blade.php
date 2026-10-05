{{-- Shows Settings → General → Loader (a GIF) the instant a plain auth form
     (register, forgot/reset password) is submitted, so there's visible feedback
     during the gap between submit and the server's redirect response. Falls
     back to the bundled default GIF if none has been uploaded yet.

     Only for forms that really navigate away. A Livewire form (wire:submit)
     answers in place — a wrong password or a validation error never leaves the
     page, so nothing would ever hide the overlay again and the user was stuck
     behind it until they reloaded. Those forms are skipped (Livewire calls
     preventDefault on them), and the overlay is also cleared whenever the page
     is shown again, e.g. via the browser's back button. --}}
@php
    $loaderUrl = \App\Models\Setting::get('loader') ?: asset('default/loader.gif');
@endphp
<div id="auth-loader-overlay"
    class="fixed inset-0 z-[9999] hidden items-center justify-center bg-white/70 backdrop-blur-sm">
    <img src="{{ $loaderUrl }}" alt="Loading" class="w-[150px] object-contain">
</div>
<script>
    (function () {
        const overlay = () => document.getElementById('auth-loader-overlay');

        const hide = () => {
            const el = overlay();
            if (el) { el.classList.add('hidden'); el.classList.remove('flex'); }
        };

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (! (form instanceof HTMLFormElement) || event.defaultPrevented) return;
            if ([...form.attributes].some((a) => a.name.startsWith('wire:submit'))) return;

            const el = overlay();
            if (el) { el.classList.remove('hidden'); el.classList.add('flex'); }
        });

        window.addEventListener('pageshow', hide);
    })();
</script>
