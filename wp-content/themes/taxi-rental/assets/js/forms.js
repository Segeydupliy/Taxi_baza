/* forms.js — universal client-side form validator for TaxiBaza.sk
 *
 * Usage:
 *   Add data-tb-validate to any <form>.
 *   Add required / data-err-required / data-err-email / data-min-length /
 *   data-err-minlength / data-match-id / data-err-match to each <input>.
 *   Place <span class="tb-field-error"></span> immediately after the input
 *   (or inside the same .tb-contact-form__field wrapper).
 *   Optionally place <div class="tb-error-banner" hidden>…</div> inside the
 *   form — it will be shown when any field fails.
 */
(function () {
  'use strict';

  /* ── helpers ────────────────────────────────────────────────────────────── */

  function findErrorEl(input) {
    /* Direct sibling first, then wrapper search */
    var next = input.nextElementSibling;
    if (next && next.classList.contains('tb-field-error')) { return next; }
    var wrap = input.closest('.tb-contact-form__field, .tb-auth-form__field, .tb-profile__field, .tb-settings__field');
    if (wrap) { return wrap.querySelector('.tb-field-error'); }
    return null;
  }

  function showFieldError(input, msg) {
    input.classList.add('is-invalid');
    input.setAttribute('aria-invalid', 'true');
    var el = findErrorEl(input);
    if (el) { el.textContent = msg; }
  }

  function clearFieldError(input) {
    input.classList.remove('is-invalid');
    input.removeAttribute('aria-invalid');
    var el = findErrorEl(input);
    if (el) { el.textContent = ''; }
  }

  function setBanner(form, show) {
    var banner = form.querySelector('.tb-error-banner');
    if (!banner) { return; }
    if (show) {
      banner.removeAttribute('hidden');
    } else {
      banner.setAttribute('hidden', '');
    }
  }

  /* ── per-field validation ───────────────────────────────────────────────── */

  function validateField(input) {
    var val = input.value.trim();
    var tag = input.tagName.toLowerCase();

    /* Required */
    if (input.required && val === '') {
      showFieldError(input, input.dataset.errRequired || 'Toto pole je povinné');
      return false;
    }

    /* E-mail format */
    if (input.type === 'email' && val !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
      showFieldError(input, input.dataset.errEmail || 'Zadajte platnú emailovú adresu');
      return false;
    }

    /* Min length */
    if (input.dataset.minLength && val.length < Number(input.dataset.minLength)) {
      showFieldError(input, input.dataset.errMinlength || 'Príliš krátke');
      return false;
    }

    /* Native minlength attribute */
    if (input.minLength > 0 && val.length > 0 && val.length < input.minLength) {
      showFieldError(input, input.dataset.errMinlength || ('Minimálne ' + input.minLength + ' znakov'));
      return false;
    }

    /* Date range (native novalidate skips the browser's own min/max check) */
    if (input.type === 'date' && val !== '' && input.min && val < input.min) {
      showFieldError(input, input.dataset.errMin || 'Zvoľte, prosím, dnešný alebo neskorší dátum');
      return false;
    }

    /* Password match */
    if (input.dataset.matchId) {
      var target = document.getElementById(input.dataset.matchId);
      if (target && val !== target.value.trim()) {
        showFieldError(input, input.dataset.errMatch || 'Heslá sa nezhodujú');
        return false;
      }
    }

    clearFieldError(input);
    return true;
  }

  /* ── attach to a form ───────────────────────────────────────────────────── */

  function attachValidator(form) {
    var fields = Array.prototype.slice.call(
      form.querySelectorAll('input[required], select[required], textarea[required], [data-validate]')
    );

    /* Real-time: clear error on input */
    fields.forEach(function (input) {
      input.addEventListener('input', function () { clearFieldError(input); setBanner(form, false); });
      input.addEventListener('change', function () { validateField(input); });

      /* Date pickers block past dates via [min], but a date can still be typed
         in by hand. `change` alone misses the case where the field is left
         half-typed, so flag it on blur too rather than waiting for submit. */
      if (input.type === 'date') {
        input.addEventListener('blur', function () {
          if (input.value !== '') { validateField(input); }
        });
      }
    });

    /* Pre-submit: validate all, then execute reCAPTCHA v3 if key is present */
    form.addEventListener('submit', function (e) {
      var valid = fields.reduce(function (ok, input) {
        /* reduce (not forEach) so every field is checked even after first fail */
        return validateField(input) && ok;
      }, true);

      if (!valid) {
        e.preventDefault();
        setBanner(form, true);
        var firstInvalid = form.querySelector('.is-invalid');
        if (firstInvalid) {
          firstInvalid.scrollIntoView({ block: 'center', behavior: 'smooth' });
          firstInvalid.focus({ preventScroll: true });
        }
        return;
      }

      /* reCAPTCHA v3 — only if key is configured and token not yet filled */
      var siteKey    = form.dataset.recaptchaKey || window.tbRecaptchaSiteKey || '';
      var tokenInput = form.querySelector('input[name="tb_recaptcha_token"]');
      if (siteKey && tokenInput && !tokenInput.value && window.grecaptcha) {
        e.preventDefault();
        window.grecaptcha.ready(function () {
          window.grecaptcha.execute(siteKey, { action: 'register' }).then(function (token) {
            tokenInput.value = token;
            form.submit();
          });
        });
      }
    });
  }

  /* ── init ───────────────────────────────────────────────────────────────── */

  Array.prototype.forEach.call(
    document.querySelectorAll('[data-tb-validate]'),
    attachValidator
  );

  /* Expose helpers for AJAX forms (contact-owner-modal.php JS) */
  window.tbForms = {
    showFieldError: showFieldError,
    clearFieldError: clearFieldError,
    setBanner: setBanner,
    attachValidator: attachValidator,
    validateField: validateField,
  };

  /* ── Password show/hide toggle + strength meter ──────────────────────────
     Used on the reset-password screen (login page) and the cabinet's
     "change password" settings form. Markup contract:
       .tb-pw-field > input + button[data-pw-toggle]
       [data-pw-strength] with data-label/data-level-weak/medium/strong,
         sitting in the same .tb-auth-form__field/.tb-profile__field wrapper
         as the input[data-pw-strength-input]. */

  Array.prototype.forEach.call(document.querySelectorAll('[data-pw-toggle]'), function (btn) {
    var field = btn.closest('.tb-pw-field');
    var input = field ? field.querySelector('input') : null;
    if (!input) { return; }

    btn.addEventListener('click', function () {
      var willShow = input.type === 'password';
      input.type = willShow ? 'text' : 'password';
      btn.setAttribute('aria-pressed', willShow ? 'true' : 'false');
      btn.setAttribute('aria-label', willShow ? btn.dataset.labelHide : btn.dataset.labelShow);
    });
  });

  function scorePassword(value) {
    if (!value) { return 0; }
    var variety = 0;
    if (/[a-z]/.test(value)) { variety++; }
    if (/[A-Z]/.test(value)) { variety++; }
    if (/[0-9]/.test(value)) { variety++; }
    if (/[^a-zA-Z0-9]/.test(value)) { variety++; }
    if (value.length < 8) { return 1; }
    if (variety >= 3) { return 3; }
    return 2;
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-pw-strength]'), function (meter) {
    var field = meter.closest('.tb-auth-form__field, .tb-profile__field');
    var input = field ? field.querySelector('[data-pw-strength-input]') : null;
    var label = meter.querySelector('.tb-pw-strength__label');
    if (!input || !label) { return; }

    var levelKeys = { 1: 'weak', 2: 'medium', 3: 'strong' };

    input.addEventListener('input', function () {
      var score = scorePassword(input.value);
      meter.classList.remove('is-weak', 'is-medium', 'is-strong');

      if (score === 0) {
        label.textContent = '';
        return;
      }

      var key = levelKeys[score];
      meter.classList.add('is-' + key);
      label.textContent = meter.getAttribute('data-label') + ': ' + meter.getAttribute('data-level-' + key);
    });
  });
}());
