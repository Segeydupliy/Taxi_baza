<?php

namespace TaxiRental\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Taxonomies {
	public static function init(): void {
		add_action( 'init', [ self::class, 'register' ] );
	}

	public static function register(): void {
		register_taxonomy(
			'city',
			'car_listing',
			[
				'labels'            => self::build_labels(
					__( 'City', 'taxi-rental' ),
					__( 'Cities', 'taxi-rental' )
				),
				'hierarchical'      => false,
				'public'            => true,
				'show_in_rest'      => true,
				'rewrite'           => [ 'slug' => 'mesto', 'with_front' => false ],
				'show_admin_column' => true,
			]
		);

		register_taxonomy(
			'platform',
			'car_listing',
			[
				'labels'            => self::build_labels(
					__( 'Platform', 'taxi-rental' ),
					__( 'Platforms', 'taxi-rental' )
				),
				'hierarchical'      => false,
				'public'            => true,
				'show_in_rest'      => true,
				'rewrite'           => [ 'slug' => 'platforma', 'with_front' => false ],
				'show_admin_column' => true,
			]
		);

		register_taxonomy(
			'ride_category',
			'car_listing',
			[
				'labels'            => self::build_labels(
					__( 'Ride Category', 'taxi-rental' ),
					__( 'Ride Categories', 'taxi-rental' )
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'rewrite'           => [ 'slug' => 'kategoria', 'with_front' => false ],
				'show_admin_column' => true,
			]
		);

		// Car makes & models: hierarchical — make = parent term, model = child term.
		register_taxonomy(
			'car_make',
			'car_listing',
			[
				'labels'            => self::build_labels(
					__( 'Make', 'taxi-rental' ),
					__( 'Makes', 'taxi-rental' )
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'rewrite'           => [ 'slug' => 'znacka', 'with_front' => false ],
				'show_admin_column' => true,
			]
		);
	}

	private static function build_labels( string $singular, string $plural ): array {
		return [
			'name'              => $plural,
			'singular_name'     => $singular,
			/* translators: %s: plural taxonomy name */
			'search_items'      => sprintf( __( 'Search %s', 'taxi-rental' ), $plural ),
			/* translators: %s: plural taxonomy name */
			'all_items'         => sprintf( __( 'All %s', 'taxi-rental' ), $plural ),
			/* translators: %s: singular taxonomy name */
			'edit_item'         => sprintf( __( 'Edit %s', 'taxi-rental' ), $singular ),
			/* translators: %s: singular taxonomy name */
			'update_item'       => sprintf( __( 'Update %s', 'taxi-rental' ), $singular ),
			/* translators: %s: singular taxonomy name */
			'add_new_item'      => sprintf( __( 'Add New %s', 'taxi-rental' ), $singular ),
			/* translators: %s: singular taxonomy name */
			'new_item_name'     => sprintf( __( 'New %s Name', 'taxi-rental' ), $singular ),
			'menu_name'         => $plural,
			'not_found'         => sprintf( __( 'No %s found', 'taxi-rental' ), strtolower( $plural ) ),
		];
	}
}
