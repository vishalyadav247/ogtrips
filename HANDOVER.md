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
6. **Plugins → Add new → Upload plugin** → `release/ogtrips-core-plugin.zip` → **Activate**. (About 6 MB — it includes the demo photos.)
7. **Settings → OgTrips demo → Import demo content.** Takes about a minute. The result is the same site as the development laptop: 4 trips (Ladakh, Shimla–Manali, Kashmir, Spiti), 4 tour guides, 2 blog posts, reviews, Instagram moments, the Home page content, About/FAQs/Cancellation/Privacy pages, Site Settings, the 5 destinations, trip types with icons and guide topics, and the same 17 photos (bundled inside the plugin — no internet download). It also sets the site name, tagline, India time zone, date format, `/blog/post-name/` links and comments off, and makes Home the front page and Blog the posts page.
8. Open **Settings → Permalinks** once and click **Save** (refreshes the link rules), then visit the site.
9. Create the client's **Editor** account (section 5) and set up email (section 4).

## 2. Before going live — replace the placeholders

The demo **trips and Ladakh guide come from your own itineraries**, but these are **fake placeholders** and must be changed:

- **Site Settings** (left menu): phone, WhatsApp number, email, **enquiry email** (where leads are sent), address, Instagram/Facebook/YouTube links.
- **Reviews:** the 4 reviews and names are invented — replace with real ones or delete.
- **Moments:** Instagram handles/likes are invented — replace with real tagged posts.
- **Trip expert "Neha Sharma"** (Users) and photos: demo — replace with your team.
- **Ratings / review counts / departure dates** on each trip: placeholders.
- **Homepage** (left menu): numbers like "12,000+ travellers", "4.9", "2,300+ reviews" are placeholders.
- Photos are free Unsplash images — swap for your own when you have them.

To wipe the demo: **Settings → OgTrips demo → Remove demo content**. It only deletes demo items nobody has edited since the import — a demo trip or guide you edited is kept as real content, photos still in use are kept, and Homepage / Site Settings fields are cleared only if they still hold the demo text. Import never overwrites settings you have filled in. **After removing, open Site Settings and check every contact field is yours** (an empty WhatsApp number hides the WhatsApp buttons).

## 3. Prices

**Site Settings → Booking → "Show prices on the website"** — **off** by default (client request). When off, every price, discount and total is hidden and trips show "Price on request". Turn it on any time; each trip's price is in its **Price & dates** tab.

## 4. Enquiries

- Contact form and trip booking form: every enquiry is saved under **Enquiries** in wp-admin (with status New / Contacted / Booked / Closed) **and** emailed to the Site Settings enquiry email.
- **"Send on WhatsApp"** buttons open WhatsApp with the trip, dates, travellers and name pre-filled (to the Site Settings WhatsApp number), and the lead is still saved in Enquiries.
- **Floating WhatsApp button** (bottom right, every page): opens a chat with the Site Settings WhatsApp number — on a trip page the message names the trip. Switch it off in Site Settings → Contact → "Show the WhatsApp button". No plugin needed.
- **No form plugin is needed.** The only plugin to add is **WP Mail SMTP** (below), so the emails are actually delivered.
- **Make email reliable (WP Mail SMTP, ~10 minutes, do this on the live site):**
  1. hPanel → **Emails** → create a mailbox, e.g. `hello@ogtrips.com`, and note its password.
  2. WordPress → **Plugins → Add New** → search **WP Mail SMTP** (by WP Mail SMTP / WPForms) → Install → Activate. Skip its setup wizard.
  3. hPanel → **File Manager** → open `public_html/wp-config.php` and paste this **above** the line `/* That's all, stop editing! */` — replace the address and password with yours:

     ```php
     // Email via the Hostinger mailbox (WP Mail SMTP reads these; the password stays out of the database).
     define( 'WPMS_ON', true );
     define( 'WPMS_MAIL_FROM', 'hello@ogtrips.com' );
     define( 'WPMS_MAIL_FROM_FORCE', true );
     define( 'WPMS_MAIL_FROM_NAME', 'OgTrips' );
     define( 'WPMS_MAIL_FROM_NAME_FORCE', true );
     define( 'WPMS_MAILER', 'smtp' );
     define( 'WPMS_SMTP_HOST', 'smtp.hostinger.com' );
     define( 'WPMS_SMTP_PORT', 465 );
     define( 'WPMS_SSL', 'ssl' );
     define( 'WPMS_SMTP_AUTH', true );
     define( 'WPMS_SMTP_USER', 'hello@ogtrips.com' );
     define( 'WPMS_SMTP_PASS', 'PUT-THE-MAILBOX-PASSWORD-HERE' );
     ```

  4. WordPress → **WP Mail SMTP → Tools → Email Test** → send a test to your own address. Then send one enquiry from the website's contact form and check it arrives at the **Site Settings → enquiry email** address (and appears under **Enquiries**).
  5. Never commit `wp-config.php` or this password to GitHub.
  - If the test fails: port `587` with `define( 'WPMS_SSL', 'tls' );` is the alternative; check the mailbox password in hPanel.
