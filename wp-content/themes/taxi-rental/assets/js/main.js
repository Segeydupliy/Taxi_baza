/* TaxiBaza.sk — main JS */
( function () {
	'use strict';

	/* -----------------------------------------------------------------------
	   Mobile nav drawer
	   --------------------------------------------------------------------- */
	const toggle   = document.getElementById( 'tb-menu-toggle' );
	const closeBtn = document.getElementById( 'tb-menu-close' );
	const drawer   = document.getElementById( 'tb-mobile-nav' );
	const backdrop = document.getElementById( 'tb-menu-backdrop' );

	function openDrawer() {
		drawer.classList.add( 'is-open' );
		backdrop.classList.add( 'is-visible' );
		toggle.setAttribute( 'aria-expanded', 'true' );
		drawer.setAttribute( 'aria-hidden', 'false' );
		document.body.style.overflow = 'hidden';
		if ( closeBtn ) closeBtn.focus();
	}

	function closeDrawer() {
		drawer.classList.remove( 'is-open' );
		backdrop.classList.remove( 'is-visible' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		drawer.setAttribute( 'aria-hidden', 'true' );
		document.body.style.overflow = '';
		toggle.focus();
	}

	if ( toggle && drawer ) {
		toggle.addEventListener( 'click', openDrawer );
	}
	if ( closeBtn ) {
		closeBtn.addEventListener( 'click', closeDrawer );
	}
	if ( backdrop ) {
		backdrop.addEventListener( 'click', closeDrawer );
	}

	// Close on Escape
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && drawer && drawer.classList.contains( 'is-open' ) ) {
			closeDrawer();
		}
	} );

	/* -----------------------------------------------------------------------
	   Mobile nav accordion (Міста, Про сервіс)
	   --------------------------------------------------------------------- */
	const accordionToggles = document.querySelectorAll( '.tb-mobile-nav__toggle' );

	accordionToggles.forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			const targetId = btn.getAttribute( 'aria-controls' );
			const sub      = document.getElementById( targetId );
			const expanded = btn.getAttribute( 'aria-expanded' ) === 'true';

			btn.setAttribute( 'aria-expanded', String( ! expanded ) );
			if ( sub ) sub.classList.toggle( 'is-open', ! expanded );
		} );
	} );

	/* -----------------------------------------------------------------------
	   Desktop nav dropdowns (click-to-open, close on outside click)
	   --------------------------------------------------------------------- */
	const dropdownBtns = document.querySelectorAll( '.tb-nav__btn[aria-haspopup]' );

	dropdownBtns.forEach( function ( btn ) {
		btn.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			const item     = btn.closest( '.tb-nav__item--has-dropdown' );
			const isOpen   = item.classList.contains( 'is-open' );

			// Close all other open dropdowns.
			document.querySelectorAll( '.tb-nav__item--has-dropdown.is-open' ).forEach( function ( el ) {
				el.classList.remove( 'is-open' );
				el.querySelector( '.tb-nav__btn' ).setAttribute( 'aria-expanded', 'false' );
			} );

			if ( ! isOpen ) {
				item.classList.add( 'is-open' );
				btn.setAttribute( 'aria-expanded', 'true' );
			}
		} );
	} );

	document.addEventListener( 'click', function () {
		document.querySelectorAll( '.tb-nav__item--has-dropdown.is-open' ).forEach( function ( el ) {
			el.classList.remove( 'is-open' );
			el.querySelector( '.tb-nav__btn' ).setAttribute( 'aria-expanded', 'false' );
		} );
	} );

	/* -----------------------------------------------------------------------
	   User avatar dropdown (logged-in header state)
	   --------------------------------------------------------------------- */
	var userToggle = document.getElementById( 'tb-user-dropdown-toggle' );
	if ( userToggle ) {
		userToggle.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			var isOpen = userToggle.getAttribute( 'aria-expanded' ) === 'true';
			userToggle.setAttribute( 'aria-expanded', isOpen ? 'false' : 'true' );
		} );

		userToggle.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				userToggle.click();
			}
			if ( e.key === 'Escape' ) {
				userToggle.setAttribute( 'aria-expanded', 'false' );
				userToggle.focus();
			}
		} );

		document.addEventListener( 'click', function () {
			userToggle.setAttribute( 'aria-expanded', 'false' );
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				userToggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	}

	/* -----------------------------------------------------------------------
	   About section accordion (homepage)
	   --------------------------------------------------------------------- */
	var aboutAccordion = document.querySelector( '[data-accordion]' );
	if ( aboutAccordion ) {
		aboutAccordion.addEventListener( 'click', function ( e ) {
			var toggle = e.target.closest( '[data-accordion-toggle]' );
			if ( ! toggle ) return;

			var item   = toggle.closest( '.tb-about__item' );
			var isOpen = item.classList.contains( 'is-expanded' );

			// Collapse all — CSS grid-rows transition on .tb-about__item-body-wrap
			// handles the smooth height animation; the chevron rotates via CSS too.
			aboutAccordion.querySelectorAll( '.tb-about__item.is-expanded' ).forEach( function ( el ) {
				el.classList.remove( 'is-expanded' );
				el.querySelector( '[data-accordion-toggle]' ).setAttribute( 'aria-expanded', 'false' );
			} );

			// Expand clicked if it was closed
			if ( ! isOpen ) {
				item.classList.add( 'is-expanded' );
				toggle.setAttribute( 'aria-expanded', 'true' );
			}
		} );
	}
} )();

