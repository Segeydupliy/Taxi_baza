<?php
/**
 * "Flotily" in the site navigation (header, mobile drawer, footer "Navigácia"),
 * right after the car catalogue, in every language. The header/footer menus are
 * ACF repeaters in the theme; the theme passes them through the filters
 * `tb_header_menu` and `tb_footer_nav_links`, so nothing is stored in the DB.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Menu {

	public static function init(): void {
		add_filter( 'tb_header_menu', [ self::class, 'header' ], 20 );
		add_filter( 'tb_footer_nav_links', [ self::class, 'footer' ], 20 );
	}

	/** Path of the car catalogue (e.g. "/avto/"). */
	private static function catalog_path(): string {
		$url  = function_exists( 'get_post_type_archive_link' ) ? (string) get_post_type_archive_link( 'car_listing' ) : '';
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$path = (string) preg_replace( '#^/[a-z]{2}/#', '/', $path ); // Drop a WPML language folder.
		return '' !== trim( $path, '/' ) ? '/' . trim( $path, '/' ) . '/' : '/avto/';
	}

	/** Path part of a menu URL without a language folder. */
	public static function path_of( string $url ): string {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$path = (string) preg_replace( '#^/[a-z]{2}/#', '/', '/' . ltrim( $path, '/' ) );
		return '/' . trim( $path, '/' ) . ( '' !== trim( $path, '/' ) ? '/' : '' );
	}

	/**
	 * Insert $item after the catalogue item (or as the 2nd item). If the menu
	 * already links to the fleets (an admin added it by hand), only mark it.
	 *
	 * @param array<int,array<string,mixed>> $menu    Menu rows (label, url, …).
	 * @param array<string,mixed>            $item    Our row.
	 * @param string                         $catalog Catalogue path, e.g. "/avto/".
	 * @return array<int,array<string,mixed>>
	 */
	public static function insert( array $menu, array $item, string $catalog ): array {
		$base = '/' . Directory::BASE . '/';
		foreach ( $menu as $i => $row ) {
			if ( is_array( $row ) && 0 === strpos( self::path_of( (string) ( $row['url'] ?? '' ) ), $base ) ) {
				$menu[ $i ]['current'] = ! empty( $item['current'] );
				return $menu;
			}
		}
		$menu = array_values( $menu );
		$pos  = min( 1, count( $menu ) );
		foreach ( $menu as $i => $row ) {
			if ( is_array( $row ) && self::path_of( (string) ( $row['url'] ?? '' ) ) === $catalog ) {
				$pos = $i + 1;
				break;
			}
		}
		array_splice( $menu, $pos, 0, [ $item ] );
		return $menu;
	}

	/**
	 * @param mixed $menu Header menu rows.
	 * @return array<int,array<string,mixed>>
	 */
	public static function header( $menu ): array {
		$menu = is_array( $menu ) ? $menu : [];
		return self::insert(
			$menu,
			[
				'label'        => t( 'fl_nav' ),
				'url'          => Directory::base_url(),
				'submenu_type' => 'none',
				'submenu'      => [],
				'current'      => Fleet_Pages::is_fleet_page(),
			],
			self::catalog_path()
		);
	}

	/**
	 * @param mixed $links Footer rows (label, url relative or absolute).
	 * @return array<int,array<string,mixed>>
	 */
	public static function footer( $links ): array {
		$links = is_array( $links ) ? $links : [];
		return self::insert(
			$links,
			[
				'label'    => t( 'fl_nav' ),
				'url'      => Directory::base_url(),
				'internal' => true, // Absolute URL, but not an external link (no new tab).
				'current'  => Fleet_Pages::is_fleet_page(),
			],
			self::catalog_path()
		);
	}
}
