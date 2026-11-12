<?php
/**
 * Make the theme's photos light (Tools → TaxiBaza images → "Optimise").
 *
 * The theme's assets/img holds the original camera photos (hero-car.jpg 7.9 MB,
 * about-car.jpg 4.8 MB …) and the city pictures are PNGs of ~1 MB (media
 * library, size medium_large); they are loaded as they are.
 * On the server, with WordPress' image editor (GD / Imagick):
 * - the original is copied once to uploads/tbs-img-originals/ (nothing is lost);
 * - the file is resized (JPEG: max 1920 px wide, PNG: max 1000 px) and saved
 *   again with the quality lowered step by step until it is ≤ 300 KB;
 * - a WebP copy is written next to it (hero-car.jpg.webp, ≤ 200 KB target);
 * - .htaccess in assets/img and uploads serves the WebP copy to browsers that
 *   accept WebP under the same address, so no theme file has to change.
 * Runs again by itself after the images are restored from a Duplicator archive.
 *
 * @package TaxiBaza\Speed
 */

namespace TaxiBaza\Speed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Optimize_Images {

	public const NONCE     = 'tbs_optimize_images';
	public const LIMIT     = 300 * 1024;
	public const LIMIT_WEB = 200 * 1024;
	public const QUALITIES = [ 82, 76, 70, 64, 58, 52 ];
	public const DONE_META = 'tbs_img_optimized'; // Option: rel path => mtime of the optimised file.

	public static function dir(): string {
		return get_template_directory() . '/assets/img';
	}

	public static function backup_dir(): string {
		$up = wp_upload_dir( null, false );
		return trailingslashit( (string) $up['basedir'] ) . 'tbs-img-originals';
	}

	private static function abs( string $rel ): string {
		return wp_normalize_path( WP_CONTENT_DIR ) . '/' . $rel;
	}