/* Legal TOC: open on desktop, collapsed on mobile. */
( function () {
	var toc = document.querySelector( '.tb-legal__toc-box' );
	if ( toc ) {
		toc.open = window.matchMedia( '(min-width: 901px)' ).matches;
	}
} )();

/* Contact form — AJAX submit to taxi_rental_contact. */
( function () {
	var form = document.getElementById( 'tb-contact-form' );
	if ( ! form || typeof taxiRental === 'undefined' ) {
		return;
	}

	var notice      = document.getElementById( 'tb-contact-notice' );
	var errorBanner = document.getElementById( 'tb-contact-error-banner' );
	var submit      = form.querySelector( '.tb-contact-form__submit' );

	// Store original placeholders once.
	form.querySelectorAll( '[data-error-placeholder]' ).forEach( function ( el ) {
		el.dataset.originalPlaceholder = el.placeholder || '';
	} );

	function showNotice( type, message ) {
		notice.hidden = false;
		notice.className = 'tb-contact-form__notice tb-contact-form__notice--' + type;
		notice.textContent = message;
	}

	function clearErrors() {
		form.querySelectorAll( '.tb-contact-form__field.is-invalid' ).forEach( function ( field ) {
			field.classList.remove( 'is-invalid' );
			var errorSpan = field.querySelector( '.tb-field-error' );
			if ( errorSpan ) {
				errorSpan.hidden = true;
				errorSpan.textContent = '';
			}
			var inp = field.querySelector( '[data-error-placeholder]' );
			if ( inp ) {
				inp.placeholder = inp.dataset.originalPlaceholder || '';
			}
		} );
		if ( errorBanner ) {
			errorBanner.hidden = true;
		}
	}

	function markInvalid( name ) {
		var inp = form.querySelector( '[name="' + name + '"]' );
		if ( ! inp ) {
			return;
		}
		var field = inp.closest( '.tb-contact-form__field' );
		if ( ! field ) {
			return;
		}
		field.classList.add( 'is-invalid' );
		var errorSpan = field.querySelector( '.tb-field-error' );
		if ( errorSpan ) {
			errorSpan.hidden = false;
		}
		if ( inp.dataset.errorPlaceholder ) {
			inp.placeholder = inp.dataset.errorPlaceholder;
		}
	}

	function markField( inp, msg ) {
		var field = inp.closest( '.tb-contact-form__field' );
		if ( field ) field.classList.add( 'is-invalid' );
		var span = field ? field.querySelector( '.tb-field-error' ) : null;
		if ( span ) {
			// Preserve PHP-rendered text; only fill if span is empty.
			if ( msg && ! span.textContent.trim() ) span.textContent = msg;
			span.hidden = false;
		}
	}

	function validateClient() {
		var valid = true;
		form.querySelectorAll( 'input[required], select[required], textarea[required]' ).forEach( function ( inp ) {
			var val = inp.tagName === 'SELECT' ? inp.value : inp.value.trim();
			if ( ! val ) {
				markField( inp, '' );
				valid = false;
			} else if ( inp.type === 'email' && ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( val ) ) {
				markField( inp, '' );
				valid = false;
			} else if ( inp.dataset.minLength && val.length < +inp.dataset.minLength ) {
				markField( inp, '' );
				valid = false;
			}
		} );
		return valid;
	}

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();

		clearErrors();
		notice.hidden = true;

		// Client-side validation before AJAX.
		if ( ! validateClient() ) {
			if ( errorBanner ) errorBanner.hidden = false;
			var first = form.querySelector( '.tb-contact-form__field.is-invalid input, .tb-contact-form__field.is-invalid select, .tb-contact-form__field.is-invalid textarea' );
			if ( first ) first.scrollIntoView( { block: 'center', behavior: 'smooth' } );
			return;
		}

		var data = new FormData( form );
		data.append( 'action', 'taxi_rental_contact' );
		data.append( 'nonce', taxiRental.nonce );

		submit.disabled = true;

		fetch( taxiRental.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' } )
			.then( function ( res ) { return res.json(); } )
			.then( function ( json ) {
				if ( json.success ) {
					/* #99 — show the confirmation card in place of the form
					   (Figma 321:3039), not a green line above it. */
					var done = document.getElementById( 'tb-contact-success' );
					form.reset();
					if ( notice ) notice.hidden = true;
					if ( errorBanner ) errorBanner.hidden = true;

					if ( done ) {
						form.hidden = true;
						done.hidden = false;
						/* #99 — scrolling to the card itself left the user mid-page
						   with no context on tablet (the card can land anywhere
						   depending on how far down the form was). Scroll to the
						   very top of the page instead, same as a fresh page load. */
						window.scrollTo( { top: 0, behavior: 'smooth' } );
					} else {
						showNotice( 'success', json.data.message );
					}
					return;
				} else {
					showNotice( 'error', ( json.data && json.data.message ) || 'Error' );
					if ( json.data && json.data.fields ) {
						Object.keys( json.data.fields ).forEach( function ( name ) {
							markInvalid( name );
						} );
						if ( errorBanner ) {
							errorBanner.hidden = false;
						}
					}
				}
			} )
			.catch( function () {
				showNotice( 'error', 'Не вдалося надіслати повідомлення. Спробуйте пізніше.' );
			} )
			.finally( function () {
				submit.disabled = false;
			} );
	} );

	/* "Надіслати ще одне" — back to an empty form. */
	var againBtn = document.querySelector( '[data-tb-contact-again]' );
	if ( againBtn ) {
		againBtn.addEventListener( 'click', function () {
			var done = document.getElementById( 'tb-contact-success' );
			if ( done ) done.hidden = true;
			form.hidden = false;
			form.scrollIntoView( { block: 'nearest', behavior: 'smooth' } );
			var first = form.querySelector( 'input, select, textarea' );
			if ( first ) first.focus();
		} );
	}
} )();

