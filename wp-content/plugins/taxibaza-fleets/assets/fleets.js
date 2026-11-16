/* TaxiBaza Fleets — public fleet pages: filter dropdowns + job application popup. */
(function () {
	'use strict';

	var cfg = window.tbfFleets || {};

	/* ---------- Dropdowns (<details data-tbf-dd>) ---------- */
	var dds = Array.prototype.slice.call(document.querySelectorAll('[data-tbf-dd]'));
	dds.forEach(function (dd) {
		dd.addEventListener('toggle', function () {
			if (dd.open) {
				dds.forEach(function (o) { if (o !== dd) { o.open = false; } });
			}
		});
	});
	document.addEventListener('click', function (e) {
		dds.forEach(function (dd) { if (dd.open && !dd.contains(e.target)) { dd.open = false; } });
	});

	/* ---------- Popup ---------- */
	var wrap = document.getElementById('tbf-apply');
	if (!wrap) { return; }
	var dialog = wrap.querySelector('.tbf-dialog');
	var form = wrap.querySelector('[data-tbf-form]');
	var done = wrap.querySelector('[data-tbf-done]');
	var banner = wrap.querySelector('[data-tbf-banner]');
	var bannerText = wrap.querySelector('[data-tbf-banner-text]');
	var submit = wrap.querySelector('[data-tbf-submit]');
	var lastFocus = null;
	var MIN = 20;

	function field(name) { return form.querySelector('[data-field="' + name + '"]'); }

	function setError(name, text) {
		var f = field(name);
		if (!f) { return; }
		var e = f.querySelector('[data-err]');
		f.classList.toggle('has-error', !!text);
		if (e) { e.textContent = text || ''; }
	}

	function clearErrors() {
		Array.prototype.forEach.call(form.querySelectorAll('.tbf-field'), function (f) {
			f.classList.remove('has-error');
			var e = f.querySelector('[data-err]');
			if (e) { e.textContent = ''; }
		});
		banner.hidden = true;
	}

	function showBanner(text) {
		bannerText.textContent = text;
		banner.hidden = false;
		dialog.scrollIntoView({ block: 'start' });
	}

	function syncSelect() {
		var s = form.querySelector('select[name="city"]');
		if (s) { s.classList.toggle('is-empty', !s.value); }
	}

	function open(btn) {
		lastFocus = btn;
		clearErrors();
		form.hidden = false;
		done.hidden = true;
		form.elements.namedItem('fleet').value = btn.getAttribute('data-fleet') || '';
		wrap.setAttribute('data-name', btn.getAttribute('data-name') || '');
		// Car options: only the models this fleet offers; preselect when there is one.
		var models = (btn.getAttribute('data-models') || '').split(',').filter(Boolean);
		var opts = form.querySelectorAll('[data-model]');
		Array.prototype.forEach.call(opts, function (o) {
			var input = o.querySelector('input');
			var on = !models.length || models.indexOf(o.getAttribute('data-model')) !== -1;
			o.hidden = !on;
			input.disabled = !on;
			input.checked = on && models.length === 1;
		});
		syncSelect();
		wrap.hidden = false;
		document.body.classList.add('tbf-modal-open');
		dialog.focus();
	}

	function close() {
		wrap.hidden = true;
		document.body.classList.remove('tbf-modal-open');
		if (lastFocus) { lastFocus.focus(); }
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('[data-tbf-apply]') : null;
		if (btn) { e.preventDefault(); open(btn); return; }
		if (!wrap.hidden && e.target.closest && e.target.closest('[data-tbf-close]')) { e.preventDefault(); close(); }
	});
	document.addEventListener('keydown', function (e) {
		if (wrap.hidden) { return; }
		if (e.key === 'Escape') { close(); return; }
		if (e.key === 'Tab') { // Keep focus inside the dialog.
			var f = dialog.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]):not([type="hidden"]):not([tabindex="-1"]),select,textarea');
			var list = Array.prototype.filter.call(f, function (el) { return el.offsetParent !== null; });
			if (!list.length) { return; }
			var first = list[0], last = list[list.length - 1];
			if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
			else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
		}
	});
	form.addEventListener('change', function (e) {
		if (e.target.name === 'city') { syncSelect(); }
		var f = e.target.closest('.tbf-field');
		if (f && f.classList.contains('has-error')) { setError(f.getAttribute('data-field'), ''); }
	});

	/* Client-side check mirrors Work_Request::validate(); the server checks again. */
	function check() {
		var el = form.elements, errs = {};
		var pick = form.getAttribute('data-err-pick');
		if (!form.querySelector('input[name="car"]:checked')) { errs.car = pick; }
		if (!form.querySelector('input[name="license"]:checked')) { errs.license = pick; }
		if (!el.city.value) { errs.city = pick; }
		if (el.namedItem('name').value.trim().length < 2) { errs.name = form.getAttribute('data-err-required'); }
		var phone = el.phone.value.replace(/[^\d+ ]/g, '').trim();
		if (!phone) { errs.phone = form.getAttribute('data-err-required'); }
		else if (!/^\+?\d[\d ]{7,18}$/.test(phone)) { errs.phone = form.getAttribute('data-err-phone'); }
		var email = el.email.value.trim();
		if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { errs.email = form.getAttribute('data-err-email'); }
		var msg = el.message.value.trim();
		if (!msg) { errs.message = form.getAttribute('data-err-required'); }
		else if (msg.length < MIN) { errs.message = form.getAttribute('data-err-short'); }
		return errs;
	}

	function showErrors(errs) {
		Object.keys(errs).forEach(function (k) { setError(k, errs[k]); });
	}

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		clearErrors();
		var errs = check();
		if (Object.keys(errs).length) {
			showErrors(errs);
			showBanner((cfg.i18n && cfg.i18n.banner) || '');
			return;
		}
		submit.disabled = true;
		var body = new FormData(form);
		fetch(cfg.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (r) { return r.json().catch(function () { return { success: false, data: {} }; }); })
			.then(function (res) {
				submit.disabled = false;
				var data = (res && res.data) || {};
				if (res && res.success) {
					wrap.querySelector('[data-tbf-done-title]').textContent = data.title || '';
					wrap.querySelector('[data-tbf-done-text]').textContent = data.text || '';
					form.hidden = true;
					done.hidden = false;
					form.elements.namedItem('message').value = '';
					dialog.focus();
					return;
				}
				if (data.errors) { showErrors(data.errors); }
				showBanner(data.message || (cfg.i18n && cfg.i18n.server) || '');
			})
			.catch(function () {
				submit.disabled = false;
				showBanner((cfg.i18n && cfg.i18n.server) || '');
			});
	});
}());
