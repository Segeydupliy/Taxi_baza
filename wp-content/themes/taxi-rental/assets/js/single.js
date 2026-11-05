/* single.js — gallery thumbnail switcher + fullscreen lightbox */
(function () {
  'use strict';

  var gallery = document.querySelector('[data-gallery]');
  if (!gallery) return;

  var mainImg = gallery.querySelector('.tb-gallery__main-img');
  var thumbs  = Array.prototype.slice.call(gallery.querySelectorAll('.tb-gallery__thumb'));
  if (!mainImg) return;

  /* Ordered sources from gallery thumbs, or fall back to the main image. */
  var sources = thumbs.length
    ? thumbs.map(function (t) { return { src: t.dataset.gallerySrc, alt: t.dataset.galleryAlt || '' }; })
    : [{ src: mainImg.src, alt: mainImg.alt }];
  var current = 0;
  var total   = sources.length;

  /* ── Inline gallery: clicking a thumb switches the main preview ── */
  thumbs.forEach(function (thumb, idx) {
    thumb.setAttribute('aria-pressed', idx === 0 ? 'true' : 'false');
    thumb.addEventListener('click', function () {
      mainImg.src = sources[idx].src;
      mainImg.alt = sources[idx].alt;
      thumbs.forEach(function (t, i) {
        t.classList.toggle('tb-gallery__thumb--active', i === idx);
        t.setAttribute('aria-pressed', i === idx ? 'true' : 'false');
      });
      current = idx;
    });
  });

  /* ── Fullscreen lightbox ── */
  var lightbox = document.getElementById('tb-lightbox');
  if (!lightbox) return;

  var lbImg       = lightbox.querySelector('.tb-lightbox__main-img');
  var lbCounter   = lightbox.querySelector('[data-lightbox-counter]');
  var stripThumbs = Array.prototype.slice.call(lightbox.querySelectorAll('.tb-lightbox__strip-thumb'));

  function lbShow(i) {
    current = (i + total) % total;
    if (lbImg)    { lbImg.src = sources[current].src; lbImg.alt = sources[current].alt; }
    if (lbCounter){ lbCounter.textContent = (current + 1) + ' / ' + total; }
    stripThumbs.forEach(function (t, idx) {
      t.classList.toggle('is-active', idx === current);
    });
    if (stripThumbs[current]) {
      stripThumbs[current].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
  }

  function lbOpen(i) {
    lbShow(i);
    lightbox.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function lbClose() {
    lightbox.hidden = true;
    document.body.style.overflow = '';
  }

  /* Open on main image click */
  var mainWrap = gallery.querySelector('.tb-gallery__main-wrap');
  if (mainWrap) {
    mainWrap.style.cursor = 'zoom-in';
    mainWrap.addEventListener('click', function () { lbOpen(current); });
  }

  /* Open on gallery thumb click (also updates inline preview) */
  thumbs.forEach(function (thumb, idx) {
    thumb.addEventListener('click', function () { lbOpen(idx); });
  });

  /* Desktop prev / next */
  var btnPrev = lightbox.querySelector('[data-lightbox-prev]');
  var btnNext = lightbox.querySelector('[data-lightbox-next]');
  if (btnPrev) btnPrev.addEventListener('click', function () { lbShow(current - 1); });
  if (btnNext) btnNext.addEventListener('click', function () { lbShow(current + 1); });
  if (total < 2) {
    if (btnPrev) btnPrev.hidden = true;
    if (btnNext) btnNext.hidden = true;
  }

  /* Strip thumbs inside lightbox */
  stripThumbs.forEach(function (thumb, idx) {
    thumb.addEventListener('click', function () { lbShow(idx); });
  });

  /* Strip scroll buttons */
  var stripInner = lightbox.querySelector('.tb-lightbox__strip-inner');
  var stripUp    = lightbox.querySelector('[data-lightbox-strip-up]');
  var stripDown  = lightbox.querySelector('[data-lightbox-strip-down]');
  if (stripInner) {
    if (stripUp)   stripUp.addEventListener('click', function () { stripInner.scrollBy({ top: -74, behavior: 'smooth' }); });
    if (stripDown) stripDown.addEventListener('click', function () { stripInner.scrollBy({ top: 74,  behavior: 'smooth' }); });
  }

  /* Close: dedicated buttons + backdrop click */
  Array.prototype.forEach.call(lightbox.querySelectorAll('[data-lightbox-close]'), function (btn) {
    btn.addEventListener('click', lbClose);
  });
  lightbox.addEventListener('click', function (e) {
    if (e.target === lightbox) lbClose();
  });

  /* Keyboard navigation */
  document.addEventListener('keydown', function (e) {
    if (lightbox.hidden) return;
    if (e.key === 'Escape')     lbClose();
    if (e.key === 'ArrowLeft')  lbShow(current - 1);
    if (e.key === 'ArrowRight') lbShow(current + 1);
  });
}());
