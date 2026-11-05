<?php

namespace TaxiRental\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Statuses {
	public static function init(): void {
		add_action( 'init', [ self::class, 'register' ] );
		add_filter( 'display_post_states', [ self::class, 'display_states' ], 10, 2 );
	}

	public static function register(): void {
		register_post_status(
			'rejected',
			[
				'label'                     => _x( 'Rejected', 'post status', 'taxi-rental' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				// translators: %s: number of posts with this status.
				'label_count'               => _n_noop(
					'Rejected <span class="count">(%s)</span>',
					'Rejected <span class="count">(%s)</span>',
					'taxi-rental'
				),
			]
		);

		register_post_status(
			'archived',
			[
				'label'                     => _x( 'Archived', 'post status', 'taxi-rental' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				// translators: %s: number of posts with this status.
				'label_count'               => _n_noop(
					'Archived <span class="count">(%s)</span>',
					'Archived <span class="count">(%s)</span>',
					'taxi-rental'
				),
			]
		);
	}

	/**
	 * @param string[]  $states Existing post state labels.
	 * @param \WP_Post  $post   Current post.
	 * @return string[]
	 */
	public static function display_states( array $states, \WP_Post $post ): array {
		if ( 'car_listing' !== $post->post_type ) {
			return $states;
		}

		match ( $post->post_status ) {
			'rejected' => $states['rejected'] = _x( 'Rejected', 'post status', 'taxi-rental' ),
			'archived' => $states['archived'] = _x( 'Archived', 'post status', 'taxi-rental' ),
			default    => null,
		};

		return $states;
	}
}
