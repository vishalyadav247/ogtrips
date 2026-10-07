# OgTrips — hand-over & Hostinger launch guide

Status on 2026-10-07: the site is built. The `release/` folder holds the two files to upload:

| File | What it is |
|---|---|
| `release/ogtrips-theme.zip` | The **OgTrips** child theme (all design, templates, one CSS + one JS) |
| `release/ogtrips-core-plugin.zip` | The **OgTrips Core** plugin (trips, tour guides, reviews, moments, enquiries, admin clean-up, demo importer) |

Only code is moved — **no database migration**. All structures (content types, forms, settings screens) live in the plugin; content is entered on the live site (or loaded with the demo importer).

---

## 1. Install on Hostinger (about 20 minutes)

1. **hPanel → Websites → Add website → WordPress.** Choose a fresh install. When Hostinger offers its onboarding/AI builder or extra plugins, skip them.
2. Log in to `https://yourdomain/wp-admin`.
3. **Plugins → Installed plugins:** delete everything Hostinger pre-installed (Hostinger tools, AI assistant, LiteSpeed is fine to keep — see step 6).
4. **Plugins → Add new**, install and activate (all free, from wordpress.org):
   - **Secure Custom Fields**
   - **Yoast SEO**
   - **LiteSpeed Cache**
5. **Appearance → Themes → Add new:** search **GeneratePress**, install (do not activate).
   Then **Upload theme** → `release/ogtrips-theme.zip` → **Activate**.
   Delete the other default themes (Twenty Twenty-…).
6. **Plugins → Add new → Upload plugin** → `release/ogtrips-core-plugin.zip` → **Activate**.
7. **Settings → Permalinks:** choose **Custom structure** and enter `/blog/%postname%/` → Save.
8. **Settings → OgTrips demo → Import demo content.** Wait 2–5 minutes (it downloads ~17 photos). This creates the 4 trips (Ladakh, Shimla–Manali, Kashmir, Spiti), 4 tour guides, reviews, Instagram moments, homepage text, site settings, and sets the Home + Blog pages.
9. Visit the site. Done.

## 2. Before going live — replace the placeholders

The demo **trips and Ladakh guide come from your own itineraries**, but these are **fake placeholders** and must be changed:

- **Site Settings** (left menu): phone, WhatsApp number, email, **enquiry email** (where leads are sent), address, Instagram/Facebook/YouTube links.
- **Reviews:** the 4 reviews and names are invented — replace with real ones or delete.
- **Moments:** Instagram handles/likes are invented — replace with real tagged posts.
- **Trip expert "Neha Sharma"** (Users) and photos: demo — replace with your team.
- **Ratings / review counts / departure dates** on each trip: placeholders.
- **Homepage** (left menu): numbers like "12,000+ travellers", "4.9", "2,300+ reviews" are placeholders.
- Photos are free Unsplash images — swap for your own when you have them.

To wipe all demo items at once: **Settings → OgTrips demo → Remove demo content** (only removes items the importer created).

## 3. Prices

**Site Settings → Booking → "Show prices on the website"** — **off** by default (client request). When off, every price, discount and total is hidden and trips show "Price on request". Turn it on any time; each trip's price is in its **Price & dates** tab.

## 4. Enquiries

- Contact form and trip booking form: every enquiry is saved under **Enquiries** in wp-admin (with status New / Contacted / Booked / Closed) **and** emailed to the Site Settings enquiry email.
- **"Send on WhatsApp"** buttons open WhatsApp with the trip, dates, travellers and name pre-filled (to the Site Settings WhatsApp number), and the lead is still saved in Enquiries.
- **Make email reliable:** in hPanel create a mailbox (e.g. `hello@yourdomain`), then install the free **WP Mail SMTP** plugin and connect it to Hostinger's SMTP (`smtp.hostinger.com`, port 465, SSL, the mailbox and its password). Send a test email from the plugin.
- Spam protection: hidden honeypot field, security token and max 5 enquiries per visitor per 10 minutes.

## 5. Accounts

- Keep your **Administrator** account for yourself (developer).
- **Users → Add new** for the client with role **Editor**. Editors see only: Dashboard, Trips, Tour Guides, Blog, Reviews, Moments, Enquiries, Homepage, Site Settings, Media.

## 6. Speed & SEO settings

- **LiteSpeed Cache → Cache:** enable. **Page Optimization:** leave CSS/JS **minify/combine OFF** (the theme already ships one small CSS and one JS file). **Cache → TTL → Default Public Cache TTL: `36000`** (10 h) — keeps the enquiry forms' security token valid.
- **Image optimisation (optional):** LiteSpeed → Image Optimization → request WebP.
- **Yoast SEO:** run the first-time configuration (organisation name "OgTrips", logo). Sitemaps are automatic (`/sitemap_index.xml`). Trip pages output **TouristTrip** and **FAQPage** structured data through Yoast.
- **Settings → Reading:** untick "Discourage search engines" when going live.
- Submit the sitemap in Google Search Console.

## 7. How the merchant edits content

| Left menu | What |
|---|---|
| **Trips** | Add / edit / delete trips — simple form with tabs (Overview, Facts, Price & dates, Day by day, Stays, Inclusions, Gallery, FAQ, Expert). Bestseller tick + rank puts a trip on the homepage. |
| **Tour Guides** | Articles in the block editor (paragraph, heading, image, list, quote, table, separator + **Trip CTA** box). Side fields: cover caption, "at a glance" box, destination, topic. |
| **Blog** | Same article layout as tour guides. |
| **Reviews / Moments** | Homepage reviews slider and Instagram grid. |
| **Homepage** | Hero slideshow places, "Why OG" text, section headings, contact text. Write `*word*` to show a word in coral, e.g. `Most-loved *trips* right now`. |
| **Site Settings** | Contact details, social links, footer text, newsletter switch, price switch. |

Menus: **Appearance → Menus** (locations: Main menu, Footer — Explore, Footer — Support). Until a menu is assigned, sensible default links are shown.

## 8. Known follow-ups (not blocking launch)

- The account icon in the header is a non-functional placeholder (as agreed).
- Newsletter box is off; it only stores sign-ups as Enquiries — connect a mail service later if wanted.
- The ₹ sign uses the system font (Poppins has no ₹ glyph in the subset we ship) — looks fine, can be refined.
- Listing pages (all trips, tour guide list, destination pages, search, 404) use a simple layout built from the design's components; they had no approved design.
- Swap the hand-drawn logo SVG (`ogtrips/assets/img/ogtrips-mark.svg`) for the official vector when available.

## 9. For a developer continuing the work

- Source: `wp/themes/ogtrips` (child theme of GeneratePress — never edit GeneratePress itself) and `wp/plugins/ogtrips-core`.
- Local copy with Docker: `cd wp && npm install && npx wp-env start && npm run setup`, then `npx wp-env run cli wp ogtrips demo import`. Site: http://localhost:8888 (admin / password).
- Field definitions: `wp/plugins/ogtrips-core/acf-json/` (Secure Custom Fields local JSON).
- Rebuild the zips: from `wp/themes` and `wp/plugins`, zip the `ogtrips` and `ogtrips-core` folders (folder at the top level of the zip).
- Project rules and decisions: `.claude/CLAUDE.md` and `.claude/specs/`.
