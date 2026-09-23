#!/usr/bin/env python3
"""Regenerate public/docs/theme-builder-guide.pdf.

Run from the repo root:
    python scripts/theme_builder_guide.py

Reproduces the branded look of the original (dark-green cover, mint accents,
green section bands, dark code boxes) and keeps the whole document generated
from this single script so future edits are a text change, not a repaint.
"""

from pathlib import Path

from reportlab.lib.colors import HexColor, white
from reportlab.lib.enums import TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle
from reportlab.pdfgen import canvas as pdfcanvas
from reportlab.platypus import (
    BaseDocTemplate,
    Flowable,
    Frame,
    PageTemplate,
    Paragraph,
    Spacer,
    Table,
    TableStyle,
)

# ── palette (mirrors the original guide) ─────────────────────────────────────
DARK = HexColor("#052E1A")      # cover background
MINT = HexColor("#34D499")      # primary accent
MINT_L = HexColor("#A7F3D0")    # light mint (tagline)
MINT_2 = HexColor("#86EFAC")    # mint green (meta line)
SECT_BG = HexColor("#045B30")   # section heading band
CODE_BG = HexColor("#0F172A")   # code block background
CODE_TEXT = HexColor("#E2E8F0") # code block text
TEXT = HexColor("#18181B")
GRAY = HexColor("#71717A")
LINE = HexColor("#E4E4E7")
ROW_ALT = HexColor("#F1F7F4")

PAGE_W, PAGE_H = A4
MARGIN = 45.35
CONTENT_W = PAGE_W - 2 * MARGIN


# ── canvas plumbing: cover art + per-body-page footer ────────────────────────
class GuideCanvas(pdfcanvas.Canvas):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.__first_page = True

    def _cover_art(self):
        self.setFillColor(DARK)
        self.rect(0, 0, PAGE_W, PAGE_H, stroke=0, fill=1)

        badge = "Theme Settings · Admin Docs"
        self.setFont("Helvetica-Bold", 11)
        tw = self.stringWidth(badge, "Helvetica-Bold", 11)
        pad_x, h = 12, 24
        self.setFillColor(MINT)
        self.roundRect(MARGIN, 772, tw + 2 * pad_x, h, 12, stroke=0, fill=1)
        self.setFillColor(DARK)
        self.drawString(MARGIN + pad_x, 778, badge)

        self.setFont("Helvetica-Bold", 34)
        self.setFillColor(white)
        self.drawString(MARGIN, 721, "Theme Builder Guide")

        self.setFont("Helvetica", 14)
        self.setFillColor(MINT_L)
        self.drawString(MARGIN, 683, "How to build and install a theme for your Codeware site")

        self.setStrokeColor(MINT)
        self.setLineWidth(1)
        self.line(MARGIN, 664, PAGE_W - MARGIN, 664)

        self.setFont("Helvetica", 10.5)
        self.setFillColor(MINT_2)
        self.drawString(MARGIN, 85, r"resources/views/frontend/themes/   ·   Admin > Theme Settings")

    def _body_page(self):
        self.setFillColor(white)
        self.rect(0, 0, PAGE_W, PAGE_H, stroke=0, fill=1)
        self.setStrokeColor(LINE)
        self.setLineWidth(0.5)
        self.line(MARGIN, 34, PAGE_W - MARGIN, 34)
        self.setFont("Helvetica", 7.5)
        self.setFillColor(GRAY)
        self.drawString(MARGIN, 22.7, "Codeware Theme Builder Guide")
        self.drawRightString(PAGE_W - MARGIN, 22.7, f"Page {self._pageNumber}")

    def showPage(self):
        if self.__first_page:
            self._cover_art()
            self.__first_page = False
        else:
            self._body_page()
        super().showPage()


# ── flowables ────────────────────────────────────────────────────────────────
class SectionHeading(Flowable):
    """Full-width green band with white bold text."""

    def __init__(self, text, fontSize=15, height=26):
        super().__init__()
        self.text = text
        self.fontSize = fontSize
        self.height = height

    def wrap(self, availWidth, availHeight):
        self.width = availWidth
        return availWidth, self.height

    def draw(self):
        self.canv.saveState()
        self.canv.setFillColor(SECT_BG)
        self.canv.roundRect(0, 0, self.width, self.height, 5, stroke=0, fill=1)
        self.canv.setFillColor(white)
        self.canv.setFont("Helvetica-Bold", self.fontSize)
        self.canv.drawString(10, (self.height - self.fontSize) / 2 + 1, self.text)
        self.canv.restoreState()


