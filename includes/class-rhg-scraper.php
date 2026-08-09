<?php
/**
 * Scrapes UK trade shows from EventsEye and stores them via RHG_DB.
 *
 * EventsEye has no robots.txt restricting this, but we still scrape
 * politely: one request at a time, a real User-Agent, and we only
 * run once per day via cron (see class-rhg-cron.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RHG_Scraper {

	const BASE_URL = 'https://www.eventseye.com/fairs/c1_trade-shows_uk-united-kingdom';

	/**
	 * Run a full scrape across all paginated UK listing pages.
	 * Returns the number of exhibitions saved/updated.
	 */
	public static function run() {
		$saved        = 0;
		$updated      = 0;
		$page         = 0;
		$ended_on_404 = false;

		do {
			$url    = ( 0 === $page ) ? self::BASE_URL . '.html' : self::BASE_URL . '_' . $page . '.html';
			$result = self::fetch( $url );

			if ( false === $result ) {
				$last_status = get_option( 'rhg_exh_last_http_status_raw' );
				if ( 0 < $page && 404 === (int) $last_status && ( $saved + $updated ) > 0 ) {
					$ended_on_404 = true;
				}
				break;
			}

			$rows = self::parse_listing_page( $result );

			if ( empty( $rows ) ) {
				if ( 0 === $page ) {
					update_option( 'rhg_exh_last_error', 'Page fetched successfully but 0 rows were parsed from the HTML. The site structure may have changed.' );
				}
				break;
			}

			foreach ( $rows as $row ) {
				$result_id = RHG_DB::upsert_exhibition( $row );
				// upsert returns positive ID whether insert or update
				if ( $result_id > 0 ) {
					$saved++;
				}
			}

			$page++;
			sleep( 1 );
		} while ( $page < 25 );

		if ( $ended_on_404 ) {
			update_option( 'rhg_exh_last_error', '' );
		}

		// Count actual distinct rows in DB for honest reporting
		global $wpdb;
		$table       = $wpdb->prefix . 'rhg_exhibitions';
		$db_count    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status='published'" );

		update_option( 'rhg_exh_last_run', current_time( 'mysql' ) );
		update_option( 'rhg_exh_last_run_count', $db_count );
		update_option( 'rhg_exh_last_run_processed', $saved );

		return $db_count;
	}

	/**
	 * Fetch a URL with a real User-Agent and reasonable timeout.
	 * On failure, records a human-readable reason in the
	 * 'rhg_exh_last_error' option so the admin Settings page can show it.
	 */
	private static function fetch( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 20,
				'sslverify'  => false, // XAMPP's bundled CA cert list is often outdated/missing, causing SSL handshake failures.
				'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
			)
		);

		if ( is_wp_error( $response ) ) {
			$code_str = $response->get_error_code();
			update_option(
				'rhg_exh_last_error',
				'CONNECTION FAILED for ' . $url . ' | WP_Error code: ' . $code_str . ' | Message: ' . $response->get_error_message() . ' | This usually means XAMPP\'s PHP/cURL cannot reach the internet or resolve SSL — check that your XAMPP machine has internet access and that the "openssl" and "curl" PHP extensions are enabled in php.ini.'
			);
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		update_option( 'rhg_exh_last_http_status_raw', $code );

		if ( 200 !== $code ) {
			update_option(
				'rhg_exh_last_error',
				'HTTP STATUS ' . $code . ' for ' . $url . ' | Body snippet: ' . esc_html( substr( wp_strip_all_tags( (string) $body ), 0, 300 ) )
			);
			return false;
		}

		if ( empty( $body ) ) {
			update_option( 'rhg_exh_last_error', 'EMPTY BODY (HTTP 200 but no content) for ' . $url );
			return false;
		}

		if ( false === stripos( $body, 'eventseye' ) && false === stripos( $body, 'trade show' ) ) {
			update_option(
				'rhg_exh_last_error',
				'UNEXPECTED CONTENT for ' . $url . ' | First 300 chars: ' . esc_html( substr( wp_strip_all_tags( $body ), 0, 300 ) ) . ' | This usually means a firewall/proxy/captcha page was returned instead of the real site.'
			);
			// Still return the body — let the parser try, and report 0-rows separately if it fails.
		} else {
			update_option( 'rhg_exh_last_error', '' );
		}

		update_option( 'rhg_exh_last_http_status', $code );
		update_option( 'rhg_exh_last_body_length', strlen( (string) $body ) );

		return $body;
	}

	/**
	 * Parse one listing page's HTML table rows into structured exhibition data.
	 */
	private static function parse_listing_page( $html ) {
		$results = array();

		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		$dom->loadHTML( $html );
		libxml_clear_errors();

		$xpath = new DOMXPath( $dom );
		// Each exhibition row is a <tr> inside the main listing table.
		$rows = $xpath->query( "//table//tr[td]" );

		foreach ( $rows as $tr ) {
			$link_node = $xpath->query( ".//td[1]//a", $tr )->item( 0 );
			if ( ! $link_node ) {
				continue;
			}

			$title_raw  = trim( $link_node->textContent );
			$source_url = $link_node->getAttribute( 'href' );

			if ( empty( $title_raw ) || empty( $source_url ) ) {
				continue;
			}

			// Description sits in italic/em text within the same cell.
			$desc_node   = $xpath->query( ".//td[1]//i", $tr )->item( 0 );
			$description = $desc_node ? trim( $desc_node->textContent ) : '';

			// Column 2 = cycle (e.g. "once a year", "every 2 years").
			$cycle_node = $xpath->query( ".//td[2]", $tr )->item( 0 );
			$cycle      = $cycle_node ? trim( $cycle_node->textContent ) : '';

			// Column 3 = venue cell, containing a city link and (optionally) a venue link.
			$city_node = $xpath->query( ".//td[3]//a[1]", $tr )->item( 0 );
			$city      = $city_node ? trim( $city_node->textContent ) : '';

			$venue_node = $xpath->query( ".//td[3]//a[2]", $tr )->item( 0 );
			$venue      = $venue_node ? trim( $venue_node->textContent ) : '';

			// Column 4 = date range, e.g. "04/13/2026 3 days".
			$date_node = $xpath->query( ".//td[4]", $tr )->item( 0 );
			$date_text = $date_node ? trim( $date_node->textContent ) : '';

			list( $start_date, $end_date ) = self::parse_date_range( $date_text );

			$slug = sanitize_title( $title_raw . '-' . wp_hash( $source_url ) );

			$results[] = array(
				'source_url'  => $source_url,
				'slug'        => $slug,
				'title'       => $title_raw,
				'description' => $description,
				'city'        => $city,
				'venue'       => $venue,
				'start_date'  => $start_date,
				'end_date'    => $end_date,
				'cycle'       => $cycle,
				'image_url'   => '',
				'category'    => RHG_Categories::auto_assign( $title_raw, $description ),
			);
		}

		return $results;
	}

	/**
	 * EventsEye dates look like "04/13/2026 3 days". Convert to MySQL DATE.
	 */
	private static function parse_date_range( $text ) {
		if ( empty( $text ) ) {
			return array( null, null );
		}

		if ( ! preg_match( '/(\d{2})\/(\d{2})\/(\d{4})/', $text, $m ) ) {
			return array( null, null );
		}

		$start = $m[3] . '-' . $m[1] . '-' . $m[2];
		$end   = $start;

		if ( preg_match( '/(\d+)\s*day/i', $text, $d ) ) {
			$days = max( 1, (int) $d[1] );
			$end  = date( 'Y-m-d', strtotime( $start . ' + ' . ( $days - 1 ) . ' days' ) );
		}

		return array( $start, $end );
	}
}