/* Generic modal open/close (Send Message, Delete Account). */
( function () {
	document.querySelectorAll( '[data-modal-open]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var modal = document.getElementById( btn.getAttribute( 'data-modal-open' ) );
			if ( modal ) {
				// Reopening after a successful send must show the form again,
				// not the confirmation screen left over from last time.
				var doneEl = modal.querySelector( '.tb-owner-success' );
				if ( doneEl && ! doneEl.hidden ) {
					doneEl.hidden = true;
					modal.querySelectorAll( '.tb-owner-form, .tb-modal__car' ).forEach( function ( el ) {
						el.hidden = false;
					} );
				}
				modal.hidden = false;
				document.body.style.overflow = 'hidden';
				var focusable = modal.querySelector( 'input, select, textarea, button' );
				if ( focusable ) focusable.focus();
			}
		} );
	} );

	function closeModal( modal ) {
		modal.hidden = true;
		document.body.style.overflow = '';
	}

	document.querySelectorAll( '.tb-modal' ).forEach( function ( modal ) {
		modal.querySelectorAll( '[data-modal-close]' ).forEach( function ( el ) {
			el.addEventListener( 'click', function () { closeModal( modal ); } );
		} );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) {
			document.querySelectorAll( '.tb-modal:not([hidden])' ).forEach( closeModal );
		}
	} );
} )();

