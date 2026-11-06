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
	 * Explicit content fixes: texts that are visibly in the wrong language now
	 * (English/Ukrainian on the Slovak site, Slovak on the English one). Checked
	 * on 6.11.2026 against what every language version shows.
	 */
	public const FIXES = [
		'tb_cat_filter_city'       => [ 'sk' => 'Mesto', 'uk' => 'Місто', 'en' => 'City', 'ru' => 'Город' ],
		'tb_cat_filter_platform'   => [ 'sk' => 'Platforma', 'uk' => 'Платформа', 'en' => 'Platform', 'ru' => 'Платформа' ],
		'tb_cab_search_label'      => [ 'sk' => 'Hľadať inzeráty', 'uk' => 'Пошук оголошень', 'en' => 'Search listings', 'ru' => 'Искать объявления' ],
		'tb_cab_profile_city'      => [ 'sk' => 'Mesto', 'uk' => 'Місто', 'en' => 'City', 'ru' => 'Город' ],
		'tb_cat_fuel_diesel'       => [ 'sk' => 'Diesel', 'en' => 'Diesel' ],
		'tb_cat_trans_automatic'   => [ 'en' => 'Automatic' ],
		'tb_cat_filter_sort_label' => [ 'en' => 'Sort by' ],
		'tb_fa_cat_delete_btn'     => [ 'en' => 'Delete category' ],
		// Slovak texts visitors saw before 6.11.2026 (restored from the backup: WPML
		// showed the Slovak gettext translation of the old Ukrainian source).
		'tb_register_label' => [ 'sk' => 'Zaregistrovať sa' ],
		'tb_cat_card_more' => [ 'sk' => 'Viac informácií' ],
		'tb_cta_title' => [ 'sk' => "Pridaj sa k 1 200+\nvodičom a majiteľom" ],
		'tb_cta_desc' => [ 'sk' => 'Nové inzeráty, tipy a priama komunikácia medzi majiteľmi a vodičmi' ],
		'tb_blog_title' => [ 'sk' => 'Užitočné materiály pre vodičov a majiteľov áut' ],
		'tb_cab_profile_error' => [ 'sk' => 'Skontrolujte meno a e-mail, polia sú povinné.' ],
		'tb_cab_profile_email_taken' => [ 'sk' => 'Tento e-mail už používa iný účet.' ],
		'tb_cab_profile_photo_title' => [ 'sk' => 'Profilová fotka' ],
		'tb_cab_profile_upload_btn' => [ 'sk' => 'Nahrať nové' ],
		'tb_cab_profile_about' => [ 'sk' => 'Stručný popis' ],
		'tb_cat_city_count' => [ 'sk' => 'K dispozícii je %d áut' ],
		'tb_legal_updated_prefix'  => [ 'sk' => 'Aktualizované: %s', 'uk' => 'Оновлено: %s', 'en' => 'Updated: %s', 'ru' => 'Обновлено: %s' ],
	];

	/** A translation WPML actually serves (complete). */
	private static function used( ?array $t ): bool {
		return is_array( $t ) && '' !== (string) $t[0] && 10 === (int) $t[1];
	}

	/**
	 * What a visitor sees for a string on language $l (the theme passes the ACF
	 * value of that language; WPML returns its translation if the language is not
	 * the string's source and a complete translation exists).
	 *
	 * @param array<string,mixed> $r Matrix row.
	 */
	public static function visible( array $r, string $l ): ?string {
		$pass = $r['acf'][ $l ] ?? null;
		if ( $r['src'] === $l ) {
			return $pass;
		}
		$t = $r['tr'][ $l ] ?? null;
		return self::used( $t ) ? (string) $t[0] : $pass;
	}

	/**
	 * Build the plan: Slovak becomes the source everywhere, every language keeps
	 * showing what it shows now, except the explicit FIXES.
	 *
	 * @return array{rows:array<int,array<string,mixed>>,stats:array<string,int>}
	 */
	public static function plan(): array {
		$rows  = [];
		$stats = [ 'total' => 0, 'wrong_lang' => 0, 'value_fix' => 0, 'tr_fix' => 0, 'acf_sk_fix' => 0, 'content_fix' => 0, 'skipped' => 0 ];
		foreach ( self::matrix() as $r ) {
			$stats['total']++;
			$r['tr'] = (array) $r['tr'];
			$fix     = self::FIXES[ $r['n'] ] ?? [];
			$sk      = $fix['sk'] ?? self::visible( $r, 'sk' );
			// Field never filled in the admin: the theme shows its built-in Slovak
			// fallback, which is what the Slovak source string already holds.
			$no_acf = ( null === $sk || '' === $sk ) && 'sk' === $r['src'] && '' !== (string) $r['v']
				&& ! preg_match( '/\p{Cyrillic}/u', (string) $r['v'] ); // Leave old non-Slovak leftovers alone.
			if ( $no_acf ) {
				$sk = (string) $r['v'];
			}
			if ( null === $sk || '' === $sk ) {
				$stats['skipped']++;
				continue;
			}
			$row = [ 'id' => $r['id'], 'name' => $r['n'], 'from' => $r['src'], 'old' => $r['v'], 'sk' => $sk, 'set_lang' => 'sk' !== $r['src'], 'set_value' => $r['v'] !== $sk, 'acf_sk' => ! $no_acf && ( $r['acf']['sk'] ?? null ) !== $sk ? $sk : null, 'tr' => [], 'fix' => (bool) $fix ];
			// WPML serves an existing Slovak "translation" row even when the source is
			// Slovak, so it must say the same as the source.
			$cur_sk = $r['tr']['sk'] ?? null;
			if ( is_array( $cur_sk ) && '' !== (string) $cur_sk[0] && $cur_sk[0] !== $sk ) {
				$row['tr']['sk'] = [ 'from' => $cur_sk[0], 'to' => $sk ];
			}
			foreach ( self::LANGS as $l ) {
				$cur  = $r['tr'][ $l ] ?? null;
				$want = $fix[ $l ] ?? null;
				if ( null === $want && $r['src'] === $l ) {
					$want = self::visible( $r, $l ); // Keep what the old source language showed.
				}
				// A ready translation left "in progress" (status 2) is not shown, so
				// visitors see the Slovak text instead: mark it complete.
				if ( null === $want && is_array( $cur ) && 2 === (int) $cur[1] && '' !== (string) $cur[0] && $cur[0] !== $sk
					&& ( null === ( $r['acf'][ $l ] ?? null ) || '' === $r['acf'][ $l ] ) ) {
					$want = (string) $cur[0];
				}
				if ( null !== $want && '' !== $want && ! ( self::used( $cur ) && $cur[0] === $want ) ) {
					$row['tr'][ $l ] = [ 'from' => $cur[0] ?? null, 'to' => $want ];
				}
			}
			$stats['wrong_lang']  += $row['set_lang'] ? 1 : 0;
			$stats['value_fix']   += $row['set_value'] ? 1 : 0;
			$stats['tr_fix']      += count( $row['tr'] );
			$stats['acf_sk_fix']  += null !== $row['acf_sk'] ? 1 : 0;
			$stats['content_fix'] += $fix ? 1 : 0;
			if ( $row['set_lang'] || $row['set_value'] || $row['tr'] || null !== $row['acf_sk'] ) {
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
			if ( null !== $r['acf_sk'] ) {
				update_option( 'options_' . $r['name'], $r['acf_sk'], false );
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
			foreach ( $r['tr'] as $lang => $t ) {
				if ( function_exists( 'icl_add_string_translation' ) ) {
					icl_add_string_translation( (int) $r['id'], $lang, $t['to'], self::STATUS );
				}
				// WPML skips the call when the text is unchanged; make sure the row is complete.
				$wpdb->update( "{$wpdb->prefix}icl_string_translations", [ 'value' => $t['to'], 'status' => self::STATUS ], [ 'string_id' => (int) $r['id'], 'language' => $lang ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
			$n++;
		}
		wp_cache_flush();
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
		echo '<p id="tbs-stats" data-stats="' . esc_attr( (string) wp_json_encode( $s ) ) . '">' . esc_html( sprintf( 'Strings: %1$d · source language to Slovak: %2$d · source text set to the Slovak text: %3$d · translations written: %4$d · Slovak options value corrected: %5$d · content fixes: %6$d · skipped (no Slovak text): %7$d', $s['total'], $s['wrong_lang'], $s['value_fix'], $s['tr_fix'], $s['acf_sk_fix'], $s['content_fix'], $s['skipped'] ) ) . '</p>';
		if ( $plan['rows'] ) {
			echo '<form method="post">';
			wp_nonce_field( self::NONCE );
			echo '<p><button class="button button-primary" name="tbs_apply" value="1" onclick="return confirm(\'Apply? Make sure a database backup exists.\')">Apply repair</button></p></form>';
			echo '<table class="widefat striped" id="tbs-plan"><thead><tr><th>ID</th><th>Name</th><th>Source lang</th><th>Current source text</th><th>New Slovak source</th><th>Translations written</th><th>Slovak options value</th></tr></thead><tbody>';
			foreach ( $plan['rows'] as $r ) {
				$fix = '';
				foreach ( $r['tr'] as $l => $t ) {
					$fix .= esc_html( $l . ': ' . (string) $t['from'] . ' → ' . $t['to'] ) . '<br>';
				}
				printf(
					'<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
					(int) $r['id'],
					esc_html( $r['name'] ),
					esc_html( $r['from'] . ( $r['set_lang'] ? ' → sk' : '' ) ),
					esc_html( mb_substr( (string) $r['old'], 0, 120 ) ),
					esc_html( mb_substr( $r['sk'], 0, 120 ) ),
					$fix, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above
					esc_html( null !== $r['acf_sk'] ? '→ ' . mb_substr( (string) $r['acf_sk'], 0, 120 ) : '' )
				);
			}
			echo '</tbody></table>';
		} else {
			echo '<p>Nothing to repair.</p>';
		}
		echo '</div>';
	}
}
