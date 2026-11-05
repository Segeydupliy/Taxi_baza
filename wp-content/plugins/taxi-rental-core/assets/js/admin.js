( function () {
	'use strict';

	/* ---- Reject prompt ---- */
	document.querySelectorAll( '.tb-action-reject' ).forEach( function ( link ) {
		link.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			var baseUrl = this.getAttribute( 'data-nonce-url' );
			if ( ! baseUrl ) return;

			var reason = window.prompt( 'Причина відхилення (необов\'язково):' );
			if ( reason === null ) return;

			window.location.href = baseUrl + '&rejection_reason=' + encodeURIComponent( reason );
		} );
	} );

	/* ---- Delete confirmation ---- */
	document.querySelectorAll( '.tb-action-delete' ).forEach( function ( link ) {
		link.addEventListener( 'click', function ( e ) {
			if ( ! window.confirm( 'Видалити оголошення? Цю дію не можна скасувати.' ) ) {
				e.preventDefault();
			}
		} );
	} );

	document.querySelectorAll( '.tb-action-delete-term' ).forEach( function ( link ) {
		link.addEventListener( 'click', function ( e ) {
			if ( ! window.confirm( 'Видалити запис? Цю дію не можна скасувати.' ) ) {
				e.preventDefault();
			}
		} );
	} );

	/* ---- Branded page header for taxonomy + post pages ---- */
	var cfg    = window.tbAdminCfg || {};
	var label  = ( cfg.labels || {} )[ cfg.screen || '' ];

	if ( label ) {
		function injectTbHeader() {
			var wrap = document.querySelector( '#wpbody-content .wrap' );
			if ( ! wrap ) return;
			if ( wrap.querySelector( '.tb-admin-h1' ) ) return; // already injected by PHP

			var h1 = wrap.querySelector( 'h1' );
			if ( ! h1 ) return;

			var banner = document.createElement( 'h1' );
			banner.className = 'tb-admin-h1';
			banner.innerHTML =
				'<span class="tb-admin-logo">TaxiBaza<span class="tb-admin-dot">.</span>sk</span>' +
				' &mdash; ' +
				label;

			h1.parentNode.insertBefore( banner, h1 );
			h1.style.cssText = 'position:absolute;opacity:0;pointer-events:none;';

			// Move "Add New" / "Page title action" button next to our header
			var action = wrap.querySelector( '.page-title-action' );
			if ( action ) {
				action.style.removeProperty( 'display' );
				banner.parentNode.insertBefore( action, banner.nextSibling );
			}
		}

		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', injectTbHeader );
		} else {
			injectTbHeader();
		}
	}

} )();
