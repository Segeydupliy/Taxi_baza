<?php
/**
 * Letters to administrators always in Slovak.
 *
 * The core builds its e-mails from ACF option templates translated by WPML
 * String Translation into the current language — for letters to the admin
 * that was the language of whoever submitted the listing or registered.
 * Templates whose key contains "_admin_" (tb_email_new_user_admin_subject,
 * tb_email_pending_admin_body …) now always use their Slovak original.
 *
 * @package TaxiBaza\Languages
 */

namespace TaxiBaza\Languages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin_Mail {

	public static function init(): void {
		add_filter( 'wpml_translate_single_string', [ self::class, 'slovak_original' ], 99, 3 );
	}

	public static function is_admin_template( string $name ): bool {
		return (bool) preg_match( '/^tb_email_[a-z0-9_]*_admin_(subject|body)$/', $name );
	}

	/**
	 * @param mixed  $value   Translated value.
	 * @param string $context String context.
	 * @param string $name    String name.
	 * @return mixed
	 */
	public static function slovak_original( $value, $context = '', $name = '' ) {
		if ( 'taxi-rental-options' !== $context || ! is_string( $name ) || ! self::is_admin_template( $name ) ) {
			return $value;
		}
		$sk = (string) get_option( 'options_' . $name, '' ); // ACF options of the main language.
		return '' !== $sk ? $sk : $value;
	}
}
