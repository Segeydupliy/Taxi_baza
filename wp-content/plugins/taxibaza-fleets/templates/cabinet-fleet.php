<?php
/**
 * Fleet cabinet — "Мій профіль" (view + edit).
 * Figma: Flotilla Profile (994:40), Flotilla - Edit (991:11118), Register Step 2.x.
 *
 * @package TaxiBaza\Fleets
 */

use TaxiBaza\Fleets\Cabinet;
use TaxiBaza\Fleets\Profile;
use TaxiBaza\Fleets\Status;
use function TaxiBaza\Fleets\t;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tbf_user    = wp_get_current_user();
$tbf_uid     = (int) $tbf_user->ID;
$tbf_status  = Status::get( $tbf_uid );
$tbf_edits   = Profile::has_pending_edits( $tbf_uid );
$tbf_live    = Profile::get_live( $tbf_uid );
$tbf_can     = Profile::can_edit( $tbf_uid );
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only UI flags.
$tbf_saved   = isset( $_GET['tbf_saved'] ) ? sanitize_key( wp_unslash( $_GET['tbf_saved'] ) ) : '';
$tbf_err     = isset( $_GET['tbf_error'] ) ? sanitize_key( wp_unslash( $_GET['tbf_error'] ) ) : '';
$tbf_is_edit = $tbf_can && ( ! empty( $_GET['edit'] ) || Status::DRAFT === $tbf_status );
// phpcs:enable

// Values for the form: values from a failed submit, else draft/live/core data.
$tbf_form   = Profile::get_for_form( $tbf_uid );
$tbf_errors = [];
$tbf_flash  = get_transient( 'tbf_form_' . $tbf_uid );
if ( is_array( $tbf_flash ) && 'form' === $tbf_err ) {
	$tbf_form   = array_merge( $tbf_form, (array) $tbf_flash['values'] );
	$tbf_errors = (array) $tbf_flash['errors'];
	delete_transient( 'tbf_form_' . $tbf_uid );
}
// What drivers see / what the view shows: live, or the submitted draft while nothing is live yet.
$tbf_show = $tbf_live ?: ( Profile::get_draft( $tbf_uid ) ?: Profile::get_for_form( $tbf_uid ) );

$tbf_published = (int) count_user_posts( $tbf_uid, 'car_listing', true );
$tbf_greeting  = function_exists( 'tb_opt' ) ? tb_opt( 'tb_cab_greeting', 'Вітаємо %s' ) : 'Вітаємо %s';
$tbf_lbl       = static fn( string $k, string $fb ) => function_exists( 'tb_opt' ) ? tb_opt( $k, $fb ) : $fb;
$tbf_locked    = ! Status::can_add_listings( $tbf_uid );

