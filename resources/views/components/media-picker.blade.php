@props([
    'model' => 'imageUrl',
    'label' => 'Image',
    'placeholder' => 'No file selected',
    'preview' => true,
    'pickerId' => null,
])

@php($pickerId = $pickerId ?: 'mp-' . preg_replace('/[^a-z0-9]/', '-', strtolower($model)))

{{-- ================================================================
     media-picker Blade component
     Dispatches 'open-media-picker' window event → PickerModal opens.
     Receives 'mediaPickerSelected' window event → updates Alpine + Livewire.
     ================================================================ --}}

<div data-media-picker-id="{{ $pickerId }}" x-data="{
    selectedUrl: @entangle($model).live,
    selectedTitle: null,
    selectedAlt: null,
    init() {
        const self = this;
        const pId = '{{ $pickerId }}';
        const lKey = '__mpHandler_' + pId;

        // Remove any stale listener from a previous mount of this picker
        if (window[lKey]) {
            window.removeEventListener('mediaPickerSelected', window[lKey]);
            delete window[lKey];
        }

        // Handler — find element fresh on every call (handles re-mounts)
        window[lKey] = function(ev) {
            const raw = ev.detail;
            const detail = Array.isArray(raw) ? raw[0] : raw;
            if (!detail || detail.pickerId !== pId) return;

            // Update Alpine reactive state
            self.selectedUrl = detail.url || null;
            self.selectedTitle = detail.title || null;
            self.selectedAlt = detail.alt || null;

            // Sync the value back to Livewire
            const el = document.querySelector('[data-media-picker-id=' + pId + ']');
            if (!el) return;
            let wireEl = el.parentElement;
            while (wireEl && !wireEl.hasAttribute('wire:id')) {
                wireEl = wireEl.parentElement;
            }
            if (wireEl) {
                const wire = window.Livewire && Livewire.find(wireEl.getAttribute('wire:id'));
                if (wire) wire.set('{{ $model }}', detail.url || null);
            }
        };

        window.addEventListener('mediaPickerSelected', window[lKey]);
    },
    openPicker() {
        window.dispatchEvent(new CustomEvent('open-media-picker', {
            detail: { pickerId: '{{ $pickerId }}' }
        }));
    },
    clearSelection() {
        this.selectedUrl = null;
        this.selectedTitle = null;
        this.selectedAlt = null;
        this.$wire.set('{{ $model }}', null);
    }
}" class="w-full min-w-0 space-y-2">
    @if ($label)
        <label class="text-[10px] font-bold uppercase tracking-widest text-slate-800 mb-2">{{ $label }}</label>
    @endif

    <div class="flex min-w-0 items-start gap-4">

        {{-- Preview thumbnail --}}
        @if ($preview)
            <button type="button" @click="openPicker()"
                class="relative h-24 w-24 shrink-0 overflow-hidden rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 transition-colors hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <template x-if="selectedUrl">
                    <img :src="selectedUrl" alt="" class="h-full w-full object-cover" />
                </template>
                <template x-if="!selectedUrl">
                    <div class="flex h-full w-full flex-col items-center justify-center gap-1 text-slate-400">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <span class="text-[9px] font-bold uppercase tracking-wider">Select</span>
                    </div>
                </template>
                {{-- Hover overlay when image is set --}}
                <template x-if="selectedUrl">
                    <div
                        class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity hover:opacity-100">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                        </svg>
                    </div>
                </template>
                {{-- X remove button — top-right corner --}}
                <template x-if="selectedUrl">
                    <button type="button" @click.stop="clearSelection()"
                        class="absolute top-1 right-1 z-10 flex h-5 w-5 items-center justify-center rounded-full bg-white shadow hover:bg-red-50 transition-colors">
                        <svg class="h-3 w-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </template>
            </button>
        @endif

        <div class="min-w-0 flex-1 space-y-2">
            {{-- Filename / URL display --}}
            <div class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm transition-colors hover:border-blue-400 hover:bg-blue-50"
                @click="openPicker()">
                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <span class="min-w-0 flex-1 truncate text-sm"
                    :class="selectedTitle || selectedUrl ? 'text-slate-700 font-medium' : 'text-slate-400'"
                    x-text="selectedTitle || (selectedUrl ? selectedUrl.split('/').pop() : '{{ $placeholder }}')"></span>
            </div>

            {{-- Button --}}
            <div class="flex items-center gap-2">
                <button type="button" @click="openPicker()"
                    class="rounded-md border border-slate-200 w-full bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-50">
                    Choose file
                </button>
            </div>
        </div>

    </div>
</div>
