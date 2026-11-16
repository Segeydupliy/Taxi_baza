<?php
/**
 * "Odstrániť" in the panel moves a listing to the trash; WordPress empties the
 * trash after 30 days (EMPTY_TRASH_DAYS). When a listing is deleted for good —
 * by that automatic cleanup or by hand — its photos go too, unless another
 * listing (e.g. its translation) still uses them. Nothing happens while a
 * listing is only in the trash, so it can be restored with its photos.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Listing_Cleanup {

	/** @var array<int,int[]> listing ID => its photos, between "before" and "after" delete. */
	private static array $photos = [];

	public static function init(): void {
		add_action( 'before_delete_post', [ self::class, 'remember' ], 10, 2 );
		add_action( 'deleted_post', [ self::class, 'delete_photos' ], 10, 2 );
	}

	/** @return int[] Photos of a listing: gallery, featured image, attached files. */
	public static function photos_of( int $post_id ): array {
		$ids     = [];
		$gallery = get_post_meta( $post_id, 'gallery', true );
		if ( is_string( $gallery ) ) {
			$gallery = explode( ',', $gallery );
		}
		foreach ( (array) $gallery as $g ) {
			$ids[] = (int) $g;
		}
		$ids[] = (int) get_post_meta( $post_id, '_thumbnail_id', true );
		foreach ( (array) get_children( [ 'post_parent' => $post_id, 'post_type' => 'attachment', 'fields' => 'ids' ] ) as $a ) {
			$ids[] = (int) $a;
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/** Is the photo still used by another listing (gallery or featured image)? */
	public static function used_elsewhere( int $attachment_id, int $except ): bool {
		global $wpdb;
		$found = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT pm.post_id FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type = 'car_listing'
				 WHERE pm.post_id <> %d AND (
				   ( pm.meta_key = '_thumbnail_id' AND pm.meta_value = %s )
				   OR ( pm.meta_key = 'gallery' AND ( pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR FIND_IN_SET( %s, pm.meta_value ) ) )
				 ) LIMIT 1",
				$except,
				(string) $attachment_id,
				'%"' . $wpdb->esc_like( (string) $attachment_id ) . '"%',
				'%i:' . $wpdb->esc_like( (string) $attachment_id ) . ';%',
				(string) $attachment_id
			)
		);
		return (bool) $found;
	}

	/**
	 * @param int           $post_id Post.
	 * @param \WP_Post|null $post    Post object.
	 */
	public static function remember( $post_id, $post = null ): void {
		$post = $post instanceof \WP_Post ? $post : get_post( (int) $post_id );
		if ( $post && 'car_listing' === $post->post_type ) {
			self::$photos[ (int) $post_id ] = self::photos_of( (int) $post_id );
		}
	}

	/**
	 * @param int           $post_id Post.
	 * @param \WP_Post|null $post    Post object.
	 */
	public static function delete_photos( $post_id, $post = null ): void {
		$post_id = (int) $post_id;
		if ( empty( self::$photos[ $post_id ] ) ) {
			return;
		}
		$ids = self::$photos[ $post_id ];
		unset( self::$photos[ $post_id ] );
		foreach ( $ids as $aid ) {
			if ( 'attachment' === get_post_type( $aid ) && ! self::used_elsewhere( $aid, $post_id ) ) {
				wp_delete_attachment( $aid, true );
			}
		}
	}
}
