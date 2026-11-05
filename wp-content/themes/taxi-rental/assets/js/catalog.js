/* catalog.js — catalog filters (/avto/ and city archives).
 *
 * The cabinet search toggle used to be wired here too, in a second delegated
 * listener that duplicated the one in main.js and fought it on every click
 * (#106) — that toolbar isn't replaced by AJAX, so it belongs in main.js only.
 *
 * Filtering is AJAX: picking a filter swaps the results and the filter bar in
 * place and pushes the new URL, instead of reloading. That is what makes the
 * mobile sheet usable at all — every choice used to be a full navigation, so
 * the sheet closed on each tap and "Model" (which only unlocks once a Make is
 * set) could never be reached.
 *
 * Because both the bar and the results are replaced wholesale, every handler
 * here is delegated from `document`. Nothing is bound to an element that a
 * swap can destroy, and the sheet's open/level state lives on <body> for the
 * same reason.
 */
(function () {
  'use strict';

  /* =====================================================================
     Cabinet toolbar — status select + collapsible search
     ===================================================================== */
  document.addEventListener('change', function (e) {
    var select = e.target.closest('[data-tb-nav-select]');
    if (select && select.value) { window.location.href = select.value; }
  });

  /* =====================================================================
     Catalog
     ===================================================================== */
  var root = document.querySelector('[data-tb-catalog]');

  function results() { return root && root.querySelector('[data-tb-results]'); }

  function closeAllDrops() {
    document.querySelectorAll('[data-filter-drop].is-open').forEach(function (d) {
      d.classList.remove('is-open');
      var btn = d.querySelector('[data-filter-toggle]');
      if (btn) { btn.setAttribute('aria-expanded', 'false'); }
    });
  }

  /* ── Mobile sheet state ──────────────────────────────────────────────
     On <body>, not on the bar: the bar is replaced on every AJAX update. */
  var rootTitle = null;

  function sheetTitle() { return document.querySelector('[data-filters-title]'); }

  function setLevel1() {
    document.body.classList.remove('tb-sheet-level-2');
    closeAllDrops();
    var t = sheetTitle();
    if (t && rootTitle !== null) { t.textContent = rootTitle; }
  }

  function openSheet() {
    var t = sheetTitle();
    if (t && rootTitle === null) { rootTitle = t.textContent; }
    document.body.classList.add('tb-sheet-open');
    setLevel1();
    document.body.style.overflow = 'hidden';
  }

  function closeSheet() {
    document.body.classList.remove('tb-sheet-open');
    setLevel1();
    document.body.style.overflow = '';
  }

  function sheetIsOpen() { return document.body.classList.contains('tb-sheet-open'); }

  /* ── AJAX ────────────────────────────────────────────────────────────── */
  var inFlight = null;

  function ajaxUrl() {
    return (typeof taxiRental !== 'undefined' && taxiRental.ajaxUrl) ? taxiRental.ajaxUrl : null;
  }

  /**
   * Load a catalog URL without navigating.
   *
   * @param {string}  href   Full URL the clicked link points at.
   * @param {boolean} append True for "load more" — keep the current cards.
   * @returns {boolean} false when it cannot run, so the caller lets the
   *                    browser follow the link instead.
   */
  function load(href, append) {
    var url = ajaxUrl();
    if (!root || !url) { return false; }

    var target;
    try { target = new URL(href, window.location.origin); } catch (err) { return false; }

    if (inFlight) { inFlight.abort(); }
    inFlight = (typeof AbortController !== 'undefined') ? new AbortController() : null;

    root.classList.add('is-loading');

    var body = new URLSearchParams();
    body.set('action', 'tb_filter_listings');
    body.set('nonce', root.dataset.nonce || '');
    body.set('query', target.search);
    body.set('base', root.dataset.base || '');
    if (root.dataset.city) { body.set('city', root.dataset.city); }
    /* #102 — admin-ajax.php has no /uk/ in its URL, so WPML can't tell which
       language the request is for and falls back to the site default (sk).
       <html lang="…"> is WPML-aware (language_attributes() in header.php),
       so it always matches the page this request was made from. */
    body.set('lang', (document.documentElement.lang || '').split('-')[0]);

    fetch(url, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      signal: inFlight ? inFlight.signal : undefined
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (!json || !json.success) { throw new Error('bad response'); }

        var box = results();
        if (append) {
          // Keep what is on screen and graft the next page's cards on.
          var holder = document.createElement('div');
          holder.innerHTML = json.data.results;
          var incoming = holder.querySelector('#tb-catalog-grid');
          var current  = box ? box.querySelector('#tb-catalog-grid') : null;
          if (incoming && current) {
            while (incoming.firstElementChild) { current.appendChild(incoming.firstElementChild); }
          }
          var oldMore = box ? box.querySelector('.tb-catalog__load-more') : null;
          var newMore = holder.querySelector('.tb-catalog__load-more');
          if (oldMore) { oldMore.remove(); }
          if (newMore && box) { box.appendChild(newMore); }
        } else {
          if (box) { box.innerHTML = json.data.results; }

          var filtersBox = root.querySelector('[data-tb-filters]');
          if (filtersBox) { filtersBox.innerHTML = json.data.filters; }

          // The bar is new markup, so the drill-down title has to be re-read.
          rootTitle = null;
          if (sheetIsOpen()) { openSheet(); }

          var countEl = root.querySelector('[data-tb-count]');
          if (countEl) { countEl.textContent = json.data.count; }
        }

        window.history.pushState(null, '', target.toString());
      })
      .catch(function (err) {
        if (err && err.name === 'AbortError') { return; }
        // Anything unexpected: fall back to a normal page load.
        window.location.href = href;
      })
      .finally(function () {
        root.classList.remove('is-loading');
        inFlight = null;
      });

    return true;
  }

  /* ── Clicks ──────────────────────────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    /* Cabinet search toggle is wired directly in main.js — it lives outside
       the catalog and isn't replaced by AJAX, so it doesn't need delegation
       from here. A second copy used to live in this file too: one tap fired
       both listeners, the second undoing the first, so the button did
       nothing (#106). */

    /* Sheet chrome. */
    if (e.target.closest('[data-filters-open]')) {
      e.preventDefault();
      if (sheetIsOpen()) { closeSheet(); } else { openSheet(); }
      return;
    }
    if (e.target.closest('[data-filters-close]')) { e.preventDefault(); closeSheet(); return; }
    if (e.target.closest('[data-filters-back]'))  { e.preventDefault(); setLevel1(); return; }

    /* A dropdown / drill-down trigger. */
    var toggle = e.target.closest('[data-filter-toggle]');
    if (toggle) {
      e.preventDefault();
      e.stopPropagation();

      var drop    = toggle.closest('[data-filter-drop]');
      var wasOpen = drop && drop.classList.contains('is-open');
      closeAllDrops();

      if (drop && !wasOpen) {
        drop.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
      }

      // Inside the sheet, opening a filter is a drill-down. The panel's
      // aria-label carries the group's DEFAULT name, which is what the header
      // must show — the pill's own text is the selected value once set.
      if (sheetIsOpen() && drop && !drop.classList.contains('tb-filter-drop--sort')) {
        var t = sheetTitle();
        if (drop.classList.contains('is-open')) {
          document.body.classList.add('tb-sheet-level-2');
          var panel = drop.querySelector('.tb-filter-panel');
          var label = panel && panel.getAttribute('aria-label');
          if (t) { t.textContent = label || rootTitle || ''; }
        } else {
          setLevel1();
        }
      }
      return;
    }

    /* Load more. */
    var more = e.target.closest('[data-tb-load-more]');
    if (more && more.href) {
      if (load(more.href, true)) { e.preventDefault(); }
      return;
    }

    /* Any filter or sort link. */
    var link = e.target.closest('[data-filter-bar] a[href], [data-tb-results] .tb-catalog__empty a[href]');
    if (link && link.href && !link.target) {
      if (load(link.href, false)) {
        e.preventDefault();
        closeAllDrops();
        if (sheetIsOpen()) { setLevel1(); }
      }
      return;
    }

    /* Click anywhere else closes open dropdowns — but not while the sheet
       is up, where the whole screen belongs to the filters. */
    if (!sheetIsOpen() && !e.target.closest('[data-filter-drop]')) { closeAllDrops(); }
  });

  /* ── The price / deposit mini-forms ──────────────────────────────────── */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches || !form.closest('[data-filter-bar]')) { return; }

    var action = form.getAttribute('action') || window.location.href;
    var params = new URLSearchParams(new FormData(form));
    var href;
    try {
      var u = new URL(action, window.location.origin);
      params.forEach(function (v, k) {
        if (v === '') { u.searchParams.delete(k); } else { u.searchParams.set(k, v); }
      });
      href = u.toString();
    } catch (err) { return; }

    if (load(href, false)) {
      e.preventDefault();
      closeAllDrops();
      if (sheetIsOpen()) { setLevel1(); }
    }
  });

  /* ── Keyboard + history ──────────────────────────────────────────────── */
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') { return; }
    if (sheetIsOpen()) {
      if (document.body.classList.contains('tb-sheet-level-2')) { setLevel1(); } else { closeSheet(); }
    } else {
      closeAllDrops();
    }
  });

  window.addEventListener('popstate', function () {
    if (root) { window.location.reload(); }
  });
}());
