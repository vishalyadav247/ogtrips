---
name: wp-testing
description: QA for the OgTrips WordPress build — Playwright functional and visual-regression tests against the approved static design, axe accessibility checks, PHP lint, Lighthouse/Core Web Vitals and a manual release checklist. Load before writing tests, running QA, or signing off a template.
---

# Testing OgTrips

Tests live in `wp/tests/e2e/`; config `wp/playwright.config.ts`. Base URL `http://localhost:8888` (wp-env must be running).

## Setup

```bash
cd wp
npm install -D @playwright/test @axe-core/playwright http-server
npx playwright install chromium
```

`package.json` scripts (create during scaffold):

```json
{
  "test:e2e": "playwright test",
  "test:visual": "playwright test --grep @visual",
  "test:a11y": "playwright test --grep @a11y",
  "design:serve": "http-server ../.claude/design -p 5500 -s",
  "lint:php": "wp-env run cli sh -c \"find wp-content/themes/ogtrips wp-content/plugins/ogtrips-core -name '*.php' -print0 | xargs -0 -n1 php -l\""
}
```

## Test layers

1. **Smoke** (`smoke.spec.ts`): every key URL returns 200, has one `h1`, no console errors, no PHP notices in HTML (`/(Warning|Notice|Deprecated):/`).
   URLs: `/`, `/trips/`, one itinerary, `/guide/`, one guide post, `/destinations/bali/`, `/?s=bali`, a 404.
2. **Functional** (`home.spec.ts`, `itinerary.spec.ts`, `guide.spec.ts`):
   - Hero: slides advance; clicking a `.hp` tab activates matching slide and `.place` text.
   - Reviews/destination carousels scroll on arrow click.
   - Itinerary: day accordion toggles + `aria-expanded`; "Expand all"; FAQ; traveller stepper updates `#total` (₹ Indian format); subnav scroll-spy.
   - Guide: TOC links scroll and highlight; progress bar width grows.
   - Mobile (390px): burger opens `.mobile-menu`, links close it; `.mobile-book` visible on itinerary.
   - Enquiry form (own handler in ogtrips-core): required validation; successful submit redirects with `?enquiry=sent` and shows confirmation; a new private `ogt_enquiry` post exists (`wp post list --post_type=ogt_enquiry`); honeypot-filled and nonce-less submissions are rejected; repeated submits are rate-limited.
3. **Visual parity** (`@visual`): compare WP pages against the approved static design served by `npm run design:serve` (`http://localhost:5500/index.html` etc.).
   - Viewports 1440×900 and 390×844, `reducedMotion: 'reduce'`, disable animations, wait for fonts (`document.fonts.ready`), mask dynamic regions (dates, counters, slideshow image).
   - Compare section by section (`locator('#why').screenshot()`), not whole pages — content lengths differ.
   - Store baselines from the **static design**, then assert WP matches with `maxDiffPixelRatio: 0.02`. Any larger diff = bug or approved change (record which).
4. **Accessibility** (`@a11y`): `new AxeBuilder({ page }).withTags(['wcag2a','wcag2aa']).analyze()` on each key URL; zero serious/critical violations. Plus keyboard pass: Tab through nav, hero tabs, accordions, form.
5. **PHP**: `npm run lint:php` must be clean; `debug.log` empty after a full test run.
6. **Performance**: `npx lighthouse http://localhost:8888/ --preset=desktop` and mobile; targets LCP < 2.5s, CLS < 0.1, TBT < 200ms. (Local numbers are indicative; re-check on staging.)

## Reporting format (tester agent)

```
RESULT: PASS | FAIL
Ran: <commands>
Failures:
- <test/page> — expected … got … (screenshot/trace path)
Not tested: <what and why>
```

Never mark PASS if a layer was skipped — list it under "Not tested".

## Release checklist (staging → production)

- [ ] Demo content (`_ogt_demo`) removed; real reviews/prices/contacts in place
- [ ] `noindex` design meta gone; `blog_public` correct per environment
- [ ] Enquiries reach the client's inbox (test real delivery on the host); spam protection on
- [ ] Plugin list contains only free wordpress.org plugins + ogtrips-core
- [ ] SEO plugin configured, sitemap reachable, JSON-LD validates (Rich Results Test)
- [ ] Backups, SSL, caching, security headers on host
- [ ] 404 and search pages styled; favicon & OG image set
- [ ] Cross-browser: Chrome, Safari iOS, Firefox, Edge
