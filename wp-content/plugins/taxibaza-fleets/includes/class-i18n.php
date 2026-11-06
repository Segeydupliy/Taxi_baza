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
	private const SEEDED = 'tbf_strings_registered_v1';

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
		$row = self::table()[ $key ] ?? null;
		if ( null === $row ) {
			return $key;
		}
		$lang = self::lang();
		$sk   = $row[0];
		$tr   = apply_filters( 'wpml_translate_single_string', $sk, self::CONTEXT, $key, $lang );
		if ( is_string( $tr ) && $tr !== $sk ) {
			return $tr; // Edited in WPML.
		}
		$col = self::LANGS[ strtolower( substr( $lang, 0, 2 ) ) ] ?? 0;
		return '' !== ( $row[ $col ] ?? '' ) ? $row[ $col ] : $sk;
	}

	/** Register all strings once so admins can edit them in WPML → String Translation. */
	public static function register_with_wpml(): void {
		if ( get_option( self::SEEDED ) || ! has_action( 'wpml_register_single_string' ) ) {
			return;
		}
		foreach ( self::table() as $key => $row ) {
			do_action( 'wpml_register_single_string', self::CONTEXT, $key, $row[0] );
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
