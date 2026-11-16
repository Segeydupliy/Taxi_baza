<?php
/**
 * Strings in the site's four languages (SK source, UK, EN, RU), overridable in
 * WPML String Translation (context "taxibaza-fleets"). Unknown languages fall back to SK.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class I18n {

	public const CONTEXT = 'taxibaza-fleets';
	private const SEEDED = 'tbf_strings_registered_v3';

	/** Column of each language in strings.php. */
	public const LANGS = [ 'sk' => 0, 'uk' => 1, 'en' => 2, 'ru' => 3 ];

	/** @var array<string,array{0:string,1:string,2:string,3:string}>|null */
	private static ?array $table = null;

	public static function init(): void {
		add_action( 'admin_init', [ self::class, 'register_with_wpml' ] );
	}

	/** @return array<string,array{0:string,1:string,2:string,3:string}> */
	private static function table(): array {
		if ( null === self::$table ) {
			self::$table = require __DIR__ . '/strings.php';
		}
		return self::$table;
	}

	public static function lang(): string {
		$lang = apply_filters( 'wpml_current_language', null );
		return is_string( $lang ) && '' !== $lang ? $lang : 'sk';
	}

	public static function t( string $key ): string {
		return self::t_in( $key, self::lang() );
	}

	/** Text in a given language (e.g. for an email to a user who reads another language). */
	public static function t_in( string $key, string $lang ): string {
		$row = self::table()[ $key ] ?? null;
		if ( null === $row ) {
			return $key;
		}
		$sk   = $row[0];
		$tr   = apply_filters( 'wpml_translate_single_string', $sk, self::CONTEXT, $key, $lang );
		if ( is_string( $tr ) && $tr !== $sk ) {
			return $tr; // Edited in WPML.
		}
		$col = self::LANGS[ strtolower( substr( $lang, 0, 2 ) ) ] ?? 0;
		return '' !== ( $row[ $col ] ?? '' ) ? $row[ $col ] : $sk;
	}

	/**
	 * Plural form for a count: 'one' | 'few' | 'many'.
	 * SK: 1 / 2–4 / rest. UK, RU: 1, 21, 31… / 2–4, 22–24… / rest (11–14 = many). EN: 1 / rest.
	 */
	public static function plural_form( int $n, string $lang ): string {
		$n    = abs( $n );
		$lang = strtolower( substr( $lang, 0, 2 ) );
		if ( 'en' === $lang ) {
			return 1 === $n ? 'one' : 'many';
		}
		if ( in_array( $lang, [ 'uk', 'ru' ], true ) ) {
			$m10  = $n % 10;
			$m100 = $n % 100;
			if ( 1 === $m10 && 11 !== $m100 ) {
				return 'one';
			}
			return ( $m10 >= 2 && $m10 <= 4 && ( $m100 < 12 || $m100 > 14 ) ) ? 'few' : 'many';
		}
		if ( 1 === $n ) {
			return 'one';
		}
		return ( $n >= 2 && $n <= 4 ) ? 'few' : 'many';
	}

	/**
	 * "%d …" with the right plural form: keys "{$stem}_one", "_few", "_many".
	 */
	public static function plural( int $n, string $stem, ?string $lang = null ): string {
		$lang = $lang ?? self::lang();
		return sprintf( self::t_in( $stem . '_' . self::plural_form( $n, $lang ), $lang ), $n );
	}

	/** Register all strings once so admins can edit them in WPML → String Translation. */
	public static function register_with_wpml(): void {
		if ( get_option( self::SEEDED ) || ! has_action( 'wpml_register_single_string' ) ) {
			return;
		}
		foreach ( self::table() as $key => $row ) {
			do_action( 'wpml_register_single_string', self::CONTEXT, $key, $row[0], false, 'sk' ); // Explicit source language, like the theme's tb_opt().
		}
		update_option( self::SEEDED, 1, false );
	}
}

/**
 * Shortcut used in templates.
 *
 * @param string $key String key from strings.php.
 */
function t( string $key ): string {
	return I18n::t( $key );
}
