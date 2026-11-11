<?php
/**
 * Fleet profile edit form. Used by the fleet's cabinet and by the admin panel
 * (/panel/?tab=fleets&fleet=ID&edit=1).
 *
 * Expects: $tbf_form (values), $tbf_errors (field => error key), $tbf_cancel_url
 * ('' = no cancel button), $tbf_admin_fleet (fleet ID when an admin edits, else 0).
 *
 * @package TaxiBaza\Fleets
 */

use TaxiBaza\Fleets\Profile;
use function TaxiBaza\Fleets\t;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- small render helpers ---------- */
$e_attr = static fn( $v ) => esc_attr( (string) $v );
$has    = static fn( string $k, string $v ) => in_array( $v, (array) ( $tbf_form[ $k ] ?? [] ), true );
$err    = static function ( string $k ) use ( $tbf_errors ) {
	return isset( $tbf_errors[ $k ] ) ? '<p class="tbf-error">' . esc_html( t( $tbf_errors[ $k ] ) ) . '</p>' : '';
};
$fcls   = static fn( string $k ) => 'tbf-field' . ( isset( $tbf_errors[ $k ] ) ? ' tbf-field--invalid' : '' );
$req    = '<span class="tbf-req" aria-hidden="true">*</span>';
$opts   = static function ( string $name, array $map ) use ( $tbf_form ) {
	$out = '';
	foreach ( $map as $val => $label ) {
		$out .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( (string) $val ), selected( (string) ( $tbf_form[ $name ] ?? '' ), (string) $val, false ), esc_html( $label ) );
	}
	return $out;
};
$checks = static function ( string $name, array $map, string $type = 'check' ) use ( $has ) {
	$out = '';
	foreach ( $map as $val => $label ) {
		$on = $has( $name, (string) $val );
		if ( 'switch' === $type ) {
			$out .= sprintf( '<label class="tbf-switch tbf-switch--center"><input type="checkbox" name="%1$s[]" value="%2$s"%3$s><span class="tbf-switch__ui"></span><span class="tbf-switch__name">%4$s</span></label>', esc_attr( $name ), esc_attr( (string) $val ), checked( $on, true, false ), esc_html( $label ) );
		} else {
			$out .= sprintf( '<label class="tbf-check"><input type="checkbox" name="%1$s[]" value="%2$s"%3$s><span class="tbf-check__box"></span>%4$s</label>', esc_attr( $name ), esc_attr( (string) $val ), checked( $on, true, false ), esc_html( $label ) );
		}
	}
	return $out;
};
$switch = static function ( string $name, string $id, string $label, string $hint = '', bool $right = false ) use ( $tbf_form ) {
	return sprintf(
		'<label class="tbf-switch%5$s"><input type="checkbox" id="%2$s" name="%1$s" value="1"%3$s><span class="tbf-switch__ui"></span><span class="tbf-switch__text"><span class="tbf-switch__name">%4$s</span>%6$s</span></label>',
		esc_attr( $name ),
		esc_attr( $id ),
		checked( ! empty( $tbf_form[ $name ] ), true, false ),
		esc_html( $label ),
		$right ? ' tbf-switch--right tbf-switch--center' : ( $hint ? '' : ' tbf-switch--center' ),
		$hint ? '<span class="tbf-switch__hint">' . esc_html( $hint ) . '</span>' : ''
	);
};
$for_map = [ 'own' => t( 'for_own' ), 'fleet' => t( 'for_fleet' ) ];
$logo_url = ! empty( $tbf_form['logo_id'] ) ? wp_get_attachment_image_url( (int) $tbf_form['logo_id'], 'thumbnail' ) : '';

