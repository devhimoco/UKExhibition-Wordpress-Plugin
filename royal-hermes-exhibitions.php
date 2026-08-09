<?php
/**
 * Plugin Name: HimoEXPO
 * Plugin URI: https://royalhermesgroup.com
 * Description: Daily-updated UK B2B exhibition listings scraped from EventsEye. Features: search, pagination, registration popup with email notifications, dual admin notification emails, and a full admin dashboard with filtered registrations and exhibition views.
 * Version: 10.1.0
 * Author: HimoEXPO (h.maghsoudloo)
 * Text Domain: rhg-exhibitions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RHG_EXH_VERSION', '10.1.0' );
define( 'RHG_EXH_PATH', plugin_dir_path( __FILE__ ) );
define( 'RHG_EXH_URL', plugin_dir_url( __FILE__ ) );

// Core includes
require_once RHG_EXH_PATH . 'includes/class-rhg-categories.php';
require_once RHG_EXH_PATH . 'includes/class-rhg-db.php';
require_once RHG_EXH_PATH . 'includes/class-rhg-scraper.php';
require_once RHG_EXH_PATH . 'includes/class-rhg-cron.php';
require_once RHG_EXH_PATH . 'includes/class-rhg-shortcode.php';
require_once RHG_EXH_PATH . 'includes/class-rhg-ajax.php';
require_once RHG_EXH_PATH . 'includes/class-rhg-email.php';
require_once RHG_EXH_PATH . 'admin/class-rhg-admin.php';

/**
 * Activation: create DB tables and schedule the daily cron.
 */
function rhg_exh_activate() {
	RHG_DB::create_tables();
	RHG_Cron::schedule_event();
	RHG_Categories::maybe_seed_defaults();
}
register_activation_hook( __FILE__, 'rhg_exh_activate' );

/**
 * Deactivation: clear the scheduled cron (keep data).
 */
function rhg_exh_deactivate() {
	RHG_Cron::clear_event();
}
register_deactivation_hook( __FILE__, 'rhg_exh_deactivate' );

/**
 * Boot the plugin pieces that need to hook into WP on every load.
 *
 * Also checks whether the database schema needs upgrading (e.g. a new
 * column was added in this version) and runs it automatically if so.
 * This matters because simply uploading new plugin files — without
 * deactivating and reactivating the plugin — does NOT re-trigger
 * register_activation_hook(), so a schema change would otherwise
 * silently never apply and could cause DB errors on the next save.
 * dbDelta() is always safe to re-run; it only adds what's missing.
 */
function rhg_exh_init() {
	if ( get_option( 'rhg_exh_db_version' ) !== RHG_EXH_VERSION ) {
		RHG_DB::create_tables();
		update_option( 'rhg_exh_db_version', RHG_EXH_VERSION );
	}

	new RHG_Cron();
	new RHG_Shortcode();
	new RHG_Ajax();
	new RHG_Admin();
}
add_action( 'plugins_loaded', 'rhg_exh_init' );

/**
 * Enqueue front-end CSS/JS only on pages that use the shortcode.
 */
function rhg_exh_enqueue_assets() {
	wp_register_style( 'rhg-exh-style', RHG_EXH_URL . 'assets/css/style.css', array(), RHG_EXH_VERSION );
	wp_register_script( 'rhg-exh-script', RHG_EXH_URL . 'assets/js/script.js', array( 'jquery' ), RHG_EXH_VERSION, true );

	wp_localize_script(
		'rhg-exh-script',
		'rhgExhData',
		array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'rhg_exh_nonce' ),
			'countryCities' => RHG_Shortcode::country_city_map(),
			'countries'     => RHG_Shortcode::countries(),
			'jobTitles'     => RHG_Shortcode::job_titles(),
		)
	);

	wp_enqueue_style( 'rhg-exh-style' );
	wp_enqueue_script( 'rhg-exh-script' );
}
add_action( 'wp_enqueue_scripts', 'rhg_exh_enqueue_assets' );
