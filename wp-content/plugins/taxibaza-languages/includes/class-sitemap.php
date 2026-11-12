<?php
/**
 * Yoast sitemap: each translation of a listing under its own language address
 * (/avto/…, /uk/avto/…, /ru/avto/…, /en/avto/…), as the hreflang on the page
 * already does. WPML gave every translation the Slovak address, so the same
 * URL appeared up to 4 times and the other languages were missing.
 *
 * @package TaxiBaza\Languages
 */

namespace TaxiBaza\Languages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Sitemap {

	public static function init(): void {
		add_filter( 'wpseo_xml_sitemap_post_url', [ self::class, 'post_url' ], 20, 2 );
	}

	/** Language folder of an address: 'uk' for …/uk/avto/x/, '' for the main language. */
	public static function url_lang( string $url, array $langs ): string {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$seg  = strtolower( (string) strtok( ltrim( $path, '/' ), '/' ) );
		return in_array( $seg, $langs, true ) ? $seg : '';
	}

	/** Put an address into a language folder (or out of it for the main language). */
	public static function in_lang( string $url, string $lang, string $main, array $langs ): string {
		$parts = wp_parse_url( $url );
		if ( empty( $parts['host'] ) ) {
			return $url;
		}
		$path = ltrim( (string) ( $parts['path'] ?? '/' ), '/' );
		$seg  = strtolower( (string) strtok( $path, '/' ) );
		if ( in_array( $seg, $langs, true ) ) {
			$path = (string) substr( $path, strlen( $seg ) + 1 );
		}
		$prefix = $lang !== $main && '' !== $lang ? $lang . '/' : '';
		$out    = ( $parts['scheme'] ?? 'https' ) . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' ) . '/' . $prefix . $path;
		return isset( $parts['query'] ) ? $out . '?' . $parts['query'] : $out;
	}

	/**
	 * @param string   $url  Address Yoast is about to print.
	 * @param \WP_Post $post Post.
	 */
	public static function post_url( $url, $post ) {
		if ( ! is_string( $url ) || ! $post instanceof \WP_Post || 'car_listing' !== $post->post_type || ! Groups::wpml() ) {
			return $url;
		}
		$lang = (string) apply_filters( 'wpml_element_language_code', '', [ 'element_id' => $post->ID, 'element_type' => 'car_listing' ] );
		$main = (string) apply_filters( 'wpml_default_language', '' );
		if ( '' === $lang || '' === $main ) {
			return $url;
		}
		$langs = array_keys( (array) apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] ) );
		$langs = $langs ?: [ 'sk', 'uk', 'en', 'ru' ];
		return self::url_lang( $url, $langs ) === ( $lang === $main ? '' : $lang ) ? $url : self::in_lang( $url, $lang, $main, $langs );
	}
}
