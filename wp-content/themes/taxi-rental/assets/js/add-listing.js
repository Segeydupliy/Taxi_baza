/* global tbAddListing, wp */
(function () {
	'use strict';

	var form = document.getElementById( 'tb-al-form' );
	if ( ! form ) return;

	var ajaxUrl   = tbAddListing.ajaxUrl;
	var nonce     = tbAddListing.nonce;
	var mediaText = tbAddListing.selectPhotos || 'Вибрати фото';

	// ── Gallery ──────────────────────────────────────────────────────
	var galleryGrid = document.getElementById( 'tb-al-gallery-grid' );
	var galleryInput = document.getElementById( 'tb-al-gallery' );
	var galleryAddBtn = document.getElementById( 'tb-al-gallery-add' );
	var galleryFrame = null;

	function syncGalleryInput() {
		var ids = [];
		galleryGrid.querySelectorAll( '.tb-fa-gallery__item' ).forEach( function ( el ) {
			ids.push( el.dataset.id );
		} );
		galleryInput.value = ids.join( ',' );
	}

	if ( galleryAddBtn && window.wp && wp.media ) {
		// One upload session per form: the media window lists only the photos of
		// this listing and the ones uploaded now (see functions.php).
		var tbSession = Array.prototype.map.call( ( window.crypto || window.msCrypto ).getRandomValues( new Uint8Array( 16 ) ), function ( b ) { return ( '0' + b.toString( 16 ) ).slice( -2 ); } ).join( '' );
		var tbListing = ( new URLSearchParams( window.location.search ) ).get( 'post_id' ) || '';
		if ( wp.Uploader && wp.Uploader.defaults ) {
			wp.Uploader.defaults.multipart_params = wp.Uploader.defaults.multipart_params || {};
			wp.Uploader.defaults.multipart_params.tb_session = tbSession;
		}
		galleryFrame = wp.media( {
			title:    mediaText,
			multiple: 'add',
			library:  { type: 'image', tb_session: tbSession, tb_listing: tbListing },
		} );

		galleryFrame.on( 'select', function () {
			galleryFrame.state().get( 'selection' ).each( function ( attachment ) {
				var id = attachment.id;
				if ( galleryGrid.querySelector( '[data-id="' + id + '"]' ) ) return;
				var sizes = attachment.attributes.sizes;
				var thumb = ( sizes && sizes.thumbnail ) ? sizes.thumbnail.url : attachment.attributes.url;
				var item = document.createElement( 'div' );
				item.className = 'tb-fa-gallery__item';
				item.dataset.id = id;
				item.innerHTML =
					'<img src="' + thumb + '" alt="">' +
					'<button type="button" class="tb-fa-gallery__remove" aria-label="Прибрати">×</button>';
				galleryGrid.insertBefore( item, galleryAddBtn );
			} );
			syncGalleryInput();
		} );

		galleryAddBtn.addEventListener( 'click', function () {
			galleryFrame.open();
		} );
	}

	if ( galleryGrid ) {
		galleryGrid.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.tb-fa-gallery__remove' );
			if ( ! btn ) return;
			btn.closest( '.tb-fa-gallery__item' ).remove();
			syncGalleryInput();
		} );
	}

	// ── Make → Model dependent selects ───────────────────────────────
	var makeSelect  = form.querySelector( '[data-make-select]' );
	var modelSelect = form.querySelector( '[data-model-select]' );
	if ( makeSelect && modelSelect ) {
		// Keep the full option list so we can rebuild per selected make.
		var allModelOpts = Array.prototype.slice.call( modelSelect.options );
		var modelPh       = allModelOpts.length ? allModelOpts[0] : null; // "Vyberte prosím"

		function refreshModels( keepValue ) {
			var makeId   = makeSelect.value;
			var selected = keepValue ? modelSelect.value : '';
			modelSelect.innerHTML = '';
			if ( modelPh ) { modelSelect.appendChild( modelPh ); }
			allModelOpts.forEach( function ( opt ) {
				if ( ! opt.value ) { return; } // skip placeholder (re-added above)
				if ( opt.getAttribute( 'data-make' ) === makeId ) {
					modelSelect.appendChild( opt );
					if ( opt.value === selected ) { opt.selected = true; }
				}
			} );
			modelSelect.disabled = ! makeId;
		}

		makeSelect.addEventListener( 'change', function () { refreshModels( false ); } );
		refreshModels( true ); // initial (preserves edit-mode selection)
	}

	// ── Dirty tracking ───────────────────────────────────────────────
	var isDirty = false;
	form.addEventListener( 'input',  function () { isDirty = true; } );
	form.addEventListener( 'change', function () { isDirty = true; } );

	// ── Submit ───────────────────────────────────────────────────────
	var allBtns = document.querySelectorAll(
		'#tb-al-btn-draft, #tb-al-btn-publish, #tb-al-btn-draft-foot, #tb-al-btn-publish-foot'
	);

	function lockBtns( lock ) {
		allBtns.forEach( function ( b ) { b.disabled = lock; } );
	}

	/* Build the POST body.
	 *
	 * The edited post's ID lives in data-post-id on the <form>, and FormData()
	 * only picks up form CONTROLS, never data attributes — so it was never sent
	 * and the server fell through to its "no post_id" branch, i.e. wp_insert_post().
	 * That is why saving an edit used to create a duplicate advert instead of
	 * updating the original. Every submit path must go through here.
	 */
	function buildPayload( setStatus ) {
		var fd = new FormData( form );
		fd.append( 'action', 'tb_submit_listing' );
		fd.append( 'nonce',  nonce );
		fd.append( 'set_status', setStatus );

		var postId = parseInt( form.dataset.postId, 10 );
		if ( postId > 0 ) {
			fd.append( 'post_id', postId );
		}

		return fd;
	}

	function submitListing( setStatus ) {
		var fd = buildPayload( setStatus );

		lockBtns( true );

		fetch( ajaxUrl, { method: 'POST', body: fd } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( data.success ) {
					isDirty = false; // Mark clean before redirect so beforeunload doesn't fire.
					var redirect = ( data.data && data.data.redirect ) ? data.data.redirect : form.dataset.redirect;
					if ( redirect ) {
						window.location.href = redirect;
					}
				} else {
					var msg = ( data.data && data.data.message ) ? data.data.message : ( tbAddListing.saveError || 'Помилка збереження' );
					alert( msg );
					lockBtns( false );
				}
			} )
			.catch( function () {
				alert( tbAddListing.networkError || 'Помилка мережі' );
				lockBtns( false );
			} );
	}

	function bindBtn( id, status ) {
		var el = document.getElementById( id );
		if ( el ) el.addEventListener( 'click', function () { submitListing( status ); } );
	}

	bindBtn( 'tb-al-btn-draft',        'draft' );
	bindBtn( 'tb-al-btn-publish',       'pending' );
	bindBtn( 'tb-al-btn-draft-foot',   'draft' );
	bindBtn( 'tb-al-btn-publish-foot', 'pending' );

	// ── Exit Alert (Figma 410:6461) ──────────────────────────────────
	var exitModal    = document.getElementById( 'tb-exit-alert' );
	var exitBackdrop = document.getElementById( 'tb-exit-backdrop' );
	var stayBtn      = document.getElementById( 'tb-exit-stay' );
	var exitSaveBtn  = document.getElementById( 'tb-exit-save' );
	var pendingHref  = null;

	function openExitAlert( href ) {
		pendingHref = href;
		if ( exitModal ) {
			exitModal.removeAttribute( 'hidden' );
			document.body.style.overflow = 'hidden';
			if ( stayBtn ) stayBtn.focus();
		}
	}

	function closeExitAlert() {
		pendingHref = null;
		if ( exitModal ) {
			exitModal.setAttribute( 'hidden', '' );
			document.body.style.overflow = '';
		}
	}

	if ( stayBtn ) stayBtn.addEventListener( 'click', closeExitAlert );
	if ( exitBackdrop ) exitBackdrop.addEventListener( 'click', closeExitAlert );

	if ( exitSaveBtn ) {
		exitSaveBtn.addEventListener( 'click', function () {
			exitSaveBtn.disabled = true;
			var dest = pendingHref || '/cabinet/';
			var fd = buildPayload( 'draft' );
			fetch( ajaxUrl, { method: 'POST', body: fd } )
				.finally( function () {
					isDirty = false;
					window.location.href = dest;
				} );
		} );
	}

	// Intercept in-page navigation links when form is dirty.
	document.addEventListener( 'click', function ( e ) {
		if ( ! isDirty || ! exitModal ) return;
		var link = e.target.closest( 'a[href]' );
		if ( ! link ) return;
		var href = link.href;
		if ( ! href || href.indexOf( '#' ) === 0 || href.indexOf( 'mailto:' ) === 0 ) return;
		if ( href === window.location.href ) return;
		e.preventDefault();
		openExitAlert( href );
	} );

	// Browser-level guard for tab close / reload (native dialog, last resort).
	window.addEventListener( 'beforeunload', function ( e ) {
		if ( isDirty ) {
			e.preventDefault();
			e.returnValue = '';
		}
	} );
} )();
