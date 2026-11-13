/* TaxiBaza Fleets — phone photo picker for the add/edit listing form (see class-listing-photos.php). */
(function () {
	'use strict';
	var D = window.tbfPhotos;
	var grid = document.getElementById('tb-al-gallery-grid');
	var input = document.getElementById('tb-al-gallery');
	var addBtn = document.getElementById('tb-al-gallery-add');
	if (!D || !grid || !input || !addBtn) { return; }
	var I = D.i18n;

	function isPhone() {
		var coarse = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
		return coarse || window.innerWidth <= 820 || /[?&]tbf_simple_photos=1/.test(location.search);
	}

	var session = Array.prototype.map.call((window.crypto || window.msCrypto).getRandomValues(new Uint8Array(16)), function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
	var pending = 0;

	// Hidden native picker: several photos, any image incl. HEIC.
	var picker = document.createElement('input');
	picker.type = 'file';
	picker.multiple = true;
	picker.accept = 'image/*,.heic,.heif';
	picker.hidden = true;
	picker.setAttribute('aria-hidden', 'true');
	document.body.appendChild(picker);

	// Error box under the gallery.
	var box = document.createElement('div');
	box.className = 'tbf-photos-err';
	box.setAttribute('role', 'alert');
	box.hidden = true;
	grid.parentNode.insertBefore(box, grid.nextSibling);
	function showError(name, msg, cls) {
		var row = document.createElement('p');
		if (cls) { row.className = cls; }
		row.textContent = (name ? name + ': ' : '') + msg;
		box.appendChild(row);
		box.hidden = false;
	}
	function clearWait() {
		box.querySelectorAll('.tbf-wait').forEach(function (r) { r.remove(); });
		if (!box.children.length) { box.hidden = true; }
	}
	box.addEventListener('click', function (e) { if (e.target === box) { box.textContent = ''; box.hidden = true; } });

	function sync() {
		var ids = [];
		grid.querySelectorAll('.tb-fa-gallery__item[data-id]').forEach(function (el) { ids.push(el.dataset.id); });
		input.value = ids.join(',');
		input.dispatchEvent(new Event('change', { bubbles: true })); // marks the form as changed
	}

	/* Reduce a photo on the phone: max side D.maxSide, JPEG. Null = the browser can't read it (server converts). */
	function shrink(file) {
		return new Promise(function (resolve) {
			if (!/^image\//.test(file.type) && !/\.(heic|heif)$/i.test(file.name)) { resolve(null); return; }
			var url = URL.createObjectURL(file);
			var img = new Image();
			img.onload = function () {
				var w = img.naturalWidth, h = img.naturalHeight;
				if (!w || !h) { URL.revokeObjectURL(url); resolve(null); return; }
				var scale = Math.min(1, D.maxSide / Math.max(w, h));
				if (scale === 1 && file.type === 'image/jpeg' && file.size <= 1.5 * 1024 * 1024) { URL.revokeObjectURL(url); resolve(file); return; }
				var c = document.createElement('canvas');
				c.width = Math.round(w * scale); c.height = Math.round(h * scale);
				var ctx = c.getContext('2d');
				ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height); // PNG transparency → white
				ctx.drawImage(img, 0, 0, c.width, c.height);
				URL.revokeObjectURL(url);
				if (!c.toBlob) { resolve(null); return; }
				c.toBlob(function (blob) { resolve(blob && blob.size ? blob : null); }, 'image/jpeg', 0.85);
			};
			img.onerror = function () { URL.revokeObjectURL(url); resolve(null); };
			img.src = url;
		});
	}

	function tile() {
		var t = document.createElement('div');
		t.className = 'tb-fa-gallery__item tbf-photos-tile';
		t.innerHTML = '<span class="tbf-photos-tile__bar"><i></i></span><span class="tbf-photos-tile__txt"></span>';
		t.querySelector('.tbf-photos-tile__txt').textContent = I.uploading;
		grid.insertBefore(t, addBtn);
		return t;
	}

	function upload(file) {
		var t = tile();
		pending++;
		function done() { pending--; if (!pending) { clearWait(); } }
		function fail(msg) { t.remove(); showError(file.name, msg); done(); }
		if (file.size > D.maxSize * 3) { fail(I.size); return Promise.resolve(); }
		return shrink(file).then(function (small) {
			var body = small || file;
			if (body.size > D.maxSize) { fail(I.size); return; }
			var name = small && small !== file ? file.name.replace(/\.[^.]+$/, '') + '.jpg' : file.name;
			var fd = new FormData();
			fd.append('action', D.action);
			fd.append('nonce', D.nonce);
			fd.append('tb_session', session);
			fd.append('file', body, name);
			return new Promise(function (resolve) {
				var xhr = new XMLHttpRequest();
				xhr.open('POST', D.ajax);
				xhr.withCredentials = true;
				xhr.upload.onprogress = function (e) {
					if (e.lengthComputable) { t.querySelector('.tbf-photos-tile__bar i').style.width = Math.round(e.loaded / e.total * 100) + '%'; }
				};
				xhr.onload = function () {
					var j = null;
					try { j = JSON.parse(xhr.responseText); } catch (e) { j = null; }
					if (j && j.success && j.data && j.data.id) {
						var item = document.createElement('div');
						item.className = 'tb-fa-gallery__item';
						item.dataset.id = j.data.id;
						var im = document.createElement('img'); im.src = j.data.thumb; im.alt = '';
						var rm = document.createElement('button'); rm.type = 'button'; rm.className = 'tb-fa-gallery__remove'; rm.setAttribute('aria-label', I.remove); rm.textContent = '×';
						item.appendChild(im); item.appendChild(rm);
						grid.replaceChild(item, t);
						sync(); done();
					} else {
						fail((j && j.data && j.data.message) || (xhr.status === 413 ? I.size : I.network));
					}
					resolve();
				};
				xhr.onerror = function () { fail(I.network); resolve(); };
				xhr.ontimeout = function () { fail(I.network); resolve(); };
				xhr.timeout = 120000;
				xhr.send(fd);
			});
		});
	}

	picker.addEventListener('change', function () {
		var files = Array.prototype.slice.call(picker.files || []);
		picker.value = '';
		box.textContent = ''; box.hidden = true;
		// Two at a time: quick, and light on the phone's memory.
		var queue = files.slice();
		function next() { var f = queue.shift(); return f ? upload(f).then(next) : Promise.resolve(); }
		next(); next();
	});

	// Phones: the button opens the phone's picker instead of the media window.
	document.addEventListener('click', function (e) {
		if (!isPhone() || !e.target.closest || !e.target.closest('#tb-al-gallery-add')) { return; }
		e.preventDefault();
		e.stopImmediatePropagation();
		picker.click();
	}, true);

	// Don't save while photos are still uploading.
	document.addEventListener('click', function (e) {
		var b = e.target.closest && e.target.closest('#tb-al-btn-draft, #tb-al-btn-publish, #tb-al-btn-draft-foot, #tb-al-btn-publish-foot, #tb-exit-save');
		if (!b || !pending) { return; }
		e.preventDefault();
		e.stopImmediatePropagation();
		clearWait(); showError('', I.wait, 'tbf-wait');
		box.scrollIntoView({ block: 'center', behavior: 'smooth' });
	}, true);
}());
