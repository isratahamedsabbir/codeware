import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            // Two CSS bundles and one JS bundle, split by who needs them.
            //
            // app.css and app.js are the admin: the panel, the vendor and
            // delivery panels, the auth screens. storefront.css is the public
            // site. There is deliberately no storefront JS entry — everything
            // app.js pulls in (sortablejs, laravel-echo, pusher-js, the date
            // range picker, the chunked uploader) is used exclusively by admin
            // views, so a shopper's page now loads no application JavaScript at
            // all. Livewire still injects its own runtime on pages that have a
            // component on them, and a theme with real JavaScript of its own
            // keeps using its own asset path — see public/themes/portfolio.
            //
            // A theme does not need an entry here to be fast; it needs the
            // storefront bundle, which every theme shares. See the @source list
            // in resources/css/storefront.css for how a theme's own classes get
            // into that bundle, and the two `not` rules there for what is kept
            // out of it.
            input: [
                'resources/css/app.css',
                'resources/css/storefront.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        watch: { ignored: ['**/storage/framework/views/**'] },
    },
    build: {
        // Both stylesheets are downloaded by a browser that cannot cache
        // across a deploy, and the storefront one is on the critical path of
        // every public page. Minify it, and let the build report the compressed
        // size so a regression here is visible in the build output rather than
        // in PageSpeed weeks later.
        cssMinify: true,
        reportCompressedSize: true,
    },
});
