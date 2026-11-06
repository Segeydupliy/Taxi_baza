<?php
/**
 * Plugin Name:       TaxiBaza Speed
 * Description:       Production fixes for speed and safety: Google Fonts served locally, 301 redirects from the old legal-page addresses, stops WPML from rewriting the theme's options strings on every page view (Slovak value is always the source), stops logging PHP notices and deprecations on every request (WP_DEBUG_LOG is on in wp-config.php) and blocks public access to *.log files in wp-content. Deactivating restores the previous behaviour.
 * Version:           1.2.4
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Focus Emotion
 * License:           GPL-2.0-or-later
 *
 * The folder name starts with "0-" so WordPress loads it before other plugins.
 * Real errors (fatal, warning) are still written to the log; only the noise is cut.
 * How translations are looked up (theme, core, WPML) is not changed.
 *
 * @package TaxiBaza\Speed
 */

namespace TaxiBaza\Speed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MARKER = 'TaxiBaza Speed: no public logs';

require_once __DIR__ . '/includes/class-wpml-guard.php';
require_once __DIR__ . '/includes/class-wpml-repair.php';
require_once __DIR__ . '/includes/class-fonts.php';
require_once __DIR__ . '/includes/class-redirects.php';
WPML_Guard::init();
Fonts::init();
Redirects::init();
if ( is_admin() ) {
	WPML_Repair::init();
}

/**
 * 1. Log only real problems. With WP_DEBUG on, PHP 8.5 + WPML/ACF/Yoast produce
 *    deprecation notices on every request, and each one is appended to a ~2 GB
 *    debug.log. Errors and warnings are kept.
 */
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	error_reporting( E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_USER_NOTICE ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting,WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting

	// WordPress' own "deprecated" / "doing it wrong" messages: don't build and log them.
	foreach ( [ 'deprecated_function_trigger_error', 'deprecated_argument_trigger_error', 'deprecated_class_trigger_error', 'deprecated_file_trigger_error', 'deprecated_hook_trigger_error', 'doing_it_wrong_trigger_error' ] as $filter ) {
		add_filter( $filter, '__return_false', 99 );
	}
}

/**
 * 2. Deny web access to *.log in wp-content (debug.log was downloadable by anyone).
 */
function htaccess_path(): string {
	return WP_CONTENT_DIR . '/.htaccess';
}

function rules(): array {
	return [
		'<FilesMatch "\.log$">',
		'	<IfModule mod_authz_core.c>',
		'		Require all denied',
		'	</IfModule>',
		'	<IfModule !mod_authz_core.c>',
		'		Order allow,deny',
		'		Deny from all',
		'	</IfModule>',
		'</FilesMatch>',
	];
}

function write_rules( array $lines ): bool {
	if ( ! function_exists( 'insert_with_markers' ) ) {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}
	return (bool) insert_with_markers( htaccess_path(), MARKER, $lines );
}

register_activation_hook( __FILE__, static fn() => write_rules( rules() ) );
register_deactivation_hook( __FILE__, static fn() => write_rules( [] ) );

// Re-apply if the block was lost (e.g. the file was replaced), at most once a day.
add_action(
	'admin_init',
	static function (): void {
		if ( get_transient( 'tbs_rules_checked' ) ) {
			return;
		}
		set_transient( 'tbs_rules_checked', 1, DAY_IN_SECONDS );
		$current = is_readable( htaccess_path() ) ? (string) file_get_contents( htaccess_path() ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === strpos( $current, '# BEGIN ' . MARKER ) ) {
			write_rules( rules() );
		}
	}
);
