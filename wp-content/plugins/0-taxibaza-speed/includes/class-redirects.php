<?php
/**
 * 301 redirects from the old addresses of the legal pages (changed on 1.11.2026).
 *
 * /konfidenzialnist/ → privacy policy (page 3), /umovy/ → terms of use (page 17),
 * also with the /uk/, /en/, /ru/ prefixes. The target is resolved by page ID and
 * language through WPML, so it follows any future slug change.
 * Only requests that would otherwise be a 404 are redirected.
 *
 * Also the English "add listing" page: its address was the machine-translated
 * /en/user-dashboard/add-stunned/. The slug becomes add-listing (once, in
 * wp-admin; the title is not touched) and the old address answers 301.
 *
 * @package TaxiBaza\Speed
 */

namespace TaxiBaza\Speed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Redirects {

	public const MAP     = [ 'konfidenzialnist' => 3, 'umovy' => 17 ];
	public const ADD     = 12; // "Add listing" page (SK).
	public const OLD_ADD = 'add-stunned';
	public const NEW_ADD = 'add-listing';
	public const LANGS = [ 'uk', 'en', 'ru' ];

	public static function init(): void {
		add_action( 'template_redirect', [ self::class, 'maybe_redirect' ], 1 );
		add_action( 'admin_init', [ self::class, 'fix_en_add_slug' ] );
	}

	/** One-time: English add-listing page slug add-stunned → add-listing. */
	public static function fix_en_add_slug(): void {
		if ( get_option( 'tbs_en_add_slug_fixed' ) || ! has_filter( 'wpml_object_id' ) ) {
			return;
		}
		$id = (int) apply_filters( 'wpml_object_id', self::ADD, 'page', false, 'en' );
		if ( $id && self::OLD_ADD === get_post_field( 'post_name', $id ) ) {
			wp_update_post( [ 'ID' => $id, 'post_name' => self::NEW_ADD ] );
		}
		update_option( 'tbs_en_add_slug_fixed', 1, false );
	}

	/** Current English address of the add-listing page. */
	public static function en_add_url(): string {
		$id  = (int) apply_filters( 'wpml_object_id', self::ADD, 'page', false, 'en' );
		$url = $id ? (string) get_permalink( $id ) : '';
		return '' !== $url ? (string) apply_filters( 'wpml_permalink', $url, 'en', true ) : '';
	}

	/**
	 * Target URL for a request path, or '' if the path is not an old legal address.
	 */
	public static function target( string $path ): string {
		if ( ! preg_match( '#^/(?:(uk|en|ru)/)?(konfidenzialnist|umovy)/?$#', $path, $m ) ) {
			return '';
		}
		$lang = '' !== $m[1] ? $m[1] : (string) apply_filters( 'wpml_default_language', 'sk' );
		$id   = (int) apply_filters( 'wpml_object_id', self::MAP[ $m[2] ], 'page', true, $lang );
		$url  = $id ? get_permalink( $id ) : '';
		if ( $url ) {
			$url = (string) apply_filters( 'wpml_permalink', $url, $lang, true );
		}
		return is_string( $url ) ? $url : '';
	}

	public static function maybe_redirect(): void {
		$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', PHP_URL_PATH );
		if ( preg_match( '#^/en/user-dashboard/' . self::OLD_ADD . '/?$#', $path ) ) {
			$url = self::en_add_url();
			if ( '' !== $url && (string) wp_parse_url( $url, PHP_URL_PATH ) !== $path ) {
				$query = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', PHP_URL_QUERY );
				wp_safe_redirect( '' !== $query ? $url . '?' . $query : $url, 301, 'TaxiBaza' );
				exit;
			}
		}
		if ( ! is_404() ) {
			return;
		}
		$url  = self::target( $path );
		if ( '' !== $url ) {
			wp_safe_redirect( $url, 301, 'TaxiBaza' );
			exit;
		}
	}
}
