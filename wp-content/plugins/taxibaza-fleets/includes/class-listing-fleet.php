<?php
/**
 * "This listing belongs to my fleet" — a checkbox in the add/edit listing form
 * for fleet accounts.
 *
 * - Checked (default): the listing page shows the fleet's name and the fleet role.
 * - Unchecked: the listing is shown as an independent one (account name, owner role).
 * Stored on the listing as meta `tbf_fleet_listing` ('1' / '0'). Listings of
 * fleets saved before this existed have no meta and count as fleet listings.
 * Listings of other accounts never belong to a fleet.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Listing_Fleet {

	public const META  = 'tbf_fleet_listing';
	public const FIELD = 'tbf_fleet_listing';
	public const SENT  = 'tbf_fleet_listing_sent';

	public static function init(): void {
		add_action( 'save_post_car_listing', [ self::class, 'on_save' ], 20, 2 );
		add_action( 'wp_footer', [ self::class, 'print_field_script' ], 52 );
		add_action( 'wp_footer', [ self::class, 'media_upload_first' ], 52 );
	}

	/**
	 * Add/edit listing: the "Vybrať fotku" window opens on "Nahrať súbory"
	 * (the library tab already shows only this listing's photos).
	 */
	public static function media_upload_first(): void {
		if ( ! is_user_logged_in() || ! is_page_template( 'page-add-listing.php' ) ) {
			return;
		}
		echo "<script id=\"tbf-media-upload-first\">(function(){function set(){if(!(window.wp&&wp.media&&wp.media.controller&&wp.media.controller.Library)){return false;}var d=wp.media.controller.Library.prototype.defaults;d.content='upload';d.contentUserSetting=false;return true;}if(!set()){document.addEventListener('DOMContentLoaded',set);window.addEventListener('load',set);}})();</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static script.
	}

	public static function is_fleet_listing( int $post_id ): bool {
		$author = (int) get_post_field( 'post_author', $post_id );
		if ( ! $author || ! Status::is_fleet( $author ) ) {
			return false;
		}
		return '0' !== (string) get_post_meta( $post_id, self::META, true );
	}

	/** Public fleet name: approved profile name, else the account name. */
	public static function fleet_name( int $user_id ): string {
		$live = Profile::get_live( $user_id );
		$name = trim( (string) ( $live['name'] ?? '' ) );
		if ( '' === $name ) {
			$user = get_userdata( $user_id );
			$name = $user ? (string) $user->display_name : '';
		}
		return $name;
	}

	/**
	 * What the listing page shows about the owner.
	 *
	 * @return array{fleet:bool,name:string}|null Null when the author is not a fleet.
	 */
	public static function owner( int $post_id ): ?array {
		$author = (int) get_post_field( 'post_author', $post_id );
		if ( ! $author || ! Status::is_fleet( $author ) ) {
			return null;
		}
		if ( self::is_fleet_listing( $post_id ) ) {
			return [ 'fleet' => true, 'name' => self::fleet_name( $author ) ];
		}
		$user = get_userdata( $author );
		return [ 'fleet' => false, 'name' => $user ? (string) $user->display_name : '' ];
	}

	/**
	 * Save from the theme's AJAX form (core handler `tb_submit_listing`).
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 */
	public static function on_save( $post_id, $post ): void {
		if ( ! wp_doing_ajax() || 'tb_submit_listing' !== sanitize_key( wp_unslash( $_POST['action'] ?? '' ) ) || empty( $_POST[ self::SENT ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the core handler verifies its nonce before saving.
			return;
		}
		if ( ! Status::is_fleet( (int) $post->post_author ) ) {
			return;
		}
		update_post_meta( (int) $post_id, self::META, empty( $_POST[ self::FIELD ] ) ? '0' : '1' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/** Checkbox at the top of the add/edit listing form, fleet accounts only. */
	public static function print_field_script(): void {
		if ( ! is_user_logged_in() || ! is_page_template( 'page-add-listing.php' ) ) {
			return;
		}
		$uid     = get_current_user_id();
		$post_id = absint( wp_unslash( $_GET['post_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $post_id ) {
			$owner = (int) get_post_field( 'post_author', $post_id );
			if ( ! $owner ) {
				return;
			}
			$uid = $owner; // An admin editing a fleet's listing sees the fleet's choice.
		}
		if ( ! Status::is_fleet( $uid ) ) {
			return;
		}
		$data = [
			'field'   => self::FIELD,
			'sent'    => self::SENT,
			'checked' => $post_id ? self::is_fleet_listing( $post_id ) : true,
			'title'   => t( 'listing_fleet_title' ),
			'label'   => sprintf( t( 'listing_fleet_label' ), self::fleet_name( $uid ) ),
			'hint'    => t( 'listing_fleet_hint' ),
		];
		?>
<script id="tbf-fleet-listing">
(function () {
	var d = <?php echo wp_json_encode( $data ); ?>;
	var form = document.getElementById('tb-al-form');
	if (!form || form.querySelector('[name="' + d.field + '"]')) { return; }
	var sec = document.createElement('section'); sec.className = 'tb-fa-fs tbf-fleet-listing';
	var h = document.createElement('h2'); h.className = 'tb-fa-fs__title'; h.textContent = d.title;
	var label = document.createElement('label'); label.className = 'tbf-fleet-listing__label';
	var box = document.createElement('input'); box.type = 'checkbox'; box.name = d.field; box.value = '1'; box.checked = !!d.checked;
	var text = document.createElement('span'); text.textContent = d.label;
	var hint = document.createElement('p'); hint.className = 'tbf-fleet-listing__hint'; hint.textContent = d.hint;
	var sent = document.createElement('input'); sent.type = 'hidden'; sent.name = d.sent; sent.value = '1';
	label.appendChild(box); label.appendChild(text);
	sec.appendChild(h); sec.appendChild(label); sec.appendChild(hint); sec.appendChild(sent);
	var first = form.querySelector('section.tb-fa-fs');
	form.insertBefore(sec, first || null);
}());
</script>
<style>.tbf-fleet-listing__label{display:flex;gap:10px;align-items:flex-start;font-size:15px;line-height:1.45;cursor:pointer}.tbf-fleet-listing__label input{margin-top:3px;width:18px;height:18px;flex:none;accent-color:#02f797}.tbf-fleet-listing__hint{margin:8px 0 0 28px;font-size:13px;opacity:.7}</style>
		<?php
	}
}

