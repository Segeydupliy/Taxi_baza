<?php
/**
 * Fleet profile — read-only view (shared by cabinet and /panel/).
 *
 * Expects: $tbf_p (profile array), $tbf_email (string), $tbf_edit_url (string, '' = no button).
 *
 * @package TaxiBaza\Fleets
 */

use TaxiBaza\Fleets\Profile;
use function TaxiBaza\Fleets\t;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tbf_p  = array_merge( Profile::defaults(), (array) ( $tbf_p ?? [] ) );
$v_list = static function ( array $values, string $prefix ) {
	return implode( ', ', array_map( static fn( $v ) => t( $prefix . $v ), $values ) );
};
$v_for  = static function ( array $for ) {
	$parts = [];
	foreach ( $for as $f ) {
		$parts[] = t( 'for_v_' . $f );
	}
	return $parts ? ' · ' . implode( ', ', $parts ) : '';
};
$money  = static fn( $v ) => rtrim( rtrim( number_format( (float) $v, 2, ',', ' ' ), '0' ), ',' );
?>

	<?php
	$p    = $tbf_p;
	$rows = static function ( array $pairs ) {
		$out = '';
		foreach ( $pairs as [ $dt, $dd ] ) {
			if ( '' === trim( (string) $dd ) ) {
				continue;
			}
			$out .= '<div class="tbf-dl__row"><dt>' . esc_html( $dt ) . '</dt><dd>' . esc_html( $dd ) . '</dd></div>';
		}
		return $out;
	};
	$plat_names = [ 'uber' => 'Uber', 'bolt' => 'Bolt', 'hopin' => 'Hopin' ];
	$models     = (array) $p['models'];
	$logo_view  = ! empty( $p['logo_id'] ) ? wp_get_attachment_image_url( (int) $p['logo_id'], 'thumbnail' ) : '';
	?>
	<div class="tbf-grid">
		<aside class="tbf-card">
			<h2 class="tbf-card__title"><?php echo esc_html( t( 'profile_logo_title' ) ); ?></h2>
			<div class="tbf-logo"><div class="tbf-logo__pic"><?php if ( $logo_view ) : ?><img src="<?php echo esc_url( $logo_view ); ?>" alt=""><?php else : ?><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9a9a9a" stroke-width="1.5" aria-hidden="true"><path d="M4 8h3l1.5-2h7L17 8h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg><?php endif; ?></div></div>
		</aside>
		<div class="tbf-main">
			<section class="tbf-card tbf-card--has-actions">
				<div class="tbf-card__head"><h2 class="tbf-card__title"><?php echo esc_html( t( 'sec_main' ) ); ?></h2>
					<?php if ( ! empty( $tbf_edit_url ) ) : ?><div class="tbf-card__actions"><a class="tbf-btn tbf-btn--dark tbf-btn--sm" href="<?php echo esc_url( $tbf_edit_url ); ?>"><?php echo esc_html( t( 'edit' ) ); ?></a></div><?php endif; ?>
				</div>
				<dl class="tbf-dl">
					<?php
					echo $rows( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
						[
							[ t( 'f_name' ), $p['name'] ],
							[ t( 'f_email' ), (string) ( $tbf_email ?? '' ) ],
							[ t( 'f_phone' ), $p['phone'] ],
							[ t( 'f_type' ), t( 'type_fleet' ) ],
							[ t( 'f_ico' ), $p['ico'] ],
							[ t( 'f_platforms' ), implode( ', ', array_map( static fn( $x ) => $plat_names[ $x ] ?? $x, (array) $p['platforms'] ) ) ],
							[ t( 'f_telegram' ), $p['telegram'] ],
							[ t( 'f_whatsapp' ), $p['whatsapp'] ],
							[ t( 'f_langs' ), $v_list( (array) $p['langs'], 'lang_' ) ],
						]
					);
					?>
					<?php if ( '' !== trim( (string) $p['description'] ) ) : ?>
						<div class="tbf-dl__row"><dt><?php echo esc_html( t( 'f_desc' ) ); ?></dt><dd><div class="tbf-clamp" id="tbf-desc"><?php echo nl2br( esc_html( $p['description'] ) ); ?></div><button type="button" class="tbf-more" id="tbf-more" aria-expanded="false"><?php echo esc_html( t( 'read_more' ) ); ?></button></dd></div>
					<?php endif; ?>
				</dl>
			</section>

			<section class="tbf-card"><h2 class="tbf-card__title"><?php echo esc_html( t( 'sec_models' ) ); ?></h2><dl class="tbf-dl">
				<?php
				$offer = 2 === count( $models ) ? t( 'models_both' ) : ( in_array( 'own', $models, true ) ? t( 'model_own' ) : t( 'model_fleet' ) );
				echo $rows( [ [ t( 'models_offer' ), $models ? $offer : '' ] ] ); // phpcs:ignore
				?>
			</dl></section>

			<?php if ( in_array( 'own', $models, true ) ) : ?>
			<section class="tbf-card"><h2 class="tbf-card__title"><?php echo esc_html( t( 'sec_own' ) ); ?></h2><dl class="tbf-dl">
				<?php
				$is_fixed = 'fixed' === $p['own_pay_type'];
				echo $rows( // phpcs:ignore
					[
						[ t( 'own_pay_type_view' ), t( 'opt_pay_' . $p['own_pay_type'] ) ],
						[ t( 'own_fee_view' ), '' === $p['own_fee'] ? '' : $money( $p['own_fee'] ) . ( $is_fixed ? ' €' : ' %' ) ],
						[ t( 'own_fee_base' ), 'percent' === $p['own_pay_type'] ? t( 'opt_base_' . $p['own_fee_base'] ) : '' ],
					]
				);
				?>
			</dl></section>
			<?php endif; ?>

			<section class="tbf-card"><h2 class="tbf-card__title"><?php echo esc_html( t( 'sec_pay' ) ); ?></h2><dl class="tbf-dl">
				<?php
				$freq_key = [ 'weekly' => 'opt_weekly', 'daily' => 'opt_daily', 'biweekly' => 'opt_biweekly', 'monthly' => 'opt_monthly' ][ $p['pay_freq'] ];
				$freq     = t( $freq_key ) . ( in_array( $p['pay_freq'], [ 'weekly', 'biweekly' ], true ) ? ', ' . mb_strtolower( t( 'day_' . $p['pay_day'] ) ) : '' );
				echo $rows( [ [ t( 'pay_freq' ), $freq ], [ t( 'pay_method' ), $v_list( (array) $p['pay_method'], 'pm_' ) ] ] ); // phpcs:ignore
				?>
			</dl></section>

			<?php if ( $p['perk_fuel'] || $p['perk_service'] ) : ?>
			<section class="tbf-card"><h2 class="tbf-card__title"><?php echo esc_html( t( 'sec_perks' ) ); ?></h2><dl class="tbf-dl">
				<?php
				$perks = [];
				if ( $p['perk_fuel'] ) {
					$amount  = '' === (string) $p['fuel_discount'] ? '' : ( 'cents' === $p['fuel_unit'] ? $money( $p['fuel_discount'] ) . ' ' . t( 'cents_per_l' ) : $money( $p['fuel_discount'] ) . ' %' ) . ' ';
					$perks[] = [ t( 'perk_fuel' ), t( 'discount' ) . ' ' . $amount . t( 'at' ) . ' ' . ( '' !== $p['fuel_network'] ? $p['fuel_network'] : t( 'fuel_partner' ) ) . $v_for( (array) $p['fuel_for'] ) ];
				}
				if ( $p['perk_service'] ) {
					$perks[] = [ t( 'perk_service' ), ( '' === (string) $p['svc_discount'] ? '' : '−' . $money( $p['svc_discount'] ) . ' % ' ) . mb_strtolower( $v_list( (array) $p['svc_what'], 'svc_' ) ) . ' — ' . mb_strtolower( t( 'partner' === $p['svc_where'] ? 'opt_partner_sto' : 'opt_own_sto' ) ) . $v_for( (array) $p['svc_for'] ) ];
				}
				echo $rows( $perks ); // phpcs:ignore
				?>
			</dl></section>
			<?php endif; ?>

			<section class="tbf-card"><h2 class="tbf-card__title"><?php echo esc_html( t( 'sec_req' ) ); ?></h2><dl class="tbf-dl">
				<?php
				$exp = '0' === $p['req_exp'] ? t( 'exp_0' ) : sprintf( t( 'exp_from' ), (int) $p['req_exp'] );
				echo $rows( [ [ t( 'req_age' ), $p['req_age'] ], [ t( 'req_exp' ), $exp ], [ t( 'req_other' ), (string) ( $p['req_other'] ?? '' ) ] ] ); // phpcs:ignore
				?>
			</dl></section>
		</div>
	</div>
