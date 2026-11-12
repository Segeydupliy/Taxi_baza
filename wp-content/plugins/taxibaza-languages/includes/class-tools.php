<?php
/**
 * Tools → TaxiBaza Languages: DeepL settings, translating listings that lack
 * translations, and cleaning up broken WPML translation links.
 *
 * @package TaxiBaza\Languages
 */

namespace TaxiBaza\Languages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Tools {

	public const PAGE = 'tbl-languages';

	public static function init(): void {
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'admin_menu', [ self::class, 'menu' ] );
		foreach ( [ 'save', 'test', 'backlog', 'cleanup' ] as $a ) {
			add_action( 'admin_post_tbl_' . $a, [ self::class, 'handle_' . $a ] );
		}
	}

	public static function menu(): void {
		add_management_page( 'TaxiBaza Languages', 'TaxiBaza Languages', 'manage_options', self::PAGE, [ self::class, 'render' ] );
	}

	private static function url( array $args = [] ): string {
		return add_query_arg( array_merge( [ 'page' => self::PAGE ], $args ), admin_url( 'tools.php' ) );
	}

	private static function guard( string $action ): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'tbl_' . $action ) ) {
			wp_die( esc_html__( 'Not allowed.', 'default' ) );
		}
	}

	private static function done( string $text, bool $ok = true ): void {
		set_transient( 'tbl_notice_' . get_current_user_id(), [ 'ok' => $ok, 'text' => $text ], 300 );
		wp_safe_redirect( self::url( [ 'done' => 1 ] ) );
		exit;
	}

	/** @param string $action Action name (tbl_ prefix is added). */
	private static function form( string $action, string $button, string $class = 'secondary', string $extra = '' ): void {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin:0 8px 8px 0">';
		echo '<input type="hidden" name="action" value="tbl_' . esc_attr( $action ) . '">';
		wp_nonce_field( 'tbl_' . $action );
		echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts by the caller.
		submit_button( $button, $class, 'submit', false );
		echo '</form>';
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s      = DeepL::settings();
		$notice = get_transient( 'tbl_notice_' . get_current_user_id() );
		if ( is_array( $notice ) && isset( $_GET['done'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			delete_transient( 'tbl_notice_' . get_current_user_id() );
		} else {
			$notice = null;
		}
		$backlog = Auto_Translate::backlog();
		$broken  = Groups::broken();
		$last    = get_option( DeepL::LAST_ERROR );
		?>
		<div class="wrap">
			<h1>TaxiBaza Languages</h1>
			<?php if ( $notice ) : ?>
				<div class="notice <?php echo $notice['ok'] ? 'notice-success' : 'notice-error'; ?>"><p><?php echo esc_html( (string) $notice['text'] ); ?></p></div>
			<?php endif; ?>

			<h2>1. Automatický preklad inzerátov (DeepL)</h2>
			<p>Keď moderátor zverejní inzerát, popis (short_description, poistenie, servis) sa preloží do ostatných jazykov stránky. Ostatné polia (mesto, cena, platformy, rok, prevodovka, palivo, záloha, fotky, kontakty) sa skopírujú. Preklady urobené ľuďmi sa neprepisujú.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="tbl_save">
				<?php wp_nonce_field( 'tbl_save' ); ?>
				<table class="form-table" role="presentation">
					<tr><th scope="row">Zapnuté</th><td><label><input type="checkbox" name="on" value="1"<?php checked( $s['on'] ); ?>> Prekladať inzeráty pri zverejnení</label></td></tr>
					<tr><th scope="row"><label for="tbl-key">DeepL API key</label></th><td><input class="regular-text" type="password" id="tbl-key" name="key" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( '' !== $s['key'] ? 'uložený — nechajte prázdne, ak nemeníte' : 'xxxxxxxx-xxxx-…:fx' ); ?>"><p class="description">deepl.com → Account → API Keys. Free kľúč končí na „:fx“ (500 000 znakov mesačne zadarmo).</p></td></tr>
				</table>
				<p>Stav: <strong><?php echo DeepL::enabled() ? 'zapnuté' : 'vypnuté'; ?></strong></p>
				<?php submit_button(); ?>
			</form>
			<?php self::form( 'test', 'Skontrolovať pripojenie k DeepL' ); ?>
			<?php if ( is_array( $last ) && ! empty( $last['at'] ) ) : ?>
				<p><strong>Posledná chyba DeepL:</strong> <?php echo esc_html( wp_date( 'd.m.Y H:i', (int) $last['at'] ) . ' — ' . (string) $last['message'] ); ?></p>
			<?php endif; ?>

			<h2>2. Zverejnené inzeráty bez prekladu: <?php echo (int) count( $backlog ); ?></h2>
			<?php if ( $backlog ) : ?>
				<p>ID: <?php echo esc_html( implode( ', ', array_slice( $backlog, 0, 40 ) ) . ( count( $backlog ) > 40 ? ' …' : '' ) ); ?></p>
				<?php
				if ( DeepL::enabled() ) {
					self::form( 'backlog', 'Preložiť teraz (do 10 inzerátov)', 'primary' );
					echo '<p class="description">Zvyšok sa preloží automaticky každú hodinu.</p>';
				} else {
					echo '<p class="description">Najprv zapnite DeepL vyššie.</p>';
				}
				?>
			<?php endif; ?>

			<h2>3. Pokazené prepojenia prekladov (WPML): <?php echo (int) count( $broken ); ?></h2>
			<p>Záznamy WPML, ktorých preklad už neexistuje alebo je v koši. Kvôli nim sa inzerát nezobrazoval v jazyku, kde „prekladom“ bol vymazaný príspevok.</p>
			<?php if ( $broken ) : ?>
				<table class="widefat striped" style="max-width:720px">
					<thead><tr><th>ID prekladu</th><th>Skupina (trid)</th><th>Jazyk</th><th>Stav</th><th>Originál</th></tr></thead>
					<tbody>
					<?php
					$groups = Groups::by_trid();
					foreach ( $broken as $r ) {
						$o = Groups::original_of( $groups[ $r['trid'] ] ?? [] );
						echo '<tr><td>' . (int) $r['id'] . '</td><td>' . (int) $r['trid'] . '</td><td>' . esc_html( strtoupper( (string) $r['lang'] ) ) . '</td><td>' . esc_html( $r['exists'] ? 'v koši' : 'vymazaný' ) . '</td><td>' . ( $o ? (int) $o['id'] . ' ' . esc_html( get_the_title( (int) $o['id'] ) ) : '—' ) . '</td></tr>';
					}
					?>
					</tbody>
				</table>
				<p>Vyčistiť = odstrániť tieto záznamy z tabuliek WPML; preklady v koši sa vymažú natrvalo. Originály a ostatné preklady sa nemenia.</p>
				<?php self::form( 'cleanup', 'Vyčistiť pokazené prepojenia', 'primary' ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function handle_save(): void {
		self::guard( 'save' );
		$old = DeepL::settings();
		$key = trim( (string) wp_unslash( $_POST['key'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated by the pattern below.
		$key = '' !== $key ? (string) preg_replace( '/[^A-Za-z0-9:\-]/', '', $key ) : $old['key'];
		update_option( DeepL::OPTION, [ 'on' => ! empty( $_POST['on'] ), 'key' => $key ], false );
		Auto_Translate::schedule();
		self::done( 'Uložené.' );
	}

	public static function handle_test(): void {
		self::guard( 'test' );
		$r = DeepL::usage();
		self::done( $r['text'], $r['ok'] );
	}

	public static function handle_backlog(): void {
		self::guard( 'backlog' );
		$r = Auto_Translate::run_backlog( 10 );
		self::done( sprintf( 'Preložené: %d, s chybou: %d, zostáva: %d.', $r['done'], $r['failed'], $r['left'] ), 0 === $r['failed'] );
	}

	public static function handle_cleanup(): void {
		self::guard( 'cleanup' );
		$r = self::cleanup();
		self::done( sprintf( 'Odstránené prepojenia: %d, natrvalo vymazané preklady z koša: %d.', $r['rows'], $r['posts'] ) );
	}

	/** @return array{rows:int,posts:int} */
	public static function cleanup(): array {
		global $wpdb;
		$rows  = 0;
		$posts = 0;
		foreach ( Groups::broken() as $r ) {
			if ( $r['exists'] ) {
				// In the trash: deleting the post also removes its WPML row.
				if ( wp_delete_post( (int) $r['id'], true ) ) {
					++$posts;
				}
				continue;
			}
			$tid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_type = %s AND element_id = %d", Groups::TYPE, (int) $r['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( ! $tid ) {
				continue;
			}
			$wpdb->delete( $wpdb->prefix . 'icl_translation_status', [ 'translation_id' => $tid ], [ '%d' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$rows += (int) $wpdb->delete( $wpdb->prefix . 'icl_translations', [ 'translation_id' => $tid ], [ '%d' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
		Groups::flush();
		do_action( 'wpml_cache_clear' );
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( 'post' );
		}
		return [ 'rows' => $rows, 'posts' => $posts ];
	}
}
