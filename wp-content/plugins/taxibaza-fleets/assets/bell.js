/* TaxiBaza Fleets — attaches a notifications dropdown to the theme's header bell. */
(function () {
	'use strict';
	var D = window.tbfBell;
	if (!D) { return; }
	var bells = Array.prototype.slice.call(document.querySelectorAll('.tb-header__bell'));
	if (!bells.length) { return; }
	var I = D.i18n, items = D.items || [];
	var pop = document.createElement('div');
	pop.className = 'tbf-bell-pop'; pop.hidden = true; pop.setAttribute('role', 'dialog'); pop.setAttribute('aria-label', I.title);
	document.body.appendChild(pop);

	function unread() { return items.filter(function (n) { return !n.read; }).length; }
	function badge() {
		bells.forEach(function (b) {
			var el = b.querySelector('.tbf-bell-badge'), c = unread();
			if (!c) { if (el) { el.remove(); } return; }
			if (!el) { el = document.createElement('span'); el.className = 'tbf-bell-badge'; b.appendChild(el); }
			el.textContent = c > 9 ? '9+' : String(c);
		});
	}
	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
	function render() {
		var h = '<div class="tbf-bell-pop__head"><p class="tbf-bell-pop__title">' + esc(I.title) + '</p>' +
			(unread() ? '<button type="button" class="tbf-bell-pop__all" data-all>' + esc(I.readAll) + '</button>' : '') + '</div>';
		if (!items.length) { h += '<p class="tbf-bell-pop__empty">' + esc(I.empty) + '</p>'; }
		var group = null;
		items.forEach(function (n) {
			var g = n.today ? I.today : I.earlier;
			if (g !== group) { h += '<p class="tbf-bell-pop__group">' + esc(g) + '</p>'; group = g; }
			h += '<a class="tbf-bell-item' + (n.read ? '' : ' tbf-bell-item--unread') + '" href="' + esc(n.url) + '" data-id="' + esc(n.id) + '">' +
				'<span class="tbf-bell-item__t">' + esc(n.t) + '<span class="tbf-bell-item__when">' + esc(n.when) + '</span></span>' +
				'<span class="tbf-bell-item__d" style="display:block">' + esc(n.d) + '</span></a>';
		});
		pop.innerHTML = h;
	}
	function send(id) {
		var body = new FormData(); body.append('action', 'tbf_notifications_read'); body.append('nonce', D.nonce); body.append('id', id || '');
		return fetch(D.ajax, { method: 'POST', credentials: 'same-origin', body: body, keepalive: true }).catch(function () {});
	}
	function place(b) {
		var r = b.getBoundingClientRect(), w = pop.offsetWidth || 420;
		pop.style.top = (r.bottom + 12) + 'px';
		pop.style.left = Math.max(16, Math.min(window.innerWidth - w - 16, r.right - w)) + 'px';
	}
	var current = null;
	function open(b) { render(); pop.hidden = false; place(b); current = b; b.setAttribute('aria-expanded', 'true'); }
	function close() { pop.hidden = true; if (current) { current.setAttribute('aria-expanded', 'false'); } current = null; }

	bells.forEach(function (b) {
		b.setAttribute('aria-haspopup', 'dialog'); b.setAttribute('aria-expanded', 'false');
		b.addEventListener('click', function (e) { e.preventDefault(); if (pop.hidden) { open(b); } else { close(); } });
	});
	pop.addEventListener('click', function (e) {
		var all = e.target.closest('[data-all]');
		if (all) { items.forEach(function (n) { n.read = true; }); send(''); render(); badge(); return; }
		var a = e.target.closest('.tbf-bell-item');
		if (a) { var id = a.getAttribute('data-id'); items.forEach(function (n) { if (n.id === id) { n.read = true; } }); send(id); }
	});
	document.addEventListener('click', function (e) { if (!pop.hidden && !pop.contains(e.target) && !e.target.closest('.tb-header__bell')) { close(); } });
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
	window.addEventListener('resize', function () { if (current) { place(current); } });
	badge();
}());