/* Send Message to owner — AJAX submit. */
( function () {
	var form = document.getElementById( 'tb-owner-form' );
	if ( ! form || typeof taxiRental === 'undefined' ) {
		return;
	}

	var notice    = document.getElementById( 'tb-owner-notice' );
	var errBanner = document.getElementById( 'tb-owner-err-banner' );
	var submit    = form.querySelector( '.tb-owner-form__submit' );

	function clearOwnerErrors() {
		form.querySelectorAll( '.tb-contact-form__field.is-invalid' ).forEach( function ( el ) {
			el.classList.remove( 'is-invalid' );
			var span = el.querySelector( '.tb-field-error' );
			if ( span ) { span.textContent = ''; span.hidden = true; }
		} );
		if ( errBanner ) errBanner.setAttribute( 'hidden', '' );
	}

	function markOwnerField( inp, msg ) {
		var field = inp.closest( '.tb-contact-form__field' );
		if ( field ) field.classList.add( 'is-invalid' );
		var span = field ? field.querySelector( '.tb-field-error' ) : null;
		if ( span ) { if ( msg ) span.textContent = msg; span.hidden = false; }
	}

	function validateClient() {
		var valid = true;
		form.querySelectorAll( 'input[required], select[required], textarea[required]' ).forEach( function ( inp ) {
			var val = inp.tagName === 'SELECT' ? inp.value : inp.value.trim();
			if ( ! val ) {
				markOwnerField( inp, inp.dataset.errRequired || 'Toto pole je povinné' );
				valid = false;
			} else if ( inp.type === 'email' && ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( val ) ) {
				markOwnerField( inp, inp.dataset.errEmail || 'Zadajte platnú emailovú adresu' );
				valid = false;
			} else if ( inp.dataset.minLength && val.length < +inp.dataset.minLength ) {
				markOwnerField( inp, inp.dataset.errMinlength || 'Príliš krátke' );
				valid = false;
			} else if ( inp.type === 'date' && inp.min && val < inp.min ) {
				/* #120 — a past date was still going through. forms.js does have
				   this check, but it only wires up forms carrying
				   data-tb-validate; this modal has its own validator, so the
				   rule has to exist here too. [min] stops the native picker,
				   not a hand-typed date. */
				markOwnerField( inp, inp.dataset.errMin || 'Zvoľte, prosím, dnešný alebo neskorší dátum' );
				valid = false;
			}
		} );
		return valid;
	}

	/* Flag a bad date as soon as the field is left, not only on submit. */
	form.querySelectorAll( 'input[type="date"]' ).forEach( function ( inp ) {
		inp.addEventListener( 'blur', function () {
			if ( inp.value && inp.min && inp.value < inp.min ) {
				markOwnerField( inp, inp.dataset.errMin || 'Zvoľte, prosím, dnešný alebo neskorší dátum' );
			}
		} );
		inp.addEventListener( 'input', function () {
			var field = inp.closest( '.tb-contact-form__field' );
			if ( field ) {
				field.classList.remove( 'is-invalid' );
				var span = field.querySelector( '.tb-field-error' );
				if ( span ) { span.hidden = true; }
			}
		} );
	} );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();

		clearOwnerErrors();
		if ( notice ) notice.hidden = true;

		// Client-side validation before AJAX.
		if ( ! validateClient() ) {
			if ( errBanner ) errBanner.removeAttribute( 'hidden' );
			var first = form.querySelector( '.tb-contact-form__field.is-invalid input, .tb-contact-form__field.is-invalid select, .tb-contact-form__field.is-invalid textarea' );
			if ( first ) first.scrollIntoView( { block: 'center', behavior: 'smooth' } );
			return;
		}

		var data = new FormData( form );
		data.append( 'action', 'taxi_rental_owner_message' );
		data.append( 'nonce', taxiRental.nonce );

		submit.disabled = true;

		fetch( taxiRental.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' } )
			.then( function ( res ) { return res.json(); } )
			.then( function ( json ) {
				notice.hidden = false;
				if ( json.success ) {
					/* #101 — swap the whole body for the confirmation screen
					   (Figma 356:6355) instead of appending a line of green
					   text under a form that is still sitting there. */
					var done = document.getElementById( 'tb-owner-success' );
					var desc = done ? done.querySelector( '.tb-owner-success__desc' ) : null;

					if ( desc ) {
						var phoneEl = form.querySelector( '[name="phone"]' );
						desc.textContent = ( desc.dataset.tpl || '' )
							.replace( '%1$s', desc.dataset.owner || '' )
							.replace( '%2$s', phoneEl ? phoneEl.value : '' );
					}

					form.reset();
					notice.hidden = true;
					form.hidden = true;

					var carRow = form.closest( '.tb-modal__panel' );
					carRow = carRow ? carRow.querySelector( '.tb-modal__car' ) : null;
					if ( carRow ) carRow.hidden = true;

					if ( done ) {
						done.hidden = false;
						done.scrollIntoView( { block: 'nearest', behavior: 'smooth' } );
					}
					return;
				} else {
					notice.className = 'tb-contact-form__notice tb-contact-form__notice--error';
					notice.textContent = ( json.data && json.data.message ) || ( window.taxiRental && taxiRental.genericError ) || 'Error';
					if ( json.data && json.data.fields ) {
						Object.keys( json.data.fields ).forEach( function ( name ) {
							var inp = form.querySelector( '[name="' + name + '"]' );
							if ( inp ) markOwnerField( inp, json.data.fields[ name ] );
						} );
						if ( errBanner ) errBanner.removeAttribute( 'hidden' );
					}
				}
				notice.scrollIntoView( { block: 'nearest', behavior: 'smooth' } );
			} )
			.catch( function () {
				notice.hidden = false;
				notice.className = 'tb-contact-form__notice tb-contact-form__notice--error';
				notice.textContent = ( window.taxiRental && taxiRental.submitFailed ) || 'Žiadosť sa nepodarilo odoslať. Skúste neskôr.';
			} )
			.finally( function () {
				submit.disabled = false;
			} );
	} );
} )();

