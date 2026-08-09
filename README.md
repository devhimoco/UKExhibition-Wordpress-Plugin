# HimoEXPO — UK Exhibitions WordPress Plugin

A WordPress plugin that automatically scrapes daily-updated UK B2B trade exhibition listings, categorizes them, and lets site visitors search, browse, and register interest in exhibitions — with a full admin dashboard for managing registrations.

Built for **Royal Hermes Group** by **HimoEXPO (h.maghsoudloo)**.

[![Latest Version](https://img.shields.io/github/v/release/devhimoco/UKExhibition-Wordpress-Plugin?label=latest%20release)](https://github.com/devhimoco/UKExhibition-Wordpress-Plugin/releases)
[![License: Proprietary](https://img.shields.io/badge/license-proprietary-red)](LICENSE)
[![WordPress Plugin](https://img.shields.io/badge/WordPress-plugin-0073aa)](https://wordpress.org)

---

## What it does

The plugin scrapes UK B2B exhibition listings from [EventsEye](https://www.eventseye.com) once a day via WP-Cron, stores them in a dedicated database table, and displays them on the front end through a shortcode. Visitors can search, filter by category, and submit a registration-of-interest form for any exhibition — which triggers a confirmation email to the visitor and dual notification emails to admins.

### Key features

- **Automated daily scraping** — WP-Cron job (with a manual "Run Scrape Now" button) pulls exhibition data from EventsEye: name, dates, city, venue, cycle, and description
- **Auto-categorization** — a keyword-based engine sorts every exhibition into one of 20 default categories (Automotive, Aerospace, Construction, Energy, Technology, Food & Drink, Healthcare, Fashion, Finance, Marketing, Manufacturing, Logistics, Real Estate, Education, Science, Agriculture, Sports, Printing, Franchise & Business, Other), each with its own bundled icon and a custom-image upload option
- **Front-end shortcode `[rhg_exhibitions]`** — a responsive (desktop/tablet/phone) card grid with:
  - Live debounced search and a configurable per-page count (10/20/40/50)
  - Multi-select category filtering (dropdown on mobile, pills on desktop)
  - "Days to go" badges, hover-to-reveal descriptions, and a branded registration popup
  - A "Can't find your exhibition?" flow for visitors to manually request a listing that isn't in the database yet
- **Registration system** — full name, email, job title, searchable country/town, and an optional phone number, with server-side validation
- **Email notifications** — visitor confirmation email plus two configurable admin notification addresses, with a built-in "Send Test Email" diagnostic and captured SMTP error reporting
- **Admin dashboard** ("HimoEXPO" menu in wp-admin) with three pages:
  - **Registrations** — every submission, filterable by keyword, date range, city, and category, including flagged "not found" requests
  - **Exhibitions** — all scraped listings grouped into a collapsible accordion by category, with registrant counts and drill-through to each exhibition's registrant list
  - **Settings** — admin notification emails, category management (add/edit/remove + keyword rules + images), scrape controls, and a shortcode reference
- **Self-upgrading database schema** — new columns introduced in later versions are added automatically on first load after an update, no manual reactivation required

## Requirements

- WordPress (tested on recent 5.x/6.x releases)
- PHP 7.4+
- MySQL/MariaDB (standard WordPress database)
- WP-Cron enabled (either the default or a real system cron hitting `wp-cron.php`)
- Outbound HTTPS access from the server to `eventseye.com` (required for the daily scrape)
- A working mail transport for notification emails — **on local environments (XAMPP, Local, etc.) install [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/)**, since PHP's built-in `mail()` function has no real mail server to talk to on localhost and will fail silently. See [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md).

## Installation

### Option A — Download a release (recommended)

1. Go to [Releases](https://github.com/devhimoco/UKExhibition-Wordpress-Plugin/releases) and download the `.zip` for the version you want (the latest tag is the current production version)
2. In wp-admin, go to **Plugins → Add New → Upload Plugin**, choose the zip, and click **Install Now**
3. Click **Activate**

### Option B — Clone from GitHub

```bash
cd wp-content/plugins
git clone https://github.com/devhimoco/UKExhibition-Wordpress-Plugin.git himoexpo-uk-exhibitions
```

Then activate **HimoEXPO** from **Plugins** in wp-admin.

### First-time setup

1. Activating the plugin creates its database tables (`rhg_exhibitions`, `rhg_registrations`) and schedules the daily scrape automatically
2. Go to **HimoEXPO → Settings** and:
   - Enter the two admin notification email addresses
   - Use **Send Test Email** to confirm mail delivery works before relying on it
   - Click **Run Scrape Now** to populate exhibitions immediately instead of waiting for the next daily cron run
   - Review **Category Management** — edit keyword rules or upload custom category images if desired
3. Add the shortcode to any page or post:
   ```
   [rhg_exhibitions]
   ```
4. If **Run Scrape Now** reports 0 exhibitions (most common on local XAMPP setups), see [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) — it covers SSL/certificate errors, connectivity issues, and bot-detection responses, matched to the exact error message the plugin shows.

## Project structure

```
royal-hermes-exhibitions.php   # Plugin bootstrap: activation, cron, schema auto-upgrade
admin/
  class-rhg-admin.php          # wp-admin menu + Registrations/Exhibitions/Settings pages
includes/
  class-rhg-categories.php     # Keyword-based auto-categorization engine
  class-rhg-db.php             # Database schema + queries
  class-rhg-scraper.php        # EventsEye scraper
  class-rhg-cron.php           # Daily WP-Cron scheduling
  class-rhg-shortcode.php      # [rhg_exhibitions] front-end rendering
  class-rhg-ajax.php           # Search/filter/registration AJAX handlers
  class-rhg-email.php          # Registration + admin notification emails
assets/
  css/, js/, img/              # Front-end and admin styling, scripts, category icons
docs/
  TROUBLESHOOTING.md           # Scraper failure diagnostics (SSL, connectivity, bot-blocking)
CHANGELOG.md                   # Full version-by-version history from v5.0.0 onward
```

## Version history

This repository's `main` branch always reflects the **latest** version. Every historical build — including early pre-release prototypes and alternate variants — is preserved and downloadable from **[Releases](https://github.com/devhimoco/UKExhibition-Wordpress-Plugin/releases)**, each with its own notes and the original `.zip` as a downloadable asset.

For the detailed, version-by-version technical changelog (what changed and why, from v5.0.0 through the current release), see **[CHANGELOG.md](CHANGELOG.md)**.

Versioning follows semantic versioning from v5.0.0 onward:
- **+0.1** — small changes (bug fixes, minor UI tweaks, small feature additions)
- **+1.0** — big changes (new pages, new systems, major redesigns, new integrations)

Versions prior to v5.0.0 (`v0.1.0`–`v0.4.0` in this repo's Releases) were early prototypes built before this versioning scheme was adopted.

## License

Proprietary — All rights reserved. This code is the property of Royal Hermes Group / HimoEXPO and is not licensed for reuse, modification, or redistribution by third parties. See [LICENSE](LICENSE).

## Author

**HimoEXPO** (h.maghsoudloo) — built for [Royal Hermes Group](https://royalhermesgroup.com)
