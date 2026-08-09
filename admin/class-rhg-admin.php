<?php
/**
 * Admin area — 3 pages:
 * 1. Registrations  : table of everyone who registered, searchable/filterable.
 * 2. Exhibitions    : all scraped exhibitions with filters; only shows those
 *                     that have at least 1 registration — click through to see
 *                     the individual registrants for that expo.
 * 3. Settings       : 2 admin notification emails + scrape controls.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RHG_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/* ------------------------------------------------------------------ */
	/* MENU                                                                 */
	/* ------------------------------------------------------------------ */

	public function add_menu() {
		add_menu_page(
			'HimoEXPO',
			'HimoEXPO',
			'manage_options',
			'rhg-exhibitions',
			array( $this, 'render_registrations_page' ),
			'dashicons-tickets-alt',
			26
		);

		add_submenu_page(
			'rhg-exhibitions',
			'Registrations',
			'Registrations',
			'manage_options',
			'rhg-exhibitions',
			array( $this, 'render_registrations_page' )
		);

		add_submenu_page(
			'rhg-exhibitions',
			'Exhibitions',
			'Exhibitions',
			'manage_options',
			'rhg-exhibitions-list',
			array( $this, 'render_exhibitions_page' )
		);

		add_submenu_page(
			'rhg-exhibitions',
			'Settings',
			'Settings',
			'manage_options',
			'rhg-exhibitions-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/* ------------------------------------------------------------------ */
	/* ASSETS                                                               */
	/* ------------------------------------------------------------------ */

	public function enqueue_admin_assets( $hook ) {
		// These must match WordPress's actual generated hook suffixes,
		// which are built from the TOP-LEVEL menu's slug ('rhg-exhibitions')
		// as the prefix for every submenu page — e.g. a submenu with slug
		// 'rhg-exhibitions-list' gets the hook 'rhg-exhibitions_page_rhg-exhibitions-list'.
		// A previous version of this list was missing the 'rhg-' prefix
		// (e.g. 'exhibitions_page_...' instead of 'rhg-exhibitions_page_...'),
		// which silently prevented admin.css from ever loading on the
		// Exhibitions and Settings pages — only the top-level Registrations
		// page happened to match. Fixed below.
		$admin_pages = array(
			'toplevel_page_rhg-exhibitions',
			'rhg-exhibitions_page_rhg-exhibitions-list',
			'rhg-exhibitions_page_rhg-exhibitions-settings',
		);
		if ( ! in_array( $hook, $admin_pages, true ) ) {
			return;
		}
		wp_enqueue_style(
			'rhg-admin-style',
			RHG_EXH_URL . 'assets/css/admin.css',
			array(),
			RHG_EXH_VERSION
		);
	}

	/* ------------------------------------------------------------------ */
	/* HANDLE FORM POSTS                                                    */
	/* ------------------------------------------------------------------ */

	public function handle_actions() {
		if ( isset( $_POST['rhg_exh_save_settings'] ) && check_admin_referer( 'rhg_exh_settings_nonce' ) ) {
			update_option( 'rhg_exh_admin_email',  sanitize_email( $_POST['rhg_exh_admin_email'] ) );
			update_option( 'rhg_exh_admin_email2', sanitize_email( $_POST['rhg_exh_admin_email2'] ) );

			// Save categories (name, keywords, and optional custom image per row)
			$new_cats      = array();
			$names         = isset( $_POST['cat_name'] )          ? (array) $_POST['cat_name']          : array();
			$keywords      = isset( $_POST['cat_keywords'] )      ? (array) $_POST['cat_keywords']      : array();
			$existing_imgs = isset( $_POST['cat_image_existing'] ) ? (array) $_POST['cat_image_existing'] : array();
			$remove_flags  = isset( $_POST['cat_image_remove'] )   ? (array) $_POST['cat_image_remove']   : array();

			foreach ( $names as $i => $name ) {
				$name = sanitize_text_field( $name );
				if ( '' === $name ) {
					continue;
				}

				// Start with whatever image was already set for this row.
				$image_url = isset( $existing_imgs[ $i ] ) ? esc_url_raw( $existing_imgs[ $i ] ) : '';

				// "Remove image" checkbox reverts this row to the default icon.
				if ( isset( $remove_flags[ $i ] ) && '1' === $remove_flags[ $i ] ) {
					$image_url = '';
				}

				// A newly uploaded file for this row overrides everything.
				if ( ! empty( $_FILES['cat_image']['name'][ $i ] ) ) {
					$uploaded = $this->handle_category_image_upload( $i );
					if ( $uploaded ) {
						$image_url = $uploaded;
					}
				}

				$new_cats[] = array(
					'name'     => $name,
					'keywords' => sanitize_text_field( $keywords[ $i ] ?? '' ),
					'image'    => $image_url,
				);
			}

			if ( ! empty( $new_cats ) ) {
				RHG_Categories::save( $new_cats );
			}

			add_settings_error( 'rhg_exh_settings', 'saved', 'Settings saved.', 'updated' );
		}

		if ( isset( $_POST['rhg_exh_run_scrape_now'] ) && check_admin_referer( 'rhg_exh_settings_nonce' ) ) {
			$count = RHG_Scraper::run();
			add_settings_error( 'rhg_exh_settings', 'scraped', sprintf( 'Scrape complete: %d exhibitions saved/updated.', $count ), 'updated' );
		}

		if ( isset( $_POST['rhg_exh_send_test_email'] ) && check_admin_referer( 'rhg_exh_settings_nonce' ) ) {
			$to = get_option( 'rhg_exh_admin_email' ) ?: get_option( 'admin_email' );

			add_action( 'wp_mail_failed', array( 'RHG_Email', 'log_mail_failure' ) );
			delete_option( 'rhg_exh_email_last_error' );

			$sent = wp_mail(
				$to,
				'HimoEXPO test email',
				"This is a test email from HimoEXPO.\n\nIf you received this, email sending is working correctly."
			);

			remove_action( 'wp_mail_failed', array( 'RHG_Email', 'log_mail_failure' ) );

			if ( $sent ) {
				delete_option( 'rhg_exh_email_last_error' );
				add_settings_error( 'rhg_exh_settings', 'test_email_sent', sprintf( 'Test email sent to %s. Check that inbox (and spam folder) to confirm it arrived.', $to ), 'updated' );
			} else {
				add_settings_error( 'rhg_exh_settings', 'test_email_failed', sprintf( 'wp_mail() reported failure sending to %s. See the error box below for details.', $to ), 'error' );
			}
		}
	}

	/**
	 * Handles a single category image file upload (index $i in the
	 * cat_image[] file input array). Returns the uploaded file's URL
	 * on success, or false on failure/no file/invalid type.
	 */
	private function handle_category_image_upload( $i ) {
		if ( empty( $_FILES['cat_image']['name'][ $i ] ) ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$allowed_types = array( 'image/jpeg', 'image/png', 'image/webp', 'image/svg+xml' );
		$file_type     = isset( $_FILES['cat_image']['type'][ $i ] ) ? $_FILES['cat_image']['type'][ $i ] : '';

		if ( ! in_array( $file_type, $allowed_types, true ) ) {
			add_settings_error( 'rhg_exh_settings', 'bad_image_type', 'One of the category images was not a valid image file (use JPG, PNG, WEBP, or SVG) and was skipped.', 'error' );
			return false;
		}

		$single_file = array(
			'name'     => $_FILES['cat_image']['name'][ $i ],
			'type'     => $_FILES['cat_image']['type'][ $i ],
			'tmp_name' => $_FILES['cat_image']['tmp_name'][ $i ],
			'error'    => $_FILES['cat_image']['error'][ $i ],
			'size'     => $_FILES['cat_image']['size'][ $i ],
		);

		$overrides = array( 'test_form' => false );
		$result    = wp_handle_upload( $single_file, $overrides );

		if ( isset( $result['error'] ) ) {
			add_settings_error( 'rhg_exh_settings', 'upload_error', 'Image upload failed: ' . esc_html( $result['error'] ), 'error' );
			return false;
		}

		return isset( $result['url'] ) ? $result['url'] : false;
	}

	/* ------------------------------------------------------------------ */
	/* PAGE 1 — REGISTRATIONS                                              */
	/* ------------------------------------------------------------------ */

	public function render_registrations_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Filter values from GET.
		$search   = isset( $_GET['rhg_search'] )   ? sanitize_text_field( $_GET['rhg_search'] )   : '';
		$date_range = isset( $_GET['rhg_date'] )   ? sanitize_text_field( $_GET['rhg_date'] )     : '';
		$city     = isset( $_GET['rhg_city'] )     ? sanitize_text_field( $_GET['rhg_city'] )     : '';
		$category = isset( $_GET['rhg_category'] ) ? sanitize_text_field( $_GET['rhg_category'] ) : '';
		$spec_date = isset( $_GET['rhg_spec_date'] ) ? sanitize_text_field( $_GET['rhg_spec_date'] ) : '';

		$rows = RHG_DB::get_all_registrations_filtered( $search, $date_range, $city, $category, $spec_date );

		$cities     = RHG_DB::get_distinct_cities();
		$categories = RHG_DB::get_distinct_categories();
		?>
		<div class="wrap rhg-admin-wrap">
			<h1 class="wp-heading-inline">Registrations</h1>
			<hr class="wp-header-end">

			<form method="get" class="rhg-filter-bar">
				<input type="hidden" name="page" value="rhg-exhibitions" />

				<input
					type="text"
					name="rhg_search"
					value="<?php echo esc_attr( $search ); ?>"
					placeholder="Search name, email, exhibition..."
					class="rhg-filter-input"
				/>

				<select name="rhg_date" class="rhg-filter-select">
					<option value="">All dates</option>
					<option value="1"  <?php selected( $date_range, '1' ); ?>>Within 1 day</option>
					<option value="3"  <?php selected( $date_range, '3' ); ?>>Within 3 days</option>
					<option value="7"  <?php selected( $date_range, '7' ); ?>>Within 7 days</option>
					<option value="30" <?php selected( $date_range, '30' ); ?>>Within 30 days</option>
					<option value="specific" <?php selected( $date_range, 'specific' ); ?>>Specific date</option>
				</select>

				<input
					type="date"
					name="rhg_spec_date"
					value="<?php echo esc_attr( $spec_date ); ?>"
					class="rhg-filter-input rhg-specific-date <?php echo ( 'specific' === $date_range ) ? '' : 'rhg-hidden'; ?>"
				/>

				<select name="rhg_city" class="rhg-filter-select">
					<option value="">All cities</option>
					<?php foreach ( $cities as $c ) : ?>
						<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $city, $c ); ?>><?php echo esc_html( $c ); ?></option>
					<?php endforeach; ?>
				</select>

				<select name="rhg_category" class="rhg-filter-select">
					<option value="">All categories</option>
					<?php foreach ( $categories as $cat ) : ?>
						<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $category, $cat ); ?>><?php echo esc_html( $cat ); ?></option>
					<?php endforeach; ?>
				</select>

				<button type="submit" class="button button-primary">Filter</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rhg-exhibitions' ) ); ?>" class="button">Reset</a>
			</form>

			<p class="rhg-result-count">
				<?php echo count( $rows ); ?> registration(s) found.
			</p>

			<table class="wp-list-table widefat fixed striped rhg-table">
				<thead>
					<tr>
						<th>Name</th>
						<th>Email</th>
						<th>Phone</th>
						<th>Job Title</th>
						<th>Country</th>
						<th>Town</th>
						<th>Exhibition</th>
						<th>Expo Date</th>
						<th>Expo City</th>
						<th>Registered On</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="10" style="text-align:center; padding:20px;">No registrations found matching your filters.</td></tr>
					<?php else : ?>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->full_name ); ?></td>
								<td><?php echo esc_html( $row->email ); ?></td>
								<td><?php echo esc_html( $row->phone ?: '—' ); ?></td>
								<td><?php echo esc_html( $row->job_title ); ?></td>
								<td><?php echo esc_html( $row->country ); ?></td>
								<td><?php echo esc_html( $row->town ); ?></td>
								<td>
									<?php if ( ! empty( $row->exhibition_title ) ) : ?>
										<strong><?php echo esc_html( $row->exhibition_title ); ?></strong>
									<?php elseif ( ! empty( $row->requested_exhibition ) ) : ?>
										<span class="rhg-badge-not-found">Not Found</span>
										<strong><?php echo esc_html( $row->requested_exhibition ); ?></strong>
									<?php else : ?>
										<span style="color:#aaa;">&mdash;</span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $row->start_date ? date( 'd M Y', strtotime( $row->start_date ) ) : '—' ); ?></td>
								<td><?php echo esc_html( $row->exhibition_city ); ?></td>
								<td><?php echo esc_html( date( 'd M Y, H:i', strtotime( $row->created_at ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php $this->render_copyright_footer(); ?>
		</div>

		<script>
		(function(){
			var sel = document.querySelector('[name="rhg_date"]');
			var sd  = document.querySelector('.rhg-specific-date');
			if ( sel && sd ) {
				sel.addEventListener('change', function(){
					sd.classList.toggle('rhg-hidden', this.value !== 'specific');
				});
			}
		})();
		</script>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* PAGE 2 — EXHIBITIONS — accordion grouped by category               */
	/* ------------------------------------------------------------------ */

	public function render_exhibitions_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$view          = isset( $_GET['view'] )         ? sanitize_text_field( $_GET['view'] )  : 'list';
		$exhibition_id = isset( $_GET['exhibition_id'] ) ? absint( $_GET['exhibition_id'] )      : 0;

		if ( 'detail' === $view && $exhibition_id ) {
			$this->render_exhibition_detail( $exhibition_id );
			return;
		}

		$grouped = RHG_DB::get_all_exhibitions_grouped_by_category();
		$total_expos = 0;
		$total_regs  = 0;
		foreach ( $grouped as $g ) {
			$total_expos += $g['total_expos'];
			$total_regs  += $g['total_registrations'];
		}
		?>
		<div class="wrap rhg-admin-wrap">
			<h1 class="wp-heading-inline">All Exhibitions by Category</h1>
			<hr class="wp-header-end">

			<p class="rhg-result-count">
				<?php echo (int) $total_expos; ?> exhibitions across <?php echo count( $grouped ); ?> categories
				&mdash; <span style="color:#1d7a3c; font-weight:700;"><?php echo (int) $total_regs; ?> total registrations</span>
			</p>

			<!-- One simple table: Name | Number | Register. Each category
			     is a real row. Clicking a row reveals a second row right
			     below it (spanning all 3 columns) containing that
			     category's exhibition list. This is plain, valid table
			     structure throughout — no colspan tricks, no separate
			     tables to align, just rows and cells. -->
			<table class="wp-list-table widefat fixed striped rhg-table" id="rhg-cat-accordion">
				<thead>
					<tr>
						<th>Exhibitions Category</th>
						<th style="width:190px; text-align:center;">Number of Exhibitions</th>
						<th style="width:190px; text-align:center;">Registered Person</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $grouped as $cat_name => $group ) :
						$has_regs = $group['total_registrations'] > 0;
						$block_id = 'rhg-cat-' . sanitize_html_class( $cat_name );
					?>
					<tr class="rhg-cat-header <?php echo $has_regs ? 'rhg-cat-has-regs' : ''; ?>" data-target="<?php echo esc_attr( $block_id ); ?>">
						<td class="rhg-cat-name-cell">
							<span class="rhg-cat-toggle-icon">▶</span>
							<strong class="rhg-cat-name"><?php echo esc_html( $cat_name ); ?></strong>
						</td>
						<td class="rhg-cat-stat-cell">
							🗂 <?php echo (int) $group['total_expos']; ?> exhibition<?php echo $group['total_expos'] !== 1 ? 's' : ''; ?>
						</td>
						<td class="rhg-cat-stat-cell rhg-cat-reg-count <?php echo $has_regs ? 'rhg-cat-reg-has' : ''; ?>">
							👥 <?php echo (int) $group['total_registrations']; ?> registered
						</td>
					</tr>
					<tr class="rhg-cat-body-row" id="<?php echo esc_attr( $block_id ); ?>" style="display:none;">
						<td colspan="3" class="rhg-cat-body-cell">
							<table class="wp-list-table widefat fixed striped rhg-expo-table">
								<thead>
									<tr>
										<th>Exhibition</th>
										<th>Date</th>
										<th>City</th>
										<th>Cycle</th>
										<th style="width:110px; text-align:center;">Registrations</th>
										<th style="width:130px;">Action</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $group['expos'] as $expo ) :
										$reg_count = (int) $expo->total_registrations;
									?>
									<tr class="<?php echo $reg_count > 0 ? 'rhg-expo-registered' : 'rhg-expo-none'; ?>">
										<td><strong><?php echo esc_html( $expo->title ); ?></strong></td>
										<td>
											<?php echo esc_html( $expo->start_date ? date( 'd M Y', strtotime( $expo->start_date ) ) : '—' ); ?>
											<?php
											if ( $expo->start_date ) {
												$diff = (int) round( ( strtotime( $expo->start_date ) - strtotime( date('Y-m-d') ) ) / 86400 );
												if ( $diff > 0 ) echo '<br><small style="color:#1d7a3c;font-weight:700;">' . number_format($diff) . ' days to go</small>';
												elseif ( $diff === 0 ) echo '<br><small style="color:#e63950;font-weight:700;">Today!</small>';
												else echo '<br><small style="color:#aaa;">Ended</small>';
											}
											?>
										</td>
										<td><?php echo esc_html( $expo->city ); ?></td>
										<td><?php echo esc_html( $expo->cycle ?: '—' ); ?></td>
										<td style="text-align:center;">
											<?php if ( $reg_count > 0 ) : ?>
												<span class="rhg-badge"><?php echo $reg_count; ?></span>
											<?php else : ?>
												<span style="color:#aaa; font-size:12px;">—</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ( $reg_count > 0 ) : ?>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=rhg-exhibitions-list&view=detail&exhibition_id=' . $expo->id ) ); ?>" class="button button-small">View Registrants</a>
											<?php else : ?>
												<span style="color:#aaa; font-size:12px;">No registrations</span>
											<?php endif; ?>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( empty( $grouped ) ) : ?>
				<p style="text-align:center; padding:40px; color:#888;">No exhibitions found. Run a scrape from the Settings page first.</p>
			<?php endif; ?>

			<?php $this->render_copyright_footer(); ?>
		</div>

		<script>
		jQuery(function($) {
			// Event delegation on the stable parent table — works even if
			// rows are re-rendered. Plain show()/hide() (not slideUp/
			// slideDown) since the row being toggled is a <tr> — jQuery's
			// slide animation manipulates height directly, which doesn't
			// behave reliably on table rows across browsers. show()/hide()
			// correctly restores 'table-row' display for a <tr>.
			$('#rhg-cat-accordion').on('click', '.rhg-cat-header', function () {
				var $header = $(this);
				var $bodyRow = $('#' + $header.data('target'));
				var $icon    = $header.find('.rhg-cat-toggle-icon');
				var isOpen   = $bodyRow.is(':visible');

				if ( isOpen ) {
					$bodyRow.hide();
					$icon.text('▶');
					$header.removeClass('rhg-cat-open');
				} else {
					$bodyRow.show();
					$icon.text('▼');
					$header.addClass('rhg-cat-open');
				}
			});
			// All closed by default — admin opens the ones they want
		});
		</script>
		<?php
	}

	private function render_exhibition_detail( $exhibition_id ) {
		$exhibition = RHG_DB::get_exhibition_by_id( $exhibition_id );
		if ( ! $exhibition ) {
			echo '<div class="wrap"><p>Exhibition not found.</p></div>';
			return;
		}
		$registrations = RHG_DB::get_registrations_for_exhibition( $exhibition_id );
		?>
		<div class="wrap rhg-admin-wrap">
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rhg-exhibitions-list' ) ); ?>">&larr; Back to exhibitions</a>
			</p>
			<h1><?php echo esc_html( $exhibition->title ); ?></h1>
			<p class="rhg-expo-meta">
				📅 <?php echo esc_html( $exhibition->start_date ? date( 'd M Y', strtotime( $exhibition->start_date ) ) : '—' ); ?>
				&nbsp;&nbsp;📍 <?php echo esc_html( $exhibition->venue . ', ' . $exhibition->city ); ?>
				&nbsp;&nbsp;🏷️ <?php echo esc_html( $exhibition->category ); ?>
			</p>

			<h2 style="margin-top:24px;"><?php echo count( $registrations ); ?> Registrant(s)</h2>

			<table class="wp-list-table widefat fixed striped rhg-table">
				<thead>
					<tr>
						<th>Name</th>
						<th>Email</th>
						<th>Phone</th>
						<th>Job Title</th>
						<th>Country</th>
						<th>Town</th>
						<th>Registered On</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $registrations ) ) : ?>
						<tr><td colspan="7" style="text-align:center; padding:20px;">No registrations yet.</td></tr>
					<?php else : ?>
						<?php foreach ( $registrations as $r ) : ?>
							<tr>
								<td><?php echo esc_html( $r->full_name ); ?></td>
								<td><?php echo esc_html( $r->email ); ?></td>
								<td><?php echo esc_html( $r->phone ?: '—' ); ?></td>
								<td><?php echo esc_html( $r->job_title ); ?></td>
								<td><?php echo esc_html( $r->country ); ?></td>
								<td><?php echo esc_html( $r->town ); ?></td>
								<td><?php echo esc_html( date( 'd M Y, H:i', strtotime( $r->created_at ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php $this->render_copyright_footer(); ?>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* PAGE 3 — SETTINGS                                                   */
	/* ------------------------------------------------------------------ */

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$admin_email  = get_option( 'rhg_exh_admin_email',  get_option( 'admin_email' ) );
		$admin_email2 = get_option( 'rhg_exh_admin_email2', '' );
		$last_run     = get_option( 'rhg_exh_last_run' );
		$last_count   = get_option( 'rhg_exh_last_run_count' );
		?>
		<div class="wrap rhg-admin-wrap">
			<h1>HimoEXPO — Settings</h1>

			<?php settings_errors( 'rhg_exh_settings' ); ?>

			<?php
			$last_error  = get_option( 'rhg_exh_last_error' );
			$last_status = get_option( 'rhg_exh_last_http_status' );
			$last_length = get_option( 'rhg_exh_last_body_length' );
			if ( ! empty( $last_error ) ) : ?>
				<div class="notice notice-error" style="padding:16px; border-left-width:6px;">
					<p style="font-size:15px; margin:0 0 8px;"><strong>⚠️ Last scrape error — copy this and send it to support:</strong></p>
					<textarea readonly rows="4" style="width:100%; font-family:monospace; font-size:13px; background:#fff; padding:10px;"><?php echo esc_textarea( $last_error ); ?></textarea>
				</div>
			<?php elseif ( $last_status ) : ?>
				<div class="notice notice-info" style="padding:12px 16px;">
					<p><strong>Last successful fetch:</strong> HTTP <?php echo esc_html( $last_status ); ?>, <?php echo esc_html( number_format( (int) $last_length ) ); ?> bytes received.</p>
				</div>
			<?php endif; ?>

			<div class="notice notice-warning" style="padding:12px 16px;">
				<p><strong>Email delivery note:</strong> Install <strong>WP Mail SMTP</strong> or <strong>Easy WP SMTP</strong> and connect to Gmail/SendGrid for reliable email delivery. On a local XAMPP/localhost setup especially, PHP's built-in mail sending has no real mail server to talk to — it will silently fail for every outgoing email until an SMTP plugin is installed and connected to a real account.</p>
			</div>

			<?php
			$email_error = get_option( 'rhg_exh_email_last_error' );
			if ( ! empty( $email_error ) && is_array( $email_error ) ) :
			?>
				<div class="notice notice-error" style="padding:16px; border-left-width:6px;">
					<p style="font-size:15px; margin:0 0 8px;"><strong>⚠️ Last registration email failed to send — copy this and send it to support:</strong></p>
					<textarea readonly rows="3" style="width:100%; font-family:monospace; font-size:13px; background:#fff; padding:10px;"><?php echo esc_textarea( $email_error['time'] . ' — ' . $email_error['message'] ); ?></textarea>
				</div>
			<?php endif; ?>

			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'rhg_exh_settings_nonce' ); ?>
				<h2>Admin Notification Emails</h2>
				<p>Both addresses below will receive an email whenever a visitor registers for an exhibition.</p>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="rhg_exh_admin_email">Admin Email 1</label></th>
						<td>
							<input type="email" id="rhg_exh_admin_email" name="rhg_exh_admin_email"
								value="<?php echo esc_attr( $admin_email ); ?>" class="regular-text" />
							<p class="description">Primary notification address. Defaults to WordPress admin email.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rhg_exh_admin_email2">Admin Email 2</label></th>
						<td>
							<input type="email" id="rhg_exh_admin_email2" name="rhg_exh_admin_email2"
								value="<?php echo esc_attr( $admin_email2 ); ?>" class="regular-text" />
							<p class="description">Second notification address (optional). Leave blank to disable.</p>
						</td>
					</tr>
				</table>

				<p>
					<input type="submit" name="rhg_exh_send_test_email" class="button button-secondary" value="Send Test Email" />
					<span class="description" style="margin-left:8px;">Sends a quick test message to Admin Email 1 — the fastest way to check whether email sending works at all on this server.</span>
				</p>

				<hr />

				<h2>Category Management</h2>
				<p>These categories are used to automatically tag exhibitions when scraped, and appear as filter pills on your website. Keywords are comma-separated — the first category whose keywords match the exhibition title or description wins. The last row (no keywords) is the catch-all fallback.</p>
				<p><strong>After changing keywords, click "Run Scrape Now" below to re-categorise all exhibitions.</strong></p>
				<p>Each category has a default icon graphic. Upload your own image (JPG, PNG, WEBP, or SVG) to replace it on the website cards — tick "Remove" to revert back to the default icon.</p>

				<table class="wp-list-table widefat fixed" id="rhg-cat-table" style="margin-bottom:12px;">
					<thead>
						<tr>
							<th style="width:90px;">Image</th>
							<th style="width:22%">Category Name</th>
							<th>Keywords (comma-separated, case-insensitive)</th>
							<th style="width:170px;">Upload New Image</th>
							<th style="width:70px;">Reset</th>
							<th style="width:60px;">Delete</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( RHG_Categories::get_all() as $i => $cat ) :
							$current_image = ! empty( $cat['image'] ) ? $cat['image'] : RHG_Categories::get_image( $cat['name'] );
						?>
						<tr class="rhg-cat-row">
							<td>
								<img src="<?php echo esc_url( $current_image ); ?>" alt="" class="rhg-cat-thumb" />
								<input type="hidden" name="cat_image_existing[]" value="<?php echo esc_attr( $cat['image'] ?? '' ); ?>" />
							</td>
							<td>
								<input type="text" name="cat_name[]"
									value="<?php echo esc_attr( $cat['name'] ); ?>"
									class="regular-text" style="width:100%;" />
							</td>
							<td>
								<input type="text" name="cat_keywords[]"
									value="<?php echo esc_attr( $cat['keywords'] ); ?>"
									placeholder="e.g. solar,energy,green,wind"
									style="width:100%;" />
							</td>
							<td>
								<input type="file" name="cat_image[]" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="rhg-cat-file" />
							</td>
							<td style="text-align:center;">
								<label style="font-size:11px; display:flex; flex-direction:column; align-items:center; gap:2px;">
									<input type="checkbox" name="cat_image_remove[]" value="1" />
									Reset
								</label>
							</td>
							<td style="text-align:center;">
								<button type="button" class="button rhg-remove-cat" style="color:#e63950;">✕</button>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<button type="button" id="rhg-add-cat" class="button">+ Add Category</button>

				<p class="submit">
					<input type="submit" name="rhg_exh_save_settings" class="button button-primary" value="Save Settings" />
				</p>
			</form>

			<script>
			(function(){
				document.getElementById('rhg-add-cat').addEventListener('click', function(){
					var tbody = document.querySelector('#rhg-cat-table tbody');
					var row   = document.createElement('tr');
					row.className = 'rhg-cat-row';
					row.innerHTML = '<td><img src="<?php echo esc_url( RHG_EXH_URL . "assets/img/categories/other.svg" ); ?>" class="rhg-cat-thumb" alt="" /><input type="hidden" name="cat_image_existing[]" value="" /></td>'
						+ '<td><input type="text" name="cat_name[]" class="regular-text" style="width:100%;" placeholder="Category name" /></td>'
						+ '<td><input type="text" name="cat_keywords[]" style="width:100%;" placeholder="keyword1,keyword2,keyword3" /></td>'
						+ '<td><input type="file" name="cat_image[]" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="rhg-cat-file" /></td>'
						+ '<td style="text-align:center;"><label style="font-size:11px; display:flex; flex-direction:column; align-items:center; gap:2px;"><input type="checkbox" name="cat_image_remove[]" value="1" />Reset</label></td>'
						+ '<td style="text-align:center;"><button type="button" class="button rhg-remove-cat" style="color:#e63950;">✕</button></td>';
					tbody.appendChild(row);
				});
				document.addEventListener('click', function(e){
					if ( e.target && e.target.classList.contains('rhg-remove-cat') ) {
						e.target.closest('tr').remove();
					}
				});
				// Live preview: show the chosen file immediately, before saving
				document.addEventListener('change', function(e){
					if ( e.target && e.target.classList.contains('rhg-cat-file') && e.target.files && e.target.files[0] ) {
						var img = e.target.closest('tr').querySelector('.rhg-cat-thumb');
						var reader = new FileReader();
						reader.onload = function(ev){ img.src = ev.target.result; };
						reader.readAsDataURL(e.target.files[0]);
					}
				});
			})();
			</script>

			<hr />
			<h2>Exhibition Data</h2>
			<p>
				<?php if ( $last_run ) :
					$db_count        = get_option( 'rhg_exh_last_run_count', 0 );
					$processed_count = get_option( 'rhg_exh_last_run_processed', 0 );
				?>
					Last scrape: <strong><?php echo esc_html( date( 'd M Y, H:i', strtotime( $last_run ) ) ); ?></strong><br><br>
					📦 <strong>Rows processed from EventsEye:</strong> <?php echo (int) $processed_count; ?> <em>(includes header/nav rows and same-exhibition duplicates across pages)</em><br>
					✅ <strong>Unique exhibitions in your database:</strong> <strong style="color:#1d7a3c; font-size:15px;"><?php echo (int) $db_count; ?></strong> — this is the real number shown in the Exhibitions page
					<br><br><em>The difference between processed and unique is normal — EventsEye's HTML tables contain navigation rows that get filtered out during deduplication.</em>
				<?php else : ?>
					No scrape has run yet.
				<?php endif; ?>
			</p>
			<p>Runs automatically once a day.</p>
			<form method="post">
				<?php wp_nonce_field( 'rhg_exh_settings_nonce' ); ?>
				<input type="submit" name="rhg_exh_run_scrape_now" class="button button-secondary" value="Run Scrape Now" />
			</form>

			<hr />

			<h2>Shortcode</h2>
			<p>Add this shortcode to any page or post to display the exhibition listing:</p>
			<code>[rhg_exhibitions]</code>

			<?php $this->render_copyright_footer(); ?>
		</div>
		<?php
	}

	/**
	 * Copyright footer shown at the bottom of every HimoEXPO admin page.
	 */
	private function render_copyright_footer() {
		?>
		<p class="rhg-copyright-footer">&copy; <?php echo esc_html( date( 'Y' ) ); ?> HimoEXPO(h.maghsoudloo). All rights reserved.</p>
		<?php
	}
}
