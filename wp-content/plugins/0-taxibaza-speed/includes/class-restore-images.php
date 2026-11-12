<?php
/**
 * Restore the theme's static images (assets/img) from a Duplicator archive.
 *
 * The folder is not in the git repository, so replacing the theme with a zip
 * built from git removes it. This copies exactly the files under
 * wp-content/themes/<theme>/assets/img/ from the chosen archive back into the
 * active theme. Existing files are left as they are.
 *
 * Tools → TaxiBaza images; also runs by itself after the theme is replaced from a zip.
 *
 * @package TaxiBaza\Speed
 */

namespace TaxiBaza\Speed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Restore_Images {

	private const NONCE = 'tbs_restore_images';

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'menu' ] );
		add_action( 'upgrader_process_complete', [ self::class, 'after_upgrade' ], 20, 2 );
	}

	/**
	 * After the theme is replaced from a zip, put the images back right away.
	 *
	 * @param mixed               $upgrader Upgrader.
	 * @param array<string,mixed> $extra    Details.
	 */
	public static function after_upgrade( $upgrader, $extra ): void {
		if ( 'theme' !== ( $extra['type'] ?? '' ) ) {
			return;
		}
		$dir = get_template_directory() . '/assets/img';
		if ( is_dir( $dir ) && glob( $dir . '/*' ) ) {
			return;
		}
		foreach ( self::archives() as $a ) {
			$r = self::restore( $a );
			if ( $r['restored'] > 0 ) {
				Optimize_Images::run( 40 ); // The archive holds the heavy originals.
				return;
			}
		}
	}

	public static function menu(): void {
		add_management_page( 'TaxiBaza images', 'TaxiBaza images', 'manage_options', 'tbs-restore-images', [ self::class, 'render' ] );
	}

	/** @return string[] Archive paths, newest first. */
	public static function archives(): array {
		$files = glob( WP_CONTENT_DIR . '/backups-dup-lite/*_archive.zip' ) ?: [];
		usort( $files, static fn( $a, $b ) => filemtime( $b ) <=> filemtime( $a ) );
		return $files;
	}

	/**
	 * @return array{restored:int,skipped:int,errors:string[]}
	 */
	public static function restore( string $archive ): array {
		$out = [ 'restored' => 0, 'skipped' => 0, 'errors' => [] ];
		if ( ! class_exists( '\ZipArchive' ) ) {
			$out['errors'][] = 'ZipArchive is not available on this server.';
			return $out;
		}
		$zip = new \ZipArchive();
		if ( true !== $zip->open( $archive ) ) {
			$out['errors'][] = 'Cannot open the archive.';
			return $out;
		}
		$theme  = get_template();
		$prefix = 'wp-content/themes/' . $theme . '/assets/img/';
		$dest   = get_template_directory() . '/assets/img/';
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name = (string) $zip->getNameIndex( $i );
			$pos  = strpos( $name, $prefix );
			if ( false === $pos || str_ends_with( $name, '/' ) ) {
				continue;
			}
			$rel = substr( $name, $pos + strlen( $prefix ) );
			if ( '' === $rel || str_contains( $rel, '..' ) || ! preg_match( '/\.(jpe?g|png|webp|avif|svg|gif)$/i', $rel ) ) {
				continue;
			}
			$target = $dest . $rel;
			if ( file_exists( $target ) ) {
				$out['skipped']++;
				continue;
			}
			wp_mkdir_p( dirname( $target ) );
			$in = $zip->getStream( $name );
			$fh = $in ? fopen( $target, 'wb' ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			if ( ! $in || ! $fh ) {
				$out['errors'][] = $rel;
				continue;
			}
			stream_copy_to_stream( $in, $fh );
			fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $in ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			$out['restored']++;
		}
		$zip->close();
		return $out;
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$archives = self::archives();
		echo '<div class="wrap"><h1>TaxiBaza: theme images</h1>';
		if ( isset( $_POST['tbs_archive'] ) && check_admin_referer( self::NONCE ) ) {
			$pick = basename( sanitize_file_name( wp_unslash( $_POST['tbs_archive'] ) ) );
			foreach ( $archives as $a ) {
				if ( basename( $a ) === $pick ) {
					@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					$r = self::restore( $a );
					echo '<div class="notice notice-' . ( $r['errors'] ? 'warning' : 'success' ) . '" id="tbs-restore-result"><p>' . esc_html( sprintf( 'Restored: %d, already there: %d, errors: %s', $r['restored'], $r['skipped'], $r['errors'] ? implode( ', ', $r['errors'] ) : '0' ) ) . '</p></div>';
				}
			}
		}
		$dir = get_template_directory() . '/assets/img';
		$cnt = is_dir( $dir ) ? count( glob( $dir . '/{,*/}*.*', GLOB_BRACE ) ?: [] ) : 0;
		echo '<p>Images in the active theme now: <strong id="tbs-img-count">' . (int) $cnt . '</strong></p>';
		if ( $archives ) {
			echo '<form method="post">';
			wp_nonce_field( self::NONCE );
			echo '<p><select name="tbs_archive">';
			foreach ( $archives as $a ) {
				echo '<option value="' . esc_attr( basename( $a ) ) . '">' . esc_html( basename( $a ) . ' (' . size_format( (int) filesize( $a ) ) . ')' ) . '</option>';
			}
			echo '</select> <button class="button button-primary">Restore missing images</button></p></form>';
		} else {
			echo '<p>No Duplicator archive found.</p>';
		}
		Optimize_Images::render_section();
		echo '</div>';
	}
}
