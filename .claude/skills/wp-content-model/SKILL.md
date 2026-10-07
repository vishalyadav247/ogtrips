---
name: wp-content-model
description: The OgTrips data contract — custom post types, taxonomies, Secure Custom Fields (SCF) field groups, options pages, the Trip CTA block, user-profile fields, enquiry storage and URL structure in the ogtrips-core plugin. Load before registering content types or fields, writing queries, importing demo content, or reading field values in templates.
---

# OgTrips content model (`wp/plugins/ogtrips-core/`)

Source of truth: spec `.claude/specs/02-content-model-admin.md` (decisions D1–D13). This skill mirrors it; if they disagree, the spec wins and this file must be fixed.

Fields use **Secure Custom Fields** (free, wordpress.org, ACF-compatible API: `get_field`, `have_rows`, options pages, blocks, local JSON). Registered in the plugin, never the theme. Field groups live in `plugins/ogtrips-core/acf-json/group_ogt_*.json` (SCF save/load paths redirected there in `includes/fields/scf.php`; the SCF builder UI shows only for administrators on the local site). Field names are `snake_case` and **are an API** — renaming one requires a migration.

## Conventions for merchant-entered text

- **`*stars*` = coral emphasis.** Headings/titles the merchant types use `*word*`; output with `ogtrips_core_emphasis( $text )` (escapes everything, then `*x*` → `<em>x</em>`). Never allow raw HTML in fields.
- Images/files/galleries return **attachment IDs** → output with `wp_get_attachment_image( $id, 'ogt-card' )` etc.
- Dates return `Y-m-d`; format on output with `wp_date()`.
- Empty optional fields fall back as noted below — templates must handle empty values.

## Post types

### `ogt_itinerary` — Trips · `/trips/{slug}/` · archive `/trips/`
Supports: title, editor (overview "The trip"), excerpt (Yoast meta fallback only), thumbnail (hero), revisions. Classic screen (block editor off). Taxonomies: `ogt_destination`, `ogt_trip_type`. Group `group_ogt_itinerary` (tabs):

| Tab | Fields |
|---|---|
| Overview | `title_display` (text, `*stars*`; empty → title), `short_title` (empty → title), `location_label` (empty → destination term), `badge` (text), `badge_style` (sun/coral/plain), `badge_icon` (award/heart/baby/flame/sparkles, optional), `is_bestseller` (bool), `bestseller_rank` (number, 1 = first, home shows 4), `route_stops` (repeater: `label`, `days` **text** e.g. "Day 1–3"), `highlights` (repeater: `icon`, `text`) |
| Facts | `duration_days`, `duration_nights`, `group_min`, `group_max` (numbers), `best_time` (text), `pace` (easy / easy-moderate / moderate / moderate-challenging / challenging), `stays_label`, `rating` (0–5 step 0.1), `review_count` |
| Price & dates | `price_from` (₹, required), `price_original` (₹, optional → strike-through + computed saving), `offer_label` ("Early-bird"), `departures` (repeater: `date` Y-m-d, `seats_left`), `pdf_itinerary` (file ID, PDF) |
| Day by day | `days` (repeater: `title`, `location`, `description` basic wysiwyg, `image`, `tags` repeater `icon`,`text`) — day number = row index |
| Stays | `stays` (repeater: `name`, `nights` number, `room`, `image`) |
| Inclusions | `incl_icons` (checkbox values = Lucide names: bed-double, utensils, car, ticket, tent, plane, waves, sailboat; labels Stay, Meals, Transfers, Activities, Camping, Flights, Water sports, Cruise), `included` / `excluded` (repeater `text`) |
| Gallery | `gallery` (IDs, 5 recommended, first = largest) |
| FAQ | `faqs` (repeater: `question`, `answer` basic wysiwyg — may contain links) |
| Expert | `expert` (user ID; see Users) |

Icon selects (`highlights.icon`, `days.tags.icon`, `why_pillars.icon`) store Lucide names from a curated list (sunrise, sprout, droplets, waves, flame, utensils, bed-double, car, coffee, mountain, ship, ticket, plane, tent, sailboat, camera, heart, sparkles, shield-check, headset). Every offered icon must exist in the sprite — add new ones to `EXTRA_ICONS` in `scripts/build-icons.mjs`.

### `ogt_guide` — Tour Guides · `/travel-guide/{slug}/` · archive `/travel-guide/`
Approved design = `guide.html`. Supports: title, editor (block editor), excerpt, thumbnail (cover), author, revisions. Taxonomies: `ogt_destination`, `ogt_guide_topic`.
- **Body blocks** (only): paragraph, heading (H2/H3 only — H2s build the TOC), image, list, quote (→ `.pullquote`), table, separator, **`ogtrips/trip-cta`**. No custom colours, font sizes, spacing, borders, patterns or Openverse.
- **Side panel** `group_ogt_article`: `cover_caption`, `glance_best_time`, `glance_budget`, `glance_length`, `glance_visa` (box hidden when all empty; heading computed "{destination} at a glance").
- Byline/author card from the **author user** (see Users). Reading time and "Updated" date computed.

### Blog = core `post` · `/blog/{slug}/`
Labelled "Blog" in admin. Same block rules and side panel as Tour Guides; taxonomy `ogt_destination` added (core categories/tags remain).

### `ogt_review` — Reviews (not public)
No title/editor; title auto-set from `reviewer_name` on save. Group `group_ogt_review`: `quote`, `reviewer_name`, `reviewer_city`, `travel_date` (Y-m-d, display "June 2026"), `rating` ("1"–"5", labels "5 stars"…), `source` (google / tripadvisor / direct), `avatar` (image ID), `itinerary` (trip ID — card shows its thumbnail + `short_title` + days), `featured` (bool → homepage).

