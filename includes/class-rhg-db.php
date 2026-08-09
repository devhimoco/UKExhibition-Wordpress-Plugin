<?php
/**
 * Handles custom database tables:
 * - {prefix}rhg_exhibitions      : scraped exhibition listings
 * - {prefix}rhg_registrations    : visitor sign-ups per exhibition
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RHG_DB {

	public static function exhibitions_table() {
		global $wpdb;
		return $wpdb->prefix . 'rhg_exhibitions';
	}

	public static function registrations_table() {
		global $wpdb;
		return $wpdb->prefix . 'rhg_registrations';
	}

	/**
	 * Create both tables. Safe to call repeatedly (dbDelta handles diffing).
	 */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$exh_table       = self::exhibitions_table();
		$reg_table       = self::registrations_table();

		$sql_exhibitions = "CREATE TABLE $exh_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			source_url VARCHAR(500) NOT NULL,
			slug VARCHAR(255) NOT NULL,
			title VARCHAR(255) NOT NULL,
			description TEXT NULL,
			city VARCHAR(150) NULL,
			venue VARCHAR(255) NULL,
			start_date DATE NULL,
			end_date DATE NULL,
			cycle VARCHAR(100) NULL,
			image_url VARCHAR(500) NULL,
			category VARCHAR(150) NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'published',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			KEY start_date (start_date),
			KEY city (city)
		) $charset_collate;";

		$sql_registrations = "CREATE TABLE $reg_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			exhibition_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			requested_exhibition VARCHAR(500) NULL,
			full_name VARCHAR(255) NOT NULL,
			email VARCHAR(255) NOT NULL,
			phone VARCHAR(50) NULL,
			job_title VARCHAR(255) NULL,
			country VARCHAR(150) NULL,
			town VARCHAR(150) NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY exhibition_id (exhibition_id),
			KEY email (email)
		) $charset_collate;";

		dbDelta( $sql_exhibitions );
		dbDelta( $sql_registrations );
	}

	/**
	 * Insert a new exhibition or update an existing one (matched by slug).
	 * Returns the row ID.
	 */
	public static function upsert_exhibition( array $data ) {
		global $wpdb;
		$table = self::exhibitions_table();
		$now   = current_time( 'mysql' );

		$existing_id = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM $table WHERE slug = %s", $data['slug'] )
		);

		if ( $existing_id ) {
			$wpdb->update(
				$table,
				array(
					'title'       => $data['title'],
					'description' => $data['description'],
					'city'        => $data['city'],
					'venue'       => $data['venue'],
					'start_date'  => $data['start_date'],
					'end_date'    => $data['end_date'],
					'cycle'       => $data['cycle'],
					'image_url'   => $data['image_url'],
					'category'    => $data['category'],
					'source_url'  => $data['source_url'],
					'updated_at'  => $now,
				),
				array( 'id' => $existing_id )
			);
			return (int) $existing_id;
		}

		$wpdb->insert(
			$table,
			array(
				'source_url'  => $data['source_url'],
				'slug'        => $data['slug'],
				'title'       => $data['title'],
				'description' => $data['description'],
				'city'        => $data['city'],
				'venue'       => $data['venue'],
				'start_date'  => $data['start_date'],
				'end_date'    => $data['end_date'],
				'cycle'       => $data['cycle'],
				'image_url'   => $data['image_url'],
				'category'    => $data['category'],
				'status'      => 'published',
				'created_at'  => $now,
				'updated_at'  => $now,
			)
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Fetch a paginated, optionally-searched list of exhibitions.
	 */
	public static function get_exhibitions( $search = '', $per_page = 20, $page = 1, $category = '' ) {
		global $wpdb;
		$table  = self::exhibitions_table();
		$offset = max( 0, ( $page - 1 ) * $per_page );

		$where  = "WHERE status = 'published'";
		$params = array();

		if ( ! empty( $search ) ) {
			$where   .= ' AND (title LIKE %s OR city LIKE %s OR venue LIKE %s OR category LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		// $category can be a string (single) or array (multi-select)
		if ( ! empty( $category ) ) {
			$cats = is_array( $category ) ? array_filter( array_map( 'sanitize_text_field', $category ) ) : array( sanitize_text_field( $category ) );
			if ( ! empty( $cats ) ) {
				$placeholders = implode( ', ', array_fill( 0, count( $cats ), '%s' ) );
				$where       .= " AND category IN ($placeholders)";
				foreach ( $cats as $c ) {
					$params[] = $c;
				}
			}
		}

		$count_sql = "SELECT COUNT(*) FROM $table $where";
		$total     = $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql );

		$data_params   = $params;
		$data_params[] = $per_page;
		$data_params[] = $offset;

		$sql  = "SELECT * FROM $table $where
				ORDER BY
					CASE WHEN start_date IS NULL OR start_date < CURDATE() THEN 1 ELSE 0 END ASC,
					start_date ASC
				LIMIT %d OFFSET %d";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $data_params ) );

		return array(
			'rows'  => $rows,
			'total' => (int) $total,
		);
	}

	public static function get_exhibition_by_id( $id ) {
		global $wpdb;
		$table = self::exhibitions_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	/**
	 * Save a registration entry.
	 */
	public static function add_registration( array $data ) {
		global $wpdb;
		$table = self::registrations_table();

		$wpdb->insert(
			$table,
			array(
				'exhibition_id'         => $data['exhibition_id'],
				'requested_exhibition'  => $data['requested_exhibition'] ?? null,
				'full_name'             => $data['full_name'],
				'email'                 => $data['email'],
				'phone'                 => $data['phone'] ?? '',
				'job_title'             => $data['job_title'],
				'country'               => $data['country'],
				'town'                  => $data['town'],
				'created_at'            => current_time( 'mysql' ),
			)
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Admin: registration counts grouped by exhibition.
	 */
	public static function get_registration_counts() {
		global $wpdb;
		$exh_table = self::exhibitions_table();
		$reg_table = self::registrations_table();

		$sql = "SELECT e.id, e.title, e.start_date, e.city, COUNT(r.id) AS total_registrations
				FROM $exh_table e
				LEFT JOIN $reg_table r ON r.exhibition_id = e.id
				GROUP BY e.id
				ORDER BY e.start_date ASC";

		return $wpdb->get_results( $sql );
	}

	/**
	 * Admin: all registrations for one exhibition.
	 */
	public static function get_registrations_for_exhibition( $exhibition_id ) {
		global $wpdb;
		$table = self::registrations_table();
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table WHERE exhibition_id = %d ORDER BY created_at DESC", $exhibition_id )
		);
	}

	/**
	 * Admin: every registration across all exhibitions (joined with exhibition title).
	 */
	public static function get_all_registrations() {
		global $wpdb;
		$exh_table = self::exhibitions_table();
		$reg_table = self::registrations_table();

		$sql = "SELECT r.*, e.title AS exhibition_title
				FROM $reg_table r
				LEFT JOIN $exh_table e ON e.id = r.exhibition_id
				ORDER BY r.created_at DESC";

		return $wpdb->get_results( $sql );
	}

	/**
	 * Admin Page 1 — All registrations with optional filters:
	 *   $search      : matches full_name, email, exhibition title
	 *   $date_range  : '1','3','7','30' (expo start_date within N days from today)
	 *                  or 'specific' (use $spec_date)
	 *   $city        : exact match on exhibition city
	 *   $category    : exact match on exhibition category
	 *   $spec_date   : Y-m-d string for 'specific' date filter
	 */
	public static function get_all_registrations_filtered( $search = '', $date_range = '', $city = '', $category = '', $spec_date = '' ) {
		global $wpdb;
		$exh_table = self::exhibitions_table();
		$reg_table = self::registrations_table();

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $search ) ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '( r.full_name LIKE %s OR r.email LIKE %s OR e.title LIKE %s )';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $date_range ) && 'specific' !== $date_range ) {
			$days     = (int) $date_range;
			$where[]  = 'e.start_date BETWEEN CURDATE() AND DATE_ADD( CURDATE(), INTERVAL %d DAY )';
			$params[] = $days;
		} elseif ( 'specific' === $date_range && ! empty( $spec_date ) ) {
			$where[]  = 'e.start_date = %s';
			$params[] = $spec_date;
		}

		if ( ! empty( $city ) ) {
			$where[]  = 'e.city = %s';
			$params[] = $city;
		}

		if ( ! empty( $category ) ) {
			$where[]  = 'e.category = %s';
			$params[] = $category;
		}

		$where_sql = implode( ' AND ', $where );

		$sql = "SELECT r.*, e.title AS exhibition_title, e.start_date, e.city AS exhibition_city
				FROM $reg_table r
				LEFT JOIN $exh_table e ON e.id = r.exhibition_id
				WHERE $where_sql
				ORDER BY r.created_at DESC";

		if ( ! empty( $params ) ) {
			return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Admin Exhibitions page — ALL exhibitions grouped by category.
	 * Within each category: registered exhibitions first (most → least),
	 * then unregistered exhibitions after.
	 */
	public static function get_all_exhibitions_grouped_by_category() {
		global $wpdb;
		$exh_table = self::exhibitions_table();

		// Migrate any rows still labelled 'Business Services' (old catch-all)
		// to 'Other' so the admin page shows them correctly.
		$wpdb->query(
			"UPDATE $exh_table SET category = 'Other'
			 WHERE category = 'Business Services' OR category = '' OR category IS NULL"
		);

		$reg_table = self::registrations_table();

		$sql = "SELECT
					e.*,
					COUNT(r.id) AS total_registrations
				FROM $exh_table e
				LEFT JOIN $reg_table r ON r.exhibition_id = e.id
				WHERE e.status = 'published'
				GROUP BY e.id
				ORDER BY
					e.category ASC,
					total_registrations DESC,
					CASE WHEN e.start_date IS NULL OR e.start_date < CURDATE() THEN 1 ELSE 0 END ASC,
					e.start_date ASC";

		$rows = $wpdb->get_results( $sql );

		// Group into [ category => [ expos ] ]
		$grouped = array();
		foreach ( $rows as $row ) {
			$cat = $row->category ?: 'Uncategorised';
			if ( ! isset( $grouped[ $cat ] ) ) {
				$grouped[ $cat ] = array(
					'expos'                => array(),
					'total_registrations'  => 0,
					'total_expos'          => 0,
				);
			}
			$grouped[ $cat ]['expos'][]               = $row;
			$grouped[ $cat ]['total_registrations']   += (int) $row->total_registrations;
			$grouped[ $cat ]['total_expos']++;
		}

		// Sort categories: those with registrations first (desc), then rest
		uasort( $grouped, function( $a, $b ) {
			if ( $b['total_registrations'] !== $a['total_registrations'] ) {
				return $b['total_registrations'] - $a['total_registrations'];
			}
			return $b['total_expos'] - $a['total_expos'];
		} );

		return $grouped;
	}
	public static function get_exhibitions_with_registrations( $name = '', $date_range = '', $city = '', $category = '', $spec_date = '' ) {
		global $wpdb;
		$exh_table = self::exhibitions_table();
		$reg_table = self::registrations_table();

		$where  = array( "e.status = 'published'" );
		$params = array();

		if ( ! empty( $name ) ) {
			$like     = '%' . $wpdb->esc_like( $name ) . '%';
			$where[]  = 'e.title LIKE %s';
			$params[] = $like;
		}

		if ( ! empty( $date_range ) && 'specific' !== $date_range ) {
			$days     = (int) $date_range;
			$where[]  = 'e.start_date BETWEEN CURDATE() AND DATE_ADD( CURDATE(), INTERVAL %d DAY )';
			$params[] = $days;
		} elseif ( 'specific' === $date_range && ! empty( $spec_date ) ) {
			$where[]  = 'e.start_date = %s';
			$params[] = $spec_date;
		}

		if ( ! empty( $city ) ) {
			$where[]  = 'e.city = %s';
			$params[] = $city;
		}

		if ( ! empty( $category ) ) {
			$where[]  = 'e.category = %s';
			$params[] = $category;
		}

		$where_sql = implode( ' AND ', $where );

		$sql = "SELECT e.*, COUNT(r.id) AS total_registrations
				FROM $exh_table e
				INNER JOIN $reg_table r ON r.exhibition_id = e.id
				WHERE $where_sql
				GROUP BY e.id
				HAVING total_registrations > 0
				ORDER BY e.start_date ASC";

		if ( ! empty( $params ) ) {
			return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Returns distinct non-empty city values from the exhibitions table.
	 */
	public static function get_distinct_cities() {
		global $wpdb;
		$table = self::exhibitions_table();
		$rows  = $wpdb->get_col( "SELECT DISTINCT city FROM $table WHERE city != '' ORDER BY city ASC" );
		return $rows ?: array();
	}

	/**
	 * Returns distinct non-empty category values from the exhibitions table.
	 */
	public static function get_distinct_categories() {
		global $wpdb;
		$table = self::exhibitions_table();
		$rows  = $wpdb->get_col( "SELECT DISTINCT category FROM $table WHERE category != '' ORDER BY category ASC" );
		return $rows ?: array();
	}
}
