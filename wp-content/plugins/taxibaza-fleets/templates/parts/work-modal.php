<?php
/**
 * Job application popup (Figma: Send Message Pop-up 895:5659, error 895:5712,
 * success 895:5774). Opened by any [data-tbf-apply] button; fleets.js fills in
 * the fleet and sends the form to admin-ajax (action tbf_work_request).
 *
 * Contact fields (name, phone, email) are not in the Figma form: without the
 * site's messaging the fleet needs them to answer. Logged-in users get them
 * pre-filled from their account.
 *
 * @package TaxiBaza\Fleets
 */

use TaxiBaza\Fleets\Consent;
use TaxiBaza\Fleets\Directory;
use TaxiBaza\Fleets\I18n;
use TaxiBaza\Fleets\Icons;
use TaxiBaza\Fleets\Work_Request;
use function TaxiBaza\Fleets\t;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tbf_m_cities = get_terms( [ 'taxonomy' => 'city', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ] );
$tbf_m_cities = is_wp_error( $tbf_m_cities ) ? [] : $tbf_m_cities;
$tbf_m_user   = wp_get_current_user();
$tbf_m_uid    = (int) ( $tbf_m_user->ID ?? 0 );
$tbf_m_name   = $tbf_m_uid ? (string) $tbf_m_user->display_name : '';
$tbf_m_phone  = $tbf_m_uid ? (string) get_user_meta( $tbf_m_uid, 'phone', true ) : '';
$tbf_m_email  = $tbf_m_uid ? (string) $tbf_m_user->user_email : '';
$tbf_m_legal  = sprintf(
	esc_html( t( 'fl_m_consent' ) ),
	'<a href="' . esc_url( Consent::legal_url( 'privacy' ) ) . '" target="_blank" rel="noopener">' . esc_html( t( 'terms_link_privacy' ) ) . '</a>',
	'<a href="' . esc_url( Consent::legal_url( 'terms' ) ) . '" target="_blank" rel="noopener">' . esc_html( t( 'terms_link_terms' ) ) . '</a>'
);
$tbf_m_req = '<span class="tbf-req" aria-hidden="true">*</span>';
?>
<div class="tbf-modal-wrap" id="tbf-apply" hidden>
	<div class="tbf-modal-wrap__backdrop" data-tbf-close></div>
	<div class="tbf-dialog" role="dialog" aria-modal="true" aria-labelledby="tbf-apply-title" tabindex="-1">

		<div class="tbf-dialog__head">
			<h2 class="tbf-dialog__title" id="tbf-apply-title"><?php echo esc_html( t( 'fl_contact_title' ) ); ?></h2>
			<button type="button" class="tbf-dialog__close" data-tbf-close aria-label="<?php echo esc_attr( t( 'close' ) ); ?>"><?php Icons::out( 'close', 24 ); ?></button>
		</div>

		<form class="tbf-form" data-tbf-form novalidate
			data-err-pick="<?php echo esc_attr( t( 'fl_m_err_pick' ) ); ?>"
			data-err-required="<?php echo esc_attr( t( 'err_required' ) ); ?>"
			data-err-phone="<?php echo esc_attr( t( 'err_phone' ) ); ?>"
			data-err-email="<?php echo esc_attr( t( 'fl_m_err_email' ) ); ?>"
			data-err-short="<?php echo esc_attr( t( 'fl_m_err_short' ) ); ?>">
			<p class="tbf-dialog__sub"><?php echo esc_html( t( 'fl_m_sub' ) ); ?></p>

			<div class="tbf-banner tbf-banner--error" data-tbf-banner role="alert" hidden>
				<span class="tbf-banner__ic" aria-hidden="true">!</span><span data-tbf-banner-text></span>
			</div>

			<p class="tbf-form__note"><span class="tbf-req" aria-hidden="true">*</span> <?php echo esc_html( t( 'required_note' ) ); ?></p>

			<input type="hidden" name="action" value="<?php echo esc_attr( Work_Request::ACTION ); ?>">
			<input type="hidden" name="fleet" value="">
			<input type="hidden" name="lang" value="<?php echo esc_attr( I18n::lang() ); ?>">
			<?php wp_nonce_field( Work_Request::NONCE, '_wpnonce', false ); ?>
			<p class="tbf-hp" aria-hidden="true"><label>Website <input type="text" name="<?php echo esc_attr( Work_Request::HONEYPOT ); ?>" tabindex="-1" autocomplete="off"></label></p>

			<fieldset class="tbf-field" data-field="car">
				<legend class="tbf-field__label"><?php echo esc_html( t( 'fl_m_car' ) ); ?> <?php echo $tbf_m_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static. ?></legend>
				<div class="tbf-opts">
					<label class="tbf-opt" data-model="fleet"><input type="radio" name="car" value="fleet" required><span class="tbf-opt__ic" aria-hidden="true"></span><span><?php echo esc_html( t( 'fl_m_car_fleet' ) ); ?></span></label>
					<label class="tbf-opt" data-model="own"><input type="radio" name="car" value="own"><span class="tbf-opt__ic" aria-hidden="true"></span><span><?php echo esc_html( t( 'fl_m_car_own' ) ); ?></span></label>
				</div>
				<p class="tbf-field__err" data-err></p>
			</fieldset>

			<fieldset class="tbf-field" data-field="license">
				<legend class="tbf-field__label"><?php echo esc_html( t( 'fl_m_lic' ) ); ?> <?php echo $tbf_m_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></legend>
				<div class="tbf-opts">
					<label class="tbf-opt"><input type="radio" name="license" value="yes" required><span class="tbf-opt__ic" aria-hidden="true"></span><span><?php echo esc_html( t( 'fl_m_lic_yes' ) ); ?></span></label>
					<label class="tbf-opt"><input type="radio" name="license" value="no"><span class="tbf-opt__ic" aria-hidden="true"></span><span><?php echo esc_html( t( 'fl_m_lic_no' ) ); ?></span></label>
				</div>
				<p class="tbf-field__err" data-err></p>
			</fieldset>

			<div class="tbf-field" data-field="city">
				<label class="tbf-field__label" for="tbf-f-city"><?php echo esc_html( t( 'fl_m_city' ) ); ?> <?php echo $tbf_m_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
				<div class="tbf-select">
					<select id="tbf-f-city" name="city" required>
						<option value=""><?php echo esc_html( t( 'fl_m_city_ph' ) ); ?></option>
						<?php foreach ( $tbf_m_cities as $tbf_m_c ) : ?>
							<option value="<?php echo esc_attr( (string) $tbf_m_c->term_id ); ?>"><?php echo esc_html( $tbf_m_c->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php Icons::out( 'expand', 20 ); ?>
				</div>
				<p class="tbf-field__err" data-err></p>
			</div>

			<div class="tbf-field" data-field="name">
				<label class="tbf-field__label" for="tbf-f-name"><?php echo esc_html( t( 'fl_m_name' ) ); ?> <?php echo $tbf_m_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
				<input class="tbf-input" id="tbf-f-name" type="text" name="name" maxlength="120" autocomplete="name" required value="<?php echo esc_attr( $tbf_m_name ); ?>">
				<p class="tbf-field__err" data-err></p>
			</div>

			<div class="tbf-form__row">
				<div class="tbf-field" data-field="phone">
					<label class="tbf-field__label" for="tbf-f-phone"><?php echo esc_html( t( 'f_phone' ) ); ?> <?php echo $tbf_m_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<input class="tbf-input" id="tbf-f-phone" type="tel" name="phone" maxlength="30" autocomplete="tel" placeholder="+421" required value="<?php echo esc_attr( $tbf_m_phone ); ?>">
					<p class="tbf-field__err" data-err></p>
				</div>
				<div class="tbf-field" data-field="email">
					<label class="tbf-field__label" for="tbf-f-email"><?php echo esc_html( t( 'f_email' ) ); ?></label>
					<input class="tbf-input" id="tbf-f-email" type="email" name="email" maxlength="120" autocomplete="email" value="<?php echo esc_attr( $tbf_m_email ); ?>">
					<p class="tbf-field__err" data-err></p>
				</div>
			</div>

			<div class="tbf-field" data-field="message">
				<label class="tbf-field__label" for="tbf-f-msg"><?php echo esc_html( t( 'fl_m_msg' ) ); ?> <?php echo $tbf_m_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
				<textarea class="tbf-input tbf-input--area" id="tbf-f-msg" name="message" maxlength="2000" minlength="<?php echo (int) Work_Request::MIN_MSG; ?>" required></textarea>
				<p class="tbf-field__err" data-err></p>
			</div>

			<p class="tbf-form__legal"><?php echo $tbf_m_legal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></p>

			<button type="submit" class="tbf-btn tbf-btn--accent tbf-btn--lg" data-tbf-submit><?php echo esc_html( t( 'fl_m_send' ) ); ?></button>
		</form>

		<div class="tbf-done" data-tbf-done hidden>
			<div class="tbf-done__msg">
				<span class="tbf-done__ic" aria-hidden="true"><?php Icons::out( 'check', 48 ); ?></span>
				<p class="tbf-done__title" data-tbf-done-title><?php echo esc_html( t( 'fl_m_ok_title' ) ); ?></p>
				<p class="tbf-done__text" data-tbf-done-text></p>
			</div>
			<div class="tbf-done__next">
				<p class="tbf-banner tbf-banner--info"><span class="tbf-banner__ic" aria-hidden="true">i</span><span><?php echo esc_html( t( 'fl_m_ok_info' ) ); ?></span></p>
				<a class="tbf-btn tbf-btn--outline tbf-btn--lg tbf-done__btn" href="<?php echo esc_url( Directory::base_url() ); ?>"><?php echo esc_html( t( 'fl_m_ok_btn' ) ); ?></a>
			</div>
		</div>

	</div>
</div>
