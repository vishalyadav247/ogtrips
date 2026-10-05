---
name: wp-theme-conversion
description: How to convert the approved OgTrips static HTML into the `ogtrips` child theme of GeneratePress — child-theme rules, file layout, section-to-template map, single CSS/JS enqueueing, dequeuing parent assets and data wiring. Load before creating or editing any child-theme template, template part or functions.php.
---

# Converting the static design into the `ogtrips` child theme

Parent: **GeneratePress (free, wordpress.org)** — installed, never edited, never copied into the repo, no Site Library/starter templates. Child: `wp/themes/ogtrips/`.

## Child theme layout

```
style.css              Theme header (Template: generatepress) + ALL custom CSS — the ONE stylesheet
functions.php          require_once inc/* only — no logic here
inc/
  setup.php            theme supports, menus (primary, footer-explore, footer-support), image sizes, block-editor restrictions
  enqueue.php          enqueue style.css + assets/js/main.js; dequeue unneeded GP + core assets
  generatepress.php    GP filters/hooks: disable GP header/footer/sidebars/page-title where our design takes over
  template-tags.php    ogtrips_price(), ogtrips_icon(), ogtrips_stars(), ogtrips_reading_time()…
  helpers.php          field getters with fallbacks (wrap get_field so the theme never fatals without SCF)
  seo.php              Yoast schema-graph pieces (TouristTrip, Offer, FAQPage), breadcrumbs output
header.php  footer.php                 override GP's — our nav/footer markup
front-page.php  single-ogt_itinerary.php  archive-ogt_itinerary.php
single-ogt_guide.php (Tour Guide = approved guide.html)  archive-ogt_guide.php
single.php (optional Blog, same article parts)  home.php (blog list)  search.php  404.php  page.php
template-parts/
  global/   nav.php  mobile-menu.php  logo.php
  home/     hero.php  why.php  reviews.php  social.php  bestsellers.php  contact.php
  itinerary/ hero.php facts.php subnav.php overview.php days.php stays.php included.php gallery.php faq.php booking.php related.php mobile-book.php
  article/  head.php glance.php toc.php trip-cta.php author.php related.php
  cards/    best-card.php  tour-card.php  review-card.php  post-card.php  ugc-item.php
assets/
  js/main.js           ALL custom JS — the ONE script
  img/  icons/sprite.svg  fonts/  (static files only, no CSS/JS elsewhere)
```

Only **one CSS file (`style.css`)** and **one JS file (`assets/js/main.js`)** may hold custom code. No inline `<style>`/`<script>` blocks, no extra stylesheets per template. (Exception: dynamic background-image `style=""` attributes and `wp_add_inline_script` data objects.)

## Starting point

1. Child `style.css` = theme header + contents of `.claude/design/assets/css/style.css` (fonts switched to self-hosted `@font-face`).
2. `assets/js/main.js` = `.claude/design/assets/js/main.js` with the Lucide call removed (icons come from the sprite).
3. Logo/icons/images from `.claude/design/assets/img/`.

## GeneratePress integration

- Our `header.php`/`footer.php` replace GP's so markup matches the design exactly; keep `wp_head()`, `wp_body_open()`, `wp_footer()`.
- Templates output `<main id="main">` with our sections directly — no GP containers/sidebars on designed pages. Use `generate_sidebar_layout` → `no-sidebar`, and GP filters to remove page titles/featured images where the design has its own.
- Dequeue on the front end anything the design doesn't use: GP's main CSS/JS (keep only if a template relies on it), `wp-block-library` + `global-styles` + `classic-theme-styles` except on blog articles, emoji scripts, oEmbed, jQuery (if no plugin needs it). Check with Query Monitor that only our CSS/JS (+ Yoast's nothing on front) load.
- Never `@import` the parent stylesheet.

## Section → template map

Design files: `.claude/design/index.html`, `itinerary.html`, `guide.html`.

| Design (file · section) | Template part | Data source |
|---|---|---|
| all · `.nav-wrap`, `.mobile-menu` | `global/nav.php`, `global/mobile-menu.php` | `wp_nav_menu('primary')`, `get_search_form()`, options (phone) |
| index · `#home` hero | `home/hero.php` | Homepage options `hero_places` |
| index · `#why` | `home/why.php` | Homepage options `why_og` |
| index · `#reviews` | `home/reviews.php` + `cards/review-card.php` | `ogt_review` query (featured) |
| index · `#social` | `home/social.php` + `cards/ugc-item.php` | `ogt_moment` query |
| index · `#trips` | `home/bestsellers.php` + `cards/best-card.php` | `ogt_itinerary` where `is_bestseller`, order `bestseller_rank`, 4 |
| index · `#contact` | `home/contact.php` | Site Settings `contact` + `ogtrips_enquiry_form()` (ogtrips-core) |
| all · footer | `footer.php` | footer menus + Site Settings |
| itinerary.html | `single-ogt_itinerary.php` + `itinerary/*` | itinerary fields |
| guide.html | `single-ogt_guide.php` (Tour Guide) + `article/*` | guide body + side fields |
| guide.html | `single.php` (optional Blog) — same `article/*` parts | post + fields |
| all · header `.nav-icon.account` | `global/nav.php` | non-functional placeholder for now (`href="#"`, keep aria-label) |
| all · WhatsApp links / "Send on WhatsApp" | `home/contact.php`, `itinerary/booking.php` | Site Settings `contact.whatsapp` → wa.me pre-filled message |

## Conventions

- Copy markup verbatim from the design, then replace content with escaped WP data. Keep every class name so `style.css`/`main.js` work unchanged.
- Pass data with `get_template_part( 'template-parts/cards/best-card', null, [ 'post_id' => $id ] )`.
- Each part hides itself when it has no data (no empty sections, no notices).
- Images via `wp_get_attachment_image()` with registered sizes (`ogt-hero` 2200w, `ogt-card` 900w, `ogt-thumb` 500w, `ogt-avatar` 120² crop). First hero slide `loading="eager" fetchpriority="high"`.
- Icons: `ogtrips_icon( 'map-pin' )` → `<svg class="lucide"><use href="…/sprite.svg#map-pin"/></svg>` (same names as `data-lucide` in the design).
- Front-page anchors stay (`#trips`, `#why`, `#reviews`, `#contact`); menus elsewhere link to `home_url( '/#trips' )`.

## Definition of done for a template

1. Markup/classes match the design; visual diff passes at 1440 and 390.
2. All content from WP (only translatable UI labels hard-coded).
3. Works with empty data.
4. Escaped, translatable, no PHP notices with `WP_DEBUG`.
5. Only `style.css` + `main.js` (+ block CSS on articles) load on the page.
6. Reviewed by `wp-reviewer`, tested by `wp-tester`.
