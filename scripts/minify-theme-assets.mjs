import { readFileSync, writeFileSync } from 'node:fs';
import { transform } from 'lightningcss';

/*
 |  Minify a theme's own CSS in place.
 |
 |  The portfolio theme ships its stylesheet as a standalone file under public/
 |  rather than through the Vite bundle, on purpose — the theme has to keep
 |  working when it is zipped out and installed on its own, with no build step.
 |  That also means nothing else minifies it, and an unminified stylesheet costs
 |  real points on the "Minify CSS" audit.
 |
 |  The file it rewrites is not kept readable, so run this only on a theme's
 |  shipped asset and not on anything a designer edits by hand day to day. For
 |  this one the source lives in version control, which is the only copy.
 |
 |  Usage: node scripts/minify-theme-assets.mjs <file.css> [more.css]
 |
 |  The guard compares the property names before and after, because that is what
 |  has to survive. A brace count is not a good signal: Lightning CSS legitimately
 |  merges same-selector rules and rewrites keyframe stops (0% to `from`, 100% to
 |  `to`), which is what the portfolio sheet's three-rule difference turned out
 |  to be. Comparing the custom property and property name sets catches a real
 |  loss and ignores a cosmetic one.
 */

const files = process.argv.slice(2);
const kb = (n) => (n / 1024).toFixed(1);

const declaredProperties = (css) =>
    new Set([...Buffer.from(transform({ code: Buffer.from(css), minify: false }).code).toString('utf8')
        .matchAll(/[{;]\s*([a-z-]+)\s*:/g)].map((m) => m[1]));

for (const file of files) {
    const source = readFileSync(file, 'utf8');

    let code;
    try {
        // The node binding wants bytes, not a JS string — handing it a string
        // fails deep in the Rust layer with "Get TypedArray info failed".
        code = Buffer.from(transform({ filename: file, code: Buffer.from(source), minify: true }).code).toString('utf8');
    } catch (error) {
        console.log(`  SKIPPED ${file}: ${error.message}`);
        continue;
    }

    const before = declaredProperties(source);
    const after = declaredProperties(code);
    const lost = [...before].filter((property) => !after.has(property));

    if (lost.length) {
        console.log(`  SKIPPED ${file}: minify dropped ${lost.length} propert(y/ies): ${lost.slice(0, 10).join(', ')}`);
        continue;
    }

    writeFileSync(file, code, 'utf8');
    console.log(`  ${file}: ${kb(Buffer.byteLength(source))} KB -> ${kb(Buffer.byteLength(code))} KB  (${before.size} properties kept)`);
}
