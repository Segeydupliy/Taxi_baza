<?php
/**
 * Phone confirmation screen (Twilio Verify).
 *
 * Figma: "Confirm Phone" 343:2846 (code), 343:2971 (wrong code),
 * "Confirm Phone Success" 343:3104. Accounts from the registration form arrive
 * with the number already known and the SMS goes out by itself; Google
 * accounts type the number first.
 *
 * @package TaxiBaza\Fleets
 */

use TaxiBaza\Fleets\Phone_Verify;
use function TaxiBaza\Fleets\t;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tbf_uid       = get_current_user_id();
$tbf_candidate = (string) get_user_meta( $tbf_uid, Phone_Verify::CANDIDATE, true );
$tbf_profile   = (string) get_user_meta( $tbf_uid, 'phone', true );
$tbf_data      = [
	'ajax'    => admin_url( 'admin-ajax.php' ),
	'nonce'   => wp_create_nonce( Phone_Verify::NONCE ),
	'phone'   => $tbf_candidate,
	'pretty'  => $tbf_candidate ? Phone_Verify::pretty( $tbf_candidate ) : '',
	'auto'    => '' !== $tbf_candidate && ! Phone_Verify::sent_recently( $tbf_uid ),
	'wait'    => 60,
	'error'   => t( 'sms_err_service' ),
	'codeErr' => t( 'sms_err_code' ),
	'resendIn'=> t( 'sms_resend_in' ),
	'resend'  => t( 'sms_resend' ),
	'okText'  => t( 'sms_ok_text' ),
	'empty'   => t( 'sms_reg_empty' ),
];
$tbf_icon_phone = '<span class="tbf-pv__emoji" aria-hidden="true">📱</span>';
get_header();
?>
<main class="tb-auth-page tbf-pv" id="main-content">
	<div class="tb-container">
		<div class="tb-auth-wrap">

			<!-- Step 1: the number (Google accounts, or "change number") -->
			<div class="tb-auth-card tbf-pv__card" id="tbf-pv-phone"<?php echo $tbf_candidate ? ' hidden' : ''; ?>>
				<div class="tbf-pv__head">
					<?php echo $tbf_icon_phone; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?>
					<h1 class="tbf-pv__title"><?php echo esc_html( t( 'sms_code_title' ) ); ?></h1>
					<p class="tbf-pv__text"><?php echo esc_html( t( 'sms_lead' ) ); ?></p>
				</div>
				<form class="tbf-pv__form" id="tbf-pv-phone-form" novalidate>
					<div class="tb-auth-form__field">
						<label class="tb-auth-form__label" for="tbf-pv-phone-in"><?php echo esc_html( t( 'sms_phone' ) ); ?></label>
						<input class="tb-auth-form__input" id="tbf-pv-phone-in" name="phone" type="tel" inputmode="tel" autocomplete="tel" required placeholder="+421 9XX XXX XXX" value="<?php echo esc_attr( $tbf_candidate ? $tbf_candidate : $tbf_profile ); ?>">
						<span class="tbf-pv__hint tbf-pv__hint--left"><?php echo esc_html( t( 'sms_phone_hint' ) ); ?></span>
					</div>
					<div class="tbf-pv__error" role="alert" hidden><span class="tbf-pv__error-ic" aria-hidden="true">!</span><span class="tbf-pv__error-t"></span></div>
					<button type="submit" class="tb-btn tb-btn--accent tb-btn--block tbf-pv__btn"><?php echo esc_html( t( 'sms_send' ) ); ?></button>
				</form>
				<p class="tbf-pv__foot"><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php echo esc_html( t( 'terms_logout' ) ); ?></a></p>
			</div>

			<!-- Step 2: the code (Figma 343:2846 / 343:2971) -->
			<div class="tb-auth-card tbf-pv__card" id="tbf-pv-code"<?php echo $tbf_candidate ? '' : ' hidden'; ?>>
				<div class="tbf-pv__head">
					<?php echo $tbf_icon_phone; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?>
					<h1 class="tbf-pv__title"><?php echo esc_html( t( 'sms_code_title' ) ); ?></h1>
					<p class="tbf-pv__text"><?php echo esc_html( t( 'sms_sent_to' ) ); ?><br><span id="tbf-pv-number"><?php echo esc_html( $tbf_data['pretty'] ); ?></span></p>
				</div>
				<form class="tbf-pv__form" id="tbf-pv-code-form" novalidate>
					<div class="tbf-pv__otp-wrap">
						<div class="tbf-pv__otp" role="group" aria-label="<?php echo esc_attr( t( 'sms_code_hint' ) ); ?>">
							<?php for ( $tbf_i = 0; $tbf_i < 6; $tbf_i++ ) : ?>
							<input class="tbf-pv__box" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="<?php echo 0 === $tbf_i ? 6 : 1; ?>" <?php echo 0 === $tbf_i ? 'autocomplete="one-time-code"' : 'autocomplete="off"'; ?> aria-label="<?php echo esc_attr( ( $tbf_i + 1 ) . '/6' ); ?>">
							<?php endfor; ?>
						</div>
						<p class="tbf-pv__hint"><?php echo esc_html( t( 'sms_code_hint' ) ); ?></p>
					</div>
					<div class="tbf-pv__error" role="alert" hidden><span class="tbf-pv__error-ic" aria-hidden="true">!</span><span class="tbf-pv__error-t"></span></div>
					<div class="tbf-pv__actions">
						<button type="submit" class="tb-btn tb-btn--accent tb-btn--block tbf-pv__btn"><?php echo esc_html( t( 'sms_confirm' ) ); ?></button>
						<p class="tbf-pv__resend"><span><?php echo esc_html( t( 'sms_no_code' ) ); ?></span> <button type="button" id="tbf-pv-resend"><?php echo esc_html( t( 'sms_resend' ) ); ?></button></p>
						<p class="tbf-pv__foot"><button type="button" id="tbf-pv-change"><?php echo esc_html( t( 'sms_change_number' ) ); ?></button> · <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php echo esc_html( t( 'terms_logout' ) ); ?></a></p>
					</div>
				</form>
			</div>

			<!-- Done (Figma 343:3104) -->
			<div class="tb-auth-confirm-success tbf-pv__ok" id="tbf-pv-ok" hidden>
				<div class="tb-auth-confirm__icon-wrap tb-auth-confirm__icon-wrap--success" aria-hidden="true">
					<svg width="36" height="36" viewBox="0 0 36 36" fill="none"><path d="M7 18.5L14 25.5L29 11" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</div>
				<div class="tb-auth-confirm-success__content">
					<h1 class="tb-auth-confirm-success__title"><?php echo esc_html( t( 'sms_ok_title' ) ); ?></h1>
					<p class="tb-auth-confirm-success__desc" id="tbf-pv-ok-text"></p>
				</div>
				<a class="tb-btn tb-btn--accent tbf-pv__ok-btn" id="tbf-pv-ok-btn" href="<?php echo esc_url( function_exists( 'tb_url' ) ? tb_url( '/cabinet/' ) : home_url( '/cabinet/' ) ); ?>"><?php echo esc_html( t( 'sms_ok_btn' ) ); ?></a>
			</div>

		</div>
	</div>