class CodeBlock(Flowable):
    """Rounded dark code band; content is shown verbatim (monospace)."""

    FONT = "Courier"
    SIZE = 8.8
    LEAD = 12.0
    PAD = 7

    def __init__(self, text):
        super().__init__()
        self.lines = text.rstrip("\n").split("\n")
        self.width = 0
        self.height = 0
        self.spaceBefore = 6
        self.spaceAfter = 9

    def wrap(self, availWidth, availHeight):
        self.width = availWidth
        self.height = self.LEAD * len(self.lines) + 2 * self.PAD
        return self.width, self.height

    def draw(self):
        self.canv.saveState()
        self.canv.setFillColor(CODE_BG)
        self.canv.roundRect(0, 0, self.width, self.height, 4, stroke=0, fill=1)
        self.canv.setFillColor(CODE_TEXT)
        self.canv.setFont(self.FONT, self.SIZE)
        tx = self.canv.beginText(self.PAD, self.height - self.PAD - self.SIZE)
        tx.setLeading(self.LEAD)
        for line in self.lines:
            tx.textLine(line)
        self.canv.drawText(tx)
        self.canv.restoreState()


def S(name, **kwargs):
    base = {
        "fontName": "Helvetica",
        "fontSize": 9.5,
        "leading": 13.5,
        "textColor": TEXT,
        "alignment": TA_LEFT,
        "spaceAfter": 7,
    }
    base.update(kwargs)
    return ParagraphStyle(name, **base)


st_body = S("Body")
st_bullet = S("Bullet", leftIndent=16, bulletIndent=4, spaceAfter=4)
st_tip = S("Tip", textColor=SECT_BG, spaceBefore=2, spaceAfter=8)
st_sub = S("Sub", fontSize=10.5, leading=13, spaceAfter=3, fontName="Helvetica-Bold")
st_signoff = S("Signoff", fontSize=10.5, leading=14, textColor=SECT_BG, fontName="Helvetica-Bold", spaceBefore=6)
st_cell = S("Cell", fontSize=8.6, leading=11.6, spaceAfter=0)
st_head = S("Head", fontName="Helvetica-Bold", fontSize=8.6, leading=11.6, textColor=white, spaceAfter=0)


def h(n, text):
    return SectionHeading(f"{n}. {text}".replace("&amp;", "&"))


def p(text):
    return Paragraph(text, st_body)


def b(text):
    return Paragraph(text, st_bullet, bulletText="•")


def code(text):
    return CodeBlock(text)


def table(headers, rows, widths=None):
    data = [[Paragraph(hh, st_head) for hh in headers]]
    for r in rows:
        data.append([Paragraph(c, st_cell) for c in r])

    t = Table(data, colWidths=widths or [None] * len(headers), repeatRows=1)
    t.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), SECT_BG),
        ("ROWBACKGROUNDS", (0, 1), (-1, -1), [white, ROW_ALT]),
        ("GRID", (0, 0), (-1, -1), 0.4, LINE),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("TOPPADDING", (0, 0), (-1, -1), 4),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
        ("LEFTPADDING", (0, 0), (-1, -1), 6),
        ("RIGHTPADDING", (0, 0), (-1, -1), 6),
    ]))
    return t


