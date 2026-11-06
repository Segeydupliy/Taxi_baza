<?php
/**
 * One moderation queue: fleets awaiting moderation (new profiles and edits) are
 * shown in the panel dashboard's "Awaiting moderation" list next to listings,
 * in the same card style, with approve / reject / view actions.
 *
 * The theme's dashboard has no hook, so the cards are inserted into its output
 * (output buffer on the panel's dashboard tab only). If the markup ever changes
 * and the anchor is not found, the page is returned untouched.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Dashboard_Queue {

	private const CARDS = '<div class="tb-fa-cards" role="list">';
	private const EMPTY = '<div class="tb-fa-empty">';

	public static function init(): void {
		add_action( 'template_redirect', [ self::class, 'maybe_buffer' ], 20 );
		add_action( 'wp_enqueue_scripts', [ self::class, 'assets' ], 20 );
	}

	private static function is_dashboard(): bool {
		if ( ! is_page_template( 'page-front-admin.php' ) || ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return 'dashboard' === $tab || '' === $tab;
	}

	public static function assets(): void {
		if ( self::is_dashboard() ) {
			$base = plugins_url( 'assets/', dirname( __DIR__ ) . '/taxibaza-fleets.php' );
			wp_enqueue_style( 'tbf-panel', $base . 'panel.css', [], VERSION );
		}
	}

	public static function maybe_buffer(): void {
		if ( self::is_dashboard() ) {
			ob_start( [ self::class, 'inject' ] );
		}
	}

	/** Fleets that need a decision: new profiles first, then edits. */
	public static function queue(): array {
		$g = Admin_Panel::grouped();
		return array_values( array_unique( $g['pending'] ) );
	}

	/**
	 * @param string $html Full page.
	 */
	public static function inject( $html ): string {
		$html  = (string) $html;
		$ids   = self::queue();
		$done  = isset( $_GET['tbf_done'] ) ? sanitize_key( wp_unslash( $_GET['tbf_done'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $ids && ! $done ) {
			return $html;
		}
		$notice = $done ? '<p class="tbf-notice' . ( 'error' === $done ? ' tbf-notice--error' : '' ) . '" role="status" style="margin:0 0 16px">' . esc_html( t( 'error' === $done ? 'fa_done_error' : 'fa_done_' . $done ) ) . '</p>' : '';
		$cards  = implode( '', array_map( [ self::class, 'card' ], $ids ) );

		$pos = strpos( $html, self::CARDS );
		if ( false !== $pos ) {
			$at = $pos + strlen( self::CARDS );
			return substr( $html, 0, $pos ) . $notice . substr( $html, $pos, strlen( self::CARDS ) ) . $cards . substr( $html, $at );
		}
		$pos = strpos( $html, self::EMPTY );
		if ( false !== $pos ) {
			$end = strpos( $html, '</div>', $pos );
			if ( false === $end ) {
				return $html;
			}
			$block = $ids ? self::CARDS . $cards . '</div>' : substr( $html, $pos, $end + 6 - $pos );
			return substr( $html, 0, $pos ) . $notice . $block . substr( $html, $end + 6 );
		}
		return $html;
	}

	public static function card( int $fleet ): string {
		$p     = Profile::get_draft( $fleet ) ?: Profile::get_live( $fleet );
		$user  = get_userdata( $fleet );
		$name  = ! empty( $p['name'] ) ? (string) $p['name'] : ( $user ? (string) $user->display_name : '#' . $fleet );
		$logo  = ! empty( $p['logo_id'] ) ? wp_get_attachment_image_url( (int) $p['logo_id'], 'thumbnail' ) : '';
		$edits = Status::APPROVED === Status::get( $fleet ) && Profile::has_pending_edits( $fleet );
		$badge = t( $edits ? 'fa_badge_edits' : 'fa_badge_pending' );
		$view  = Admin_Panel::panel_url( [ 'fleet' => $fleet ] );
		$back  = remove_query_arg( 'tbf_done', ( is_ssl() ? 'https://' : 'http://' ) . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/' ) );
		$init  = strtoupper( implode( '', array_map( static fn( $w ) => mb_substr( $w, 0, 1 ), array_slice( preg_split( '/\s+/u', trim( $name ) ) ?: [], 0, 2 ) ) ) );

		$meta = [];
		foreach ( [ $user ? $user->user_email : '', $p['phone'] ?? '' ] as $m ) {
			if ( '' !== (string) $m ) {
				$meta[] = '<span class="tb-fa-mod-card__meta-item">' . esc_html( (string) $m ) . '</span>';
			}
		}
		$acts = [];
		foreach ( Admin_Panel::actions_for( $fleet ) as $act ) {
			if ( 'block' === $act ) {
				continue; // Blocking lives in the Fleets tab; the queue is for approve / reject.
			}
			$cls    = 'approve' === $act ? 'tb-fa-btn tb-fa-btn--accent' : 'tb-fa-btn tb-fa-btn--dark';
			$acts[] = Admin_Panel::button( $fleet, $act, $back, $cls );
		}
		array_splice( $acts, max( 0, count( $acts ) - 1 ), 0, '<a href="' . esc_url( $view ) . '" class="tb-fa-btn tb-fa-btn--outline">' . esc_html( t( 'fa_view' ) ) . '</a>' );

		return '<article class="tb-fa-mod-card tbf-mod-card" data-fleet-id="' . (int) $fleet . '" role="listitem">'
			. '<div class="tb-fa-mod-card__body">'
			. '<div class="tb-fa-mod-card__thumb tbf-mod-card__thumb">' . ( $logo ? '<img src="' . esc_url( $logo ) . '" alt="" loading="lazy" width="140" height="100">' : '<span>' . esc_html( $init ) . '</span>' ) . '</div>'
			. '<div class="tb-fa-mod-card__info"><div class="tb-fa-mod-card__head"><span class="tb-fa-mod-card__title">' . esc_html( $name ) . '</span>'
			. '<span class="tb-fa-mod-card__badge">' . esc_html( t( 'fa_queue_type' ) . ' · ' . $badge ) . '</span></div>'
			. '<div class="tb-fa-mod-card__meta">' . implode( '', $meta ) . '</div></div>'
			. '<span class="tb-fa-mod-card__id">ID: ' . (int) $fleet . '</span>'
			. '</div>'
			. '<div class="tb-fa-mod-card__bar"><div class="tb-fa-mod-card__actions">' . implode( '', $acts ) . '</div></div>'
			. '</article>';
	}
}
