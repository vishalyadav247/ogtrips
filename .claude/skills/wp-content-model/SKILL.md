---
name: wp-content-model
description: The OgTrips data contract — custom post types, taxonomies, Secure Custom Fields (SCF) field groups, options pages, enquiry storage and URL structure in the ogtrips-core plugin. Load before registering content types or fields, writing queries, importing demo content, or reading field values in templates.
---

# OgTrips content model (`wp/plugins/ogtrips-core/`)

Fields use **Secure Custom Fields** (free, wordpress.org, ACF-compatible API: `get_field`, `have_rows`, `acf_add_options_page`, local JSON). No paid plugins. Registered in the plugin (never the theme). Field groups saved to `plugins/ogtrips-core/acf-json/` (SCF keeps ACF's local-JSON folder name) so they're versioned. Field names are `snake_case` and **are an API** — renaming one requires a migration.

## Post types

### `ogt_itinerary` — Trips · URL `/trips/{slug}/` · archive `/trips/`
Supports: title, editor (overview), excerpt (card summary), thumbnail (hero), revisions. `show_in_rest: true`.

| Field | Type | Notes / design element |
|---|---|---|
| `duration_days`, `duration_nights` | number | "7D / 6N" |
| `price_from`, `price_original` | number (₹) | strike-through price + "save" chip computed |
| `group_min`, `group_max` | number | "2–12 people" |
| `best_time` | text | "April – October" |
| `pace` | select easy/moderate/challenging | facts bar |
| `stays_label` | text | "4★ villas & resorts" |
| `rating`, `review_count` | number | until reviews are aggregated automatically |
| `badge` | text | "Bestseller", "Honeymoon pick" |
| `is_bestseller`, `bestseller_rank` | true/false, number | home "Most-loved trips" |
| `incl_icons` | checkbox (bed, meals, car, ticket, tent, plane, waves, sailboat) | `.incl-mini` |
| `route_stops` | repeater: `label`, `days` | `.route` |
| `highlights` | repeater: `icon`, `text` | `.highlight-grid` |
| `days` | repeater: `title`, `location`, `description` (wysiwyg basic), `image`, `tags` (repeater `icon`,`text`) | day accordion |
| `stays` | repeater: `name`, `nights`, `room`, `image` | `.stays` |
| `included`, `excluded` | repeater: `text` | `.incl` |
| `gallery` | gallery | bento gallery |
| `faqs` | repeater: `question`, `answer` | FAQ + FAQPage JSON-LD |
| `departures` | repeater: `date`, `seats_left` | booking select |
| `expert` | user (role trip_captain) | `.expert` card |
| `pdf_itinerary` | file | download button |

### `ogt_review` — Reviews (not public singles)
`quote` (textarea), `reviewer_name`, `reviewer_city`, `travel_date` (month), `rating` (1–5), `source` (google/tripadvisor/direct), `avatar` (image), `itinerary` (post object → ogt_itinerary), `featured` (true/false).

### `ogt_moment` — Social tags (curated by the team; not public singles)
`media_type` (image/video), `image`, `video_url` (self-hosted mp4 or Instagram permalink), `duration`, `handle`, `likes`, `views`, `permalink`, `tile_size` (normal/tall/wide/big). Get the traveller's permission before posting their content.

### `ogt_enquiry` — Leads from the contact / Reserve forms (private, admin-only)
`show_ui: true`, `public: false`, `capability_type` mapped so only editors+ see it. Stored fields (post meta, not SCF): `name`, `phone`, `email`, `destination`, `travel_date`, `trip_type`, `travellers`, `message`, `itinerary_id`, `departure`, `source_page`, `status` (new/contacted/won/lost). Title = "{name} – {destination}".
Handler: `admin-post.php` actions `ogtrips_enquiry` (logged-out + logged-in) → verify nonce, honeypot field empty, rate-limit by IP (transient), sanitise → `wp_insert_post` → `wp_mail` to the address in options `contact.enquiry_email` (sent via Hostinger SMTP) → redirect back with `?enquiry=sent#contact`. Never store more personal data than the form asks for.
**WhatsApp channel**: every enquiry form also has a "Send on WhatsApp" button. JS builds `https://wa.me/<contact.whatsapp>?text=` + URL-encoded message (name, trip, departure, travellers, message) and opens it; it also posts the same data to the handler (`channel=whatsapp`) so the lead is logged. Floating/`contact-list` WhatsApp links use the same number. Stored field: `channel` (email/whatsapp).

### `ogt_guide` — Tour Guides (priority) · URL `/travel-guide/{slug}/` · archive `/travel-guide/`
Approved design = `guide.html`. Supports: title, editor (article body), excerpt, thumbnail (cover), author, revisions; `show_in_rest: true` (needed for the block editor). Comments off.
- **Body**: core block editor limited to paragraph, heading (H2/H3), image, list, quote (→ `.pullquote` style), table (→ `.table`), separator. Headings auto-build the sticky TOC; first paragraph gets the drop cap. Register 2 simple block styles only if needed: "Tip box" (group → `.tip`) and "Area cards" (columns → `.area-cards`).
- **Side fields (SCF)**: `glance_best_time`, `glance_budget`, `glance_length`, `glance_visa` (the "at a glance" box; hidden if empty), `related_itinerary` (post object → `.trip-cta`), `cover_caption`, `byline_role` (e.g. "OgTrips trip captain").
- Taxonomy: `ogt_destination`. Reading time computed. Author name/photo/bio from the user profile.

### Blog = core `post` (optional for the client; no extra build)
Labelled "Blog" in admin; URL `/blog/{slug}/`. Uses the same article template and block restrictions as Tour Guides. Comments off.

## Taxonomies

- `ogt_destination` (hierarchical, country → region) on `ogt_itinerary`, `ogt_guide`, `post`. URL `/destinations/{slug}/`. Term fields: `cover_image`, `country_label`.
- `ogt_trip_type` (flat: honeymoon, family, adventure, friends, culture) on `ogt_itinerary`. Drives filters + contact form pills.

## Options pages (SCF)

**"Homepage"** (top-level menu, house icon) — everything on the front page that isn't a post:
- `hero_places` repeater: `image`, `place` ("Bali."), `country`, `tab_label`
- `why_og` group: `quote` (allows `<span class="hl">`, `<span class="teal">`), `cite`, `pillars` repeater (`icon`, `title`, `text`, `stat_number`, `stat_suffix`, `stat_label`)
- `reviews_summary`: `rating`, `count_label`

**"Site Settings"** (contact & brand details used site-wide):
- `social`: `instagram_handle`, `hashtag`, profile URLs
- `contact`: `phone`, `whatsapp`, `email`, `enquiry_email` (where leads are sent), `address`, `office_map_url`
- `footer_blurb`

## Merchant admin experience (ogtrips-core `admin/`)

- Client logs in with an **Editor**-role account (developer keeps Administrator). Comments disabled site-wide (menus, metaboxes, admin bar, front end).
- Menu order & labels: Dashboard · **Trips** · **Tour Guides** · **Blog** · **Reviews** · **Moments** · **Enquiries** (with "new" count bubble) · **Homepage** · **Site Settings** · Media · (Pages, Appearance, Plugins, Tools, Settings, Yoast only for administrators).
- Hide for the merchant role: Comments (if off), Tools, unused dashboard widgets, WP/Yoast/plugin nag notices, "Howdy" clutter. Custom dashboard widget: quick-add Trip / Guide / Blog post, latest enquiries.
- Forms: fields grouped in **tabs** (Overview · Pricing & Dates · Day by Day · Stays · Inclusions · Gallery · FAQ), clear instructions on every field, sensible defaults, required fields marked, image size hints.
- Block editor disabled for all CPTs except Tour Guides and Blog posts; those are limited to basic blocks, no custom colours/fonts (design stays consistent).
- Yoast metabox moved below our fields and collapsed for the merchant; keep the SEO title/description fields visible.

## Rules

- Templates read fields only through `inc/helpers.php` getters with sensible fallbacks.
- Queries: `WP_Query` with `no_found_rows => true` when not paginating; cache expensive ones in transients invalidated on `save_post_{type}`.
- Demo content import script (WP-CLI) lives in `plugins/ogtrips-core/cli/` and marks every item with meta `_ogt_demo = 1` so it can be wiped before launch.
- After changing rewrite slugs: `npx wp-env run cli wp rewrite flush`.
