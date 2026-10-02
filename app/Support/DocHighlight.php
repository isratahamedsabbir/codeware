<?php

namespace App\Support;

/**
 * Server-side syntax highlighting for the Developer Guide's code blocks.
 *
 * Done in PHP rather than a browser highlighter (highlight.js, Prism) on
 * purpose: the admin panel loads no charting or highlighting bundle today, and
 * adding one would mean a build-step dependency plus a flash of unhighlighted
 * code on every page view, for a page that exists to explain this one codebase.
 * token_get_all() is already in the box, runs in the same process that built
 * the HTML, and colours are plain Tailwind classes that follow the light/dark
 * theme like everything else here.
 *
 * PHP goes through the real tokenizer, so a snippet that is not valid PHP
 * (a folder listing, half an expression) is no worse off than it would have
 * been in any highlighter - it just comes back as inline HTML. JSON gets a
 * cheap regex pass because the manifests are the other thing people paste in
 * here, and the tokenizer has nothing useful to say about those.
 *
 * Every token's text is escaped with e() before it is wrapped, so the returned
 * string is safe to emit raw. Nothing here is ever trusted as markup.
 */
class DocHighlight
{
    /**
     * The tokenizer's own ids for keywords, which is every keyword token PHP
     * defines. A keyword missing from here is not a crash - it is a word that
     * quietly loses its colour - so the set is asserted against a representative
     * sample in the test rather than left to rot.
     *
     * @var array<int, true>
     */
    private const KEYWORDS = [
        // control flow
        T_IF => true, T_ELSE => true, T_ELSEIF => true, T_ENDIF => true,
        T_FOR => true, T_FOREACH => true, T_ENDWHILE => true, T_ENDFOR => true,
        T_ENDFOREACH => true, T_WHILE => true, T_DO => true, T_SWITCH => true,
        T_ENDSWITCH => true, T_CASE => true, T_DEFAULT => true, T_BREAK => true,
        T_CONTINUE => true, T_GOTO => true,
        // functions, classes and shapes
        T_FUNCTION => true, T_FN => true, T_CLASS => true, T_INTERFACE => true,
        T_TRAIT => true, T_ENUM => true, T_EXTENDS => true, T_IMPLEMENTS => true,
        T_ABSTRACT => true, T_FINAL => true, T_READONLY => true, T_INSTEADOF => true,
        // visibility and modifiers
        T_PUBLIC => true, T_PROTECTED => true, T_PRIVATE => true,
        T_STATIC => true, T_VAR => true, T_CONST => true,
        // values
        T_NEW => true, T_CLONE => true, T_INSTANCEOF => true, T_ECHO => true,
        T_PRINT => true, T_YIELD => true, T_YIELD_FROM => true, T_MATCH => true,
        T_THROW => true, T_ARRAY => true, T_LIST => true, T_CALLABLE => true,
        T_RETURN => true,
        // scope and imports
        T_NAMESPACE => true, T_USE => true, T_AS => true, T_GLOBAL => true,
        T_REQUIRE => true, T_REQUIRE_ONCE => true,
        T_INCLUDE => true, T_INCLUDE_ONCE => true,
        // language constructs that read as keywords
        T_ISSET => true, T_UNSET => true, T_EMPTY => true, T_EXIT => true,
        T_EVAL => true, T_TRY => true, T_CATCH => true, T_FINALLY => true,
        T_LOGICAL_AND => true, T_LOGICAL_OR => true, T_LOGICAL_XOR => true,
        T_DECLARE => true, T_ENDDECLARE => true, T_HALT_COMPILER => true,
    ];

    /**
     * Uppercase-only identifiers, which is how this codebase writes class
     * constants (MenuItem::GROUP_ADMIN_SIDEBAR, Plugins::MANIFEST). Matched on
     * shape rather than looked up, so a constant from any class in the app is
     * picked up without being registered here.
     */
    private const SHAPE = '/^[A-Z][A-Z0-9_]*$/';

    /**
     * Reserved words that PHP reports as a plain identifier (T_STRING) because
     * they are contextual rather than syntactic - the tokenizer has no token for
     * a literal or a type name, so they arrive looking exactly like a class name.
     *
     * Deliberately only the words that really are T_STRING. static, enum, match,
     * fn and the rest have their own token and are handled by KEYWORDS above;
     * listing them here would be a comment that is quietly wrong.
     *
     * @var array<string, true>
     */
    private const SOFT_KEYWORDS = [
        'true' => true, 'false' => true, 'null' => true,
        'self' => true, 'parent' => true,
        'int' => true, 'float' => true, 'string' => true, 'bool' => true,
        'void' => true, 'iterable' => true, 'object' => true,
        'mixed' => true, 'never' => true,
    ];

    /**
     * Token ids that get their own colour. Checked before KEYWORDS, because a
     * few (T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING) are more specific than
     * what a keyword pass would decide.
     *
     * @var array<int, string>
     */
    private const COLOURS = [
        T_COMMENT => 'doc-c-zinc',
        T_DOC_COMMENT => 'doc-c-zinc',
        T_CONSTANT_ENCAPSED_STRING => 'doc-c-emerald',
        T_ENCAPSED_AND_WHITESPACE => 'doc-c-emerald',
        T_INLINE_HTML => 'doc-c-zinc',
        T_VARIABLE => 'doc-c-sky',
        T_LNUMBER => 'doc-c-amber',
        T_DNUMBER => 'doc-c-amber',
        T_STRING => 'doc-c-plain',
        T_NAME_QUALIFIED => 'doc-c-sky',
        T_NAME_FULLY_QUALIFIED => 'doc-c-sky',
        T_NAME_RELATIVE => 'doc-c-sky',
        T_NS_SEPARATOR => 'doc-c-zinc',
        T_WHITESPACE => 'doc-c-plain',
        T_OPEN_TAG => 'doc-c-zinc',
        T_CLOSE_TAG => 'doc-c-zinc',
    ];

