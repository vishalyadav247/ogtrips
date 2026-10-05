---
name: wp-coding-standards
description: WordPress PHP/JS/CSS standards for OgTrips — security (escaping, sanitising, nonces, capabilities), enqueueing, performance, i18n, accessibility and SEO basics. Load before writing or reviewing any theme or plugin code.
---

# WordPress coding standards — OgTrips

## Security (non-negotiable)

- **Escape late, on output**: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` (rich text), `wp_kses( $s, $allowed )` for fields allowing `<em>`/`<span class>`. Translations: `esc_html__()`, `esc_html_e()`, `esc_attr__()`.
- **Sanitise early, on input**: `sanitize_text_field()`, `sanitize_email()`, `absint()`, `wp_unslash()` first.
- **Nonces + capability checks** on every form, AJAX and REST write: `wp_nonce_field()` / `check_ajax_referer()` / `current_user_can()`. REST routes need a real `permission_callback` (never `__return_true` for writes).
- No direct SQL; if unavoidable, `$wpdb->prepare()`.
- Never `eval`, `extract()`, unserialize user input, or echo `$_GET`/`$_POST`.
- Every PHP file starts with `defined( 'ABSPATH' ) || exit;` (except templates loaded by WP).
- No secrets/licence keys in code — use `wp-config.php` constants or env.

## Structure & naming

- Prefix everything: functions `ogtrips_`, hooks `ogtrips/…`, CPT/tax `ogt_`, options `ogtrips_`, handles `ogtrips-…`. Text domain `ogtrips` (theme) / `ogtrips-core` (plugin).
- WPCS formatting: tabs, spaces inside parentheses, Yoda conditions, `array()` or short arrays consistently (pick short `[]`).
- Hook everything; no logic at file load. One responsibility per `inc/` file.
- Theme = presentation. Plugin = data (CPTs, tax, fields, CLI, REST). Theme must not fatal if the plugin/SCF is inactive (`function_exists( 'get_field' )` guards in helpers).
- **Free & open-source only**: wordpress.org plugins or our own code. Never add paid/"Pro" plugins, licence keys, or premium-only APIs. Third-party code must be GPL-compatible (Lucide = ISC/MIT, Poppins/DM Sans = OFL — OK).

## Assets

- **Child theme rule**: never edit GeneratePress; override via child templates, hooks and filters only.
- **Exactly one CSS file (child `style.css`) and one JS file (`assets/js/main.js`)** for all custom code. No page builders, no per-template stylesheets, no inline `<style>`/`<script>`.
- Dequeue unused parent/core assets on the front end (GP CSS/JS if unused, block library CSS except on blog posts, emoji, embeds, jQuery when unused).

- `wp_enqueue_style/script` only — no hard-coded `<link>`/`<script>` in templates. Version with `filemtime()`.
- Scripts in footer with `'strategy' => 'defer'`. Pass data via `wp_add_inline_script()` / `wp_localize_script()`.
- Load per-template where possible (itinerary-only JS only on `is_singular('ogt_itinerary')`).
- Self-host fonts (`font-display: swap`, preload the two most-used weights). No third-party CDNs in production.

## Performance

- Hero LCP image: `fetchpriority="high"`, no lazy; everything else `loading="lazy"` + correct `sizes`.
- Register image sizes; never output full-size originals.
- Avoid queries in loops (prime caches: `update_post_thumbnail_cache()`, `_prime_post_caches()`).
- Transients for expensive/remote data (Instagram, review aggregates).
- Targets: LCP < 2.5s, CLS < 0.1, INP < 200ms on mobile.

## Accessibility

- Semantic landmarks (`header`, `nav`, `main`, `footer`), one `h1` per page, logical heading order.
- Buttons are `<button>`, links are `<a>`; no `<button>` nested inside `<a>` (the design's `.fav` heart must sit outside the card link).
- Sliders/accordions: keyboard operable, `aria-expanded`/`aria-controls`, pause auto-advancing hero on hover/focus, respect reduced motion.
- Images: meaningful `alt` from the media library; decorative images `alt=""`.
- Forms: real `<label>`s, error messages tied with `aria-describedby`.

## SEO

- **Yoast SEO (free)** owns titles, meta descriptions, canonical, Open Graph, XML sitemaps, breadcrumbs and the base schema graph. Never output our own `<title>`/meta/OG tags.
- Add trip structured data by **extending Yoast's graph** (`wpseo_schema_graph_pieces` / `wpseo_schema_*` filters): `TouristTrip` + `Offer` on itineraries, `FAQPage` from `faqs`, `Place` for destination guides. No separate JSON-LD blocks.
- Breadcrumbs: `yoast_breadcrumb()` styled with the design's `.crumbs`.
- Register CPTs/taxonomies with clean slugs so Yoast sitemaps include them; exclude `ogt_review`, `ogt_moment`, `ogt_enquiry` (non-public).
- One `h1` per page, descriptive alt text, internal links guide ↔ trips, fast pages (see Performance).
- Clean permalinks per wp-content-model. Breadcrumbs markup on itinerary and guide.
- Remove the design's `noindex` meta — staging is protected by the host/`blog_public` instead.

## JS / CSS

- Keep `main.js` vanilla and defensive (feature-detect elements; no errors on pages missing a component).
- CSS: extend `style.css` using existing tokens; no `!important` except utilities; no inline `style=""` in templates except dynamic background images.
