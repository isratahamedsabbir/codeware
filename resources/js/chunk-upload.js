/**
 * Chunked upload for Media Library files over 10MB — used by both the
 * standalone Media Library page (Index.php) and the picker modal
 * (PickerModal.php) so large files (video, big PDFs, ...) don't have to go
 * through a single giant multipart request the way Livewire's own
 * WithFileUploads does. Files at or under the threshold are untouched and
 * keep using the normal wire:model upload.
 *
 * Registered as an Alpine component so any <div x-data="mediaChunkUpload(...)">
 * placed inside a Livewire component gets `this.$wire` for free (Livewire
 * exposes it as an Alpine magic to every nested Alpine scope).
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('mediaChunkUpload', (config = {}) => ({
        thresholdBytes: 10 * 1024 * 1024,
        chunkSizeBytes: 5 * 1024 * 1024,
        allowedExt: config.allowedExt || 'jpg,jpeg,png,gif,webp,avif,pdf,mp4,mp3,doc,docx,xls,xlsx',
        chunkUploads: [],

        isLarge(file) {
            return file.size > this.thresholdBytes;
        },

        splitFiles(fileList) {
            const files = Array.from(fileList);

            return {
                small: files.filter((file) => !this.isLarge(file)),
                large: files.filter((file) => this.isLarge(file)),
            };
        },

        // allowedExt is passed per call (not read from the init config) because
        // the picker modal's allowed extensions change at runtime — whichever
        // <x-media-picker mimes="..."> last opened it — and a stale value
        // baked in at x-data init time would never pick that up.
        startChunkUploads(files, allowedExt) {
            files.forEach((file) => this.chunkUploadOne(file, allowedExt || this.allowedExt));
        },

        async chunkUploadOne(file, allowedExt) {
            const id = window.crypto?.randomUUID ? window.crypto.randomUUID() : `${Date.now()}-${Math.random().toString(16).slice(2)}`;
            const entry = { id, name: file.name, progress: 0, error: null, done: false };
            this.chunkUploads.push(entry);

            const totalChunks = Math.max(1, Math.ceil(file.size / this.chunkSizeBytes));
            const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            try {
                for (let index = 0; index < totalChunks; index++) {
                    const start = index * this.chunkSizeBytes;
                    const chunk = file.slice(start, start + this.chunkSizeBytes);

                    const form = new FormData();
                    form.append('chunk', chunk, file.name);
                    form.append('chunkIndex', String(index));
                    form.append('totalChunks', String(totalChunks));
                    form.append('uploadId', id);
                    form.append('filename', file.name);
                    form.append('allowedExt', allowedExt);

                    const response = await fetch('/admin/media-library/chunk-upload', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                        body: form,
                    });

                    if (!response.ok) {
                        const payload = await response.json().catch(() => ({}));
                        const message = payload?.errors
                            ? Object.values(payload.errors).flat().join(' ')
                            : (payload?.message || 'Upload failed.');
                        throw new Error(message);
                    }

                    const data = await response.json();
                    entry.progress = Math.round(((index + 1) / totalChunks) * 100);

                    if (data.done) {
                        entry.done = true;
                        this.$wire.call('finishChunkedUpload', data.media.id);
                        setTimeout(() => {
                            this.chunkUploads = this.chunkUploads.filter((upload) => upload.id !== id);
                        }, 1500);
                    }
                }
            } catch (error) {
                entry.error = error.message || 'Upload failed.';
            }
        },
    }));
});
