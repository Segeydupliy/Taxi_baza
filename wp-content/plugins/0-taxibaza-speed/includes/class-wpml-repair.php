<?php
/**
 * One-off repair of the theme's options strings in WPML (Tools → TaxiBaza WPML).
 *
 * Target scheme (as requested): every string of the context `taxi-rental-options`
 * has Slovak as the source language and the Slovak options-page value as the
 * source text; Ukrainian/English/Russian are translations.
 *
 * What visitors currently see on a language version L is: the WPML translation
 * into L if there is one and L is not the string's (wrong) source language,
 * otherwise the ACF option value for L (options_<lang>_<name>). The repair keeps
 * exactly that, so the visible texts don't change:
 *  - source language → sk, source value → options_<name> (Slovak);
 *  - for the old source language L (uk/en/ru), whose WPML translation was ignored
 *    until now, the translation is set to what was shown: the ACF value for L.
 * Every change is listed in a dry run first; nothing is written without the button.
 *
 * @package TaxiBaza\Speed
 */

namespace TaxiBaza\Speed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WPML_Repair {

	private const PAGE   = 'tbs-wpml-repair';
	private const NONCE  = 'tbs_wpml_repair';
	private const LANGS  = [ 'uk', 'en', 'ru' ];
	private const STATUS = 10; // ICL_TM_COMPLETE.

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'menu' ] );
	}

	public static function menu(): void {
		add_management_page( 'TaxiBaza WPML', 'TaxiBaza WPML', 'manage_options', self::PAGE, [ self::class, 'render' ] );
	}

	/**
	 * Build the plan.
	 *
	 * @return array{rows:array<int,array<string,mixed>>,stats:array<string,int>}
	 */
	public static function plan(): array {
		global $wpdb;
		$strings = $wpdb->get_results( $wpdb->prepare( "SELECT id, name, language, value FROM {$wpdb->prefix}icl_strings WHERE context = %s ORDER BY id", WPML_Guard::OPTIONS_CTX ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows    = [];
		$stats   = [ 'total' => count( $strings ), 'wrong_lang' => 0, 'value_fix' => 0, 'tr_fix' => 0, 'no_sk' => 0, 'sk_tr_differs' => 0 ];
		foreach ( $strings as $s ) {
			$sk = get_option( 'options_' . $s['name'], null );
			if ( ! is_string( $sk ) || '' === $sk ) {
				$stats['no_sk']++;
				continue;
			}
			$tr = [];
			foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT language, value, status FROM {$wpdb->prefix}icl_string_translations WHERE string_id = %d", $s['id'] ), ARRAY_A ) as $t ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$tr[ $t['language'] ] = $t;
			}
			$row = [ 'id' => (int) $s['id'], 'name' => $s['name'], 'from' => $s['language'], 'old' => $s['value'], 'sk' => $sk, 'set_lang' => 'sk' !== $s['language'], 'set_value' => $s['value'] !== $sk, 'tr' => [] ];
			if ( isset( $tr['sk']['value'] ) && '' !== (string) $tr['sk']['value'] && $tr['sk']['value'] !== $sk ) {
				$row['sk_tr'] = $tr['sk']['value'];
				$stats['sk_tr_differs']++;
			}
			if ( $row['set_lang'] && in_array( $s['language'], self::LANGS, true ) ) {
				$acf = get_option( 'options_' . $s['language'] . '_' . $s['name'], null );
				$cur = $tr[ $s['language'] ]['value'] ?? null;
				if ( is_string( $acf ) && '' !== $acf && $cur !== $acf ) {
					$row['tr'][ $s['language'] ] = [ 'from' => $cur, 'to' => $acf ];
					$stats['tr_fix']++;
				}
			}
			if ( $row['set_lang'] ) {
				$stats['wrong_lang']++;
			}
			if ( $row['set_value'] ) {
				$stats['value_fix']++;
			}
			if ( $row['set_lang'] || $row['set_value'] || $row['tr'] ) {
				$rows[] = $row;
			}
		}
		return [ 'rows' => $rows, 'stats' => $stats ];
	}

	/** Apply the plan. Returns number of strings changed. */
	public static function apply( array $plan ): int {
		global $wpdb;
		$n = 0;
		foreach ( $plan['rows'] as $r ) {
			foreach ( $r['tr'] as $lang => $t ) {
				if ( function_exists( 'icl_add_string_translation' ) ) {
					icl_add_string_translation( $r['id'], $lang, $t['to'], self::STATUS );
				}
			}
			$data = [];
			if ( $r['set_lang'] ) {
				$data['language'] = 'sk';
			}
			if ( $r['set_value'] ) {
				$data['value'] = $r['sk'];
			}
			if ( $data ) {
				$wpdb->update( "{$wpdb->prefix}icl_strings", $data, [ 'id' => $r['id'] ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
			$n++;
		}
		// Let WPML rebuild its caches.
		wp_cache_flush();
		do_action( 'wpml_st_string_updated' );
		WPML_Guard::reset();
		return $n;
	}

	/**
	 * Full picture of the options strings (read only), for review.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function matrix(): array {
		global $wpdb;
		$out = [];
		$strings = $wpdb->get_results( $wpdb->prepare( "SELECT id, name, language, value, status FROM {$wpdb->prefix}icl_strings WHERE context = %s ORDER BY id", WPML_Guard::OPTIONS_CTX ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$all_tr  = $wpdb->get_results( $wpdb->prepare( "SELECT t.string_id, t.language, t.value, t.status FROM {$wpdb->prefix}icl_string_translations t JOIN {$wpdb->prefix}icl_strings s ON s.id = t.string_id WHERE s.context = %s", WPML_Guard::OPTIONS_CTX ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$tr = [];
		foreach ( $all_tr as $t ) {
			$tr[ (int) $t['string_id'] ][ $t['language'] ] = [ (string) $t['value'], (int) $t['status'] ];
		}
		foreach ( $strings as $s ) {
			$acf = [];
			foreach ( [ 'sk' => 'options_', 'uk' => 'options_uk_', 'en' => 'options_en_', 'ru' => 'options_ru_' ] as $l => $prefix ) {
				$v         = get_option( $prefix . $s['name'], null );
				$acf[ $l ] = is_string( $v ) ? $v : null;
			}
			$out[] = [ 'id' => (int) $s['id'], 'n' => $s['name'], 'src' => $s['language'], 'v' => $s['value'], 'tr' => $tr[ (int) $s['id'] ] ?? (object) [], 'acf' => $acf ];
		}
		return $out;
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_GET['matrix'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view for admins.
			echo '<div class="wrap"><h1>TaxiBaza WPML — matrix</h1><script type="application/json" id="tbs-matrix">' . wp_json_encode( self::matrix(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script><p>Rows: ' . count( self::matrix() ) . '</p></div>';
			return;
		}
		$plan = self::plan();
		echo '<div class="wrap"><h1>TaxiBaza: WPML — options strings (taxi-rental-options)</h1>';
		if ( isset( $_POST['tbs_apply'] ) && check_admin_referer( self::NONCE ) ) {
			$done = self::apply( $plan );
			echo '<div class="notice notice-success"><p>' . esc_html( sprintf( 'Done: %d strings repaired.', $done ) ) . '</p></div>';
			$plan = self::plan();
		}
		$s = $plan['stats'];
		echo '<p id="tbs-stats" data-stats="' . esc_attr( (string) wp_json_encode( $s ) ) . '">' . esc_html( sprintf( 'Strings: %1$d · wrong source language: %2$d · source text to set to the Slovak value: %3$d · translations to fill for the old source language: %4$d · Slovak "translations" differing from the options value: %5$d · without a Slovak options value (skipped): %6$d', $s['total'], $s['wrong_lang'], $s['value_fix'], $s['tr_fix'], $s['sk_tr_differs'], $s['no_sk'] ) ) . '</p>';
		if ( $plan['rows'] ) {
			echo '<form method="post">';
			wp_nonce_field( self::NONCE );
			echo '<p><button class="button button-primary" name="tbs_apply" value="1" onclick="return confirm(\'Apply? Make sure a database backup exists.\')">Apply repair</button></p></form>';
			echo '<table class="widefat striped" id="tbs-plan"><thead><tr><th>ID</th><th>Name</th><th>Source lang</th><th>Current source text</th><th>Slovak value (new source)</th><th>Translation fix</th><th>Existing SK "translation" (kept, not used)</th></tr></thead><tbody>';
			foreach ( $plan['rows'] as $r ) {
				$fix = '';
				foreach ( $r['tr'] as $l => $t ) {
					$fix .= esc_html( $l . ': ' . (string) $t['from'] . ' → ' . $t['to'] );
				}
				printf(
					'<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
					(int) $r['id'],
					esc_html( $r['name'] ),
					esc_html( $r['from'] . ( $r['set_lang'] ? ' → sk' : '' ) ),
					esc_html( mb_substr( (string) $r['old'], 0, 120 ) ),
					esc_html( mb_substr( $r['sk'], 0, 120 ) ),
					$fix, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above
					esc_html( mb_substr( (string) ( $r['sk_tr'] ?? '' ), 0, 120 ) )
				);
			}
			echo '</tbody></table>';
		} else {
			echo '<p>Nothing to repair.</p>';
		}
		echo '</div>';
	}
}