### `ogt_moment` — Moments (not public; curated, get the traveller's permission)
Title = admin-only note. Group `group_ogt_moment`: `media_type` (image/video), `image` (required; video cover), `video_file` (MP4 ID, optional), `duration` (text "0:42"), `handle`, `likes` (text "12.4k"), `views` (text), `link` (Instagram post URL), `tile_size` (normal/tall/wide/big → CSS classes; normal = none).

### `ogt_enquiry` — Enquiries (private, Editors + Administrators only)
Created only by the form handler (phase 06) or WP-CLI — `create_posts` = `do_not_allow`; all other caps map to `edit_others_posts` / `delete_others_posts`. `post_status` = `private`, title "{name} – {destination}". Protected meta: `_ogt_name`, `_ogt_phone`, `_ogt_email`, `_ogt_destination`, `_ogt_travel_date`, `_ogt_trip_type`, `_ogt_travellers`, `_ogt_departure`, `_ogt_message`, `_ogt_channel` (email/whatsapp), `_ogt_source_page`, `_ogt_itinerary_id`, `_ogt_status` (new/contacted/won/lost; defaults to `new` on save). `ogtrips_core_new_enquiry_count()` (transient `ogtrips_core_new_enquiries`, cleared on status change/delete) feeds the menu bubble. Never store more personal data than the form asks for.
Handler (phase 06): `admin-post.php` action `ogtrips_enquiry` → nonce, honeypot, IP rate limit, sanitise → insert → `wp_mail` to `enquiry_email` → redirect `?enquiry=sent#contact`. WhatsApp: JS opens `https://wa.me/<whatsapp>?text=…` and posts the same data with `channel=whatsapp`.

## Taxonomies

- `ogt_destination` — hierarchical (country → region), on trips, guides, posts. Public, `/destinations/{slug}/`. No term fields yet (cover/label return with the destination page, phase 07).
- `ogt_trip_type` — on trips; not public; tick-boxes. Term field `icon` (heart/users/mountain/party-popper/landmark/waves/sparkles). Seeded: honeymoon, family, adventure, friends, culture.
- `ogt_guide_topic` — on guides; not public; tick-boxes. Seeded: destination-guide, travel-tips, seasonal.

(Trip types and guide topics are registered hierarchical only for the tick-box UI; never nested — admin.css hides the parent picker.)

## Block `ogtrips/trip-cta` (Trip CTA)
`blocks/trip-cta/` (block.json + render.php, design `.trip-cta` markup). Fields `group_ogt_trip_cta`: `itinerary` (trip ID, required), `heading` (empty → trip title), `text`. Renders nothing on the front end if the trip isn't published.

## Users (trip experts & authors — D1)
Experts and guide authors are WordPress users (role Author, created by the administrator). Group `group_ogt_user`: `ogt_photo` (image ID), `byline_role` ("OgTrips trip captain"), `specialty`, `reply_time`, `whatsapp` (digits; empty → site number). Read with `get_field( 'specialty', 'user_' . $user_id )`. Bio = core description. `get_avatar()` uses `ogt_photo` or `assets/avatar.svg` — **never Gravatar**.

## Options pages (`get_field( 'name', 'option' )`; capability `edit_pages`)

**Homepage** (`ogtrips-homepage`, `group_ogt_homepage`):
- Hero: `hero_heading`, `hero_subtitle`, `hero_cta_primary`, `hero_cta_secondary` (link arrays), `hero_places` (repeater: `image`, `place` "the Alps.", `country`, `tab_label` "Swiss Alps"; max 6)
- Why OG: `why_label`, `why_heading`, `why_quote` (highlight syntax fixed in phase 05), `why_cite`, `why_pillars` (repeater: `icon`, `title`, `text`, `stat_number`, `stat_suffix`, `stat_label`; max 3)
- Reviews: `reviews_label`, `reviews_heading`, `reviews_rating`, `reviews_count_label`
- Moments: `moments_label`, `moments_heading`, `moments_intro`
- Trips: `trips_label`, `trips_heading`
- Contact: `contact_label`, `contact_heading`, `contact_lead`, `form_title`, `form_subtitle`

**Site Settings** (`ogtrips-site-settings`, `group_ogt_site_settings`):
- Contact: `phone` (display), `whatsapp` (digits), `email`, `enquiry_email` (required, not shown), `address`, `office_map_url`
- Social: `instagram_handle`, `hashtag`, `instagram_url`, `facebook_url`, `youtube_url`
- Footer: `footer_blurb`, `newsletter_enabled` (bool, default off → newsletter box hidden)
- Booking: `booking_trust_text`

Fixed UI words on trip/guide pages ("Day by day", "Expand all", facts labels…) are translatable strings in the theme, not fields.

## Merchant admin (`includes/admin/`)
Editor account sees exactly: Dashboard · Trips · Tour Guides · Blog · Reviews · Moments · Enquiries (bubble) · Homepage · Site Settings · Media · Profile. Comments off everywhere; plugin notices stripped (SCF/OgTrips notices kept); dashboard = "OgTrips" widget (quick-add + latest enquiries); Yoast box low; Yoast columns hidden. Administrators see everything.

## Rules
- Templates read fields through theme helpers (`ogtrips_field()` etc.) with fallbacks; the theme must not fatal without SCF/ogtrips-core.
- Queries: `no_found_rows => true` when not paginating; cache expensive ones in transients invalidated on `save_post_{type}`.
- Demo content seeder (phase 03, `includes/cli/`) marks every item with meta `_ogt_demo = 1` so it can be wiped before launch.
- After changing rewrite slugs: `npx wp-env run cli wp rewrite flush`.
