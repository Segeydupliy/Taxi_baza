<?php
/**
 * Phone confirmation screen (SMS code via Twilio Verify) after registration.
 *
 * @package TaxiBaza\Fleets
 */

use TaxiBaza\Fleets\Phone_Verify;
use function TaxiBaza\Fleets\t;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tbf_uid   = get_current_user_id();
$tbf_phone = (string) get_user_meta( $tbf_uid, 'phone', true );
$tbf_data  = [
	'ajax'    => admin_url( 'admin-ajax.php' ),
	'nonce'   => wp_create_nonce( Phone_Verify::NONCE ),
	'sending' => t( 'sms_sending' ),
	'error'   => t( 'sms_err_service' ),
];
get_header();
?>
<main class="tb-auth tbf-phone">
	<div class="tb-container">
		<div class="tb-auth-card" style="max-width:520px;margin:80px auto">
			<h1 class="tb-auth-card__title"><?php echo esc_html( t( 'sms_title' ) ); ?></h1>
			<p class="tb-auth-card__subtitle"><?php echo esc_html( t( 'sms_lead' ) ); ?></p>

			<form class="tb-auth-form" id="tbf-phone-step1" style="margin-top:24px" novalidate>
				<label class="tb-field__label" for="tbf-phone-in" style="display:block;font-weight:600;margin-bottom:8px"><?php echo esc_html( t( 'sms_phone' ) ); ?></label>
				<input class="tb-field__input tb-input" id="tbf-phone-in" name="phone" type="tel" inputmode="tel" autocomplete="tel" required placeholder="+421 9XX XXX XXX" value="<?php echo esc_attr( $tbf_phone ); ?>" style="width:100%">
				<p class="tbf-phone__hint" style="margin:8px 0 0;font-size:13px;color:#737373"><?php echo esc_html( t( 'sms_phone_hint' ) ); ?></p>
				<button type="submit" class="tb-btn tb-btn--primary tb-btn--block" style="margin-top:24px"><?php echo esc_html( t( 'sms_send' ) ); ?></button>
			</form>

			<form class="tb-auth-form" id="tbf-phone-step2" style="margin-top:24px" hidden novalidate>
				<p id="tbf-phone-sent" style="margin:0 0 16px"></p>
				<label class="tb-field__label" for="tbf-code-in" style="display:block;font-weight:600;margin-bottom:8px"><?php echo esc_html( t( 'sms_code' ) ); ?></label>
				<input class="tb-field__input tb-input" id="tbf-code-in" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required style="width:100%;letter-spacing:4px;font-size:20px">
				<button type="submit" class="tb-btn tb-btn--primary tb-btn--block" style="margin-top:24px"><?php echo esc_html( t( 'sms_confirm' ) ); ?></button>
				<p style="margin:16px 0 0;text-align:center"><button type="button" id="tbf-phone-back" style="background:none;border:0;padding:0;text-decoration:underline;cursor:pointer;font:inherit"><?php echo esc_html( t( 'sms_change' ) ); ?></button></p>
			</form>

			<p id="tbf-phone-err" role="alert" style="color:#d0021b;margin:12px 0 0;min-height:1em"></p>
			<p class="tb-auth-card__footer" style="margin-top:16px;text-align:center">
				<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php echo esc_html( t( 'terms_logout' ) ); ?></a>
			</p>
		</div>
	</div>
</main>
<script>
(function () {
	var D = <?php echo wp_json_encode( $tbf_data ); ?>;
	var s1 = document.getElementById('tbf-phone-step1'), s2 = document.getElementById('tbf-phone-step2');
	var err = document.getElementById('tbf-phone-err'), sent = document.getElementById('tbf-phone-sent');
	function post(action, data, btn) {
		var fd = new FormData(); fd.append('action', action); fd.append('nonce', D.nonce);
		Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
		err.textContent = ''; btn.disabled = true;
		return fetch(D.ajax, { method: 'POST', credentials: 'same-origin', body: fd })
			.then(function (r) { return r.json(); })
			.catch(function () { return { success: false, data: { message: D.error } }; })
			.then(function (j) { btn.disabled = false; return j; });
	}
	s1.addEventListener('submit', function (e) {
		e.preventDefault();
		var btn = s1.querySelector('button[type=submit]'), phone = document.getElementById('tbf-phone-in').value;
		post('tbf_phone_send', { phone: phone }, btn).then(function (j) {
			if (j && j.success && j.data && j.data.done) { location.reload(); return; }
			if (!j || !j.success) { err.textContent = (j && j.data && j.data.message) || D.error; return; }
			sent.textContent = j.data.message; s1.hidden = true; s2.hidden = false; document.getElementById('tbf-code-in').focus();
		});
	});
	s2.addEventListener('submit', function (e) {
		e.preventDefault();
		var btn = s2.querySelector('button[type=submit]');
		post('tbf_phone_check', { code: document.getElementById('tbf-code-in').value }, btn).then(function (j) {
			if (j && j.success) { location.href = (j.data && j.data.redirect) || '/'; return; }
			err.textContent = (j && j.data && j.data.message) || D.error;
		});
	});
	document.getElementById('tbf-phone-back').addEventListener('click', function () { s2.hidden = true; s1.hidden = false; err.textContent = ''; });
}());
</script>
<?php
get_footer();
