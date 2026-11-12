<?php
/**
 * DeepL API client (Free keys end with ":fx" and use api-free.deepl.com).
 * The key is kept in an option and never printed back.
 *
 * @package TaxiBaza\Languages
 */

namespace TaxiBaza\Languages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DeepL {

	public const OPTION     = 'tbl_deepl';
	public const LAST_ERROR = 'tbl_deepl_last_error';

	/** WPML code → DeepL target / source code. */
	public const TARGET = [ 'sk' => 'SK', 'uk' => 'UK', 'ru' => 'RU', 'en' => 'EN-GB', 'de' => 'DE', 'cs' => 'CS', 'pl' => 'PL', 'hu' => 'HU' ];
	public const SOURCE = [ 'sk' => 'SK', 'uk' => 'UK', 'ru' => 'RU', 'en' => 'EN', 'de' => 'DE', 'cs' => 'CS', 'pl' => 'PL', 'hu' => 'HU' ];

	/** For tests: replaces wp_remote_request(). fn( string $method, string $url, array $args ): array{code:int,body:array}. @var callable|null */
	public static $http = null;

	/** @return array{on:bool,key:string} */
	public static function settings(): array {
		$s = get_option( self::OPTION, [] );
		$s = is_array( $s ) ? $s : [];
		return [
			'on'  => ! empty( $s['on'] ),
			'key' => (string) ( $s['key'] ?? '' ),
		];
	}

	public static function enabled(): bool {
		$s = self::settings();
		return $s['on'] && '' !== $s['key'];
	}

	public static function base( string $key ): string {
		return str_ends_with( $key, ':fx' ) ? 'https://api-free.deepl.com/v2/' : 'https://api.deepl.com/v2/';
	}

	/**
	 * @param array<string,mixed>|null $body JSON body.
	 * @return array{code:int,body:array<string,mixed>}
	 */
	private static function call( string $method, string $path, ?array $body = null ): array {
		$key  = self::settings()['key'];
		$url  = self::base( $key ) . $path;
		$args = [
			'method'  => $method,
			'timeout' => 25,
			'headers' => [
				'Authorization' => 'DeepL-Auth-Key ' . $key,
				'Content-Type'  => 'application/json',
			],
		];
		if ( null !== $body ) {
			$args['body'] = (string) wp_json_encode( $body );
		}
		if ( self::$http ) {
			$res = (array) call_user_func( self::$http, $method, $url, $args );
		} else {
			$raw = wp_remote_request( $url, $args );
			if ( is_wp_error( $raw ) ) {
				update_option( self::LAST_ERROR, [ 'at' => time(), 'message' => 'network: ' . $raw->get_error_message() ], false );
				return [ 'code' => 0, 'body' => [] ];
			}
			$json = json_decode( (string) wp_remote_retrieve_body( $raw ), true );
			$res  = [ 'code' => (int) wp_remote_retrieve_response_code( $raw ), 'body' => is_array( $json ) ? $json : [] ];
		}
		$code = (int) ( $res['code'] ?? 0 );
		if ( $code < 200 || $code >= 300 ) {
			$msg = (string) ( $res['body']['message'] ?? '' );
			if ( 403 === $code ) {
				$msg = trim( $msg . ' (wrong API key?)' );
			} elseif ( 456 === $code ) {
				$msg = trim( $msg . ' (monthly character limit reached)' );
			}
			update_option( self::LAST_ERROR, [ 'at' => time(), 'message' => 'HTTP ' . $code . ( '' !== $msg ? ' · ' . substr( $msg, 0, 300 ) : '' ) ], false );
		}
		return [ 'code' => $code, 'body' => (array) ( $res['body'] ?? [] ) ];
	}

	/**
	 * Translate several texts at once. Empty texts stay empty and are not sent.
	 *
	 * @param array<string,string> $texts key => text.
	 * @return array<string,string>|null Same keys, or null when DeepL failed.
	 */
	public static function translate( array $texts, string $from, string $to ): ?array {
		$target = self::TARGET[ $to ] ?? '';
		if ( '' === $target ) {
			return null;
		}
		$send = array_filter( $texts, static fn( $t ) => '' !== trim( (string) $t ) );
		if ( ! $send ) {
			return array_map( 'strval', $texts );
		}
		$body = [
			'text'                => array_values( array_map( 'strval', $send ) ),
			'target_lang'         => $target,
			'preserve_formatting' => true,
		];
		if ( isset( self::SOURCE[ $from ] ) ) {
			$body['source_lang'] = self::SOURCE[ $from ];
		}
		$res = self::call( 'POST', 'translate', $body );
		$out = (array) ( $res['body']['translations'] ?? [] );
		if ( 200 !== $res['code'] || count( $out ) !== count( $send ) ) {
			return null;
		}
		$result = array_map( 'strval', $texts );
		$i      = 0;
		foreach ( array_keys( $send ) as $k ) {
			$result[ $k ] = (string) ( $out[ $i ]['text'] ?? '' );
			++$i;
		}
		return $result;
	}

	/** @return array{ok:bool,text:string} For the "check connection" button. */
	public static function usage(): array {
		if ( '' === self::settings()['key'] ) {
			return [ 'ok' => false, 'text' => 'API key is empty.' ];
		}
		$res = self::call( 'GET', 'usage' );
		if ( 200 !== $res['code'] ) {
			$e = get_option( self::LAST_ERROR );
			return [ 'ok' => false, 'text' => is_array( $e ) ? (string) $e['message'] : 'HTTP ' . $res['code'] ];
		}
		$used  = (int) ( $res['body']['character_count'] ?? 0 );
		$limit = (int) ( $res['body']['character_limit'] ?? 0 );
		return [ 'ok' => true, 'text' => sprintf( 'OK · %s / %s characters this month', number_format_i18n( $used ), $limit ? number_format_i18n( $limit ) : '∞' ) ];
	}
}
