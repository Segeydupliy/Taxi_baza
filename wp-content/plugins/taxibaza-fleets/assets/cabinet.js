/* TaxiBaza Fleets — cabinet profile (view + form). No dependencies. */
(function () {
	'use strict';
	var I = (window.tbfCabinet && window.tbfCabinet.i18n) || {};
	var q = function (s, r) { return (r || document).querySelector(s); };
	var qa = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

	/* modal after save */
	var modal = q('#tbf-modal');
	if (modal) {
		var close = function () { modal.hidden = true; };
		q('#tbf-modal-close').addEventListener('click', close);
		modal.addEventListener('click', function (e) { if (e.target === modal) { close(); } });
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
		q('#tbf-modal-close').focus();
	}

	/* view: description clamp */
	var d = q('#tbf-desc'), more = q('#tbf-more');
	if (d && more) {
		var need = function () {
			if (d.classList.contains('is-open')) { return; }
			d.classList.remove('is-short');
			var short = d.scrollHeight <= d.clientHeight + 2;
			d.classList.toggle('is-short', short);
			more.hidden = short;
		};
		more.addEventListener('click', function () {
			var o = d.classList.toggle('is-open');
			more.setAttribute('aria-expanded', o);
			more.textContent = o ? (I.collapse || '−') : (I.readMore || '+');
		});
		need();
		window.addEventListener('resize', need);
	}

	var form = q('#tbf-profile');
	if (!form) { return; }

	function syncDeps() {
		qa('[data-show-if]', form).forEach(function (el) {
			var src = document.getElementById(el.getAttribute('data-show-if'));
			el.hidden = !(src && src.checked);
		});
		var f = q('#p-freq').value, day = q('#p-day-f');
		day.hidden = !(f === 'weekly' || f === 'biweekly');
		var t = q('#o-pay').value, lab = q('#o-fee-l');
		if (lab && lab.firstChild) { lab.firstChild.nodeValue = t === 'fixed' ? I.feeFixed : I.feePct; }
		q('#o-base-f').hidden = t !== 'percent';
	}
	form.addEventListener('change', syncDeps);
	syncDeps();

	function hiddenByDeps(el) { return !!el.closest('[hidden]'); }
	function setErr(field, msg) {
		field.classList.toggle('tbf-field--invalid', !!msg);
		var p = q(':scope > .tbf-error', field);
		if (!p && msg) { p = document.createElement('p'); p.className = 'tbf-error'; field.appendChild(p); }
		if (p) { p.textContent = msg || ''; p.hidden = !msg; }
	}
	function num(v) { return parseFloat(String(v).replace(',', '.')); }

	function validate() {
		var first = null;
		function bad(field, msg) { setErr(field, msg); if (!first) { first = field; } }
		qa('.tbf-field', form).forEach(function (f) { setErr(f, ''); });
		var name = q('#f-name');
		if (name.value.trim().length < 2) { bad(name.closest('.tbf-field'), I.required); }
		var ph = q('#f-phone');
		if (ph.value.trim() && !/^\+?\d[\d ]{7,18}$/.test(ph.value.trim())) { bad(ph.closest('.tbf-field'), I.phone); }
		['platforms', 'langs', 'models', 'paymethod', 'docs', 'svcwhat'].forEach(function (g) {
			var el = q('[data-group="' + g + '"]', form);
			if (el && !hiddenByDeps(el) && !qa('input:checked', el).length) { bad(el, I.pickOne); }
		});
		var ranges = { 'o-fee': [0, q('#o-pay').value === 'fixed' ? 100000 : 100], 'r-price': [1, 100000], 'r-comm': [0, 100], 'r-dep': [0, 100000], 'r-min': [1, 1000], 'k-fuel-v': [0, 100], 'k-svc-v': [0, 100] };
		Object.keys(ranges).forEach(function (id) {
			var i = document.getElementById(id);
			if (!i || hiddenByDeps(i)) { return; }
			var n = num(i.value);
			if (isNaN(n) || n < ranges[id][0] || n > ranges[id][1]) { bad(i.closest('.tbf-field'), i.value.trim() ? I.number : I.required); }
		});
		if (first) { first.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
		return !first;
	}

	var sending = false;
	form.addEventListener('submit', function (e) {
		if (sending || !validate()) { e.preventDefault(); return; }
		sending = true;
		var b = q('#tbf-save'); if (b) { b.disabled = true; }
	});

	/* logo preview / delete */
	var file = q('#tbf-logo-file'), pic = q('#tbf-logo-pic'), del = q('#tbf-logo-delete');
	var orig = pic ? pic.innerHTML : '';
	if (file) {
		file.addEventListener('change', function () {
			var f = file.files[0]; if (!f) { return; }
			if (!/^image\/(jpeg|png|webp)$/.test(f.type) || f.size > 5 * 1024 * 1024) { file.value = ''; window.alert(I.logo); return; }
			var img = new Image(); img.alt = ''; img.src = URL.createObjectURL(f);
			pic.innerHTML = ''; pic.appendChild(img); del.value = '';
		});
		q('#tbf-logo-del').addEventListener('click', function () {
			file.value = ''; del.value = '1';
			pic.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9a9a9a" stroke-width="1.5" aria-hidden="true"><path d="M4 8h3l1.5-2h7L17 8h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg>';
		});
	}
}());
