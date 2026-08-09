<?php
/**
 * [rhg_exhibitions] shortcode — searchable, paginated 4-column grid
 * with category filter pills. Default: 20 per page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RHG_Shortcode {

	public function __construct() {
		add_shortcode( 'rhg_exhibitions', array( __CLASS__, 'render' ) );
	}

	/**
	 * Full country list with major cities for each — powers the
	 * cascading Country -> Town datalist fields. Selecting/typing a
	 * country repopulates the town datalist with that country's cities.
	 * Countries not listed here still work — the field is free text.
	 */
	public static function country_city_map() {
		return array(
			'United Kingdom' => array( 'London', 'Birmingham', 'Manchester', 'Glasgow', 'Edinburgh', 'Bristol', 'Leeds', 'Liverpool', 'Sheffield', 'Newcastle', 'Belfast', 'Cardiff', 'Coventry', 'Harrogate', 'Aberdeen', 'Southampton', 'Oxford', 'Cambridge' ),
			'Ireland' => array( 'Dublin', 'Cork', 'Galway', 'Limerick', 'Waterford' ),
			'France' => array( 'Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice', 'Bordeaux', 'Lille' ),
			'Germany' => array( 'Berlin', 'Munich', 'Frankfurt', 'Hamburg', 'Cologne', 'Stuttgart', 'Dusseldorf' ),
			'Spain' => array( 'Madrid', 'Barcelona', 'Valencia', 'Seville', 'Bilbao' ),
			'Italy' => array( 'Rome', 'Milan', 'Turin', 'Naples', 'Bologna', 'Florence' ),
			'Netherlands' => array( 'Amsterdam', 'Rotterdam', 'The Hague', 'Utrecht', 'Eindhoven' ),
			'Belgium' => array( 'Brussels', 'Antwerp', 'Ghent', 'Bruges' ),
			'Portugal' => array( 'Lisbon', 'Porto', 'Braga' ),
			'Switzerland' => array( 'Zurich', 'Geneva', 'Basel', 'Bern' ),
			'Austria' => array( 'Vienna', 'Salzburg', 'Graz' ),
			'Sweden' => array( 'Stockholm', 'Gothenburg', 'Malmo' ),
			'Norway' => array( 'Oslo', 'Bergen', 'Trondheim' ),
			'Denmark' => array( 'Copenhagen', 'Aarhus', 'Odense' ),
			'Finland' => array( 'Helsinki', 'Tampere', 'Turku' ),
			'Poland' => array( 'Warsaw', 'Krakow', 'Wroclaw', 'Gdansk' ),
			'Czech Republic' => array( 'Prague', 'Brno', 'Ostrava' ),
			'Hungary' => array( 'Budapest', 'Debrecen' ),
			'Romania' => array( 'Bucharest', 'Cluj-Napoca' ),
			'Greece' => array( 'Athens', 'Thessaloniki' ),
			'Turkey' => array( 'Istanbul', 'Ankara', 'Izmir' ),
			'Russia' => array( 'Moscow', 'Saint Petersburg' ),
			'Ukraine' => array( 'Kyiv', 'Lviv', 'Odesa' ),
			'Luxembourg' => array( 'Luxembourg City' ),
			'Iceland' => array( 'Reykjavik' ),
			'Croatia' => array( 'Zagreb', 'Split' ),
			'Slovakia' => array( 'Bratislava' ),
			'Slovenia' => array( 'Ljubljana' ),
			'Bulgaria' => array( 'Sofia', 'Plovdiv' ),
			'Serbia' => array( 'Belgrade', 'Novi Sad' ),
			'United States' => array( 'New York', 'Los Angeles', 'Chicago', 'Houston', 'Miami', 'San Francisco', 'Boston', 'Atlanta', 'Dallas', 'Seattle' ),
			'Canada' => array( 'Toronto', 'Vancouver', 'Montreal', 'Calgary', 'Ottawa' ),
			'Mexico' => array( 'Mexico City', 'Guadalajara', 'Monterrey' ),
			'United Arab Emirates' => array( 'Dubai', 'Abu Dhabi', 'Sharjah' ),
			'Saudi Arabia' => array( 'Riyadh', 'Jeddah', 'Dammam' ),
			'Qatar' => array( 'Doha' ),
			'Kuwait' => array( 'Kuwait City' ),
			'Bahrain' => array( 'Manama' ),
			'Oman' => array( 'Muscat' ),
			'Israel' => array( 'Tel Aviv', 'Jerusalem', 'Haifa' ),
			'Jordan' => array( 'Amman' ),
			'Lebanon' => array( 'Beirut' ),
			'Egypt' => array( 'Cairo', 'Alexandria', 'Giza' ),
			'China' => array( 'Beijing', 'Shanghai', 'Shenzhen', 'Guangzhou', 'Hong Kong' ),
			'Japan' => array( 'Tokyo', 'Osaka', 'Yokohama', 'Nagoya' ),
			'South Korea' => array( 'Seoul', 'Busan', 'Incheon' ),
			'India' => array( 'Mumbai', 'Delhi', 'Bangalore', 'Chennai', 'Hyderabad', 'Kolkata' ),
			'Singapore' => array( 'Singapore' ),
			'Malaysia' => array( 'Kuala Lumpur', 'Penang', 'Johor Bahru' ),
			'Indonesia' => array( 'Jakarta', 'Surabaya', 'Bandung' ),
			'Thailand' => array( 'Bangkok', 'Chiang Mai', 'Phuket' ),
			'Vietnam' => array( 'Ho Chi Minh City', 'Hanoi', 'Da Nang' ),
			'Philippines' => array( 'Manila', 'Cebu City', 'Davao City' ),
			'Pakistan' => array( 'Karachi', 'Lahore', 'Islamabad' ),
			'Bangladesh' => array( 'Dhaka', 'Chittagong' ),
			'Taiwan' => array( 'Taipei', 'Kaohsiung', 'Taichung' ),
			'Australia' => array( 'Sydney', 'Melbourne', 'Brisbane', 'Perth', 'Adelaide' ),
			'New Zealand' => array( 'Auckland', 'Wellington', 'Christchurch' ),
			'South Africa' => array( 'Johannesburg', 'Cape Town', 'Durban', 'Pretoria' ),
			'Nigeria' => array( 'Lagos', 'Abuja', 'Kano' ),
			'Kenya' => array( 'Nairobi', 'Mombasa' ),
			'Morocco' => array( 'Casablanca', 'Rabat', 'Marrakech' ),
			'Algeria' => array( 'Algiers', 'Oran' ),
			'Tunisia' => array( 'Tunis' ),
			'Ghana' => array( 'Accra', 'Kumasi' ),
			'Ethiopia' => array( 'Addis Ababa' ),
			'Brazil' => array( 'Sao Paulo', 'Rio de Janeiro', 'Brasilia', 'Belo Horizonte' ),
			'Argentina' => array( 'Buenos Aires', 'Cordoba' ),
			'Chile' => array( 'Santiago', 'Valparaiso' ),
			'Colombia' => array( 'Bogota', 'Medellin' ),
			'Peru' => array( 'Lima', 'Arequipa' ),
			'Other' => array(),
		);
	}

	/**
	 * Country names only, for the country datalist.
	 */
	public static function countries() {
		return array_keys( self::country_city_map() );
	}

	/**
	 * Common exhibition-relevant job titles for the datalist.
	 * The field still accepts any free-typed text not on this list.
	 */
	public static function job_titles() {
		return array(
			'CEO / Managing Director', 'Owner / Founder', 'Director', 'General Manager',
			'Sales Manager', 'Sales Director', 'Business Development Manager',
			'Marketing Manager', 'Marketing Director', 'Procurement Manager',
			'Purchasing Manager', 'Buyer', 'Operations Manager', 'Product Manager',
			'Project Manager', 'Regional Manager', 'Export Manager', 'Import Manager',
			'Account Manager', 'Key Account Manager', 'Head of Sales', 'Head of Marketing',
			'Engineer', 'Design Engineer', 'Architect', 'Consultant', 'Analyst',
			'Supply Chain Manager', 'Logistics Manager', 'HR Manager', 'Finance Manager',
			'Chief Financial Officer', 'Chief Operating Officer', 'Chief Technology Officer',
			'Retail Manager', 'Store Manager', 'Distributor', 'Wholesaler', 'Agent',
			'Event Manager', 'Exhibition Manager', 'Trade Show Coordinator',
			'Product Developer', 'Category Manager', 'Merchandiser', 'Other',
		);
	}

	/**
	 * Splits a raw scraped title into a clean exhibition NAME, removing
	 * description text that got glued onto it (a scraper parsing
	 * artifact — EventsEye's HTML sometimes concatenates the exhibition
	 * name directly against its own description with no separating
	 * space, e.g. "MANCHESTER FURNITURE SHOWJanuary Furniture Show is
	 * devoted to contract buyers...").
	 *
	 * Two-step approach:
	 *   1. PRECISE MATCH — the description field was scraped correctly
	 *      and separately, so we already know its exact text. If that
	 *      text (or its opening chunk) appears inside the title, cut
	 *      the title off right there. No guessing needed.
	 *   2. FALLBACK — if there's no description to compare against, or
	 *      it doesn't appear in the title, fall back to a capitalisation
	 *      heuristic: the NAME is the leading run of ALL-CAPS words;
	 *      the boundary is wherever normal sentence-case text begins
	 *      (handles both glued-with-no-space and space-separated cases).
	 *
	 * Returns [ $clean_name, $leaked_explanation ]. $leaked_explanation
	 * is only non-empty when the fallback heuristic finds trailing text
	 * that wasn't already captured by the known $description.
	 */
	public static function split_title_and_explanation( $raw_title, $description = '' ) {
		$raw_title   = trim( (string) $raw_title );
		$description = trim( (string) $description );

		if ( '' === $raw_title ) {
			return array( '', '' );
		}

		// ── Step 1: precise match against the known description ──────
		if ( '' !== $description ) {
			$needle = mb_strlen( $description ) > 25 ? mb_substr( $description, 0, 25 ) : $description;
			$pos    = mb_stripos( $raw_title, $needle );

			if ( false !== $pos && $pos > 0 ) {
				$clean_name = trim( mb_substr( $raw_title, 0, $pos ) );
				if ( '' !== $clean_name ) {
					return array( $clean_name, '' );
				}
			}
		}

		// ── Step 2: fallback capitalisation heuristic ─────────────────
		// Insert a space at any glued ACRONYM|Capitalword boundary, and
		// at any digit|Capitalword boundary (e.g. a year glued directly
		// to text, "2026Business").
		$normalized = preg_replace( '/(?<=[A-Z])(?=[A-Z][a-z])/', ' ', $raw_title );
		$normalized = preg_replace( '/(?<=[0-9])(?=[A-Z][a-z])/', ' ', $normalized );
		$normalized = preg_replace( '/\s+/', ' ', trim( $normalized ) );

		$words      = explode( ' ', $normalized );
		$name_words = array();
		$rest_words = array();
		$in_name    = true;

		foreach ( $words as $word ) {
			if ( $in_name ) {
				$letters_only = preg_replace( '/[^A-Za-z]/', '', $word );
				// A word with no letters at all (pure number/symbol) stays
				// part of the name run without breaking it.
				$is_capsy = ( '' === $letters_only ) || ( $letters_only === mb_strtoupper( $letters_only ) );

				if ( $is_capsy ) {
					$name_words[] = $word;
					continue;
				}
				$in_name = false;
			}
			$rest_words[] = $word;
		}

		$clean_name = trim( implode( ' ', $name_words ) );
		$leaked     = trim( implode( ' ', $rest_words ) );

		// Safety net: if the heuristic produced an empty name (title
		// didn't start with a capital run at all), just use the whole
		// normalised string as the name with no leaked explanation.
		if ( '' === $clean_name ) {
			return array( $normalized, '' );
		}

		// Don't duplicate: if the leaked text is already contained in
		// the known description, discard it here since it'll be shown
		// via $description anyway.
		if ( '' !== $description && false !== stripos( $description, mb_substr( $leaked, 0, 20 ) ) ) {
			$leaked = '';
		}

		return array( $clean_name, $leaked );
	}

	public static function render( $atts ) {
		$categories = RHG_Categories::get_names();
		ob_start();
		?>
		<div class="rhg-exh-wrapper">

			<!-- Can't find your exhibition? — opens the registration
			     popup in "manual entry" mode, with a field for the
			     visitor to type the exhibition name/date themselves. -->
			<div class="rhg-not-found-banner">
				<span class="rhg-not-found-text">Can't find your exhibition on this list?</span>
				<button type="button" id="rhg-not-found-btn" class="rhg-not-found-btn">Register Your Interest</button>
			</div>

			<!-- Search + per-page row -->
			<div class="rhg-exh-controls">
				<input
					type="text"
					id="rhg-exh-search"
					class="rhg-exh-search-input"
					placeholder="Search by exhibition, city, or venue..."
				/>
				<select id="rhg-exh-per-page" class="rhg-exh-select">
					<option value="10">Show 10</option>
					<option value="20" selected>Show 20</option>
					<option value="40">Show 40</option>
					<option value="50">Show 50</option>
				</select>
			</div>

			<!-- Mobile-only: tap to open the category list as a dropdown -->
			<button type="button" id="rhg-cat-toggle" class="rhg-cat-toggle-btn">
				<span>Categories<span id="rhg-cat-toggle-count"></span></span>
				<span class="rhg-cat-toggle-arrow">&#9662;</span>
			</button>

			<!-- Category filter pills — same list + same click mechanism on
			     every screen size; only the CSS layout changes (inline row
			     on desktop, collapsible dropdown list on mobile). -->
			<div class="rhg-exh-categories" id="rhg-exh-categories">
				<button type="button" class="rhg-cat-pill active" data-category="">All Categories</button>
				<?php foreach ( $categories as $cat ) : ?>
					<button type="button" class="rhg-cat-pill" data-category="<?php echo esc_attr( $cat ); ?>">
						<?php echo esc_html( $cat ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<!-- Active filter label -->
			<div id="rhg-exh-active-filter" class="rhg-exh-active-filter" style="display:none;">
				Filtered by: <strong id="rhg-active-cat-name"></strong>
				<button type="button" id="rhg-clear-filter" class="rhg-clear-filter">&times; Clear all</button>
			</div>

			<!-- Grid -->
			<div id="rhg-exh-grid" class="rhg-exh-grid">
				<p class="rhg-exh-loading">Loading exhibitions...</p>
			</div>

			<div id="rhg-exh-pagination" class="rhg-exh-pagination"></div>

			<p class="rhg-exh-updated">
				<?php
				$last_run = get_option( 'rhg_exh_last_run' );
				if ( $last_run ) {
					echo 'Listings last updated: ' . esc_html( date( 'd M Y, H:i', strtotime( $last_run ) ) );
				}
				?>
			</p>

			<p class="rhg-exh-copyright">&copy; <?php echo esc_html( date( 'Y' ) ); ?> HimoEXPO(h.maghsoudloo). All rights reserved.</p>

		</div>

		<?php self::render_modal(); ?>
		<?php
		return ob_get_clean();
	}

	public static function render_card( $exhibition ) {
		$image = ! empty( $exhibition->image_url )
			? esc_url( $exhibition->image_url )
			: esc_url( RHG_Categories::get_image( $exhibition->category ) );

		// Split the raw scraped title into a clean NAME and any leaked
		// explanation text (scraper artifact — see split_title_and_explanation()).
		list( $clean_title, $leaked_explanation ) = self::split_title_and_explanation( $exhibition->title, $exhibition->description );

		// Short title — derived from the CLEAN name only, never the raw
		// (possibly explanation-contaminated) title.
		$short_title = $clean_title;
		foreach ( array( ' - ', ' – ', ' | ' ) as $sep ) {
			if ( strpos( $short_title, $sep ) !== false ) {
				$short_title = trim( explode( $sep, $short_title )[0] );
				break;
			}
		}
		if ( mb_strlen( $short_title ) > 44 ) {
			$short_title = mb_substr( $short_title, 0, 42 ) . '…';
		}

		// Date — always show the exact scraped date if we have it
		$date_display = '';
		$start_ts     = null;
		if ( ! empty( $exhibition->start_date ) ) {
			$start_ts     = strtotime( $exhibition->start_date );
			$date_display = date( 'd M Y', $start_ts );
			if ( ! empty( $exhibition->end_date ) && $exhibition->end_date !== $exhibition->start_date ) {
				$date_display .= ' – ' . date( 'd M Y', strtotime( $exhibition->end_date ) );
			}
		}

		// Days left — only shown when date is available
		$days_left_html = '';
		if ( $start_ts ) {
			$today     = strtotime( date( 'Y-m-d' ) );
			$diff_days = (int) round( ( $start_ts - $today ) / 86400 );
			if ( $diff_days > 0 ) {
				$days_left_html = '<strong class="rhg-days-left">' . number_format( $diff_days ) . ' day' . ( $diff_days !== 1 ? 's' : '' ) . ' to go</strong>';
			} elseif ( $diff_days === 0 ) {
				$days_left_html = '<strong class="rhg-days-left rhg-days-today">Today!</strong>';
			} else {
				$days_left_html = '<span class="rhg-days-past">Ended ' . abs( $diff_days ) . 'd ago</span>';
			}
		}

		// Cycle
		$cycle = ! empty( $exhibition->cycle ) ? $exhibition->cycle : '';

		// Location
		$place = trim( implode( ', ', array_filter( array( $exhibition->venue, $exhibition->city ) ) ) );

		// Full description for overlay — if the scraper's explanation
		// text leaked into the title, fold it back in here (where it
		// belongs) rather than losing it, unless it's already present.
		$description = trim( (string) $exhibition->description );
		if ( '' !== $leaked_explanation && false === stripos( $description, mb_substr( $leaked_explanation, 0, 20 ) ) ) {
			$description = trim( $leaked_explanation . ' ' . $description );
		}
		if ( '' === $description ) {
			$description = 'Click Register to secure your place at this exhibition.';
		}
		?>
		<div class="rhg-exh-card"
			data-id="<?php echo esc_attr( $exhibition->id ); ?>"
			data-title="<?php echo esc_attr( $clean_title ); ?>"
			data-short="<?php echo esc_attr( $short_title ); ?>"
			data-date="<?php echo esc_attr( $date_display ); ?>"
			data-place="<?php echo esc_attr( $place ); ?>"
		>
			<!-- Image band -->
			<div class="rhg-exh-card-image" style="background-image:url('<?php echo $image; ?>');">
				<span class="rhg-exh-card-category"><?php echo esc_html( $exhibition->category ?: 'Exhibition' ); ?></span>
			</div>

			<!-- Card body — name, date, days, location, cycle always visible -->
			<div class="rhg-exh-card-body">
				<h3 class="rhg-exh-card-title"><?php echo esc_html( $short_title ); ?></h3>

				<?php if ( $date_display ) : ?>
				<p class="rhg-exh-card-meta">
					<span class="rhg-exh-icon-date"></span> <?php echo esc_html( $date_display ); ?>
				</p>
				<?php endif; ?>

				<?php if ( $days_left_html ) : ?>
				<p class="rhg-exh-card-meta rhg-meta-days"><?php echo $days_left_html; ?></p>
				<?php endif; ?>

				<?php if ( $place ) : ?>
				<p class="rhg-exh-card-meta">
					<span class="rhg-exh-icon-place"></span> <?php echo esc_html( $place ); ?>
				</p>
				<?php endif; ?>

				<?php if ( $cycle ) : ?>
				<p class="rhg-exh-card-meta">
					<span class="rhg-exh-icon-cycle"></span> <?php echo esc_html( $cycle ); ?>
				</p>
				<?php endif; ?>
			</div>

			<!-- Red hover overlay — scrollable content + fixed bottom-left button -->
			<div class="rhg-exh-card-overlay">
				<div class="rhg-overlay-scroll">
					<h4 class="rhg-overlay-title"><?php echo esc_html( $clean_title ); ?></h4>

					<?php if ( $date_display ) : ?>
					<p class="rhg-overlay-date">
						📅 <?php echo esc_html( $date_display ); ?>
						<?php if ( $days_left_html ) echo '&nbsp;' . $days_left_html; ?>
					</p>
					<?php endif; ?>

					<?php if ( $place ) : ?>
					<p class="rhg-overlay-place">📍 <?php echo esc_html( $place ); ?></p>
					<?php endif; ?>

					<?php if ( $cycle ) : ?>
					<p class="rhg-overlay-cycle">🔁 <?php echo esc_html( $cycle ); ?></p>
					<?php endif; ?>

					<hr class="rhg-overlay-divider" />
					<p class="rhg-overlay-desc"><?php echo esc_html( $description ); ?></p>
				</div>

				<div class="rhg-overlay-footer">
					<button type="button" class="rhg-exh-register-btn">
						Register <span class="rhg-exh-arrow">&rarr;</span>
					</button>
				</div>
			</div>

		</div>
		<?php
	}

	private static function render_modal() {
		$logo_url = RHG_EXH_URL . 'assets/img/logo.png';
		?>
		<div id="rhg-exh-modal" class="rhg-exh-modal" style="display:none;">
			<div class="rhg-exh-modal-overlay"></div>
			<div class="rhg-exh-modal-box">
				<button type="button" class="rhg-exh-modal-close">&times;</button>

				<!-- Modal header: centered logo + expo info in dark navy text -->
				<div class="rhg-modal-header">
					<div class="rhg-modal-logo-wrap">
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="Royal Hermes Group" class="rhg-modal-logo" />
					</div>
					<h3 id="rhg-exh-modal-title" class="rhg-modal-expo-name"></h3>
					<div class="rhg-modal-expo-meta">
						<p class="rhg-modal-meta-row">
							<span class="rhg-modal-icon">📅</span>
							<span id="rhg-modal-date-val" class="rhg-modal-meta-text"></span>
						</p>
						<p class="rhg-modal-meta-row">
							<span class="rhg-modal-icon">📍</span>
							<span id="rhg-modal-place-val" class="rhg-modal-meta-text"></span>
						</p>
					</div>
				</div>

				<div class="rhg-modal-divider"></div>

				<form id="rhg-exh-register-form">
					<input type="hidden" name="exhibition_id" id="rhg-exh-exhibition-id" value="" />

					<!-- Shown only when opened via "Can't find your exhibition?"
					     — visitor types the exhibition name/date themselves
					     since there's no listing to attach the registration to. -->
					<div id="rhg-manual-expo-field" style="display:none;">
						<label>Exhibition Name &amp; Date *</label>
						<input type="text" name="requested_exhibition" id="rhg-requested-exhibition" placeholder="e.g. London Tech Expo, March 2027" autocomplete="off" />
					</div>

					<label>Full Name *</label>
					<input type="text" name="full_name" required autocomplete="off" />

					<label>Email *</label>
					<input type="email" name="email" required placeholder="name@example.com" autocomplete="off" />

					<label>Phone Number <span style="opacity:.6; font-weight:400;">(optional)</span></label>
					<input type="tel" name="phone" placeholder="e.g. +44 7700 900123" autocomplete="off" />

					<label>Job Title *</label>
					<div class="rhg-combo" data-combo="job_title">
						<input type="text" name="job_title" class="rhg-combo-input" required placeholder="Type or select a job title" autocomplete="off" />
						<span class="rhg-combo-arrow">&#9662;</span>
						<ul class="rhg-combo-menu"></ul>
					</div>

					<label>Country *</label>
					<div class="rhg-combo" data-combo="country">
						<input type="text" name="country" id="rhg-country-input" class="rhg-combo-input" required placeholder="Type or select your country" autocomplete="off" />
						<span class="rhg-combo-arrow">&#9662;</span>
						<ul class="rhg-combo-menu"></ul>
					</div>

					<label>Town *</label>
					<div class="rhg-combo" data-combo="town">
						<input type="text" name="town" id="rhg-town-input" class="rhg-combo-input" required placeholder="Select a country first, or type your city" autocomplete="off" />
						<span class="rhg-combo-arrow">&#9662;</span>
						<ul class="rhg-combo-menu"></ul>
					</div>

					<div id="rhg-exh-form-message" class="rhg-exh-form-message"></div>

					<button type="submit" class="rhg-exh-submit-btn">Submit Registration</button>
				</form>
			</div>
		</div>

		<!-- Success popup — shown after a registration is submitted successfully -->
		<div id="rhg-exh-success-modal" class="rhg-exh-modal" style="display:none;">
			<div class="rhg-exh-modal-overlay"></div>
			<div class="rhg-success-box">
				<div class="rhg-success-icon">&#10003;</div>
				<h3 class="rhg-success-title">Thank You!</h3>
				<p class="rhg-success-message" id="rhg-success-message-text">Your registration has been received.</p>
				<button type="button" class="rhg-success-close-btn" id="rhg-success-close">Done</button>
			</div>
		</div>
		<?php
	}
}