/* Language switcher — desktop popup + mobile full-screen panel. */
( function () {
	var MOBILE_BP = 900;

	var btn          = document.getElementById( 'tb-lang-btn' );
	var popup        = document.getElementById( 'tb-lang-popup' );
	var popupClose   = document.getElementById( 'tb-lang-popup-close' );
	var popupOverlay = document.getElementById( 'tb-lang-popup-overlay' );
	var screen       = document.getElementById( 'tb-lang-screen' );
	var screenClose  = document.getElementById( 'tb-lang-screen-close' );
	var screenBtn    = document.getElementById( 'tb-lang-screen-close-btn' );

	if ( ! btn ) return;

	function isMobile() {
		return window.innerWidth <= MOBILE_BP;
	}

	function openPopup() {
		if ( ! popup ) return;
		popup.hidden = false;
		btn.setAttribute( 'aria-expanded', 'true' );
		document.body.style.overflow = 'hidden';
		if ( popupClose ) popupClose.focus();
	}

	function closePopup() {
		if ( ! popup ) return;
		popup.hidden = true;
		btn.setAttribute( 'aria-expanded', 'false' );
		document.body.style.overflow = '';
		btn.focus();
	}

	function openScreen() {
		if ( ! screen ) return;
		screen.classList.add( 'is-open' );
		screen.setAttribute( 'aria-hidden', 'false' );
		document.body.style.overflow = 'hidden';
		if ( screenClose ) screenClose.focus();
	}

	function closeScreen() {
		if ( ! screen ) return;
		screen.classList.remove( 'is-open' );
		screen.setAttribute( 'aria-hidden', 'true' );
		document.body.style.overflow = '';
		btn.focus();
	}

	btn.addEventListener( 'click', function ( e ) {
		e.stopPropagation();
		if ( isMobile() ) {
			openScreen();
		} else {
			openPopup();
		}
	} );

	if ( popupClose ) {
		popupClose.addEventListener( 'click', closePopup );
	}
	if ( popupOverlay ) {
		popupOverlay.addEventListener( 'click', closePopup );
	}
	if ( screenClose ) {
		screenClose.addEventListener( 'click', closeScreen );
	}
	if ( screenBtn ) {
		screenBtn.addEventListener( 'click', closeScreen );
	}

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key !== 'Escape' ) return;
		if ( popup && ! popup.hidden ) closePopup();
		if ( screen && screen.classList.contains( 'is-open' ) ) closeScreen();
	} );
} )();

