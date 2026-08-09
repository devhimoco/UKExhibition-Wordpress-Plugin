<?php
/**
 * Sends registration confirmation emails using wp_mail().
 *
 * IMPORTANT: wp_mail() relies on the server's mail setup, which is
 * unreliable on most hosts (emails often fail silently or land in spam).
 * For real-world reliability, install a free SMTP plugin such as
 * "WP Mail SMTP" or "Easy WP SMTP" and connect it to a real mail
 * provider (Gmail, Outlook, SendGrid free tier, etc).
 *
 * On a local XAMPP/localhost setup specifically, PHP's built-in mail()
 * transport (which wp_mail() falls back to without an SMTP plugin) has
 * no real mail server to talk to — it fails silently for EVERY outgoing
 * email on the whole site, not just this plugin. This is the single
 * most common reason registration emails don't arrive. Installing WP
 * Mail SMTP and connecting it to a real account (even a free Gmail
 * account) is the standard fix.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RHG_Email {

	/**
	 * Send both the admin notification and the customer confirmation.
	 * Captures any failure reason to an option so it's visible on the
	 * Settings page instead of failing completely silently.
	 */
	public static function send_registration_emails( $registration, $exhibition ) {
		add_action( 'wp_mail_failed', array( __CLASS__, 'log_mail_failure' ) );

		$admin_ok    = self::send_admin_notification( $registration, $exhibition );
		$customer_ok = self::send_customer_confirmation( $registration, $exhibition );

		if ( $admin_ok && $customer_ok ) {
			// Clear any stale error from a previous failed attempt now
			// that a send has succeeded, so the Settings page reflects
			// current status rather than an old problem.
			delete_option( 'rhg_exh_email_last_error' );
		}

		remove_action( 'wp_mail_failed', array( __CLASS__, 'log_mail_failure' ) );
	}

	/**
	 * Fired by WordPress core whenever PHPMailer throws an exception
	 * (bad SMTP credentials, connection refused, invalid recipient,
	 * etc). Without this, wp_mail() failures are completely invisible.
	 */
	public static function log_mail_failure( $wp_error ) {
		update_option( 'rhg_exh_email_last_error', array(
			'time'    => current_time( 'mysql' ),
			'message' => $wp_error->get_error_message(),
		) );
	}

	private static function admin_emails() {
		$email1 = get_option( 'rhg_exh_admin_email' );
		$email2 = get_option( 'rhg_exh_admin_email2' );

		if ( empty( $email1 ) ) {
			$email1 = get_option( 'admin_email' );
		}

		$emails = array_filter( array( $email1, $email2 ) );
		return array_values( $emails );
	}

	private static function send_admin_notification( $registration, $exhibition ) {
		$recipients  = self::admin_emails();
		$is_request  = ! empty( $exhibition->is_request );

		if ( $is_request ) {
			$subject = sprintf( '[Royal Hermes Group] Exhibition request (not found on site): %s', $exhibition->title );
		} else {
			$subject = sprintf( '[Royal Hermes Group] New registration: %s', $exhibition->title );
		}

		// Plain-text body — do NOT use esc_html() here. esc_html()
		// converts characters like & and ' into HTML entities (&amp;,
		// &#039;) which is only correct for HTML output; in a plain
		// text email it just corrupts venue/city names that contain
		// those characters (e.g. "Fashion & Retail" would literally
		// show up as "Fashion &amp; Retail" in the email).
		if ( $is_request ) {
			$body  = "A visitor could not find their exhibition on the site and submitted a request instead.\n\n";
			$body .= 'Exhibition/date they entered: ' . $exhibition->title . "\n\n";
		} else {
			$body  = "A new registration has been received.\n\n";
			$body .= 'Exhibition: ' . $exhibition->title . "\n";
			$body .= 'Date: ' . $exhibition->start_date . "\n";
			$body .= 'City/Venue: ' . $exhibition->city . ' - ' . $exhibition->venue . "\n\n";
		}
		$body .= "Registrant details:\n";
		$body .= 'Name: ' . $registration['full_name'] . "\n";
		$body .= 'Email: ' . $registration['email'] . "\n";
		if ( ! empty( $registration['phone'] ) ) {
			$body .= 'Phone: ' . $registration['phone'] . "\n";
		}
		$body .= 'Job title: ' . $registration['job_title'] . "\n";
		$body .= 'Country: ' . $registration['country'] . "\n";
		$body .= 'Town: ' . $registration['town'] . "\n";

		$all_sent = true;
		foreach ( $recipients as $to ) {
			$sent = wp_mail( $to, $subject, $body );
			if ( ! $sent ) {
				$all_sent = false;
			}
		}
		return $all_sent;
	}

	private static function send_customer_confirmation( $registration, $exhibition ) {
		$to         = $registration['email'];
		$is_request = ! empty( $exhibition->is_request );

		if ( $is_request ) {
			$subject = 'We received your exhibition request';
			$body    = 'Hi ' . $registration['full_name'] . ",\n\n";
			$body   .= "Thanks for letting us know you're looking for an exhibition we don't currently list:\n\n";
			$body   .= '"' . $exhibition->title . "\"\n\n";
			$body   .= "We'll look into it and get back to you if we're able to add it or point you in the right direction.\n\n";
			$body   .= "Best regards,\nRoyal Hermes Group";
		} else {
			$subject = sprintf( 'Your registration for %s is confirmed', $exhibition->title );
			// Plain-text body — see note above about not using esc_html() here.
			$body  = 'Hi ' . $registration['full_name'] . ",\n\n";
			$body .= "Thank you for registering. Here are your details:\n\n";
			$body .= 'Exhibition: ' . $exhibition->title . "\n";
			$body .= 'Date: ' . $exhibition->start_date . "\n";
			$body .= 'Venue: ' . $exhibition->venue . ', ' . $exhibition->city . "\n\n";
			$body .= "We will be in touch with further details.\n\n";
			$body .= "Best regards,\nRoyal Hermes Group";
		}

		return wp_mail( $to, $subject, $body );
	}
}