- Spam protection: hidden honeypot field and max 5 enquiries per visitor per 10 minutes (pages are cached, so the forms deliberately use no expiring security token). **After launch, send 2 test enquiries** and check both arrive; if the limit ever blocks everyone, Hostinger may be hiding visitor IPs behind a proxy — tell a developer.

## 5. Accounts

- **Logging in:** click the person icon in the site header (or go to `/wp-login.php`). The login page is branded with the OgTrips logo and the first homepage photo. Once logged in, the same icon opens the dashboard.
- Keep your **Administrator** account for yourself (developer).
- **Users → Add new** for the client with role **Editor**. Editors see only: Dashboard, Trips, Tour Guides, Blog, Reviews, Moments, Enquiries, Pages, Site Settings, Media and Appearance → Menus (Customizer, Widgets and theme screens are blocked; the Home and Blog pages cannot be deleted; the Privacy page can only be edited by the admin — a WordPress rule).

## 6. Speed & SEO settings

- **LiteSpeed Cache → Cache:** enable. **Page Optimization:** leave CSS/JS **minify/combine OFF** (the theme already ships one small CSS and one JS file). The default cache time is fine.
- **Image optimisation (optional):** LiteSpeed → Image Optimization → request WebP.
- **Yoast SEO:** run the first-time configuration (organisation name "OgTrips", logo). Sitemaps are automatic (`/sitemap_index.xml`). Trip pages output **TouristTrip** and **FAQPage** structured data through Yoast.
- **Settings → Reading:** untick "Discourage search engines" when going live.
- Submit the sitemap in Google Search Console.

## 7. How the merchant edits content

| Left menu | What |
|---|---|
| **Trips** | Add / edit / delete trips — simple form with tabs (Overview, Facts, Price & dates, Day by day, Stays, Inclusions, Gallery, FAQ, Expert). Bestseller tick + rank puts a trip on the homepage. |
| **Tour Guides** | Articles in the block editor (paragraph, heading, image, list, quote, table, separator + **Trip CTA** box). Side fields: cover caption, "at a glance" box, destination, topic. |
| **Blog** | Same article layout as tour guides. Kept because the client may want to post news/stories; 2 demo posts are imported. |
| **Footer pages** | About us, FAQs, Cancellation policy (terms from the Ladakh itinerary document) and Privacy policy are created by the demo import with placeholder text — the **admin** edits them under Pages (the merchant account has no Pages menu). Privacy text needs a legal review. |
| **Reviews / Moments** | Homepage reviews slider and Instagram grid. For a gap-free grid with 8 moments use tile sizes 1 Big + 1 Tall + 6 Normal. |
| **Pages → Home** | Hero slideshow places, "Why OG" text, section headings, contact text. Write `*word*` to show a word in coral, e.g. `Most-loved *trips* right now`. Fields only (no editor) plus the Yoast SEO box for the homepage title / description; changes can be undone from Revisions. |
| **Site Settings** | Logo (optional upload — replaces the built-in OgTrips logo in header, footer and login page), contact details, WhatsApp button switch, social links, footer text, newsletter switch, price switch. |

Menus: **Appearance → Menus** (the merchant can edit these too) (locations: Main menu, Footer — Explore, Footer — Support). Until a menu is assigned, sensible default links are shown.

## 8. Known follow-ups (not blocking launch)

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

## 10. Code-review follow-ups (minor, 2026-10-08)

The pre-launch review's high and medium items are fixed. Still open (small, not blocking):

- Bestseller with an empty rank sorts before rank 1 — always fill the rank when ticking Bestseller.
- Homepage reviews: the 12 newest are taken, then featured ones shown first — an old featured review may not appear.
- Trip expert's WhatsApp pre-filled text can show codes like `&#8217;` for apostrophes in trip titles.
- Accessibility polish: day/FAQ accordion buttons need `aria-controls` and the heading outside the button; the scrollable route line needs keyboard focus.
- Newsletter (off by default): after subscribing, no thank-you message is shown on article pages.
