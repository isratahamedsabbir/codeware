import { readFileSync } from 'node:fs';
import { transform } from 'lightningcss';

/*
 |  Check that minifying a theme stylesheet did not change what it declares.
 |
 |  Lightning CSS legitimately merges adjacent rules with the same selector and
 |  drops redundant declarations, so the brace count is not a reliable guard on
 |  its own — that is why the minifier script compares it as a smoke test and
 |  this script does the real comparison.
 |
 |  Compares two files on the things that carry a theme's appearance: every
 |  custom property name (the design tokens), every declared property, and
 |  every selector. Reports what each side has that the other does not.
 |
 |  Usage: node scripts/diff-css.mjs <original.css> <minified.css>
 */

const [, , originalPath, minifiedPath] = process.argv;

const collect = (source) => {
    const { code } = transform({ filename: originalPath, code: Buffer.from(source), minify: false });
    const text = Buffer.from(code).toString('utf8');

    const customProperties = new Set([...text.matchAll(/(--[A-Za-z0-9_-]+)\s*:/g)].map((m) => m[1]));
    const properties = new Set([...text.matchAll(/[{;]\s*([a-z-]+)\s*:/g)].map((m) => m[1]));
    const selectors = new Set(
        [...text.matchAll(/([^{}@\/][^{}]*?)\s*\{/g)]
            .map((m) => m[1].trim().replace(/\s+/g, ' '))
            .filter((s) => s.length > 0),
    );

    return { customProperties, properties, selectors };
};

const original = collect(readFileSync(originalPath, 'utf8'));
const minified = collect(readFileSync(minifiedPath, 'utf8'));

const report = (label, left, right) => {
    const onlyInOriginal = [...left].filter((x) => !right.has(x));
    const onlyInMinified = [...right].filter((x) => !left.has(x));
    console.log(`  ${label}: ${left.size} -> ${right.size}`);
    if (onlyInOriginal.length) console.log(`    lost by minify: ${onlyInOriginal.slice(0, 12).join(', ')}${onlyInOriginal.length > 12 ? ' …' : ''}`);
    if (onlyInMinified.length) console.log(`    added by minify: ${onlyInMinified.slice(0, 12).join(', ')}${onlyInMinified.length > 12 ? ' …' : ''}`);
    if (!onlyInOriginal.length && !onlyInMinified.length) console.log('    identical');
};

console.log(`compare ${originalPath} -> ${minifiedPath}`);
report('custom properties', original.customProperties, minified.customProperties);
report('declared properties', original.properties, minified.properties);
report('selectors', original.selectors, minified.selectors);
