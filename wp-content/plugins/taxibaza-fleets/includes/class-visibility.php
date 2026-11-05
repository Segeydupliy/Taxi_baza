<?php
/**
 * Hides listings of fleets that are not approved (or are blocked) from the
 * public catalog, without touching the listings' own post status.
 *
 * Covers every public query of published `car_listing` posts: catalog archive,
 * city pages, AJAX catalog filtering (tb_filter_listings), front page and
 * "similar" blocks, plus the single listing page (404).
 *
 * Never filters: wp-admin screens, /panel/ AJAX (tb_fa_*), queries limited to
 * one author (cabinet "my listings"), non-published status lists.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Visibility {

	public static function init(): void {
		add_action( 'pre_get_posts', [ self::class, 'filter_query' ], 20 );
		add_action( 'template_redirect', [ self::class, 'guard_single' ], 5 );
	}

	/**
	 * Decide whether a query is a public listing query we must filter.
	 *
	 * @param \WP_Query $query Query.
	 */
	public static function should_filter( $query ): bool {
		// Plain wp-admin screens: never. AJAX runs with is_admin() === true, so check it separately.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}
		if ( wp_doing_ajax() ) {
			$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 0 === strpos( $action, 'tb_fa_' ) ) {
				return false; // Front admin panel.
			}
		}

		// Server-rendered /panel/ tabs: admins must see everything there.
		if ( ! $query->is_main_query() && did_action( 'wp' ) && is_page_template( 'page-front-admin.php' ) ) {
			return false;
		}

		$post_type = $query->get( 'post_type' );
		$types     = (array) $post_type;
		$is_single = $query->is_main_query() && $query->is_singular() && in_array( 'car_listing', $types, true );
		if ( ! in_array( 'car_listing', $types, true ) ) {
			return false;
		}
		if ( $is_single ) {
			return false; // Handled by guard_single() so the author can still preview.
		}

		// Queries scoped to an author (cabinet, user detail) are private views.
		if ( $query->get( 'author' ) || $query->get( 'author__in' ) || $query->get( 'author_name' ) ) {
			return false;
		}

		$status = $query->get( 'post_status' );
		if ( '' === $status || null === $status ) {
			return true; // Front-end default for public post types is "publish".
		}
		return 'publish' === $status || [ 'publish' ] === $status;
	}

	/**
	 * @param \WP_Query $query Query.
	 */
	public static function filter_query( $query ): void {
		if ( ! self::should_filter( $query ) ) {
			return;
		}
		$hidden = Status::hidden_authors();
		if ( ! $hidden ) {
			return;
		}
		$existing = array_filter( array_map( 'intval', (array) $query->get( 'author__not_in' ) ) );
		$query->set( 'author__not_in', array_values( array_unique( array_merge( $existing, $hidden ) ) ) );
	}

	/** Single listing of a hidden fleet: 404 for everyone except its author and admins. */
	public static function guard_single(): void {
		if ( ! is_singular( 'car_listing' ) ) {
			return;
		}
		$post = get_queried_object();
		if ( ! $post || empty( $post->post_author ) ) {
			return;
		}
		$author = (int) $post->post_author;
		if ( Status::listings_visible( $author ) ) {
			return;
		}
		if ( get_current_user_id() === $author || current_user_can( 'manage_options' ) ) {
			return;
		}
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
