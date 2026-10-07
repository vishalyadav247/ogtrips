# OgTrips WordPress build — roadmap

Spec-driven, phase by phase. Each phase has a spec (`NN-name.md`) → user approves the spec → build → `wp-tester` + `wp-reviewer` → user checkpoint → git commit/tag `phase-NN` → next phase.

Rules for every phase: read `.claude/CLAUDE.md` and the skills it lists. The WordPress output must look and behave like the approved design in `.claude/design/` (desktop 1440 and mobile 390).

| # | Phase | Main outcome | Status |
|---|---|---|---|
| 01 | Local environment & child theme skeleton | WordPress running locally with GeneratePress + `ogtrips` child theme (design CSS/JS, self-hosted fonts, icon sprite), SCF, Yoast, `ogtrips-core` skeleton, Playwright smoke test | Done |
| 02 | Content model & clean admin | Trips, Tour Guides, Reviews, Moments, Enquiries post types; destinations/trip types; SCF forms with tabs; Homepage + Site Settings screens; Editor-role menu clean-up; comments off | Done |
| 03 | Trip page | `single-ogt_itinerary.php` identical to `itinerary.html`, fully driven by admin data; demo-content seeder | Built 2026-10-07 (fast-track) |
| 04 | Tour Guide page | `single-ogt_guide.php` identical to `guide.html`; restricted block editor + side fields; TOC/progress; Blog uses same layout | Built 2026-10-07 (fast-track) |
| 05 | Homepage | `front-page.php` identical to `index.html`; every section editable (hero places, Why OG, reviews, moments, best sellers, contact) | Built 2026-10-07 (fast-track) |
| 06 | Enquiries | Email (saved + emailed) and WhatsApp (pre-filled wa.me + logged) from contact and trip booking forms | Built 2026-10-07 — email delivery untested (no mail locally; needs SMTP on Hostinger) |
| 07 | Listing & utility pages | wp-designer proposals → client approval → build: all trips, all tour guides, destination filter, blog list, search, 404, generic page | Built 2026-10-07 with a simple layout from design components — no wp-designer proposals, client approval pending |
| 08 | SEO & speed | Yoast configuration + schema extensions, breadcrumbs, sitemaps; dequeue unused assets, image sizes/WebP, preload, Lighthouse targets | Partly: GP/emoji/block CSS dequeued, TouristTrip + FAQPage via Yoast graph, preloads done. Pending: Yoast set-up, Lighthouse run, WebP, breadcrumbs |
| 09 | QA & Hostinger launch | Full test pass, accessibility, cross-browser; Hostinger setup (LiteSpeed Cache, SMTP, clean plugins), real content, demo content removed, go-live checklist | Pending — release zips + `HANDOVER.md` ready; code review 2026-10-08; tests to be run by the user |

## Spec template

```
# NN — Title
Status: Draft | Approved | Built | Done
## Goal
## In scope / Out of scope
## Deliverables (files)
## Steps
## Acceptance criteria   (each one testable — wp-tester checks these)
## Test plan
## Checkpoint for the user (what to look at / approve)
```

## Fast-track build (2026-10-07)

The developer had to hand over the laptop on 2026-10-08, so phases 03–08 were built in one day **without** separate specs, approval rounds or wp-tester runs (each page type was checked for PHP errors and compared with the design in screenshots at 1440px and 390px). Commits on `main` after tag `phase-02`; no `phase-03`+ tags.

What changed versus the plan:

- **Destinations:** the client sells only **Ladakh, Kashmir, Manali, Shimla, Spiti**. The demo importer (`ogtrips-core/demo/`, Settings → OgTrips demo, or `wp ogtrips demo import|remove`) builds the client's real itineraries (Soul of Ladakh, Shimla & Manali, Winter Kashmir, Spiti Winter Expedition) plus a Ladakh tour guide. Reviews, handles, phone, ratings and dates are fake placeholders.
- **Prices:** new Site Settings → Booking → `show_prices` switch, **off by default** (client does not want prices shown). Off = no price, saving, total or Offer schema anywhere; trips show "Price on request".
- **Client source material** lives in `resources/` (git-ignored — contains private pricing; purged from history).
- **Launch:** Hostinger steps, placeholder list and follow-ups are in `/HANDOVER.md`; upload files in `/release/`.

Still open: user's own test pass; Hostinger launch (phase 09); client approval of the listing-page layout; official logo; real photos and reviews; account icon and newsletter decisions.
