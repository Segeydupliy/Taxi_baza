<?php
/**
 * Fleet cabinet: the "Мій профіль" tab of /cabinet/ is served by the plugin for
 * fleet accounts (Figma: Flotilla Profile / Flotilla - Edit / Register Step 2.x).
 * Theme files are not modified: we swap the template for that one request.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Cabinet {

	public static function init(): void {
		add_action( 'template_redirect', [ self::class, 'force_profile_for_draft' ], 2 );
		add_filter( 'template_include', [ self::class, 'swap_template' ], 99 );
		add_action( 'wp_enqueue_scripts', [ self::class, 'assets' ] );
	}

	private static function is_cabinet(): bool {
		return is_page_template( 'page-dashboard.php' );
	}

	private static function current_tab(): string {
		return isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	private static function serves_request(): bool {
		$uid = get_current_user_id();
		return $uid && self::is_cabinet() && Status::is_fleet( $uid ) && 'profile' === self::current_tab();
	}

	public static function profile_url( array $args = [] ): string {
		$base = function_exists( 'tb_url' ) ? tb_url( '/cabinet/' ) : home_url( '/cabinet/' );
		return add_query_arg( array_merge( [ 'tab' => 'profile' ], $args ), $base );
	}

	public static function tab_url( string $tab ): string {
		$base = function_exists( 'tb_url' ) ? tb_url( '/cabinet/' ) : home_url( '/cabinet/' );
		return add_query_arg( 'tab', $tab, $base );
	}

	/** A fleet that has not submitted its profile lands on the profile form. */
	public static function force_profile_for_draft(): void {
		$uid = get_current_user_id();
		if ( ! $uid || ! self::is_cabinet() || ! Status::is_fleet( $uid ) ) {
			return;
		}
		if ( Status::DRAFT === Status::get( $uid ) && ! in_array( self::current_tab(), [ 'profile', 'settings' ], true ) ) {
			wp_safe_redirect( self::profile_url() );
			exit;
		}
	}

	/**
	 * @param string $template Template path.
	 */
	public static function swap_template( $template ) {
		return self::serves_request() ? dirname( __DIR__ ) . '/templates/cabinet-fleet.php' : $template;
	}

	public static function assets(): void {
		if ( ! self::serves_request() ) {
			return;
		}
		$base = plugins_url( 'assets/', dirname( __DIR__ ) . '/taxibaza-fleets.php' );
		wp_enqueue_style( 'tbf-cabinet', $base . 'cabinet.css', [], VERSION );
		self::enqueue_form_script();
	}

	/** Script of the profile form (validation, show/hide, logo preview). */
	public static function enqueue_form_script(): void {
		$base = plugins_url( 'assets/', dirname( __DIR__ ) . '/taxibaza-fleets.php' );
		wp_enqueue_script( 'tbf-cabinet', $base . 'cabinet.js', [], VERSION, true );
		wp_localize_script(
			'tbf-cabinet',
			'tbfCabinet',
			[
				'i18n' => [
					'required' => t( 'err_required' ),
					'number'   => t( 'err_number' ),
					'phone'    => t( 'err_phone' ),
					'pickOne'  => t( 'err_pick_one' ),
					'logo'     => t( 'err_logo' ),
					'readMore' => t( 'read_more' ),
					'collapse' => t( 'collapse' ),
					'feeFixed' => t( 'own_fee_fixed' ),
					'feePct'   => t( 'own_fee' ),
				],
			]
		);
	}
}
