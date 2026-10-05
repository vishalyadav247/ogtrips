# OgTrips — WordPress build

Travel company site for **OgTrips** ("Where to next?"). The static HTML design was **approved by the client**; the job now is converting it into a WordPress site that looks and behaves identically, with all content editable from a clean, simple wp-admin.

## Client/developer requirements (2026-10-05) — these override anything else

1. **Fully SEO- and speed-optimised** site (Core Web Vitals green on mobile).
2. **No page builders** (Elementor, Divi, WPBakery, etc.) — they bloat markup.
3. **Free, official parent theme: GeneratePress** (wordpress.org). **No starter templates, Site Library or demo imports** — we build from scratch.
4. **Never modify the parent theme.** All work goes in the **child theme `ogtrips`** so parent updates are safe.
5. **SEO plugin: Yoast SEO (free).**
6. **Itineraries are fully dynamic** — merchant can add / edit / delete them from wp-admin via simple forms.
7. **Priority = Itinerary pages + Tour Guides**, both fully manageable (add / edit / delete) from wp-admin. **Tour Guide** = its own content type built on the approved `guide.html` design. The **Blog** (core posts, same article layout) stays available for the client to use if they wish — no extra work on it now.
11. **Account icon** in the header stays as a **non-functional placeholder** for now.
12. **Blog/guide comments: off.**
13. **Enquiries go two ways**: email (saved in admin + emailed) **and** WhatsApp directly (pre-filled wa.me message).
14. **No payments** in the site.
15. **Hosting: Hostinger** (LiteSpeed servers).
8. **wp-admin must be neat and clean**; editing must be simple enough for a non-technical merchant.
9. **One CSS file and one JS file** in the child theme hold all custom styles/scripts.
10. **Free & open-source only** — no paid plugins, themes, licences or "Pro" add-ons (GeneratePress Premium included).

## Folder map (paths relative to the project root)

| Path | What it is | Rules |
|---|---|---|
| `.claude/design/` | **Approved design reference**: `index.html`, `itinerary.html`, `guide.html`, `assets/` (css, js, img) | Source of truth for look & behaviour. Don't edit to "fix" WP output — fix the child theme. Design changes need client sign-off. |
| `.claude/design/proposals/` | Mockups for screens with no approved design yet | Created by `wp-designer`; link `../assets/css/style.css`. |
| `wp/` *(planned, not yet created)* | The WordPress project | See layout below. |

Planned `wp/` layout:

```
wp/
  .wp-env.json              local WordPress (Docker): GeneratePress + child theme + plugins
  package.json              @wordpress/env, @playwright/test, @axe-core/playwright
  themes/ogtrips/           CHILD theme of GeneratePress (Template: generatepress)
    style.css               theme header + ALL custom CSS (the one CSS file)
    assets/js/main.js       ALL custom JS (the one JS file)
  plugins/ogtrips-core/     content model, admin clean-up, enquiry handler, WP-CLI seeder
  tests/e2e/                Playwright tests (functional, visual, a11y)
```

GeneratePress itself is installed from wordpress.org by wp-env/WP-CLI — never copied into the repo or edited. Content structures live in the **plugin**, not the theme, so content survives a theme change.

## Environment (this machine)

- Windows 11, Git Bash + PowerShell. **No local PHP/Composer** — use wp-env (Docker) for WordPress, WP-CLI and PHP.
- Available: Node 24, npm 11, Docker 29, git.
- Local site (once started): http://localhost:8888 · admin: `admin` / `password` · test site on :8889.
- Git Bash + Docker volume paths: prefix with `MSYS_NO_PATHCONV=1` when passing container paths.

## Architecture decisions

