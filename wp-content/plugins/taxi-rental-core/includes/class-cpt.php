<?php

namespace TaxiRental\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CPT {
	public static function init(): void {
		add_action( 'init', [ self::class, 'register' ] );
	}

	public static function register(): void {
		$labels = [
			'name'               => _x( 'Car Listings', 'post type general name', 'taxi-rental' ),
			'singular_name'      => _x( 'Car Listing', 'post type singular name', 'taxi-rental' ),
			'add_new'            => __( 'Add New', 'taxi-rental' ),
			'add_new_item'       => __( 'Add New Car Listing', 'taxi-rental' ),
			'edit_item'          => __( 'Edit Car Listing', 'taxi-rental' ),
			'new_item'           => __( 'New Car Listing', 'taxi-rental' ),
			'view_item'          => __( 'View Car Listing', 'taxi-rental' ),
			'search_items'       => __( 'Search Car Listings', 'taxi-rental' ),
			'not_found'          => __( 'No car listings found', 'taxi-rental' ),
			'not_found_in_trash' => __( 'No car listings found in Trash', 'taxi-rental' ),
			'menu_name'          => __( 'Car Listings', 'taxi-rental' ),
			'all_items'          => __( 'All Car Listings', 'taxi-rental' ),
		];

		register_post_type(
			'car_listing',
			[
				'labels'          => $labels,
				'public'          => true,
				'has_archive'     => true,
				'rewrite'         => [
					'slug'       => 'avto',
					'with_front' => false,
				],
				'supports'        => [ 'title', 'thumbnail', 'author' ],
				'show_in_rest'    => true,
				'menu_icon'       => 'dashicons-car',
				'capability_type' => 'car_listing',
				'map_meta_cap'    => true,
			]
		);
	}
}
