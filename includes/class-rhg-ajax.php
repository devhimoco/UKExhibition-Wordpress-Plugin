<?php
/**
 * AJAX endpoints:
 * - rhg_exh_filter       : search + pagination for the exhibitions grid
 * - rhg_exh_register     : handles the popup registration form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RHG_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_rhg_exh_filter', array( __CLASS__, 'filter' ) );
		add_action( 'wp_ajax_nopriv_rhg_exh_filter', array( __CLASS__, 'filter' ) );

		add_action( 'wp_ajax_rhg_exh_register', array( __CLASS__, 'register' ) );
		add_action( 'wp_ajax_nopriv_rhg_exh_register', array( __CLASS__, 'register' ) );
	}

	private static function verify_nonce() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'rhg_exh_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Please refresh the page.' ), 403 );
		}
	}

	/**
	 * Search + pagination, returns rendered card HTML so the front end
	 * just swaps innerHTML.
	 */
	public static function filter() {
		self::verify_nonce();

		$search   = isset( $_POST['search'] )   ? sanitize_text_field( $_POST['search'] )   : '';
		$per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] )               : 20;
		$page     = isset( $_POST['page'] )     ? max( 1, absint( $_POST['page'] ) )         : 1;

		// category can arrive as a single string or a JSON-encoded array
		$category = '';
		if ( isset( $_POST['category'] ) ) {
			$raw = $_POST['category'];
			if ( is_array( $raw ) ) {
				$category = array_filter( array_map( 'sanitize_text_field', $raw ) );
			} elseif ( is_string( $raw ) && '' !== $raw ) {
				$decoded = json_decode( stripslashes( $raw ), true );
				if ( is_array( $decoded ) ) {
					$category = array_filter( array_map( 'sanitize_text_field', $decoded ) );
				} else {
					$category = sanitize_text_field( $raw );
				}
			}
		}

		$allowed_per_page = array( 10, 20, 40, 50 );
		if ( ! in_array( $per_page, $allowed_per_page, true ) ) {
			$per_page = 20;
		}

		$result = RHG_DB::get_exhibitions( $search, $per_page, $page, $category );

		ob_start();
		if ( empty( $result['rows'] ) ) {
			echo '<p class="rhg-exh-empty">No exhibitions found. Try a different search.</p>';
		} else {
			foreach ( $result['rows'] as $exhibition ) {
				RHG_Shortcode::render_card( $exhibition );
			}
		}
		$cards_html = ob_get_clean();

		$total_pages = max( 1, ceil( $result['total'] / $per_page ) );

		wp_send_json_success(
			array(
				'html'        => $cards_html,
				'total'       => $result['total'],
				'total_pages' => $total_pages,
				'page'        => $page,
			)
		);
	}

	/**
	 * Handles registration form submission with strict server-side
	 * validation (the requirement: email must contain "@" and ".com"-style
	 * domain, all required fields present).
	 */
	public static function register() {
		self::verify_nonce();

		$exhibition_id        = isset( $_POST['exhibition_id'] ) ? absint( $_POST['exhibition_id'] ) : 0;
		$requested_exhibition = isset( $_POST['requested_exhibition'] ) ? sanitize_text_field( $_POST['requested_exhibition'] ) : '';
		$full_name             = isset( $_POST['full_name'] ) ? sanitize_text_field( $_POST['full_name'] ) : '';
		$email                 = isset( $_POST['email'] ) ? sanitize_text_field( $_POST['email'] ) : '';
		$phone                 = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : ''; // optional — no validation required
		$job_title             = isset( $_POST['job_title'] ) ? sanitize_text_field( $_POST['job_title'] ) : '';
		$country               = isset( $_POST['country'] ) ? sanitize_text_field( $_POST['country'] ) : '';
		$town                  = isset( $_POST['town'] ) ? sanitize_text_field( $_POST['town'] ) : '';

		$errors = array();

		// Either a real exhibition was selected (normal card flow) OR
		// the visitor typed their own exhibition name/date (the "Can't
		// find your exhibition?" flow) — exactly one of these is needed.
		if ( empty( $exhibition_id ) && empty( $requested_exhibition ) ) {
			$errors[] = 'Please select an exhibition, or enter the exhibition name and date you were looking for.';
		}

		if ( empty( $full_name ) ) {
			$errors[] = 'Name is required.';
		}

		// Strict email format check: must look like a real email address
		// (contains "@" and a valid domain with a dot, e.g. ".com", ".co.uk").
		if ( empty( $email ) || ! self::is_valid_email_format( $email ) ) {
			$errors[] = 'Please enter a valid email address (e.g. name@example.com).';
		}

		if ( empty( $job_title ) ) {
			$errors[] = 'Job title is required.';
		}

		if ( empty( $country ) ) {
			$errors[] = 'Country is required.';
		}

		if ( empty( $town ) ) {
			$errors[] = 'Town is required.';
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error( array( 'message' => implode( ' ', $errors ) ), 422 );
		}

		$exhibition = null;
		if ( ! empty( $exhibition_id ) ) {
			$exhibition = RHG_DB::get_exhibition_by_id( $exhibition_id );
			if ( ! $exhibition ) {
				wp_send_json_error( array( 'message' => 'Exhibition not found.' ), 404 );
			}
		}

		$registration = array(
			'exhibition_id'         => $exhibition_id,
			'requested_exhibition'  => $requested_exhibition,
			'full_name'             => $full_name,
			'email'                 => $email,
			'phone'                 => $phone,
			'job_title'             => $job_title,
			'country'               => $country,
			'town'                  => $town,
		);

		RHG_DB::add_registration( $registration );

		// For a manual "can't find my exhibition" entry there's no real
		// exhibition row — build a lightweight stand-in object so the
		// email functions (which expect ->title, ->start_date, etc.)
		// still work correctly and clearly label it as a request rather
		// than a confirmed listing.
		if ( ! $exhibition ) {
			$exhibition = (object) array(
				'title'      => $requested_exhibition,
				'start_date' => '',
				'city'       => '',
				'venue'      => '',
				'is_request' => true,
			);
		}

		RHG_Email::send_registration_emails( $registration, $exhibition );

		wp_send_json_success( array( 'message' => 'Thank you! Your registration has been received.' ) );
	}

	/**
	 * Validates an email address: must pass PHP's filter AND explicitly
	 * contain "@" plus a dot in the domain portion (covers .com, .co.uk, etc).
	 */
	private static function is_valid_email_format( $email ) {
		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return false;
		}

		if ( strpos( $email, '@' ) === false ) {
			return false;
		}

		$parts = explode( '@', $email );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$domain = $parts[1];
		if ( strpos( $domain, '.' ) === false ) {
			return false;
		}

		return true;
	}
}