1. **Child theme on GeneratePress (free)**. Our design replaces GP's layout: child templates (`front-page.php`, `single-ogt_itinerary.php`, …) output the design markup; GP hooks/filters used where useful. Parent CSS/JS that our design doesn't need is dequeued so the front end loads only our one CSS + one JS (+ block CSS on article pages only).
2. **Editors**: Itineraries, Reviews, Moments, Homepage/Site settings use **simple forms (Secure Custom Fields)** — block editor disabled for them. **Tour Guides and Blog posts use the core block editor** for the article body, restricted to basic blocks (paragraph, heading, image, list, quote, table, separator), plus side fields for the structured parts of the design (at-a-glance box, related trip, destination).
3. **Custom fields: Secure Custom Fields (SCF)** — free, wordpress.org, maintained by WordPress.org; repeater, flexible content, gallery, options pages, local JSON; ACF-compatible API. Field groups saved as JSON in `plugins/ogtrips-core/acf-json/`.
4. **"Reserve" = enquiry only, two channels**: our own handler in `ogtrips-core` (nonce + honeypot + rate limit) stores each lead as private CPT `ogt_enquiry` and emails the team via `wp_mail()`; a **"Send on WhatsApp"** option opens `https://wa.me/<number>?text=<pre-filled trip, date, travellers, name>` (number from Site Settings) and logs the lead too. No payments.
4b. **Hostinger stack**: free **LiteSpeed Cache** plugin for page cache (CSS/JS combine/minify **off** — we already ship one file each; image WebP optional). Enquiry email sent through the Hostinger mailbox via SMTP (free SMTP plugin or LSCache-independent snippet with credentials in `wp-config.php`). Remove any Hostinger auto-installed helper/AI plugins to keep admin clean.
4c. **Login**: standard WordPress login (`/wp-admin`, `/admin` redirects there). Client gets their own account with the **Editor** role (sees only content menus: Trips, Tour Guides, Blog, Reviews, Moments, Enquiries, Homepage, Site Settings, Media); developer keeps the Administrator account.
5. **Instagram "tagged" section**: curated `ogt_moment` posts added by the team.
6. **SEO: Yoast SEO (free)** owns titles, meta, sitemaps, breadcrumbs, base schema. We extend Yoast's schema graph (filters) with `TouristTrip`/`Offer`/`FAQPage` — no duplicate JSON-LD.
7. **Speed**: no build step; one CSS + one JS (deferred), self-hosted fonts (Poppins + DM Sans, preload 2 weights), local SVG icon sprite (Lucide, MIT) instead of CDN, responsive images with WebP, lazy-load everything except the LCP image, no jQuery on the front end, remove emoji/embeds/unused block CSS. Page caching decided with hosting.
8. **Clean admin** (in `ogtrips-core`): custom menu order and labels (Trips, Tour Guides, Blog, Reviews, Moments, Enquiries, Homepage, Site Settings), comments disabled site-wide, hide unused menus/widgets for the merchant role, no nag notices, simple dashboard with quick-add buttons.
9. Prefixes: PHP functions `ogtrips_`, post types/taxonomies `ogt_`, text domains `ogtrips` (child theme) / `ogtrips-core`.

## Open decisions (ask the user before assuming)

- What the account icon should eventually do (placeholder for now).
- Whether a per-destination landing page is wanted later (currently: tour guides + trips filtered by destination).

## Screens with no approved design yet

Trips archive (`/trips/`), Tour Guide listing (`/travel-guide/`), destination filter page, blog listing, search results, 404, generic page. Use the **wp-designer** agent to mock them in `.claude/design/proposals/` for client approval before building. The approved `guide.html` is the **Tour Guide** (and blog article) design; `itinerary.html` is the trip page design.

## Project skills & agents

Skills (`.claude/skills/`) — load the matching one before working:
- `ogtrips-design-system` — brand tokens, components, fidelity rules
- `wp-theme-conversion` — HTML section → child-theme template mapping and conventions
- `wp-content-model` — CPTs, taxonomies, SCF fields (the data contract)
- `wp-coding-standards` — security, escaping, enqueueing, performance, i18n
- `wp-local-env` — wp-env + WP-CLI commands
- `wp-testing` — Playwright functional/visual/a11y tests, PHP lint, QA checklist

Agents (`.claude/agents/`): `wp-designer`, `wp-developer`, `wp-tester`, `wp-reviewer`.

## Working rules

- Match the approved design pixel-for-pixel at 1440px and 390px before calling a template done.
- Every user-visible string translatable; every output escaped; every input sanitised.
- Never commit secrets; never deploy `.claude/`.
- The logo SVG is a hand-redrawn approximation — swap in the official vector when the client supplies it.
- Placeholder content in the design (reviews, handles, prices, phone, address) is **fake** — import it only as clearly-marked demo content.