?>
	<form class="tbf-grid" id="tbf-profile" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" novalidate>
		<input type="hidden" name="action" value="tbf_save_profile">
		<?php if ( ! empty( $tbf_admin_fleet ) ) : ?><input type="hidden" name="tbf_admin_fleet" value="<?php echo (int) $tbf_admin_fleet; ?>"><?php endif; ?>
		<input type="hidden" name="tbf_lang" value="<?php echo esc_attr( \TaxiBaza\Fleets\I18n::lang() ); ?>">
		<?php wp_nonce_field( Profile::NONCE ); ?>
		<input type="hidden" name="logo_id" value="<?php echo $e_attr( (int) $tbf_form['logo_id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
		<input type="hidden" name="logo_delete" id="tbf-logo-delete" value="">

		<aside class="tbf-card">
			<h2 class="tbf-card__title"><?php echo esc_html( t( 'profile_logo_title' ) ); ?></h2>
			<div class="tbf-logo">
				<div class="tbf-logo__pic" id="tbf-logo-pic" data-empty="<?php echo $logo_url ? '0' : '1'; ?>">
					<?php if ( $logo_url ) : ?><img src="<?php echo esc_url( $logo_url ); ?>" alt=""><?php else : ?><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9a9a9a" stroke-width="1.5" aria-hidden="true"><path d="M4 8h3l1.5-2h7L17 8h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg><?php endif; ?>
				</div>
				<div class="tbf-logo__actions">
					<label class="tbf-btn tbf-btn--dark" style="cursor:pointer"><?php echo esc_html( t( 'upload_new' ) ); ?><input type="file" name="logo" id="tbf-logo-file" accept="image/jpeg,image/png,image/webp" hidden></label>
					<button type="button" class="tbf-link" id="tbf-logo-del"><?php echo esc_html( t( 'delete' ) ); ?></button>
				</div>
			</div>
		</aside>

		<div class="tbf-main">
			<p class="tbf-card__sub"><span class="tbf-req">*</span> <?php echo esc_html( t( 'required_note' ) ); ?></p>

			<section class="tbf-card">
				<h2 class="tbf-card__title"><?php echo esc_html( t( 'sec_main' ) ); ?></h2>
				<div class="tbf-card__body">
					<div class="<?php echo esc_attr( $fcls( 'name' ) ); ?>"><label class="tbf-label" for="f-name"><?php echo esc_html( t( 'f_name' ) ) . $req; // phpcs:ignore ?></label><input class="tbf-input" id="f-name" name="name" value="<?php echo $e_attr( $tbf_form['name'] ); // phpcs:ignore ?>" required maxlength="120"><?php echo $err( 'name' ); // phpcs:ignore ?></div>
					<div class="tbf-field"><label class="tbf-label" for="f-email"><?php echo esc_html( t( 'f_email' ) ); ?></label><input class="tbf-input" id="f-email" value="<?php echo $e_attr( $tbf_user->user_email ); // phpcs:ignore ?>" readonly></div>
					<div class="<?php echo esc_attr( $fcls( 'phone' ) ); ?>"><label class="tbf-label" for="f-phone"><?php echo esc_html( t( 'f_phone' ) ); ?></label><input class="tbf-input" id="f-phone" name="phone" inputmode="tel" placeholder="+421 XXX XXX XXX" value="<?php echo $e_attr( $tbf_form['phone'] ); // phpcs:ignore ?>"><?php echo $err( 'phone' ); // phpcs:ignore ?></div>
					<div class="tbf-field"><label class="tbf-label" for="f-type"><?php echo esc_html( t( 'f_type' ) ); ?></label><select class="tbf-select" id="f-type" disabled><option><?php echo esc_html( t( 'type_fleet' ) ); ?></option></select></div>
					<div class="tbf-field"><label class="tbf-label" for="f-ico"><?php echo esc_html( t( 'f_ico' ) ); ?></label><input class="tbf-input" id="f-ico" name="ico" inputmode="numeric" value="<?php echo $e_attr( $tbf_form['ico'] ); // phpcs:ignore ?>"></div>
					<div class="<?php echo esc_attr( $fcls( 'platforms' ) ); ?>" data-group="platforms"><span class="tbf-label"><?php echo esc_html( t( 'f_platforms' ) ) . $req; // phpcs:ignore ?></span><div class="tbf-checks"><?php echo $checks( 'platforms', [ 'uber' => 'Uber', 'bolt' => 'Bolt', 'hopin' => 'Hopin' ], 'switch' ); // phpcs:ignore ?></div><?php echo $err( 'platforms' ); // phpcs:ignore ?></div>
					<div class="tbf-field"><label class="tbf-label" for="f-tg"><?php echo esc_html( t( 'f_telegram' ) ); ?></label><input class="tbf-input" id="f-tg" name="telegram" placeholder="<?php echo esc_attr( t( 'f_telegram_ph' ) ); ?>" value="<?php echo $e_attr( $tbf_form['telegram'] ); // phpcs:ignore ?>"></div>
					<div class="tbf-field"><label class="tbf-label" for="f-wa"><?php echo esc_html( t( 'f_whatsapp' ) ); ?></label><input class="tbf-input" id="f-wa" name="whatsapp" value="<?php echo $e_attr( $tbf_form['whatsapp'] ); // phpcs:ignore ?>"></div>
					<div class="tbf-field"><label class="tbf-label" for="f-desc"><?php echo esc_html( t( 'f_desc' ) ); ?></label><textarea class="tbf-textarea" id="f-desc" name="description" maxlength="2000"><?php echo esc_textarea( $tbf_form['description'] ); ?></textarea></div>
					<div class="<?php echo esc_attr( $fcls( 'langs' ) ); ?>" data-group="langs"><span class="tbf-label"><?php echo esc_html( t( 'f_langs' ) ) . $req; // phpcs:ignore ?></span><div class="tbf-checks"><?php echo $checks( 'langs', [ 'sk' => t( 'lang_sk' ), 'uk' => t( 'lang_uk' ), 'cs' => t( 'lang_cs' ), 'en' => t( 'lang_en' ), 'de' => t( 'lang_de' ), 'ru' => t( 'lang_ru' ) ] ); // phpcs:ignore ?></div><?php echo $err( 'langs' ); // phpcs:ignore ?></div>
				</div>
			</section>

			<section class="tbf-card">
				<h2 class="tbf-card__title"><?php echo esc_html( t( 'sec_models' ) ); ?></h2>
				<p class="tbf-card__sub"><?php echo esc_html( t( 'sec_models_sub' ) ); ?></p>
				<div class="tbf-card__body <?php echo esc_attr( $fcls( 'models' ) ); ?>" data-group="models">
					<label class="tbf-switch"><input type="checkbox" name="models[]" value="own" id="m-own"<?php checked( $has( 'models', 'own' ) ); ?>><span class="tbf-switch__ui"></span><span class="tbf-switch__text"><span class="tbf-switch__name"><?php echo esc_html( t( 'model_own' ) ); ?></span><span class="tbf-switch__hint"><?php echo esc_html( t( 'model_own_hint' ) ); ?></span></span></label>
					<label class="tbf-switch"><input type="checkbox" name="models[]" value="fleet" id="m-fleet"<?php checked( $has( 'models', 'fleet' ) ); ?>><span class="tbf-switch__ui"></span><span class="tbf-switch__text"><span class="tbf-switch__name"><?php echo esc_html( t( 'model_fleet' ) ); ?></span><span class="tbf-switch__hint"><?php echo esc_html( t( 'model_fleet_hint' ) ); ?></span></span></label>
					<?php echo $err( 'models' ); // phpcs:ignore ?>
				</div>
			</section>

			<section class="tbf-card" data-show-if="m-own">
				<h2 class="tbf-card__title"><?php echo esc_html( t( 'sec_own' ) ); ?></h2>
				<p class="tbf-card__sub"><?php echo esc_html( t( 'sec_own_sub' ) ); ?></p>
				<div class="tbf-card__body">
					<div class="tbf-row">
						<div class="tbf-field"><label class="tbf-label" for="o-pay"><?php echo esc_html( t( 'own_pay_type' ) ) . $req; // phpcs:ignore ?></label><select class="tbf-select" id="o-pay" name="own_pay_type"><?php echo $opts( 'own_pay_type', [ 'percent' => t( 'opt_pay_percent' ), 'fixed' => t( 'opt_pay_fixed' ), 'after' => t( 'opt_pay_after' ) ] ); // phpcs:ignore ?></select></div>
						<div class="<?php echo esc_attr( $fcls( 'own_fee' ) ); ?>"><label class="tbf-label" for="o-fee" id="o-fee-l"><?php echo esc_html( t( 'own_fee' ) ) . $req; // phpcs:ignore ?></label><input class="tbf-input" id="o-fee" name="own_fee" inputmode="decimal" value="<?php echo $e_attr( $tbf_form['own_fee'] ); // phpcs:ignore ?>" data-req="1"><?php echo $err( 'own_fee' ); // phpcs:ignore ?></div>
					</div>
					<div class="tbf-field" id="o-base-f"><label class="tbf-label" for="o-base"><?php echo esc_html( t( 'own_fee_base' ) ); ?></label><select class="tbf-select" id="o-base" name="own_fee_base"><?php echo $opts( 'own_fee_base', [ 'gross' => t( 'opt_base_gross' ), 'net' => t( 'opt_base_net' ) ] ); // phpcs:ignore ?></select></div>
				</div>
			</section>

			<section class="tbf-card">
				<h2 class="tbf-card__title"><?php echo esc_html( t( 'sec_pay' ) ); ?></h2>
				<p class="tbf-card__sub"><?php echo esc_html( t( 'sec_pay_sub' ) ); ?></p>
				<div class="tbf-card__body">
					<div class="tbf-row">
						<div class="tbf-field"><label class="tbf-label" for="p-freq"><?php echo esc_html( t( 'pay_freq' ) ) . $req; // phpcs:ignore ?></label><select class="tbf-select" id="p-freq" name="pay_freq"><?php echo $opts( 'pay_freq', [ 'daily' => t( 'opt_daily' ), 'weekly' => t( 'opt_weekly' ), 'biweekly' => t( 'opt_biweekly' ), 'monthly' => t( 'opt_monthly' ) ] ); // phpcs:ignore ?></select></div>
						<div class="tbf-field" id="p-day-f"><label class="tbf-label" for="p-day"><?php echo esc_html( t( 'pay_day' ) ); ?></label><select class="tbf-select" id="p-day" name="pay_day"><?php echo $opts( 'pay_day', [ 'mon' => t( 'day_mon' ), 'tue' => t( 'day_tue' ), 'wed' => t( 'day_wed' ), 'thu' => t( 'day_thu' ), 'fri' => t( 'day_fri' ), 'sat' => t( 'day_sat' ), 'sun' => t( 'day_sun' ) ] ); // phpcs:ignore ?></select></div>
					</div>
					<div class="<?php echo esc_attr( $fcls( 'pay_method' ) ); ?>" data-group="paymethod"><span class="tbf-label"><?php echo esc_html( t( 'pay_method' ) ) . $req; // phpcs:ignore ?></span><div class="tbf-checks"><?php echo $checks( 'pay_method', [ 'bank' => t( 'pm_bank' ), 'cash' => t( 'pm_cash' ) ] ); // phpcs:ignore ?></div><?php echo $err( 'pay_method' ); // phpcs:ignore ?></div>
				</div>
			</section>

			<section class="tbf-card">
				<h2 class="tbf-card__title"><?php echo esc_html( t( 'sec_perks' ) ); ?></h2>
				<p class="tbf-card__sub"><?php echo esc_html( t( 'sec_perks_sub' ) ); ?></p>
				<div class="tbf-card__body">
					<div class="tbf-perk">
						<?php echo $switch( 'perk_fuel', 'k-fuel', t( 'perk_fuel' ), t( 'perk_fuel_hint' ), true ); // phpcs:ignore ?>
						<div class="tbf-perk__fields" data-show-if="k-fuel">
							<div class="tbf-row">
								<div class="<?php echo esc_attr( $fcls( 'fuel_discount' ) ); ?>"><label class="tbf-label" for="k-fuel-v"><?php echo esc_html( t( 'discount' ) ); ?></label><input class="tbf-input" id="k-fuel-v" name="fuel_discount" inputmode="decimal" value="<?php echo $e_attr( $tbf_form['fuel_discount'] ); // phpcs:ignore ?>"><?php echo $err( 'fuel_discount' ); // phpcs:ignore ?></div>
								<div class="tbf-field"><label class="tbf-label" for="k-fuel-u"><?php echo esc_html( t( 'unit' ) ); ?></label><select class="tbf-select" id="k-fuel-u" name="fuel_unit"><?php echo $opts( 'fuel_unit', [ 'cents' => t( 'opt_cents' ), 'percent' => t( 'opt_percent' ) ] ); // phpcs:ignore ?></select></div>
							</div>
							<div class="tbf-field"><label class="tbf-label" for="k-fuel-n"><?php echo esc_html( t( 'fuel_network' ) ); ?></label><input class="tbf-input" id="k-fuel-n" name="fuel_network" placeholder="<?php echo esc_attr( t( 'fuel_network_ph' ) ); ?>" value="<?php echo $e_attr( $tbf_form['fuel_network'] ); // phpcs:ignore ?>"></div>
							<div class="tbf-field"><span class="tbf-label"><?php echo esc_html( t( 'for_whom' ) ); ?></span><div class="tbf-checks"><?php echo $checks( 'fuel_for', $for_map ); // phpcs:ignore ?></div></div>
						</div>
					</div>
					<div class="tbf-perk">
						<?php echo $switch( 'perk_service', 'k-svc', t( 'perk_service' ), t( 'perk_service_hint' ), true ); // phpcs:ignore ?>
						<div class="tbf-perk__fields" data-show-if="k-svc">
							<div class="tbf-row">
								<div class="<?php echo esc_attr( $fcls( 'svc_discount' ) ); ?>"><label class="tbf-label" for="k-svc-v"><?php echo esc_html( t( 'discount_pct' ) ); ?></label><input class="tbf-input" id="k-svc-v" name="svc_discount" inputmode="decimal" value="<?php echo $e_attr( $tbf_form['svc_discount'] ); // phpcs:ignore ?>"><?php echo $err( 'svc_discount' ); // phpcs:ignore ?></div>
								<div class="tbf-field"><label class="tbf-label" for="k-svc-w"><?php echo esc_html( t( 'svc_where' ) ); ?></label><select class="tbf-select" id="k-svc-w" name="svc_where"><?php echo $opts( 'svc_where', [ 'partner' => t( 'opt_partner_sto' ), 'own' => t( 'opt_own_sto' ) ] ); // phpcs:ignore ?></select></div>
							</div>
							<div class="<?php echo esc_attr( $fcls( 'svc_what' ) ); ?>"><span class="tbf-label"><?php echo esc_html( t( 'svc_what' ) ); ?></span><div class="tbf-checks"><?php echo $checks( 'svc_what', [ 'service' => t( 'svc_service' ), 'tires' => t( 'svc_tires' ), 'parts' => t( 'svc_parts' ), 'wash' => t( 'svc_wash' ) ] ); // phpcs:ignore ?></div><?php echo $err( 'svc_what' ); // phpcs:ignore ?></div>
							<div class="tbf-field"><span class="tbf-label"><?php echo esc_html( t( 'for_whom' ) ); ?></span><div class="tbf-checks"><?php echo $checks( 'svc_for', $for_map ); // phpcs:ignore ?></div></div>
						</div>
					</div>
				</div>
			</section>

			<section class="tbf-card">
				<h2 class="tbf-card__title"><?php echo esc_html( t( 'sec_req' ) ); ?></h2>
				<p class="tbf-card__sub"><?php echo esc_html( t( 'sec_req_sub' ) ); ?></p>
				<div class="tbf-card__body">
					<div class="tbf-row">
						<div class="tbf-field"><label class="tbf-label" for="q-age"><?php echo esc_html( t( 'req_age' ) ) . $req; // phpcs:ignore ?></label><select class="tbf-select" id="q-age" name="req_age"><?php echo $opts( 'req_age', [ '18' => '18', '21' => '21', '25' => '25', '30' => '30' ] ); // phpcs:ignore ?></select></div>
						<div class="tbf-field"><label class="tbf-label" for="q-exp"><?php echo esc_html( t( 'req_exp' ) ); ?></label><select class="tbf-select" id="q-exp" name="req_exp"><?php
							$exp_map = [];
							foreach ( Profile::CHOICES['req_exp'] as $y ) {
								$exp_map[ $y ] = '0' === $y ? t( 'exp_0' ) : sprintf( t( 'exp_n' ), (int) $y );
							}
							echo $opts( 'req_exp', $exp_map ); // phpcs:ignore
						?></select></div>
					</div>
					<div class="tbf-field"><label class="tbf-label" for="q-other"><?php echo esc_html( t( 'req_other' ) ); ?></label><textarea class="tbf-input tbf-textarea" id="q-other" name="req_other" rows="4" maxlength="1000" placeholder="<?php echo esc_attr( t( 'req_other_ph' ) ); ?>"><?php echo esc_textarea( (string) ( $tbf_form['req_other'] ?? '' ) ); ?></textarea></div>
				</div>
			</section>
		</div>
	</form>
	<div class="tbf-savebar">
		<?php if ( $tbf_cancel_url ) : ?><a class="tbf-btn tbf-btn--ghost tbf-btn--lg" href="<?php echo esc_url( $tbf_cancel_url ); ?>"><?php echo esc_html( t( 'cancel' ) ); ?></a><?php endif; ?>
		<button type="submit" form="tbf-profile" class="tbf-btn tbf-btn--lg" id="tbf-save"><?php echo esc_html( t( 'save' ) ); ?></button>
	</div>

