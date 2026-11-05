<?php
/**
 * class-submission.php
 * ACF form submission handler: auto-title, status routing, capability guard.
 * Hooks into acf/save_post (priority 20, after ACF writes fields).
 */

namespace TaxiRental\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Submission {

	public static function init(): void {
		add_action( 'acf/save_post', [ static::class, 'handle_save' ], 20 );
	}

	/**
	 * Fires after ACF saves fields for any post.
	 * We only act on car_listing posts submitted by the front-end form.
	 *
	 * @param int|string $post_id
	 */
	public static function handle_save( $post_id ): void {
		// Bail for non-numeric IDs (options pages, etc.).
		if ( ! is_numeric( $post_id ) ) {
			return;
		}

		$post_id = (int) $post_id;
		$post    = get_post( $post_id );

		if ( ! $post || 'car_listing' !== $post->post_type ) {
			return;
		}

		// Must be logged in.
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user    = wp_get_current_user();
		$user_id = (int) $user->ID;

		// Only car_owner / fleet roles (or admin) may submit.
		$allowed_roles = [ 'car_owner', 'fleet', 'administrator', 'editor' ];
		if ( ! array_intersect( (array) $user->roles, $allowed_roles ) ) {
			return;
		}

		// Ownership guard: non-admins may only save their own posts.
		$is_privileged = array_intersect( (array) $user->roles, [ 'administrator', 'editor' ] );
		if ( ! $is_privileged && (int) $post->post_author !== $user_id ) {
			return;
		}

		// Auto-generate post title from ACF fields.
		static::maybe_set_title( $post_id );

		// Route status: new posts go to pending; published posts stay published after edit.
		static::route_status( $post_id, $post );
	}

	/** Build and save post title from make + model + year. */
	private static function maybe_set_title( int $post_id ): void {
		$make  = sanitize_text_field( (string) get_field( 'make', $post_id ) );
		$model = sanitize_text_field( (string) get_field( 'model', $post_id ) );
		$year  = (int) get_field( 'year', $post_id );

		$parts = array_filter( [ $make, $model, $year > 0 ? (string) $year : '' ] );
		if ( empty( $parts ) ) {
			return;
		}

		$title = implode( ' ', $parts );

		// Avoid infinite loop — unhook before updating.
		remove_action( 'acf/save_post', [ static::class, 'handle_save' ], 20 );
		wp_update_post(
			[
				'ID'         => $post_id,
				'post_title' => $title,
				'post_name'  => sanitize_title( $title ),
			]
		);
		add_action( 'acf/save_post', [ static::class, 'handle_save' ], 20 );
	}

	/**
	 * Route post status:
	 * - draft (newly created) → pending (requires moderation)
	 * - pending               → stays pending
	 * - publish               → stays publish (owner edits live listing)
	 * - rejected              → pending (resubmission after fix)
	 * - archived              → stays archived
	 */
	private static function route_status( int $post_id, \WP_Post $post ): void {
		$current = $post->post_status;

		$needs_moderation = [ 'draft', 'pending', 'rejected' ];
		if ( ! in_array( $current, $needs_moderation, true ) ) {
			return;
		}

		if ( 'pending' === $current ) {
			return; // Already queued — no re-save needed.
		}

		remove_action( 'acf/save_post', [ static::class, 'handle_save' ], 20 );
		wp_update_post( [ 'ID' => $post_id, 'post_status' => 'pending' ] );
		add_action( 'acf/save_post', [ static::class, 'handle_save' ], 20 );
	}
}