	/**
	 * Every photo handled here, keyed by its path inside wp-content:
	 * the theme's assets/img and the city pictures (term meta tb_image_id, in the
	 * size the city cards use — medium_large).
	 *
	 * @return array<string,int> rel path => bytes.
	 */
	public static function files(): array {
		$out  = [];
		$base = wp_normalize_path( WP_CONTENT_DIR ) . '/';
		$dir  = self::dir();
		if ( is_dir( $dir ) ) {
			$it = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) );
			foreach ( $it as $f ) {
				if ( $f->isFile() && preg_match( '/\.(jpe?g|png)$/i', $f->getFilename() ) ) {
					$out[ substr( wp_normalize_path( $f->getPathname() ), strlen( $base ) ) ] = (int) $f->getSize();
				}
			}
		}
		foreach ( self::city_image_paths() as $path ) {
			$path = wp_normalize_path( $path );
			if ( str_starts_with( $path, $base ) && is_file( $path ) && preg_match( '/\.(jpe?g|png)$/i', $path ) ) {
				$out[ substr( $path, strlen( $base ) ) ] = (int) filesize( $path );
			}
		}
		ksort( $out );
		return $out;
	}

	/** @return string[] Files of the city pictures the site shows. */
	public static function city_image_paths(): array {
		global $wpdb;
		$ids = array_unique( array_map( 'intval', (array) $wpdb->get_col( "SELECT meta_value FROM {$wpdb->termmeta} WHERE meta_key = 'tb_image_id' AND meta_value <> ''" ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$up  = wp_upload_dir( null, false );
		$out = [];
		foreach ( array_filter( $ids ) as $id ) {
			foreach ( [ 'medium_large', 'large' ] as $size ) {
				$url = (string) wp_get_attachment_image_url( $id, $size );
				if ( '' !== $url && str_starts_with( $url, (string) $up['baseurl'] ) ) {
					$out[] = (string) $up['basedir'] . substr( $url, strlen( (string) $up['baseurl'] ) );
				}
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Files still to do: too heavy, or without a WebP copy — unless already
	 * processed in their current version (a PNG photo can stay above 300 KB;
	 * browsers get its WebP copy).
	 *
	 * @return array<string,int>
	 */
	public static function todo(): array {
		$done = (array) get_option( self::DONE_META, [] );
		return array_filter(
			self::files(),
			static function ( $size, $rel ) use ( $done ) {
				if ( isset( $done[ $rel ] ) && (int) $done[ $rel ] === (int) @filemtime( self::abs( $rel ) ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					return false;
				}
				return $size > self::LIMIT || ( self::webp_ok() && ! file_exists( self::abs( $rel ) . '.webp' ) );
			},
			ARRAY_FILTER_USE_BOTH
		);
	}

	public static function webp_ok(): bool {
		return function_exists( 'wp_image_editor_supports' ) && wp_image_editor_supports( [ 'mime_type' => 'image/webp' ] );
	}

	/**
	 * Save with falling quality until the file is small enough.
	 *
	 * @param \WP_Image_Editor $ed Editor with the resized image.
	 */
	private static function save_small( $ed, string $dest, string $mime, int $limit ): int {
		$ext  = [ 'image/png' => 'png', 'image/webp' => 'webp' ][ $mime ] ?? 'jpg';
		$last = self::QUALITIES[ count( self::QUALITIES ) - 1 ];
		foreach ( self::QUALITIES as $q ) {
			$ed->set_quality( $q );
			// The editor forces the extension of the MIME type, so the temp name ends with it.
			$r = $ed->save( $dest . '.tbs-tmp.' . $ext, $mime );
			if ( is_wp_error( $r ) || empty( $r['path'] ) || ! file_exists( $r['path'] ) ) {
				return 0;
			}
			$size = (int) filesize( $r['path'] );
			if ( $size <= $limit || $last === $q || 'image/png' === $mime ) {
				rename( $r['path'], $dest ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
				return $size;
			}
			wp_delete_file( $r['path'] );
		}
		return 0;
	}

	/** @return array{before:int,after:int,webp:int,error:string} */
	public static function optimize( string $rel ): array {
		$out  = [ 'before' => 0, 'after' => 0, 'webp' => 0, 'error' => '' ];
		$src  = self::abs( $rel );
		if ( str_contains( $rel, '..' ) || ! is_file( $src ) ) {
			$out['error'] = 'not found';
			return $out;
		}
		$out['before'] = (int) filesize( $src );
		$backup        = self::backup_dir() . '/' . $rel;
		if ( ! file_exists( $backup ) ) {
			wp_mkdir_p( dirname( $backup ) );
			copy( $src, $backup );
		}
		$png  = (bool) preg_match( '/\.png$/i', $rel );
		$mime = $png ? 'image/png' : 'image/jpeg';
		$max  = str_starts_with( $rel, 'uploads/' ) ? 1920 : ( $png ? 1000 : 1920 ); // Media sizes are already sized.

		$ed = wp_get_image_editor( $backup );
		if ( is_wp_error( $ed ) ) {
			$out['error'] = $ed->get_error_message();
			return $out;
		}
		$dim = $ed->get_size();
		if ( (int) $dim['width'] > $max ) {
			$ed->resize( $max, null, false );
		}
		if ( $out['before'] > self::LIMIT ) {
			$after        = self::save_small( $ed, $src, $mime, self::LIMIT );
			$out['after'] = $after && $after < $out['before'] ? $after : $out['before'];
			if ( $after && $after >= $out['before'] ) {
				copy( $backup, $src ); // Never make a file heavier.
			}
		} else {
			$out['after'] = $out['before'];
		}
		if ( self::webp_ok() ) {
			$out['webp'] = self::save_small( $ed, $src . '.webp', 'image/webp', self::LIMIT_WEB );
		}
		clearstatcache();
		$done         = (array) get_option( self::DONE_META, [] );
		$done[ $rel ] = (int) filemtime( $src );
		update_option( self::DONE_META, $done, false );
		return $out;
	}

	/** @return string[] .htaccess lines: same address, WebP body for browsers that accept it. */
	public static function webp_rules(): array {
		return [
			'<IfModule mod_rewrite.c>',
			'RewriteEngine On',
			'RewriteCond %{HTTP_ACCEPT} image/webp',
			'RewriteCond %{REQUEST_FILENAME}.webp -f',
			'RewriteRule ^(.+)\.(jpe?g|png)$ $1.$2.webp [T=image/webp,L]',
			'</IfModule>',
			'<IfModule mod_headers.c>',
			'<FilesMatch "\.(jpe?g|png|webp)$">',
			'Header append Vary Accept',
			'Header set Cache-Control "public, max-age=2592000"',
			'</FilesMatch>',
			'</IfModule>',
			'<IfModule mod_mime.c>',
			'AddType image/webp .webp',
			'</IfModule>',
		];
	}

	/** Rules in assets/img/.htaccess and uploads/.htaccess (only files with a .webp copy are affected). */
	public static function write_htaccess(): bool {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		$up = wp_upload_dir( null, false );
		$ok = true;
		foreach ( [ self::dir(), (string) $up['basedir'] ] as $dir ) {
			if ( is_dir( $dir ) ) {
				$ok = insert_with_markers( $dir . '/.htaccess', 'TaxiBaza WebP', self::webp_rules() ) && $ok;
			}
		}
		return $ok;
	}

	/**
	 * Optimise up to $limit files (big ones first). Time-boxed for shared hosting.
	 *
	 * @return array{done:int,saved:int,left:int,errors:string[]}
	 */
	public static function run( int $limit = 8 ): array {
		@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$res   = [ 'done' => 0, 'saved' => 0, 'left' => 0, 'errors' => [] ];
		$todo  = self::todo();
		arsort( $todo );
		$start = microtime( true );
		foreach ( array_keys( $todo ) as $rel ) {
			if ( $res['done'] >= $limit || microtime( true ) - $start > 60 ) {
				break;
			}
			$r = self::optimize( $rel );
			if ( '' !== $r['error'] ) {
				$res['errors'][] = $rel . ': ' . $r['error'];
				continue;
			}
			$res['done']++;
			$res['saved'] += max( 0, $r['before'] - $r['after'] );
		}
		self::write_htaccess();
		$res['left'] = count( self::todo() );
		return $res;
	}

	public static function render_section(): void {
		$files = self::files();
		$todo  = self::todo();
		$total = array_sum( $files );
		echo '<hr><h2>Optimise photos (≤ 300 KB + WebP)</h2>';
		if ( isset( $_POST['tbs_optimize'] ) && check_admin_referer( self::NONCE ) ) {
			$r = self::run( 8 );
			echo '<div class="notice notice-' . ( $r['errors'] ? 'warning' : 'success' ) . '"><p>' . esc_html( sprintf( 'Optimised: %d, saved: %s, left: %d%s', $r['done'], size_format( $r['saved'] ), $r['left'], $r['errors'] ? ' · errors: ' . implode( '; ', $r['errors'] ) : '' ) ) . '</p></div>';
			$files = self::files();
			$todo  = self::todo();
			$total = array_sum( $files );
		}
		echo '<p>Theme photos + city pictures: ' . (int) count( $files ) . ', total ' . esc_html( size_format( $total ) ) . '. WebP supported by the server: <strong>' . ( self::webp_ok() ? 'yes' : 'no' ) . '</strong>. Originals are kept in uploads/tbs-img-originals/.</p>';
		if ( $todo ) {
			arsort( $todo );
			echo '<table class="widefat striped" style="max-width:640px"><thead><tr><th>File</th><th>Size</th></tr></thead><tbody>';
			foreach ( array_slice( $todo, 0, 30, true ) as $rel => $size ) {
				echo '<tr><td>' . esc_html( $rel ) . '</td><td>' . esc_html( size_format( $size ) ) . '</td></tr>';
			}
			echo '</tbody></table><form method="post">';
			wp_nonce_field( self::NONCE );
			echo '<p><button class="button button-primary" name="tbs_optimize" value="1">Optimise next 8 files</button> <span class="description">Repeat until nothing is left (≈ 1 min per click).</span></p></form>';
		} else {
			echo '<p><strong>All photos are optimised.</strong></p>';
		}
	}
}
