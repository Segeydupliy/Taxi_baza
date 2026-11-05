<?php
/**
 * Rental requests — lightweight record of a driver's "send message to owner"
 * submissions so they appear in the driver's cabinet ("Moje žiadosti").
 *
 * There is still no stored conversation (per MVP): a request only records that
 * the driver contacted the owner about a specific listing, plus a snapshot of
 * the submitted contact details. The actual message is delivered by email.
 *
 * @package TaxiRental\Core
 */

namespace TaxiRental\Core;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CPT + helpers for driver rental requests.
 */
final class Requests {

	public const POST_TYPE = 'tb_request';

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'init', [ self::class, 'register' ] );
		add_action( 'admin_post_tb_delete_request', [ self::class, 'handle_delete' ] );
	}

	/**
	 * Register the internal (non-public) request CPT.
	 */
	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'label'               => __( 'Rental Requests', 'taxi-rental' ),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'hierarchical'        => false,
				'supports'            => [ 'title', 'author' ],
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			]
		);
	}

	/**
	 * Store a rental request for a driver.
	 *
	 * @param int                 $driver_id  Logged-in driver's user ID.
	 * @param int                 $listing_id Target car_listing ID.
	 * @param array<string,mixed> $snapshot   Submitted contact fields.
	 * @return int|\WP_Error New request post ID or error.
	 */
	public static function create( int $driver_id, int $listing_id, array $snapshot = [] ) {
		$listing = get_post( $listing_id );
		if ( ! $listing || 'car_listing' !== $listing->post_type ) {
			return new \WP_Error( 'invalid_listing', __( 'Inzerát nebol nájdený.', 'taxi-rental' ) );
		}

		// Avoid duplicate requests for the same listing by the same driver.
		$existing = get_posts(
			[
				'post_type'      => self::POST_TYPE,
				'author'         => $driver_id,
				'post_status'    => 'publish',
				'meta_key'       => 'tb_listing_id',
				'meta_value'     => $listing_id,
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			]
		);
		if ( $existing ) {
			return (int) $existing[0];
		}

		$request_id = wp_insert_post(
			[
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_author' => $driver_id,
				'post_title'  => $listing->post_title,
			],
			true
		);

		if ( is_wp_error( $request_id ) ) {
			return $request_id;
		}

		update_post_meta( $request_id, 'tb_listing_id', $listing_id );
		update_post_meta( $request_id, 'tb_owner_id', (int) $listing->post_author );

		$allowed = [ 'first_name', 'last_name', 'email', 'phone', 'experience', 'term', 'start_date', 'message' ];
		foreach ( $allowed as $key ) {
			if ( isset( $snapshot[ $key ] ) && '' !== $snapshot[ $key ] ) {
				update_post_meta( $request_id, 'tb_' . $key, sanitize_text_field( (string) $snapshot[ $key ] ) );
			}
		}

		return (int) $request_id;
	}

	/**
	 * Fetch a driver's rental requests, newest first.
	 *
	 * @param int $driver_id Driver user ID.
	 * @return \WP_Post[]
	 */
	public static function for_driver( int $driver_id ): array {
		return get_posts(
			[
				'post_type'      => self::POST_TYPE,
				'author'         => $driver_id,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);
	}

	/**
	 * Delete one of the current user's own requests (from the cabinet).
	 */
	public static function handle_delete(): void {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$request_id = absint( wp_unslash( $_POST['request_id'] ?? 0 ) );
		$nonce      = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );

		if ( ! $request_id || ! wp_verify_nonce( $nonce, 'tb_delete_request_' . $request_id ) ) {
			wp_die( esc_html__( 'Neplatná požiadavka.', 'taxi-rental' ) );
		}

		$request = get_post( $request_id );
		if ( $request
			&& self::POST_TYPE === $request->post_type
			&& (int) $request->post_author === get_current_user_id()
		) {
			wp_delete_post( $request_id, true );
		}

		wp_safe_redirect( add_query_arg( 'tab', 'requests', tb_url( '/cabinet/' ) ) );
		exit;
	}
}
