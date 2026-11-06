<?php
/**
 * In-site notifications for the header bell (Figma: Notification 1001:971, 1001:1076).
 *
 * Stored per user in meta `tbf_notifications` (newest first, max 30). Texts are
 * resolved at display time, so they follow the visitor's current language.
 * The theme's bell is a plain link; we attach a dropdown to it with a script.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Notifications {

	public const META  = 'tbf_notifications';
	public const NONCE = 'tbf_notifications';
	public const MAX   = 30;

	public static function init(): void {
		add_action( 'tbf_profile_submitted', [ self::class, 'on_submitted' ], 10, 2 );
		add_action( 'tbf_status_changed', [ self::class, 'on_status' ], 20, 3 );
		add_action( 'tbf_edits_rejected', [ self::class, 'on_edits_rejected' ] );
		add_action( 'wp_ajax_tbf_notifications_read', [ self::class, 'ajax_read' ] );
		add_action( 'wp_footer', [ self::class, 'render_bell' ], 60 );
		// Rental request from a listing page ("Kontaktovať vlastníka", core action).
		add_action( 'wp_ajax_taxi_rental_owner_message', [ self::class, 'watch_request' ], 1 );
		add_action( 'wp_ajax_nopriv_taxi_rental_owner_message', [ self::class, 'watch_request' ], 1 );
	}

	/**
	 * @param array<string,mixed> $args Extra data (fleet id, name).
	 */
	public static function add( int $user_id, string $type, array $args = [] ): void {
		$list = self::get( $user_id );
		array_unshift(
			$list,
			[
				'id'   => wp_generate_uuid4(),
				'type' => $type,
				'args' => $args,
				'time' => time(),
				'read' => false,
			]
		);
		update_user_meta( $user_id, self::META, array_slice( $list, 0, self::MAX ) );
	}

	/** @return array<int,array<string,mixed>> */
	public static function get( int $user_id ): array {
		$v = get_user_meta( $user_id, self::META, true );
		return is_array( $v ) ? array_values( $v ) : [];
	}

	public static function unread( int $user_id ): int {
		return count( array_filter( self::get( $user_id ), static fn( $n ) => empty( $n['read'] ) ) );
	}

	/** @return int[] */
	private static function admins(): array {
		return array_map( 'intval', get_users( [ 'role' => 'administrator', 'fields' => 'ID' ] ) );
	}

	private static function fleet_name( int $fleet ): string {
		$p = Profile::get_draft( $fleet ) ?: Profile::get_live( $fleet );
		if ( ! empty( $p['name'] ) ) {
			return (string) $p['name'];
		}
		$u = get_userdata( $fleet );
		return $u ? (string) $u->display_name : '#' . $fleet;
	}

	/**
	 * @param int    $fleet Fleet user.
	 * @param string $kind  'new' | 'edits'.
	 */
	public static function on_submitted( $fleet, $kind ): void {
		$type = 'edits' === $kind ? 'fleet_edits' : 'fleet_new';
		foreach ( self::admins() as $admin ) {
			self::add(
				$admin,
				$type,
				[
					'fleet' => (int) $fleet,
					'name'  => self::fleet_name( (int) $fleet ),
				]
			);
		}
	}

	/**
	 * @param int    $fleet Fleet user.
	 * @param string $to    New status.
	 * @param string $from  Old status.
	 */
	public static function on_status( $fleet, $to, $from ): void {
		$fleet = (int) $fleet;
		if ( Status::APPROVED === $to && Status::APPROVED === $from ) {
			self::add( $fleet, 'edits_ok' );
		} elseif ( Status::APPROVED === $to ) {
			self::add( $fleet, 'approved' );
		} elseif ( Status::REJECTED === $to ) {
			self::add( $fleet, 'rejected' );
		} elseif ( Status::BLOCKED === $to ) {
			self::add( $fleet, 'blocked' );
		}
	}

	/**
	 * @param int $fleet Fleet user.
	 */
	public static function on_edits_rejected( $fleet ): void {
		self::add( (int) $fleet, 'edits_no' );
	}

	/**
	 * Texts + link for one notification, in the current language.
	 *
	 * @param array<string,mixed> $n Notification.
	 * @return array{t:string,d:string,url:string}
	 */
	public static function present( array $n ): array {
		$args  = (array) ( $n['args'] ?? [] );
		$name  = (string) ( $args['name'] ?? '' );
		$fleet = (int) ( $args['fleet'] ?? 0 );
		switch ( $n['type'] ?? '' ) {
			case 'fleet_new':
				return [ 't' => t( 'n_fleet_new_t' ), 'd' => sprintf( t( 'n_fleet_new_d' ), $name ), 'url' => Admin_Panel::panel_url( [ 'fleet' => $fleet ] ) ];
			case 'fleet_edits':
				return [ 't' => t( 'n_fleet_edits_t' ), 'd' => sprintf( t( 'n_fleet_edits_d' ), $name ), 'url' => Admin_Panel::panel_url( [ 'fleet' => $fleet ] ) ];
			case 'approved':
				return [ 't' => t( 'n_approved_t' ), 'd' => t( 'n_approved_d' ), 'url' => Cabinet::profile_url() ];
			case 'edits_ok':
				return [ 't' => t( 'n_edits_ok_t' ), 'd' => t( 'n_edits_ok_d' ), 'url' => Cabinet::profile_url() ];
			case 'edits_no':
				return [ 't' => t( 'n_edits_no_t' ), 'd' => t( 'n_edits_no_d' ), 'url' => Cabinet::profile_url() ];
			case 'rejected':
				return [ 't' => t( 'n_rejected_t' ), 'd' => t( 'n_rejected_d' ), 'url' => Cabinet::profile_url() ];
			case 'blocked':
				return [ 't' => t( 'n_blocked_t' ), 'd' => t( 'n_blocked_d' ), 'url' => Cabinet::profile_url() ];
			case 'rental_request':
				$listing = (int) ( $args['listing'] ?? 0 );
				$url     = $listing ? (string) get_permalink( (int) apply_filters( 'wpml_object_id', $listing, 'car_listing', true ) ) : '';
				return [ 't' => t( 'n_request_t' ), 'd' => sprintf( t( 'n_request_d' ), '' !== $name ? $name : '—', (string) ( $args['title'] ?? '' ) ), 'url' => $url ];
			case 'work_request':
				// Driver's job application from the public fleet page; details went by email.
				return [ 't' => t( 'n_work_t' ), 'd' => sprintf( t( 'n_work_d' ), '' !== $name ? $name : '—', (string) ( $args['phone'] ?? '' ) ), 'url' => Cabinet::profile_url() ];
		}
		return [ 't' => '', 'd' => '', 'url' => '' ];
	}

	/**
	 * The core handler answers with wp_send_json() and exits, so its result is
	 * read from the output buffer at shutdown: only a request that was really
	 * sent produces a bell notification for the listing's owner.
	 */
	public static function watch_request(): void {
		$listing_id = isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the core handler checks its nonce; nothing is stored unless it succeeds.
		if ( ! $listing_id ) {
			return;
		}
		$name = trim( sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ) . ' ' . sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		ob_start();
		add_action(
			'shutdown',
			static function () use ( $listing_id, $name ): void {
				$out  = (string) ob_get_contents();
				$json = json_decode( $out, true );
				if ( empty( $json['success'] ) ) {
					return;
				}
				self::on_rental_request( $listing_id, $name );
			},
			0
		);
	}

	public static function on_rental_request( int $listing_id, string $name ): void {
		$owner = (int) get_post_field( 'post_author', $listing_id );
		if ( ! $owner ) {
			return;
		}
		self::add( $owner, 'rental_request', [ 'listing' => $listing_id, 'name' => $name, 'title' => (string) get_the_title( $listing_id ) ] );
	}

	public static function ajax_read(): void {
		$uid   = get_current_user_id();
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! $uid || ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			wp_send_json_error( null, 403 );
		}
		$id   = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$list = self::get( $uid );
		foreach ( $list as &$n ) {
			if ( '' === $id || $n['id'] === $id ) {
				$n['read'] = true;
			}
		}
		unset( $n );
		update_user_meta( $uid, self::META, $list );
		wp_send_json_success( [ 'unread' => self::unread( $uid ) ] );
	}

	/** Dropdown for the theme's bell (logged-in users only). */
	/** 'req' — requests from drivers (rental, work); 'sys' — everything else. */
	public static function category( string $type ): string {
		return in_array( $type, [ 'rental_request', 'work_request' ], true ) ? 'req' : 'sys';
	}

	public static function render_bell(): void {
		$uid = get_current_user_id();
		if ( ! $uid ) {
			return;
		}
		$today     = strtotime( 'today', current_time( 'timestamp' ) );
		$yesterday = $today - DAY_IN_SECONDS;
		$items     = [];
		foreach ( array_slice( self::get( $uid ), 0, 30 ) as $n ) {
			$p = self::present( $n );
			if ( '' === $p['t'] ) {
				continue;
			}
			$local = (int) $n['time'] + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			if ( $local >= $today ) {
				$day = t( 'n_today' );
			} elseif ( $local >= $yesterday ) {
				$day = t( 'n_yesterday' );
			} else {
				$day = gmdate( 'd.m.Y', $local );
			}
			$items[] = [
				'id'   => $n['id'],
				't'    => $p['t'],
				'd'    => $p['d'],
				'url'  => $p['url'],
				'read' => ! empty( $n['read'] ),
				'when' => gmdate( 'H:i', $local ),
				'day'  => $day,
				'cat'  => self::category( (string) ( $n['type'] ?? '' ) ),
			];
		}
		$data = [
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( self::NONCE ),
			'items' => $items,
			'i18n'  => [
				'title'    => t( 'n_title' ),
				'readAll'  => t( 'n_read_all' ),
				'empty'    => t( 'n_empty' ),
				'all'      => t( 'n_tab_all' ),
				'requests' => t( 'n_tab_requests' ),
				'system'   => t( 'n_tab_system' ),
				'close'    => t( 'n_close' ),
			],
		];
		$base = plugins_url( 'assets/', dirname( __DIR__ ) . '/taxibaza-fleets.php' );
		echo '<script>window.tbfBell=' . wp_json_encode( $data ) . ";</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON.
		echo '<link rel="stylesheet" href="' . esc_url( $base . 'bell.css?ver=' . VERSION ) . '">' . "\n"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- printed late in footer on purpose.
		echo '<script src="' . esc_url( $base . 'bell.js?ver=' . VERSION ) . '"></script>' . "\n"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}
}
