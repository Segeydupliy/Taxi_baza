<?php

namespace TaxiRental\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Capabilities {
	public static function init(): void {
		add_filter( 'user_has_cap',               [ self::class, 'own_listings_only' ],    10, 4 );
		add_filter( 'show_admin_bar',             [ self::class, 'hide_admin_bar' ],        10, 1 );
		add_action( 'admin_init',                 [ self::class, 'block_backend_access' ] );
		add_filter( 'ajax_query_attachments_args', [ self::class, 'own_media_only' ] );
	}

	private static function is_frontend_role(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		return (bool) array_intersect( [ 'car_owner', 'fleet', 'driver' ], (array) wp_get_current_user()->roles );
	}

	/**
	 * Hide the WordPress admin bar for car_owner / fleet roles.
	 */
	public static function hide_admin_bar( bool $show ): bool {
		return self::is_frontend_role() ? false : $show;
	}

	/**
	 * Redirect car_owner / fleet away from wp-admin.
	 * AJAX requests and admin-post.php form submissions are allowed through.
	 */
	public static function block_backend_access(): void {
		if ( wp_doing_ajax() ) {
			return;
		}
		global $pagenow;
		if ( 'admin-post.php' === $pagenow ) {
			return;
		}
		if ( self::is_frontend_role() ) {
			wp_safe_redirect( tb_url( '/cabinet/' ) );
			exit;
		}
	}

	/**
	 * Restrict the media library to attachments uploaded by the current user
	 * when the user is a car_owner or fleet member.
	 *
	 * @param array<string,mixed> $query WP_Query args for the media modal.
	 * @return array<string,mixed>
	 */
	public static function own_media_only( array $query ): array {
		if ( self::is_frontend_role() ) {
			$query['author'] = get_current_user_id();
		}
		return $query;
	}

	/**
	 * Extra guard: car_owner / fleet can only edit/delete their own car_listing posts.
	 * The CPT capability_type + map_meta_cap handles this natively, but this filter
	 * provides a hard fallback in case a 3rd-party plugin bypasses meta cap mapping.
	 *
	 * @param bool[]   $allcaps All user capabilities.
	 * @param string[] $caps    Required primitive capabilities.
	 * @param mixed[]  $args    [0] meta cap, [1] user_id, [2] post_id.
	 * @param \WP_User $user    Current user.
	 * @return bool[]
	 */
	public static function own_listings_only(
		array $allcaps,
		array $caps,
		array $args,
		\WP_User $user
	): array {
		$meta_cap = $args[0] ?? '';

		if ( ! in_array( $meta_cap, [ 'edit_car_listing', 'delete_car_listing' ], true ) ) {
			return $allcaps;
		}

		if ( ! array_intersect( [ 'car_owner', 'fleet' ], (array) $user->roles ) ) {
			return $allcaps;
		}

		$post_id = isset( $args[2] ) ? (int) $args[2] : 0;
		if ( ! $post_id ) {
			return $allcaps;
		}

		$post = get_post( $post_id );
		if ( ! $post || 'car_listing' !== $post->post_type ) {
			return $allcaps;
		}

		if ( (int) $post->post_author !== (int) $user->ID ) {
			foreach ( $caps as $cap ) {
				$allcaps[ $cap ] = false;
			}
		}

		return $allcaps;
	}
}
