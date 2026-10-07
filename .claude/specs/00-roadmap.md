# OgTrips WordPress build — roadmap

Spec-driven, phase by phase. Each phase has a spec (`NN-name.md`) → user approves the spec → build → `wp-tester` + `wp-reviewer` → user checkpoint → git commit/tag `phase-NN` → next phase.

Rules for every phase: read `.claude/CLAUDE.md` and the skills it lists. The WordPress output must look and behave like the approved design in `.claude/design/` (desktop 1440 and mobile 390).

| # | Phase | Main outcome | Status |
|---|---|---|---|
| 01 | Local environment & child theme skeleton | WordPress running locally with GeneratePress + `ogtrips` child theme (design CSS/JS, self-hosted fonts, icon sprite), SCF, Yoast, `ogtrips-core` skeleton, Playwright smoke test | Done |
| 02 | Content model & clean admin | Trips, Tour Guides, Reviews, Moments, Enquiries post types; destinations/trip types; SCF forms with tabs; Homepage + Site Settings screens; Editor-role menu clean-up; comments off | Done |
| 03 | Trip page | `single-ogt_itinerary.php` identical to `itinerary.html`, fully driven by admin data; demo-content seeder | — |
| 04 | Tour Guide page | `single-ogt_guide.php` identical to `guide.html`; restricted block editor + side fields; TOC/progress; Blog uses same layout | — |
| 05 | Homepage | `front-page.php` identical to `index.html`; every section editable (hero places, Why OG, reviews, moments, best sellers, contact) | — |
| 06 | Enquiries | Email (saved + emailed) and WhatsApp (pre-filled wa.me + logged) from contact and trip booking forms | — |
| 07 | Listing & utility pages | wp-designer proposals → client approval → build: all trips, all tour guides, destination filter, blog list, search, 404, generic page | — |
| 08 | SEO & speed | Yoast configuration + schema extensions, breadcrumbs, sitemaps; dequeue unused assets, image sizes/WebP, preload, Lighthouse targets | — |
| 09 | QA & Hostinger launch | Full test pass, accessibility, cross-browser; Hostinger setup (LiteSpeed Cache, SMTP, clean plugins), real content, demo content removed, go-live checklist | — |

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