# ── document construction ────────────────────────────────────────────────────
def build_story():
    story = []

    # The cover art occupies the top ~180pt, so push the body below it.
    story.append(Spacer(1, 125))
    story.append(SectionHeading("Theme Builder Guide", fontSize=22, height=34))

    story.append(p(
        "This guide explains how a Codeware theme works, what files it needs, what data its templates "
        "receive, and how to package &amp; install it. Read it top to bottom the first time, then use the "
        "section headings to jump back in while you build."
    ))

    # 1. What a theme is
    story.append(h(1, "What a theme is"))
    story.append(p(
        "A theme is a single folder of Blade templates that styles the public site. Every folder under "
        "<font face='Courier' size='8'>resources/views/frontend/themes/</font> is a theme; the folder name "
        "is the theme's slug. The Admin panel's Theme Settings page "
        "(<font face='Courier' size='8'>admin.theme-settings</font>) lists them automatically by scanning "
        "that directory, and whichever is marked <b>Live</b> in the picker is the active theme."
    ))
    story.append(p(
        "A theme may also ship two optional extras: a <font face='Courier' size='8'>theme.json</font> "
        "manifest (its name, version, author and tags — what the admin picker shows, see §3) and a "
        "<font face='Courier' size='8'>settings.blade.php</font> (its own admin settings screen, see §8). "
        "Neither is required for the theme to work."
    ))
    story.append(p(
        "Themes are pure views — no routes, controllers, or models. Pages are created in the admin CMS and "
        "the framework renders them through the active theme's templates. A theme can be as small as the "
        "two required templates (home + page), or as large as the built-in ecommerce theme with shop, "
        "product, blog, cart and account pages."
    ))

    # 2. Folder structure
    story.append(h(2, "Folder structure"))
    story.append(code(
        "resources/views/frontend/themes/\n"
        "`-- my-theme/\n"
        "    |-- theme.json                 # optional - manifest: name, version, author, tags\n"
        "    |-- settings.blade.php         # optional - theme's own admin settings screen\n"
        "    |-- home.blade.php             # REQUIRED - homepage\n"
        "    |-- page.blade.php             # REQUIRED - every standalone page\n"
        "    |-- shop.blade.php             # optional - catalog (falls back to ecommerce)\n"
        "    |-- product.blade.php          # optional - product detail\n"
        "    |-- blog.blade.php             # optional - blog feed\n"
        "    |-- post.blade.php             # optional - blog post detail\n"
        "    |-- cart.blade.php             # optional - shopping cart\n"
        "    |-- checkout.blade.php         # optional - checkout\n"
        "    |-- order-confirmation.blade.php  # optional - post-checkout confirmation\n"
        "    |-- favorites.blade.php        # optional - saved favorites\n"
        "    |-- category.blade.php         # optional - category landing\n"
        "    |-- brand.blade.php            # optional - brand landing\n"
        "    |-- tag.blade.php              # optional - tag landing\n"
        "    |-- auth/                      # optional - login, register, forgot/reset password\n"
        "    |-- account/                   # optional - dashboard, orders, order, profile\n"
        "    `-- partials/                  # your own partials (header, footer, cards...)\n"
    ))
    story.append(p(
        "The <font face='Courier' size='8'>home</font> and <font face='Courier' size='8'>page</font> "
        "templates are always rendered from the active theme. Every other storefront view (shop, product, "
        "category, brand, tag, blog, post, cart, checkout, favorites, order-confirmation, account.dashboard, "
        "account.orders, account.order, account.profile) uses "
        "<font face='Courier' size='8'>Themes::view('name')</font>: your theme's template when you ship one, "
        "otherwise the ecommerce theme's. That means a one-page portfolio theme only needs "
        "<font face='Courier' size='8'>home.blade.php</font> and "
        "<font face='Courier' size='8'>page.blade.php</font> — every missing page silently falls back to the "
        "ecommerce theme."
    ))
    story.append(p(
        "Customer auth views (login, register, forgot/reset password under "
        "<font face='Courier' size='8'>auth/</font>) behave the same way, but only while the active theme is "
        "the ecommerce theme — with any other theme they come from the shared "
        "<font face='Courier' size='8'>pages::auth.*</font> views instead, and the "
        "<font face='Courier' size='8'>account/</font> pages are ecommerce-only too (see §11)."
    ))
    story.append(p(
        "Static assets (CSS, JavaScript) are not served from the theme folder — they live in "
        "<font face='Courier' size='8'>public/themes/&lt;slug&gt;/</font> and are linked with "
        "<font face='Courier' size='8'>asset('themes/&lt;slug&gt;/...')</font> (see §9)."
    ))

    # 3. Theme manifest
    story.append(h(3, "Theme manifest (theme.json)"))
    story.append(p(
        "A theme can advertise itself with a <font face='Courier' size='8'>theme.json</font> manifest at "
        "the folder's root. The admin picker reads it to render the theme's card — name, version, author, "
        "description and tags — instead of just the folder name. The manifest is optional: when a theme "
        "ships none, the card falls back to the slug as the name, no author, version "
        "<font face='Courier' size='8'>1.0.0</font>, and no tags, so every theme still has a well-formed "
        "manifest."
    ))
    story.append(code(
        "{\n"
        '    "name": "My Theme",\n'
        '    "version": "1.2.0",\n'
        '    "author": "Acme Studio",\n'
        '    "description": "A lightweight portfolio theme for photographers.",\n'
        '    "tags": ["portfolio", "light"]\n'
        "}\n"
    ))
    story.append(p(
        "All fields are optional strings; <font face='Courier' size='8'>tags</font> is an array of strings. "
        "Empty or missing values fall back to the defaults above, and a malformed "
        "<font face='Courier' size='8'>theme.json</font> is treated as if it were absent — a broken file "
        "never breaks the picker."
    ))

    # 4. The two required templates (home)
    story.append(h(4, "The two required templates"))
    story.append(Paragraph("<b>home.blade.php</b>", st_sub))
    story.append(p(
        "Rendered at the site root. Receives the CMS \"home\" page and its sections plus the standard "
        "payload. Start from this skeleton:"
    ))
    story.append(code(
        "<!DOCTYPE html>\n"
        "<html lang=\"{{ str_replace('_', '-', app()->getLocale()) }}\" dir=\"{{ \\App\\Support\\Locale::direction() }}\">\n"
        "<head>\n"
        "    @include('partials.head')\n"
        "    @include('partials.seo-meta')\n"
        "    <link rel=\"stylesheet\" href=\"{{ asset('themes/my-theme/style.css') }}\">\n"
        "</head>\n"
        "<body>\n"
        "    @php\n"
        "        $siteName = \\App\\Models\\Setting::get('site_name', config('app.name'));\n"
        "    @endphp\n"
        "    <header>\n"
        "        <a href=\"{{ url('/') }}\">{{ $siteName }}</a>\n"
        "        <nav>\n"
        "            @foreach ($menuItems as $item)\n"
        "                <a href=\"{{ url($item->url) }}\">{{ $item->label }}</a>\n"
        "            @endforeach\n"
        "        </nav>\n"
        "    </header>\n"
        "    <main>\n"
        "        @if ($heroImage = \\App\\Models\\Setting::get('home_hero_image'))\n"
        "            <img src=\"{{ $heroImage }}\" alt=\"\">\n"
        "        @endif\n"
        "        @foreach ($sections as $section)\n"
        "            @foreach ($section->localizedCards() as $card)\n"
        "                <article>\n"
        "                    @if ($card['title'])<h2>{{ $card['title'] }}</h2>@endif\n"
        "                    @if ($card['description'])<p>{{ $card['description'] }}</p>@endif\n"
        "                    @if ($card['image'])<img src=\"{{ $card['image'] }}\" alt=\"\">@endif\n"
        "                </article>\n"
        "            @endforeach\n"
        "        @endforeach\n"
        "    </main>\n"
        "    <livewire:frontend.chat-widget />\n"
        "    @fluxScripts\n"
        "    <script src=\"{{ asset('themes/my-theme/script.js') }}\" defer></script>\n"
        "</body>\n"
        "</html>\n"
    ))

    # 5. Standard page template
    story.append(h(5, "Standard page template (page.blade.php)"))
    story.append(p(
        "Used for every non-home page (about, contact, faq, and any page you create). Identical payload to "
        "home, scoped to the requested page instead of the home page. The template can reuse a shared "
        "partial:"
    ))
    story.append(code(
        "<!DOCTYPE html>\n"
        "<html lang=\"{{ str_replace('_', '-', app()->getLocale()) }}\" dir=\"{{ \\App\\Support\\Locale::direction() }}\">\n"
        "<head>\n"
        "    @include('partials.head')\n"
        "    @include('partials.seo-meta')\n"
        "    <link rel=\"stylesheet\" href=\"{{ asset('themes/my-theme/style.css') }}\">\n"
        "</head>\n"
        "<body>\n"
        "    @include('themes.my-theme.partials.header')\n"
        "    <main>\n"
        "        <h1>{{ $page?->getTranslation('title', 'en', false) ?: $title }}</h1>\n"
        "        @foreach ($sections as $section)\n"
        "            @foreach ($section->localizedCards() as $card)\n"
        "                @include('themes.my-theme.partials.card', ['card' => $card])\n"
        "            @endforeach\n"
        "        @endforeach\n"
        "    </main>\n"
        "    @include('themes.my-theme.partials.footer')\n"
        "    @fluxScripts\n"
        "</body>\n"
        "</html>\n"
    ))
    story.append(Paragraph(
        "<b>Tip:</b> $page can be null on view pages that have no paired CMS Page (shop, blog, brand, tag, "
        "favorites, cart…), so guard every $page read with $page?-&gt;….",
        st_tip,
    ))

    # 6. Data your templates receive
    story.append(h(6, "Data your templates receive"))
    story.append(p("Every template gets the same shared payload from the frontend controller:"))
    story.append(table(
        ["Variable", "What it holds"],
        [
            ["$page", "The current CMS Page model, or null on views without one (shop, blog, tags, cart…)."],
            ["$sections", "Collection of the page's CMS sections (Puck-built content cards). Empty on pages with no paired Page."],
            ["$title", "Page SEO title, else the global SEO title, else the site name — used by both the &lt;title&gt; and the page body."],
            ["$navPages", "Standalone published pages in admin sort order — the nav list (portfolio/default style)."],
            ["$menuItems", "The admin-managed \"Frontend\" menu items — the nav list (ecommerce style). Each has -&gt;label and -&gt;url."],
            ["$showVendorLogin", "Whether to show a Vendor Login link (false when the vendor portal is disabled)."],
            ["$currentSlug", "The current route's slug (home, shop, blog, the page slug…) — handy for active nav states."],
        ],
        widths=[CONTENT_W * 0.24, CONTENT_W * 0.76],
    ))
    story.append(Paragraph("<b>Per-view extra data</b>", st_sub))
    story.append(table(
        ["Template", "Extra variables"],
        [
            ["home", "None beyond the shared payload; the hero image/tagline come from settings (see §7)."],
            ["shop", "$products (paginated), $categories, $brands, $tags, $attributeFacets, $priceBounds, $filters"],
            ["product", "$product (with gallery, brand, tags, categories, faqs, page), $related"],
            ["category", "$category, $children, $products"],
            ["brand / tag", "$brand / $tag, plus $products"],
            ["blog", "$posts (paginated, with category &amp; user), $categories"],
            ["post", "$post, $related (other posts)"],
            ["favorites", "$products (paginated favorite products)"],
            ["cart / checkout", "No extra data — rendering happens in the CartPage/Checkout Livewire components (see §11)."],
            ["order-confirmation", "$order (with items)"],
            ["account.*", "dashboard/orders: $orders; order: $order, $order items. Account pages only exist while the ecommerce theme is active."],
        ],
        widths=[CONTENT_W * 0.2, CONTENT_W * 0.8],
    ))

    # 7. Settings, routes & helpers
    story.append(h(7, "Settings, routes &amp; helpers"))
    story.append(p(
        "Every setting is read with <font face='Courier' size='8'>\\App\\Models\\Setting::get('key', "
        "default)</font> and written with "
        "<font face='Courier' size='8'>Setting::set('key', value)</font>. Settings the frontend commonly "
        "uses:"
    ))
    story.append(table(
        ["Setting", "Purpose"],
        [
            ["site_name", "Site/brand name shown in headers, footers, &lt;title&gt;."],
            ["site_icon / site_icon_white", "Logo image URLs (white variant for dark headers)."],
            ["site_tagline", "Short line under the site name on the homepage hero."],
            ["home_hero_image", "Homepage hero banner image URL."],
            ["home_promo_banner_1/2", "Two promotional strips below the hero."],
            ["contact_email / contact_address", "Contact details for the footer / contact section."],
            ["chat_widget_enabled", "Show a chat widget — the portfolio template gates &lt;livewire:frontend.chat-widget /&gt; on it."],
            ["seo_meta_title / seo_meta_description", "Global SEO fallbacks (used by the seo-meta partial)."],
        ],
        widths=[CONTENT_W * 0.34, CONTENT_W * 0.66],
    ))
    story.append(p(
        "Name your own keys under the <font face='Courier' size='8'>theme_&lt;slug&gt;_*</font> prefix to "
        "keep each theme's values namespaced and side-effect free — see §8 for the theme settings screen."
    ))
    story.append(Paragraph("<b>Link to pages and routes</b>", st_sub))
    story.append(p(
        "Use the named routes from your templates: <font face='Courier' size='8'>route('shop')</font> for "
        "the catalog, <font face='Courier' size='8'>route('blog')</font> for the blog, "
        "<font face='Courier' size='8'>route('products.show', ['slug' =&gt; $product-&gt;slug])</font>, "
        "<font face='Courier' size='8'>route('shop.category', ['slug' =&gt; "
        "$category-&gt;page-&gt;slug])</font>, <font face='Courier' size='8'>route('cart')</font>, "
        "<font face='Courier' size='8'>route('checkout')</font>, "
        "<font face='Courier' size='8'>route('favorites')</font>, and "
        "<font face='Courier' size='8'>url($menuItem-&gt;url)</font> or "
        "<font face='Courier' size='8'>url($page-&gt;slug)</font> for CMS pages. Right-click anywhere on "
        "the live site — \"Copy link\" — to grab real URLs while you prototype."
    ))
    story.append(Paragraph("<b>Language &amp; direction</b>", st_sub))
    story.append(p(
        "The site is bilingual (English / Bengali). Use <font face='Courier' size='8'>__('...')</font> for "
        "interface strings, <font face='Courier' size='8'>app()-&gt;getLocale()</font> for the current "
        "locale, and translatable model fields are JSON arrays — read them with "
        "<font face='Courier' size='8'>$page-&gt;getTranslation('title', 'en', false)</font> or "
        "<font face='Courier' size='8'>$post-&gt;getTranslation('title', app()-&gt;getLocale(), "
        "false)</font>. Set the <font face='Courier' size='8'>dir</font> attribute on "
        "<font face='Courier' size='8'>&lt;html&gt;</font> with "
        "<font face='Courier' size='8'>\\App\\Support\\Locale::direction()</font>."
    ))

    # 8. Theme settings screen
    story.append(h(8, "Theme settings screen (settings.blade.php)"))
    story.append(p(
        "Drop a <font face='Courier' size='8'>settings.blade.php</font> at the theme folder's root and the "
        "admin's Theme Settings page renders it as its own \"Theme Settings\" section whenever that theme is "
        "selected in the picker. It is an admin-only screen, so it edits the settings table directly — "
        "nothing in it renders on the public site."
    ))
    story.append(Paragraph("1. Fields bind to settings", st_sub))
    story.append(p(
        "Use Flux fields. Every control's key starts with the theme slug so settings stay namespaced per "
        "theme — a theme named <font face='Courier' size='8'>first-one</font> uses "
        "<font face='Courier' size='8'>settings.theme_first_one_</font>&lt;key&gt;:"
    ))
    story.append(code(
        "<flux:field>\n"
        "    <flux:label>Hero badge</flux:label>\n"
        "    <flux:input wire:model=\"settings.theme_first_one_hero_badge\" />\n"
        "</flux:field>\n"
    ))
    story.append(Paragraph("2. Read it in your theme views", st_sub))
    story.append(code(
        "{{-- inside home.blade.php --}}\n"
        "@php($badge = \\App\\Models\\Setting::get('theme_first_one_hero_badge'))\n"
        "@if ($badge) <span>{{ $badge }}</span> @endif\n"
    ))
    story.append(Paragraph("3. Stored in the database", st_sub))
    story.append(p(
        "Values persist in the <font face='Courier' size='8'>settings</font> table under the "
        "<font face='Courier' size='8'>theme_&lt;slug&gt;_*</font> key format and survive theme switching — "
        "each theme keeps its own values. Read with "
        "<font face='Courier' size='8'>Setting::get()</font>, write with "
        "<font face='Courier' size='8'>Setting::set()</font>. The default theme's "
        "<font face='Courier' size='8'>settings.blade.php</font> is a working example."
    ))

    # 9. CSS & JavaScript
    story.append(h(9, "CSS &amp; JavaScript (optional)"))
    story.append(p(
        "Blade files are the only thing a theme strictly needs, but most themes ship a stylesheet. Put "
        "static assets in <font face='Courier' size='8'>public/themes/&lt;slug&gt;/</font> and link them "
        "with Laravel's <font face='Courier' size='8'>asset()</font> helper — the portfolio theme is a "
        "working example:"
    ))
    story.append(code(
        "public/themes/my-theme/\n"
        "|-- style.css\n"
        "`-- script.js\n"
    ))
    story.append(p(
        "If you skip your own stylesheet, the site's global CSS (Tailwind, via the "
        "<font face='Courier' size='8'>@vite</font> assets in "
        "<font face='Courier' size='8'>partials.head</font>) is already available — the ecommerce theme "
        "builds its whole design on Tailwind utility classes with no theme CSS file at all."
    ))

    # 10. Livewire components you can embed
    story.append(h(10, "Livewire components you can embed"))
    story.append(p(
        "Rich interactive blocks are Livewire components, not plain Blade partials. Drop them into your "
        "templates with <font face='Courier' size='8'>@livewire(...)</font>. Use these in your theme:"
    ))
    story.append(Paragraph(
        "<font face='Courier' size='8'>&lt;livewire:frontend.chat-widget /&gt;</font> — the support chat "
        "bubble (portfolio home uses it, gated on the setting).",
        st_bullet, bulletText="•",
    ))
    story.append(Paragraph(
        "<font face='Courier' size='8'>CartPage</font> / <font face='Courier' size='8'>Checkout</font> — "
        "the cart and checkout Livewire components your "
        "<font face='Courier' size='8'>cart.blade.php</font> / "
        "<font face='Courier' size='8'>checkout.blade.php</font> wrap (see the ecommerce theme's templates "
        "for exact usage).",
        st_bullet, bulletText="•",
    ))
    story.append(Paragraph(
        "<font face='Courier' size='8'>@fluxScripts</font> and "
        "<font face='Courier' size='8'>@fluxAppearance</font> — Flux UI's scripts; include the former "
        "before <font face='Courier' size='8'>&lt;/body&gt;</font> exactly as the examples do.",
        st_bullet, bulletText="•",
    ))
    story.append(p(
        "The easiest way to build a feature-complete ecommerce theme is to duplicate the ecommerce folder "
        "and restyle it — every interaction (add to cart, favorites, checkout) is already wired."
    ))

    # 11. Packaging & installing a theme
    story.append(h(11, "Packaging &amp; installing a theme"))
    story.append(Paragraph("<b>Step 1 — build the folder</b>", st_sub))
    story.append(p(
        "Create a folder named after the theme (lowercase letters, numbers, dashes, underscores — "
        "<font face='Courier' size='8'>my-theme</font>, not <i>My Theme</i>). The name becomes the slug. Put "
        "your <font face='Courier' size='8'>home.blade.php</font>, "
        "<font face='Courier' size='8'>page.blade.php</font>, other templates, a "
        "<font face='Courier' size='8'>theme.json</font> manifest and "
        "<font face='Courier' size='8'>settings.blade.php</font> (both optional) inside."
    ))
    story.append(Paragraph("<b>Step 2 — zip it</b>", st_sub))
    story.append(p(
        "Zip the folder itself, so the zip contains exactly one top-level folder inside (e.g. "
        "<font face='Courier' size='8'>my-theme/…</font>). The installer accepts a single root folder; loose "
        "templates at the zip root, multiple folders, or a different folder name than the theme slug will be "
        "rejected. (Auto-generated junk like <font face='Courier' size='8'>__MACOSX</font> and dotfiles is "
        "ignored.)"
    ))
    story.append(Paragraph("<b>Limits enforced on upload</b>", st_sub))
    story.append(Paragraph(
        "50 MB / 2,000 files maximum — keep themes lean (fonts and images should stay in the media library / "
        "CDN).",
        st_bullet, bulletText="•",
    ))
    story.append(Paragraph(
        "Path-traversal and absolute paths are rejected (\"..\" escapes), so every extracted file stays "
        "inside the theme folder.",
        st_bullet, bulletText="•",
    ))
    story.append(Paragraph(
        "An existing theme of the same slug is never overwritten — rename the folder and re-zip.",
        st_bullet, bulletText="•",
    ))
    story.append(Paragraph("<b>Step 3 — upload in the admin</b>", st_sub))
    story.append(p(
        "Open Admin → Theme Settings → Install Theme, choose the zip, and install. The new card appears in "
        "the picker immediately (a browser reload refreshes the list)."
    ))
    story.append(Paragraph("<b>Step 4 — activate</b>", st_sub))
    story.append(p(
        "Click the theme's card in the picker, then Save Theme Settings. The Live badge moves to the new "
        "active theme and the public site starts rendering it. To go back, pick another theme and save again."
    ))
    story.append(p(
        "<b>Note:</b> anyone with code-level access can also just drop a folder into "
        "<font face='Courier' size='8'>resources/views/frontend/themes/</font> — it shows up in the picker "
        "on the next visit. The zip installer exists so non-developers can hand over themes too."
    ))

    # 12. Shipping checklist
    story.append(h(12, "Shipping checklist"))
    story.append(Paragraph(
        "Folder name is a clean slug (<font face='Courier' size='8'>my-theme</font>), and it matches what "
        "you tell the developer.",
        st_bullet, bulletText="•",
    ))
    story.append(Paragraph(
        "<font face='Courier' size='8'>home.blade.php</font> and "
        "<font face='Courier' size='8'>page.blade.php</font> exist — the site renders without them.",
        st_bullet, bulletText="•",
    ))
    story.append(Paragraph(
        "All optional pages you want (shop, product, blog…) are overridden; everything else falls back to "
        "ecommerce gracefully.",
        st_bullet, bulletText="•",
    ))
    story.append(Paragraph(
        "Every <font face='Courier' size='8'>$page</font> read is null-safe with "
        "<font face='Courier' size='8'>$page?-&gt;…</font>.",
        st_bullet, bulletText="•",
    ))
    story.append(Paragraph(
        "Translated fields use <font face='Courier' size='8'>getTranslation()</font>; interface strings use "
        "<font face='Courier' size='8'>__('…')</font>.",
        st_bullet, bulletText="•",
    ))
    story.append(Paragraph(
        "Settings are read via <font face='Courier' size='8'>Setting::get()</font>; assets linked via "
        "<font face='Courier' size='8'>asset('themes/&lt;slug&gt;/…')</font>.",
        st_bullet, bulletText="•",
    ))
    story.append(Paragraph(
        "<font face='Courier' size='8'>theme.json</font> (if shipped) is valid JSON, and "
        "<font face='Courier' size='8'>settings.blade.php</font> (if shipped) saves and reloads its "
        "<font face='Courier' size='8'>theme_&lt;slug&gt;_*</font> values without errors.",
        st_bullet, bulletText="•",
    ))
    story.append(Paragraph(
        "Zip = single root folder; uploads and activates cleanly in Theme Settings.",
        st_bullet, bulletText="•",
    ))
    story.append(Paragraph(
        "Tested on the live site: homepage, a content page, and any optional pages you ship.",
        st_bullet, bulletText="•",
    ))
    story.append(Spacer(1, 10))
    story.append(Paragraph(
        "Good luck, and thanks for building a theme for Codeware!",
        st_signoff,
    ))

    return story