/* ---------------------------------------------------------------------------
   Toolbars: navigation selects, collapsible search, blog sort  (#94–#96, #105–#106)
   ---------------------------------------------------------------------------
   Shared by the cabinet listing toolbar and the blog archive toolbar. Both
   carry data-tb-toolbar; each select option value is simply the URL the
   equivalent desktop pill links to, so there is one source of truth for where
   a filter goes.
--------------------------------------------------------------------------- */
( function () {
	'use strict';

	document.querySelectorAll( '[data-tb-nav-select]' ).forEach( function ( select ) {
		select.addEventListener( 'change', function () {
			if ( this.value ) {
				window.location.href = this.value;
			}
		} );
	} );

	document.querySelectorAll( '[data-tb-search-toggle]' ).forEach( function ( toggle ) {
		var toolbar = toggle.closest( '[data-tb-toolbar]' );
		if ( ! toolbar ) return;

		var field = toolbar.querySelector( '.tb-cabinet__search-input, .tb-blog__search-input' );

		// A search is already running — open it, or the term is invisible.
		if ( field && field.value ) {
			toolbar.classList.add( 'is-search-open' );
			toggle.setAttribute( 'aria-expanded', 'true' );
		}

		toggle.addEventListener( 'click', function () {
			var open = toolbar.classList.toggle( 'is-search-open' );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			if ( open && field ) field.focus();
		} );
	} );

	var blogSort = document.querySelector( '[data-tb-blog-sort]' );
	if ( blogSort ) {
		var sortToggle = blogSort.querySelector( '[data-tb-sort-toggle]' );

		if ( sortToggle ) {
			sortToggle.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				var open = blogSort.classList.toggle( 'is-open' );
				sortToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			} );
		}

		document.addEventListener( 'click', function ( e ) {
			if ( ! blogSort.contains( e.target ) ) {
				blogSort.classList.remove( 'is-open' );
				if ( sortToggle ) sortToggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				blogSort.classList.remove( 'is-open' );
				if ( sortToggle ) sortToggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	}
}() );
