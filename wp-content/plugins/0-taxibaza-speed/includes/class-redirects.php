<?php
/**
 * 301 redirects from the old addresses of the legal pages (changed on 1.11.2026).
 *
 * /konfidenzialnist/ → privacy policy (page 3), /umovy/ → terms of use (page 17),
 * also with the /uk/, /en/, /ru/ prefixes. The target is resolved by page ID and
 * language through WPML, so it follows any future slug change.
 * Only requests that would otherwise be a 404 are redirected.
 *
 * @package TaxiBaza\Speed
 */

namespace TaxiBaza\Speed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Redirects {

	public const MAP   = [ 'konfidenzialnist' => 3, 'umovy' => 17 ];
	public const LANGS = [ 'uk', 'en', 'ru' ];

	public static function init(): void {
		add_action( 'template_redirect', [ self::class, 'maybe_redirect' ], 1 );
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
		if ( ! is_404() ) {
			return;
		}
		$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', PHP_URL_PATH );
		$url  = self::target( $path );
		if ( '' !== $url ) {
			wp_safe_redirect( $url, 301, 'TaxiBaza' );
			exit;
		}
	}
}
