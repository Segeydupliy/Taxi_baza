<?php
/**
 * rel="nofollow" on links that only change filters or sorting of a list
 * (catalogue /avto/, city pages, /flotily/ …). Google had found ~59 500 such
 * combinations; robots.txt already blocks crawling them, nofollow stops the
 * links from inviting it. Plain links and pagination stay as they are.
 * Done on the finished HTML, so no theme file changes.
 *
 * @package TaxiBaza\Speed
 */

namespace TaxiBaza\Speed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Filter_Nofollow {

	/** Query parameters of the catalogue and fleet filters / sorting. */
	public const KEYS = [ 'cat', 'city', 'platform', 'make', 'model', 'year', 'tb_year', 'fuel', 'transmission', 'price_min', 'price_max', 'deposit_min', 'deposit_max', 'orderby', 'order', 'sort' ];

	public static function init(): void {
		add_action( 'template_redirect', [ self::class, 'start' ], 99 );
	}

	public static function start(): void {
		if ( is_admin() || wp_doing_ajax() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return;
		}
		ob_start( [ self::class, 'filter_html' ] );
	}

	public static function is_filter_url( string $href ): bool {
		$query = (string) wp_parse_url( html_entity_decode( $href ), PHP_URL_QUERY );
		if ( '' === $query ) {
			return false;
		}
		$host = (string) wp_parse_url( $href, PHP_URL_HOST );
		if ( '' !== $host && $host !== (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			return false;
		}
		parse_str( $query, $args );
		foreach ( array_keys( $args ) as $k ) {
			if ( in_array( $k, self::KEYS, true ) || str_starts_with( (string) $k, 'f_' ) ) {
				return true;
			}
		}
		return false;
	}

	public static function filter_html( $html ) {
		if ( ! is_string( $html ) || ! str_contains( $html, '<a' ) ) {
			return $html;
		}
		return (string) preg_replace_callback(
			'/<a\b([^>]*)>/i',
			static function ( $m ) {
				$attrs = $m[1];
				if ( ! preg_match( '/\shref\s*=\s*(["\'])(.*?)\1/is', $attrs, $h ) || ! self::is_filter_url( $h[2] ) ) {
					return $m[0];
				}
				if ( preg_match( '/\srel\s*=\s*(["\'])(.*?)\1/is', $attrs, $r ) ) {
					if ( preg_match( '/\bnofollow\b/i', $r[2] ) ) {
						return $m[0];
					}
					$attrs = str_replace( $r[0], ' rel=' . $r[1] . trim( $r[2] . ' nofollow' ) . $r[1], $attrs );
					return '<a' . $attrs . '>';
				}
				return '<a' . $attrs . ' rel="nofollow">';
			},
			$html
		);
	}
}
