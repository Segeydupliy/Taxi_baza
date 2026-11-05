<?php

namespace TaxiRental\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	public static function init(): void {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu',                             [ self::class, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts',                  [ self::class, 'enqueue_assets' ] );
		add_filter( 'parent_file',                            [ self::class, 'fix_parent_file' ] );
		add_filter( 'submenu_file',                           [ self::class, 'fix_submenu_file' ] );
		add_filter( 'manage_car_listing_posts_columns',       [ self::class, 'columns' ] );
		add_action( 'manage_car_listing_posts_custom_column', [ self::class, 'column_content' ], 10, 2 );
		add_filter( 'post_row_actions',                       [ self::class, 'row_actions' ], 10, 2 );
		add_action( 'admin_action_tb_approve',                [ self::class, 'action_approve' ] );
		add_action( 'admin_action_tb_reject',                 [ self::class, 'action_reject' ] );
		add_action( 'admin_action_tb_pending',                [ self::class, 'action_pending' ] );
		add_action( 'admin_action_tb_delete',                 [ self::class, 'action_delete' ] );
		add_action( 'add_meta_boxes',                         [ self::class, 'register_meta_boxes' ] );
		add_action( 'save_post_car_listing',                  [ self::class, 'save_meta' ] );
		add_action( 'admin_notices',                          [ self::class, 'notices' ] );
		add_action( 'admin_head-post.php',                    [ self::class, 'inject_back_button' ] );
		add_action( 'admin_head-post-new.php',                [ self::class, 'inject_back_button' ] );
		add_action( 'admin_init',                             [ self::class, 'maybe_acf_form_head' ] );
		add_action( 'admin_action_tb_save_term',              [ self::class, 'action_save_term' ] );
		add_action( 'admin_action_tb_delete_term',            [ self::class, 'action_delete_term' ] );
		add_action( 'admin_action_tb_save_user',              [ self::class, 'action_save_user' ] );
		add_action( 'wp_ajax_tb_fa_filter_listings',          [ self::class, 'ajax_fa_filter_listings' ] );
		add_action( 'wp_ajax_tb_fa_filter_users',             [ self::class, 'ajax_fa_filter_users' ] );
		add_action( 'wp_ajax_tb_fa_publish',                  [ self::class, 'ajax_fa_publish' ] );
		add_action( 'wp_ajax_tb_fa_reject',                   [ self::class, 'ajax_fa_reject' ] );
		add_action( 'wp_ajax_tb_fa_archive',                  [ self::class, 'ajax_fa_archive' ] );
		add_action( 'wp_ajax_tb_fa_pending',                  [ self::class, 'ajax_fa_pending' ] );
		add_action( 'wp_ajax_tb_fa_delete',                   [ self::class, 'ajax_fa_delete' ] );
		add_action( 'wp_ajax_tb_fa_block_user',               [ self::class, 'ajax_fa_block_user' ] );
		add_action( 'wp_ajax_tb_fa_unblock_user',             [ self::class, 'ajax_fa_unblock_user' ] );
		add_action( 'wp_ajax_tb_fa_save_user',                [ self::class, 'ajax_fa_save_user' ] );
		add_action( 'wp_ajax_tb_fa_save_city',                [ self::class, 'ajax_fa_save_city' ] );
		add_action( 'wp_ajax_tb_fa_delete_city',              [ self::class, 'ajax_fa_delete_city' ] );
		add_action( 'wp_ajax_tb_fa_save_category',            [ self::class, 'ajax_fa_save_category' ] );
		add_action( 'wp_ajax_tb_fa_delete_category',          [ self::class, 'ajax_fa_delete_category' ] );
		add_action( 'wp_ajax_tb_fa_save_listing',             [ self::class, 'ajax_fa_save_listing' ] );
		add_action( 'wp_ajax_tb_fa_save_post',                [ self::class, 'ajax_fa_save_post' ] );
		add_action( 'wp_ajax_tb_fa_delete_post',              [ self::class, 'ajax_fa_delete_post' ] );
		add_action( 'wp_ajax_tb_fa_save_blog_cat',            [ self::class, 'ajax_fa_save_blog_cat' ] );
		add_action( 'wp_ajax_tb_fa_delete_blog_cat',          [ self::class, 'ajax_fa_delete_blog_cat' ] );
		// User-facing add/edit listing (car_owner, fleet, administrator, editor).
		add_action( 'wp_ajax_tb_submit_listing',              [ self::class, 'ajax_tb_submit_listing' ] );
		add_action( 'admin_action_tb_run_seed',               [ self::class, 'action_run_seed' ] );
		add_action( 'admin_action_tb_update_post',            [ self::class, 'action_update_post' ] );
	}

	public static function register_menus(): void {
		$icon = 'data:image/svg+xml;base64,' . base64_encode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#a7aaad"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>'
		);

		add_menu_page(
			esc_html__( 'TaxiBaza', 'taxi-rental' ),
			esc_html__( 'TaxiBaza', 'taxi-rental' ),
			'manage_options',
			'taxibaza',
			[ self::class, 'page_dashboard' ],
			$icon,
			3
		);

		add_submenu_page(
			'taxibaza',
			esc_html__( 'Overview', 'taxi-rental' ),
			esc_html__( 'Overview', 'taxi-rental' ),
			'manage_options',
			'taxibaza',
			[ self::class, 'page_dashboard' ]
		);

		add_submenu_page(
			'taxibaza',
			esc_html__( 'All Listings', 'taxi-rental' ),
			esc_html__( 'All Listings', 'taxi-rental' ),
			'manage_options',
			'taxibaza-listings',
			[ self::class, 'page_listings' ]
		);

		add_submenu_page(
			'taxibaza',
			esc_html__( 'Add Listing', 'taxi-rental' ),
			esc_html__( '+ New Listing', 'taxi-rental' ),
			'manage_options',
			'taxibaza-listing-edit',
			[ self::class, 'page_listing_edit' ]
		);

		add_submenu_page(
			'taxibaza',
			esc_html__( 'Cities', 'taxi-rental' ),
			esc_html__( 'Cities', 'taxi-rental' ),
			'manage_options',
			'taxibaza-cities',
			[ self::class, 'page_cities' ]
		);

		add_submenu_page(
			'taxibaza',
			esc_html__( 'Platforms', 'taxi-rental' ),
			esc_html__( 'Platforms', 'taxi-rental' ),
			'manage_options',
			'taxibaza-platforms',
			[ self::class, 'page_platforms' ]
		);

		add_submenu_page(
			'taxibaza',
			esc_html__( 'Ride Categories', 'taxi-rental' ),
			esc_html__( 'Ride Categories', 'taxi-rental' ),
			'manage_options',
			'taxibaza-categories',
			[ self::class, 'page_categories' ]
		);

		add_submenu_page(
			'taxibaza',
			esc_html__( 'Makes & Models', 'taxi-rental' ),
			esc_html__( 'Makes & Models', 'taxi-rental' ),
			'manage_options',
			'taxibaza-car-makes',
			[ self::class, 'page_car_makes' ]
		);

		add_submenu_page(
			'taxibaza',
			esc_html__( 'Owners & Fleets', 'taxi-rental' ),
			esc_html__( 'Users', 'taxi-rental' ),
			'manage_options',
			'taxibaza-users',
			[ self::class, 'page_users' ]
		);

		add_submenu_page(
			'taxibaza',
			esc_html__( 'Tools', 'taxi-rental' ),
			esc_html__( 'Tools', 'taxi-rental' ),
			'manage_options',
			'taxibaza-tools',
			[ self::class, 'page_tools' ]
		);

		// Hidden utility pages (registered for routing, removed from sidebar display).
		add_submenu_page( 'taxibaza', '', '', 'manage_options', 'taxibaza-user-edit', [ self::class, 'page_user_edit' ] );

		// Remove the default WP "Car Listings" CPT menu and the empty hidden items from sidebar.
		add_action( 'admin_menu', [ self::class, 'remove_default_cpt_menu' ], 999 );
	}

	public static function remove_default_cpt_menu(): void {
		remove_menu_page( 'edit.php?post_type=car_listing' );
	}

	private const TB_TAXONOMIES = [ 'city', 'platform', 'ride_category', 'car_make' ];

	public static function fix_parent_file( string $parent_file ): string {
		global $current_screen;
		if ( ! $current_screen ) {
			return $parent_file;
		}
		if ( 'car_listing' === $current_screen->post_type ) {
			return 'taxibaza';
		}
		// Fallback for taxonomy pages still reached via native WP URLs.
		if ( in_array( $current_screen->taxonomy ?? '', self::TB_TAXONOMIES, true ) ) {
			return 'taxibaza';
		}
		// Hidden utility pages registered under taxibaza.
		$page = sanitize_key( $_GET['page'] ?? '' );
		if ( in_array( $page, [ 'taxibaza-listing-edit', 'taxibaza-user-edit' ], true ) ) {
			return 'taxibaza';
		}
		return $parent_file;
	}

	public static function fix_submenu_file( ?string $submenu_file ): ?string {
		global $current_screen, $pagenow;
		if ( ! $current_screen ) {
			return $submenu_file;
		}

		// car_listing post type (native post.php / post-new.php — fallback if accessed directly).
		if ( 'car_listing' === $current_screen->post_type ) {
			if ( 'post-new.php' === $pagenow ) {
				return 'taxibaza-listing-edit';
			}
			return 'taxibaza-listings';
		}

		// Hidden custom pages — keep parent submenu highlighted.
		$page = sanitize_key( $_GET['page'] ?? '' );
		if ( 'taxibaza-listing-edit' === $page ) {
			$action = sanitize_key( $_GET['action'] ?? 'new' );
			return 'new' === $action ? 'taxibaza-listing-edit' : 'taxibaza-listings';
		}
		if ( 'taxibaza-user-edit' === $page ) {
			return 'taxibaza-users';
		}

		return $submenu_file;
	}

	public static function enqueue_assets( string $hook ): void {
		$screen = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$tb_screens = [
			'edit-car_listing', 'car_listing',
			'edit-city', 'city',
			'edit-platform', 'platform',
			'edit-ride_category', 'ride_category',
		];

		$is_car_listing_screen = in_array( $hook, [
			'toplevel_page_taxibaza',
			'taxibaza_page_taxibaza-users',
			'taxibaza_page_taxibaza-listings',
			'taxibaza_page_taxibaza-cities',
			'taxibaza_page_taxibaza-platforms',
			'taxibaza_page_taxibaza-categories',
			'taxibaza_page_taxibaza-listing-edit',
			'taxibaza_page_taxibaza-user-edit',
		], true )
		|| in_array( $screen_id, $tb_screens, true );

		if ( ! $is_car_listing_screen ) {
			return;
		}

		if ( in_array( $hook, [
			'taxibaza_page_taxibaza-cities',
			'taxibaza_page_taxibaza-platforms',
			'taxibaza_page_taxibaza-categories',
		], true ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_style(
			'taxi-rental-admin',
			TAXI_RENTAL_PLUGIN_URL . 'assets/css/admin.css',
			[],
			TAXI_RENTAL_VERSION
		);

		wp_enqueue_script(
			'taxi-rental-admin',
			TAXI_RENTAL_PLUGIN_URL . 'assets/js/admin.js',
			[],
			TAXI_RENTAL_VERSION,
			true
		);

		global $pagenow;
		wp_localize_script( 'taxi-rental-admin', 'tbAdminCfg', [
			'screen'  => $screen_id,
			'pagenow' => $pagenow ?? '',
			'labels'  => [
				'edit-city'          => __( 'Cities', 'taxi-rental' ),
				'city'               => __( 'Edit City', 'taxi-rental' ),
				'edit-platform'      => __( 'Platforms', 'taxi-rental' ),
				'platform'           => __( 'Edit Platform', 'taxi-rental' ),
				'edit-ride_category' => __( 'Ride Categories', 'taxi-rental' ),
				'ride_category'      => __( 'Edit Category', 'taxi-rental' ),
			],
		] );
	}

	/**
	 * @param string[] $columns
	 * @return string[]
	 */
	public static function columns( array $columns ): array {
		$date = $columns['date'] ?? esc_html__( 'Date', 'taxi-rental' );
		unset( $columns['date'] );

		return array_merge(
			array_slice( $columns, 0, 2 ),
			[
				'tb_status'   => esc_html__( 'Status', 'taxi-rental' ),
				'tb_owner'    => esc_html__( 'Owner', 'taxi-rental' ),
				'tb_city'     => esc_html__( 'City', 'taxi-rental' ),
				'tb_platform' => esc_html__( 'Platform', 'taxi-rental' ),
				'tb_price'    => esc_html__( '€/week', 'taxi-rental' ),
				'date'        => $date,
			]
		);
	}

	public static function column_content( string $column, int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		switch ( $column ) {
			case 'tb_status':
				$status = $post->post_status;
				$map    = [
					'publish'  => [ 'label' => esc_html__( 'Published', 'taxi-rental' ), 'class' => 'tb-badge--green' ],
					'pending'  => [ 'label' => esc_html__( 'Pending', 'taxi-rental' ),  'class' => 'tb-badge--yellow' ],
					'rejected' => [ 'label' => esc_html__( 'Rejected', 'taxi-rental' ),    'class' => 'tb-badge--red' ],
					'archived' => [ 'label' => esc_html__( 'Archive', 'taxi-rental' ),        'class' => 'tb-badge--gray' ],
					'draft'    => [ 'label' => esc_html__( 'Draft', 'taxi-rental' ),     'class' => 'tb-badge--gray' ],
				];
				$info   = $map[ $status ] ?? [ 'label' => esc_html( $status ), 'class' => 'tb-badge--gray' ];

				printf(
					'<span class="tb-badge %s">%s</span>',
					esc_attr( $info['class'] ),
					esc_html( $info['label'] )
				);

				$reason = get_post_meta( $post_id, '_rejection_reason', true );
				if ( 'rejected' === $status && $reason ) {
					printf( '<br><small class="tb-muted">%s</small>', esc_html( $reason ) );
				}
				break;

			case 'tb_owner':
				$user = get_user_by( 'id', (int) $post->post_author );
				if ( $user ) {
					printf(
						'<a href="%s">%s</a>',
						esc_url( get_edit_user_link( $user->ID ) ),
						esc_html( $user->display_name )
					);
				}
				break;

			case 'tb_city':
				$terms = get_the_terms( $post_id, 'city' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
				} else {
					echo '<span class="tb-muted">—</span>';
				}
				break;

			case 'tb_platform':
				$terms = get_the_terms( $post_id, 'platform' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
				} else {
					echo '<span class="tb-muted">—</span>';
				}
				break;

			case 'tb_price':
				$price = get_post_meta( $post_id, 'price_week', true );
				if ( $price ) {
					printf( '€%s', esc_html( number_format( (float) $price, 0 ) ) );
				} else {
					echo '<span class="tb-muted">—</span>';
				}
				break;
		}
	}

	/**
	 * @param string[]  $actions
	 * @param \WP_Post  $post
	 * @return string[]
	 */
	public static function row_actions( array $actions, \WP_Post $post ): array {
		if ( 'car_listing' !== $post->post_type || ! current_user_can( 'manage_options' ) ) {
			return $actions;
		}

		if ( 'publish' !== $post->post_status ) {
			$actions['tb_approve'] = sprintf(
				'<a href="%s" class="tb-action-approve">%s</a>',
				esc_url( wp_nonce_url(
					admin_url( 'admin.php?action=tb_approve&post=' . $post->ID ),
					'tb_approve_' . $post->ID
				) ),
				esc_html__( '✓ Publish', 'taxi-rental' )
			);
		}

		if ( 'rejected' !== $post->post_status ) {
			$actions['tb_reject'] = sprintf(
				'<a href="%s" class="tb-action-reject" data-nonce-url="%s">%s</a>',
				'#',
				esc_url( wp_nonce_url(
					admin_url( 'admin.php?action=tb_reject&post=' . $post->ID ),
					'tb_reject_' . $post->ID
				) ),
				esc_html__( '✗ Reject', 'taxi-rental' )
			);
		}

		if ( 'pending' !== $post->post_status ) {
			$actions['tb_pending'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url(
					admin_url( 'admin.php?action=tb_pending&post=' . $post->ID ),
					'tb_pending_' . $post->ID
				) ),
				esc_html__( '↩ Return to Review', 'taxi-rental' )
			);
		}

		return $actions;
	}

	public static function action_approve(): void {
		$post_id = absint( $_GET['post'] ?? 0 );
		if ( ! $post_id || ! check_admin_referer( 'tb_approve_' . $post_id ) ) {
			wp_die( esc_html__( 'Security error.', 'taxi-rental' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'taxi-rental' ) );
		}

		wp_update_post( [ 'ID' => $post_id, 'post_status' => 'publish' ] );
		self::redirect_after_action( 'approved' );
	}

	public static function action_reject(): void {
		$post_id = absint( $_GET['post'] ?? 0 );
		if ( ! $post_id || ! check_admin_referer( 'tb_reject_' . $post_id ) ) {
			wp_die( esc_html__( 'Security error.', 'taxi-rental' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'taxi-rental' ) );
		}

		$reason = sanitize_textarea_field( wp_unslash( $_GET['rejection_reason'] ?? '' ) );
		// Save the reason first: the status change sends the e-mail that includes it.
		update_post_meta( $post_id, '_rejection_reason', $reason );
		wp_update_post( [ 'ID' => $post_id, 'post_status' => 'rejected' ] );
		self::redirect_after_action( 'rejected' );
	}

	public static function action_pending(): void {
		$post_id = absint( $_GET['post'] ?? 0 );
		if ( ! $post_id || ! check_admin_referer( 'tb_pending_' . $post_id ) ) {
			wp_die( esc_html__( 'Security error.', 'taxi-rental' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'taxi-rental' ) );
		}

		wp_update_post( [ 'ID' => $post_id, 'post_status' => 'pending' ] );
		self::redirect_after_action( 'pending' );
	}

	public static function action_delete(): void {
		$post_id = absint( $_GET['post'] ?? 0 );
		if ( ! $post_id || ! check_admin_referer( 'tb_delete_' . $post_id ) ) {
			wp_die( esc_html__( 'Security error.', 'taxi-rental' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'taxi-rental' ) );
		}
		wp_trash_post( $post_id );
		self::redirect_after_action( 'deleted' );
	}

	// ── Front-end admin AJAX handlers ─────────────────────────────────────────

	/**
	 * Re-run the Adverts tab's listing query and return the results partial
	 * as HTML — powers AJAX search/sort/status-pill filtering without a full
	 * page reload (and without ever touching WordPress's reserved `s` query
	 * var, which was causing 404s when used directly in the page URL).
	 */
	public static function ajax_fa_filter_listings(): void {
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}
		if ( ! function_exists( 'get_field' ) ) {
			wp_send_json_error( [ 'message' => __( 'ACF Pro is not activated.', 'taxi-rental' ) ] );
		}

		$status_filter = sanitize_key( wp_unslash( $_POST['status'] ?? 'all' ) );
		$search_q      = sanitize_text_field( wp_unslash( $_POST['fa_s'] ?? '' ) );
		$sort_filter   = sanitize_key( wp_unslash( $_POST['sort'] ?? 'date_desc' ) );
		$paged         = max( 1, absint( wp_unslash( $_POST['paged'] ?? 1 ) ) );
		$panel_path    = sanitize_text_field( wp_unslash( $_POST['panel_path'] ?? '/panel/' ) );
		$per_page      = 20;

		$allowed_statuses = [ 'all', 'publish', 'pending', 'rejected', 'archived', 'draft' ];
		if ( ! in_array( $status_filter, $allowed_statuses, true ) ) {
			$status_filter = 'all';
		}
		$allowed_sorts = [ 'date_desc', 'date_asc', 'price_desc', 'price_asc' ];
		if ( ! in_array( $sort_filter, $allowed_sorts, true ) ) {
			$sort_filter = 'date_desc';
		}

		$panel_url = home_url( '/' . ltrim( $panel_path, '/' ) );

		// Same helper the template uses, so the stats line this handler sends
		// back matches the list it sends back with it (wp_count_posts() is not
		// language-filtered, WP_Query is — see tb_listing_count()).
		$cnt_all  = tb_listing_count( [ 'publish', 'pending', 'draft', 'rejected', 'archived' ] );
		$cnt_pub  = tb_listing_count( [ 'publish' ] );
		$cnt_pend = tb_listing_count( [ 'pending' ] );
		$cnt_arch = tb_listing_count( [ 'archived' ] );
		$cnt_rej  = tb_listing_count( [ 'rejected' ] );

		$post_statuses = 'all' === $status_filter
			? [ 'publish', 'pending', 'draft', 'rejected', 'archived' ]
			: [ $status_filter ];

		$query_args = [
			'post_type'      => 'car_listing',
			'post_status'    => $post_statuses,
			'posts_per_page' => $per_page,
			'paged'          => $paged,
		];
		switch ( $sort_filter ) {
			case 'date_asc':
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'ASC';
				break;
			case 'price_asc':
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = 'price_week';
				$query_args['order']    = 'ASC';
				break;
			case 'price_desc':
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = 'price_week';
				$query_args['order']    = 'DESC';
				break;
			default: // date_desc
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'DESC';
				break;
		}
		if ( $search_q ) {
			$query_args['s'] = $search_q;
		}
		$q = new \WP_Query( $query_args );

		$list_base = add_query_arg( 'tab', 'listings', remove_query_arg( [ 'edit', 'paged' ], $panel_url ) );
		$edit_url  = static fn( int $id ): string => esc_url( add_query_arg( 'edit', $id, $list_base ) );

		$sort_base = $list_base;
		if ( 'all' !== $status_filter ) {
			$sort_base = add_query_arg( 'status', $status_filter, $sort_base );
		}
		if ( $search_q ) {
			$sort_base = add_query_arg( 'fa_s', $search_q, $sort_base );
		}
		$sort_url = static fn( string $sk ): string => esc_url( add_query_arg( 'sort', $sk, remove_query_arg( 'paged', $sort_base ) ) );

		$badge_cfg = [
			'publish'  => [ 'label' => tb_opt( 'tb_fa_ae_st_publish', 'Zverejnené' ),  'bg' => '#02f797' ],
			'pending'  => [ 'label' => tb_opt( 'tb_fa_ae_st_pending', 'V moderácii' ), 'bg' => '#f4d597' ],
			'rejected' => [ 'label' => tb_opt( 'tb_fa_ae_st_rejected', 'Zamietnuté' ), 'bg' => '#ffbdbd' ],
			'archived' => [ 'label' => tb_opt( 'tb_fa_ae_st_archived', 'Archivované' ),'bg' => '#e5e5e5' ],
			'draft'    => [ 'label' => tb_opt( 'tb_fa_ae_st_draft', 'Koncept' ),    'bg' => '#e5e5e5' ],
		];

		ob_start();
		require get_template_directory() . '/template-parts/front-admin/partial-adverts-results.php';
		$html = ob_get_clean();

		$stats = sprintf(
			/* translators: %1$d total %2$d published %3$d pending %4$d archived %5$d rejected */
			tb_opt( 'tb_fa_adv_stats', 'Celkom %1$d, zverejnených %2$d, v moderácii %3$d, archivovaných %4$d, zamietnutých %5$d' ),
			$cnt_all, $cnt_pub, $cnt_pend, $cnt_arch, $cnt_rej
		);

		wp_send_json_success( [
			'html'  => $html,
			'stats' => esc_html( $stats ),
			'total' => (int) $q->found_posts,
		] );
	}

	/**
	 * Re-run the Users tab's query and return the results partial as HTML —
	 * powers AJAX search/sort/status-pill/city/type filtering without a full
	 * page reload (and without the reserved `s` query var — see fa_s).
	 */
	public static function ajax_fa_filter_users(): void {
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}

		$status_filter = sanitize_key( wp_unslash( $_POST['status'] ?? '' ) );
		$search_q      = sanitize_text_field( wp_unslash( $_POST['fa_s'] ?? '' ) );
		$sort_filter   = sanitize_key( wp_unslash( $_POST['sort'] ?? 'registered_desc' ) );
		$paged         = max( 1, absint( wp_unslash( $_POST['paged'] ?? 1 ) ) );
		$panel_path    = sanitize_text_field( wp_unslash( $_POST['panel_path'] ?? '/panel/' ) );
		$per_page      = 20;

		$allowed_sorts = [ 'registered_desc', 'registered_asc', 'name_asc', 'name_desc' ];
		if ( ! in_array( $sort_filter, $allowed_sorts, true ) ) {
			$sort_filter = 'registered_desc';
		}

		$city_filter = absint( wp_unslash( $_POST['city'] ?? 0 ) );
		$type_filter = sanitize_key( wp_unslash( $_POST['utype'] ?? '' ) );
		if ( ! in_array( $type_filter, [ 'car_owner', 'fleet', 'driver' ], true ) ) {
			$type_filter = '';
		}

		$panel_url = home_url( '/' . ltrim( $panel_path, '/' ) );

		$base_count_args = [ 'role__in' => [ 'car_owner', 'fleet', 'driver' ], 'fields' => 'ID', 'number' => 0 ];
		$total_count     = count( get_users( $base_count_args ) );
		$blocked_count   = count( get_users( array_merge( $base_count_args, [
			'meta_query' => [ [ 'key' => 'tb_user_blocked', 'value' => '1', 'compare' => '=' ] ],
		] ) ) );
		$active_count = $total_count - $blocked_count;

		$query_args = [
			'role__in'    => $type_filter ? [ $type_filter ] : [ 'car_owner', 'fleet', 'driver' ],
			'number'      => $per_page,
			'paged'       => $paged,
			'count_total' => true,
		];
		switch ( $sort_filter ) {
			case 'registered_asc':
				$query_args['orderby'] = 'registered';
				$query_args['order']   = 'ASC';
				break;
			case 'name_asc':
				$query_args['orderby'] = 'display_name';
				$query_args['order']   = 'ASC';
				break;
			case 'name_desc':
				$query_args['orderby'] = 'display_name';
				$query_args['order']   = 'DESC';
				break;
			default: // registered_desc
				$query_args['orderby'] = 'registered';
				$query_args['order']   = 'DESC';
				break;
		}

		if ( $city_filter ) {
			global $wpdb;
			$city_author_ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT DISTINCT p.post_author
				 FROM {$wpdb->term_taxonomy} tt
				 JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
				 JOIN {$wpdb->posts} p ON p.ID = tr.object_id
				 WHERE tt.taxonomy = %s AND tt.term_id = %d
				   AND p.post_type = %s
				   AND p.post_status IN ( 'publish', 'pending', 'draft', 'archived', 'rejected' )",
				'city',
				$city_filter,
				'car_listing'
			) );
			$city_author_ids        = array_map( 'intval', (array) $city_author_ids );
			$query_args['include']  = $city_author_ids ?: [ 0 ];
		}

		if ( $search_q ) {
			$query_args['search']         = '*' . $search_q . '*';
			$query_args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
		}
		if ( 'blocked' === $status_filter ) {
			$query_args['meta_query'] = [
				[ 'key' => 'tb_user_blocked', 'value' => '1', 'compare' => '=' ],
			];
		} elseif ( 'active' === $status_filter ) {
			$query_args['meta_query'] = [
				'relation' => 'OR',
				[ 'key' => 'tb_user_blocked', 'compare' => 'NOT EXISTS' ],
				[ 'key' => 'tb_user_blocked', 'value' => '0', 'compare' => '=', 'type' => 'NUMERIC' ],
			];
		}

		$user_query  = new \WP_User_Query( $query_args );
		$users       = $user_query->get_results();
		$total_found = (int) $user_query->get_total();
		$num_pages   = (int) ceil( $total_found / $per_page );

		$extra_qs = array_filter( [
			'city'  => $city_filter ?: null,
			'utype' => $type_filter ?: null,
			'sort'  => 'registered_desc' !== $sort_filter ? $sort_filter : null,
		] );

		$role_type_labels = [
			'car_owner' => tb_opt( 'tb_fa_ue_type_owner', 'Fyzická osoba' ),
			'fleet'     => tb_opt( 'tb_fa_ue_type_fleet', 'Flotila' ),
			'driver'    => tb_opt( 'tb_fa_ue_type_driver', 'Vodič' ),
		];

		ob_start();
		require get_template_directory() . '/template-parts/front-admin/partial-users-results.php';
		$html = ob_get_clean();

		$stats = sprintf(
			/* translators: 1: total 2: active 3: blocked */
			tb_opt( 'tb_fa_usr_stats', 'Celkom %1$d, aktívnych %2$d, zablokovaných %3$d' ),
			$total_count, $active_count, $blocked_count
		);

		wp_send_json_success( [
			'html'  => $html,
			'stats' => esc_html( $stats ),
			'total' => $total_found,
		] );
	}

	public static function ajax_fa_publish(): void {
		$post_id = absint( wp_unslash( $_POST['post_id'] ?? 0 ) );
		$nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! $post_id || ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}
		$result = wp_update_post( [ 'ID' => $post_id, 'post_status' => 'publish' ] );
		if ( is_wp_error( $result ) || ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Update error.', 'taxi-rental' ) ] );
		}
		wp_send_json_success( [ 'message' => __( 'Published.', 'taxi-rental' ), 'status' => 'publish' ] );
	}

	public static function ajax_fa_reject(): void {
		$post_id = absint( wp_unslash( $_POST['post_id'] ?? 0 ) );
		$nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! $post_id || ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}
		$reason = sanitize_textarea_field( wp_unslash( $_POST['rejection_reason'] ?? '' ) );
		// Save the reason first: the status change sends the e-mail that includes it.
		update_post_meta( $post_id, '_rejection_reason', $reason );
		$result = wp_update_post( [ 'ID' => $post_id, 'post_status' => 'rejected' ] );
		if ( is_wp_error( $result ) || ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Update error.', 'taxi-rental' ) ] );
		}
		wp_send_json_success( [ 'message' => __( 'Rejected.', 'taxi-rental' ), 'status' => 'rejected' ] );
	}

	public static function ajax_fa_archive(): void {
		$post_id = absint( wp_unslash( $_POST['post_id'] ?? 0 ) );
		$nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! $post_id || ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}
		$result = wp_update_post( [ 'ID' => $post_id, 'post_status' => 'archived' ] );
		if ( is_wp_error( $result ) || ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Update error.', 'taxi-rental' ) ] );
		}
		wp_send_json_success( [ 'message' => __( 'Archived.', 'taxi-rental' ), 'status' => 'archived' ] );
	}

	public static function ajax_fa_pending(): void {
		$post_id = absint( wp_unslash( $_POST['post_id'] ?? 0 ) );
		$nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! $post_id || ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}
		$result = wp_update_post( [ 'ID' => $post_id, 'post_status' => 'pending' ] );
		if ( is_wp_error( $result ) || ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Update error.', 'taxi-rental' ) ] );
		}
		wp_send_json_success( [ 'message' => __( 'Moved to moderation.', 'taxi-rental' ), 'status' => 'pending' ] );
	}

	public static function ajax_fa_delete(): void {
		$post_id = absint( wp_unslash( $_POST['post_id'] ?? 0 ) );
		$nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! $post_id || ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}
		$result = wp_trash_post( $post_id );
		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Deletion error.', 'taxi-rental' ) ] );
		}
		wp_send_json_success( [ 'message' => __( 'Deleted.', 'taxi-rental' ) ] );
	}

	private static function redirect_after_action( string $msg ): void {
		$back = admin_url( 'admin.php?page=taxibaza-listings&tb_msg=' . $msg );
		wp_safe_redirect( $back );
		exit;
	}

	public static function register_meta_boxes(): void {
		add_meta_box(
			'tb-moderation',
			esc_html__( 'Moderation', 'taxi-rental' ),
			[ self::class, 'meta_box_moderation' ],
			'car_listing',
			'side',
			'high'
		);
	}

	public static function meta_box_moderation( \WP_Post $post ): void {
		wp_nonce_field( 'tb_moderation_' . $post->ID, 'tb_moderation_nonce' );

		$status = $post->post_status;
		$reason = (string) get_post_meta( $post->ID, '_rejection_reason', true );

		$map  = [
			'publish'  => [ 'label' => __( 'Published', 'taxi-rental' ), 'class' => 'tb-badge--green' ],
			'pending'  => [ 'label' => __( 'Pending', 'taxi-rental' ),  'class' => 'tb-badge--yellow' ],
			'rejected' => [ 'label' => __( 'Rejected', 'taxi-rental' ),    'class' => 'tb-badge--red' ],
			'archived' => [ 'label' => __( 'Archive', 'taxi-rental' ),        'class' => 'tb-badge--gray' ],
			'draft'    => [ 'label' => __( 'Draft', 'taxi-rental' ),     'class' => 'tb-badge--gray' ],
		];
		$info = $map[ $status ] ?? [ 'label' => $status, 'class' => 'tb-badge--gray' ];
		?>
		<div class="tb-meta-box">
			<p class="tb-meta-status-row">
				<strong><?php esc_html_e( 'Status:', 'taxi-rental' ); ?></strong>
				<span class="tb-badge <?php echo esc_attr( $info['class'] ); ?>"><?php echo esc_html( $info['label'] ); ?></span>
			</p>

			<div class="tb-meta-actions">
				<?php if ( 'publish' !== $status ) : ?>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?action=tb_approve&post=' . $post->ID ), 'tb_approve_' . $post->ID ) ); ?>" class="button button-primary tb-btn-approve">
						<?php esc_html_e( '✓ Publish', 'taxi-rental' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( 'rejected' !== $status ) : ?>
					<a href="#" class="button tb-btn-reject tb-action-reject" data-nonce-url="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?action=tb_reject&post=' . $post->ID ), 'tb_reject_' . $post->ID ) ); ?>">
						<?php esc_html_e( '✗ Reject', 'taxi-rental' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<div class="tb-meta-reason <?php echo 'rejected' !== $status ? 'tb-hidden' : ''; ?>">
				<label for="tb_rejection_reason">
					<strong><?php esc_html_e( 'Rejection reason:', 'taxi-rental' ); ?></strong>
				</label>
				<textarea id="tb_rejection_reason" name="tb_rejection_reason" rows="3" class="widefat" style="margin-top:4px"><?php echo esc_textarea( $reason ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * @param int       $post_id
	 */
	public static function save_meta( int $post_id ): void {
		if ( ! isset( $_POST['tb_moderation_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['tb_moderation_nonce'] ) ),
			'tb_moderation_' . $post_id
		) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['tb_rejection_reason'] ) ) {
			update_post_meta(
				$post_id,
				'_rejection_reason',
				sanitize_textarea_field( wp_unslash( $_POST['tb_rejection_reason'] ) )
			);
		}
	}

	public static function notices(): void {
		$msg = sanitize_key( $_GET['tb_msg'] ?? '' );
		if ( ! $msg ) {
			return;
		}

		$messages = [
			'approved'     => [ 'type' => 'success', 'text' => __( 'Listing published.', 'taxi-rental' ) ],
			'rejected'     => [ 'type' => 'warning', 'text' => __( 'Listing rejected.', 'taxi-rental' ) ],
			'pending'      => [ 'type' => 'info',    'text' => __( 'Listing returned to review.', 'taxi-rental' ) ],
			'deleted'      => [ 'type' => 'success', 'text' => __( 'Listing deleted.', 'taxi-rental' ) ],
			'saved'        => [ 'type' => 'success', 'text' => __( 'Listing saved.', 'taxi-rental' ) ],
			'created'      => [ 'type' => 'success', 'text' => __( 'Listing created.', 'taxi-rental' ) ],
			'term_added'   => [ 'type' => 'success', 'text' => __( 'Entry added.', 'taxi-rental' ) ],
			'term_saved'   => [ 'type' => 'success', 'text' => __( 'Entry saved.', 'taxi-rental' ) ],
			'term_deleted' => [ 'type' => 'success', 'text' => __( 'Entry deleted.', 'taxi-rental' ) ],
			'term_error'   => [ 'type' => 'error',   'text' => __( 'Save error. Please check the fields.', 'taxi-rental' ) ],
			'user_saved'   => [ 'type' => 'success', 'text' => __( 'User saved.', 'taxi-rental' ) ],
		];

		if ( isset( $messages[ $msg ] ) ) {
			$m = $messages[ $msg ];
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $m['type'] ),
				esc_html( $m['text'] )
			);
		}
	}

	public static function page_dashboard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'taxi-rental' ) );
		}

		$counts    = wp_count_posts( 'car_listing' );
		$published = (int) ( $counts->publish ?? 0 );
		$pending   = (int) ( $counts->pending ?? 0 );
		$rejected  = (int) ( $counts->rejected ?? 0 );
		$archived  = (int) ( $counts->archived ?? 0 );

		$total_users = (int) count( get_users( [ 'fields' => 'ID' ] ) );

		$pending_posts = get_posts( [
			'post_type'      => 'car_listing',
			'post_status'    => 'pending',
			'posts_per_page' => 15,
			'orderby'        => 'date',
			'order'          => 'ASC',
		] );
		?>
		<div class="wrap tb-admin-wrap">
			<h1 class="tb-admin-h1">
				<span class="tb-admin-logo">TaxiBaza<span class="tb-admin-dot">.</span>sk</span>
				&mdash; <?php esc_html_e( 'Dashboard', 'taxi-rental' ); ?>
			</h1>

			<div class="tb-admin-stats">
				<div class="tb-admin-stat tb-admin-stat--green">
					<span class="tb-admin-stat__num"><?php echo esc_html( $published ); ?></span>
					<span class="tb-admin-stat__label"><?php esc_html_e( 'Published', 'taxi-rental' ); ?></span>
				</div>
				<div class="tb-admin-stat tb-admin-stat--yellow">
					<span class="tb-admin-stat__num"><?php echo esc_html( $pending ); ?></span>
					<span class="tb-admin-stat__label"><?php esc_html_e( 'Pending', 'taxi-rental' ); ?></span>
				</div>
				<div class="tb-admin-stat tb-admin-stat--red">
					<span class="tb-admin-stat__num"><?php echo esc_html( $rejected ); ?></span>
					<span class="tb-admin-stat__label"><?php esc_html_e( 'Rejected', 'taxi-rental' ); ?></span>
				</div>
				<div class="tb-admin-stat tb-admin-stat--gray">
					<span class="tb-admin-stat__num"><?php echo esc_html( $archived ); ?></span>
					<span class="tb-admin-stat__label"><?php esc_html_e( 'Archive', 'taxi-rental' ); ?></span>
				</div>
				<div class="tb-admin-stat tb-admin-stat--blue">
					<span class="tb-admin-stat__num"><?php echo esc_html( $total_users ); ?></span>
					<span class="tb-admin-stat__label"><?php esc_html_e( 'All Users', 'taxi-rental' ); ?></span>
				</div>
			</div>

			<?php if ( ! empty( $pending_posts ) ) : ?>
			<div class="tb-admin-section">
				<h2 class="tb-admin-h2">
					<?php esc_html_e( 'Needs Moderation', 'taxi-rental' ); ?>
					<span class="tb-admin-count"><?php echo esc_html( count( $pending_posts ) ); ?></span>
				</h2>
				<table class="widefat tb-admin-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Listing', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Owner', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'City', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Submitted', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'taxi-rental' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pending_posts as $p ) :
							$owner      = get_user_by( 'id', (int) $p->post_author );
							$cities     = get_the_terms( $p->ID, 'city' );
							$city_names = ( $cities && ! is_wp_error( $cities ) )
								? implode( ', ', wp_list_pluck( $cities, 'name' ) )
								: '—';
							$ago = sprintf(
								/* translators: %s: human-readable time diff */
								__( '%s ago', 'taxi-rental' ),
								human_time_diff( (int) strtotime( $p->post_date ), (int) current_time( 'timestamp' ) )
							);
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( (string) get_edit_post_link( $p->ID ) ); ?>" class="tb-link-strong">
									<?php echo esc_html( $p->post_title ?: __( '(no title)', 'taxi-rental' ) ); ?>
								</a>
							</td>
							<td>
								<?php if ( $owner ) : ?>
									<a href="<?php echo esc_url( get_edit_user_link( $owner->ID ) ); ?>">
										<?php echo esc_html( $owner->display_name ); ?>
									</a>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $city_names ); ?></td>
							<td class="tb-muted"><?php echo esc_html( $ago ); ?></td>
							<td>
								<div class="tb-admin-actions-col">
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?action=tb_approve&post=' . $p->ID ), 'tb_approve_' . $p->ID ) ); ?>" class="button button-primary button-small tb-btn-approve">
										<?php esc_html_e( '✓ Publish', 'taxi-rental' ); ?>
									</a>
									<a href="#" class="button button-small tb-btn-danger tb-action-reject" data-nonce-url="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?action=tb_reject&post=' . $p->ID ), 'tb_reject_' . $p->ID ) ); ?>">
										<?php esc_html_e( '✗ Reject', 'taxi-rental' ); ?>
									</a>
									<a href="<?php echo esc_url( (string) get_edit_post_link( $p->ID ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Edit', 'taxi-rental' ); ?>
									</a>
								</div>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php else : ?>
			<div class="tb-admin-section">
				<p class="tb-admin-empty"><?php esc_html_e( '🎉 No listings pending review.', 'taxi-rental' ); ?></p>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function page_listings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'taxi-rental' ) );
		}

		$status_filter = sanitize_key( $_GET['status'] ?? '' );
		$search        = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$author_filter = absint( $_GET['author'] ?? 0 );
		$paged         = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$per_page      = 20;

		$all_statuses = [ 'publish', 'pending', 'rejected', 'archived', 'draft', 'trash' ];

		$counts = [];
		foreach ( $all_statuses as $st ) {
			$c = wp_count_posts( 'car_listing' );
			$counts[ $st ] = (int) ( $c->$st ?? 0 );
		}
		$counts['all'] = array_sum( array_values( $counts ) );

		$query_args = [
			'post_type'      => 'car_listing',
			'post_status'    => $status_filter ?: $all_statuses,
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];
		if ( $search ) {
			$query_args['s'] = $search;
		}
		if ( $author_filter ) {
			$query_args['author'] = $author_filter;
		}

		$q          = new \WP_Query( $query_args );
		$posts      = $q->posts;
		$total      = $q->found_posts;
		$num_pages  = (int) ceil( $total / $per_page );

		$status_tabs = [
			''         => [ 'label' => __( 'All', 'taxi-rental' ),         'count' => $counts['all'] ],
			'publish'  => [ 'label' => __( 'Published', 'taxi-rental' ), 'count' => $counts['publish'] ],
			'pending'  => [ 'label' => __( 'Pending', 'taxi-rental' ),  'count' => $counts['pending'] ],
			'rejected' => [ 'label' => __( 'Rejected', 'taxi-rental' ),    'count' => $counts['rejected'] ],
			'archived' => [ 'label' => __( 'Archive', 'taxi-rental' ),        'count' => $counts['archived'] ],
			'draft'    => [ 'label' => __( 'Drafts', 'taxi-rental' ),     'count' => $counts['draft'] ],
		];

		$badge_map = [
			'publish'  => 'tb-badge--green',
			'pending'  => 'tb-badge--yellow',
			'rejected' => 'tb-badge--red',
			'archived' => 'tb-badge--gray',
			'draft'    => 'tb-badge--gray',
			'trash'    => 'tb-badge--red',
		];
		$status_labels = [
			'publish'  => __( 'Published', 'taxi-rental' ),
			'pending'  => __( 'Pending', 'taxi-rental' ),
			'rejected' => __( 'Rejected', 'taxi-rental' ),
			'archived' => __( 'Archive', 'taxi-rental' ),
			'draft'    => __( 'Draft', 'taxi-rental' ),
			'trash'    => __( 'Trash', 'taxi-rental' ),
		];

		$base_url = admin_url( 'admin.php?page=taxibaza-listings' );
		?>
		<div class="wrap tb-admin-wrap">
			<h1 class="tb-admin-h1">
				<span class="tb-admin-logo">TaxiBaza<span class="tb-admin-dot">.</span>sk</span>
				&mdash; <?php esc_html_e( 'Listings', 'taxi-rental' ); ?>
			</h1>

			<?php /* search bar */ ?>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="tb-admin-search-form">
				<input type="hidden" name="page" value="taxibaza-listings">
				<?php if ( $status_filter ) : ?>
					<input type="hidden" name="status" value="<?php echo esc_attr( $status_filter ); ?>">
				<?php endif; ?>
				<div class="tb-admin-search-wrap">
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>"
						placeholder="<?php esc_attr_e( 'Search listings…', 'taxi-rental' ); ?>"
						class="tb-admin-search-input">
					<button type="submit" class="button"><?php esc_html_e( 'Search', 'taxi-rental' ); ?></button>
					<?php if ( $search ) : ?>
						<a href="<?php echo esc_url( $base_url ); ?>" class="button"><?php esc_html_e( '× Reset', 'taxi-rental' ); ?></a>
					<?php endif; ?>
				</div>
			</form>

			<?php /* status tabs */ ?>
			<div class="tb-admin-tabs">
				<?php foreach ( $status_tabs as $st_key => $st_info ) :
					$tab_url = $st_key
						? add_query_arg( 'status', $st_key, $base_url )
						: $base_url;
					$is_active = $status_filter === $st_key;
				?>
				<a href="<?php echo esc_url( $tab_url ); ?>"
				   class="tb-admin-tab <?php echo $is_active ? 'is-active' : ''; ?>">
					<?php echo esc_html( $st_info['label'] ); ?>
					<span class="tb-admin-tab-count"><?php echo esc_html( $st_info['count'] ); ?></span>
				</a>
				<?php endforeach; ?>
			</div>

			<div class="tb-admin-section" style="padding:0">
				<?php if ( empty( $posts ) ) : ?>
				<p class="tb-admin-empty" style="padding:24px"><?php esc_html_e( 'No listings found.', 'taxi-rental' ); ?></p>
				<?php else : ?>
				<table class="widefat tb-admin-table tb-listings-table">
					<thead>
						<tr>
							<th class="tb-col-thumb"><?php esc_html_e( 'Photo', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Name / ID', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Owner', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'City', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Platform', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( '€/wk', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Status', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Date', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'taxi-rental' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $posts as $p ) :
							$thumb   = get_the_post_thumbnail( $p->ID, [ 60, 60 ] );
							$owner   = get_user_by( 'id', (int) $p->post_author );
							$cities  = get_the_terms( $p->ID, 'city' );
							$city_str = ( $cities && ! is_wp_error( $cities ) )
								? implode( ', ', wp_list_pluck( $cities, 'name' ) )
								: '—';
							$platforms  = get_the_terms( $p->ID, 'platform' );
							$plat_str   = ( $platforms && ! is_wp_error( $platforms ) )
								? implode( ', ', wp_list_pluck( $platforms, 'name' ) )
								: '—';
							$price       = get_post_meta( $p->ID, 'price_week', true );
							$st          = $p->post_status;
							$badge_class = $badge_map[ $st ] ?? 'tb-badge--gray';
							$st_label    = $status_labels[ $st ] ?? $st;
							$reason      = (string) get_post_meta( $p->ID, '_rejection_reason', true );
							$edit_url    = admin_url( 'admin.php?page=taxibaza-listing-edit&action=edit&id=' . $p->ID );
							$owner_url   = $owner ? admin_url( 'admin.php?page=taxibaza-user-edit&id=' . $owner->ID ) : '';
						?>
						<tr>
							<td class="tb-col-thumb">
								<?php if ( $thumb ) : ?>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="tb-listing-thumb">
										<?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput ?>
									</a>
								<?php else : ?>
									<span class="tb-listing-thumb tb-listing-thumb--empty">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
									</span>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( $edit_url ); ?>" class="tb-link-strong">
									<?php echo esc_html( $p->post_title ?: __( '(no title)', 'taxi-rental' ) ); ?>
								</a>
								<br><span class="tb-muted" style="font-size:11px">ID: <?php echo esc_html( $p->ID ); ?></span>
								<?php if ( 'rejected' === $st && $reason ) : ?>
									<br><span class="tb-muted" style="font-size:11px; color:#dc3545"><?php echo esc_html( $reason ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $owner ) : ?>
									<a href="<?php echo esc_url( $owner_url ); ?>">
										<?php echo esc_html( $owner->display_name ); ?>
									</a>
								<?php else : ?>
									<span class="tb-muted">—</span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $city_str ); ?></td>
							<td><?php echo esc_html( $plat_str ); ?></td>
							<td>
								<?php if ( $price ) : ?>
									<strong>€<?php echo esc_html( $price ); ?></strong>
								<?php else : ?>
									<span class="tb-muted">—</span>
								<?php endif; ?>
							</td>
							<td>
								<span class="tb-badge <?php echo esc_attr( $badge_class ); ?>">
									<?php echo esc_html( $st_label ); ?>
								</span>
							</td>
							<td class="tb-muted">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $p->post_date ) ) ); ?>
							</td>
							<td>
								<div class="tb-admin-row-actions">
									<a href="<?php echo esc_url( $edit_url ); ?>"
									   class="tb-row-btn tb-row-btn--edit" title="<?php esc_attr_e( 'Edit', 'taxi-rental' ); ?>">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
									</a>
									<?php if ( 'publish' !== $st ) : ?>
									<a href="<?php echo esc_url( add_query_arg( 'redirect', 'listings', wp_nonce_url( admin_url( 'admin.php?action=tb_approve&post=' . $p->ID ), 'tb_approve_' . $p->ID ) ) ); ?>"
									   class="tb-row-btn tb-row-btn--approve" title="<?php esc_attr_e( 'Publish', 'taxi-rental' ); ?>">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
									</a>
									<?php endif; ?>
									<?php if ( 'rejected' !== $st ) : ?>
									<a href="#"
									   class="tb-row-btn tb-row-btn--reject tb-action-reject"
									   data-nonce-url="<?php echo esc_url( add_query_arg( 'redirect', 'listings', wp_nonce_url( admin_url( 'admin.php?action=tb_reject&post=' . $p->ID ), 'tb_reject_' . $p->ID ) ) ); ?>"
									   title="<?php esc_attr_e( 'Reject', 'taxi-rental' ); ?>">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
									</a>
									<?php endif; ?>
									<?php if ( 'pending' !== $st ) : ?>
									<a href="<?php echo esc_url( add_query_arg( 'redirect', 'listings', wp_nonce_url( admin_url( 'admin.php?action=tb_pending&post=' . $p->ID ), 'tb_pending_' . $p->ID ) ) ); ?>"
									   class="tb-row-btn tb-row-btn--pending" title="<?php esc_attr_e( 'Return to Review', 'taxi-rental' ); ?>">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
									</a>
									<?php endif; ?>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?action=tb_delete&post=' . $p->ID ), 'tb_delete_' . $p->ID ) ); ?>"
									   class="tb-row-btn tb-row-btn--delete tb-action-delete" title="<?php esc_attr_e( 'Delete', 'taxi-rental' ); ?>">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
									</a>
								</div>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $num_pages > 1 ) : ?>
				<div class="tb-admin-pagination">
					<?php for ( $i = 1; $i <= $num_pages; $i++ ) :
						$p_url = add_query_arg( 'paged', $i,
							$status_filter ? add_query_arg( 'status', $status_filter, $base_url ) : $base_url
						);
					?>
					<a href="<?php echo esc_url( $p_url ); ?>"
					   class="tb-page-btn <?php echo $i === $paged ? 'is-active' : ''; ?>">
						<?php echo esc_html( $i ); ?>
					</a>
					<?php endfor; ?>
				</div>
				<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public static function inject_back_button(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'car_listing' !== $screen->post_type ) {
			return;
		}
		$back_url = admin_url( 'admin.php?page=taxibaza-listings' );
		?>
		<style>
		#tb-back-to-listings { display:inline-flex; align-items:center; gap:6px; margin:8px 0 4px; font-size:13px; color:#646970; text-decoration:none; }
		#tb-back-to-listings:hover { color:#02F797; }
		</style>
		<script>
		document.addEventListener( 'DOMContentLoaded', function () {
			var h1 = document.querySelector( '.wp-heading-inline' );
			if ( ! h1 ) return;
			var a = document.createElement( 'a' );
			a.id   = 'tb-back-to-listings';
			a.href = '<?php echo esc_url( $back_url ); ?>';
			a.innerHTML = '&#8592; <?php echo esc_js( __( 'All Listings', 'taxi-rental' ) ); ?>';
			h1.parentNode.insertBefore( a, h1 );
		} );
		</script>
		<?php
	}

	// =========================================================================
	// Taxonomy CRUD pages
	// =========================================================================

	public static function page_cities(): void {
		self::page_taxonomy_crud( 'city', __( 'Cities', 'taxi-rental' ), __( 'City', 'taxi-rental' ) );
	}

	public static function page_platforms(): void {
		self::page_taxonomy_crud( 'platform', __( 'Platforms', 'taxi-rental' ), __( 'Platform', 'taxi-rental' ) );
	}

	public static function page_categories(): void {
		self::page_taxonomy_crud( 'ride_category', __( 'Ride Categories', 'taxi-rental' ), __( 'Category', 'taxi-rental' ), true );
	}

	public static function page_car_makes(): void {
		self::page_taxonomy_crud( 'car_make', __( 'Makes & Models', 'taxi-rental' ), __( 'Brand', 'taxi-rental' ), true );
	}

	private static function page_taxonomy_crud( string $taxonomy, string $label, string $singular_acc, bool $hierarchical = false ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'taxi-rental' ) );
		}

		$slug_map  = [ 'city' => 'cities', 'platform' => 'platforms', 'ride_category' => 'categories', 'car_make' => 'car-makes' ];
		$page_slug = 'taxibaza-' . ( $slug_map[ $taxonomy ] ?? str_replace( '_', '-', $taxonomy . 's' ) );
		$action     = sanitize_key( $_GET['action'] ?? '' );
		$edit_id    = absint( $_GET['term_id'] ?? 0 );
		$edit_term  = ( 'edit' === $action && $edit_id ) ? get_term( $edit_id, $taxonomy ) : null;
		$page_url   = admin_url( 'admin.php?page=' . $page_slug );

		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		] );
		if ( is_wp_error( $terms ) ) {
			$terms = [];
		}

		// WPML: active languages + element type for this taxonomy.
		$wpml_langs   = function_exists( 'icl_get_languages' )
			? (array) apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] )
			: [];
		$wpml_el_type = ! empty( $wpml_langs )
			? (string) apply_filters( 'wpml_element_type', $taxonomy )
			: '';
		$wpml_flags   = [ 'uk' => '🇺🇦', 'sk' => '🇸🇰', 'en' => '🇬🇧', 'de' => '🇩🇪', 'pl' => '🇵🇱', 'cs' => '🇨🇿', 'hu' => '🇭🇺', 'ru' => '🇷🇺' ];

		$parent_terms = $hierarchical ? get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'parent'     => 0,
			'orderby'    => 'name',
		] ) : [];
		if ( is_wp_error( $parent_terms ) ) {
			$parent_terms = [];
		}

		$is_editing   = ( $edit_term instanceof \WP_Term );
		$form_action  = $is_editing ? 'tb_edit_term_' . $edit_id : 'tb_add_term_' . $taxonomy;
		$current_parent = $is_editing ? (int) $edit_term->parent : 0;
		?>
		<div class="wrap tb-admin-wrap">
			<h1 class="tb-admin-h1">
				<span class="tb-admin-logo">TaxiBaza<span class="tb-admin-dot">.</span>sk</span>
				&mdash; <?php echo esc_html( $label ); ?>
			</h1>

			<div class="tb-taxonomy-layout">

				<?php /* ---- Left: form ---- */ ?>
				<div class="tb-taxonomy-form-col">
					<div class="tb-admin-section">
						<h2 class="tb-admin-h2">
							<?php echo $is_editing
								? esc_html( sprintf( __( 'Edit %s', 'taxi-rental' ), $singular_acc ) )
								: esc_html( sprintf( __( 'Add %s', 'taxi-rental' ), $singular_acc ) ); ?>
						</h2>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
							<input type="hidden" name="action" value="tb_save_term">
							<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxonomy ); ?>">
							<input type="hidden" name="page_slug" value="<?php echo esc_attr( $page_slug ); ?>">
							<?php if ( $is_editing ) : ?>
								<input type="hidden" name="term_id" value="<?php echo esc_attr( $edit_id ); ?>">
							<?php endif; ?>
							<?php wp_nonce_field( $form_action ); ?>

							<div class="tb-form-row">
								<label for="tb_term_name"><?php esc_html_e( 'Name', 'taxi-rental' ); ?> <span class="tb-required">*</span></label>
								<input type="text" id="tb_term_name" name="term_name"
									value="<?php echo $is_editing ? esc_attr( $edit_term->name ) : ''; ?>"
									class="widefat" required>
							</div>

							<div class="tb-form-row">
								<label for="tb_term_slug"><?php esc_html_e( 'Slug', 'taxi-rental' ); ?></label>
								<input type="text" id="tb_term_slug" name="term_slug"
									value="<?php echo $is_editing ? esc_attr( $edit_term->slug ) : ''; ?>"
									class="widefat">
								<p class="tb-form-hint"><?php esc_html_e( 'Unique identifier (Latin letters, digits, hyphen). Leave empty — it will be generated automatically.', 'taxi-rental' ); ?></p>
							</div>

							<div class="tb-form-row">
								<label for="tb_term_desc"><?php esc_html_e( 'Description', 'taxi-rental' ); ?></label>
								<textarea id="tb_term_desc" name="term_description" class="widefat" rows="3"><?php echo $is_editing ? esc_textarea( $edit_term->description ) : ''; ?></textarea>
							</div>

							<div class="tb-form-row">
								<label><?php esc_html_e( 'Image', 'taxi-rental' ); ?></label>
								<?php
								$tb_img_id  = $is_editing ? (int) get_term_meta( $edit_id, 'tb_image_id', true ) : 0;
								$tb_img_url = $tb_img_id ? wp_get_attachment_image_url( $tb_img_id, 'medium' ) : '';
								?>
								<input type="hidden" name="term_image_id" id="tb_term_image_id" value="<?php echo esc_attr( $tb_img_id ?: '' ); ?>">
								<div id="tb-img-preview"<?php echo $tb_img_url ? '' : ' style="display:none"'; ?>>
									<img src="<?php echo esc_url( $tb_img_url ); ?>" id="tb-img-preview-src" style="max-width:160px;max-height:120px;display:block;border-radius:4px;margin-bottom:8px;object-fit:cover">
								</div>
								<button type="button" class="button" id="tb-img-select"><?php esc_html_e( 'Select Image', 'taxi-rental' ); ?></button>
								<button type="button" class="button" id="tb-img-remove"<?php echo $tb_img_url ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Delete', 'taxi-rental' ); ?></button>
								<script>
								(function(){
									var frame;
									document.getElementById('tb-img-select').addEventListener('click',function(e){
										e.preventDefault();
										if(frame){frame.open();return;}
										frame=wp.media({title:<?php echo wp_json_encode( __( 'Choose Image', 'taxi-rental' ) ); ?>,button:{text:<?php echo wp_json_encode( __( 'Use', 'taxi-rental' ) ); ?>},multiple:false,library:{type:'image'}});
										frame.on('select',function(){
											var att=frame.state().get('selection').first().toJSON();
											document.getElementById('tb_term_image_id').value=att.id;
											document.getElementById('tb-img-preview-src').src=att.url;
											document.getElementById('tb-img-preview').style.display='';
											document.getElementById('tb-img-remove').style.display='';
										});
										frame.open();
									});
									document.getElementById('tb-img-remove').addEventListener('click',function(){
										document.getElementById('tb_term_image_id').value='';
										document.getElementById('tb-img-preview').style.display='none';
										this.style.display='none';
									});
								})();
								</script>
							</div>

							<?php if ( $hierarchical ) : ?>
							<div class="tb-form-row">
								<label for="tb_term_parent"><?php esc_html_e( 'Parent category', 'taxi-rental' ); ?></label>
								<select id="tb_term_parent" name="term_parent" class="widefat">
									<option value="0"><?php esc_html_e( '— None (top level) —', 'taxi-rental' ); ?></option>
									<?php foreach ( $parent_terms as $pt ) : ?>
										<option value="<?php echo esc_attr( $pt->term_id ); ?>"
											<?php selected( $current_parent, $pt->term_id ); ?>>
											<?php echo esc_html( $pt->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
							<?php endif; ?>

							<div class="tb-form-actions">
								<button type="submit" class="button button-primary tb-btn-approve">
									<?php echo $is_editing
										? esc_html__( 'Save', 'taxi-rental' )
										: esc_html__( 'Add', 'taxi-rental' ); ?>
								</button>
								<?php if ( $is_editing ) : ?>
									<a href="<?php echo esc_url( $page_url ); ?>" class="button">
										<?php esc_html_e( 'Cancel', 'taxi-rental' ); ?>
									</a>
								<?php endif; ?>
							</div>
						</form>
					</div>
				</div>

				<?php /* ---- Right: table ---- */ ?>
				<div class="tb-taxonomy-table-col">
					<div class="tb-admin-section" style="padding:0">
						<div class="tb-section-header">
							<h2 class="tb-admin-h2" style="padding:16px 20px 0">
								<?php echo esc_html( $label ); ?>
								<span class="tb-admin-count"><?php echo esc_html( count( $terms ) ); ?></span>
							</h2>
						</div>
						<?php if ( empty( $terms ) ) : ?>
							<p class="tb-admin-empty"><?php esc_html_e( 'No entries found.', 'taxi-rental' ); ?></p>
						<?php else : ?>
						<table class="widefat tb-admin-table">
							<thead>
								<tr>
									<th style="width:52px"><?php esc_html_e( 'Photo', 'taxi-rental' ); ?></th>
									<th><?php esc_html_e( 'Name', 'taxi-rental' ); ?></th>
									<th><?php esc_html_e( 'Slug', 'taxi-rental' ); ?></th>
									<?php if ( $hierarchical ) : ?>
									<th><?php esc_html_e( 'Parent', 'taxi-rental' ); ?></th>
									<?php endif; ?>
									<th><?php esc_html_e( 'Listings', 'taxi-rental' ); ?></th>
									<?php if ( ! empty( $wpml_langs ) ) : ?>
									<th><?php esc_html_e( 'Languages', 'taxi-rental' ); ?></th>
									<?php endif; ?>
									<th><?php esc_html_e( 'Actions', 'taxi-rental' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $terms as $t ) :
									$edit_term_url   = add_query_arg( [ 'action' => 'edit', 'term_id' => $t->term_id ], $page_url );
									$delete_term_url = wp_nonce_url(
										admin_url( 'admin.php?action=tb_delete_term&term_id=' . $t->term_id . '&taxonomy=' . $taxonomy . '&page_slug=' . $page_slug ),
										'tb_delete_term_' . $t->term_id
									);
									$parent_name = '';
									if ( $hierarchical && $t->parent ) {
										$par = get_term( $t->parent, $taxonomy );
										$parent_name = ( $par instanceof \WP_Term ) ? $par->name : '—';
									}
									$count_url = add_query_arg( [ $taxonomy => $t->slug ], admin_url( 'admin.php?page=taxibaza-listings' ) );
								?>
								<tr class="<?php echo $is_editing && $edit_id === $t->term_id ? 'tb-row-active' : ''; ?>">
									<td style="vertical-align:middle">
										<?php
										$t_img_id  = (int) get_term_meta( $t->term_id, 'tb_image_id', true );
										$t_img_url = $t_img_id ? wp_get_attachment_image_url( $t_img_id, 'thumbnail' ) : '';
										?>
										<?php if ( $t_img_url ) : ?>
											<img src="<?php echo esc_url( $t_img_url ); ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:4px;display:block">
										<?php else : ?>
											<span class="tb-muted">—</span>
										<?php endif; ?>
									</td>
									<td>
										<a href="<?php echo esc_url( $edit_term_url ); ?>" class="tb-link-strong">
											<?php echo esc_html( $t->name ); ?>
										</a>
										<?php if ( $t->description ) : ?>
											<br><span class="tb-muted" style="font-size:11px"><?php echo esc_html( wp_trim_words( $t->description, 8 ) ); ?></span>
										<?php endif; ?>
									</td>
									<td class="tb-muted"><?php echo esc_html( $t->slug ); ?></td>
									<?php if ( $hierarchical ) : ?>
									<td class="tb-muted"><?php echo $t->parent ? esc_html( $parent_name ) : '<span class="tb-muted">—</span>'; ?></td>
									<?php endif; ?>
									<td>
										<?php if ( $t->count > 0 ) : ?>
											<a href="<?php echo esc_url( $count_url ); ?>" class="tb-badge tb-badge--blue">
												<?php echo esc_html( $t->count ); ?>
											</a>
										<?php else : ?>
											<span class="tb-muted">0</span>
										<?php endif; ?>
									</td>
									<?php if ( ! empty( $wpml_langs ) ) :
										$wpml_trid  = (int) apply_filters( 'wpml_element_trid', null, $t->term_id, $wpml_el_type );
										$wpml_trans = $wpml_trid
											? (array) apply_filters( 'wpml_get_element_translations', null, $wpml_trid, $wpml_el_type )
											: [];
									?>
									<td style="white-space:nowrap">
										<?php foreach ( $wpml_langs as $wl ) :
											$wl_code = (string) ( $wl['code'] ?? '' );
											$wl_flag = $wpml_flags[ $wl_code ] ?? strtoupper( $wl_code );
											$wl_name = (string) ( $wl['native_name'] ?? $wl_code );
											$has_tr  = isset( $wpml_trans[ $wl_code ] );
											if ( $has_tr ) {
												$tr_id   = (int) $wpml_trans[ $wl_code ]->element_id;
												$tr_url  = add_query_arg(
													[ 'action' => 'edit', 'taxonomy' => $taxonomy, 'tag_ID' => $tr_id, 'lang' => $wl_code ],
													admin_url( 'edit-tags.php' )
												);
												printf(
													'<a href="%s" title="%s" style="text-decoration:none;margin-right:4px;font-size:18px">%s</a>',
													esc_url( $tr_url ),
													esc_attr( $wl_name ),
													esc_html( $wl_flag )
												);
											} else {
												$add_url = add_query_arg(
													[ 'taxonomy' => $taxonomy, 'new_lang' => $wl_code, 'trid' => $wpml_trid ],
													admin_url( 'edit-tags.php' )
												);
												printf(
													'<a href="%s" title="%s" style="text-decoration:none;margin-right:4px;font-size:18px;opacity:0.25" aria-label="%s">%s</a>',
													esc_url( $add_url ),
													/* translators: %s: language name */
													esc_attr( sprintf( __( 'Add translation: %s', 'taxi-rental' ), $wl_name ) ),
													/* translators: %s: language name */
													esc_attr( sprintf( __( 'Add translation: %s', 'taxi-rental' ), $wl_name ) ),
													esc_html( $wl_flag )
												);
											}
										endforeach; ?>
									</td>
									<?php endif; ?>
									<td>
										<div class="tb-admin-row-actions">
											<a href="<?php echo esc_url( $edit_term_url ); ?>"
											   class="tb-row-btn tb-row-btn--edit"
											   title="<?php esc_attr_e( 'Edit', 'taxi-rental' ); ?>">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
											</a>
											<a href="<?php echo esc_url( $delete_term_url ); ?>"
											   class="tb-row-btn tb-row-btn--delete tb-action-delete-term"
											   title="<?php esc_attr_e( 'Delete', 'taxi-rental' ); ?>">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
											</a>
										</div>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<?php endif; ?>
					</div>
				</div>

			</div>
		</div>
		<?php
	}

	public static function action_save_term(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'taxi-rental' ) );
		}

		$taxonomy  = sanitize_key( $_POST['taxonomy'] ?? '' );
		$page_slug = sanitize_key( $_POST['page_slug'] ?? '' );
		$term_id   = absint( $_POST['term_id'] ?? 0 );
		$name      = sanitize_text_field( wp_unslash( $_POST['term_name'] ?? '' ) );
		$slug      = sanitize_title( wp_unslash( $_POST['term_slug'] ?? '' ) );
		$desc      = sanitize_textarea_field( wp_unslash( $_POST['term_description'] ?? '' ) );
		$parent    = absint( $_POST['term_parent'] ?? 0 );

		if ( ! $taxonomy || ! $name ) {
			wp_die( esc_html__( 'Name is required.', 'taxi-rental' ) );
		}

		$nonce_action = $term_id ? 'tb_edit_term_' . $term_id : 'tb_add_term_' . $taxonomy;
		check_admin_referer( $nonce_action );

		$allowed_taxonomies = self::TB_TAXONOMIES;
		if ( ! in_array( $taxonomy, $allowed_taxonomies, true ) ) {
			wp_die( esc_html__( 'Unknown taxonomy.', 'taxi-rental' ) );
		}

		$args = [ 'description' => $desc ];
		if ( $slug ) {
			$args['slug'] = $slug;
		}
		if ( $parent ) {
			$args['parent'] = $parent;
		}

		if ( $term_id ) {
			$result = wp_update_term( $term_id, $taxonomy, array_merge( [ 'name' => $name ], $args ) );
			$msg    = is_wp_error( $result ) ? 'term_error' : 'term_saved';
		} else {
			$result = wp_insert_term( $name, $taxonomy, $args );
			$msg    = is_wp_error( $result ) ? 'term_error' : 'term_added';
		}

		if ( ! is_wp_error( $result ) ) {
			$saved_term_id = $term_id ?: (int) ( $result['term_id'] ?? 0 );
			$image_id      = absint( $_POST['term_image_id'] ?? 0 );
			if ( $image_id ) {
				update_term_meta( $saved_term_id, 'tb_image_id', $image_id );
			} else {
				delete_term_meta( $saved_term_id, 'tb_image_id' );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . $page_slug . '&tb_msg=' . $msg ) );
		exit;
	}

	public static function action_delete_term(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'taxi-rental' ) );
		}

		$term_id   = absint( $_GET['term_id'] ?? 0 );
		$taxonomy  = sanitize_key( $_GET['taxonomy'] ?? '' );
		$page_slug = sanitize_key( $_GET['page_slug'] ?? '' );

		if ( ! $term_id || ! $taxonomy ) {
			wp_die( esc_html__( 'Invalid request.', 'taxi-rental' ) );
		}

		check_admin_referer( 'tb_delete_term_' . $term_id );

		if ( ! in_array( $taxonomy, self::TB_TAXONOMIES, true ) ) {
			wp_die( esc_html__( 'Unknown taxonomy.', 'taxi-rental' ) );
		}

		wp_delete_term( $term_id, $taxonomy );

		wp_safe_redirect( admin_url( 'admin.php?page=' . $page_slug . '&tb_msg=term_deleted' ) );
		exit;
	}

	// =========================================================================
	// Listing create / edit
	// =========================================================================

	public static function maybe_acf_form_head(): void {
		if ( ! isset( $_GET['page'] ) || 'taxibaza-listing-edit' !== $_GET['page'] ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( function_exists( 'acf_form_head' ) ) {
			acf_form_head();
		}

		// Save post status after ACF saves the post.
		add_action( 'acf/save_post', static function ( $post_id ) {
			if ( ! is_numeric( $post_id ) ) {
				return;
			}
			if ( get_post_type( (int) $post_id ) !== 'car_listing' ) {
				return;
			}
			if ( ! isset( $_POST['tb_post_status'] ) ) {
				return;
			}
			$status  = sanitize_key( $_POST['tb_post_status'] );
			$allowed = [ 'pending', 'publish', 'rejected', 'archived', 'draft' ];
			if ( in_array( $status, $allowed, true ) && current_user_can( 'manage_options' ) ) {
				global $wpdb;
				$wpdb->update( $wpdb->posts, [ 'post_status' => $status ], [ 'ID' => (int) $post_id ] );
				clean_post_cache( (int) $post_id );
			}
		}, 20 );
	}

	public static function page_listing_edit(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'taxi-rental' ) );
		}

		$action  = sanitize_key( $_GET['action'] ?? 'new' );
		$post_id = absint( $_GET['id'] ?? 0 );
		$is_new  = ( 'new' === $action || ! $post_id );

		if ( ! $is_new ) {
			$post = get_post( $post_id );
			if ( ! $post || 'car_listing' !== $post->post_type ) {
				wp_die( esc_html__( 'Listing not found.', 'taxi-rental' ) );
			}
		}

		$back_url   = admin_url( 'admin.php?page=taxibaza-listings' );
		$return_url = admin_url( 'admin.php?page=taxibaza-listings&tb_msg=' . ( $is_new ? 'created' : 'saved' ) );

		$statuses = [
			'pending'  => __( 'Pending', 'taxi-rental' ),
			'publish'  => __( 'Published', 'taxi-rental' ),
			'rejected' => __( 'Rejected', 'taxi-rental' ),
			'archived' => __( 'Archive', 'taxi-rental' ),
			'draft'    => __( 'Draft', 'taxi-rental' ),
		];
		$current_status = $is_new ? 'pending' : ( get_post( $post_id )->post_status ?? 'pending' );

		ob_start();
		?>
		<div class="tb-form-row tb-form-row--status">
			<label for="tb_post_status"><?php esc_html_e( 'Listing Status', 'taxi-rental' ); ?></label>
			<select id="tb_post_status" name="tb_post_status" class="tb-select">
				<?php foreach ( $statuses as $val => $lbl ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current_status, $val ); ?>>
						<?php echo esc_html( $lbl ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
		$status_html = ob_get_clean();

		$submit_html = '<div class="tb-form-actions">'
			. '<button class="button button-primary tb-btn-approve" type="submit">' . esc_html__( 'Save', 'taxi-rental' ) . '</button>'
			. ' <a href="' . esc_url( $back_url ) . '" class="button">' . esc_html__( 'Cancel', 'taxi-rental' ) . '</a>'
			. '</div>';
		?>
		<div class="wrap tb-admin-wrap">
			<h1 class="tb-admin-h1">
				<span class="tb-admin-logo">TaxiBaza<span class="tb-admin-dot">.</span>sk</span>
				&mdash; <?php echo $is_new ? esc_html__( 'New Listing', 'taxi-rental' ) : esc_html__( 'Edit Listing', 'taxi-rental' ); ?>
				<a href="<?php echo esc_url( $back_url ); ?>" class="tb-back-link">
					&#8592; <?php esc_html_e( 'Listings', 'taxi-rental' ); ?>
				</a>
			</h1>

			<?php if ( function_exists( 'acf_form' ) ) : ?>
			<div class="tb-listing-edit-wrap">
				<?php
				acf_form( [
					'post_id'             => $is_new ? 'new_post' : $post_id,
					'new_post'            => $is_new ? [
						'post_type'   => 'car_listing',
						'post_status' => 'pending',
					] : false,
					'post_title'          => true,
					'post_content'        => false,
					'return'              => $return_url,
					'submit_value'        => __( 'Save', 'taxi-rental' ),
					'html_before_fields'  => '<div class="tb-acf-fields">' . $status_html,
					'html_after_fields'   => '</div>',
					'html_submit_button'  => $submit_html,
					'html_submit_spinner' => '',
					'id'                  => 'tb-listing-form',
					'field_el'            => 'div',
					'uploader'            => 'wp',
					'label_placement'     => 'top',
					'instruction_placement' => 'label',
				] );
				?>
			</div>
			<?php else : ?>
			<div class="tb-admin-section">
				<p><?php esc_html_e( 'ACF Pro is not installed or activated.', 'taxi-rental' ); ?></p>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	// =========================================================================
	// User edit page
	// =========================================================================

	public static function page_user_edit(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'taxi-rental' ) );
		}

		$user_id = absint( $_GET['id'] ?? 0 );
		if ( ! $user_id ) {
			wp_die( esc_html__( 'Unknown user.', 'taxi-rental' ) );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_die( esc_html__( 'User not found.', 'taxi-rental' ) );
		}

		$back_url = admin_url( 'admin.php?page=taxibaza-users' );
		// count_user_posts() only counts public statuses — drafts, pending,
		// rejected and archived listings were silently left out of the total.
		$listing_count = tb_listing_count( [ 'publish', 'pending', 'draft', 'rejected', 'archived' ], $user_id );

		$phone        = get_user_meta( $user_id, 'phone_number', true );
		$company      = get_user_meta( $user_id, 'company_name', true );
		$primary_role = ! empty( $user->roles ) ? $user->roles[0] : 'subscriber';

		$all_roles = [
			'car_owner'     => __( 'Car Owner', 'taxi-rental' ),
			'fleet'         => __( 'Fleet', 'taxi-rental' ),
			'administrator' => __( 'Administrator', 'taxi-rental' ),
			'editor'        => __( 'Editor', 'taxi-rental' ),
			'subscriber'    => __( 'Subscriber', 'taxi-rental' ),
		];
		?>
		<div class="wrap tb-admin-wrap">
			<h1 class="tb-admin-h1">
				<span class="tb-admin-logo">TaxiBaza<span class="tb-admin-dot">.</span>sk</span>
				&mdash; <?php esc_html_e( 'Edit User', 'taxi-rental' ); ?>
				<a href="<?php echo esc_url( $back_url ); ?>" class="tb-back-link">
					&#8592; <?php esc_html_e( 'Users', 'taxi-rental' ); ?>
				</a>
			</h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="action" value="tb_save_user">
				<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>">
				<?php wp_nonce_field( 'tb_save_user_' . $user_id ); ?>

				<div class="tb-edit-grid">

					<div class="tb-edit-main">

						<div class="tb-admin-section">
							<h2 class="tb-admin-h2"><?php esc_html_e( 'Basic Information', 'taxi-rental' ); ?></h2>
							<div class="tb-form-grid-2">
								<div class="tb-form-row">
									<label for="tb_first_name"><?php esc_html_e( 'First name', 'taxi-rental' ); ?></label>
									<input type="text" id="tb_first_name" name="first_name"
										value="<?php echo esc_attr( $user->first_name ); ?>" class="widefat">
								</div>
								<div class="tb-form-row">
									<label for="tb_last_name"><?php esc_html_e( 'Last name', 'taxi-rental' ); ?></label>
									<input type="text" id="tb_last_name" name="last_name"
										value="<?php echo esc_attr( $user->last_name ); ?>" class="widefat">
								</div>
							</div>
							<div class="tb-form-row">
								<label for="tb_display_name"><?php esc_html_e( 'Display name', 'taxi-rental' ); ?></label>
								<input type="text" id="tb_display_name" name="display_name"
									value="<?php echo esc_attr( $user->display_name ); ?>" class="widefat">
							</div>
							<div class="tb-form-row">
								<label for="tb_user_email"><?php esc_html_e( 'Email', 'taxi-rental' ); ?> <span class="tb-required">*</span></label>
								<input type="email" id="tb_user_email" name="user_email"
									value="<?php echo esc_attr( $user->user_email ); ?>" class="widefat" required>
							</div>
						</div>

						<div class="tb-admin-section">
							<h2 class="tb-admin-h2"><?php esc_html_e( 'Contacts', 'taxi-rental' ); ?></h2>
							<div class="tb-form-row">
								<label for="tb_phone"><?php esc_html_e( 'Phone', 'taxi-rental' ); ?></label>
								<input type="tel" id="tb_phone" name="phone_number"
									value="<?php echo esc_attr( (string) $phone ); ?>" class="widefat"
									placeholder="+421 XXX XXX XXX">
							</div>
							<div class="tb-form-row">
								<label for="tb_company"><?php esc_html_e( 'Company / fleet name', 'taxi-rental' ); ?></label>
								<input type="text" id="tb_company" name="company_name"
									value="<?php echo esc_attr( (string) $company ); ?>" class="widefat">
							</div>
						</div>

						<div class="tb-admin-section">
							<h2 class="tb-admin-h2"><?php esc_html_e( 'Change Password', 'taxi-rental' ); ?></h2>
							<div class="tb-form-row">
								<label for="tb_user_pass"><?php esc_html_e( 'New Password', 'taxi-rental' ); ?></label>
								<input type="password" id="tb_user_pass" name="user_pass"
									value="" class="widefat" autocomplete="new-password"
									placeholder="<?php esc_attr_e( 'Leave empty to keep unchanged', 'taxi-rental' ); ?>">
							</div>
						</div>

					</div>

					<div class="tb-edit-sidebar">

						<div class="tb-admin-section">
							<h2 class="tb-admin-h2"><?php esc_html_e( 'Role', 'taxi-rental' ); ?></h2>
							<div class="tb-form-row">
								<label for="tb_user_role"><?php esc_html_e( 'Site Role', 'taxi-rental' ); ?></label>
								<select id="tb_user_role" name="user_role" class="widefat">
									<?php foreach ( $all_roles as $role_key => $role_label ) : ?>
										<option value="<?php echo esc_attr( $role_key ); ?>"
											<?php selected( $primary_role, $role_key ); ?>>
											<?php echo esc_html( $role_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="tb-admin-section">
							<h2 class="tb-admin-h2"><?php esc_html_e( 'Statistics', 'taxi-rental' ); ?></h2>
							<ul class="tb-user-stats">
								<li>
									<span><?php esc_html_e( 'Login:', 'taxi-rental' ); ?></span>
									<strong><?php echo esc_html( $user->user_login ); ?></strong>
								</li>
								<li>
									<span><?php esc_html_e( 'Registered:', 'taxi-rental' ); ?></span>
									<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ) ); ?></strong>
								</li>
								<li>
									<span><?php esc_html_e( 'Listing:', 'taxi-rental' ); ?></span>
									<?php if ( $listing_count > 0 ) : ?>
										<a href="<?php echo esc_url( add_query_arg( [ 'author' => $user_id ], admin_url( 'admin.php?page=taxibaza-listings' ) ) ); ?>">
											<strong><?php echo esc_html( $listing_count ); ?></strong>
										</a>
									<?php else : ?>
										<strong>0</strong>
									<?php endif; ?>
								</li>
							</ul>
						</div>

						<div class="tb-admin-section tb-save-card">
							<button type="submit" class="button button-primary tb-btn-approve widefat">
								<?php esc_html_e( 'Save Changes', 'taxi-rental' ); ?>
							</button>
							<a href="<?php echo esc_url( $back_url ); ?>" class="button widefat" style="margin-top:6px">
								<?php esc_html_e( 'Cancel', 'taxi-rental' ); ?>
							</a>
						</div>

					</div>
				</div>

			</form>
		</div>
		<?php
	}

	public static function action_save_user(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'taxi-rental' ) );
		}

		$user_id = absint( $_POST['user_id'] ?? 0 );
		if ( ! $user_id ) {
			wp_die( esc_html__( 'Unknown user.', 'taxi-rental' ) );
		}

		check_admin_referer( 'tb_save_user_' . $user_id );

		$data = [
			'ID'           => $user_id,
			'first_name'   => sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ),
			'last_name'    => sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ),
			'display_name' => sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) ),
			'user_email'   => sanitize_email( wp_unslash( $_POST['user_email'] ?? '' ) ),
		];

		$new_pass = wp_unslash( $_POST['user_pass'] ?? '' );
		if ( '' !== $new_pass ) {
			$data['user_pass'] = $new_pass;
		}

		wp_update_user( $data );

		// Role.
		$role          = sanitize_key( $_POST['user_role'] ?? '' );
		$allowed_roles = [ 'administrator', 'car_owner', 'fleet', 'editor', 'subscriber' ];
		if ( $role && in_array( $role, $allowed_roles, true ) ) {
			$wp_user = new \WP_User( $user_id );
			$wp_user->set_role( $role );
		}

		// Meta fields (ACF user meta or direct user_meta).
		$meta_fields = [
			'phone_number' => sanitize_text_field( wp_unslash( $_POST['phone_number'] ?? '' ) ),
			'company_name' => sanitize_text_field( wp_unslash( $_POST['company_name'] ?? '' ) ),
		];
		foreach ( $meta_fields as $key => $val ) {
			if ( function_exists( 'update_field' ) ) {
				update_field( $key, $val, 'user_' . $user_id );
			} else {
				update_user_meta( $user_id, $key, $val );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=taxibaza-users&tb_msg=user_saved' ) );
		exit;
	}

	// ── Block / Unblock user ─────────────────────────────────────────────────

	public static function ajax_fa_block_user(): void {
		$user_id = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! $user_id || ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}
		update_user_meta( $user_id, 'tb_user_blocked', '1' );
		wp_send_json_success( [ 'message' => __( 'User blocked.', 'taxi-rental' ) ] );
	}

	public static function ajax_fa_unblock_user(): void {
		$user_id = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! $user_id || ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}
		delete_user_meta( $user_id, 'tb_user_blocked' );
		wp_send_json_success( [ 'message' => __( 'User unblocked.', 'taxi-rental' ) ] );
	}

	// ── Save user profile (front-admin form) ──────────────────────────────────

	public static function ajax_fa_save_user(): void {
		$user_id = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$nonce   = sanitize_text_field( wp_unslash( $_POST['tb_fa_nonce'] ?? '' ) );
		if ( ! $user_id || ! wp_verify_nonce( $nonce, 'tb_fa_save_user_' . $user_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}

		$data = [
			'ID'         => $user_id,
			'first_name' => sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ),
			'last_name'  => sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ),
			'user_email' => sanitize_email( wp_unslash( $_POST['user_email'] ?? '' ) ),
		];

		$result = wp_update_user( $data );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		// Role.
		$role          = sanitize_key( $_POST['user_role'] ?? '' );
		$allowed_roles = [ 'car_owner', 'fleet', 'administrator', 'editor', 'subscriber' ];
		if ( $role && in_array( $role, $allowed_roles, true ) ) {
			$wp_user = new \WP_User( $user_id );
			$wp_user->set_role( $role );
		}

		// Meta fields.
		$meta_fields = [
			'phone_number' => sanitize_text_field( wp_unslash( $_POST['phone_number'] ?? '' ) ),
			'telegram'     => sanitize_text_field( wp_unslash( $_POST['telegram'] ?? '' ) ),
			'description'  => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
		];
		foreach ( $meta_fields as $key => $val ) {
			update_user_meta( $user_id, $key, $val );
		}

		// Avatar (attachment id) — front-admin photo card.
		// Canonical key is 'avatar' — the same one the cabinet writes. This used
		// to save to 'tb_avatar_id', which nothing on the front end ever read.
		$avatar_id = absint( wp_unslash( $_POST['avatar_id'] ?? 0 ) );
		if ( $avatar_id ) {
			update_user_meta( $user_id, 'avatar', $avatar_id );
		} else {
			delete_user_meta( $user_id, 'avatar' );
		}
		delete_user_meta( $user_id, 'tb_avatar_id' );

		wp_send_json_success( [ 'message' => __( 'Saved.', 'taxi-rental' ) ] );
	}

	// ── City CRUD (front-admin AJAX) ──────────────────────────────────────────

	public static function ajax_fa_save_city(): void {
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}

		$term_id  = absint( wp_unslash( $_POST['term_id'] ?? 0 ) );
		$name     = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$image_id = absint( wp_unslash( $_POST['image_id'] ?? 0 ) );

		if ( ! $name ) {
			wp_send_json_error( [ 'message' => __( 'City name is required.', 'taxi-rental' ) ] );
		}

		if ( $term_id ) {
			$result = wp_update_term( $term_id, 'city', [ 'name' => $name ] );
		} else {
			$result = wp_insert_term( $name, 'city' );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$saved_id = $term_id ?: (int) ( $result['term_id'] ?? 0 );

		if ( $image_id ) {
			update_term_meta( $saved_id, 'tb_image_id', $image_id );
		} else {
			delete_term_meta( $saved_id, 'tb_image_id' );
		}

		$term      = get_term( $saved_id, 'city' );
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium_large' ) : '';

		wp_send_json_success( [
			'message'   => __( 'Saved.', 'taxi-rental' ),
			'term_id'   => $saved_id,
			'name'      => $term instanceof \WP_Term ? $term->name : $name,
			'slug'      => $term instanceof \WP_Term ? $term->slug : '',
			'image_url' => $image_url,
		] );
	}

	public static function ajax_fa_delete_city(): void {
		$nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		$term_id = absint( wp_unslash( $_POST['term_id'] ?? 0 ) );
		if ( ! $term_id || ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}

		$result = wp_delete_term( $term_id, 'city' );
		if ( is_wp_error( $result ) || ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Deletion error.', 'taxi-rental' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'City deleted.', 'taxi-rental' ) ] );
	}

	// ── Category CRUD (front-admin AJAX) ──────────────────────────────────────

	public static function ajax_fa_save_category(): void {
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}

		$term_id       = absint( wp_unslash( $_POST['term_id'] ?? 0 ) );
		$name          = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$children_raw  = sanitize_text_field( wp_unslash( $_POST['children'] ?? '[]' ) );

		if ( ! $name ) {
			wp_send_json_error( [ 'message' => __( 'Block name is required.', 'taxi-rental' ) ] );
		}

		$children_data = json_decode( $children_raw, true );
		if ( ! is_array( $children_data ) ) {
			$children_data = [];
		}

		// Save / update parent term.
		if ( $term_id ) {
			$result = wp_update_term( $term_id, 'ride_category', [ 'name' => $name ] );
		} else {
			$result = wp_insert_term( $name, 'ride_category' );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$parent_id = $term_id ?: (int) ( $result['term_id'] ?? 0 );

		// Existing children IDs.
		$existing_ids = get_terms( [
			'taxonomy'   => 'ride_category',
			'parent'     => $parent_id,
			'hide_empty' => false,
			'fields'     => 'ids',
		] );
		if ( is_wp_error( $existing_ids ) ) {
			$existing_ids = [];
		}
		$existing_ids = array_map( 'intval', (array) $existing_ids );

		// Submitted existing-child IDs (id > 0).
		$submitted_ids = [];
		foreach ( $children_data as $c ) {
			$cid = isset( $c['id'] ) ? (int) $c['id'] : 0;
			if ( $cid > 0 ) {
				$submitted_ids[] = $cid;
			}
		}

		// Delete children removed by the user.
		foreach ( $existing_ids as $eid ) {
			if ( ! in_array( $eid, $submitted_ids, true ) ) {
				wp_delete_term( $eid, 'ride_category' );
			}
		}

		// Update existing / create new children.
		foreach ( $children_data as $c ) {
			$cid   = isset( $c['id'] ) ? (int) $c['id'] : 0;
			$cname = sanitize_text_field( $c['name'] ?? '' );
			if ( ! $cname ) {
				continue;
			}
			if ( $cid > 0 && in_array( $cid, $existing_ids, true ) ) {
				wp_update_term( $cid, 'ride_category', [ 'name' => $cname ] );
			} else {
				wp_insert_term( $cname, 'ride_category', [ 'parent' => $parent_id ] );
			}
		}

		wp_send_json_success( [
			'message' => __( 'Saved.', 'taxi-rental' ),
			'term_id' => $parent_id,
		] );
	}

	public static function ajax_fa_delete_category(): void {
		$nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		$term_id = absint( wp_unslash( $_POST['term_id'] ?? 0 ) );
		if ( ! $term_id || ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}

		// Delete children first (if this is a parent term).
		$children = get_terms( [
			'taxonomy'   => 'ride_category',
			'parent'     => $term_id,
			'hide_empty' => false,
			'fields'     => 'ids',
		] );
		if ( ! is_wp_error( $children ) ) {
			foreach ( (array) $children as $child_id ) {
				wp_delete_term( (int) $child_id, 'ride_category' );
			}
		}

		$result = wp_delete_term( $term_id, 'ride_category' );
		if ( is_wp_error( $result ) || ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Deletion error.', 'taxi-rental' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Deleted.', 'taxi-rental' ) ] );
	}

	// ── Blog: save post ───────────────────────────────────────────────────────

	/**
	 * Save a car_listing from the custom (non-ACF) front-admin edit form.
	 * Writes ACF fields by key, sets gallery + featured image, title, and
	 * city / ride_category / platform taxonomies. Status is left unchanged
	 * (moderation is handled by the separate status buttons).
	 */
	public static function ajax_fa_save_listing(): void {
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}
		if ( ! function_exists( 'update_field' ) ) {
			wp_send_json_error( [ 'message' => __( 'ACF Pro is not activated.', 'taxi-rental' ) ] );
		}

		$post_id = absint( wp_unslash( $_POST['post_id'] ?? 0 ) );
		$post    = $post_id ? get_post( $post_id ) : null;
		if ( ! $post || 'car_listing' !== $post->post_type ) {
			wp_send_json_error( [ 'message' => __( 'Listing not found.', 'taxi-rental' ) ] );
		}

		// ── Collect + sanitize input ────────────────────────────────
		$make_term_id  = absint( wp_unslash( $_POST['make_term'] ?? 0 ) );
		$model_term_id = absint( wp_unslash( $_POST['model_term'] ?? 0 ) );
		$make          = self::car_make_term_name( $make_term_id );
		$model         = self::car_make_term_name( $model_term_id );
		$year         = absint( wp_unslash( $_POST['year'] ?? 0 ) );
		$engine_type  = sanitize_key( wp_unslash( $_POST['engine_type'] ?? '' ) );
		$transmission = sanitize_key( wp_unslash( $_POST['transmission'] ?? '' ) );
		$consumption  = (float) wp_unslash( $_POST['consumption_100km'] ?? 0 );
		$price_week   = absint( wp_unslash( $_POST['price_week'] ?? 0 ) );
		$deposit      = absint( wp_unslash( $_POST['deposit'] ?? 0 ) );
		$mileage      = absint( wp_unslash( $_POST['mileage_limit_month'] ?? 0 ) );
		$min_term     = absint( wp_unslash( $_POST['min_term_days'] ?? 0 ) );
		$insurance    = ! empty( $_POST['insurance'] ) ? 'yes' : 'no';
		$maintenance  = ! empty( $_POST['maintenance_included'] ) ? 'yes' : 'no';
		$description  = sanitize_textarea_field( wp_unslash( $_POST['short_description'] ?? '' ) );

		$gallery_raw  = sanitize_text_field( wp_unslash( $_POST['gallery'] ?? '' ) );
		$gallery_ids  = array_values( array_filter( array_map( 'absint', explode( ',', $gallery_raw ) ) ) );

		$city_id      = absint( wp_unslash( $_POST['city'] ?? 0 ) );
		$cat_ids_raw  = (array) ( $_POST['ride_category'] ?? [] );
		$cat_ids      = array_values( array_filter( array_map( 'absint', wp_unslash( $cat_ids_raw ) ) ) );

		// ── Required validation ─────────────────────────────────────
		if ( '' === $make || '' === $model || $year < 1 || '' === $engine_type || '' === $transmission || $price_week < 1 || empty( $gallery_ids ) || ! $city_id ) {
			wp_send_json_error( [ 'message' => __( 'Please fill in all required fields (make, model, year, fuel type, transmission, price, photo, city).', 'taxi-rental' ) ] );
		}

		// ── Write ACF fields by key ─────────────────────────────────
		update_field( 'field_tb_make', $make, $post_id );
		update_field( 'field_tb_model', $model, $post_id );
		update_field( 'field_tb_year', $year, $post_id );
		update_field( 'field_tb_engine_type', $engine_type, $post_id );
		update_field( 'field_tb_transmission', $transmission, $post_id );
		update_field( 'field_tb_consumption_100km', $consumption ?: '', $post_id );
		update_field( 'field_tb_price_week', $price_week, $post_id );
		update_field( 'field_tb_deposit', $deposit, $post_id );
		update_field( 'field_tb_mileage_limit_month', $mileage, $post_id );
		update_field( 'field_tb_min_term_days', $min_term ?: 1, $post_id );
		update_field( 'field_tb_insurance', $insurance, $post_id );
		update_field( 'field_tb_maintenance_included', $maintenance, $post_id );
		update_field( 'field_tb_short_description', $description, $post_id );
		update_field( 'field_tb_gallery', $gallery_ids, $post_id );

		// Featured image = first gallery photo (so list/catalog thumbnails work).
		set_post_thumbnail( $post_id, $gallery_ids[0] );

		// ── Title from make + model + year ──────────────────────────
		$post_update = [ 'ID' => $post_id ];
		$title       = trim( implode( ' ', array_filter( [ $make, $model, $year > 0 ? (string) $year : '' ] ) ) );
		if ( $title ) {
			$post_update['post_title'] = $title;
			$post_update['post_name']  = sanitize_title( $title );
		}

		// Accent submit button = "Опублікувати" → publish on save.
		$set_status = sanitize_key( wp_unslash( $_POST['set_status'] ?? '' ) );
		if ( 'publish' === $set_status ) {
			$post_update['post_status'] = 'publish';
		}

		if ( count( $post_update ) > 1 ) {
			wp_update_post( $post_update );
		}

		// ── Taxonomies: city + ride_category + derived platform ─────
		wp_set_object_terms( $post_id, [ $city_id ], 'city' );
		wp_set_object_terms( $post_id, $cat_ids, 'ride_category' );
		wp_set_object_terms( $post_id, array_values( array_filter( [ $make_term_id, $model_term_id ] ) ), 'car_make' );

		// Derive platform terms from the parents of the chosen categories.
		$platform_ids = [];
		$parent_names = [];
		foreach ( $cat_ids as $cid ) {
			$term = get_term( $cid, 'ride_category' );
			if ( $term && ! is_wp_error( $term ) && $term->parent ) {
				$parent = get_term( $term->parent, 'ride_category' );
				if ( $parent && ! is_wp_error( $parent ) ) {
					$parent_names[ $parent->name ] = true;
				}
			}
		}
		foreach ( array_keys( $parent_names ) as $pname ) {
			$pt = get_term_by( 'name', $pname, 'platform' );
			if ( $pt ) {
				$platform_ids[] = (int) $pt->term_id;
			}
		}
		wp_set_object_terms( $post_id, $platform_ids, 'platform' );

		wp_send_json_success( [
			'message' => __( 'Changes saved', 'taxi-rental' ),
			'post_id' => $post_id,
		] );
	}

	/**
	 * User-facing add/edit listing.
	 * Allowed for: car_owner, fleet, administrator, editor.
	 * Status: draft (save draft) or pending (send for moderation).
	 */
	public static function ajax_tb_submit_listing(): void {
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'tb_submit_listing' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Authorization required.', 'taxi-rental' ) ], 403 );
		}

		$user          = wp_get_current_user();
		$user_id       = (int) $user->ID;
		$allowed_roles = [ 'car_owner', 'fleet', 'administrator', 'editor' ];
		if ( ! array_intersect( (array) $user->roles, $allowed_roles ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}
		if ( ! function_exists( 'update_field' ) ) {
			wp_send_json_error( [ 'message' => __( 'ACF Pro is not activated.', 'taxi-rental' ) ] );
		}

		// ── Edit mode: verify ownership ─────────────────────────────
		$post_id = absint( wp_unslash( $_POST['post_id'] ?? 0 ) );
		if ( $post_id ) {
			$edit_post = get_post( $post_id );
			if ( ! $edit_post || 'car_listing' !== $edit_post->post_type ) {
				wp_send_json_error( [ 'message' => __( 'Listing not found.', 'taxi-rental' ) ] );
			}
			$is_privileged = (bool) array_intersect( (array) $user->roles, [ 'administrator', 'editor' ] );
			if ( ! $is_privileged && (int) $edit_post->post_author !== $user_id ) {
				wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
			}
		}

		// ── Collect + sanitize ──────────────────────────────────────
		$make_term_id  = absint( wp_unslash( $_POST['make_term'] ?? 0 ) );
		$model_term_id = absint( wp_unslash( $_POST['model_term'] ?? 0 ) );
		$make          = self::car_make_term_name( $make_term_id );
		$model         = self::car_make_term_name( $model_term_id );
		$year         = absint( wp_unslash( $_POST['year'] ?? 0 ) );
		$engine_type  = sanitize_key( wp_unslash( $_POST['engine_type'] ?? '' ) );
		$transmission = sanitize_key( wp_unslash( $_POST['transmission'] ?? '' ) );
		$consumption  = (float) wp_unslash( $_POST['consumption_100km'] ?? 0 );
		$price_week   = absint( wp_unslash( $_POST['price_week'] ?? 0 ) );
		$deposit      = absint( wp_unslash( $_POST['deposit'] ?? 0 ) );
		$mileage      = absint( wp_unslash( $_POST['mileage_limit_month'] ?? 0 ) );
		$min_term     = absint( wp_unslash( $_POST['min_term_days'] ?? 7 ) );
		$insurance    = ! empty( $_POST['insurance'] ) ? 'yes' : 'no';
		$casco        = ! empty( $_POST['casco'] ) ? '1' : '0';
		$maintenance  = ! empty( $_POST['maintenance_included'] ) ? 'yes' : 'no';
		$toll_roads   = ! empty( $_POST['toll_roads'] ) ? '1' : '0';
		$description  = sanitize_textarea_field( wp_unslash( $_POST['short_description'] ?? '' ) );
		$gallery_raw  = sanitize_text_field( wp_unslash( $_POST['gallery'] ?? '' ) );
		$gallery_ids  = array_values( array_filter( array_map( 'absint', explode( ',', $gallery_raw ) ) ) );
		$city_id      = absint( wp_unslash( $_POST['city'] ?? 0 ) );
		$cat_ids_raw  = (array) ( $_POST['ride_category'] ?? [] );
		$cat_ids      = array_values( array_filter( array_map( 'absint', wp_unslash( $cat_ids_raw ) ) ) );

		// ── Status ──────────────────────────────────────────────────
		$set_status_raw = sanitize_key( wp_unslash( $_POST['set_status'] ?? 'draft' ) );
		$is_admin_role  = (bool) array_intersect( (array) $user->roles, [ 'administrator', 'editor' ] );
		if ( 'publish' === $set_status_raw && $is_admin_role ) {
			$new_status = 'publish';
		} elseif ( 'pending' === $set_status_raw ) {
			$new_status = 'pending';
		} else {
			$new_status = 'draft';
		}

		// ── Validation ──────────────────────────────────────────────
		if ( '' === $make || '' === $model || $year < 1 || '' === $engine_type || '' === $transmission || $price_week < 1 || ! $city_id ) {
			wp_send_json_error( [ 'message' => __( 'Please fill in all required fields (make, model, year, engine, transmission, price, city).', 'taxi-rental' ) ] );
		}

		$title = trim( implode( ' ', array_filter( [ $make, $model, $year > 0 ? (string) $year : '' ] ) ) );

		// ── Create or update post ────────────────────────────────────
		if ( $post_id ) {
			wp_update_post( [
				'ID'          => $post_id,
				'post_title'  => $title,
				'post_name'   => sanitize_title( $title ),
				'post_status' => $new_status,
			] );
		} else {
			$post_id = wp_insert_post( [
				'post_type'   => 'car_listing',
				'post_title'  => $title,
				'post_status' => $new_status,
				'post_author' => $user_id,
			] );
			if ( is_wp_error( $post_id ) ) {
				wp_send_json_error( [ 'message' => $post_id->get_error_message() ] );
			}
		}

		// ── ACF fields ──────────────────────────────────────────────
		update_field( 'field_tb_make', $make, $post_id );
		update_field( 'field_tb_model', $model, $post_id );
		update_field( 'field_tb_year', $year, $post_id );
		update_field( 'field_tb_engine_type', $engine_type, $post_id );
		update_field( 'field_tb_transmission', $transmission, $post_id );
		update_field( 'field_tb_consumption_100km', $consumption ?: '', $post_id );
		update_field( 'field_tb_price_week', $price_week, $post_id );
		update_field( 'field_tb_deposit', $deposit, $post_id );
		update_field( 'field_tb_mileage_limit_month', $mileage, $post_id );
		update_field( 'field_tb_min_term_days', $min_term ?: 7, $post_id );
		update_field( 'field_tb_insurance', $insurance, $post_id );
		update_field( 'field_tb_maintenance_included', $maintenance, $post_id );
		update_field( 'field_tb_short_description', $description, $post_id );
		if ( ! empty( $gallery_ids ) ) {
			update_field( 'field_tb_gallery', $gallery_ids, $post_id );
			set_post_thumbnail( $post_id, $gallery_ids[0] );
		}

		// ── Extra meta ──────────────────────────────────────────────
		update_post_meta( $post_id, 'tb_casco', $casco );
		update_post_meta( $post_id, 'tb_toll_roads', $toll_roads );

		// ── Taxonomies ───────────────────────────────────────────────
		wp_set_object_terms( $post_id, [ $city_id ], 'city' );
		wp_set_object_terms( $post_id, $cat_ids, 'ride_category' );
		wp_set_object_terms( $post_id, array_values( array_filter( [ $make_term_id, $model_term_id ] ) ), 'car_make' );

		$platform_ids = [];
		$parent_names = [];
		foreach ( $cat_ids as $cid ) {
			$term = get_term( $cid, 'ride_category' );
			if ( $term && ! is_wp_error( $term ) && $term->parent ) {
				$parent = get_term( $term->parent, 'ride_category' );
				if ( $parent && ! is_wp_error( $parent ) ) {
					$parent_names[ $parent->name ] = true;
				}
			}
		}
		foreach ( array_keys( $parent_names ) as $pname ) {
			$pt = get_term_by( 'name', $pname, 'platform' );
			if ( $pt ) {
				$platform_ids[] = (int) $pt->term_id;
			}
		}
		wp_set_object_terms( $post_id, $platform_ids, 'platform' );

		if ( 'pending' === $new_status ) {
			$toast_key = 'submitted';
		} elseif ( 'draft' === $new_status ) {
			$toast_key = 'draft';
		} else {
			$toast_key = 'updated';
		}

		$redirect = esc_url_raw(
			add_query_arg( $toast_key, '1', tb_url( '/cabinet/' ) )
		);

		wp_send_json_success( [
			'message'  => __( 'Saved', 'taxi-rental' ),
			'post_id'  => $post_id,
			'redirect' => $redirect,
		] );
	}

	public static function ajax_fa_save_post(): void {
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}

		$post_id      = absint( wp_unslash( $_POST['post_id'] ?? 0 ) );
		$post_title   = sanitize_text_field( wp_unslash( $_POST['post_title'] ?? '' ) );
		$post_content = wp_kses_post( wp_unslash( $_POST['post_content'] ?? '' ) );
		$cat_id       = absint( wp_unslash( $_POST['post_category'] ?? 0 ) );
		$post_date    = sanitize_text_field( wp_unslash( $_POST['post_date'] ?? '' ) );
		$post_status  = sanitize_key( wp_unslash( $_POST['post_status'] ?? 'draft' ) );
		$thumb_id     = absint( wp_unslash( $_POST['thumbnail_id'] ?? 0 ) );
		$thumb_clear  = (bool) absint( wp_unslash( $_POST['thumb_cleared'] ?? 0 ) );

		if ( ! $post_title ) {
			wp_send_json_error( [ 'message' => __( 'Post title is required.', 'taxi-rental' ) ] );
		}

		if ( ! in_array( $post_status, [ 'draft', 'publish', 'pending' ], true ) ) {
			$post_status = 'draft';
		}

		$post_data = [
			'post_title'   => $post_title,
			'post_content' => $post_content,
			'post_status'  => $post_status,
			'post_type'    => 'post',
		];

		if ( $post_date ) {
			$post_data['post_date']     = sanitize_text_field( $post_date ) . ' 00:00:00';
			$post_data['post_date_gmt'] = get_gmt_from_date( $post_data['post_date'] );
		}

		if ( $post_id ) {
			$post_data['ID'] = $post_id;
			// Ensure the post is of type 'post' and exists.
			$existing = get_post( $post_id );
			if ( ! $existing || 'post' !== $existing->post_type ) {
				wp_send_json_error( [ 'message' => __( 'Post not found.', 'taxi-rental' ) ] );
			}
			$result = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$saved_id = (int) $result;

		if ( $cat_id ) {
			wp_set_post_categories( $saved_id, [ $cat_id ] );
		}

		if ( $thumb_id ) {
			set_post_thumbnail( $saved_id, $thumb_id );
		} elseif ( $thumb_clear ) {
			delete_post_thumbnail( $saved_id );
		}

		wp_send_json_success( [
			'message' => __( 'Saved.', 'taxi-rental' ),
			'post_id' => $saved_id,
		] );
	}

	// ── Blog: delete post ─────────────────────────────────────────────────────

	public static function ajax_fa_delete_post(): void {
		$nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		$post_id = absint( wp_unslash( $_POST['post_id'] ?? 0 ) );
		if ( ! $post_id || ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}

		$post = get_post( $post_id );
		if ( ! $post || 'post' !== $post->post_type ) {
			wp_send_json_error( [ 'message' => __( 'Post not found.', 'taxi-rental' ) ] );
		}

		$result = wp_trash_post( $post_id );
		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Deletion error.', 'taxi-rental' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Post moved to trash.', 'taxi-rental' ) ] );
	}

	// ── Blog: save blog category ──────────────────────────────────────────────

	public static function ajax_fa_save_blog_cat(): void {
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}

		$term_id = absint( wp_unslash( $_POST['term_id'] ?? 0 ) );
		$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );

		if ( ! $name ) {
			wp_send_json_error( [ 'message' => __( 'Category name is required.', 'taxi-rental' ) ] );
		}

		if ( $term_id ) {
			$result = wp_update_term( $term_id, 'category', [ 'name' => $name ] );
		} else {
			$result = wp_insert_term( $name, 'category' );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$saved_id = $term_id ?: (int) ( $result['term_id'] ?? 0 );
		$term     = get_term( $saved_id, 'category' );

		wp_send_json_success( [
			'message' => __( 'Saved.', 'taxi-rental' ),
			'term_id' => $saved_id,
			'name'    => $term instanceof \WP_Term ? $term->name : $name,
		] );
	}

	// ── Blog: delete blog category ────────────────────────────────────────────

	public static function ajax_fa_delete_blog_cat(): void {
		$nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		$term_id = absint( wp_unslash( $_POST['term_id'] ?? 0 ) );
		if ( ! $term_id || ! wp_verify_nonce( $nonce, 'tb_front_admin_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security error.', 'taxi-rental' ) ], 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'taxi-rental' ) ], 403 );
		}

		$result = wp_delete_term( $term_id, 'category' );
		if ( is_wp_error( $result ) || ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Deletion error.', 'taxi-rental' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Category deleted.', 'taxi-rental' ) ] );
	}

	// ── Users list page ───────────────────────────────────────────────────────

	public static function page_users(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'taxi-rental' ) );
		}

		$role_filter = sanitize_key( $_GET['role_filter'] ?? '' );

		$query_args = [
			'orderby' => 'registered',
			'order'   => 'DESC',
			'number'  => 100,
		];
		if ( $role_filter ) {
			$query_args['role'] = $role_filter;
		}
		$users = get_users( $query_args );
		?>
		<div class="wrap tb-admin-wrap">
			<h1 class="tb-admin-h1"><?php esc_html_e( 'Owners & Fleets', 'taxi-rental' ); ?></h1>

			<div class="tb-admin-tabs">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=taxibaza-users' ) ); ?>"
				   class="tb-admin-tab <?php echo ! $role_filter ? 'is-active' : ''; ?>">
					<?php esc_html_e( 'All', 'taxi-rental' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'role_filter', 'car_owner', admin_url( 'admin.php?page=taxibaza-users' ) ) ); ?>"
				   class="tb-admin-tab <?php echo 'car_owner' === $role_filter ? 'is-active' : ''; ?>">
					<?php esc_html_e( 'Car Owners', 'taxi-rental' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'role_filter', 'fleet', admin_url( 'admin.php?page=taxibaza-users' ) ) ); ?>"
				   class="tb-admin-tab <?php echo 'fleet' === $role_filter ? 'is-active' : ''; ?>">
					<?php esc_html_e( 'Fleets', 'taxi-rental' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'role_filter', 'administrator', admin_url( 'admin.php?page=taxibaza-users' ) ) ); ?>"
				   class="tb-admin-tab <?php echo 'administrator' === $role_filter ? 'is-active' : ''; ?>">
					<?php esc_html_e( 'Admins', 'taxi-rental' ); ?>
				</a>
			</div>

			<div class="tb-admin-section" style="padding:0">
				<table class="widefat tb-admin-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'User', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Role', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Email', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Listing', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Registered', 'taxi-rental' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'taxi-rental' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $users ) ) : ?>
						<tr>
							<td colspan="6" class="tb-admin-empty"><?php esc_html_e( 'No users found.', 'taxi-rental' ); ?></td>
						</tr>
						<?php else : ?>
						<?php foreach ( $users as $user ) :
							// count_user_posts() would miss drafts/pending/rejected/archived.
						$listing_count = tb_listing_count( [ 'publish', 'pending', 'draft', 'rejected', 'archived' ], (int) $user->ID );
							$role_labels   = [
								'administrator' => [ 'label' => 'Admin',         'class' => 'tb-badge--red' ],
								'fleet'         => [ 'label' => 'Fleet',           'class' => 'tb-badge--blue' ],
								'car_owner'     => [ 'label' => 'Car Owner',   'class' => 'tb-badge--gray' ],
								'editor'        => [ 'label' => 'Editor',       'class' => 'tb-badge--yellow' ],
							];
							$primary_role  = ! empty( $user->roles ) ? $user->roles[0] : 'subscriber';
							$role_info     = $role_labels[ $primary_role ] ?? [ 'label' => esc_html( $primary_role ), 'class' => 'tb-badge--gray' ];
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=taxibaza-user-edit&id=' . $user->ID ) ); ?>" class="tb-link-strong">
									<?php echo esc_html( $user->display_name ); ?>
								</a>
							</td>
							<td>
								<span class="tb-badge <?php echo esc_attr( $role_info['class'] ); ?>">
									<?php echo esc_html( $role_info['label'] ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $user->user_email ); ?></td>
							<td>
								<?php if ( $listing_count > 0 ) : ?>
									<a href="<?php echo esc_url( add_query_arg( [ 'author' => $user->ID ], admin_url( 'admin.php?page=taxibaza-listings' ) ) ); ?>">
										<?php echo esc_html( $listing_count ); ?>
									</a>
								<?php else : ?>
									<span class="tb-muted">0</span>
								<?php endif; ?>
							</td>
							<td class="tb-muted">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ) ); ?>
							</td>
							<td>
								<div class="tb-admin-actions-col">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=taxibaza-user-edit&id=' . $user->ID ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Edit', 'taxi-rental' ); ?>
									</a>
									<?php if ( $listing_count > 0 ) : ?>
									<a href="<?php echo esc_url( add_query_arg( [ 'author' => $user->ID ], admin_url( 'admin.php?page=taxibaza-listings' ) ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Listings', 'taxi-rental' ); ?>
									</a>
									<?php endif; ?>
								</div>
							</td>
						</tr>
						<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Tools page — manual seeder trigger
	// -------------------------------------------------------------------------

	public static function page_tools(): void {
		$seeded        = get_option( 'tb_options_seeded_v5' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'TaxiBaza — Tools', 'taxi-rental' ); ?></h1>

			<?php if ( isset( $_GET['seed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<?php if ( 'ok' === sanitize_key( $_GET['seed'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Seed completed successfully. All content updated to Slovak.', 'taxi-rental' ); ?></p></div>
				<?php else : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Error: ACF is unavailable. Check that the ACF Pro plugin is active.', 'taxi-rental' ); ?></p></div>
				<?php endif; ?>
			<?php endif; ?>

			<?php
			// phpcs:ignore WordPress.Security.NonceVerification
			if ( isset( $_GET['updated'] ) ) :
				$upd_status = sanitize_key( $_GET['updated'] );
				// phpcs:ignore WordPress.Security.NonceVerification
				$upd_slug = isset( $_GET['slug'] ) ? sanitize_text_field( wp_unslash( $_GET['slug'] ) ) : '';
				if ( 'ok' === $upd_status ) :
			?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					/* translators: %s: post slug */
					printf( esc_html__( 'Post «%s» updated successfully.', 'taxi-rental' ), esc_html( $upd_slug ) );
					?>
				</p></div>
			<?php elseif ( 'notfound' === $upd_status ) : ?>
				<div class="notice notice-error is-dismissible"><p>
					<?php
					/* translators: %s: post slug */
					printf( esc_html__( 'Error: post with slug «%s» not found.', 'taxi-rental' ), esc_html( $upd_slug ) );
					?>
				</p></div>
			<?php else : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Error updating the post.', 'taxi-rental' ); ?></p></div>
			<?php endif; endif; ?>

			<table class="widefat" style="max-width:600px;margin-top:20px">
				<thead>
					<tr><th colspan="2"><?php esc_html_e( 'Seeders', 'taxi-rental' ); ?></th></tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<strong><?php esc_html_e( 'Slovak Content', 'taxi-rental' ); ?></strong><br>
							<span style="color:#888;font-size:12px">
								<?php echo $seeded ? esc_html__( 'Status: done', 'taxi-rental' ) : esc_html__( 'Status: not done', 'taxi-rental' ); ?>
							</span>
						</td>
						<td style="vertical-align:middle">
							<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
								<?php wp_nonce_field( 'tb_run_seed', 'tb_seed_nonce' ); ?>
								<input type="hidden" name="action" value="tb_run_seed">
								<button type="submit" class="button button-primary">
									<?php esc_html_e( 'Run Seeder', 'taxi-rental' ); ?>
								</button>
							</form>
						</td>
					</tr>
					<tr>
					<td colspan="2">
						<strong><?php esc_html_e( 'Update Post by Slug', 'taxi-rental' ); ?></strong><br>
						<span style="color:#888;font-size:12px"><?php esc_html_e( 'Replaces the title and content of an existing post.', 'taxi-rental' ); ?></span>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin-top:10px">
							<?php wp_nonce_field( 'tb_update_post', 'tb_update_post_nonce' ); ?>
							<input type="hidden" name="action" value="tb_update_post">
							<table class="form-table" style="margin:0">
								<tr>
									<th style="width:140px;padding:4px 0"><label for="tb-up-slug"><?php esc_html_e( 'Slug', 'taxi-rental' ); ?></label></th>
									<td style="padding:4px 0"><input id="tb-up-slug" type="text" name="tb_slug" class="regular-text" required placeholder="my-post-slug"></td>
								</tr>
								<tr>
									<th style="padding:4px 0"><label for="tb-up-title"><?php esc_html_e( 'Name', 'taxi-rental' ); ?></label></th>
									<td style="padding:4px 0"><input id="tb-up-title" type="text" name="tb_title" class="large-text" required></td>
								</tr>
								<tr>
									<th style="padding:4px 0"><label for="tb-up-content"><?php esc_html_e( 'Content (HTML)', 'taxi-rental' ); ?></label></th>
									<td style="padding:4px 0"><textarea id="tb-up-content" name="tb_content" rows="6" class="large-text"></textarea></td>
								</tr>
								<tr>
									<th style="padding:4px 0"><label for="tb-up-type"><?php esc_html_e( 'Post Type', 'taxi-rental' ); ?></label></th>
									<td style="padding:4px 0">
										<select id="tb-up-type" name="tb_post_type">
											<option value="post">post</option>
											<option value="page">page</option>
											<option value="car_listing">car_listing</option>
										</select>
									</td>
								</tr>
							</table>
							<p><button type="submit" class="button button-secondary"><?php esc_html_e( 'Update Post', 'taxi-rental' ); ?></button></p>
						</form>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<?php
}

public static function action_run_seed(): void {
		if ( ! current_user_can( 'manage_options' )
			|| ! isset( $_POST['tb_seed_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_seed_nonce'] ) ), 'tb_run_seed' )
		) {
			wp_die( esc_html__( 'Insufficient permissions.', 'taxi-rental' ) );
		}

		delete_option( 'tb_options_seeded_v5' );
		Seeder::seed_options_defaults();

		$status = get_option( 'tb_options_seeded_v5' ) ? 'ok' : 'fail';

		wp_safe_redirect( add_query_arg(
			[ 'page' => 'taxibaza-tools', 'seed' => $status ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * Resolve a car_make term ID to its name (empty string if invalid).
	 * Used to keep the `make`/`model` text meta (title + display) in sync with
	 * the selected taxonomy terms.
	 */
	private static function car_make_term_name( int $term_id ): string {
		if ( ! $term_id ) {
			return '';
		}
		$term = get_term( $term_id, 'car_make' );
		return ( $term && ! is_wp_error( $term ) ) ? $term->name : '';
	}

	public static function action_update_post(): void {
		if ( ! current_user_can( 'manage_options' )
			|| ! isset( $_POST['tb_update_post_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_update_post_nonce'] ) ), 'tb_update_post' )
		) {
			wp_die( esc_html__( 'Insufficient permissions.', 'taxi-rental' ) );
		}

		$slug      = isset( $_POST['tb_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['tb_slug'] ) ) : '';
		$title     = isset( $_POST['tb_title'] ) ? sanitize_text_field( wp_unslash( $_POST['tb_title'] ) ) : '';
		$content   = isset( $_POST['tb_content'] ) ? wp_kses_post( wp_unslash( $_POST['tb_content'] ) ) : '';
		$post_type = isset( $_POST['tb_post_type'] ) ? sanitize_key( $_POST['tb_post_type'] ) : 'post';

		$result = Seeder::update_post_by_slug( $slug, $title, $content, $post_type );

		if ( is_wp_error( $result ) ) {
			$status = 'notfound' === $result->get_error_code() ? 'notfound' : 'fail';
		} else {
			$status = 'ok';
		}

		wp_safe_redirect( add_query_arg(
			[ 'page' => 'taxibaza-tools', 'updated' => $status, 'slug' => rawurlencode( $slug ) ],
			admin_url( 'admin.php' )
		) );
		exit;
	}
}
