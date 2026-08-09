<?php
/**
 * Category engine — auto-assigns a category to each exhibition
 * based on keyword matching against title + description.
 *
 * The category list and their keywords are stored in WP options
 * so admins can manage them from Settings → Categories without
 * touching code.
 *
 * Default categories ship with the plugin on first activation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RHG_Categories {

	const OPTION_KEY = 'rhg_exh_categories';

	/**
	 * Default category definitions.
	 * Each entry: [ 'name' => '...', 'keywords' => 'comma,separated,words' ]
	 * Keywords are checked (case-insensitive) against exhibition title + description.
	 * Order matters — first match wins.
	 */
	public static function defaults() {
		return array(
			array( 'name' => 'Automotive & Transport',     'keywords' => 'automotive,vehicle,car,motor,fleet,transport,truck,van,driving,automobile', 'image' => '' ),
			array( 'name' => 'Aerospace & Defence',        'keywords' => 'aerospace,aviation,defence,defense,military,security,drone,space,air show', 'image' => '' ),
			array( 'name' => 'Building & Construction',    'keywords' => 'building,construction,architecture,property,infrastructure,housing,concrete,scaffold,planning', 'image' => '' ),
			array( 'name' => 'Energy & Environment',       'keywords' => 'energy,solar,wind,renewable,sustainability,green,environment,climate,biogas,hydrogen,battery,electric', 'image' => '' ),
			array( 'name' => 'Electronics & Technology',   'keywords' => 'electronics,technology,tech,digital,computing,software,hardware,IT,cyber,robot,AI,data,network,telecom,connected', 'image' => '' ),
			array( 'name' => 'Food & Drink',               'keywords' => 'food,drink,beverage,catering,restaurant,hospitality,bakery,coffee,beer,wine,nutrition,culinary', 'image' => '' ),
			array( 'name' => 'Healthcare & Medical',       'keywords' => 'health,medical,pharma,pharmaceutical,dental,clinical,care,hospital,wellness,biotech,life science,therapy', 'image' => '' ),
			array( 'name' => 'Fashion & Retail',           'keywords' => 'fashion,retail,clothing,apparel,jewellery,jewelry,gift,lifestyle,luxury,beauty,cosmetics,aesthetics,spa', 'image' => '' ),
			array( 'name' => 'Finance & Investment',       'keywords' => 'finance,financial,investment,banking,insurance,fintech,mortgage,wealth,trading,fund', 'image' => '' ),
			array( 'name' => 'Marketing & Advertising',   'keywords' => 'marketing,advertising,media,digital marketing,PR,brand,content,social media,creative,design,print', 'image' => '' ),
			array( 'name' => 'Manufacturing & Engineering','keywords' => 'manufacturing,engineering,industrial,machinery,process,automation,fabrication,materials,advanced materials', 'image' => '' ),
			array( 'name' => 'Logistics & Packaging',      'keywords' => 'logistics,packaging,supply chain,warehouse,delivery,shipping,storage,distribution,pallet', 'image' => '' ),
			array( 'name' => 'Real Estate & Property',     'keywords' => 'real estate,property,land,commercial property,residential,housing,estate agent,investment property', 'image' => '' ),
			array( 'name' => 'Education & Training',       'keywords' => 'education,training,learning,HR,human resources,employment,skills,career,university,school,coaching', 'image' => '' ),
			array( 'name' => 'Science & Research',         'keywords' => 'science,research,R&D,laboratory,lab,chemistry,biology,physics,materials science,innovation', 'image' => '' ),
			array( 'name' => 'Agriculture & Horticulture', 'keywords' => 'agriculture,farming,horticulture,garden,crop,livestock,food production,rural', 'image' => '' ),
			array( 'name' => 'Sports & Leisure',           'keywords' => 'sport,fitness,leisure,recreation,outdoor,gym,wellness,yoga,running,cycling', 'image' => '' ),
			array( 'name' => 'Printing & Publishing',      'keywords' => 'print,printing,publishing,graphic,editorial,media,magazine,book,signage', 'image' => '' ),
			array( 'name' => 'Franchise & Business',       'keywords' => 'franchise,franchising,business,entrepreneur,start-up,startup,SME,growth,strategy,consulting', 'image' => '' ),
			array( 'name' => 'Other',                       'keywords' => '', 'image' => '' ), // catch-all — always last, empty keywords = default fallback for unmatched expos
		);
	}

	/**
	 * Maps each default category name to its bundled default SVG icon
	 * filename (inside assets/img/categories/). Used as a last-resort
	 * fallback when a category has no custom uploaded image. Fixed
	 * mapping by name — does NOT rely on sanitize_title(), which can
	 * behave inconsistently across category name edits.
	 */
	public static function default_image_filenames() {
		return array(
			'Automotive & Transport'      => 'automotive-transport.svg',
			'Aerospace & Defence'         => 'aerospace-defence.svg',
			'Building & Construction'     => 'building-construction.svg',
			'Energy & Environment'        => 'energy-environment.svg',
			'Electronics & Technology'    => 'electronics-technology.svg',
			'Food & Drink'                => 'food-drink.svg',
			'Healthcare & Medical'        => 'healthcare-medical.svg',
			'Fashion & Retail'            => 'fashion-retail.svg',
			'Finance & Investment'        => 'finance-investment.svg',
			'Marketing & Advertising'     => 'marketing-advertising.svg',
			'Manufacturing & Engineering' => 'manufacturing-engineering.svg',
			'Logistics & Packaging'       => 'logistics-packaging.svg',
			'Real Estate & Property'      => 'real-estate-property.svg',
			'Education & Training'        => 'education-training.svg',
			'Science & Research'          => 'science-research.svg',
			'Agriculture & Horticulture'  => 'agriculture-horticulture.svg',
			'Sports & Leisure'            => 'sports-leisure.svg',
			'Printing & Publishing'       => 'printing-publishing.svg',
			'Franchise & Business'        => 'franchise-business.svg',
			'Other'                        => 'other.svg',
		);
	}

	/**
	 * Returns the image URL to use for a given category name:
	 * 1. A custom image uploaded by the admin for that category, if set
	 * 2. The bundled default icon matching that category name, if known
	 * 3. The generic "other.svg" icon as a final fallback
	 */
	public static function get_image( $category_name ) {
		foreach ( self::get_all() as $cat ) {
			if ( $cat['name'] === $category_name && ! empty( $cat['image'] ?? '' ) ) {
				return $cat['image'];
			}
		}

		$defaults = self::default_image_filenames();
		$filename = isset( $defaults[ $category_name ] ) ? $defaults[ $category_name ] : 'other.svg';

		return RHG_EXH_URL . 'assets/img/categories/' . $filename;
	}

	/**
	 * Get the live category list (from DB option, or defaults if not set yet).
	 */
	public static function get_all() {
		$saved = get_option( self::OPTION_KEY );
		if ( ! empty( $saved ) && is_array( $saved ) ) {
			return $saved;
		}
		return self::defaults();
	}

	/**
	 * Save category list to DB.
	 */
	public static function save( array $categories ) {
		update_option( self::OPTION_KEY, $categories );
	}

	/**
	 * Return just the category names as a flat array (for dropdowns).
	 */
	public static function get_names() {
		$names = array();
		foreach ( self::get_all() as $cat ) {
			$names[] = $cat['name'];
		}
		return $names;
	}

	/**
	 * Auto-assign a category to an exhibition by matching keywords
	 * against the title and description (case-insensitive).
	 * Returns the first matching category name, or the last category
	 * (catch-all) if nothing matches.
	 */
	public static function auto_assign( $title, $description = '' ) {
		$haystack   = strtolower( $title . ' ' . $description );
		$categories = self::get_all();
		$fallback   = end( $categories )['name'] ?? 'Other';

		foreach ( $categories as $cat ) {
			if ( empty( $cat['keywords'] ) ) {
				continue; // skip the fallback entry during matching
			}

			$keywords = array_map( 'trim', explode( ',', strtolower( $cat['keywords'] ) ) );

			foreach ( $keywords as $kw ) {
				if ( '' !== $kw && strpos( $haystack, $kw ) !== false ) {
					return $cat['name'];
				}
			}
		}

		return $fallback;
	}

	/**
	 * Seed the option with defaults on first activation.
	 * Also migrates existing installs that still have the old
	 * 'Business Services' catch-all name → 'Other'.
	 */
	public static function maybe_seed_defaults() {
		$existing = get_option( self::OPTION_KEY );

		if ( empty( $existing ) ) {
			self::save( self::defaults() );
			return;
		}

		// Migrate: rename old 'Business Services' catch-all to 'Other'
		$changed = false;
		foreach ( $existing as &$cat ) {
			if ( 'Business Services' === $cat['name'] && empty( $cat['keywords'] ) ) {
				$cat['name'] = 'Other';
				$changed     = true;
			}
		}
		unset( $cat );

		if ( $changed ) {
			self::save( $existing );
		}
	}
}