</main>
<style>
.tbf-pv .tbf-pv__card{max-width:480px;display:flex;flex-direction:column;gap:48px}
.tbf-pv [hidden]{display:none!important}
.tbf-pv__head{display:flex;flex-direction:column;align-items:center;gap:16px;text-align:center}
.tbf-pv__emoji{display:flex;align-items:center;justify-content:center;width:72px;height:72px;font-size:32px;line-height:1}
.tbf-pv__title{font:700 22px/28px var(--tb-font-body,Inter,sans-serif);color:#000;margin:0}
.tbf-pv__text{font-size:14px;line-height:20px;color:#555;margin:0}
.tbf-pv__form{display:flex;flex-direction:column;gap:48px}
.tbf-pv__otp-wrap{display:flex;flex-direction:column;gap:16px}
.tbf-pv__otp{display:flex;gap:10px}
.tbf-pv__box{flex:1 1 0;min-width:0;width:100%;height:52px;box-sizing:border-box;border:1px solid #e5e5e5;border-radius:8px;background:#fff;text-align:center;font:700 20px/1 var(--tb-font-body,Inter,sans-serif);color:#000;padding:0;outline:none;transition:border-color .15s}
.tbf-pv__box.is-filled,.tbf-pv__box:focus{border:1.5px solid #000}
.tbf-pv__otp.is-invalid .tbf-pv__box{border:1.5px solid #d44f4f;background:#fff5f5;color:#d44f4f}
.tbf-pv__hint{display:block;font-size:13px;line-height:16px;color:#999;text-align:center;margin:0}
.tbf-pv__hint--left{text-align:left;margin-top:8px}
.tbf-pv__error{display:flex;align-items:center;gap:12px;padding:14px 16px;border:1px solid #d44f4f;border-radius:12px;background:#fff5f5;color:#d44f4f;font-size:13px;line-height:18px;margin-top:-24px}
.tbf-pv__error[hidden]{display:none}
.tbf-pv__error-ic{flex:none;display:flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:#d44f4f;color:#fff;font-size:11px;font-weight:700}
.tbf-pv__actions{display:flex;flex-direction:column;align-items:center;gap:24px}
.tbf-pv__btn,.tbf-pv__ok-btn{background:var(--tb-accent,#02f797);border:0;color:#000;border-radius:100px;font-weight:700;font-size:15px}
.tbf-pv__btn[disabled]{opacity:.6;cursor:wait}
.tbf-pv__resend{margin:0;font-size:14px;color:#555;text-align:center}
.tbf-pv__resend button,.tbf-pv__foot button{background:none;border:0;padding:0;font:inherit;cursor:pointer}
.tbf-pv__resend button{font-weight:600;color:#02c879}
.tbf-pv__resend button[disabled]{color:#999;cursor:default}
.tbf-pv__foot{margin:-8px 0 0;font-size:13px;color:#999;text-align:center}
.tbf-pv__foot a,.tbf-pv__foot button{color:#555;text-decoration:underline}
.tbf-pv__ok-btn{padding:16px 32px;text-decoration:none;display:inline-flex}
.tbf-pv .tb-auth-form__input{box-sizing:border-box;width:100%}
@media (max-width:480px){.tbf-pv .tbf-pv__card{padding:24px 16px;gap:32px}.tbf-pv__otp{gap:6px}.tbf-pv__form{gap:32px}.tbf-pv__error{margin-top:-8px}}
</style>
<script>
(function () {
	var D = <?php echo wp_json_encode( $tbf_data ); ?>;
	var $ = function (id) { return document.getElementById(id); };
	var cardPhone = $('tbf-pv-phone'), cardCode = $('tbf-pv-code'), ok = $('tbf-pv-ok');
	var phoneForm = $('tbf-pv-phone-form'), codeForm = $('tbf-pv-code-form');
	var otp = codeForm.querySelector('.tbf-pv__otp'), boxes = [].slice.call(codeForm.querySelectorAll('.tbf-pv__box'));
	var resendBtn = $('tbf-pv-resend'), timer = null;

	function err(card, msg) {
		var b = card.querySelector('.tbf-pv__error');
		b.querySelector('.tbf-pv__error-t').textContent = msg || '';
		b.hidden = !msg;
	}
	function post(action, data) {
		var fd = new FormData(); fd.append('action', action); fd.append('nonce', D.nonce);
		Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
		return fetch(D.ajax, { method: 'POST', credentials: 'same-origin', body: fd })
			.then(function (r) { return r.json(); })
			.catch(function () { return { success: false, data: { message: D.error } }; });
	}
	function busy(btn, on) { if (btn) { btn.disabled = on; } }
	function showCode(pretty) {
		$('tbf-pv-number').textContent = pretty || D.pretty;
		cardPhone.hidden = true; cardCode.hidden = false; ok.hidden = true;
		clearBoxes(); boxes[0].focus();
	}
	function showPhone(msg) {
		cardCode.hidden = true; cardPhone.hidden = false; err(cardPhone, msg || '');
		$('tbf-pv-phone-in').focus();
	}
	function countdown() {
		var left = D.wait;
		clearInterval(timer); resendBtn.disabled = true;
		resendBtn.textContent = D.resendIn.replace('%s', left);
		timer = setInterval(function () {
			left -= 1;
			if (left <= 0) { clearInterval(timer); resendBtn.disabled = false; resendBtn.textContent = D.resend; return; }
			resendBtn.textContent = D.resendIn.replace('%s', left);
		}, 1000);
	}
	function send(phone, btn) {
		busy(btn, true);
		return post('tbf_phone_send', { phone: phone || '' }).then(function (j) {
			busy(btn, false);
			if (j && j.success && j.data && j.data.done) { location.href = '/'; return; }
			if (!j || !j.success) {
				var m = (j && j.data && j.data.message) || D.error;
				if (btn === resendBtn) { err(cardCode, m); } else { showPhone(m); }
				return;
			}
			D.pretty = j.data.pretty; err(cardCode, ''); showCode(j.data.pretty); countdown();
		});
	}

	/* code boxes */
	function code() { return boxes.map(function (b) { return b.value; }).join(''); }
	function paint() { boxes.forEach(function (b) { b.classList.toggle('is-filled', b.value !== ''); }); }
	function clearBoxes() { boxes.forEach(function (b) { b.value = ''; }); otp.classList.remove('is-invalid'); paint(); }
	function fill(from, digits) {
		for (var k = 0; k < digits.length && from + k < boxes.length; k++) { boxes[from + k].value = digits[k]; }
		paint();
		var next = Math.min(from + digits.length, boxes.length - 1);
		boxes[next].focus();
		if (code().length === boxes.length) { submitCode(); }
	}
	boxes.forEach(function (b, i) {
		b.addEventListener('input', function () {
			var d = b.value.replace(/\D/g, '');
			otp.classList.remove('is-invalid'); err(cardCode, '');
			b.value = '';
			if (d) { fill(i, d.split('')); } else { paint(); }
		});
		b.addEventListener('keydown', function (e) {
			if (e.key === 'Backspace' && !b.value && i > 0) { boxes[i - 1].value = ''; boxes[i - 1].focus(); paint(); e.preventDefault(); }
			if (e.key === 'ArrowLeft' && i > 0) { boxes[i - 1].focus(); }
			if (e.key === 'ArrowRight' && i < boxes.length - 1) { boxes[i + 1].focus(); }
		});
		b.addEventListener('paste', function (e) {
			var d = ((e.clipboardData || window.clipboardData).getData('text') || '').replace(/\D/g, '');
			if (d) { e.preventDefault(); fill(i, d.split('')); }
		});
		b.addEventListener('focus', function () { b.select(); });
	});

	var checking = false;
	function submitCode() {
		if (checking) { return; }
		var c = code(), btn = codeForm.querySelector('button[type=submit]');
		if (c.length < boxes.length) { otp.classList.add('is-invalid'); err(cardCode, D.codeErr); return; }
		checking = true; busy(btn, true);
		post('tbf_phone_check', { code: c }).then(function (j) {
			checking = false; busy(btn, false);
			if (j && j.success) {
				if (j.data && j.data.redirect) { $('tbf-pv-ok-btn').href = j.data.redirect; }
				$('tbf-pv-ok-text').textContent = D.okText.replace('%s', (j.data && j.data.pretty) || D.pretty);
				cardCode.hidden = true; cardPhone.hidden = true; ok.hidden = false;
				return;
			}
			otp.classList.add('is-invalid');
			err(cardCode, (j && j.data && j.data.message) || D.codeErr);
		});
	}
	codeForm.addEventListener('submit', function (e) { e.preventDefault(); submitCode(); });
	phoneForm.addEventListener('submit', function (e) {
		e.preventDefault();
		var v = $('tbf-pv-phone-in').value.trim();
		if (!v) { err(cardPhone, D.empty); return; }
		send(v, phoneForm.querySelector('button[type=submit]'));
	});
	resendBtn.addEventListener('click', function () { send('', resendBtn); });
	$('tbf-pv-change').addEventListener('click', function () { clearInterval(timer); resendBtn.disabled = false; resendBtn.textContent = D.resend; showPhone(''); });

	if (D.phone) {
		if (D.auto) { send('', null); } else { showCode(D.pretty); countdown(); }
	}
}());
</script>
<?php
get_footer();
