<?php
/**
 * Schedules and runs the daily scrape via WP-Cron.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RHG_Cron {

	const HOOK = 'rhg_exh_daily_scrape';

	public function __construct() {
		add_action( self::HOOK, array( __CLASS__, 'run_scrape' ) );
	}

	public static function schedule_event() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			// Run once a day, starting at next midnight site time.
			$timestamp = strtotime( 'tomorrow midnight' );
			wp_schedule_event( $timestamp, 'daily', self::HOOK );
		}
	}

	public static function clear_event() {
		$timestamp = wp_next_scheduled( self::HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
		}
	}

	public static function run_scrape() {
		RHG_Scraper::run();
	}
}
