<?php
/**
 * Front admin panel (/panel/) — "Флотилії" tab: list, detail, moderation actions.
 * Figma: Admin Flotillias (952:1148), Admin Flotilla Info (952:1955).
 *
 * The theme's panel has a hard-coded tab list, so for ?tab=fleets we swap the
 * template, and on other panel tabs we add the nav link with a tiny script.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin_Panel {

	public const NONCE   = 'tbf_moderate';
	public const ACTIONS = [ 'approve', 'reject', 'block', 'unblock', 'reject_edits' ];

	public static function init(): void {
		add_filter( 'template_include', [ self::class, 'swap_template' ], 99 );
		add_action( 'admin_post_tbf_moderate', [ self::class, 'handle_action' ] );
		add_action( 'wp_footer', [ self::class, 'nav_link_script' ], 99 );
		add_action( 'wp_enqueue_scripts', [ self::class, 'assets' ] );
	}

	private static function on_panel(): bool {
		return is_page_template( 'page-front-admin.php' ) && current_user_can( 'manage_options' );
	}

	private static function is_fleets_tab(): bool {
		return self::on_panel() && isset( $_GET['tab'] ) && 'fleets' === sanitize_key( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	public static function panel_url( array $args = [] ): string {
		$base = function_exists( 'tb_url' ) ? tb_url( '/panel/' ) : home_url( '/panel/' );
		return add_query_arg( array_merge( [ 'tab' => 'fleets' ], $args ), $base );
	}

	/**
	 * @param string $template Template path.
	 */
	public static function swap_template( $template ) {
		return self::is_fleets_tab() ? dirname( __DIR__ ) . '/templates/panel-fleets.php' : $template;
	}

	public static function assets(): void {
		if ( self::is_fleets_tab() ) {
			$base = plugins_url( 'assets/', dirname( __DIR__ ) . '/taxibaza-fleets.php' );
			wp_enqueue_style( 'tbf-cabinet', $base . 'cabinet.css', [], VERSION );
			wp_enqueue_style( 'tbf-panel', $base . 'panel.css', [ 'tbf-cabinet' ], VERSION );
			if ( ! empty( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which assets to load.
				Cabinet::enqueue_form_script();
			}
		}
	}

	/**
	 * Fleet IDs grouped by status (incl. pseudo-status "edits").
	 *
	 * @return array<string,int[]>
	 */
	public static function grouped(): array {
		$ids    = array_map( 'intval', get_users( [ 'role' => 'fleet', 'fields' => 'ID', 'orderby' => 'registered', 'order' => 'DESC' ] ) );
		$groups = [ 'all' => $ids, 'pending' => [], 'approved' => [], 'rejected' => [], 'blocked' => [], 'draft' => [] ];
		foreach ( $ids as $id ) {
			$st = Status::get( $id ) ?: Status::DRAFT;
			$groups[ $st ][] = $id;
			if ( Status::APPROVED === $st && Profile::has_pending_edits( $id ) ) {
				$groups['pending'][] = $id; // Approved fleets with new edits also need attention.
			}
		}
		return $groups;
	}

	public static function pending_count(): int {
		return count( self::grouped()['pending'] );
	}

	/** Nav link on the theme's own panel tabs. */
	public static function nav_link_script(): void {
		if ( ! self::on_panel() || self::is_fleets_tab() ) {
			return;
		}
		$count = self::pending_count();
		$label = t( 'fa_nav' ) . ( $count ? ' (' . $count . ')' : '' );
		printf(
			"<script>(function(){var u=%s,l=%s;function add(sel,cls){document.querySelectorAll(sel).forEach(function(n){if(n.querySelector('[data-tbf-nav]'))return;var a=document.createElement('a');a.href=u;a.className=cls;a.setAttribute('data-tbf-nav','1');a.textContent=l;var users=n.querySelector('a[href*=\"tab=users\"]');users&&users.nextSibling?n.insertBefore(a,users.nextSibling):n.insertBefore(a,n.querySelector('hr'));});}add('.tb-fa-nav__inner','tb-fa-nav__tab');add('.tb-fa-mobile-nav__drawer','tb-fa-mobile-nav__link');})();</script>\n",
			wp_json_encode( self::panel_url() ),
			wp_json_encode( $label )
		);
	}

	/** POST handler: approve / reject / block / unblock / reject_edits. */
	public static function handle_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( t( 'fa_done_error' ) ), 403 );
		}
		$fleet = isset( $_POST['fleet'] ) ? absint( $_POST['fleet'] ) : 0;
		$act   = isset( $_POST['tbf_act'] ) ? sanitize_key( wp_unslash( $_POST['tbf_act'] ) ) : '';
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		$back  = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : self::panel_url();

		$ok = $fleet && in_array( $act, self::ACTIONS, true ) && wp_verify_nonce( $nonce, self::NONCE . '_' . $fleet ) && Status::is_fleet( $fleet );
		if ( $ok ) {
			$ok = self::apply( $fleet, $act );
		}
		wp_safe_redirect( add_query_arg( 'tbf_done', $ok ? $act : 'error', $back ) );
		exit;
	}

	public static function apply( int $fleet, string $act ): bool {
		switch ( $act ) {
			case 'approve':
				return Profile::approve( $fleet );
			case 'reject':
				return Status::set( $fleet, Status::REJECTED );
			case 'block':
				return Status::set( $fleet, Status::BLOCKED );
			case 'unblock':
				// Back to approved if the fleet had an approved profile, else to moderation.
				return Status::set( $fleet, Profile::get_live( $fleet ) ? Status::APPROVED : Status::PENDING );
			case 'reject_edits':
				Profile::reject_edits( $fleet );
				do_action( 'tbf_edits_rejected', $fleet );
				return true;
		}
		return false;
	}

	/**
	 * Which buttons to show for a fleet.
	 *
	 * @return string[]
	 */
	public static function actions_for( int $fleet ): array {
		$st = Status::get( $fleet ) ?: Status::DRAFT;
		switch ( $st ) {
			case Status::PENDING:
				return [ 'reject', 'approve' ];
			case Status::APPROVED:
				return Profile::has_pending_edits( $fleet ) ? [ 'reject_edits', 'approve', 'block' ] : [ 'block' ];
			case Status::REJECTED:
				return [ 'approve', 'block' ];
			case Status::BLOCKED:
				return [ 'unblock' ];
			default:
				return [ 'block' ];
		}
	}

	/** Render one action button as a small POST form. */
	public static function button( int $fleet, string $act, string $back, string $class ): string {
		$labels  = [ 'approve' => Profile::has_pending_edits( $fleet ) ? 'fa_approve_edits' : 'fa_approve', 'reject' => 'fa_reject', 'block' => 'fa_block', 'unblock' => 'fa_unblock', 'reject_edits' => 'fa_reject_edits' ];
		$confirm = [ 'block' => 'fa_confirm_block', 'reject' => 'fa_confirm_reject' ];
		$onsub   = isset( $confirm[ $act ] ) ? ' onsubmit="return confirm(' . esc_attr( wp_json_encode( t( $confirm[ $act ] ) ) ) . ')"' : '';
		return sprintf(
			'<form method="post" action="%1$s" class="tbf-fa-act"%7$s><input type="hidden" name="action" value="tbf_moderate"><input type="hidden" name="fleet" value="%2$d"><input type="hidden" name="tbf_act" value="%3$s"><input type="hidden" name="back" value="%4$s">%5$s<button type="submit" class="%6$s">%8$s</button></form>',
			esc_url( admin_url( 'admin-post.php' ) ),
			$fleet,
			esc_attr( $act ),
			esc_attr( $back ),
			wp_nonce_field( self::NONCE . '_' . $fleet, '_wpnonce', false, false ),
			esc_attr( $class ),
			$onsub,
			esc_html( t( $labels[ $act ] ) )
		);
	}
}
