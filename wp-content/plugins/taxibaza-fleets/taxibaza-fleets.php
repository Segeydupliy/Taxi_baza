<?php
/**
 * Plugin Name:       TaxiBaza Fleets
 * Description:       Fleet (flotila) profiles, profile moderation and visibility of fleet listings for TaxiBaza.sk. Works on top of taxi-rental-core without changing its data.
 * Version:           0.3.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Focus Emotion
 * Text Domain:       taxibaza-fleets
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION = '0.3.0';
const FILE    = __FILE__;

require_once __DIR__ . '/includes/class-i18n.php';
require_once __DIR__ . '/includes/class-status.php';
require_once __DIR__ . '/includes/class-visibility.php';
require_once __DIR__ . '/includes/class-lock.php';
require_once __DIR__ . '/includes/class-role-switch.php';
require_once __DIR__ . '/includes/class-profile.php';
require_once __DIR__ . '/includes/class-cabinet.php';
require_once __DIR__ . '/includes/class-admin-panel.php';
require_once __DIR__ . '/includes/class-registration.php';
require_once __DIR__ . '/includes/class-notifications.php';

add_action(
	'plugins_loaded',
	static function (): void {
		I18n::init();
		Status::init();
		Visibility::init();
		Lock::init();
		Role_Switch::init();
		Profile::init();
		Cabinet::init();
		Admin_Panel::init();
		Registration::init();
		Notifications::init();
	},
	20
);

register_activation_hook( __FILE__, [ Status::class, 'on_activate' ] );
