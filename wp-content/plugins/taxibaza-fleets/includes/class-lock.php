<?php
/**
 * Locks listing creation for fleets whose profile is not submitted / not allowed.
 *
 * Server side (authoritative):
 *  - AJAX `tb_submit_listing` is rejected before the core handler runs.
 *  - The add-listing page redirects to the cabinet profile tab.
 * Client side (cosmetic): body class `tbf-locked` disables "Add listing" buttons.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Lock {

	public static function init(): void {
		add_action( 'wp_ajax_tb_submit_listing', [ self::class, 'guard_ajax_submit' ], 1 );
		add_action( 'template_redirect', [ self::class, 'guard_add_listing_page' ], 1 );
		add_filter( 'body_class', [ self::class, 'body_class' ] );
		add_action( 'wp_head', [ self::class, 'print_css' ], 99 );
	}

	public static function is_locked( int $user_id ): bool {
		return $user_id > 0 && Status::is_fleet( $user_id ) && ! Status::can_add_listings( $user_id );
	}

	public static function message( int $user_id ): string {
		switch ( Status::get( $user_id ) ) {
			case Status::BLOCKED:
				return t( 'lock_blocked' );
			case Status::REJECTED:
				return t( 'lock_rejected' );
			default:
				return t( 'lock_draft' );
		}
	}

	public static function guard_ajax_submit(): void {
		$user_id = get_current_user_id();
		if ( self::is_locked( $user_id ) ) {
			wp_send_json_error( [ 'message' => self::message( $user_id ) ], 403 );
		}
	}

	public static function guard_add_listing_page(): void {
		if ( ! is_page_template( 'page-add-listing.php' ) ) {
			return;
		}
		$user_id = get_current_user_id();
		if ( ! self::is_locked( $user_id ) ) {
			return;
		}
		$url = function_exists( 'tb_url' ) ? tb_url( '/cabinet/' ) : home_url( '/cabinet/' );
		wp_safe_redirect( add_query_arg( 'tab', 'profile', $url ) );
		exit;
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( array $classes ): array {
		$user_id = get_current_user_id();
		if ( self::is_locked( $user_id ) ) {
			$classes[] = 'tbf-locked';
		}
		if ( $user_id && Status::is_fleet( $user_id ) ) {
			$classes[] = 'tbf-fleet';
			$status    = Status::get( $user_id );
			if ( $status ) {
				$classes[] = 'tbf-status-' . sanitize_html_class( $status );
			}
		}
		return $classes;
	}

	public static function print_css(): void {
		if ( ! self::is_locked( get_current_user_id() ) ) {
			return;
		}
		echo "<style id=\"tbf-lock\">.tbf-locked .tb-cabinet__add-btn,.tbf-locked .tb-cabinet__empty .tb-btn{opacity:.4;pointer-events:none}</style>\n";
	}
}