def main():
    out = Path(__file__).resolve().parents[1] / "public" / "docs" / "theme-builder-guide.pdf"

    meta = {
        "title": "Codeware Theme Builder Guide",
        "author": "Codeware",
        "subject": "How to build and install a theme for Codeware",
        "keywords": "codeware, theme, blade, laravel",
        "creator": "scripts/theme_builder_guide.py",
    }

    def canvas_factory(*args, **kwargs):
        c = GuideCanvas(*args, **kwargs)
        c.setAuthor(meta["author"])
        c.setTitle(meta["title"])
        c.setSubject(meta["subject"])
        c.setKeywords(meta["keywords"])
        c.setCreator(meta["creator"])
        return c

    frame = Frame(MARGIN, 45, CONTENT_W, PAGE_H - 45 - 60, id="main")
    doc = BaseDocTemplate(
        str(out),
        pagesize=A4,
        leftMargin=MARGIN,
        rightMargin=MARGIN,
        topMargin=60,
        bottomMargin=45,
        title=meta["title"],
        author=meta["author"],
        subject=meta["subject"],
    )
    doc.addPageTemplates([
        PageTemplate(id="guide", frames=[frame]),
    ])
    doc.build(build_story(), canvasmaker=canvas_factory)
    print(f"Wrote {out} ({out.stat().st_size / 1024:.1f} KiB)")


if __name__ == "__main__":
    main()