import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";
import { readdirSync, existsSync } from 'node:fs';
import { join } from 'node:path';

/**
 * Every theme's own stylesheet, as a Vite input.
 *
 * Scanned rather than listed, because a theme is a module folder: a theme
 * installed later as a zip drops themes/{slug}/public/css/theme.css in and has
 * to get a bundle without anyone editing this file. A theme with no stylesheet
 * simply contributes no input and is served the catch-all storefront bundle
 * instead — see Themes::storefrontEntry().
 *
 * Keyed by a name that includes the slug rather than passed as a list, because
 * all of these files are called theme.css: Vite names an entry chunk after the
 * file, so inputs sharing a basename share a chunk name too, and the manifest
 * then collapses them into one entry that reports a file the wrong two of them
 * were never built from. The slug in the name is what keeps them separate.
 */
function themeStylesheets() {
    const dir = 'themes';

    if (!existsSync(dir)) {
        return {};
    }

    return Object.fromEntries(
        readdirSync(dir, { withFileTypes: true })
            .filter((entry) => entry.isDirectory() && existsSync(join(dir, entry.name, 'public/css/theme.css')))
            .map((entry) => [`theme-${entry.name}`, `${dir}/${entry.name}/public/css/theme.css`])
            .sort(([a], [b]) => a.localeCompare(b)),
    );
}
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
            // keeps using its own asset path — see themes/portfolio/public.
            //
            // The storefront bundle is further split per theme: a theme with a
            // stylesheet at themes/{slug}/public/css/theme.css is served
            // that, so its page pays only for the utility classes its own
            // templates use. storefront.css stays in the list as the catch-all
            // for a theme that ships none — it scans every theme, so such a
            // theme is styled rather than bare.
            //
            // The theme inputs are read off the filesystem (see above) rather
            // than listed, so installing a theme does not mean editing this
            // file.
            input: {
                app: 'resources/css/app.css',
                'app-js': 'resources/js/app.js',
                // Its own entry, because it has to be loadable on a page that
                // is *not* an admin page: the two-factor challenge and the
                // forced-enrolment screen are both plain Blade views on the
                // auth layout, and that layout serves the storefront bundle
                // (see partials._head's $assetBundle) rather than app.js — a
                // shopper's or a portal's sign-in must not drag in sortablejs,
                // echo and pusher to answer a WebAuthn prompt. A separate input
                // is how one file reaches both worlds; the admin bundle
                // imports it too, and Vite serves the two off one shared chunk.
                passkeys: 'resources/js/passkeys.js',
                storefront: 'resources/css/storefront.css',
                ...themeStylesheets(),
            },
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
