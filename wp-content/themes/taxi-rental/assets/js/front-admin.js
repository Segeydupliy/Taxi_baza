/* front-admin.js — TaxiBaza Front-End Admin Panel */
(function () {
	'use strict';

	var cfg     = window.tbFrontAdmin || {};
	var ajaxUrl = cfg.ajaxUrl || '';
	var nonce   = cfg.nonce   || '';
	var i18n    = cfg.i18n    || {};

	// ── Delete-modal text interpolation ───────────────────────────
	// Fills a modal's [data-tpl="...%s..."] element with the specific item's
	// name so "Delete category?" becomes "Delete category "Bolt"?" — avoids
	// accidental deletes of the wrong item. Plain textContent, so the name
	// never needs escaping.
	function fillModalName(el, name) {
		if (!el) return;
		var tpl = el.dataset.tpl || '';
		el.textContent = name ? tpl.replace('%s', name) : tpl.replace('„%s“', '').replace('%s', '');
	}

	// ── Toast ────────────────────────────────────────────────────
	function showToast(msg, isError) {
		var el = document.getElementById('tb-fa-toast');
		if (!el) return;
		el.textContent = msg;
		el.classList.toggle('tb-fa-toast--error', !!isError);
		el.classList.add('is-visible');
		clearTimeout(el._timer);
		el._timer = setTimeout(function () {
			el.classList.remove('is-visible');
		}, 3200);
	}

	// ── Success notification (Figma 410:7811) ────────────────────
	// The dark toast above is the error treatment; anything that succeeded
	// gets the plate with the green check. Rebuilt on every call so the CSS
	// enter/exit animation replays from the start.
	function showNotify(msg) {
		var old = document.querySelector('.tb-fa-notify--js');
		if (old) { old.remove(); }

		var el = document.createElement('div');
		el.className = 'tb-fa-notify tb-fa-notify--js';
		el.setAttribute('role', 'status');
		el.setAttribute('aria-live', 'polite');

		var icon = document.createElement('div');
		icon.className = 'tb-fa-notify__icon';
		icon.setAttribute('aria-hidden', 'true');
		icon.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none">'
			+ '<path d="M20 6L9 17L4 12" stroke="black" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
			+ '</svg>';

		var text = document.createElement('span');
		text.className = 'tb-fa-notify__text';
		text.textContent = msg;

		el.appendChild(icon);
		el.appendChild(text);

		// Must live inside .tb-fadmin: every custom property the plate relies on
		// (--fa-header-h for its offset, --fa-accent, --fa-ink) is declared on
		// that container, not on :root. Appended to <body> the top/calc() is
		// invalid, the declaration is dropped and the plate renders off-screen.
		(document.querySelector('.tb-fadmin') || document.body).appendChild(el);

		// Matches the CSS animation: visible 3.7s, then a 0.3s fade out.
		setTimeout(function () { el.remove(); }, 4200);
	}

	// ── AJAX POST helper ─────────────────────────────────────────
	function sendAction(action, postId, extra, callback) {
		var body = new URLSearchParams({ action: action, post_id: postId, nonce: nonce });
		if (extra) {
			Object.keys(extra).forEach(function (k) { body.append(k, extra[k]); });
		}
		fetch(ajaxUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
		})
			.then(function (r) { return r.json(); })
			.then(function (data) { callback(null, data); })
			.catch(function (err) { callback(err, null); });
	}

	// ── Remove card with fade ────────────────────────────────────
	function fadeRemoveCard(cardEl, msg) {
		if (!cardEl) return;
		cardEl.style.transition = 'opacity 0.3s, transform 0.3s';
		cardEl.style.opacity    = '0';
		cardEl.style.transform  = 'scale(0.98)';
		showNotify(msg);
		setTimeout(function () {
			cardEl.remove();
			// Update pending count badge.
			updatePendingBadge(-1);
		}, 320);
	}

	function updatePendingBadge(delta) {
		var badge = document.querySelector('.tb-fa-section-badge');
		// The card is tagged in the markup. Picking it by index (it used to be
		// hardcoded to the 3rd) decremented the "Users" counter instead.
		var pendEl = document.querySelector('[data-fa-stat="pending"]');
		if (badge) {
			var cur = parseInt(badge.textContent, 10) || 0;
			var next = Math.max(0, cur + delta);
			badge.textContent = next;
			if (next === 0) badge.style.display = 'none';
		}
		if (pendEl) {
			var cur2 = parseInt(pendEl.textContent.replace(/\s/g, ''), 10) || 0;
			pendEl.textContent = Math.max(0, cur2 + delta);
		}
	}

	// ── Publish action ───────────────────────────────────────────
	function bindPublish(btn) {
		btn.addEventListener('click', function () {
			var card   = btn.closest('[data-listing-id]');
			var postId = card ? card.dataset.listingId : 0;
			if (!postId) return;
			btn.disabled = true;
			sendAction('tb_fa_publish', postId, null, function (err, data) {
				if (err || !data || !data.success) {
					showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
					btn.disabled = false;
					return;
				}
				fadeRemoveCard(card, i18n.published || 'Zverejnené');
			});
		});
	}

	// ── Archive action ───────────────────────────────────────────
	function bindArchive(btn) {
		btn.addEventListener('click', function () {
			var card   = btn.closest('[data-listing-id]');
			var postId = card ? card.dataset.listingId : 0;
			if (!postId) return;
			btn.disabled = true;
			sendAction('tb_fa_archive', postId, null, function (err, data) {
				if (err || !data || !data.success) {
					showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
					btn.disabled = false;
					return;
				}
				fadeRemoveCard(card, i18n.archived || 'Archivované');
			});
		});
	}

	// ── Decline modal ────────────────────────────────────────────
	var modal         = document.getElementById('tb-fa-modal-decline');
	var backdrop      = document.getElementById('tb-fa-modal-backdrop');
	var reasonInput   = document.getElementById('tb-fa-decline-reason');
	var cancelBtn     = document.getElementById('tb-fa-decline-cancel');
	var confirmBtn    = document.getElementById('tb-fa-decline-confirm');
	var pendingPostId = 0;

	function openModal(postId) {
		pendingPostId = postId;
		if (reasonInput) reasonInput.value = '';
		if (modal) {
			modal.removeAttribute('hidden');
			document.body.style.overflow = 'hidden';
			if (reasonInput) reasonInput.focus();
		}
	}

	function closeModal() {
		if (modal) {
			modal.setAttribute('hidden', '');
			document.body.style.overflow = '';
		}
		pendingPostId = 0;
	}

	function bindReject(btn) {
		btn.addEventListener('click', function () {
			var card   = btn.closest('[data-listing-id]');
			var postId = card ? card.dataset.listingId : 0;
			if (!postId) return;
			openModal(postId);
		});
	}

	if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
	if (backdrop)  backdrop.addEventListener('click', closeModal);

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && modal && !modal.hasAttribute('hidden')) {
			closeModal();
		}
	});

	if (confirmBtn) {
		confirmBtn.addEventListener('click', function () {
			if (!pendingPostId) return;
			var reason = reasonInput ? reasonInput.value.trim() : '';
			confirmBtn.disabled = true;
			sendAction('tb_fa_reject', pendingPostId, { rejection_reason: reason }, function (err, data) {
				confirmBtn.disabled = false;
				if (err || !data || !data.success) {
					showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
					return;
				}
				var card = document.querySelector('[data-listing-id="' + pendingPostId + '"]');
				closeModal();
				fadeRemoveCard(card, i18n.rejected || 'Zamietnuté');
			});
		});
	}

	// ── Pending action ───────────────────────────────────────────
	function bindPending(btn) {
		btn.addEventListener('click', function () {
			var card   = btn.closest('[data-listing-id]');
			var postId = card ? card.dataset.listingId : 0;
			var redir  = btn.dataset.redirect || null;
			if (!postId) return;
			btn.disabled = true;
			sendAction('tb_fa_pending', postId, null, function (err, data) {
				if (err || !data || !data.success) {
					showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
					btn.disabled = false;
					return;
				}
				if (redir) { window.location.href = redir; return; }
				fadeRemoveCard(card, i18n.pending || 'Na posúdenie');
			});
		});
	}

	// ── Delete modal ─────────────────────────────────────────────
	var deleteModal    = document.getElementById('tb-fa-modal-delete');
	var deleteBdrop    = document.getElementById('tb-fa-delete-backdrop');
	var deleteCancelBtn= document.getElementById('tb-fa-delete-cancel');
	var deleteConfBtn  = document.getElementById('tb-fa-delete-confirm');
	var deletePostId   = 0;

	function openDeleteModal(postId, name) {
		deletePostId = postId;
		if (deleteModal) {
			fillModalName(document.getElementById('tb-fa-modal-delete-title'), name);
			deleteModal.removeAttribute('hidden');
			document.body.style.overflow = 'hidden';
		}
	}

	function closeDeleteModal() {
		if (deleteModal) {
			deleteModal.setAttribute('hidden', '');
			document.body.style.overflow = '';
		}
		deletePostId = 0;
	}

	function bindDelete(btn) {
		btn.addEventListener('click', function () {
			var card    = btn.closest('[data-listing-id]');
			var postId  = card ? card.dataset.listingId : 0;
			var titleEl = card ? card.querySelector('.tb-fa-mod-card__title') : null;
			if (!postId) return;
			openDeleteModal(postId, titleEl ? titleEl.textContent.trim() : '');
		});
	}

	if (deleteCancelBtn) deleteCancelBtn.addEventListener('click', closeDeleteModal);
	if (deleteBdrop)     deleteBdrop.addEventListener('click', closeDeleteModal);

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && deleteModal && !deleteModal.hasAttribute('hidden')) {
			closeDeleteModal();
		}
	});

	if (deleteConfBtn) {
		deleteConfBtn.addEventListener('click', function () {
			if (!deletePostId) return;
			deleteConfBtn.disabled = true;
			sendAction('tb_fa_delete', deletePostId, null, function (err, data) {
				deleteConfBtn.disabled = false;
				if (err || !data || !data.success) {
					showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
					return;
				}
				var card = document.querySelector('[data-listing-id="' + deletePostId + '"]');
				closeDeleteModal();
				fadeRemoveCard(card, i18n.deleted || 'Odstránené');
			});
		});
	}

	// ── Archive/Publish with redirect (edit page buttons) ────────
	// Buttons on tab-advert-edit.php carry data-redirect attribute.
	function bindArchiveWithRedirect(btn) {
		btn.addEventListener('click', function () {
			var wrap   = btn.closest('[data-listing-id]');
			var postId = wrap ? wrap.dataset.listingId : 0;
			var redir  = btn.dataset.redirect || null;
			if (!postId) return;
			btn.disabled = true;
			sendAction('tb_fa_archive', postId, null, function (err, data) {
				if (err || !data || !data.success) {
					showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
					btn.disabled = false;
					return;
				}
				if (redir) { window.location.href = redir; return; }
				fadeRemoveCard(wrap, i18n.archived || 'Archivované');
			});
		});
	}

	function bindPublishWithRedirect(btn) {
		btn.addEventListener('click', function () {
			var wrap   = btn.closest('[data-listing-id]');
			var postId = wrap ? wrap.dataset.listingId : 0;
			var redir  = btn.dataset.redirect || null;
			if (!postId) return;
			btn.disabled = true;
			sendAction('tb_fa_publish', postId, null, function (err, data) {
				if (err || !data || !data.success) {
					showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
					btn.disabled = false;
					return;
				}
				if (redir) { window.location.href = redir; return; }
				fadeRemoveCard(wrap, i18n.published || 'Zverejnené');
			});
		});
	}

	// ── Block user modal ─────────────────────────────────────────
	var blockModal     = document.getElementById('tb-fa-modal-block');
	var blockBdrop     = document.getElementById('tb-fa-block-backdrop');
	var blockCancelBtn = document.getElementById('tb-fa-block-cancel');
	var blockConfBtn   = document.getElementById('tb-fa-block-confirm');
	var blockUserId    = 0;

	function openBlockModal(uid) {
		blockUserId = uid;
		if (blockModal) {
			blockModal.removeAttribute('hidden');
			document.body.style.overflow = 'hidden';
		}
	}

	function closeBlockModal() {
		if (blockModal) {
			blockModal.setAttribute('hidden', '');
			document.body.style.overflow = '';
		}
		blockUserId = 0;
	}

	function sendUserAction(action, userId, extra, callback) {
		var body = new URLSearchParams({ action: action, user_id: userId, nonce: nonce });
		if (extra) {
			Object.keys(extra).forEach(function (k) { body.append(k, extra[k]); });
		}
		fetch(ajaxUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
		})
			.then(function (r) { return r.json(); })
			.then(function (data) { callback(null, data); })
			.catch(function (err) { callback(err, null); });
	}

	function bindBlock(btn) {
		btn.addEventListener('click', function () {
			var card   = btn.closest('[data-user-id]');
			var userId = card ? card.dataset.userId : 0;
			if (!userId) return;
			openBlockModal(userId);
		});
	}

	function bindUnblock(btn) {
		btn.addEventListener('click', function () {
			var card   = btn.closest('[data-user-id]');
			var userId = card ? card.dataset.userId : 0;
			var redir  = btn.dataset.redirect || null;
			if (!userId) return;
			btn.disabled = true;
			sendUserAction('tb_fa_unblock_user', userId, null, function (err, data) {
				btn.disabled = false;
				if (err || !data || !data.success) {
					showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
					return;
				}
				showNotify(i18n.unblocked || 'Odblokované');
				if (redir) { window.location.href = redir; return; }
				setTimeout(function () { window.location.reload(); }, 600);
			});
		});
	}

	if (blockCancelBtn) blockCancelBtn.addEventListener('click', closeBlockModal);
	if (blockBdrop)     blockBdrop.addEventListener('click', closeBlockModal);

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && blockModal && !blockModal.hasAttribute('hidden')) {
			closeBlockModal();
		}
	});

	if (blockConfBtn) {
		blockConfBtn.addEventListener('click', function () {
			if (!blockUserId) return;
			blockConfBtn.disabled = true;
			sendUserAction('tb_fa_block_user', blockUserId, null, function (err, data) {
				blockConfBtn.disabled = false;
				if (err || !data || !data.success) {
					showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
					closeBlockModal();
					return;
				}
				closeBlockModal();
				showNotify(i18n.blocked || 'Zablokované');
				setTimeout(function () { window.location.reload(); }, 600);
			});
		});
	}

	// ── User edit form AJAX submit ────────────────────────────────
	var userEditForm = document.getElementById('tb-fa-user-edit-form');
	if (userEditForm) {
		userEditForm.addEventListener('submit', function (e) {
			e.preventDefault();
			var userId   = userEditForm.dataset.userId || 0;
			var redir    = userEditForm.dataset.redirect || null;
			var submitBtn = userEditForm.querySelector('[type="submit"]');
			if (submitBtn) submitBtn.disabled = true;

			var body = new URLSearchParams({ action: 'tb_fa_save_user', nonce: nonce });
			var fields = userEditForm.querySelectorAll('input, select, textarea');
			fields.forEach(function (f) {
				if (f.name) body.append(f.name, f.value);
			});

			fetch(ajaxUrl, {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
			})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (submitBtn) submitBtn.disabled = false;
					if (!data || !data.success) {
						showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
						return;
					}
					if (redir) {
						window.location.href = redir + '&saved=1';
					} else {
						showNotify(i18n.saved || 'Uložené');
					}
				})
				.catch(function () {
					if (submitBtn) submitBtn.disabled = false;
					showToast(i18n.error || 'Chyba', true);
				});
		});
	}

	// ── User avatar upload (wp.media) ─────────────────────────────
	var avatarUpload = document.getElementById('tb-ue-avatar-upload');
	if (avatarUpload && window.wp && wp.media) {
		var avatarIdInput = document.getElementById('tb-ue-avatar-id');
		var avatarCircle  = document.getElementById('tb-ue-avatar-circle');
		var avatarRemove  = document.getElementById('tb-ue-avatar-remove');
		var avatarFrame   = null;

		avatarUpload.addEventListener('click', function () {
			if (avatarFrame) { avatarFrame.open(); return; }
			avatarFrame = wp.media({
				title: i18n.mediaProfile || 'Fotografia profilu',
				button: { text: i18n.mediaChoose || 'Vybrať' },
				library: { type: 'image' },
				multiple: false,
			});
			avatarFrame.on('select', function () {
				var att = avatarFrame.state().get('selection').first().toJSON();
				var url = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
				if (avatarIdInput) avatarIdInput.value = att.id;
				if (avatarCircle) avatarCircle.innerHTML = '<img src="' + url + '" alt="" id="tb-ue-avatar-img">';
				if (avatarRemove) avatarRemove.classList.remove('is-hidden');
			});
			avatarFrame.open();
		});

		if (avatarRemove) {
			avatarRemove.addEventListener('click', function () {
				if (avatarIdInput) avatarIdInput.value = '';
				if (avatarCircle) {
					avatarCircle.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 9a2 2 0 0 1 2-2h1.5l1-1.5h5l1 1.5H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z" stroke="#999" stroke-width="1.5"/><circle cx="12.5" cy="13" r="3.5" stroke="#999" stroke-width="1.5"/></svg>';
				}
				avatarRemove.classList.add('is-hidden');
			});
		}
	}

	// ── Listing edit form (custom, Figma 1:1) ────────────────────
	var listingForm = document.getElementById('tb-fa-listing-form');
	if (listingForm) {
		var galGrid  = document.getElementById('tb-l-gallery-grid');
		var galInput = document.getElementById('tb-l-gallery');
		var galAdd   = document.getElementById('tb-l-gallery-add');

		var syncGallery = function () {
			if (!galGrid || !galInput) return;
			var ids = [];
			galGrid.querySelectorAll('.tb-fa-gallery__item').forEach(function (it) {
				if (it.dataset.id) ids.push(it.dataset.id);
			});
			galInput.value = ids.join(',');
		};

		var addGalleryItem = function (id, url) {
			if (galGrid.querySelector('.tb-fa-gallery__item[data-id="' + id + '"]')) return;
			var div = document.createElement('div');
			div.className = 'tb-fa-gallery__item';
			div.dataset.id = id;
			div.innerHTML = '<img src="' + url + '" alt=""><button type="button" class="tb-fa-gallery__remove" aria-label="×">&times;</button>';
			galGrid.insertBefore(div, galAdd);
			syncGallery();
		};

		// Remove (delegated)
		if (galGrid) {
			galGrid.addEventListener('click', function (e) {
				var btn = e.target.closest('.tb-fa-gallery__remove');
				if (!btn) return;
				var item = btn.closest('.tb-fa-gallery__item');
				if (item) { item.remove(); syncGallery(); }
			});
		}

		// Add via wp.media
		var galFrame;
		if (galAdd) {
			galAdd.addEventListener('click', function (e) {
				e.preventDefault();
				if (typeof wp === 'undefined' || !wp.media) return;
				if (!galFrame) {
					galFrame = wp.media({ title: i18n.mediaGallery || 'Fotografie', multiple: true, library: { type: 'image' }, button: { text: i18n.mediaAdd || 'Pridať' } });
					galFrame.on('select', function () {
						galFrame.state().get('selection').each(function (att) {
							var a = att.toJSON();
							var url = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
							addGalleryItem(a.id, url);
						});
					});
				}
				galFrame.open();
			});
		}

		// Submit
		listingForm.addEventListener('submit', function (e) {
			e.preventDefault();
			var redir   = listingForm.dataset.redirect || null;
			var submits = document.querySelectorAll('[form="tb-fa-listing-form"][type="submit"]');
			submits.forEach(function (b) { b.disabled = true; });

			var fd = new FormData(listingForm);
			fd.append('action', 'tb_fa_save_listing');
			fd.append('nonce', nonce);
			fd.append('post_id', listingForm.dataset.postId || 0);
			// Accent submit button = "Опублікувати" → publish on save.
			fd.append('set_status', 'publish');

			fetch(ajaxUrl, {
				method: 'POST',
				body: fd,
				credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
			})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					submits.forEach(function (b) { b.disabled = false; });
					if (!data || !data.success) {
						showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
						return;
					}
					if (redir) { window.location.href = redir; }
					else { showNotify(i18n.saved || 'Uložené'); }
				})
				.catch(function () {
					submits.forEach(function (b) { b.disabled = false; });
					showToast(i18n.error || 'Chyba', true);
				});
		});
	}

	// ── Bind all action buttons ──────────────────────────────────
	// Exposed as a named function so it can be re-run after AJAX swaps in
	// fresh cards (e.g. the Adverts tab's filter/sort/search — those new
	// buttons don't exist yet at first page load, so they need re-binding).
	function bindActionButtons() {
		document.querySelectorAll('[data-fa-action="publish"]').forEach(function (btn) {
			if (btn.dataset.bound) { return; }
			btn.dataset.bound = '1';
			if (btn.dataset.redirect) { bindPublishWithRedirect(btn); } else { bindPublish(btn); }
		});
		document.querySelectorAll('[data-fa-action="archive"]').forEach(function (btn) {
			if (btn.dataset.bound) { return; }
			btn.dataset.bound = '1';
			if (btn.dataset.redirect) { bindArchiveWithRedirect(btn); } else { bindArchive(btn); }
		});
		document.querySelectorAll('[data-fa-action="reject"]').forEach(function (btn) {
			if (btn.dataset.bound) { return; }
			btn.dataset.bound = '1';
			bindReject(btn);
		});
		document.querySelectorAll('[data-fa-action="pending"]').forEach(function (btn) {
			if (btn.dataset.bound) { return; }
			btn.dataset.bound = '1';
			bindPending(btn);
		});
		document.querySelectorAll('[data-fa-action="delete"]').forEach(function (btn) {
			if (btn.dataset.bound) { return; }
			btn.dataset.bound = '1';
			bindDelete(btn);
		});
		document.querySelectorAll('[data-fa-action="block"]').forEach(function (btn) {
			if (btn.dataset.bound) { return; }
			btn.dataset.bound = '1';
			bindBlock(btn);
		});
		document.querySelectorAll('[data-fa-action="unblock"]').forEach(function (btn) {
			if (btn.dataset.bound) { return; }
			btn.dataset.bound = '1';
			bindUnblock(btn);
		});
	}
	bindActionButtons();

	// ── Categories Tab (Phase 5) ─────────────────────────────────

	var catEditModal      = document.getElementById('tb-fa-modal-cat-edit');
	var catEditBdrop      = document.getElementById('tb-fa-cat-edit-backdrop');
	var catEditCloseBtn   = document.getElementById('tb-fa-cat-edit-close');
	var catEditCancelBtn  = document.getElementById('tb-fa-cat-edit-cancel');
	var catEditSaveBtn    = document.getElementById('tb-fa-cat-edit-save');
	var catEditTitleEl    = document.getElementById('tb-fa-cat-edit-title');
	var catTermIdInput    = document.getElementById('tb-fa-cat-term-id');
	var catNameInput      = document.getElementById('tb-fa-cat-edit-name');
	var catChildrenList   = document.getElementById('tb-fa-cat-children-list');
	var catAddChildBtn    = document.getElementById('tb-fa-cat-add-child');

	var deleteCatModal    = document.getElementById('tb-fa-modal-delete-cat');
	var deleteCatBdrop    = document.getElementById('tb-fa-delete-cat-backdrop');
	var deleteCatCancelBtn = document.getElementById('tb-fa-delete-cat-cancel');
	var deleteCatConfBtn  = document.getElementById('tb-fa-delete-cat-confirm');
	var deleteCatTermId   = 0;

	function openCatEditModal(termId, name, children) {
		if (!catEditModal) return;
		catTermIdInput.value  = termId || 0;
		catEditTitleEl.textContent = termId ? (catEditTitleEl.dataset.labelEdit || 'Upraviť') : (catEditTitleEl.dataset.labelAdd || 'Pridať kategóriu');
		catNameInput.value = name || '';
		catChildrenList.innerHTML = '';
		(children || []).forEach(function (c) { appendCatChild(c.id, c.name); });
		catEditModal.removeAttribute('hidden');
		catNameInput.focus();
	}

	function closeCatEditModal() {
		if (catEditModal) catEditModal.setAttribute('hidden', '');
	}

	function appendCatChild(id, name) {
		if (!catChildrenList) return;
		var li = document.createElement('li');
		li.className = 'tb-fa-cat-modal-item';
		li.dataset.childId = id || 0;

		var inp = document.createElement('input');
		inp.type = 'text';
		inp.className = 'tb-fa-field__input tb-fa-cat-child-input';
		inp.value = name || '';
		inp.placeholder = i18n.catNamePh || 'Názov kategórie';

		var delBtn = document.createElement('button');
		delBtn.type = 'button';
		delBtn.className = 'tb-fa-cat-item__del';
		delBtn.setAttribute('aria-label', i18n.deleteLabel || 'Odstrániť');
		delBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
		delBtn.addEventListener('click', function () { li.remove(); });

		li.appendChild(inp);
		li.appendChild(delBtn);
		catChildrenList.appendChild(li);
		return inp;
	}

	if (catAddChildBtn) {
		catAddChildBtn.addEventListener('click', function () {
			var inp = appendCatChild(0, '');
			if (inp) inp.focus();
		});
	}

	if (catEditSaveBtn) {
		catEditSaveBtn.addEventListener('click', function () {
			var termId = parseInt(catTermIdInput.value, 10) || 0;
			var name   = catNameInput ? catNameInput.value.trim() : '';
			if (!name) { showToast(i18n.catNameRequired || 'Názov je povinný.', true); return; }

			var children = [];
			if (catChildrenList) {
				catChildrenList.querySelectorAll('.tb-fa-cat-modal-item').forEach(function (li) {
					var cid   = parseInt(li.dataset.childId, 10) || 0;
					var cinp  = li.querySelector('.tb-fa-cat-child-input');
					var cname = cinp ? cinp.value.trim() : '';
					if (cname) { children.push({ id: cid, name: cname }); }
				});
			}

			var body = new URLSearchParams({
				action: 'tb_fa_save_category',
				term_id: termId,
				name: name,
				children: JSON.stringify(children),
				nonce: nonce,
			});
			fetch(ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (!data.success) { showToast((data.data && data.data.message) || i18n.error || 'Chyba', true); return; }
					window.location.reload();
				})
				.catch(function () { showToast(i18n.error || 'Chyba', true); });
		});
	}

	if (catEditBdrop)     catEditBdrop.addEventListener('click', closeCatEditModal);
	if (catEditCloseBtn)  catEditCloseBtn.addEventListener('click', closeCatEditModal);
	if (catEditCancelBtn) catEditCancelBtn.addEventListener('click', closeCatEditModal);

	// Delete category modal
	function openDeleteCatModal(termId) {
		deleteCatTermId = termId;
		if (deleteCatModal) deleteCatModal.removeAttribute('hidden');
	}

	function closeDeleteCatModal() {
		deleteCatTermId = 0;
		if (deleteCatModal) deleteCatModal.setAttribute('hidden', '');
	}

	if (deleteCatBdrop)     deleteCatBdrop.addEventListener('click', closeDeleteCatModal);
	if (deleteCatCancelBtn) deleteCatCancelBtn.addEventListener('click', closeDeleteCatModal);
	if (deleteCatConfBtn) {
		deleteCatConfBtn.addEventListener('click', function () {
			if (!deleteCatTermId) return;
			var tid = deleteCatTermId;
			closeDeleteCatModal();
			var body = new URLSearchParams({ action: 'tb_fa_delete_category', term_id: tid, nonce: nonce });
			fetch(ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (!data.success) { showToast((data.data && data.data.message) || i18n.error || 'Chyba', true); return; }
					var block = document.querySelector('.tb-fa-cat-block[data-term-id="' + tid + '"]');
					var listItem = document.querySelector('.tb-fa-cat-item[data-term-id="' + tid + '"]');
					var target = block || listItem;
					if (target) {
						target.style.transition = 'opacity 0.3s';
						target.style.opacity = '0';
						setTimeout(function () { target.remove(); }, 320);
					}
					showNotify(i18n.deleted || 'Odstránené');
				})
				.catch(function () { showToast(i18n.error || 'Chyba', true); });
		});
	}

	// Bind category blocks
	document.querySelectorAll('.tb-fa-cat-block').forEach(function (block) {
		block.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-cat-action]');
			if (!btn) return;
			var action = btn.dataset.catAction;
			if (action === 'edit') {
				var name     = block.dataset.name || '';
				var children = [];
				try { children = JSON.parse(block.dataset.children || '[]'); } catch (ex) {}
				openCatEditModal(parseInt(block.dataset.termId, 10) || 0, name, children);
			}
			if (action === 'delete') {
				openDeleteCatModal(parseInt(block.dataset.termId, 10) || 0, undefined, undefined, block.dataset.name || '');
			}
			if (action === 'delete-child') {
				var childItem = btn.closest('.tb-fa-cat-item');
				var childName = childItem ? childItem.querySelector('.tb-fa-cat-item__name') : null;
				openDeleteCatModal(parseInt(btn.dataset.termId, 10) || 0, undefined, undefined, childName ? childName.textContent.trim() : '');
			}
		});
	});

	// "Додати категорію" button
	var addCatBtn = document.getElementById('tb-fa-add-cat-btn');
	if (addCatBtn) {
		addCatBtn.addEventListener('click', function () {
			openCatEditModal(0, '', []);
		});
	}

	// Escape closes category modals
	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') return;
		if (catEditModal && !catEditModal.hasAttribute('hidden')) closeCatEditModal();
		if (deleteCatModal && !deleteCatModal.hasAttribute('hidden')) closeDeleteCatModal();
	});

	// ── Cities Tab (Phase 4) ─────────────────────────────────────

	var deleteCityModal    = document.getElementById('tb-fa-modal-delete-city');
	var deleteCityBdrop    = document.getElementById('tb-fa-delete-city-backdrop');
	var deleteCityCancelBtn = document.getElementById('tb-fa-delete-city-cancel');
	var deleteCityConfBtn  = document.getElementById('tb-fa-delete-city-confirm');
	var deleteCityTermId   = 0;

	function openDeleteCityModal(termId, name) {
		deleteCityTermId = termId;
		if (deleteCityModal) {
			fillModalName(document.getElementById('tb-fa-delete-city-title'), name);
			deleteCityModal.removeAttribute('hidden');
		}
	}

	function closeDeleteCityModal() {
		deleteCityTermId = 0;
		if (deleteCityModal) deleteCityModal.setAttribute('hidden', '');
	}

	if (deleteCityBdrop)    deleteCityBdrop.addEventListener('click', closeDeleteCityModal);
	if (deleteCityCancelBtn) deleteCityCancelBtn.addEventListener('click', closeDeleteCityModal);
	if (deleteCityConfBtn) {
		deleteCityConfBtn.addEventListener('click', function () {
			if (!deleteCityTermId) return;
			var tid = deleteCityTermId;
			closeDeleteCityModal();
			var body = new URLSearchParams({ action: 'tb_fa_delete_city', term_id: tid, nonce: nonce });
			fetch(ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (!data.success) { showToast((data.data && data.data.message) || i18n.error || 'Chyba', true); return; }
					var card = document.querySelector('.tb-fa-city-card[data-term-id="' + tid + '"]');
					if (card) {
						card.style.transition = 'opacity 0.3s, transform 0.3s';
						card.style.opacity    = '0';
						card.style.transform  = 'scale(0.97)';
						setTimeout(function () { card.remove(); }, 320);
					}
					showNotify(i18n.deleted || 'Odstránené');
				})
				.catch(function () { showToast(i18n.error || 'Chyba', true); });
		});
	}

	function enterEditMode(card) {
		card.classList.add('is-editing');
	}

	function exitEditMode(card) {
		card.classList.remove('is-editing');
	}

	function saveCity(card) {
		var termId   = parseInt(card.dataset.termId, 10) || 0;
		var nameInput = card.querySelector('.tb-fa-city-name-input');
		var imgInput  = card.querySelector('input[name="image_id"]');
		var name      = nameInput ? nameInput.value.trim() : '';
		var imageId   = imgInput  ? (parseInt(imgInput.value, 10) || 0) : 0;

		if (!name) { showToast(i18n.cityNameRequired || 'Názov mesta je povinný', true); return; }

		var body = new URLSearchParams({ action: 'tb_fa_save_city', term_id: termId, name: name, image_id: imageId, nonce: nonce });
		fetch(ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (!data.success) { showToast((data.data && data.data.message) || i18n.error || 'Chyba', true); return; }
				var d = data.data;
				if (!termId) {
					// New city added — reload to show fresh card from server
					window.location.reload();
					return;
				}
				// Update display state in existing card
				var nameEl = card.querySelector('.tb-fa-city-name');
				var slugEl = card.querySelector('.tb-fa-city-card__slug');
				var thumbEl = card.querySelector('.tb-fa-city-card__thumb');
				if (nameEl)  nameEl.textContent = d.name;
				if (slugEl)  slugEl.textContent = '/' + d.slug;
				if (thumbEl && d.image_url) {
					thumbEl.style.backgroundImage = 'url(' + d.image_url + ')';
					thumbEl.classList.remove('tb-fa-city-card__thumb--empty');
				}
				card.dataset.termId = d.term_id;
				exitEditMode(card);
				showNotify(i18n.saved || 'Uložené');
			})
			.catch(function () { showToast(i18n.error || 'Chyba', true); });
	}

	function initImagePicker(card) {
		if (!window.wp || !window.wp.media) return;
		var thumbEditable = card.querySelector('[data-city-action="pick-image"]');
		if (!thumbEditable) return;
		var imgInput = card.querySelector('input[name="image_id"]');
		var frame;
		thumbEditable.addEventListener('click', function () {
			if (frame) { frame.open(); return; }
			frame = window.wp.media({ title: i18n.mediaCity || 'Vyberte obrázok mesta', button: { text: i18n.mediaUse || 'Použiť' }, multiple: false, library: { type: 'image' } });
			frame.on('select', function () {
				var att = frame.state().get('selection').first().toJSON();
				if (imgInput) imgInput.value = att.id;
				thumbEditable.style.backgroundImage = 'url(' + att.url + ')';
				thumbEditable.classList.remove('tb-fa-city-card__thumb--empty');
			});
			frame.open();
		});
	}

	function bindCityCard(card) {
		initImagePicker(card);
		card.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-city-action]');
			if (!btn) return;
			var action = btn.dataset.cityAction;
			if (action === 'edit')   { enterEditMode(card); }
			if (action === 'cancel') {
				if (card.classList.contains('is-new')) {
					card.style.display = 'none';
				} else {
					exitEditMode(card);
				}
			}
			if (action === 'save')   { saveCity(card); }
			if (action === 'delete') {
				var termId = parseInt(card.dataset.termId, 10);
				var nameEl = card.querySelector('.tb-fa-city-name');
				if (termId) openDeleteCityModal(termId, nameEl ? nameEl.textContent.trim() : '');
			}
		});
	}

	// Add city button shows the hidden add card
	var addCityBtn  = document.getElementById('tb-fa-add-city-btn');
	var addCityCard = document.getElementById('tb-fa-add-city-card');
	if (addCityBtn && addCityCard) {
		addCityBtn.addEventListener('click', function () {
			addCityCard.style.display = '';
			initImagePicker(addCityCard);
			var input = addCityCard.querySelector('.tb-fa-city-name-input');
			if (input) input.focus();
		});
	}

	document.querySelectorAll('.tb-fa-city-card:not(.is-new)').forEach(bindCityCard);
	if (addCityCard) bindCityCard(addCityCard);

	// ── Phase 6: Blog — delete post modal ───────────────────────
	var deletePostModal   = document.getElementById('tb-fa-modal-delete-post');
	var deletePostBackdrop = document.getElementById('tb-fa-delete-post-backdrop');
	var deletePostCancel  = document.getElementById('tb-fa-delete-post-cancel');
	var deletePostConfirm = document.getElementById('tb-fa-delete-post-confirm');
	var pendingDeletePostId = 0;

	function openDeletePostModal(postId, name) {
		pendingDeletePostId = postId;
		if (!deletePostModal) return;
		fillModalName(document.getElementById('tb-fa-delete-post-desc'), name);
		deletePostModal.removeAttribute('hidden');
		document.body.style.overflow = 'hidden';
		if (deletePostConfirm) deletePostConfirm.focus();
	}

	function closeDeletePostModal() {
		if (!deletePostModal) return;
		deletePostModal.setAttribute('hidden', '');
		document.body.style.overflow = '';
		pendingDeletePostId = 0;
	}

	if (deletePostBackdrop) deletePostBackdrop.addEventListener('click', closeDeletePostModal);
	if (deletePostCancel)   deletePostCancel.addEventListener('click', closeDeletePostModal);

	if (deletePostConfirm) {
		deletePostConfirm.addEventListener('click', function () {
			if (!pendingDeletePostId) return;
			deletePostConfirm.disabled = true;
			sendAction('tb_fa_delete_post', pendingDeletePostId, {}, function (err, data) {
				deletePostConfirm.disabled = false;
				closeDeletePostModal();
				if (err || !data || !data.success) {
					showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
					return;
				}
				var row = document.querySelector('.tb-fa-blog-row[data-post-id="' + pendingDeletePostId + '"]');
				if (row) {
					row.style.transition = 'opacity 0.3s, max-height 0.3s';
					row.style.opacity = '0';
					row.style.overflow = 'hidden';
					setTimeout(function () { row.remove(); }, 320);
				}
				showNotify(i18n.deleted || 'Odstránené');
			});
		});
	}

	// Delegate delete-post button clicks on the blog list.
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-fa-action="delete-post"]');
		if (!btn) return;
		var postId  = parseInt(btn.dataset.postId, 10);
		var card    = btn.closest('.tb-fa-blog-card');
		var titleEl = card ? card.querySelector('.tb-fa-blog-card__title') : null;
		if (postId) openDeletePostModal(postId, titleEl ? titleEl.textContent.trim() : '');
	});

	// ── Phase 6: Blog categories modal ───────────────────────────
	var blogCatModal    = document.getElementById('tb-fa-modal-blog-cat');
	var blogCatBackdrop = document.getElementById('tb-fa-blog-cat-backdrop');
	var blogCatClose    = document.getElementById('tb-fa-blog-cat-close');
	var blogCatCancel   = document.getElementById('tb-fa-blog-cat-cancel');
	var blogCatSave     = document.getElementById('tb-fa-blog-cat-save');
	var blogCatTermId   = document.getElementById('tb-fa-blog-cat-term-id');
	var blogCatName     = document.getElementById('tb-fa-blog-cat-name');
	var addBlogCatBtn   = document.getElementById('tb-fa-add-blog-cat-btn');

	function openBlogCatModal(termId, name) {
		if (!blogCatModal) return;
		if (blogCatTermId) blogCatTermId.value = termId || 0;
		if (blogCatName)   blogCatName.value   = name  || '';
		blogCatModal.removeAttribute('hidden');
		document.body.style.overflow = 'hidden';
		if (blogCatName) { blogCatName.focus(); blogCatName.select(); }
	}

	function closeBlogCatModal() {
		if (!blogCatModal) return;
		blogCatModal.setAttribute('hidden', '');
		document.body.style.overflow = '';
	}

	if (blogCatBackdrop) blogCatBackdrop.addEventListener('click', closeBlogCatModal);
	if (blogCatClose)    blogCatClose.addEventListener('click', closeBlogCatModal);
	if (blogCatCancel)   blogCatCancel.addEventListener('click', closeBlogCatModal);

	if (addBlogCatBtn) {
		addBlogCatBtn.addEventListener('click', function () { openBlogCatModal(0, ''); });
	}

	// Blog cat row: edit / delete delegation.
	document.addEventListener('click', function (e) {
		var editBtn = e.target.closest('[data-blog-cat-action="edit"]');
		if (editBtn) {
			var row = editBtn.closest('.tb-fa-blog-cat-row');
			if (row) openBlogCatModal(row.dataset.termId, row.dataset.name);
			return;
		}
		var delBtn = e.target.closest('[data-blog-cat-action="delete"]');
		if (delBtn) {
			var row = delBtn.closest('.tb-fa-blog-cat-row');
			if (!row) return;
			var termId = parseInt(row.dataset.termId, 10);
			if (!termId) return;
			// Reuse delete-category modal with blog-cat action.
			openDeleteCatModal(termId, 'tb_fa_delete_blog_cat', row, row.dataset.name || '');
			return;
		}
	});

	if (blogCatSave) {
		blogCatSave.addEventListener('click', function () {
			var termId = parseInt(blogCatTermId ? blogCatTermId.value : 0, 10);
			var name   = blogCatName ? blogCatName.value.trim() : '';
			if (!name) {
				if (blogCatName) blogCatName.focus();
				return;
			}
			blogCatSave.disabled = true;
			var body = new URLSearchParams({
				action:  'tb_fa_save_blog_cat',
				nonce:   nonce,
				term_id: termId || 0,
				name:    name,
			});
			fetch(ajaxUrl, {
				method: 'POST', body: body, credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
			})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					blogCatSave.disabled = false;
					closeBlogCatModal();
					if (!data || !data.success) {
						showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
						return;
					}
					showNotify(i18n.saved || 'Uložené');
					// Update DOM if editing existing row, else reload for new.
					if (termId) {
						var row = document.querySelector('.tb-fa-blog-cat-row[data-term-id="' + termId + '"]');
						if (row) {
							var nameEl = row.querySelector('.tb-fa-blog-cat-row__name');
							var newName = (data.data && data.data.name) || name;
							if (nameEl) nameEl.textContent = newName;
							row.dataset.name = newName;
						}
					} else {
						setTimeout(function () { window.location.reload(); }, 400);
					}
				})
				.catch(function () {
					blogCatSave.disabled = false;
					showToast(i18n.error || 'Chyba', true);
				});
		});
	}

	// ── Phase 6: Delete blog cat — extend existing delete-cat modal ──
	// The existing deleteCatConfirm from Phase 5 handles ride_category.
	// For blog categories we track a separate pending action.
	var pendingBlogCatDelete = { termId: 0, rowEl: null, ajaxAction: '' };

	// Override: openDeleteCatModal to support multi-action.
	var _origOpenDeleteCatModal = typeof openDeleteCatModal === 'function' ? openDeleteCatModal : null;
	function openDeleteCatModal(termId, ajaxAction, rowEl, name) {
		pendingBlogCatDelete = {
			termId: termId,
			rowEl:  rowEl || null,
			ajaxAction: ajaxAction || 'tb_fa_delete_category',
		};
		var modal = document.getElementById('tb-fa-modal-delete-cat');
		if (!modal) return;
		fillModalName(document.getElementById('tb-fa-delete-cat-title'), name);
		modal.removeAttribute('hidden');
	}

	// Patch the existing deleteCatConfirm button (defined in Phase 5 block) to be action-aware.
	// We do this by re-attaching the listener after replacing the button.
	var deleteCatConfBtn = document.getElementById('tb-fa-delete-cat-confirm');
	if (deleteCatConfBtn) {
		var newBtn = deleteCatConfBtn.cloneNode(true);
		deleteCatConfBtn.parentNode.replaceChild(newBtn, deleteCatConfBtn);
		newBtn.addEventListener('click', function () {
			var info = pendingBlogCatDelete;
			if (!info.termId) return;
			newBtn.disabled = true;
			var body = new URLSearchParams({
				action:  info.ajaxAction,
				nonce:   nonce,
				term_id: info.termId,
			});
			fetch(ajaxUrl, {
				method: 'POST', body: body, credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
			})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					newBtn.disabled = false;
					// Close modal.
					var modal = document.getElementById('tb-fa-modal-delete-cat');
					if (modal) modal.setAttribute('hidden', '');
					document.body.style.overflow = '';
					if (!data || !data.success) {
						showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
						return;
					}
					var rowEl = info.rowEl || null;
					if (rowEl) {
						rowEl.style.transition = 'opacity 0.3s';
						rowEl.style.opacity = '0';
						setTimeout(function () { rowEl.remove(); }, 320);
					} else {
						// ride_category — remove block (parent) or list item (child).
						var target = document.querySelector('.tb-fa-cat-block[data-term-id="' + info.termId + '"]') ||
						             document.querySelector('.tb-fa-cat-item[data-term-id="' + info.termId + '"]');
						if (target) {
							target.style.transition = 'opacity 0.3s';
							target.style.opacity = '0';
							setTimeout(function () { target.remove(); }, 320);
						}
					}
					showNotify(i18n.deleted || 'Odstránené');
					pendingBlogCatDelete = { termId: 0, rowEl: null, ajaxAction: '' };
				})
				.catch(function () {
					newBtn.disabled = false;
					showToast(i18n.error || 'Chyba', true);
				});
		});
	}

	// ── Phase 6: Blog edit page ───────────────────────────────────
	var blogEditTopbar = document.querySelector('.tb-fa-blog-edit-topbar');
	if (blogEditTopbar) {
		var blogSaveDraft   = document.getElementById('tb-blog-save-draft');
		var blogSavePublish = document.getElementById('tb-blog-save-publish');
		var blogDraftBtns   = document.querySelectorAll('.tb-blog-save-draft-bottom');
		var blogPublishBtns = document.querySelectorAll('.tb-blog-save-publish-bottom');
		var blogPostIdEl    = document.getElementById('tb-blog-post-id');
		var blogTitleEl     = document.getElementById('tb-blog-title');
		var blogCategoryEl  = document.getElementById('tb-blog-category');
		var blogDateEl      = document.getElementById('tb-blog-date');
		var blogThumbIdEl   = document.getElementById('tb-blog-thumb-id');
		var blogThumbCleared = document.getElementById('tb-blog-thumb-cleared');
		var blogPhotoArea   = document.getElementById('tb-blog-photo-area');
		var blogPhotoPreview = document.getElementById('tb-blog-photo-preview');
		var blogPhotoImg    = document.getElementById('tb-blog-photo-img');
		var blogAddPhoto    = document.getElementById('tb-blog-add-photo');
		var blogRemovePhoto = document.getElementById('tb-blog-photo-remove');
		var blogSlugPreview = document.getElementById('tb-blog-slug-text');
		var blogFormAlert   = document.getElementById('tb-blog-form-alert');
		var blogEditData    = window.tbBlogEdit || {};

		// Slug auto-generation.
		function slugify(str) {
			return str
				.toLowerCase()
				.normalize('NFD').replace(/[̀-ͯ]/g, '')
				.replace(/[^a-z0-9\s-]/g, '')
				.trim()
				.replace(/[\s]+/g, '-')
				.replace(/-+/g, '-');
		}

		if (blogTitleEl && blogSlugPreview) {
			blogTitleEl.addEventListener('input', function () {
				var slug = slugify(blogTitleEl.value);
				blogSlugPreview.textContent = slug ? '/' + slug : '';
			});
		}

		// Featured image picker.
		var mediaFrame;
		function openMediaPicker() {
			if (!window.wp || !wp.media) return;
			if (mediaFrame) { mediaFrame.open(); return; }
			mediaFrame = wp.media({
				title: i18n.mediaCover || 'Vybrať fotografiu',
				button: { text: i18n.mediaUse || 'Použiť' },
				multiple: false,
				library: { type: 'image' },
			});
			mediaFrame.on('select', function () {
				var att = mediaFrame.state().get('selection').first().toJSON();
				if (blogThumbIdEl) blogThumbIdEl.value = att.id;
				if (blogThumbCleared) blogThumbCleared.value = '0';
				if (blogPhotoImg) { blogPhotoImg.src = att.url; }
				if (blogPhotoPreview) blogPhotoPreview.classList.remove('tb-fa-blog-photo-preview--hidden');
			});
			mediaFrame.open();
		}

		if (blogAddPhoto) blogAddPhoto.addEventListener('click', openMediaPicker);

		if (blogRemovePhoto) {
			blogRemovePhoto.addEventListener('click', function () {
				if (blogThumbIdEl) blogThumbIdEl.value = '';
				if (blogThumbCleared) blogThumbCleared.value = '1';
				if (blogPhotoImg) blogPhotoImg.src = '';
				if (blogPhotoPreview) blogPhotoPreview.classList.add('tb-fa-blog-photo-preview--hidden');
			});
		}

		// ── Validation (Figma 410:4956) ───────────────────────────────
		function clearBlogFieldErrors() {
			if (blogFormAlert) blogFormAlert.hidden = true;

			if (blogTitleEl) {
				blogTitleEl.classList.remove('is-error');
				blogTitleEl.placeholder = blogTitleEl.dataset.placeholderDefault || blogTitleEl.placeholder;
			}
			var titleErr = document.getElementById('tb-blog-title-error');
			if (titleErr) titleErr.classList.remove('is-visible');

			if (blogCategoryEl) blogCategoryEl.classList.remove('is-error');
			var catErr = document.getElementById('tb-blog-category-error');
			if (catErr) catErr.classList.remove('is-visible');

			var contentWrap = document.getElementById('wp-tb_blog_content-wrap');
			if (contentWrap) contentWrap.classList.remove('is-error');
			var contentErr = document.getElementById('tb-blog-content-error');
			if (contentErr) contentErr.classList.remove('is-visible');

			if (blogPhotoArea) blogPhotoArea.classList.remove('is-error');
			var photoErr = document.getElementById('tb-blog-photo-error');
			if (photoErr) photoErr.classList.remove('is-visible');

			if (blogDateEl) blogDateEl.classList.remove('is-error');
		}

		// The picker won't offer a date before [min], but one can still be
		// typed in, so check it explicitly.
		function blogDateIsPast() {
			return !!(blogDateEl && blogDateEl.value && blogDateEl.min && blogDateEl.value < blogDateEl.min);
		}

		if (blogDateEl) {
			blogDateEl.addEventListener('change', function () {
				blogDateEl.classList.toggle('is-error', blogDateIsPast());
			});
			blogDateEl.addEventListener('blur', function () {
				blogDateEl.classList.toggle('is-error', blogDateIsPast());
			});
		}

		function validateBlogForm(title, content) {
			var valid = true;
			clearBlogFieldErrors();

			if (!title) {
				if (blogTitleEl) {
					blogTitleEl.classList.add('is-error');
					if (blogTitleEl.dataset.placeholderError) {
						blogTitleEl.placeholder = blogTitleEl.dataset.placeholderError;
					}
				}
				var titleErr = document.getElementById('tb-blog-title-error');
				if (titleErr) titleErr.classList.add('is-visible');
				valid = false;
			}

			if (blogCategoryEl && (!blogCategoryEl.value || blogCategoryEl.value === '0')) {
				blogCategoryEl.classList.add('is-error');
				var catErr = document.getElementById('tb-blog-category-error');
				if (catErr) catErr.classList.add('is-visible');
				valid = false;
			}

			var plainContent = (content || '').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
			if (plainContent.length < 500) {
				var contentWrap = document.getElementById('wp-tb_blog_content-wrap');
				if (contentWrap) contentWrap.classList.add('is-error');
				var contentErr = document.getElementById('tb-blog-content-error');
				if (contentErr) contentErr.classList.add('is-visible');
				valid = false;
			}

			var hasPhoto = blogThumbIdEl && blogThumbIdEl.value && (!blogThumbCleared || blogThumbCleared.value !== '1');
			if (!hasPhoto) {
				if (blogPhotoArea) blogPhotoArea.classList.add('is-error');
				var photoErr = document.getElementById('tb-blog-photo-error');
				if (photoErr) photoErr.classList.add('is-visible');
				valid = false;
			}

			if (blogDateIsPast()) {
				blogDateEl.classList.add('is-error');
				valid = false;
			}

			if (!valid && blogFormAlert) {
				blogFormAlert.hidden = false;
				blogFormAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}

			return valid;
		}

		// Save post.
		function saveBlogPost(status) {
			// Sync TinyMCE content.
			if (typeof tinymce !== 'undefined') {
				tinymce.triggerSave();
			}

			var title = blogTitleEl ? blogTitleEl.value.trim() : '';
			var contentEl = document.getElementById('tb_blog_content');
			var content   = contentEl ? contentEl.value : '';

			if (!validateBlogForm(title, content)) {
				return;
			}

			var wasNew = !(blogPostIdEl && blogPostIdEl.value);

			var body = new URLSearchParams({
				action:         'tb_fa_save_post',
				nonce:          nonce,
				post_id:        blogPostIdEl ? blogPostIdEl.value : 0,
				post_title:     title,
				post_content:   content,
				post_category:  blogCategoryEl ? blogCategoryEl.value : 0,
				post_date:      blogDateEl     ? blogDateEl.value    : '',
				post_status:    status,
				thumbnail_id:   blogThumbIdEl  ? blogThumbIdEl.value : 0,
				thumb_cleared:  blogThumbCleared ? blogThumbCleared.value : 0,
			});

			// Disable all save buttons.
			[blogSaveDraft, blogSavePublish].concat(Array.from(blogDraftBtns)).concat(Array.from(blogPublishBtns)).forEach(function (b) {
				if (b) b.disabled = true;
			});

			fetch(ajaxUrl, {
				method: 'POST', body: body, credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
			})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					[blogSaveDraft, blogSavePublish].concat(Array.from(blogDraftBtns)).concat(Array.from(blogPublishBtns)).forEach(function (b) {
						if (b) b.disabled = false;
					});
					if (!data || !data.success) {
						showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
						return;
					}
					var savedId = data.data && data.data.post_id;
					var base = blogEditData.panelUrl || window.location.pathname;
					if (wasNew) {
						// New article — go back to the posts list with a "added" notice.
						window.location.href = base + '?tab=blog&posted=1';
					} else if (savedId) {
						// Existing article — stay on the edit page with a "saved" notice.
						window.location.href = base + '?tab=blog&edit=' + savedId + '&saved=' + status;
					}
				})
				.catch(function () {
					[blogSaveDraft, blogSavePublish].concat(Array.from(blogDraftBtns)).concat(Array.from(blogPublishBtns)).forEach(function (b) {
						if (b) b.disabled = false;
					});
					showToast(i18n.error || 'Chyba', true);
				});
		}

		if (blogSaveDraft)   blogSaveDraft.addEventListener('click', function () { saveBlogPost('draft'); });
		if (blogSavePublish) blogSavePublish.addEventListener('click', function () { saveBlogPost('publish'); });
		blogDraftBtns.forEach(function (b) { b.addEventListener('click', function () { saveBlogPost('draft'); }); });
		blogPublishBtns.forEach(function (b) { b.addEventListener('click', function () { saveBlogPost('publish'); }); });
	}

	// ── Sort dropdown (Figma 214:5865) ───────────────────────────
	document.querySelectorAll('.tb-fa-sort-drop').forEach(function (drop) {
		var toggle = drop.querySelector('[data-fa-sort-toggle]');
		if (!toggle) return;

		toggle.addEventListener('click', function (e) {
			e.stopPropagation();
			var wasOpen = drop.classList.contains('is-open');
			document.querySelectorAll('.tb-fa-sort-drop.is-open').forEach(function (d) {
				d.classList.remove('is-open');
				var t = d.querySelector('[data-fa-sort-toggle]');
				if (t) t.setAttribute('aria-expanded', 'false');
			});
			if (!wasOpen) {
				drop.classList.add('is-open');
				toggle.setAttribute('aria-expanded', 'true');
			}
		});

		var panel = drop.querySelector('.tb-fa-sort-panel');
		if (panel) {
			panel.addEventListener('click', function (e) { e.stopPropagation(); });
		}
	});

	document.addEventListener('click', function () {
		document.querySelectorAll('.tb-fa-sort-drop.is-open').forEach(function (d) {
			d.classList.remove('is-open');
			var t = d.querySelector('[data-fa-sort-toggle]');
			if (t) t.setAttribute('aria-expanded', 'false');
		});
	});

	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') return;
		document.querySelectorAll('.tb-fa-sort-drop.is-open').forEach(function (d) {
			d.classList.remove('is-open');
			var t = d.querySelector('[data-fa-sort-toggle]');
			if (t) t.setAttribute('aria-expanded', 'false');
		});
	});

	// ── Navigating status dropdowns (tablet/mobile) ──────────────
	// The pill row turns into a <select> below 1024px. Option values are the
	// same URLs the pills link to, so this is a plain navigation. Screens that
	// filter over AJAX use [data-fa-status-select] instead and are wired below.
	document.querySelectorAll('[data-fa-nav-select]').forEach(function (sel) {
		sel.addEventListener('change', function () {
			if (sel.value) { window.location.href = sel.value; }
		});
	});

	// ── Collapsed search (mobile) ────────────────────────────────
	// Below 768px the toolbar carries a round search button instead of the
	// input (Figma 265:23158 / 265:23709). Clicking it expands the form in
	// place; the status dropdown steps aside for it via CSS.
	document.querySelectorAll('[data-fa-search-toggle]').forEach(function (btn) {
		var toolbar = btn.closest('.tb-fa-toolbar');
		if (!toolbar) { return; }
		var input = toolbar.querySelector('.tb-fa-search-input');

		// Arriving on the page with an active query — show what is filtering.
		if (input && input.value.trim()) {
			toolbar.classList.add('is-search-open');
			btn.setAttribute('aria-expanded', 'true');
		}

		btn.addEventListener('click', function () {
			var open = toolbar.classList.toggle('is-search-open');
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open && input) { input.focus(); }
		});
	});

	// ── Adverts tab: AJAX search / sort / status filter ──────────
	// No full page reload, and no collision with WordPress's reserved
	// `s` query var (we use `fa_s`) which was causing 404s.
	(function () {
		var resultsEl = document.getElementById('tb-fa-listings-results');
		if (!resultsEl || resultsEl.dataset.faList !== 'listings') { return; }

		var toolbar     = document.querySelector('.tb-fa-toolbar');
		var searchForm  = toolbar ? toolbar.querySelector('.tb-fa-search-form') : null;
		var searchInput = searchForm ? searchForm.querySelector('input[name="fa_s"]') : null;
		var clearLink   = toolbar ? toolbar.querySelector('.tb-fa-search-clear') : null;
		var statsEl     = document.querySelector('.tb-fa-page-head__stats');
		var pills       = toolbar ? Array.prototype.slice.call(toolbar.querySelectorAll('.tb-fa-pill')) : [];
		var sortOptions = toolbar ? Array.prototype.slice.call(toolbar.querySelectorAll('.tb-fa-sort-option')) : [];
		var statusSel   = toolbar ? toolbar.querySelector('[data-fa-status-select]') : null;

		function paramFromHref(href, key, fallback) {
			try {
				return new URL(href).searchParams.get(key) || fallback;
			} catch (e) {
				return fallback;
			}
		}

		function currentState() {
			var activePill = pills.filter(function (p) { return p.classList.contains('is-active'); })[0];
			var activeSort = sortOptions.filter(function (s) { return s.classList.contains('is-active'); })[0];
			return {
				status: activePill ? paramFromHref(activePill.href, 'status', 'all') : 'all',
				sort:   activeSort ? paramFromHref(activeSort.href, 'sort', 'date_desc') : 'date_desc',
				fa_s:   searchInput ? searchInput.value.trim() : '',
				paged:  1,
			};
		}

		function buildUrl(state) {
			var params = new URLSearchParams();
			params.set('tab', 'listings');
			if (state.status && state.status !== 'all') { params.set('status', state.status); }
			if (state.sort && state.sort !== 'date_desc') { params.set('sort', state.sort); }
			if (state.fa_s) { params.set('fa_s', state.fa_s); }
			if (state.paged > 1) { params.set('paged', state.paged); }
			var qs = params.toString();
			return window.location.pathname + (qs ? '?' + qs : '');
		}

		function updateActiveStates(state) {
			pills.forEach(function (p) {
				var isActive = paramFromHref(p.href, 'status', 'all') === state.status;
				p.classList.toggle('is-active', isActive);
				if (isActive) { p.setAttribute('aria-current', 'true'); } else { p.removeAttribute('aria-current'); }
			});
			sortOptions.forEach(function (s) {
				var isActive = paramFromHref(s.href, 'sort', 'date_desc') === state.sort;
				s.classList.toggle('is-active', isActive);
				s.setAttribute('aria-checked', isActive ? 'true' : 'false');
			});
			if (statusSel) {
				Array.prototype.forEach.call(statusSel.options, function (o) {
					if (paramFromHref(o.value, 'status', 'all') === state.status) { statusSel.value = o.value; }
				});
			}
			if (clearLink) { clearLink.style.display = state.fa_s ? '' : 'none'; }
		}

		function fetchResults(state, opts) {
			opts = opts || {};
			resultsEl.setAttribute('aria-busy', 'true');

			var body = new URLSearchParams({
				action:     'tb_fa_filter_listings',
				nonce:      nonce,
				status:     state.status,
				sort:       state.sort,
				fa_s:       state.fa_s,
				paged:      state.paged,
				panel_path: window.location.pathname,
			});

			fetch(ajaxUrl, {
				method: 'POST', body: body, credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
			})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					resultsEl.removeAttribute('aria-busy');
					if (!data || !data.success) {
						showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
						return;
					}

					if (opts.append) {
						var temp = document.createElement('div');
						temp.innerHTML = data.data.html;
						var existingGrid = resultsEl.querySelector('.tb-fa-cards');
						var oldLoadMore  = resultsEl.querySelector('.tb-fa-load-more');
						if (existingGrid) {
							temp.querySelectorAll('.tb-fa-mod-card').forEach(function (c) {
								existingGrid.appendChild(c);
							});
						}
						if (oldLoadMore) { oldLoadMore.remove(); }
						var newLoadMore = temp.querySelector('.tb-fa-load-more');
						if (newLoadMore) { resultsEl.appendChild(newLoadMore); }
					} else {
						resultsEl.innerHTML = data.data.html;
						updateActiveStates(state);
					}

					bindActionButtons();
					if (statsEl && data.data.stats) { statsEl.textContent = data.data.stats; }
					if (opts.updateHistory !== false) {
						window.history.pushState(state, '', buildUrl(state));
					}
				})
				.catch(function () {
					resultsEl.removeAttribute('aria-busy');
					showToast(i18n.error || 'Chyba', true);
				});
		}

		pills.forEach(function (pill) {
			pill.addEventListener('click', function (e) {
				e.preventDefault();
				var state = currentState();
				state.status = paramFromHref(pill.href, 'status', 'all');
				state.paged = 1;
				fetchResults(state);
			});
		});

		if (statusSel) {
			statusSel.addEventListener('change', function () {
				var state = currentState();
				state.status = paramFromHref(statusSel.value, 'status', 'all');
				state.paged = 1;
				fetchResults(state);
			});
		}

		sortOptions.forEach(function (opt) {
			opt.addEventListener('click', function (e) {
				e.preventDefault();
				var state = currentState();
				state.sort = paramFromHref(opt.href, 'sort', 'date_desc');
				state.paged = 1;
				fetchResults(state);
				var drop = opt.closest('.tb-fa-sort-drop');
				if (drop) {
					drop.classList.remove('is-open');
					var t = drop.querySelector('[data-fa-sort-toggle]');
					if (t) t.setAttribute('aria-expanded', 'false');
				}
			});
		});

		if (searchForm) {
			searchForm.addEventListener('submit', function (e) {
				e.preventDefault();
				var state = currentState();
				state.paged = 1;
				fetchResults(state);
			});
		}

		if (clearLink) {
			clearLink.addEventListener('click', function (e) {
				e.preventDefault();
				if (searchInput) { searchInput.value = ''; }
				var state = currentState();
				state.fa_s = '';
				state.paged = 1;
				fetchResults(state);
			});
		}

		// "Load more" — event delegation, since the button is replaced/removed
		// on every fetch (results container itself is never replaced).
		resultsEl.addEventListener('click', function (e) {
			var btn = e.target.closest('.tb-fa-load-more .tb-fa-btn');
			if (!btn) { return; }
			e.preventDefault();
			var state = currentState();
			state.paged = parseInt(btn.dataset.faPage, 10) || 2;
			fetchResults(state, { append: true });
		});

		window.addEventListener('popstate', function () {
			var params = new URLSearchParams(window.location.search);
			var state = {
				status: params.get('status') || 'all',
				sort:   params.get('sort') || 'date_desc',
				fa_s:   params.get('fa_s') || '',
				paged:  parseInt(params.get('paged'), 10) || 1,
			};
			if (searchInput) { searchInput.value = state.fa_s; }
			fetchResults(state, { updateHistory: false });
		});
	})();

	// ── Users tab: AJAX search / sort / status / city / type filter ──
	(function () {
		var resultsEl = document.getElementById('tb-fa-users-results');
		if (!resultsEl || resultsEl.dataset.faList !== 'users') { return; }

		var toolbar      = document.querySelector('.tb-fa-toolbar--users');
		var searchForm   = toolbar ? toolbar.querySelector('.tb-fa-search-form') : null;
		var searchInput  = searchForm ? searchForm.querySelector('input[name="fa_s"]') : null;
		var clearLink    = toolbar ? toolbar.querySelector('.tb-fa-search-clear') : null;
		var filtersForm  = toolbar ? toolbar.querySelector('.tb-fa-user-filters') : null;
		var citySelect   = filtersForm ? filtersForm.querySelector('select[name="city"]') : null;
		var utypeSelect  = filtersForm ? filtersForm.querySelector('select[name="utype"]') : null;
		var statsEl      = document.querySelector('.tb-fa-page-head__stats');
		var pills        = toolbar ? Array.prototype.slice.call(toolbar.querySelectorAll('.tb-fa-pill')) : [];
		var sortOptions  = toolbar ? Array.prototype.slice.call(toolbar.querySelectorAll('.tb-fa-sort-option')) : [];
		var statusSel    = toolbar ? toolbar.querySelector('[data-fa-status-select]') : null;

		function paramFromHref(href, key, fallback) {
			try {
				return new URL(href).searchParams.get(key) || fallback;
			} catch (e) {
				return fallback;
			}
		}

		function currentState() {
			var activePill = pills.filter(function (p) { return p.classList.contains('is-active'); })[0];
			var activeSort = sortOptions.filter(function (s) { return s.classList.contains('is-active'); })[0];
			return {
				status: activePill ? paramFromHref(activePill.href, 'status', '') : '',
				sort:   activeSort ? paramFromHref(activeSort.href, 'sort', 'registered_desc') : 'registered_desc',
				fa_s:   searchInput ? searchInput.value.trim() : '',
				city:   citySelect ? citySelect.value : '0',
				utype:  utypeSelect ? utypeSelect.value : '',
				paged:  1,
			};
		}

		function buildUrl(state) {
			var params = new URLSearchParams();
			params.set('tab', 'users');
			if (state.status) { params.set('status', state.status); }
			if (state.sort && state.sort !== 'registered_desc') { params.set('sort', state.sort); }
			if (state.fa_s) { params.set('fa_s', state.fa_s); }
			if (state.city && state.city !== '0') { params.set('city', state.city); }
			if (state.utype) { params.set('utype', state.utype); }
			if (state.paged > 1) { params.set('paged', state.paged); }
			var qs = params.toString();
			return window.location.pathname + (qs ? '?' + qs : '');
		}

		function updateActiveStates(state) {
			pills.forEach(function (p) {
				var isActive = paramFromHref(p.href, 'status', '') === state.status;
				p.classList.toggle('is-active', isActive);
				if (isActive) { p.setAttribute('aria-current', 'true'); } else { p.removeAttribute('aria-current'); }
			});
			sortOptions.forEach(function (s) {
				var isActive = paramFromHref(s.href, 'sort', 'registered_desc') === state.sort;
				s.classList.toggle('is-active', isActive);
				s.setAttribute('aria-checked', isActive ? 'true' : 'false');
			});
			if (statusSel) {
				Array.prototype.forEach.call(statusSel.options, function (o) {
					if (paramFromHref(o.value, 'status', '') === state.status) { statusSel.value = o.value; }
				});
			}
			if (clearLink) { clearLink.style.display = state.fa_s ? '' : 'none'; }
		}

		function fetchResults(state, opts) {
			opts = opts || {};
			resultsEl.setAttribute('aria-busy', 'true');

			var body = new URLSearchParams({
				action:     'tb_fa_filter_users',
				nonce:      nonce,
				status:     state.status,
				sort:       state.sort,
				fa_s:       state.fa_s,
				city:       state.city,
				utype:      state.utype,
				paged:      state.paged,
				panel_path: window.location.pathname,
			});

			fetch(ajaxUrl, {
				method: 'POST', body: body, credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
			})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					resultsEl.removeAttribute('aria-busy');
					if (!data || !data.success) {
						showToast((data && data.data && data.data.message) || i18n.error || 'Chyba', true);
						return;
					}

					if (opts.append) {
						var temp = document.createElement('div');
						temp.innerHTML = data.data.html;
						var existingList = resultsEl.querySelector('.tb-fa-user-list');
						var oldLoadMore  = resultsEl.querySelector('.tb-fa-load-more');
						if (existingList) {
							temp.querySelectorAll('.tb-fa-user-row').forEach(function (row) {
								existingList.appendChild(row);
							});
						}
						if (oldLoadMore) { oldLoadMore.remove(); }
						var newLoadMore = temp.querySelector('.tb-fa-load-more');
						if (newLoadMore) { resultsEl.appendChild(newLoadMore); }
					} else {
						resultsEl.innerHTML = data.data.html;
						updateActiveStates(state);
					}

					bindActionButtons();
					if (statsEl && data.data.stats) { statsEl.textContent = data.data.stats; }
					if (opts.updateHistory !== false) {
						window.history.pushState(state, '', buildUrl(state));
					}
				})
				.catch(function () {
					resultsEl.removeAttribute('aria-busy');
					showToast(i18n.error || 'Chyba', true);
				});
		}

		pills.forEach(function (pill) {
			pill.addEventListener('click', function (e) {
				e.preventDefault();
				var state = currentState();
				state.status = paramFromHref(pill.href, 'status', '');
				state.paged = 1;
				fetchResults(state);
			});
		});

		if (statusSel) {
			statusSel.addEventListener('change', function () {
				var state = currentState();
				state.status = paramFromHref(statusSel.value, 'status', '');
				state.paged = 1;
				fetchResults(state);
			});
		}

		sortOptions.forEach(function (opt) {
			opt.addEventListener('click', function (e) {
				e.preventDefault();
				var state = currentState();
				state.sort = paramFromHref(opt.href, 'sort', 'registered_desc');
				state.paged = 1;
				fetchResults(state);
				var drop = opt.closest('.tb-fa-sort-drop');
				if (drop) {
					drop.classList.remove('is-open');
					var t = drop.querySelector('[data-fa-sort-toggle]');
					if (t) t.setAttribute('aria-expanded', 'false');
				}
			});
		});

		if (searchForm) {
			searchForm.addEventListener('submit', function (e) {
				e.preventDefault();
				var state = currentState();
				state.paged = 1;
				fetchResults(state);
			});
		}

		if (clearLink) {
			clearLink.addEventListener('click', function (e) {
				e.preventDefault();
				if (searchInput) { searchInput.value = ''; }
				var state = currentState();
				state.fa_s = '';
				state.paged = 1;
				fetchResults(state);
			});
		}

		if (filtersForm) {
			filtersForm.addEventListener('submit', function (e) {
				e.preventDefault();
				var state = currentState();
				state.paged = 1;
				fetchResults(state);
			});
		}

		// "Load more" — event delegation, since the button is replaced/removed
		// on every fetch (results container itself is never replaced).
		resultsEl.addEventListener('click', function (e) {
			var btn = e.target.closest('.tb-fa-load-more .tb-fa-btn');
			if (!btn) { return; }
			e.preventDefault();
			var state = currentState();
			state.paged = parseInt(btn.dataset.faPage, 10) || 2;
			fetchResults(state, { append: true });
		});

		window.addEventListener('popstate', function () {
			var params = new URLSearchParams(window.location.search);
			var state = {
				status: params.get('status') || '',
				sort:   params.get('sort') || 'registered_desc',
				fa_s:   params.get('fa_s') || '',
				city:   params.get('city') || '0',
				utype:  params.get('utype') || '',
				paged:  parseInt(params.get('paged'), 10) || 1,
			};
			if (searchInput) { searchInput.value = state.fa_s; }
			if (citySelect) { citySelect.value = state.city; }
			if (utypeSelect) { utypeSelect.value = state.utype; }
			fetchResults(state, { updateHistory: false });
		});
	})();

	// ── Mobile Nav (hamburger) ───────────────────────────────────
	var mobileNav    = document.getElementById('tb-fa-mobile-nav');
	var hamburgerBtn = document.getElementById('tb-fa-hamburger');
	var navBackdrop  = mobileNav ? mobileNav.querySelector('.tb-fa-mobile-nav__backdrop') : null;

	function openMobileNav() {
		if (!mobileNav) return;
		mobileNav.removeAttribute('hidden');
		document.body.style.overflow = 'hidden';
		if (hamburgerBtn) hamburgerBtn.setAttribute('aria-expanded', 'true');
	}

	function closeMobileNav() {
		if (!mobileNav) return;
		mobileNav.setAttribute('hidden', '');
		document.body.style.overflow = '';
		if (hamburgerBtn) hamburgerBtn.setAttribute('aria-expanded', 'false');
	}

	if (hamburgerBtn) hamburgerBtn.addEventListener('click', openMobileNav);
	if (navBackdrop)  navBackdrop.addEventListener('click', closeMobileNav);

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && mobileNav && !mobileNav.hasAttribute('hidden')) {
			closeMobileNav();
		}
	});

}());

/* Make → Model dependent selects (front-admin listing edit form). */
(function () {
	'use strict';
	var makeSelect  = document.querySelector('[data-make-select]');
	var modelSelect = document.querySelector('[data-model-select]');
	if (!makeSelect || !modelSelect) { return; }

	var allModelOpts = Array.prototype.slice.call(modelSelect.options);
	var modelPh      = allModelOpts.length ? allModelOpts[0] : null;

	function refreshModels(keepValue) {
		var makeId   = makeSelect.value;
		var selected = keepValue ? modelSelect.value : '';
		modelSelect.innerHTML = '';
		if (modelPh) { modelSelect.appendChild(modelPh); }
		allModelOpts.forEach(function (opt) {
			if (!opt.value) { return; }
			if (opt.getAttribute('data-make') === makeId) {
				modelSelect.appendChild(opt);
				if (opt.value === selected) { opt.selected = true; }
			}
		});
		modelSelect.disabled = !makeId;
	}

	makeSelect.addEventListener('change', function () { refreshModels(false); });
	refreshModels(true);
}());
