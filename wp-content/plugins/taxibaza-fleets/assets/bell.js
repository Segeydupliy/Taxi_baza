/* TaxiBaza Fleets — notifications dropdown on the theme's header bell (Figma "Сповіщення"). */
(function () {
	'use strict';
	var D = window.tbfBell;
	if (!D) { return; }
	var bells = Array.prototype.slice.call(document.querySelectorAll('.tb-header__bell'));
	if (!bells.length) { return; }
	var I = D.i18n, items = D.items || [], tab = 'all';
	var ICON_SYS = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5" stroke-linecap="round"/><circle cx="12" cy="16.5" r="1.1" fill="currentColor" stroke="none"/></svg>';
	var ICON_REQ = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 6 8.5-6"/></svg>';
	var ICON_X = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>';

	var pop = document.createElement('div');
	pop.className = 'tbf-bell-pop'; pop.hidden = true; pop.setAttribute('role', 'dialog'); pop.setAttribute('aria-label', I.title);
	document.body.appendChild(pop);

	function unread() { return items.filter(function (n) { return !n.read; }).length; }
	function badge() {
		bells.forEach(function (b) {
			var el = b.querySelector('.tbf-bell-badge');
			if (!unread()) { if (el) { el.remove(); } return; }
			if (!el) { el = document.createElement('span'); el.className = 'tbf-bell-badge'; el.setAttribute('aria-hidden', 'true'); b.appendChild(el); }
		});
	}
	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
	function render() {
		var list = items.filter(function (n) { return 'all' === tab || ('req' === tab ? 'req' === n.cat : 'sys' === n.cat); });
		var h = '<div class="tbf-bell-pop__head"><p class="tbf-bell-pop__title">' + esc(I.title) + '</p>' +
			'<button type="button" class="tbf-bell-pop__close" data-close aria-label="' + esc(I.close) + '">' + ICON_X + '</button></div>' +
			'<div class="tbf-bell-pop__tabs" role="tablist">' +
			[['all', I.all], ['req', I.requests], ['sys', I.system]].map(function (t) {
				return '<button type="button" role="tab" class="tbf-bell-tab' + (t[0] === tab ? ' is-active' : '') + '" aria-selected="' + (t[0] === tab) + '" data-tab="' + t[0] + '">' + esc(t[1]) + '</button>';
			}).join('') + '</div>';
		if (!list.length) { h += '<p class="tbf-bell-pop__empty">' + esc(I.empty) + '</p>'; }
		var group = null, first = true;
		list.forEach(function (n) {
			if (n.day !== group) {
				h += '<div class="tbf-bell-pop__group"><span>' + esc(n.day) + '</span>' +
					(first && unread() ? '<button type="button" class="tbf-bell-pop__all" data-all>' + esc(I.readAll) + '</button>' : '') + '</div>';
				group = n.day; first = false;
			}
			var tag = n.url ? 'a' : 'div';
			h += '<' + tag + ' class="tbf-bell-item' + (n.read ? '' : ' tbf-bell-item--unread') + '"' + (n.url ? ' href="' + esc(n.url) + '"' : '') + ' data-id="' + esc(n.id) + '">' +
				'<span class="tbf-bell-item__icon">' + ('req' === n.cat ? ICON_REQ : ICON_SYS) + '</span>' +
				'<span class="tbf-bell-item__body"><span class="tbf-bell-item__t">' + esc(n.t) + '</span>' +
				'<span class="tbf-bell-item__d">' + esc(n.d) + '</span></span>' +
				'<span class="tbf-bell-item__when">' + esc(n.when) + '</span></' + tag + '>';
		});
		pop.innerHTML = h;
	}
	function send(id) {
		var body = new FormData(); body.append('action', 'tbf_notifications_read'); body.append('nonce', D.nonce); body.append('id', id || '');
		return fetch(D.ajax, { method: 'POST', credentials: 'same-origin', body: body, keepalive: true }).catch(function () {});
	}
	function place(b) {
		var r = b.getBoundingClientRect(), w = pop.offsetWidth || 516;
		pop.style.top = (r.bottom + 16) + 'px';
		pop.style.left = Math.max(16, Math.min(window.innerWidth - w - 16, r.right - w + 120)) + 'px';
	}
	var current = null;
	function open(b) { render(); pop.hidden = false; place(b); current = b; b.setAttribute('aria-expanded', 'true'); }
	function close() { pop.hidden = true; if (current) { current.setAttribute('aria-expanded', 'false'); } current = null; }

	bells.forEach(function (b) {
		b.setAttribute('aria-haspopup', 'dialog'); b.setAttribute('aria-expanded', 'false');
		b.addEventListener('click', function (e) { e.preventDefault(); if (pop.hidden) { open(b); } else { close(); } });
	});
	pop.addEventListener('click', function (e) {
		if (e.target.closest('[data-close]')) { close(); return; }
		var t = e.target.closest('[data-tab]');
		if (t) { tab = t.getAttribute('data-tab'); render(); return; }
		if (e.target.closest('[data-all]')) { items.forEach(function (n) { n.read = true; }); send(''); render(); badge(); return; }
		var a = e.target.closest('.tbf-bell-item');
		if (a) {
			var id = a.getAttribute('data-id');
			items.forEach(function (n) { if (n.id === id) { n.read = true; } });
			send(id); badge();
			if ('A' !== a.tagName) { render(); }
		}
	});
	// composedPath(): a tab click re-renders the popup, so e.target is already detached from it.
	document.addEventListener('click', function (e) { var path = e.composedPath ? e.composedPath() : []; if (!pop.hidden && path.indexOf(pop) < 0 && !pop.contains(e.target) && !(e.target.closest && e.target.closest('.tb-header__bell'))) { close(); } });
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
	window.addEventListener('resize', function () { if (current) { place(current); } });
	badge();
}());
