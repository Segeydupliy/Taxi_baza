<?php
/**
 * Texts of the public fleet page built from the approved profile
 * (Figma: Flotilla 909:11298 — "Умови співпраці", "Вимоги до водія").
 * Every block that has no data is left out.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Fleet_View {

	/**
	 * @param array<int,string> $values Choice values.
	 */
	private static function list( array $values, string $prefix ): string {
		return implode( ', ', array_map( static fn( $v ) => t( $prefix . $v ), $values ) );
	}

	/** Lower-case the first letter for use mid-sentence ("Партнерське СТО" → "партнерське СТО"; "ID…" stays). */
	private static function lc( string $s ): string {
		$two = mb_substr( $s, 0, 2 );
		if ( mb_strlen( $two ) === 2 && mb_strtoupper( $two ) === $two && mb_strtolower( $two ) !== $two ) {
			return $s; // Acronym.
		}
		return mb_strtolower( mb_substr( $s, 0, 1 ) ) . mb_substr( $s, 1 );
	}

	/** "A, b, c": items after the first start lower-case. */
	private static function sentence_list( array $values, string $prefix ): string {
		$items = array_map( static fn( $v ) => t( $prefix . $v ), $values );
		foreach ( $items as $i => $item ) {
			$items[ $i ] = $i ? self::lc( $item ) : $item;
		}
		return implode( ', ', $items );
	}

	/** Lower-case a day or language name — not in English, where they are capitalised. */
	private static function lc_name( string $s ): string {
		return 'en' === I18n::lang() ? $s : mb_strtolower( $s );
	}

	private static function ucfirst( string $s ): string {
		return mb_strtoupper( mb_substr( $s, 0, 1 ) ) . mb_substr( $s, 1 );
	}

	/** "Щотижня, понеділок, на банківський рахунок." */
	public static function payouts( array $p ): string {
		$freq = (string) ( $p['pay_freq'] ?? '' );
		if ( ! in_array( $freq, Profile::CHOICES['pay_freq'], true ) ) {
			return '';
		}
		$parts = [ t( 'opt_' . $freq ) ];
		if ( in_array( $freq, [ 'weekly', 'biweekly' ], true ) && in_array( (string) ( $p['pay_day'] ?? '' ), Profile::CHOICES['pay_day'], true ) ) {
			$parts[] = self::lc_name( t( 'day_' . $p['pay_day'] ) );
		}
		$methods = array_values( array_intersect( Profile::CHOICES['pay_method'], (array) ( $p['pay_method'] ?? [] ) ) );
		if ( $methods ) {
			$parts[] = self::lc( self::sentence_list( $methods, 'pm_' ) );
		}
		return implode( ', ', $parts ) . '.';
	}

	/**
	 * Perks that apply to one model ('own' | 'fleet').
	 *
	 * @param array<string,mixed> $p Profile.
	 * @return array<int,array{icon:string,title:string,text:string}>
	 */
	public static function perks_for( array $p, string $model ): array {
		$out = [];
		if ( ! empty( $p['perk_fuel'] ) && in_array( $model, (array) $p['fuel_for'], true ) && '' !== (string) $p['fuel_discount'] ) {
			$amount = 'cents' === $p['fuel_unit'] ? Directory::money( $p['fuel_discount'] ) . ' ' . t( 'cents_per_l' ) : Directory::money( $p['fuel_discount'] ) . ' %';
			$where  = '' !== (string) $p['fuel_network'] ? (string) $p['fuel_network'] : t( 'fuel_partner' );
			$out[]  = [ 'icon' => 'card', 'title' => t( 'perk_fuel' ), 'text' => t( 'discount' ) . ' ' . $amount . ' ' . t( 'at' ) . ' ' . $where . '.' ];
		}
		if ( ! empty( $p['perk_service'] ) && in_array( $model, (array) $p['svc_for'], true ) && '' !== (string) $p['svc_discount'] ) {
			$what  = self::lc( self::sentence_list( array_values( array_intersect( Profile::CHOICES['svc_what'], (array) $p['svc_what'] ) ), 'svc_' ) );
			$where = self::lc( t( 'partner' === $p['svc_where'] ? 'opt_partner_sto' : 'opt_own_sto' ) );
			$out[] = [ 'icon' => 'build', 'title' => t( 'perk_service' ), 'text' => '−' . Directory::money( $p['svc_discount'] ) . ' %' . ( '' !== $what ? ' ' . $what : '' ) . ' — ' . $where . '.' ];
		}
		if ( ! empty( $p['perk_concession'] ) && in_array( $model, (array) $p['con_for'], true ) ) {
			$out[] = [ 'icon' => 'doc', 'title' => t( 'perk_con' ), 'text' => t( 'help' === $p['con_what'] ? 'opt_con_help' : 'opt_con_do' ) . ', ' . self::lc( t( 'opt_' . $p['con_cost'] ) ) . '.' ];
		}
		return $out;
	}

	/**
	 * Columns of "Умови співпраці": 'own' and/or 'fleet'.
	 *
	 * @param array<string,mixed> $p Profile.
	 * @return array<string,array{kicker:string,value:string,value_text:string,perks:array<int,array{icon:string,title:string,text:string}>}>
	 */
	public static function terms( array $p ): array {
		$p      = array_merge( Profile::defaults(), $p );
		$models = (array) $p['models'];
		$cols   = [];
		$pay    = self::payouts( $p );
		$pay_in = in_array( 'own', $models, true ) ? 'own' : 'fleet';

		if ( in_array( 'own', $models, true ) ) {
			$fixed = 'fixed' === $p['own_pay_type'];
			$net   = 'after' === $p['own_pay_type'] || 'net' === $p['own_fee_base'];
			$perks = [];
			if ( '' !== $pay && 'own' === $pay_in ) {
				$perks[] = [ 'icon' => 'wallet', 'title' => t( 'fl_payouts' ), 'text' => $pay ];
			}
			$cols['own'] = [
				'kicker'     => t( 'fl_col_own' ),
				'value'      => '' === (string) $p['own_fee'] ? '' : Directory::money( $p['own_fee'] ) . ( $fixed ? ' €' : ' %' ),
				'value_text' => $fixed ? t( 'fl_metric_fixed' ) : t( $net ? 'fl_metric_net' : 'fl_metric_gross' ),
				'perks'      => array_merge( $perks, self::perks_for( $p, 'own' ) ),
			];
		}

		if ( in_array( 'fleet', $models, true ) ) {
			$perks = [];
			if ( '' !== $pay && 'fleet' === $pay_in ) {
				$perks[] = [ 'icon' => 'wallet', 'title' => t( 'fl_payouts' ), 'text' => $pay ];
			}
			if ( $p['rent_comm_on'] && '' !== (string) $p['rent_comm'] ) {
				$perks[] = [ 'icon' => 'percent', 'title' => t( 'fl_rent_comm' ), 'text' => sprintf( t( 'fl_rent_comm_d' ), Directory::money( $p['rent_comm'] ) . ' %' ) ];
			}
			if ( $p['rent_dep_on'] && '' !== (string) $p['rent_dep'] ) {
				$ret     = [ 'handover' => 'opt_ret_handover', '7' => 'opt_ret_7', '14' => 'opt_ret_14' ][ $p['rent_dep_return'] ] ?? 'opt_ret_handover';
				$perks[] = [ 'icon' => 'payments', 'title' => t( 'fl_deposit' ), 'text' => Directory::money( $p['rent_dep'] ) . ' €, ' . self::lc( t( $ret ) ) . '.' ];
			}
			$inc = array_values( array_intersect( Profile::CHOICES['inc'], (array) $p['inc'] ) );
			if ( $inc ) {
				$perks[] = [ 'icon' => 'shield', 'title' => t( 'fl_included' ), 'text' => self::ucfirst( self::list( $inc, 'inc_v_' ) ) . '.' ];
			}
			if ( '' !== (string) $p['rent_min'] ) {
				$unit    = [ 'days' => 'opt_days', 'weeks' => 'opt_weeks', 'months' => 'opt_months' ][ $p['rent_min_unit'] ] ?? 'opt_days';
				$ext     = [ 'weekly' => 'opt_weekly', 'daily' => 'opt_daily', 'monthly' => 'opt_monthly' ][ $p['rent_ext'] ] ?? 'opt_weekly';
				$perks[] = [ 'icon' => 'calendar', 'title' => t( 'fl_min_term' ), 'text' => sprintf( '%s %s, %s %s.', Directory::money( $p['rent_min'] ), t( $unit ), t( 'then' ), self::lc( t( $ext ) ) ) ];
			}
			$cols['fleet'] = [
				'kicker'     => t( 'fl_col_fleet' ),
				'value'      => '' === (string) $p['rent_price'] ? '' : t( 'from' ) . ' ' . Directory::money( $p['rent_price'] ) . ' €',
				'value_text' => t( 'day' === $p['rent_period'] ? 'fl_metric_rent_day' : 'fl_metric_rent_week' ),
				'perks'      => array_merge( $perks, self::perks_for( $p, 'fleet' ) ),
			];
		}
		return $cols;
	}

	/**
	 * "Вимоги до водія".
	 *
	 * @param array<string,mixed> $p Profile.
	 * @return array<int,array{icon:string,title:string,text:string}>
	 */
	public static function requirements( array $p ): array {
		$p   = array_merge( Profile::defaults(), $p );
		$out = [];
		if ( '' !== (string) $p['req_age'] ) {
			$out[] = [ 'icon' => 'person', 'title' => t( 'req_age' ), 'text' => I18n::plural( (int) $p['req_age'], 'fl_years' ) ];
		}
		if ( '' !== (string) $p['req_exp'] ) {
			$exp   = (int) $p['req_exp'];
			$out[] = [ 'icon' => 'badge', 'title' => t( 'req_exp' ), 'text' => 0 === $exp ? t( 'exp_0' ) : I18n::plural( $exp, 'fl_exp' ) ];
		}
		$docs = array_values( array_intersect( Profile::CHOICES['docs'], (array) $p['docs'] ) );
		if ( $docs ) {
			$out[] = [ 'icon' => 'doc', 'title' => t( 'fl_docs' ), 'text' => self::sentence_list( $docs, 'doc_' ) ];
		}
		$langs = array_values( array_intersect( Profile::CHOICES['langs'], (array) $p['langs'] ) );
		if ( $langs ) {
			$out[] = [ 'icon' => 'translate', 'title' => t( 'fl_langs' ), 'text' => self::ucfirst( self::lc_name( self::list( $langs, 'lang_' ) ) ) ];
		}
		return $out;
	}
}
