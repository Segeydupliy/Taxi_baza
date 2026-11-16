<?php
/**
 * Photos on the add/edit listing form from a phone.
 *
 * On phones and tablets the theme's media window (WP media library) is hard to
 * use: on iPhone Safari the confirm button can end up out of sight, so a photo
 * is picked but never added. There the "Pridať fotografiu" button opens the
 * phone's own photo picker instead:
 * - several photos at once (HEIC, JPEG, PNG, WebP);
 * - each photo is reduced on the phone to max 2048 px, JPEG (also turns HEIC
 *   into JPEG where the browser can read it; otherwise the server converts
 *   HEIC → JPEG with Imagick);
 * - a tile with progress per photo, then the same gallery item as before;
 * - a clear message in the page language if a photo can't be uploaded;
 * - saving waits until the uploads are finished.
 * Desktop keeps the media window.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Listing_Photos {

	public const ACTION   = 'tbf_listing_photo';
	public const NONCE    = 'tbf_listing_photo';
	public const MAX_SIZE = 26214400; // 25 MB.
	public const TYPES    = [ 'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif' ];

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue' ], 30 );
		add_action( 'wp_ajax_' . self::ACTION, [ self::class, 'ajax_upload' ] );
	}

	public static function enqueue(): void {
		if ( ! is_user_logged_in() || ! is_page_template( 'page-add-listing.php' ) || ! current_user_can( 'upload_files' ) ) {
			return;
		}
		$base = plugins_url( 'assets/', FILE );
		wp_enqueue_style( 'tbf-listing-photos', $base . 'listing-photos.css', [], VERSION );
		wp_enqueue_script( 'tbf-listing-photos', $base . 'listing-photos.js', [], VERSION, true );
		wp_localize_script(
			'tbf-listing-photos',
			'tbfPhotos',
			[
				'ajax'    => admin_url( 'admin-ajax.php' ),
				'action'  => self::ACTION,
				'nonce'   => wp_create_nonce( self::NONCE ),
				'maxSize' => self::MAX_SIZE,
				'maxSide' => 2048,
				'i18n'    => [
					'uploading' => t( 'photo_uploading' ),
					'type'      => t( 'photo_err_type' ),
					'size'      => t( 'photo_err_size' ),
					'network'   => t( 'photo_err_network' ),
					'server'    => t( 'photo_err_server' ),
					'wait'      => t( 'photo_wait' ),
					'remove'    => t( 'photo_remove' ),
					'close'     => t( 'n_close' ),
				],
			]
		);
	}

	/** Friendly message for an upload error. */
	public static function message( string $code, string $detail = '' ): string {
		switch ( $code ) {
			case 'type':
				return t( 'photo_err_type' );
			case 'size':
				return t( 'photo_err_size' );
			default:
				return '' !== $detail ? sprintf( t( 'photo_err_server' ), $detail ) : t( 'photo_err_network' );
		}
	}

	public static function ajax_upload(): void {
		if ( ! check_ajax_referer( self::NONCE, 'nonce', false ) || ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( [ 'message' => self::message( 'server', 'nonce' ) ], 403 );
		}
		$file = $_FILES['file'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- checked by wp_handle_upload().
		if ( ! is_array( $file ) || empty( $file['tmp_name'] ) || ! empty( $file['error'] ) ) {
			$err = is_array( $file ) ? (int) ( $file['error'] ?? 0 ) : 0;
			wp_send_json_error( [ 'message' => in_array( $err, [ UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ], true ) ? self::message( 'size' ) : self::message( 'network' ) ], 400 );
		}
		if ( (int) $file['size'] > self::MAX_SIZE ) {
			wp_send_json_error( [ 'message' => self::message( 'size' ) ], 400 );
		}
		$check = wp_check_filetype_and_ext( (string) $file['tmp_name'], (string) $file['name'] );
		$mime  = (string) ( $check['type'] ?: '' );
		if ( '' === $mime ) {
			$info = @getimagesize( (string) $file['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$mime = is_array( $info ) ? (string) $info['mime'] : '';
		}
		if ( ! in_array( $mime, self::TYPES, true ) ) {
			wp_send_json_error( [ 'message' => self::message( 'type' ) ], 400 );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		// The theme ties the upload to this form's session (tb_session) on add_attachment.
		$id = media_handle_upload( 'file', 0, [], [ 'test_form' => false, 'mimes' => [ 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'heic' => 'image/heic', 'heif' => 'image/heif' ] ] );
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( [ 'message' => self::message( 'server', $id->get_error_message() ) ], 400 );
		}
		$thumb = (string) wp_get_attachment_image_url( (int) $id, 'thumbnail' );
		wp_send_json_success(
			[
				'id'    => (int) $id,
				'thumb' => '' !== $thumb ? $thumb : (string) wp_get_attachment_url( (int) $id ),
			]
		);
	}
}
