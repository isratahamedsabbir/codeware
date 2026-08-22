{{-- Shows Settings → General → Loader (a GIF) the instant an auth form (login,
     register, forgot/reset password) is submitted, so there's visible feedback
     during the gap between submit and the server's redirect response — these
     pages are plain HTML forms, not Livewire, so this is a vanilla listener
     rather than wire:loading. Renders nothing if no loader GIF has been
     uploaded yet. --}}
@php($loaderUrl = \App\Models\Setting::get('loader'))
@if ($loaderUrl)
    <div id="auth-loader-overlay"
        class="fixed inset-0 z-[9999] hidden items-center justify-center bg-white/70 backdrop-blur-sm">
        <img src="{{ $loaderUrl }}" alt="Loading" class="h-16 w-16 object-contain">
    </div>
    <script>
        document.addEventListener('submit', function (event) {
            const overlay = document.getElementById('auth-loader-overlay');
            if (overlay && event.target instanceof HTMLFormElement) {
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
            }
        });
    </script>
@endif
