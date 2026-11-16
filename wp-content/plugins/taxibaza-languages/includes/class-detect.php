<?php
/**
 * Language of a listing by its text (not by the interface language the owner
 * happened to use): Cyrillic → Ukrainian / Russian by their own letters,
 * Latin → Slovak / English by Slovak diacritics and common words.
 * Returns '' when the text is too short or unclear — then nothing changes.
 *
 * @package TaxiBaza\Languages
 */

namespace TaxiBaza\Languages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Detect {

	public const MIN_LETTERS = 20;

	private const SK_WORDS = [ 'a', 'je', 'na', 'v', 'vo', 's', 'so', 'pre', 'za', 'auto', 'vozidlo', 'týždeň', 'mesiac', 'nájom', 'prenájom', 'cena', 'záloha', 'ktorý', 'alebo', 'aj', 'sa', 'nie', 'áno', 'možné', 'potrebné', 'vodič', 'vodičov' ];
	private const EN_WORDS = [ 'the', 'and', 'with', 'for', 'is', 'are', 'car', 'week', 'month', 'rent', 'rental', 'price', 'deposit', 'driver', 'drivers', 'available', 'insurance', 'included', 'per', 'of', 'to', 'in', 'you', 'we' ];

	public static function lang( string $text ): string {
		$text = wp_strip_all_tags( $text );
		preg_match_all( '/\p{Cyrillic}/u', $text, $cyr );
		preg_match_all( '/\p{Latin}/u', $text, $lat );
		$c = count( $cyr[0] );
		$l = count( $lat[0] );
		if ( $c + $l < self::MIN_LETTERS ) {
			return '';
		}
		if ( $c > $l ) {
			$uk = preg_match_all( '/[іїєґІЇЄҐ]/u', $text );
			$ru = preg_match_all( '/[ыэъёЫЭЪЁ]/u', $text );
			if ( $uk === $ru ) {
				return '';
			}
			return $uk > $ru ? 'uk' : 'ru';
		}
		$sk    = preg_match_all( '/[áäčďéíĺľňóôŕšťúýžÁÄČĎÉÍĹĽŇÓÔŔŠŤÚÝŽ]/u', $text ) * 2;
		$en    = 0;
		$words = preg_split( '/[^\p{L}]+/u', mb_strtolower( $text ), -1, PREG_SPLIT_NO_EMPTY );
		foreach ( (array) $words as $w ) {
			if ( in_array( $w, self::SK_WORDS, true ) ) {
				++$sk;
			} elseif ( in_array( $w, self::EN_WORDS, true ) ) {
				++$en;
			}
		}
		if ( $sk === $en ) {
			return '';
		}
		return $sk > $en ? 'sk' : 'en';
	}

	/** Text of a listing that says which language it is written in. */
	public static function listing_text( int $post_id ): string {
		$parts = [];
		foreach ( Auto_Translate::FIELDS as $f ) {
			$parts[] = (string) get_post_meta( $post_id, $f, true );
		}
		return trim( implode( "\n", $parts ) );
	}
}
