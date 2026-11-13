<?php
/**
 * Plugin Name:       TaxiBaza Languages
 * Description:       Listings in every language for TaxiBaza.sk (WPML stays in manual mode): one list of all languages in the cabinet and in /panel/, the catalogue never picks a deleted or unpublished translation, one sitemap address per language, cleanup of broken translation links, and automatic DeepL translation of a listing's description when it is published.
 * Version:           1.0.2
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Results Factory
 * License:           GPL-2.0-or-later
 * Text Domain:       taxibaza-languages
 *
 * Works on top of taxi-rental-core, the taxi-rental theme and WPML without
 * changing their code. Deactivating returns the previous behaviour; no data is
 * changed except by the tools in Tools → TaxiBaza Languages and by the
 * automatic translation (which only creates translations that don't exist).
 *
 * @package TaxiBaza\Languages
 */

namespace TaxiBaza\Languages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION = '1.0.2';

require_once __DIR__ . '/includes/class-groups.php';
require_once __DIR__ . '/includes/class-scope.php';
require_once __DIR__ . '/includes/class-sitemap.php';
require_once __DIR__ . '/includes/class-deepl.php';
require_once __DIR__ . '/includes/class-auto-translate.php';
require_once __DIR__ . '/includes/class-tools.php';

add_action(
	'plugins_loaded',
	static function (): void {
		Scope::init();
		Sitemap::init();
		Auto_Translate::init();
		Tools::init();
	},
	20
);
