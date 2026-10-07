# 02 — Content model & clean admin

Status: Done (2026-10-07)

## Goal

Every piece of content the approved designs show can be added, edited and deleted from a clean, simple wp-admin by a non-technical merchant (Editor role) — with no front-end templates yet. After this phase the data contract is final enough that phases 03–05 only read fields.

## In scope

- `ogtrips-core`: post types, taxonomies, SCF field groups (local JSON), two SCF options pages, one SCF block (Trip CTA), user-profile fields, enquiry storage (post type + list screen only — the form handler is phase 06).
- Clean admin for the merchant: Editor-role account, menu order/labels, comments off site-wide, dashboard widget, hidden clutter, Yoast box placement.
- Permalinks for all content (`/trips/…`, `/travel-guide/…`, `/blog/…`, `/destinations/…`).
- Updating the `wp-content-model` skill to match this spec (it is the data contract).

## Out of scope

Front-end templates (03–05: until then new content renders in GeneratePress's default layout); enquiry form handler, email and WhatsApp (06); demo-content seeder (03); Yoast schema extensions (08); production users/passwords (09).

## Decisions taken in this spec (approve or change)

The design check against `index.html`, `itinerary.html` and `guide.html` found content the old contract didn't cover. Recommended resolutions:

| # | Gap in the design | Decision |
|---|---|---|
| D1 | Trip "Talk to Neha" expert card (photo, specialty, reply time, WhatsApp) and guide byline/author card | Experts **are WordPress users** (they're also the guide authors — better for SEO/E-E-A-T via Yoast's author schema). Extra **profile fields**: `ogt_photo`, `byline_role` ("OgTrips trip captain"), `specialty` ("Bali specialist"), `reply_time` ("replies in ~5 min"), `whatsapp` (optional; else site number). `ogt_photo` replaces Gravatar site-wide (no third-party avatar requests). The administrator creates these users (role Author); the merchant picks one on each trip. |
| D2 | Guide's mid-article "Bali Bliss, fully planned" trip box (sits between sections, own heading/text) | One custom block **"Trip CTA"** (SCF block, `ogtrips/trip-cta`) the merchant inserts anywhere in the article: `itinerary`, `heading`, `text`. Replaces the side field `related_itinerary`. |
| D3 | Guide categories "Destination guide / Travel tips / Seasonal" (eyebrow + related cards) | New flat taxonomy **`ogt_guide_topic`** ("Guide topics") on Tour Guides. |
| D4 | Emphasised words in headings (`Most-loved <em>trips</em>`, trip title `Bali Bliss: temples <em>&amp;</em> rice terraces`) | Merchant wraps words in `*stars*` → rendered as `<em>` (no HTML in admin). Trip field `title_display` (optional; falls back to the title). |
| D5 | Trip location line "Bali, Indonesia" / "Munnar · Alleppey" and short card names "Bali Bliss" | Trip fields `location_label` and `short_title` (optional; fall back to destination term / title). |
| D6 | Badge chips with colour + icon ("#1 Bestseller" sun, "Selling fast" coral, "Honeymoon pick" ♥) and "Early-bird · save ₹7,001" | Trip fields `badge` (text) + `badge_style` (Yellow / Coral / Plain) + `badge_icon` (none / award / heart / baby / flame / sparkles); `offer_label` ("Early-bird"; save amount computed). |
| D7 | Homepage marketing copy (hero heading/sub/CTAs, every section's label + heading, intros, form title) | **Editable** in Homepage settings. Fixed UI words on trip/guide pages ("Day by day", "Expand all", facts labels…) stay translatable text in the theme. |
| D8 | Newsletter box in every footer (no provider decided) | Site Settings toggle `newsletter_enabled` (default **off** → box hidden). Where sign-ups go is decided in phase 06. |
| D9 | Pace shown as a range ("Easy – moderate"); route days as ranges ("Day 1–3") | `pace` select: Easy · Easy–moderate · Moderate · Moderate–challenging · Challenging. `route_stops.days` is text. |
| D10 | Moments counters "12.4k" / "98k", "Reel · 0:42" | `likes`, `views`, `duration` are short text; one `link` (Instagram post) + optional `video_file` (self-hosted mp4) instead of overlapping `video_url`/`permalink`. |
| D11 | Unused in the designs | Dropped for now: destination term fields `cover_image`/`country_label` (come back with the destination page in phase 07), custom role `trip_captain` (D1). Trip excerpt kept only as Yoast's meta-description fallback. |
| D12 | Unused default themes (15 × Twenty*) clutter Appearance | `setup.sh` deletes all inactive themes except GeneratePress. |
| D13 | Blog URL `/blog/{slug}/` | Permalink structure `/blog/%postname%/` for posts; all custom types/taxonomies use `with_front => false` so they stay at `/trips/…` etc. Pages stay at `/{slug}/`. |

## Deliverables

```
wp/plugins/ogtrips-core/
  ogtrips-core.php                  + Requires Plugins: secure-custom-fields; activation/deactivation flush rewrites
  includes/post-types/
    itinerary.php                   ogt_itinerary  "Trips"        /trips/{slug}/, archive /trips/
    guide.php                       ogt_guide      "Tour Guides"  /travel-guide/{slug}/, archive /travel-guide/
    review.php                      ogt_review     "Reviews"      not public
    moment.php                      ogt_moment     "Moments"      not public
    enquiry.php                     ogt_enquiry    "Enquiries"    private; list + view only (no "Add new"); status column/filter
    taxonomies.php                  ogt_destination (hierarchical) · ogt_trip_type (flat list, term field `icon`) · ogt_guide_topic (flat list)
                                      — the two flat lists are registered hierarchical only to get tick-boxes; never nested (parent picker hidden)
    blog.php                        core "Posts" relabelled "Blog"
  includes/fields/
    scf.php                         local JSON load/save → acf-json/; hide SCF admin UI unless administrator + local env
    options-pages.php               "Homepage", "Site Settings" (capability edit_pages → Editors can edit)
    blocks.php                      ogtrips/trip-cta SCF block (render template stub; styled in phase 04)
    user-profile.php                ogt_photo/byline_role/specialty/reply_time/whatsapp; get_avatar uses `ogt_photo`
    format.php                      ogtrips_core_emphasis( $text ) — *word* → <em>word</em>, escaped
  includes/admin/
    menu.php                        order + labels + icons; Enquiries "new" count bubble
    comments.php                    comments off everywhere (supports, menus, admin bar, widgets, front end, feeds)
    merchant.php                    Editor-role clean-up: hide Tools, Yoast menu/notices, update nags, Howdy clutter, unused dashboard widgets
    dashboard.php                   "OgTrips" widget: quick-add Trip / Tour Guide / Blog post / Review / Moment + 5 latest enquiries
    editor.php                      block editor only for Tour Guides + Blog; allowed blocks: paragraph, heading (H2/H3), image, list, quote, table, separator, ogtrips/trip-cta; no custom colours/fonts/sizes
    yoast.php                       Yoast metabox below our fields (low priority)
  acf-json/group_*.json             one file per field group (below)
wp/scripts/setup.sh                 + Editor user `merchant` (local only), permalink /blog/%postname%/, delete inactive default themes,
                                      seed trip types (5) + guide topics (3) — terms only, idempotent
wp/tests/e2e/admin.spec.ts          admin tests (see Acceptance)
.claude/skills/wp-content-model/SKILL.md   rewritten to match this spec exactly
```

## Field groups (SCF, tabs in this order; every field has a one-line instruction)

**Trip (`ogt_itinerary`)** — title, editor (overview "The trip"), featured image (hero), excerpt, destination + trip type boxes
- *Overview*: `title_display`, `short_title`, `location_label`, `badge`, `badge_style`, `badge_icon`, `is_bestseller`, `bestseller_rank`, `route_stops` (repeater: `label`, `days` text), `highlights` (repeater: `icon` select*, `text`)
- *Facts*: `duration_days`, `duration_nights`, `group_min`, `group_max`, `best_time`, `pace`, `stays_label`, `rating` (0–5, step 0.1), `review_count`
- *Price & dates*: `price_from`, `price_original`, `offer_label`, `departures` (repeater: `date` date picker, `seats_left`), `pdf_itinerary` (file, PDF)
- *Day by day*: `days` (repeater: `title`, `location`, `description` basic editor, `image`, `tags` repeater `icon` select*, `text`)
- *Stays*: `stays` (repeater: `name`, `nights` number, `room`, `image`)
- *Inclusions*: `incl_icons` (checkboxes: Stay, Meals, Transfers, Activities, Camping, Flights, Water sports, Cruise → bed-double, utensils, car, ticket, tent, plane, waves, sailboat), `included` (repeater `text`), `excluded` (repeater `text`)
- *Gallery*: `gallery` (gallery field, 5 images recommended)
- *FAQ*: `faqs` (repeater: `question`, `answer` basic editor — links allowed)
- *Expert*: `expert` (user)

\* Icon selects offer a curated list with human labels (from the design: sunrise, sprout, droplets, waves, flame, utensils, bed-double, car, coffee, mountain, ship, ticket, plane, tent, sailboat, camera, heart, sparkles), all present in the sprite.

**Tour Guide (`ogt_guide`)** — title, block editor body, featured image (cover), excerpt, author, destination + guide topic boxes; side panel: `cover_caption`, `glance_best_time`, `glance_budget`, `glance_length`, `glance_visa` (box hidden if all empty; heading = "{destination} at a glance").

**Blog post** — same side panel as Tour Guide.

**Review (`ogt_review`)** — title = reviewer name (auto-filled): `quote`, `reviewer_name`, `reviewer_city`, `travel_date` (month + year), `rating` (1–5), `source` (Google / Tripadvisor / Direct), `avatar`, `itinerary` (trip), `featured`.

**Moment (`ogt_moment`)** — `media_type` (Photo / Video), `image`, `video_file`, `duration`, `handle`, `likes`, `views`, `link`, `tile_size` (Normal / Tall / Wide / Big).

**Trip CTA block** — `itinerary`, `heading`, `text`.

**Homepage options** — tabs:
- *Hero*: `hero_heading`, `hero_subtitle`, `hero_cta_primary` (link), `hero_cta_secondary` (link), `hero_places` (repeater: `image`, `place`, `country`, `tab_label`)
- *Why OG*: `why_label`, `why_heading`, `why_quote` (with `*highlight*`/`_teal_` marks — final syntax fixed in phase 05), `why_cite`, `why_pillars` (repeater: `icon`, `title`, `text`, `stat_number`, `stat_suffix`, `stat_label`)
- *Reviews*: `reviews_label`, `reviews_heading`, `reviews_rating`, `reviews_count_label`
- *Moments*: `moments_label`, `moments_heading`, `moments_intro`
- *Trips*: `trips_label`, `trips_heading`
- *Contact*: `contact_label`, `contact_heading`, `contact_lead`, `form_title`, `form_subtitle`

**Site Settings options** — tabs:
- *Contact*: `phone`, `whatsapp` (international format, digits only), `email`, `enquiry_email`, `address`, `office_map_url`
- *Social*: `instagram_handle`, `hashtag`, `instagram_url`, `facebook_url`, `youtube_url`
- *Footer*: `footer_blurb`, `newsletter_enabled`
- *Booking*: `booking_trust_text` ("No payment now · Free cancellation")

**Users** — `ogt_photo`, `byline_role`, `specialty`, `reply_time`, `whatsapp` (D1); bio = core "Biographical Info".

## Merchant admin (Editor role)

Menu: Dashboard · Trips · Tour Guides · Blog · Reviews · Moments · Enquiries (bubble) · Homepage · Site Settings · Media. Nothing else except Profile. Administrators additionally see Pages, Appearance, Plugins, Users, Tools, Settings, Yoast, SCF (local only).

## Steps

1. Write post types/taxonomies/fields/admin files; build field groups in the SCF UI locally, export to `acf-json/` and keep the JSON as the source.
2. Update `setup.sh`; run `npm run setup` (still idempotent).
3. Lint, debug.log, admin tests; update the `wp-content-model` skill.
4. `wp-tester` + `wp-reviewer`; fix; commit + tag `phase-02`.

## Acceptance criteria

1. As `merchant` (Editor), the admin menu is exactly the list above, in that order; no Comments, Tools, Yoast, SCF, Appearance, Plugins, Settings, update nags or plugin notices.
2. The merchant can **add, edit and delete** a Trip filling every tab, and the values save and reload correctly (Playwright fills at least one field per tab incl. a repeater row, image, date and gallery).
3. Same for a Tour Guide (block editor shows only the allowed blocks + Trip CTA), a Review, a Moment and a Blog post.
4. Homepage and Site Settings pages save and reload every field.
5. Enquiries: list visible to the merchant with Status column; no "Add New" button; a post inserted via WP-CLI shows up with the "new" bubble count.
6. URLs: a published trip → `/trips/{slug}/` 200; guide → `/travel-guide/{slug}/` 200; blog post → `/blog/{slug}/` 200; `/trips/`, `/travel-guide/`, `/destinations/{term}/` 200; review/moment/enquiry have no public URL (404) and are excluded from Yoast's sitemap.
7. Comments: no comment form, menu, count or feed anywhere; `default_comment_status` closed; existing post types have no comments support.
8. Block editor is off for Trips, Reviews, Moments (classic SCF form only) and on for Tour Guides and Blog.
9. No Gravatar requests on any admin or front-end page.
10. Field groups load from `acf-json/` (`wp eval` confirms local JSON source); deactivating SCF leaves the site and admin without fatal errors.
11. `npm run setup` re-runs cleanly; `lint:php` clean; `debug.log` empty after the admin test run; `npm run test:e2e` (smoke + admin) passes.

## Test plan

`wp-tester`: acceptance 1–11 with Playwright logged in as `merchant` and as `admin`. `wp-reviewer`: capabilities (Editor can't reach admin-only screens by URL), sanitising/escaping in admin columns and `ogtrips_core_emphasis()`, field names vs this spec, no front-end assets added.

## Checkpoint for the user

Log in as **merchant / password** at http://localhost:8888/wp-admin and try: add a trip (all tabs), a tour guide with a Trip CTA block, a review, a moment; edit Homepage and Site Settings. Judge: is it simple enough for the client? Front-end pages still look like plain GeneratePress — expected.
