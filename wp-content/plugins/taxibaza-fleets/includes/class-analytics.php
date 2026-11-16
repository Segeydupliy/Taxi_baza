<?php
/**
 * GA4 events (G-82EESDNFQV), see assets/ga4-events.js.
 *
 * The script is a separate file on every front-end page, after the theme's
 * main.js — not a Complianz "statistics" script: events go to gtag only once
 * GA is allowed (Complianz loads it after consent) and wait up to 60 s.
 *
 * Events that happen before a redirect are stored once on the server and fired
 * on the next page:
 * - sign_up        { method: email|google, account_type } — new account
 *                   (form or "Pokračovať cez Google");
 * - listing_submit { listing_id, account_type } — a new listing sent to
 *                   moderation (first time, not edits of a live one).
 * Stored in a short-lived cookie (the next page after an e-mail sign-up is the
 * not-logged-in "check your e-mail" screen, so user meta can't be used there).
 * Only IDs and types — no names, e-mails, phones or texts.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Analytics {

	public const COOKIE = 'tbf_ga';

	/** @var array<int,array{0:string,1:array<string,mixed>}> Events to fire on this page. */
	private static array $now = [];

	/** @var array<int,array{0:string,1:array<string,mixed>}> Events stored in this request. */
	private static array $queued = [];

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue' ], 40 );
		add_action( 'template_redirect', [ self::class, 'take' ], 0 );
		add_action( 'user_register', [ self::class, 'on_register' ], 30 );
		add_action( 'transition_post_status', [ self::class, 'on_listing' ], 30, 3 );
	}

	public static function account_type( int $user_id ): string {
		$user  = get_userdata( $user_id );
		$roles = $user ? (array) $user->roles : [];
		foreach ( [ 'fleet', 'car_owner', 'driver' ] as $r ) {
			if ( in_array( $r, $roles, true ) ) {
				return $r;
			}
		}
		return Status::is_fleet( $user_id ) ? 'fleet' : 'car_owner';
	}

	/** @param array<string,mixed> $params Event parameters (IDs and types only). */
	public static function later( string $name, array $params ): void {
		self::$queued[] = [ $name, $params ];
		if ( headers_sent() ) {
			return;
		}
		$value = base64_encode( (string) wp_json_encode( self::$queued ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- cookie-safe JSON.
		setcookie( self::COOKIE, $value, [ 'expires' => time() + 600, 'path' => COOKIEPATH ?: '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ] );
		$_COOKIE[ self::COOKIE ] = $value;
	}

	/** Read the stored events once (before any output) and forget them. */
	public static function take(): void {
		if ( empty( $_COOKIE[ self::COOKIE ] ) || is_admin() || wp_doing_ajax() ) {
			return;
		}
		$list = json_decode( (string) base64_decode( sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ), true ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( ! headers_sent() ) {
			setcookie( self::COOKIE, '', [ 'expires' => time() - 3600, 'path' => COOKIEPATH ?: '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ] );
		}
		unset( $_COOKIE[ self::COOKIE ] );
		foreach ( (array) $list as $e ) {
			if ( is_array( $e ) && isset( $e[0] ) && in_array( $e[0], [ 'sign_up', 'listing_submit' ], true ) ) {
				self::$now[] = [ (string) $e[0], array_map( 'sanitize_text_field', array_map( 'strval', (array) ( $e[1] ?? [] ) ) ) ];
			}
		}
	}

	public static function enqueue(): void {
		if ( is_admin() ) {
			return;
		}
		$deps = wp_script_is( 'taxi-rental-main', 'registered' ) ? [ 'taxi-rental-main' ] : [];
		wp_enqueue_script( 'tbf-ga4-events', plugins_url( 'assets/ga4-events.js', FILE ), $deps, VERSION, true );
		if ( self::$now ) {
			$js = '';
			foreach ( self::$now as $e ) {
				$js .= 'window.tbTrack && window.tbTrack(' . wp_json_encode( $e[0] ) . ', ' . wp_json_encode( (object) $e[1] ) . ');';
			}
			wp_add_inline_script( 'tbf-ga4-events', $js, 'after' );
		}
	}

	/** sign_up: form (tb_action=register) or Google. */
	public static function on_register( $user_id ): void {
		$user_id = (int) $user_id;
		$method  = '';
		if ( isset( $_POST['tb_action'] ) && 'register' === sanitize_key( wp_unslash( $_POST['tb_action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the core verified it.
			$method = 'email';
		} elseif ( isset( $_GET['tb_google'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$method = 'google';
		}
		if ( '' === $method || user_can( $user_id, 'manage_options' ) ) {
			return;
		}
		self::later( 'sign_up', [ 'method' => $method, 'account_type' => self::account_type( $user_id ) ] );
	}

	/**
	 * listing_submit: a new listing sent to moderation from the add-listing form.
	 *
	 * @param string   $new  New status.
	 * @param string   $old  Old status.
	 * @param \WP_Post $post Post.
	 */
	public static function on_listing( $new, $old, $post ): void {
		if ( 'pending' !== $new || ! $post instanceof \WP_Post || 'car_listing' !== $post->post_type ) {
			return;
		}
		if ( ! in_array( $old, [ 'new', 'auto-draft', 'draft' ], true ) || ! wp_doing_ajax() ) {
			return;
		}
		if ( 'tb_submit_listing' !== sanitize_key( wp_unslash( $_POST['action'] ?? '' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the core verified it.
			return;
		}
		self::later( 'listing_submit', [ 'listing_id' => (string) $post->ID, 'account_type' => Status::is_fleet( (int) $post->post_author ) ? 'fleet' : 'car_owner' ] );
	}
}
