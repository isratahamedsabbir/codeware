document.addEventListener('alpine:init', () => {
    // Shared across every admin-row-actions dropdown so opening one always
    // closes any other that's open, instead of each row tracking independent
    // state (which let several stay open at once).
    window.Alpine.store('rowActions', { openId: null });
});