// Status banner.
$tbf_banner = '';
$tbf_banner_class = 'pending';
if ( Status::DRAFT === $tbf_status ) {
	$tbf_banner = t( 'st_draft' );
} elseif ( Status::PENDING === $tbf_status ) {
	$tbf_banner = t( 'st_pending' );
} elseif ( Status::REJECTED === $tbf_status ) {
	$tbf_banner       = t( 'st_rejected' );
	$tbf_banner_class = 'rejected';
} elseif ( Status::BLOCKED === $tbf_status ) {
	$tbf_banner       = t( 'st_blocked' );
	$tbf_banner_class = 'blocked';
} elseif ( $tbf_edits ) {
	$tbf_banner = t( 'st_edits' );
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

get_header();
?>
<main class="tb-cabinet tbf-cab" id="main-content">
<div class="tb-container">

	<div class="tb-cabinet__hero">
		<div class="tb-cabinet__hero-text">
			<h1 class="tb-cabinet__greeting"><?php printf( esc_html( $tbf_greeting ), esc_html( '' !== (string) $tbf_show['name'] ? $tbf_show['name'] : $tbf_user->display_name ) ); ?></h1>
			<p class="tb-cabinet__stats"><?php echo esc_html( Status::DRAFT === $tbf_status ? t( 'complete_lead' ) : sprintf( t( 'stats_published' ), $tbf_published ) ); ?></p>
		</div>
		<a href="<?php echo esc_url( function_exists( 'tb_url' ) ? tb_url( '/cabinet/add-listing/' ) : home_url( '/cabinet/add-listing/' ) ); ?>" class="tb-btn tb-btn--primary tb-cabinet__add-btn"<?php echo $tbf_locked ? ' aria-disabled="true" tabindex="-1"' : ''; ?>>
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 13H13V19H11V13H5V11H11V5H13V11H19V13Z" fill="currentColor"/></svg>
			<?php echo esc_html( $tbf_lbl( 'tb_cab_add_listing_btn', 'Додати оголошення' ) ); ?>
		</a>
	</div>

	<?php if ( $tbf_banner ) : ?>
		<p class="tbf-status tbf-status--<?php echo esc_attr( $tbf_banner_class ); ?>" role="status"><?php echo esc_html( $tbf_banner ); ?></p>
	<?php endif; ?>
	<?php if ( 'server' === $tbf_err ) : ?>
		<p class="tbf-status tbf-status--rejected" role="alert"><?php echo esc_html( t( 'err_server' ) ); ?></p>
	<?php elseif ( 'form' === $tbf_err ) : ?>
		<p class="tbf-status tbf-status--rejected" role="alert"><?php echo esc_html( t( 'err_form' ) ); ?></p>
	<?php endif; ?>

	<nav class="tb-cabinet__tabs" role="tablist">
		<?php $tbf_tab_off = Status::DRAFT === $tbf_status ? ' tb-cabinet__tab--disabled" aria-disabled="true' : ''; ?>
		<a href="<?php echo esc_url( Cabinet::tab_url( 'listings' ) ); ?>" class="tb-cabinet__tab<?php echo $tbf_tab_off; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static string. ?>" role="tab" aria-selected="false"><?php echo esc_html( $tbf_lbl( 'tb_cab_nav_listings', 'Мої оголошення' ) ); ?></a>
		<span class="tb-cabinet__tab tb-cabinet__tab--disabled" aria-disabled="true"><?php echo esc_html( $tbf_lbl( 'tb_cab_nav_messages', 'Повідомлення' ) ); ?></span>
		<a href="<?php echo esc_url( Cabinet::profile_url() ); ?>" class="tb-cabinet__tab tb-cabinet__tab--active" role="tab" aria-selected="true"><?php echo esc_html( $tbf_lbl( 'tb_cab_nav_profile', 'Мій профіль' ) ); ?></a>
		<a href="<?php echo esc_url( Cabinet::tab_url( 'settings' ) ); ?>" class="tb-cabinet__tab" role="tab" aria-selected="false"><?php echo esc_html( $tbf_lbl( 'tb_cab_nav_settings', 'Налаштування' ) ); ?></a>
	</nav>

<?php if ( $tbf_is_edit ) : ?>
	<form class="tbf-grid" id="tbf-profile" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" novalidate>
		<input type="hidden" name="action" value="tbf_save_profile">
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
					<div class="<?php echo esc_attr( $fcls( 'langs' ) ); ?>" data-group="langs"><span class="tbf-label"><?php echo esc_html( t( 'f_langs' ) ) . $req; // phpcs:ignore ?></span><div class="tbf-checks"><?php echo $checks( 'langs', [ 'sk' => t( 'lang_sk' ), 'uk' => t( 'lang_uk' ), 'cs' => t( 'lang_cs' ), 'en' => t( 'lang_en' ), 'ru' => t( 'lang_ru' ) ] ); // phpcs:ignore ?></div><?php echo $err( 'langs' ); // phpcs:ignore ?></div>
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

			<section class="tbf-card" data-show-if="m-fleet">
				<h2 class="tbf-card__title"><?php echo esc_html( t( 'sec_rent' ) ); ?></h2>
				<p class="tbf-card__sub"><?php echo esc_html( t( 'sec_rent_sub' ) ); ?></p>
				<div class="tbf-card__body">
					<div class="tbf-row">
						<div class="<?php echo esc_attr( $fcls( 'rent_price' ) ); ?>"><label class="tbf-label" for="r-price"><?php echo esc_html( t( 'rent_price' ) ) . $req; // phpcs:ignore ?></label><input class="tbf-input" id="r-price" name="rent_price" inputmode="decimal" value="<?php echo $e_attr( $tbf_form['rent_price'] ); // phpcs:ignore ?>" data-req="1"><?php echo $err( 'rent_price' ); // phpcs:ignore ?></div>
						<div class="tbf-field"><label class="tbf-label" for="r-period"><?php echo esc_html( t( 'rent_period' ) ); ?></label><select class="tbf-select" id="r-period" name="rent_period"><?php echo $opts( 'rent_period', [ 'week' => t( 'opt_per_week' ), 'day' => t( 'opt_per_day' ) ] ); // phpcs:ignore ?></select></div>
					</div>
					<?php echo $switch( 'rent_comm_on', 'r-comm-on', t( 'rent_comm_on' ) ); // phpcs:ignore ?>
					<div class="tbf-row" data-show-if="r-comm-on"><div class="<?php echo esc_attr( $fcls( 'rent_comm' ) ); ?>"><label class="tbf-label" for="r-comm"><?php echo esc_html( t( 'rent_comm' ) ) . $req; // phpcs:ignore ?></label><input class="tbf-input" id="r-comm" name="rent_comm" inputmode="decimal" value="<?php echo $e_attr( $tbf_form['rent_comm'] ); // phpcs:ignore ?>" data-req="1"><?php echo $err( 'rent_comm' ); // phpcs:ignore ?></div></div>
					<?php echo $switch( 'rent_dep_on', 'r-dep-on', t( 'rent_dep_on' ) ); // phpcs:ignore ?>
					<div class="tbf-row" data-show-if="r-dep-on">
						<div class="<?php echo esc_attr( $fcls( 'rent_dep' ) ); ?>"><label class="tbf-label" for="r-dep"><?php echo esc_html( t( 'rent_dep' ) ) . $req; // phpcs:ignore ?></label><input class="tbf-input" id="r-dep" name="rent_dep" inputmode="decimal" value="<?php echo $e_attr( $tbf_form['rent_dep'] ); // phpcs:ignore ?>" data-req="1"><?php echo $err( 'rent_dep' ); // phpcs:ignore ?></div>
						<div class="tbf-field"><label class="tbf-label" for="r-dep-ret"><?php echo esc_html( t( 'rent_dep_return' ) ); ?></label><select class="tbf-select" id="r-dep-ret" name="rent_dep_return"><?php echo $opts( 'rent_dep_return', [ 'handover' => t( 'opt_ret_handover' ), '7' => t( 'opt_ret_7' ), '14' => t( 'opt_ret_14' ) ] ); // phpcs:ignore ?></select></div>
					</div>
					<div class="tbf-row">
						<div class="<?php echo esc_attr( $fcls( 'rent_min' ) ); ?>"><label class="tbf-label" for="r-min"><?php echo esc_html( t( 'rent_min' ) ) . $req; // phpcs:ignore ?></label><div class="tbf-inline tbf-inline--min"><input class="tbf-input" id="r-min" name="rent_min" inputmode="numeric" value="<?php echo $e_attr( $tbf_form['rent_min'] ); // phpcs:ignore ?>" data-req="1"><select class="tbf-select" name="rent_min_unit" aria-label="<?php echo esc_attr( t( 'unit' ) ); ?>"><?php echo $opts( 'rent_min_unit', [ 'days' => t( 'opt_days' ), 'weeks' => t( 'opt_weeks' ), 'months' => t( 'opt_months' ) ] ); // phpcs:ignore ?></select></div><?php echo $err( 'rent_min' ); // phpcs:ignore ?></div>
						<div class="tbf-field"><label class="tbf-label" for="r-ext"><?php echo esc_html( t( 'rent_ext' ) ); ?></label><select class="tbf-select" id="r-ext" name="rent_ext"><?php echo $opts( 'rent_ext', [ 'weekly' => t( 'opt_weekly' ), 'daily' => t( 'opt_daily' ), 'monthly' => t( 'opt_monthly' ) ] ); // phpcs:ignore ?></select></div>
					</div>
					<div class="tbf-card__body" style="margin:0;gap:16px"><?php echo $checks( 'inc', [ 'insurance' => t( 'inc_insurance' ), 'service' => t( 'inc_service' ), 'tires' => t( 'inc_tires' ), 'wash' => t( 'inc_wash' ) ], 'switch' ); // phpcs:ignore ?></div>
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
								<div class="<?php echo esc_attr( $fcls( 'fuel_discount' ) ); ?>"><label class="tbf-label" for="k-fuel-v"><?php echo esc_html( t( 'discount' ) ) . $req; // phpcs:ignore ?></label><input class="tbf-input" id="k-fuel-v" name="fuel_discount" inputmode="decimal" value="<?php echo $e_attr( $tbf_form['fuel_discount'] ); // phpcs:ignore ?>" data-req="1"><?php echo $err( 'fuel_discount' ); // phpcs:ignore ?></div>
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
								<div class="<?php echo esc_attr( $fcls( 'svc_discount' ) ); ?>"><label class="tbf-label" for="k-svc-v"><?php echo esc_html( t( 'discount_pct' ) ) . $req; // phpcs:ignore ?></label><input class="tbf-input" id="k-svc-v" name="svc_discount" inputmode="decimal" value="<?php echo $e_attr( $tbf_form['svc_discount'] ); // phpcs:ignore ?>" data-req="1"><?php echo $err( 'svc_discount' ); // phpcs:ignore ?></div>
								<div class="tbf-field"><label class="tbf-label" for="k-svc-w"><?php echo esc_html( t( 'svc_where' ) ); ?></label><select class="tbf-select" id="k-svc-w" name="svc_where"><?php echo $opts( 'svc_where', [ 'partner' => t( 'opt_partner_sto' ), 'own' => t( 'opt_own_sto' ) ] ); // phpcs:ignore ?></select></div>
							</div>
							<div class="<?php echo esc_attr( $fcls( 'svc_what' ) ); ?>" data-group="svcwhat"><span class="tbf-label"><?php echo esc_html( t( 'svc_what' ) ) . $req; // phpcs:ignore ?></span><div class="tbf-checks"><?php echo $checks( 'svc_what', [ 'service' => t( 'svc_service' ), 'tires' => t( 'svc_tires' ), 'parts' => t( 'svc_parts' ), 'wash' => t( 'svc_wash' ) ] ); // phpcs:ignore ?></div><?php echo $err( 'svc_what' ); // phpcs:ignore ?></div>
							<div class="tbf-field"><span class="tbf-label"><?php echo esc_html( t( 'for_whom' ) ); ?></span><div class="tbf-checks"><?php echo $checks( 'svc_for', $for_map ); // phpcs:ignore ?></div></div>
						</div>
					</div>
					<div class="tbf-perk">
						<?php echo $switch( 'perk_concession', 'k-con', t( 'perk_con' ), t( 'perk_con_hint' ), true ); // phpcs:ignore ?>
						<div class="tbf-perk__fields" data-show-if="k-con">
							<div class="tbf-field"><label class="tbf-label" for="k-con-w"><?php echo esc_html( t( 'con_what' ) ); ?></label><select class="tbf-select" id="k-con-w" name="con_what"><?php echo $opts( 'con_what', [ 'help' => t( 'opt_con_help' ), 'do' => t( 'opt_con_do' ) ] ); // phpcs:ignore ?></select></div>
							<div class="tbf-field"><label class="tbf-label" for="k-con-c"><?php echo esc_html( t( 'con_cost' ) ); ?></label><select class="tbf-select" id="k-con-c" name="con_cost"><?php echo $opts( 'con_cost', [ 'free' => t( 'opt_free' ), 'compensate' => t( 'opt_compensate' ), 'paid' => t( 'opt_paid' ) ] ); // phpcs:ignore ?></select></div>
							<div class="tbf-field"><span class="tbf-label"><?php echo esc_html( t( 'for_whom' ) ); ?></span><div class="tbf-checks"><?php echo $checks( 'con_for', $for_map ); // phpcs:ignore ?></div></div>
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
					<div class="<?php echo esc_attr( $fcls( 'docs' ) ); ?>" data-group="docs"><span class="tbf-label"><?php echo esc_html( t( 'docs' ) ) . $req; // phpcs:ignore ?></span><div class="tbf-checks" style="flex-direction:column;gap:12px"><?php echo $checks( 'docs', [ 'id' => t( 'doc_id' ), 'license' => t( 'doc_license' ), 'record' => t( 'doc_record' ) ] ); // phpcs:ignore ?></div><?php echo $err( 'docs' ); // phpcs:ignore ?></div>
				</div>
			</section>
		</div>
	</form>
	<div class="tbf-savebar">
		<?php if ( $tbf_live ) : ?><a class="tbf-btn tbf-btn--ghost tbf-btn--lg" href="<?php echo esc_url( Cabinet::profile_url() ); ?>"><?php echo esc_html( t( 'cancel' ) ); ?></a><?php endif; ?>
		<button type="submit" form="tbf-profile" class="tbf-btn tbf-btn--lg" id="tbf-save"><?php echo esc_html( t( 'save' ) ); ?></button>
	</div>

<?php else : // ---------------- VIEW ---------------- ?>
	<?php
	$tbf_p        = $tbf_show;
	$tbf_email    = $tbf_user->user_email;
	$tbf_edit_url = $tbf_can ? Cabinet::profile_url( [ 'edit' => 1 ] ) : '';
	require __DIR__ . '/parts/profile-view.php';
	?>
	<?php if ( $tbf_can ) : ?>
	<div class="tbf-savebar"><a class="tbf-btn tbf-btn--dark tbf-btn--lg" href="<?php echo esc_url( Cabinet::profile_url( [ 'edit' => 1 ] ) ); ?>"><?php echo esc_html( t( 'edit' ) ); ?></a></div>
	<?php endif; ?>
<?php endif; ?>

</div>
</main>

<?php if ( in_array( $tbf_saved, [ 'submitted', 'edits' ], true ) ) : ?>
<div class="tbf-modal" id="tbf-modal" role="dialog" aria-modal="true" aria-labelledby="tbf-modal-t">
	<div class="tbf-modal__box">
		<h2 class="tbf-modal__title" id="tbf-modal-t"><?php echo esc_html( t( 'mod_modal_title' ) ); ?></h2>
		<p class="tbf-modal__text"><?php echo esc_html( 'edits' === $tbf_saved ? t( 'mod_edits_modal_text' ) : t( 'mod_modal_text' ) ); ?></p>
		<button type="button" class="tbf-btn tbf-btn--dark" id="tbf-modal-close"><?php echo esc_html( t( 'close' ) ); ?></button>
	</div>
</div>
<?php endif; ?>

<?php
get_footer();
