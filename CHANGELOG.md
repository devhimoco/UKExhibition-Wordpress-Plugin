# Royal Hermes Exhibitions — Changelog

Versioning rules:
- **+0.1** for small changes (bug fixes, minor UI tweaks, small feature additions)
- **+1.0** for big changes (new pages, new systems, major redesigns, new integrations)
- Starting point: **v5.0.0**

Each zip delivered is named with its version so you can always identify and reinstall a previous release.

> Every version described below — plus the four pre-v5.0.0 prototypes (v0.1.0–v0.4.0) that predate this changelog — is available as a downloadable `.zip` on the [Releases](https://github.com/devhimoco/UKExhibition-Wordpress-Plugin/releases) page.

---

## v10.1.0 — 2026-07-03
**Small release — optional phone number field**

### What changed
- Added a **Phone Number** field to the registration form, right after Email — clearly marked "(optional)" and not required, so customers can leave it blank
- New `phone` column on the registrations database table (nullable) — will be created automatically on next load via the auto-upgrade check added in v10.0.0, no reactivation needed
- Included in the **admin notification email** when provided (omitted entirely if left blank — no "Phone: " with nothing after it)
- New **Phone column** added to both the Registrations admin page and the per-exhibition registrants list, showing "—" when not provided
- Works identically for both normal registrations and the "Can't find your exhibition?" manual-entry flow from v10.0.0

---

## v10.0.0 — 2026-07-03
**Major release — "Can't find your exhibition?" section with manual registration**

### What's new
- **New banner at the top of the page**, above the search bar: *"Can't find your exhibition on this list? [Register Your Interest]"*
- Clicking it opens the **same registration popup**, but in a special mode with one extra field: **"Exhibition Name & Date"** — a free-text box where the visitor types the exhibition they were actually looking for
- The rest of the form (Name, Email, Job Title, Country, Town) is identical to a normal registration — same validation, same design
- **Emails are worded differently for this flow** — the admin gets "Exhibition request (not found on site): [what they typed]" instead of a normal registration notice, and the customer gets "We received your exhibition request" instead of a booking confirmation, so nobody mistakes this for a real listing
- **Admin → Registrations page** now shows a red "Not Found" badge next to these entries, followed by the exhibition name/date the visitor typed — so you can tell at a glance which registrations are for real listings vs. requests for exhibitions you don't have yet

### Database change
Added a `requested_exhibition` column to the registrations table for this free-text entry. **Important:** added an automatic schema-upgrade check that runs on every plugin load (comparing a stored version number against the current one) — this means the new column gets created automatically the next time the site loads this version, **without needing to deactivate and reactivate the plugin**. This will also make any future schema changes apply automatically going forward.

---

## v9.15.0 — 2026-07-03
**Major release — email diagnostics, a real bug fix, and a Send Test Email button**

### Most likely explanation for "registration saves but no emails arrive"
Registration saving is a database write (always works). Sending email depends entirely on the server's mail transport, which is a completely separate system. **If you're running this on local XAMPP without an SMTP plugin, this is almost certainly the cause** — PHP's built-in mail sending has no real mail server to talk to on a local machine, so it fails silently for every outgoing email on the whole site, not just this plugin. The fix is the same for any WordPress site on localhost: install **WP Mail SMTP** (free) and connect it to a real account (a free Gmail account works fine), which routes outgoing mail through an actual mail server instead of relying on PHP's native — and typically nonfunctional on localhost — mail() function.

### A real bug fixed either way
Both email bodies were using `esc_html()` on plain-text content. `esc_html()` converts characters like `&` and `'` into HTML entities (`&amp;`, `&#039;`) — correct for HTML output, but wrong for a plain-text email, where it would literally corrupt exhibition/venue names containing those characters (e.g. "Fashion & Retail" would render as "Fashion &amp; Retail" in the email body). Removed the incorrect escaping.

### New: you can now actually see why an email failed
Previously, `wp_mail()` failures were completely invisible — it just silently returns false with no error anywhere. Added:
- A `wp_mail_failed` hook that captures the real PHPMailer error message (bad SMTP credentials, connection refused, invalid recipient, etc.) to a new option
- An error box on the Settings page showing that captured message directly — copy-pasteable for diagnosis
- A **"Send Test Email"** button next to the admin email fields — sends one test message immediately and reports success or the exact failure reason, without needing to submit a full registration to test

---

## v9.14.0 — 2026-07-03
**Small release — renamed accordion column headers**

### What changed
Renamed the three column headers on the "All Exhibitions by Category" accordion table:
- "Name" → **"Exhibitions Category"**
- "Number" → **"Number of Exhibitions"**
- "Register" → **"Registered Person"**

Widened the two stat columns slightly (160px → 190px) to comfortably fit the longer labels. The Registrations page and exhibition detail page still correctly say "Name" for registrant names — those are unrelated columns and were left untouched.

---

## v9.13.0 — 2026-07-03
**Major simplification — rebuilt to match the sketch: one simple 3-column table (Name | Number | Register)**

### What changed
Scrapped the colspan-alignment approach from v9.9–9.12 entirely in favour of a much simpler, sketch-matching structure:

- **One table** with three real column headers: **Name | Number | Register**
- **Each category is one genuine `<tr>`** in that table — name on the left, exhibition count centred under "Number", registered count centred under "Register"
- **Clicking a row reveals a second `<tr>` directly below it** (spanning all 3 columns), containing that category's full exhibition table — with its own headers (Exhibition/Date/City/Cycle/Registrations/Action), shown only while expanded

This is plain, valid table markup throughout — no colspan tricks, no matching `<colgroup>`s between separate tables, nothing to keep in sync. It reads correctly as a real table with real columns in both the closed and open states, matching the sketch exactly.

---

## v9.12.0 — 2026-07-03
**Small release — clearer left/right split: name gets more room on the left, both counts grouped tightly at the far right**

### What changed
Adjusted the column mapping in the closed category header row:
- **Category name** now spans **4 of 6 columns** (colspan=4, covering Exhibition/Date/City/Cycle) — giving it noticeably more room on the left rather than being squeezed into 3 columns
- **Exhibition count** and **registered count** are now the final two cells, occupying the Registrations (110px) + Action (130px) columns — roughly two columns' worth of width at the very right edge of the page, with no empty trailing cell between them and the edge
- This creates a clear visual gap between the name (left) and the two stats (grouped together, right), rather than the stats sitting in the middle of the row

Still uses the same shared `<colgroup>` as the exhibition table below, so this stays perfectly aligned once a category is opened.

---

## v9.11.0 — 2026-07-03
**Small release — header columns now precisely aligned with the exhibition table below via a shared colgroup**

### What changed
Each category's header row now uses the exact column mapping requested:

| Cell | Content | Spans |
|---|---|---|
| 1 | ▶ Category name | 3 columns (under Exhibition/Date/City) |
| 2 | 🗂 Exhibition count | 1 column (under Cycle) |
| 3 | 👥 Registration count | 1 column (under Registrations) |
| 4 | *(empty)* | 1 column (under Action) |

Both the header's mini-table and the exhibition table (shown once opened) now share an **identical `<colgroup>`** defining the same 6 column widths. This is what makes the alignment precise rather than approximate — both tables compute their column widths from the same source, so the name/count cells in the closed header land exactly above the columns they represent once you open that category.

This keeps v9.10's fix (each category is still its own independent `<div>` with its own table — not one shared table for the whole page) while adding the real column structure you asked for, so a closed category reads as organised columns rather than loose text sitting next to other text.

---

## v9.10.0 — 2026-07-03
**Small release — reverted to per-category divs (v9.7.0 structure), kept v9.9's separate-td-cells improvement**

### Why
v9.8/v9.9 wrapped the entire accordion (all 15 categories) in ONE giant `<table>` with a single shared `<thead>` at the top of the page. That header row's column labels ("Exhibition / Date / City / Cycle / Registrations / Action") describe the exhibition rows once a category is opened — but they don't describe the category *summary* rows (name / exhibition count / registered count) sitting above them. Having one mismatched header for the whole page looked wrong, as flagged.

### What changed
Went back to v9.7.0's structure — each category is its own self-contained `<div class="rhg-cat-block">`, not part of one page-spanning table — while keeping v9.9's genuine improvement:
- Each category's header is now its **own tiny one-row table**, with real `<td>` cells for name / exhibition count / registered count (no custom flexbox layout needed for basic readability)
- Each category's exhibition list, once opened, is its **own independent table** with its **own column headers** — exactly matching the data inside it, not a shared header from elsewhere on the page
- `.rhg-cat-block` is a real `<div>` again, so its border/border-radius/background render reliably (these don't work consistently on `<tbody>`, which is why v9.8/9.9 had to drop them)
- Toggle animation restored to smooth `slideUp()`/`slideDown()` — that only misbehaves on `<tbody>` elements, and the body is a plain `<div>` again now

---

## v9.9.0 — 2026-07-03
**Small release — category header row now uses separate td cells per data point instead of one merged cell**

### What changed
The category header row previously used a single `<td colspan="6">` containing a `<div style="display:flex">` to lay out the name and stats side by side. That inner flexbox layout is custom CSS — if `admin.css` ever failed to load again, the name and stats would have no layout at all and could render stacked/overlapping.

**Rebuilt using genuinely separate `<td>` cells**, mapped onto the same 6-column grid the exhibition rows below use:
- `colspan="3"` — category name + toggle icon (spans where "Exhibition / Date / City" would be)
- 1 cell — exhibition count (aligned under "Cycle")
- 1 cell — registration count (aligned under "Registrations")
- 1 empty cell (aligned under "Action", keeping the column count consistent)

Each piece of information now has its own table cell with native cell padding/alignment, rather than depending on a custom flexbox wrapper to position things correctly. The colour/hover/open-state styling in `admin.css` is still there as polish on top, but the row is now legible even without it.

---

## v9.8.0 — 2026-07-03
**Major release — accordion rebuilt as one continuous table (tbody/tr/td), per user's suggested fix**

### The insight
You correctly spotted the pattern: when the dropdown is **open**, the exhibition list uses real `<table><tbody><tr><td>` markup — which automatically inherits WordPress's own built-in `.wp-list-table` borders and striping (loaded on every admin page by default, regardless of our plugin). When **closed**, the category header was just plain `<div>` tags with no such fallback — so it depended entirely on our own `admin.css` loading correctly, which (per v9.7.0) had been failing.

### What changed
Rebuilt the whole accordion as **one continuous `<table>`**, so both states get the same free WordPress styling as a baseline, with our own CSS layered on top rather than being the only thing holding it together:
- Each category is now two `<tbody>` elements back to back: a **header tbody** (always visible, containing one `<tr>` with a single `<td colspan="6">` for the clickable category summary) followed by a **body tbody** (hidden until clicked, containing that category's exhibition `<tr>` rows — unchanged from before)
- The whole thing lives inside one `<table class="wp-list-table widefat fixed striped">`, so even in a worst-case scenario where our CSS fails to load again, WordPress's own table borders and row striping still apply to the category headers automatically
- Updated `admin.css` to match: flex layout moved from the (now `<tr>`) header onto an inner wrapper `<div>` inside the `<td>` — a `<tr>` cannot itself be `display:flex` without breaking table layout
- Toggle animation switched from `slideUp()`/`slideDown()` to plain `show()`/`hide()` — jQuery's slide animation manipulates height directly, which doesn't behave reliably on `<tbody>` elements across browsers; `show()`/`hide()` correctly restores `display:table-row-group`

No visible functional changes — same stats, same "closed by default" behaviour, same columns — but the page should now look reasonable even in the failure case that caused v9.7.0's bug in the first place.

---

## v9.7.0 — 2026-07-03
**Small release — found the real cause: admin.css was never loading on the Exhibitions/Settings pages**

### Root cause
`enqueue_admin_assets()` only loads `admin.css` when the current page's WordPress "hook name" matches a hard-coded list. That list had the wrong hook names — `exhibitions_page_rhg-exhibitions-list` and `exhibitions_page_rhg-exhibitions-settings` — missing the `rhg-` prefix.

WordPress builds a submenu page's hook name from its **parent menu's actual slug** as the prefix. Since the top-level menu here is registered with the slug `rhg-exhibitions`, the real hook names are `rhg-exhibitions_page_rhg-exhibitions-list` and `rhg-exhibitions_page_rhg-exhibitions-settings`. Because the hard-coded list didn't match, the `in_array()` check silently failed and `wp_enqueue_style()` never ran on those two pages — **`admin.css` has never actually loaded on the Exhibitions or Settings pages**, only on the top-level Registrations page (where the hook happened to coincidentally match).

### Why it looked the way it did in your screenshots
- **Closed accordion (ugly):** the category boxes' borders, backgrounds, padding, and hover states are all defined in `admin.css` — none of it ever arrived, so it rendered as plain unstyled text
- **Open accordion (looked fine):** the exhibition table inside uses WordPress's own built-in `wp-list-table widefat fixed striped` classes, which are core WordPress styles loaded on every admin page automatically, regardless of whether our own plugin stylesheet loads — so it looked properly styled by coincidence, while the category header/box around it did not

### Fix
Corrected the hook names in the `$admin_pages` allow-list to match WordPress's actual naming convention. `admin.css` will now load correctly on all three HimoEXPO admin pages (Registrations, Exhibitions, Settings), including the category box borders, backgrounds, and hover states that were missing.

---

## v9.6.0 — 2026-07-03
**Small release — installed your own edited style.css**

### What changed
Replaced `assets/css/style.css` with the version you uploaded and edited yourself. Verified before installing:
- Brace balance is correct (133/133) — no syntax errors
- Every class name it targets (`.rhg-cat-toggle-btn`, `#rhg-cat-toggle-count`, `.rhg-exh-categories`, `.rhg-cat-pill`, etc.) matches what the plugin's PHP and JavaScript already output — fully compatible, no markup changes needed on top of this

This includes your category-dropdown mechanism used consistently across desktop, tablet, and phone, and your `padding:15px 12px; line-height:2px;` fix on the category pills.

One minor note (informational only, not changed): the `.rhg-days-left` / `.rhg-days-today` / `.rhg-days-past` rules appear twice in the file (identical both times) — harmless, CSS just treats the second as redundant, but flagging it in case you want to tidy it up later.

---

## v9.5.0 — 2026-07-03
**Small release — more reliable accordion on the Exhibitions admin page**

### What changed
The "All Exhibitions by Category" admin page (Exhibitions menu) uses a click-to-expand accordion per category. It was built with plain vanilla JavaScript (`addEventListener` bound directly to each header at page-load time), which is more fragile inside wp-admin — other plugins on the site (Elementor, Phlox Pro, Depicter, etc. all loading their own scripts) can affect timing or interfere with direct event bindings, making the expand/collapse feel unreliable or "stuck."

**Rebuilt using jQuery** (which WordPress always loads in wp-admin by default) with:
- **Event delegation** on the accordion's parent container, instead of binding to each header individually — this keeps working even if anything else on the page re-renders content, rather than silently failing to attach
- **Smooth slide animation** (`slideDown`/`slideUp`, 180ms) instead of an instant `display:none`/`block` toggle — feels like an actual dropdown opening rather than a flicker

No visual/layout changes — categories are still closed by default, same stats, same table columns. This only replaces the underlying interaction code with the more robust standard.

---

## v9.4.0 — 2026-07-02
**Small release — select text disappeared again (screenshot confirmed) + category button native-appearance fix**

### "Show 10/20/40/50" text was still missing (per screenshot)
The `height + line-height` combination has now failed twice in the live environment despite testing fine in isolation. Rather than trying a fourth variant of that technique, **both the search box and select box now use only symmetric padding with no fixed height at all** — the same simple method confirmed to render visible text previously. Both fields use matching padding values so they land at the same natural height without forcing anything.

### Category dropdown text sitting low / overlapping the row below (per screenshot)
The category buttons are native `<button>` elements, which carry browser/OS-default padding and appearance that can conflict with custom CSS padding and push text out of its intended position. Added `appearance: none` (safe here — these are plain flat buttons with no native combobox behaviour attached, unlike `<select>`), plus explicit `margin:0`, `font-family:inherit`, and a **fixed pixel line-height** (`20px`, not a ratio) to remove any ambiguity in how the browser computes line height.

### Also fixed
- Removed a negative top margin on the dropdown panel (`-2px`) that was used to tuck it close to its toggle button — replaced with safe positive spacing, since negative margins on bordered boxes can behave inconsistently across browsers/themes
- Increased container padding slightly for more breathing room around the first/last items

**Note:** the select box text-visibility issue has now recurred across two different fix attempts in the live environment despite passing my own testing. If it persists after this version, the most useful next step would be a link to the live page (or a screenshot with browser dev tools open on that element) so the actual computed CSS can be inspected directly, rather than continuing to guess blind.

---

## v9.3.0 — 2026-07-02
**Small release — universal category dropdown, fixed text overlap, matched select/search height**

### Category dropdown now used on desktop and tablet too, not just phone
Removed the media-query scoping that limited the "Categories ▾" dropdown to mobile only. The inline pill-row layout has been retired entirely — **every screen size now uses the same collapsible dropdown mechanism**, exactly as requested. On wide screens the dropdown panel caps at `max-width:420px` so it doesn't stretch awkwardly across a full desktop row.

### Fixed: mobile dropdown text overlap on long category names
Longer names (e.g. "Manufacturing & Engineering") were wrapping to two lines without enough line-height or gap between items, causing the wrapped second line to visually overlap the pill below it. Fixed with `line-height:1.4`, explicit `white-space:normal`, and a slightly larger gap (4px) between list items.

### Fixed: search box and per-page select height mismatch
Reverted to the same safe technique used for the select earlier, but calibrated correctly this time: explicit `height:46px` (exactly matching the search box) with `line-height:44px` to centre the text with equal spacing above and below — no `appearance:none`, no custom background image, nothing that risks breaking native rendering. Both fields now sit at identical height, symmetrically padded.

---

## v9.2.0 — 2026-07-02
**Small release — fixed invisible select text (my own v9.1 regression) + stronger mobile category contrast + tighter dropdown position**

### Bug: "Show 10/20/40/50" text disappeared entirely
This was a regression I introduced in v9.1 while trying to fix the vertical-centering issue — combining `appearance: none` with a hand-written data-URI SVG background arrow is fragile and doesn't render consistently across browsers/environments. **Reverted to the simple, proven approach:** no fixed height, no `appearance` override, just symmetric `padding: 13px 14px`, which naturally centres the text and is far more reliable across browsers. Removed the custom arrow entirely — the browser's native dropdown arrow is back.

### Mobile category dropdown — selected state now unmistakable
Went further than a subtle tint: selected categories on mobile now show a **solid red background with white text** (identical styling to the desktop pill's active state) plus a **✓ checkmark**, so there's no ambiguity about which categories are selected, even at a glance.

### Positioning tightened
Reduced the gap between the "Categories ▾" toggle button and the dropdown panel beneath it (moved up a few pixels, per your request) — the panel now sits closer/more attached to its toggle button.

---

## v9.1.0 — 2026-07-02
**Small release — fixed Show 10/20/40/50 text position + mobile category selection contrast bug**

### Bug 1: "Show 10/20/40/50" text pushed down / not visible correctly (desktop + mobile)
The per-page `<select>` had a fixed `height:46px` with `padding:0 14px` but no `appearance:none` or `line-height` normalization — native browser select rendering doesn't respect custom height/padding consistently across browsers without this, causing the text to sit off-center or get clipped. Fixed by:
- Adding `appearance:none` (removes inconsistent native OS styling)
- Setting `line-height:44px` to reliably vertical-centre the text
- Adding a custom SVG chevron arrow (since `appearance:none` also removes the native dropdown arrow)

### Bug 2: mobile category dropdown — selected items looked the same as unselected after tapping
Root cause: the `:hover` and `.active` CSS rules had **equal specificity**, and `:hover` was declared *after* `.active` in the mobile stylesheet block — so it won. On phones, tapping a pill triggers a "stuck" hover state (touchscreens have no real mouse-leave event to clear it), which was overriding the red "selected" highlight with the grey "hover" style, making tapped categories look unselected even when they were active.

Fixed with `:hover:not(.active)` so hover styling can never apply to an already-selected pill, regardless of source order or touch-hover quirks. Selected categories now reliably show a light-red background + bold red text on mobile, matching the visual language used elsewhere in the plugin.

---

## v9.0.0 — 2026-07-02
**Major release — full responsive overhaul (phone, tablet, desktop)**

### Root cause of the oversized mobile search box
The search input used `flex: 1 1 280px` for its desktop width. `flex-basis` always applies along the **main axis** — on desktop that's horizontal (row), so `280px` set the width. But `.rhg-exh-controls` switches to `flex-direction: column` on mobile, which flips the main axis to vertical — so that same `280px` was being applied as **height** instead, making the search box enormous. Fixed by explicitly resetting the field to `width:100%; height:46px;` inside the mobile breakpoint instead of letting the desktop rule bleed through.

### Category filter — mobile dropdown (same mechanism as desktop)
- Added a "Categories ▾" toggle button, shown only on phones (≤600px)
- Tapping it reveals the exact same category pill list used on desktop, just re-styled as a vertical scrollable dropdown instead of a wrapped inline row
- Every pill still uses the identical click handler and multi-select logic as desktop — selecting/deselecting categories works exactly the same way everywhere, only the *layout* changes
- Toggle button shows a live count of selected categories, e.g. "Categories (3)"
- Dropdown closes automatically when tapping outside it

### New responsive breakpoints (used consistently throughout)
- **Desktop** (>1024px): 4-column grid, inline category pills, search + per-page side by side
- **Tablet** (601–1024px): 3-column grid (2-column below 768px), tightened spacing
- **Phone** (≤600px): 1–2 column grid, stacked full-width search/select fields, category dropdown, larger touch targets (40px pagination buttons, bigger combo-dropdown rows), reduced modal/card padding
- **Extra-small phones** (≤360px): further-reduced card image height for very narrow screens

### Fixed: hover-to-reveal cards were unreachable on touch devices
The card's description/Register button only appeared on `:hover` — but touchscreens have no real hover state, meaning phone and tablet users could never see or reach that content. Added JavaScript hover-capability detection (`matchMedia('(hover: hover)')`); on touch devices, **tapping a card now toggles its overlay open** (accordion-style — opening one closes any other), while desktop mouse behaviour is unchanged. Tapping outside a card, or tapping the Register button itself, closes/opens correctly without conflicting.

### Other responsive fixes
- All form inputs, modal, and success popup verified to scale fluidly down to 320px-wide screens (percentage-based widths, no fixed-pixel overflow)
- Combo dropdown (Country/Town/Job Title) touch targets enlarged on phone
- General box-sizing safety net added (`* { box-sizing: border-box }` scoped to the plugin wrapper) to prevent any width-overflow surprises from padding/border math

---

## v8.3.0 — 2026-07-02
**Small release — hybrid name-cleaning: precise description-match + capitalisation fallback**

### What changed
Combined both approaches into one robust two-step method:
1. **Precise match (primary):** since the description field is already scraped correctly and separately, look for that exact text inside the title and cut the title off right there — no guessing when we already know the answer.
2. **Capitalisation heuristic (fallback):** used only when there's no description to compare against, or it doesn't appear in the title. Detects the boundary between an ALL-CAPS name and following sentence-case text, handling both glued-with-no-space and normal space-separated cases.

Verified against 7 real-world scenarios including the exact reported case:
```
IN:   MANCHESTER FURNITURE SHOWJanuary Furniture Show is devoted to
      contract buyers, interior designers, specifierr or procurement
      officers. From mainstream volume to high-end premium design
NAME: MANCHESTER FURNITURE SHOW
```
All 7 test cases pass, including titles with no contamination (left untouched), glued acronym boundaries, and year-glued-to-text boundaries.

- `split_title_and_explanation()` now accepts the description as a second parameter — the call site in `render_card()` was updated to actually pass it through (this was missing in v8.2.0, meaning the precise-match step could never run — likely contributing to the earlier issue)
- Verified brace/parenthesis balance across all 9 PHP files before packaging this time, plus a full method review, given the previous version had a reported problem

---

## v8.2.0 — 2026-07-02
**Small release — fixed exhibition names contaminated with leaked description text**

### The bug
EventsEye's HTML sometimes has no space between an exhibition's name and its tagline, so the scraper's title field ended up containing both glued together — e.g. `"TRUCKFEST WEST MIDLANDSFair dedicated to Motorsports"` instead of just `"TRUCKFEST WEST MIDLANDS"`. This showed up on cards and in the hover panel as garbled, run-on text.

### The fix
Added `RHG_Shortcode::split_title_and_explanation()` — detects the boundary between the exhibition NAME (a leading run of ALL-CAPS words) and any leaked explanation text using capitalisation as the signal:
- Handles glued boundaries with no space at all: `"MIDLANDSFair"` → `"MIDLANDS"` + `"Fair"`
- Handles glued year-to-text boundaries: `"2026Business"` → `"2026"` + `"Business"`
- Handles normal space-separated boundaries too: the first word that isn't fully uppercase marks where the explanation starts

**Result:**
- Card title and hover-panel heading now show only the clean, correct exhibition name
- Any leaked explanation text is no longer discarded — it's folded into the hover panel's description text instead (deduplicated against the existing description so nothing shows twice)
- This is a **display-layer fix** — no rescrape required, no database changes. Takes effect immediately for all 325 existing exhibitions

---

## v8.1.0 — 2026-07-02
**Small release — renamed app to HimoEXPO, added copyright footer, fixed pagination alignment**

### What changed
- **App renamed to HimoEXPO** — the WordPress admin sidebar menu now reads "HimoEXPO" instead of "Exhibitions". Plugin header (Plugins list) and the Settings page heading updated to match. Author credited as "HimoEXPO (h.maghsoudloo)"
- **Copyright footer added to every admin page** (Registrations, Exhibitions, Exhibition Detail, Settings): "© 2026 HimoEXPO(h.maghsoudloo). All rights reserved." — shown at the bottom of each page, year is dynamic
- **Same copyright text added to the front-end** — appears just below "Listings last updated" at the bottom of the exhibition listing page
- **Fixed pagination button text alignment:** page numbers weren't perfectly centred inside the circular buttons due to missing flex centring — added `display:flex; align-items:center; justify-content:center` so digits sit dead-centre regardless of browser font metrics
- **Fixed pagination spacing imbalance:** the gap above the pagination row was 34px while the gap below was only 16px. Both are now an equal 28px, so pagination sits visually centred between the grid and the "last updated" text

---

## v8.0.0 — 2026-07-02
**Major release — per-category images with admin upload mechanism**

### Important honesty note
No image-generation tool is available in this build environment, so the 20 default images below are **original flat-icon graphics I hand-designed in your navy/gold brand colours** — not AI-generated character illustrations like the franchise reference image you shared. The upload mechanism built in this release lets you replace any or all of them with real illustrations (including that franchise image) whenever you're ready.

### What changed
- **20 bundled default category icons** (`assets/img/categories/*.svg`) — one per category, navy background with a gold icon glyph representing the category (car for Automotive, plane for Aerospace, building skyline for Construction, lightning bolt for Energy, chip for Technology, fork & knife for Food & Drink, medical cross for Healthcare, hanger for Fashion, coin for Finance, megaphone for Marketing, gear for Manufacturing, delivery box/truck for Logistics, house for Real Estate, graduation cap for Education, flask for Science, leaf for Agriculture, ball for Sports, printer for Printing, storefront for Franchise & Business, folder for Other)
- **Settings → Category Management now has a full image mechanism per row:**
  - Thumbnail preview of the current image (custom or default)
  - File upload input (JPG, PNG, WEBP, or SVG accepted) with instant live preview before saving
  - "Reset" checkbox to revert a category back to its default icon
  - Uploaded images are stored properly via WordPress's native media upload handler (`wp_handle_upload`)
- **Fixed a real pre-existing bug:** the Category Management table was rendered *outside* the `<form>` tag, meaning category name/keyword edits never actually saved when clicking "Save Settings". This is now fixed — everything (admin emails + category management) lives inside one unified form with `enctype="multipart/form-data"` (required for file uploads)
- **Cards now display category images automatically:** any exhibition without its own scraped photo now falls back to its category's image (custom-uploaded or default icon) instead of a generic placeholder
- `RHG_Categories::get_image( $category_name )` — new method resolving custom image → default icon → generic fallback, in that priority order
- Category data structure now includes an `image` field per entry (backward-compatible — old installs without this field simply fall back to default icons, no data loss or errors)

---

## v7.0.0 — 2026-07-02
**Major release — custom dropdown widget (replaces native datalist), branded success popup, fixed field alignment**

### What changed
- **Fixed left/right field imbalance:** the modal box's `overflow-y: auto` scrollbar was eating into the right-side padding only, making fields appear off-centre. Added `scrollbar-gutter: stable both-edges` so the browser reserves equal gutter space on both sides regardless of whether a scrollbar is showing — fields are now visually centred
- **Replaced `<datalist>` entirely with a custom-built dropdown widget** for Job Title, Country, and Town. This was the root cause of the "black dropdown with browser-saved data" — native `<datalist>` in Edge/Chrome mixes in browser autofill history alongside our own suggestions, and its styling isn't controllable. The new widget:
  - Shows **only** our own data (90+ countries, their cities, 40+ job titles) — zero browser autofill interference
  - Has a small ▾ arrow icon on the right of each field (click to open/close), matching the pattern used on most websites
  - Filters live as you type (case-insensitive substring match)
  - Fully custom-styled to match the site (white dropdown panel, red highlight on hover)
  - Country selection still cascades to refresh Town's suggestions
  - Still 100% free-text friendly — anything typed that isn't in the list is accepted
- **New branded success popup:** submitting the form no longer just shows a small inline message — it now opens a dedicated navy popup with a gold checkmark icon, "Thank You!" heading, the confirmation message, and a gold "Done" button. Replaces the previous auto-closing inline message for a clearer, more professional confirmation moment
- Registration modal automatically closes the instant the success popup opens

---

## v6.0.0 — 2026-07-02
**Major release — searchable cascading Country/Town fields, uniform field sizing, searchable job titles**

### What changed
- **Country field is now searchable:** covers 90+ countries across Europe, North America, Middle East, Asia, Oceania, Africa, and South America. Customer can type any letter and matching countries appear in a native browser dropdown, or pick from the full list
- **Town field cascades from Country:** selecting or typing a recognised country automatically repopulates the Town field's suggestions with that country's major cities (e.g. choosing "France" offers Paris, Lyon, Marseille, Toulouse, Nice, Bordeaux, Lille). Town remains free-text, so any city can still be typed even if not suggested
- **Job Title field is now searchable too:** 40+ common exhibition-relevant titles (Sales Manager, Procurement Manager, CEO / Managing Director, Buyer, Export Manager, etc.) appear as suggestions while typing, but any custom title can still be entered — nothing is locked to the list
- **All 5 form fields are now the exact same size:** Country and Town were previously native `<select>` dropdowns which render with different default height/padding than `<input>` fields in most browsers. All fields are now `<input>` elements with an explicit `height: 44px` and identical padding/border/font — guaranteed pixel-uniform regardless of browser
- Implementation uses native HTML5 `<datalist>` — no extra JavaScript libraries needed, fully accessible, works with keyboard navigation
- Country→city data is defined once in PHP (`RHG_Shortcode::country_city_map()`) and passed to JavaScript via `wp_localize_script`, so there's a single source of truth
- Server-side validation unchanged — still simply requires non-empty values, so this works seamlessly with the new free-text-friendly fields

---

## v5.9.0 — 2026-07-02
**Small release — fixed root cause of white popup + overlay spacing/button position**

### What changed
- **Root cause found and fixed:** the registration modal is rendered as a *sibling* of `.rhg-exh-wrapper` in the DOM (not nested inside it). CSS custom properties (`--rhg-navy`, `--rhg-gold`, etc.) only cascade to descendant elements, not siblings — so every `var(--rhg-navy)` inside the modal's CSS was silently failing and falling back to no color (white). This is why earlier attempts to "make it navy" didn't visually take effect even though the CSS looked correct.
  **Fix:** replaced all `var(--rhg-navy)`, `var(--rhg-red)`, `var(--rhg-gold)` references inside the modal-specific CSS with hardcoded hex values (`#0b1f4d`, `#e63950`, `#c9a227`). The modal is now reliably navy regardless of where it sits in the DOM.
- **Card hover overlay — spacing:** added margin between the exhibition name and the date line (title now has `margin-bottom: 6px` instead of `0`)
- **Card hover overlay — button position:** restructured into two zones — a scrollable content area (name, date, location, cycle, description) and a **fixed footer pinned to the bottom-left** containing the Register button. The button no longer scrolls away with the description; it's always visible in the bottom-left corner of the hover card, exactly where requested.

---

## v5.8.0 — 2026-07-02
**Small release — modal overlay transparent white, submit button white text + red hover**

### What changed
- **Page behind modal:** overlay changed from dark navy `rgba(11,31,77,.7)` to **semi-transparent white** `rgba(255,255,255,.55)` with a 3px backdrop blur — the page fades softly behind the navy popup rather than going dark
- **Submit button:** background stays navy, text is now **white**, border is a subtle white outline. On hover → background turns **red**, keeping white text
- No other changes to modal structure or card behaviour

---

## v5.7.0 — 2026-07-02
**Small release — card shows all 5 fields; modal fully navy with white text**

### What changed
- **Card body now shows all 5 fields at rest:** short name (bold navy), 📅 date, days-to-go badge, 📍 location, 🔁 cycle — all visible without hovering. Hover overlay still shows full description + register button (red, scrollable)
- **Registration popup — full navy blue:** entire modal box background is `#0B1F4D` (navy). Applies to both header section and form section
- **All text in modal is white:** exhibition name, date, location (header), and all form labels (`Full Name`, `Email`, `Job Title`, `Country`, `Town`) are white — clearly readable on navy
- **Form inputs keep white background** so customers can see what they're typing
- **Submit button changed to gold** (`#C9A227`) with navy text — better contrast on navy background than red-on-navy; hover turns white
- **Success/error messages** adapted for navy background (green-tinted and red-tinted rgba instead of solid light colours)
- **Close button** is white with gold hover
- **Gold divider** between header and form (was red — gold reads better on navy)
- Logo gets a subtle gold drop-shadow so the crest pops against the navy background

---

## v5.6.0 — 2026-07-02
**Small release — taller cards, name-only face, scrollable overlay, modal redesign**

### What changed
- **Card height doubled again:** `min-height: 520px`, image band `height: 280px` — much taller cards now
- **Card face shows only short name:** date, days-to-go, location, and cycle removed from card body. Card face = image + centred bold name only. Clean and simple
- **Hover overlay is now fully scrollable:** `overflow-y: auto` on overlay inner div with thin white custom scrollbar — customer can scroll down to read the full description without it being cut off
- **Modal header completely rebuilt:**
  - White background (not navy) — all text clearly readable
  - Logo centred, 120px (was 58px — now 2× as requested)
  - Exhibition name: 20px bold dark navy (was white on navy — invisible)
  - Date and location: 16px bold dark navy with emoji icons (bigger as requested)
  - Close button now dark navy so it's visible on white background
  - Red 4px divider strip separates header from form
  - Form labels also bumped to 14px, inputs to 14px for consistency

---

## v5.5.0 — 2026-07-02
**Small release — date always shown, cycle field, double-height cards, honest count, sort by days-to-go**

### What changed
- **Date always visible on card:** exact scraped date (e.g. "14 Mar 2027") always shown regardless of whether days-left can be calculated. If date is null, nothing shown (honest)
- **Days-left now shows "Ended X days ago"** for past expos instead of just "Ended"
- **Sort order fixed:** exhibitions sorted upcoming-first (smallest days-to-go at top); nulls and past expos pushed to the bottom — applies to both front-end grid and admin accordion
- **Cycle field added** to cards (🔁 Annual / Every 2 years etc.) and to admin accordion table column
- **Card height doubled** (`min-height: 360px`, image 160px + body flex-1) so overlay has enough room to show full description without overflow
- **Overlay description** shows up to 6 lines (CSS clamp) — enough for any EventsEye description
- **Settings: honest scrape count** — now shows two numbers: "rows processed from EventsEye" (the inflated count including nav rows/duplicates) AND "unique exhibitions in your database" (the real number). Explains why they differ
- **Admin accordion: cycle column** replaces Venue column (venue was usually blank; cycle is more useful)
- **Admin accordion: days-to-go in date cell** — green "X days to go", red "Today!", grey "Ended"
- DB `get_exhibitions()` ORDER BY changed to: upcoming first (start_date >= today, ASC), then past/null

---

## v5.4.0 — 2026-07-02
**Small release — bug fixes: dates visible, Other category, overlay fit, logo in modal**

### What changed
- **Date + days-left now always visible on card** — fixed CSS that was clipping card body content; card now sizes to its content instead of a fixed height
- **"Other" category now shows in admin** — added a SQL migration in `get_all_exhibitions_grouped_by_category()` that updates existing DB rows from `Business Services` / empty to `Other` the first time the Exhibitions admin page loads (no rescrape needed)
- **Overlay now fits card perfectly** — `overflow:visible` on card + `inset:0` on overlay means the red panel exactly covers the card, regardless of how tall the card's description makes it. Description text is `text-align: justify`. Added a white divider line between meta info and description
- **Modal header redesigned** — navy background band at top of modal containing: Royal Hermes logo (58px, white-bg rounded box) + short expo name (bold white) + date (📅) + location (📍). Red 4px divider strip separates header from the form. Close button moved into the header band
- Logo (`logo.png`) is now bundled inside the plugin zip under `assets/img/`
- `data-short` attribute added to cards so modal uses shortened title not the full one

---

## v5.3.0 — 2026-07-02
**Small release — Other category + closed accordion + card hover overlay**

### What changed
- **Category:** renamed catch-all `Business Services` → `Other` (all 119 unmatched exhibitions now land here instead of showing "Business" as a category). Migration runs automatically on plugin load for existing installs
- **Admin accordion:** all category blocks now **start closed** by default — admin clicks to open whichever they want
- **Card redesign:** visible card now shows only short title (max 42 chars), date, **days-left badge** (gold border, bold), and location — no description or register button cluttering the front
- **Days-left badge:** shows "X days to go" in gold for future expos, "Today!" in red if same-day, "Ended" greyed-out for past
- **Hover overlay:** red semi-transparent overlay (92% opacity) fades in on mouse-over showing full exhibition title, date + days-left, location, description (28 words), and the Register button (white with red text). Overlay slides up smoothly (CSS transition)
- Register button moved **inside the overlay only** — cleaner card at rest

---

## v5.2.0 — 2026-07-02
**Small release — Admin accordion + multi-select category filter**

### What changed
- Admin → Exhibitions page completely rebuilt as **accordion by category**
  - One collapsible block per category
  - Header shows: category name / expo count / total registrations (green)
  - Categories with registrations auto-expand and appear first (most → least)
  - Inside each block: table of all expos, registered ones first with red badge; unregistered show "—"
  - Click "View Registrants" on any registered expo → detail page unchanged
- Front-end category pills now support **multi-select** — click multiple pills to filter by several categories at once; "All Categories" clears selection
- Active filter label shows all selected category names comma-separated with "× Clear all"
- DB `get_exhibitions()` now accepts string or array for category filter (IN clause)
- AJAX handler decodes JSON-encoded category array from front end

---

## v5.1.0 — 2026-07-02
**Small release — Category system + 4-column grid**

### What changed
- Added `class-rhg-categories.php` — keyword-based auto-categorisation engine
- 20 default categories shipped (Automotive, Aerospace, Construction, Energy, Tech, Food, Healthcare, Fashion, Finance, Marketing, Manufacturing, Logistics, Real Estate, Education, Science, Agriculture, Sports, Printing, Franchise, Business Services)
- Scraper now calls `RHG_Categories::auto_assign()` instead of hardcoding 'Business'
- Settings page: full Category Management table (add, edit, remove categories + keywords)
- Website: category filter pills above the grid ("All Categories" + one pill per category)
- Active filter label with "× Clear" button
- Grid changed from 3 to **4 columns** (responsive: 3 on tablet, 2 on mobile, 1 on small mobile)
- Default per-page changed from 10 to **20**
- `get_exhibitions()` DB query updated to accept `$category` filter param
- AJAX filter handler passes category through

---

## v5.0.0 — 2026-07-02
**Major release — starting version**

### What's in this version
- Scrapes UK B2B trade shows daily from EventsEye (no robots.txt blocking, polite 1-second delay between pages)
- Custom DB tables: `rhg_exhibitions` + `rhg_registrations`
- Daily WP-Cron job with manual "Run Scrape Now" button
- Front-end shortcode `[rhg_exhibitions]` with:
  - Live search (debounced)
  - Show 10 / 20 / 40 / 50 dropdown
  - Paginated grid of cards (navy/red/gold brand colours)
  - Registration popup modal per exhibition
  - Server-side email validation (@ + valid domain required)
- Emails on registration: customer confirmation + dual admin notifications
- Admin → Registrations page: filterable by keyword, date range (1/3/7/30 days or specific date), city, category
- Admin → Exhibitions page: only shows exhibitions with ≥1 registration; filterable; click-through to registrant list
- Admin → Settings page: 2 admin notification email fields, scraper controls, shortcode reference
- XAMPP SSL fix baked in (`sslverify => false`, real browser User-Agent)
- Detailed scrape error logging with copy-pasteable admin error box
- Versioned CHANGELOG (this file)

---

## How to read the version number

```
v  5  .  0  .  0
   │     │     └─ patch: tiny fixes (typos, CSS tweaks)
   │     └─ minor: small features or bug fixes  (+0.1 per release)
   └─ major: big new features, pages, integrations  (+1.0 per release)
```

Future entries go at the TOP of this file, newest first.

---

*Next version will be v5.1.0 for the next small change, or v6.0.0 for the next big one.*
