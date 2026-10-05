---
name: ogtrips-design-system
description: OgTrips brand tokens, typography, components and design-fidelity rules. Load before any styling, theme.json, CSS/JS, new screen design, or visual QA on the OgTrips WordPress theme.
---

# OgTrips design system

Source of truth: the approved static design in `.claude/design/` — `index.html`, `itinerary.html`, `guide.html`, `assets/css/style.css`, `assets/js/main.js`. Brand colours/logo were taken from the client's brand PDF; the design now carries all of it. If this file and the CSS disagree, **the CSS wins**; update this file.

## Brand

- Name: **OgTrips** (logo wordmark "Trips" with a coral map-pin as the dot of the i; mark = coral O ring + teal G ring + sun overlap + paper plane). Mark file: `.claude/design/assets/img/ogtrips-mark.svg` (hand-redrawn; replace with official vector when the client supplies it).
- Tagline: **"Where to next?"** — used in hero, contact heading and footer wordmark.
- Voice: warm, playful, confident, honest. Short sentences. Indian English, prices in ₹ with Indian grouping (₹1,24,000).

## Tokens (`:root` in style.css → mirror into theme.json)

| Token | Value | Use |
|---|---|---|
| `--coral` / `--coral-2` | #ff5a4f / #e8443a | Primary CTA, emphasis words (`h* em`), pins |
| `--teal` / `--teal-2` | #00b3bf / #0094a0 | Secondary accent, labels, focus rings |
| `--sun` | #ffc72c | Highlights, hero place name, badges |
| `--navy` / `--navy-2` | #0a2540 / #12355c | Text (ink), dark sections, primary button |
| `--cream` / `--cream-2` | #fff8ee / #fbeedb | Page background |
| `--sky` | #e3f6f8 | Reviews section background |
| `--ink-2` / `--muted` | #3b4d63 / #6b7b8f | Body / secondary text |
| Radii | 14 / 24 / 36px | `--r-sm` / `--r` / `--r-lg` (frames, dark panels) |
| Easing | `--ease` cubic-bezier(.22,1,.36,1), `--spring` (.34,1.56,.64,1) | All motion |
| Layout | container 1320px, gutter clamp(16px,4vw,40px) | |

Fonts: **Poppins** 500/600/700 (headings, buttons, labels; letter-spacing −.03em) and **DM Sans** 400/500/700 (body). Self-host in WP.

theme.json mapping: palette slugs `coral, coral-2, teal, teal-2, sun, navy, navy-2, cream, cream-2, sky, paper, ink-2, muted`; fontFamilies `display` (Poppins) and `body` (DM Sans); `layout.contentSize` 720px, `wideSize` 1320px.

## Components (class → where)

- **Nav**: `.nav-wrap > .nav` floating glass pill — logo · `.menu` · `.nav-search` · `.nav-icon.account` · `.btn--coral` Reserve · `.burger`; `.mobile-menu` full-screen circle reveal. Hides on scroll down.
- **Buttons**: `.btn` (+ `.arrow` circle that rotates −45° on hover), variants `--coral --teal --sun --light --plain --ghost --ghost-light --block --callout` (pulse ring).
- **Label**: `.label` uppercase eyebrow with tri-colour bar (coral/sun/teal).
- **Hero**: `.hero > .hero-frame` inset rounded frame; `.slides > .slide[data-place][data-country]` crossfade + Ken Burns; `.flight` dotted path + plane; `.place` swap animation; `.hero-places > .hp` progress tabs.
- **Why OG**: `.og-quote` (big coral “ mark, `.hl` sun underline) + `.pillars > .pillar` (3, icon tile, `data-count` counter).
- **Reviews**: `.reviews-sec` sky panel, `.track` scroll-snap slider with `[data-carousel]` arrows, `.review` cards.
- **Social / UGC**: `.social-sec` navy panel, `.ugc` 6-col grid, `.ugc-item` modifiers `big tall wide is-video`, `.tag-cta`.
- **Best sellers**: `.best` 2×2 of `.best-card` (media | body, rank chip, `.fav` heart, `.incl-mini` icons, price + Reserve).
- **Contact**: `.contact` sky gradient + `.sunball` + `.cloud` + `.waves` SVG; `.contact-list`; `.form-card` with floating-label `.input` and `.trip-types` pills.
- **Footer**: link columns + giant `.wordmark` "Where to next?".
- **Itinerary**: `.page-hero`, `.facts-bar`, sticky `.subnav` (scroll-spy), `.route`, `.highlight-grid`, `.days > .day` accordion, `.stays`, `.incl`, `.gallery` bento, `.faq-item`, sticky `.aside > .book` (stepper + live total), `.expert`, `.mobile-book` bar.
- **Guide**: `.progress` bar, `.article-head`, `.cover`, `.read-layout` (sticky `.toc` scroll-spy), `.prose` (drop cap), `.glance`, `.tip`, `.pullquote`, `.area-cards`, `.table`, `.trip-cta`, `.author-card`, `.posts`.
- **Motion utilities**: `.reveal` (+ `-d1..-d3`) via IntersectionObserver; all motion disabled under `prefers-reduced-motion`.

## Fidelity rules

1. Never introduce new colours, fonts, radii or shadows — extend tokens only with user approval.
2. Breakpoints in use: 1180, 860, 560px. Check 1440, 1024, 768, 390.
3. Headline emphasis is `<em>` (rendered coral, not italic). In WP, let editors produce it via a highlighted-text format or an SCF text field that allows `<em>`.
4. Icons are Lucide line icons (stroke 1.9). In WP use a local sprite with the same names.
5. New screens (archive, 404, search, listing) must reuse existing components; propose layout before building.
6. Accessibility floor: visible focus (teal ring), text contrast ≥ 4.5:1 (coral on white is only for large text/buttons — check), keyboard-operable sliders/accordions, `aria-expanded` kept in sync.