    /**
     * Highlight a snippet for display inside the admin panel.
     *
     * @param  string  $code  Raw source, not HTML.
     * @param  string|null  $lang  'php' by default; 'json' and 'text' are also understood.
     * @return string HTML safe to emit unescaped - every token is escaped individually.
     */
    public static function render(string $code, ?string $lang = null): string
    {
        return match ($lang) {
            'json' => self::json($code),
            'text', 'txt', 'plain' => self::plain($code),
            default => self::php($code),
        };
    }

    /**
     * PHP through the real tokenizer.
     *
     * The snippet is wrapped in an open tag first because token_get_all() treats
     * a leading bare word as inline HTML - which is right for a page of markup
     * and wrong for the `Settings::get('key')` fragment someone pasted in. The
     * wrapper is stripped again afterwards so it never reaches the output.
     *
     * TOKEN_PARSE is deliberately not passed: it validates as it tokenizes and
     * throws on the incomplete snippets that documentation is made of. Plain
     * tokenizing never throws, which is the property that makes this safe to
     * point at arbitrary text from the view.
     */
    private static function php(string $code): string
    {
        $code = str_replace(["\r\n", "\r"], "\n", $code);

        // Only the leading newline is added. token_get_all() flushes its last
        // token at EOF regardless, and appending a trailing one would ship a
        // blank line that the copy button then hands to the clipboard.
        $tokens = @token_get_all("<?php\n".$code);

        // The open tag we added is always the first token; drop it so the leading
        // newline we injected along with it does not become a visible blank line.
        array_shift($tokens);

        $out = '';

        foreach ($tokens as $token) {
            if (! is_array($token)) {
                // Single-character tokens: braces, punctuation, operators.
                $out .= e($token);

                continue;
            }

            $class = self::colourFor($token[0], $token[1]);

            // Two skips, both about not shipping markup that changes nothing:
            // whitespace is most of a snippet and adds no colour, and anything
            // that resolves to plain is already the colour it would inherit.
            $out .= $token[0] === T_WHITESPACE || $class === 'doc-c-plain'
                ? e($token[1])
                : self::wrap($class, $token[1]);
        }

        return $out;
    }

    private static function colourFor(int $id, string $text): string
    {
        // T_STRING is deliberately absent from COLOURS: the tokenizer reports
        // true, void and every class constant as a plain identifier, so it has
        // to fall through to the checks below rather than be coloured once and
        // for all.
        if ($id === T_STRING) {
            return isset(self::SOFT_KEYWORDS[strtolower($text)]) || preg_match(self::SHAPE, $text)
                ? 'doc-c-violet'
                : 'doc-c-sky';
        }

        if (isset(self::COLOURS[$id])) {
            return self::COLOURS[$id];
        }

        return isset(self::KEYWORDS[$id]) ? 'doc-c-violet' : 'doc-c-plain';
    }

    /**
     * JSON with a regex pass. Keys, string values, numbers and the three
     * literals each get a colour; everything else - the punctuation - is left
     * alone, which is all a reader needs to scan a manifest.
     */
    private static function json(string $code): string
    {
        $out = '';

        // Order matters: a quoted key is consumed by the first branch, so a
        // string value never gets re-coloured as a key on the next pass.
        $pattern = '/"(?:\\\\.|[^"\\\\])*"(?=\s*:)|"(?:\\\\.|[^"\\\\])*"|\b-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?\b|\b(?:true|false|null)\b/';

        $offset = 0;

        while (preg_match($pattern, $code, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $m[0][1];
            $matched = $m[0][0];

            $out .= e(substr($code, $offset, $start - $offset));

            $class = match (true) {
                self::isJsonKey($code, $start + strlen($matched)) => 'doc-c-sky',
                $matched === 'true' || $matched === 'false' => 'doc-c-violet',
                $matched === 'null' => 'doc-c-zinc',
                is_numeric(ltrim($matched, '-')) => 'doc-c-amber',
                default => 'doc-c-emerald',
            };

            $out .= self::wrap($class, $matched);

            $offset = $start + strlen($matched);
        }

        return $out.e(substr($code, $offset));
    }

    /**
     * Whether the token ending at $offset is an object key rather than a value.
     *
     * The only thing that tells them apart is the colon that follows, and the
     * pretty-printed manifests people paste in here put whitespace between the
     * quote and the colon - so the check has to skip over that whitespace rather
     * than look at the very next character.
     */
    private static function isJsonKey(string $code, int $offset): bool
    {
        $rest = ltrim(substr($code, $offset), " \t\n\r");

        return str_starts_with($rest, ':');
    }

    private static function plain(string $code): string
    {
        return e($code);
    }

    /**
     * Wrap a token in a colour span.
     *
     * Plain tokens are returned bare: doc-c-plain is the colour the block
     * already inherits, so a span around a comma would be markup with no
     * effect on it. Every other token is escaped here rather than by the
     * caller, which is the only reason this function exists.
     */
    private static function wrap(string $class, string $text): string
    {
        return $class === 'doc-c-plain'
            ? e($text)
            : '<span class="'.$class.'">'.e($text).'</span>';
    }
}
