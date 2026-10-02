<?php

use App\Support\DocHighlight;

/**
 * Unit coverage for the guide's syntax highlighter. Two properties matter
 * regardless of how the colours turn out: a snippet that is not valid PHP never
 * throws (documentation is full of fragments), and nothing in a snippet is ever
 * emitted as markup.
 */
it('colours php keywords, strings and variables', function () {
    $html = DocHighlight::render("if (\$live) { return Setting::get('site_name'); }");

    expect($html)
        ->toContain('<span class="doc-c-violet">if</span>')
        ->toContain('<span class="doc-c-violet">return</span>')
        ->toContain('<span class="doc-c-sky">$live</span>')
        ->toContain('<span class="doc-c-emerald">&#039;site_name&#039;</span>');
});

it('colours the keywords the guide leans on hardest', function () {
    // return and function were both genuinely missing from the map at one point
    // and neither crashed anything - they just came out plain. A keyword that
    // loses its colour is invisible, so it gets pinned here.
    $html = DocHighlight::render(
        'final class Foo extends Bar implements Baz { public function run(): void { foreach ([] as $i) {} } }'
    );

    foreach (['class', 'extends', 'implements', 'public', 'function', 'foreach', 'as', 'void'] as $keyword) {
        expect($html)->toContain('<span class="doc-c-violet">'.$keyword.'</span>');
    }
});

it('colours a screaming-case constant and the language literals', function () {
    $html = DocHighlight::render('MenuItem::GROUP_ADMIN_SIDEBAR === true && $x !== null;');

    expect($html)
        ->toContain('<span class="doc-c-violet">GROUP_ADMIN_SIDEBAR</span>')
        ->toContain('<span class="doc-c-violet">true</span>')
        ->toContain('<span class="doc-c-violet">null</span>');
});

it('tells a json key from a json string value', function () {
    $html = DocHighlight::render('{"name" : "Clock", "count": 3}', 'json');

    expect($html)
        ->toContain('<span class="doc-c-sky">&quot;name&quot;</span>')
        ->toContain('<span class="doc-c-emerald">&quot;Clock&quot;</span>')
        ->toContain('<span class="doc-c-amber">3</span>');
});

it('escapes markup in a snippet instead of emitting it', function () {
    $html = DocHighlight::render('echo "<script>alert(1)</script>";');

    expect($html)
        ->not->toContain('<script>')
        ->toContain('&lt;script&gt;');
});

it('does not throw on a snippet that is not valid php', function () {
    expect(DocHighlight::render("function (\$x { return 'unclosed"))
        ->toContain('return');
});

it('leaves plain text unstyled', function () {
    expect(DocHighlight::render('plugin.json        required - manifest', 'text'))
        ->toBe('plugin.json        required - manifest');
});

it('round-trips the snippet text so the copy button gets clean source', function () {
    // The visible block is highlighted markup and the copy button reads
    // textContent off it, so strip_tags of the result has to equal the input -
    // otherwise "Copy" hands over a file with stray punctuation in it.
    $source = "Setting::set('a', \"b < c\");\nreturn true;";

    $text = html_entity_decode(strip_tags(DocHighlight::render($source)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    expect($text)->toBe($source);
});
