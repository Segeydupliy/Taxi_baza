/* TaxiBaza — GA4 events (G-82EESDNFQV). Events reach gtag only after consent (Complianz loads GA); until then they wait up to 60 s. No names, e-mails, phones or message texts — only IDs, types and places. */
(function () {
  'use strict';
  var queue = [], waited = 0, timer = null;
  function lang(){ return (document.documentElement.lang || 'sk').slice(0,2).toLowerCase(); }
  function flush(){
    if (typeof window.gtag === 'function') {
      while (queue.length) { var e = queue.shift(); window.gtag('event', e.name, e.params); }
      clearInterval(timer); timer = null; return;
    }
    waited += 2;
    if (waited >= 60) { queue.length = 0; clearInterval(timer); timer = null; }
  }
  window.tbTrack = function (name, params) {
    params = params || {}; params.site_lang = params.site_lang || lang();
    if (typeof window.gtag === 'function') { window.gtag('event', name, params); return; }
    queue.push({ name: name, params: params });
    if (!timer) { waited = 0; timer = setInterval(flush, 2000); }
  };
  function listingId(el){
    var f = document.querySelector('#tb-owner-form input[name="listing_id"]');
    if (f && f.value) return f.value;
    var c = el && el.closest('[data-listing-id]'); return c ? c.getAttribute('data-listing-id') : undefined;
  }
  function where(el){
    if (el.closest('.tb-owner-box')) return 'listing_owner_box';
    if (el.closest('.tb-lightbox')) return 'listing_gallery';
    if (el.closest('.tb-cta-tg')) return 'cta_block';
    if (el.closest('footer')) return 'footer';
    if (el.closest('header')) return 'header';
    return 'other';
  }
  document.addEventListener('click', function (ev) {
    var el = ev.target.closest('a, button'); if (!el) return;
    if (el.matches('.tb-owner-box__cta, .tb-lightbox__mobile-cta') ||
        /Kontaktovať vlastníka|Зв.язатися з власником|Связаться с владельцем|Contact (the )?owner/i.test(el.textContent)) {
      window.tbTrack('contact_owner_click', { listing_id: listingId(el), location: where(el) }); return;
    }
    var href = el.getAttribute('href') || '';
    if (/^https?:\/\/(www\.)?t\.me\//i.test(href)) {
      window.tbTrack('telegram_click', { link_url: href, location: where(el) });
    }
  }, true);
})();

/* generate_lead — only when the rental request (#tb-owner-form, AJAX taxi_rental_owner_message) really succeeded. */
(function () {
  'use strict';
  if (!window.fetch) { return; }
  var orig = window.fetch;
  window.fetch = function (input, init) {
    var body = init && init.body, action = '', listing = '';
    if (body && typeof FormData !== 'undefined' && body instanceof FormData) {
      action = String(body.get('action') || '');
      listing = String(body.get('listing_id') || '');
    }
    var p = orig.apply(this, arguments);
    if (action !== 'taxi_rental_owner_message') { return p; }
    return p.then(function (res) {
      try {
        res.clone().json().then(function (j) {
          if (j && j.success === true && window.tbTrack) { window.tbTrack('generate_lead', { form: 'rental_request', listing_id: listing || undefined }); }
        }).catch(function () {});
      } catch (e) { /* not JSON */ }
      return res;
    });
  };
})();
