# 01 — Local environment & child theme skeleton

Status: **Draft — awaiting user approval**

## Goal

A reproducible local WordPress site on this PC (Docker via wp-env) running **GeneratePress (parent, untouched)** + **`ogtrips` child theme** + **Secure Custom Fields** + **Yoast SEO** + an empty **`ogtrips-core`** plugin, with the approved design's CSS, JS, fonts, logo and icons already inside the child theme — the foundation every later phase builds on.

## In scope

- `wp/` project folder: wp-env config, npm scripts, Playwright setup.
- Child theme skeleton with the one-CSS/one-JS structure, self-hosted fonts and a local icon sprite.
- `ogtrips-core` plugin skeleton (main file + folder structure, no content types yet).
- Clean WordPress baseline (settings, default content/plugins removed).
- One smoke test.

## Out of scope

Content types, fields, admin clean-up (phase 02); any page templates matching the design (phases 03–05); dequeuing GeneratePress assets (phase 08 — until our templates replace GP's, GP still styles unbuilt pages); Hostinger (phase 09).

## Deliverables

```
wp/
  .wp-env.json                 GeneratePress (wordpress.org zip) + ./themes/ogtrips;
                               plugins: ./plugins/ogtrips-core, secure-custom-fields, wordpress-seo;
                               config: WP_DEBUG, WP_DEBUG_LOG, WP_DEBUG_DISPLAY=false, SCRIPT_DEBUG, WP_ENVIRONMENT_TYPE=local
  package.json                 devDeps: @wordpress/env, @playwright/test, @axe-core/playwright, http-server,
                               lucide-static, @fontsource/poppins, @fontsource/dm-sans
                               scripts: env:start, env:stop, setup, build:icons, build:fonts, test:e2e,
                               design:serve, lint:php
  scripts/setup.sh             idempotent WP-CLI baseline (see Steps 4)
  scripts/build-icons.mjs      builds assets/icons/sprite.svg from the Lucide icon names used in .claude/design/*.html
  scripts/build-fonts.mjs      copies Poppins 500/600/700 + DM Sans 400/500/700 latin woff2 into assets/fonts/
  playwright.config.ts         baseURL http://localhost:8888, chromium, 1440 + 390 projects
  tests/e2e/smoke.spec.ts
  themes/ogtrips/
    style.css                  header (Theme Name: OgTrips, Template: generatepress, Text Domain: ogtrips,
                               Version 0.1.0, License GPL-2.0-or-later) + @font-face rules +
                               full contents of .claude/design/assets/css/style.css
    functions.php              require_once inc/*.php
    inc/setup.php              theme supports (title-tag, post-thumbnails, html5, responsive-embeds),
                               menus (primary, footer-explore, footer-support), image sizes
                               (ogt-hero 2200, ogt-card 900, ogt-thumb 500, ogt-avatar 120x120 crop),
                               load_child_theme_textdomain
    inc/enqueue.php            enqueue ogtrips-style (style.css) and ogtrips-main (assets/js/main.js,
                               footer, defer) with filemtime() versions; preload the 2 main font files
    inc/template-tags.php      ogtrips_icon( $name, $class = '' ) → <svg class="lucide …"><use href="…sprite.svg#name"/></svg>
    inc/helpers.php            ogtrips_field( $name, $post_id = null, $default = '' ) wrapper (safe without SCF)
    assets/js/main.js          .claude/design/assets/js/main.js with the window.lucide call removed
    assets/img/ogtrips-mark.svg, assets/icons/sprite.svg, assets/fonts/*.woff2
    screenshot.png             1200×900 crop of the approved homepage hero
  plugins/ogtrips-core/
    ogtrips-core.php           plugin header (GPL-2.0-or-later), constants, ABSPATH guard, autoload of includes/
    includes/                  (empty folders: post-types/, fields/, admin/, enquiry/, cli/) + index.php guards
    acf-json/                  (empty, for SCF local JSON in phase 02)
.gitignore                     node_modules, test-results, playwright-report, wp/.wp-env.override.json, *.zip, *.log
```

## Steps

1. Scaffold `wp/` files above; `npm install`.
2. `npm run build:fonts` and `npm run build:icons`; replace the Google Fonts `<link>` dependency with `@font-face` in `style.css` (`font-display: swap`).
3. `npx wp-env start`.
4. `npm run setup` (WP-CLI, safe to re-run):
   - activate theme `ogtrips`; activate SCF, Yoast, ogtrips-core
   - delete Hello Dolly + Akismet; delete "Hello world!" post, "Sample Page", "Privacy Policy" draft
   - blogname "OgTrips", blogdescription "Where to next?", timezone Asia/Kolkata, date format `j M Y`
   - permalinks `/%postname%/`; comments: default_comment_status/ping_status closed
   - discourage search engines ON locally (`blog_public 0`)
5. Lint all PHP via wp-env; check `debug.log` is empty.
6. Smoke test; commit and tag `phase-01`.

## Acceptance criteria

1. `cd wp && npm install && npx wp-env start && npm run setup` on a clean machine gives a working site at http://localhost:8888 (admin/password) with no errors.
2. Active theme is **OgTrips** with parent **GeneratePress**; GeneratePress files are not in the repo and not modified.
3. Active plugins = exactly: Secure Custom Fields, Yoast SEO, OgTrips Core.
4. Front-end page source includes `themes/ogtrips/style.css?ver=…` and `themes/ogtrips/assets/js/main.js?ver=…` (deferred), and **no** requests to fonts.googleapis.com, fonts.gstatic.com or unpkg.com.
5. Fonts load from the theme (`assets/fonts/*.woff2`); `document.fonts.check('700 16px Poppins')` and `('400 16px "DM Sans"')` are true.
6. `ogtrips_icon('map-pin')` renders a visible SVG; the sprite contains every `data-lucide` name used in the three design HTML files.
7. Only one custom CSS file and one custom JS file exist in the child theme.
8. All PHP files pass `php -l`; `wp-content/debug.log` is empty after loading home, wp-admin and a 404.
9. Settings from Step 4 verified via WP-CLI; comments closed by default; default content/plugins removed.
10. `npm run test:e2e` (smoke) passes: home + /wp-admin/ login page return 200, no console errors from our JS, no "Warning/Notice/Deprecated" in HTML.

## Test plan

`wp-tester`: run acceptance 1–10, report in the wp-testing format. `wp-reviewer`: review child theme + plugin skeleton (headers, guards, enqueue, escaping in `ogtrips_icon`).

## Checkpoint for the user

- Open http://localhost:8888/wp-admin, log in (admin / password): theme = OgTrips (child of GeneratePress), 3 plugins, no clutter from default content.
- The front end still shows GeneratePress's default layout — expected; designed templates arrive in phases 03–05.
- Approve → phase 02 spec.
