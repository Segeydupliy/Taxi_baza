<?php
/**
 * Fonts from the site itself instead of fonts.googleapis.com (GDPR + speed).
 *
 * The theme enqueues Inter 400/600/700 + Hanken Grotesk 800 from Google and its
 * header.php preconnects to fonts.googleapis.com / fonts.gstatic.com. Here the
 * same handle points to a local stylesheet with the same families and weights
 * (Latin, Latin Extended for Slovak, Cyrillic for UK/RU), and the two
 * preconnect tags are removed from the page. Nothing is sent to Google any more.
 *
 * @package TaxiBaza\Speed
 */

namespace TaxiBaza\Speed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Fonts {

	public const VERSION = '5.3.0';

	public static function init(): void {
		add_filter( 'style_loader_src', [ self::class, 'src' ], 10, 2 );
		add_action( 'template_redirect', [ self::class, 'buffer' ], 0 );
		add_filter( 'wp_resource_hints', [ self::class, 'hints' ], 99 );
	}

	/**
	 * WordPress adds dns-prefetch for the original Google URL; drop it.
	 *
	 * @param array<int,mixed> $urls Hints.
	 */
	public static function hints( $urls ) {
		return array_values( array_filter( (array) $urls, static function ( $u ) {
			$href = is_array( $u ) ? (string) ( $u['href'] ?? '' ) : (string) $u;
			return false === strpos( $href, 'fonts.googleapis.com' ) && false === strpos( $href, 'fonts.gstatic.com' );
		} ) );
	}

	public static function local_url(): string {
		return plugins_url( 'fonts/fonts.css', dirname( __DIR__ ) . '/0-taxibaza-speed.php' ) . '?ver=' . self::VERSION;
	}

	/**
	 * @param string $src    Stylesheet URL.
	 * @param string $handle Handle.
	 */
	public static function src( $src, $handle ) {
		if ( is_string( $src ) && false !== strpos( $src, 'fonts.googleapis.com/css' ) && ( false !== strpos( $src, 'family=Inter' ) || false !== strpos( $src, 'Hanken' ) ) ) {
			return self::local_url();
		}
		return $src;
	}

	public static function buffer(): void {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
			return;
		}
		ob_start( [ self::class, 'strip' ] );
	}

	/** Remove only the preconnect hints to Google Fonts. */
	public static function strip( $html ): string {
		return (string) preg_replace( '#<link\s+rel="preconnect"\s+href="https://fonts\.(?:googleapis|gstatic)\.com"(?:\s+crossorigin)?\s*/?>\s*#i', '', (string) $html );
	}
}
