# CLAUDE.md — TaxiBaza.sk

Auto-loaded by Claude Code at the start of every session in this repo.
Read `/docs/PLAN_taxi_rental_SK.md` before writing any code — it is the source of truth.

---

## Project

Multilingual WordPress platform for renting cars for taxi work (Bolt/Uber) in Slovakia.
Owners/fleets self-register and post car listings; drivers browse the catalog as guests.
Custom theme + custom core plugin. **No heavy listing themes.**

Communicate with the developer in **Russian**. Code, comments, identifiers in **English**.
UI strings in Slovak (primary) + Ukrainian, all translatable via text-domain `taxi-rental`.

## Stack (fixed — do not substitute)

- WordPress 6.4+, PHP 8.1+, MySQL/MariaDB.
- Plugins: ACF Pro, WPML (+ String Translation, + Yoast SEO Multilingual), Yoast SEO,
  WP Mail SMTP, Wordfence, UpdraftPlus.
- **Do NOT use** WPUF, Ultimate Member, ProfilePress, CPT UI, PublishPress, Rank Math, Polylang.

## Architecture

Data in the plugin, presentation in the theme. Never register CPTs/taxonomies/roles in the theme.

```
wp-content/
├── plugins/taxi-rental-core/        # DATA + LOGIC
│   ├── taxi-rental-core.php         # bootstrap, headers, autoload, activation hooks
│   ├── includes/
│   │   ├── class-cpt.php            # CPT car_listing
│   │   ├── class-taxonomies.php     # city, platform, ride_category
│   │   ├── class-roles.php          # car_owner, fleet (+caps)
│   │   ├── class-statuses.php       # rejected, archived
│   │   ├── class-account.php        # register/login/recovery
│   │   ├── class-submission.php     # acf_form handler + moderation routing
│   │   ├── class-queries.php        # catalog filters (pre_get_posts / AJAX)
│   │   ├── class-notifications.php  # email events
│   │   └── class-capabilities.php   # "own listings only" guard
│   ├── acf-json/                    # ACF field groups (version-controlled)
│   └── languages/
└── themes/taxi-rental/              # PRESENTATION
    ├── style.css  functions.php  theme.json
    ├── assets/{css,js,img}/
    ├── template-parts/  parts/
    ├── front-page.php  archive-car_listing.php  single-car_listing.php
    ├── taxonomy-city.php  page-dashboard.php  page-add-listing.php  page-login.php
    └── 404.php
```

## Data model

- CPT `car_listing`: rewrite slug `avto`, supports title/thumbnail/author, show_in_rest, icon dashicons-car.
- Taxonomies: `city` (flat, admin-managed), `platform` (flat: Bolt, Uber),
  `ride_category` (**hierarchical** — platforms are parents, categories are children:
  Bolt→Bolt,Comfort,Pet,Green,Premium,Luxury,XL; Uber→UberX,Comfort,XL).
- Roles `car_owner` (1–2 cars) and `fleet` (3+). Caps on OWN `car_listing` only — **no** `edit_others_car_listings`.
- Statuses: `draft`, `pending`, `publish`, `rejected` (+meta `rejection_reason`), `archived` (last two via register_post_status).
- Listing/profile fields via ACF, stored as `acf-json/` (commit the JSON).
- Activation seeder (idempotent): 10 Slovak cities + platform terms + ride_category terms.

## SCOPE-LOCK — build MVP only (per ТЗ)

**IN:** Home, Catalog + filters, Single car, City, Cities, About, Contact (+ form states),
Privacy, Terms, 404; Auth for owners/fleets (Login, Register with account-type, Password
Recover, optional Confirm Email); Cabinet (Dashboard, My Listings, Profile, Profile Edit,
Settings, Add/Edit Car via acf_form); "Send Message" = **email to owner + show contacts**
(no stored conversation); moderation & all management in **native wp-admin**; blog on native
WP posts; WPML, email notifications, Yoast SEO, security, backups.

**OUT (phase 2 — do not build, ignore these Figma screens):** User Messages / internal chat,
custom front-end admin (Admin Dashboard/Advert/Users/Cities/Blog/Categories), Confirm Phone /
SMS, driver accounts, Blog Search/Sort, Brand/Model/Year filters, payments/featured/subscriptions.

**Catalog filters (exactly):** required — city, platform, category; desired — price, fuel,
transmission, deposit, min-term. `make`/`model`/`year` are listing FIELDS but NOT filters in MVP.

> If a request would exceed this scope, stop and flag it instead of building it.

## Design (Figma: xyjZy2loldTsxO9bYCqMFu)

Canvases: Desktop `66:308` (1920), Mobile `239:1569` (390). Adaptive per screen, mobile-first.
Tokens live in `themes/taxi-rental/theme.json` and `assets/css/tokens.css` (mirror `/docs/tokens.css`).

- Colors: accent `#02F797`, ink `#000`, base `#fff`, border `#E5E5E5`, overlays `#000000CC`/`#00000033`.
- Fonts: **Inter** (UI 400/600/700) + **Hanken Grotesk ExtraBold** (logo/headings). Preload both.
- Container 1368px (gutter 48px desktop / 16px mobile); pill buttons radius 100px; cards radius 16px; hairline 1px.
- Logo `TaxiBaza.sk` — the `.` is accent-colored.

The dev pulls each screen from Figma by nodeId (in the chat UI, not Code) and pastes reference
markup; build it 1:1 against the tokens. Screen→template map is in `/docs/PLAN_taxi_rental_SK.md` §9.

## Security (always)

- Nonces on every form + AJAX request; verify with `wp_verify_nonce( sanitize_text_field( wp_unslash( ... ) ) )`.
- Sanitize on input (`sanitize_text_field`, `wp_kses_post`, `esc_url_raw`), escape on output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
- `$wpdb->prepare` for all queries; never hardcode table names (use `$wpdb->prefix`).
- `current_user_can()` + "own listings only" guard before edit/delete.
- Safe media handling via WP media API. All strings i18n with text-domain `taxi-rental`.

## Conventions

- Follow WordPress Coding Standards. Run `composer run lint` (phpcs --standard=WordPress) before considering a file done.
- PHP 8.1 typed signatures, namespaced classes under `TaxiRental\Core\`.
- Use WordPress hooks; never edit core. No PHP short tags, no deprecated functions.
- Enqueue assets via `wp_enqueue_scripts` / `admin_enqueue_scripts`; version with filemtime.

## Build order (one phase per task — full files, then lint)

1. Phase 1–2: plugin bootstrap + CPT + taxonomies + roles + statuses + seeder.
2. Phase 3: ACF field groups (acf-json) — listing + user profile.
3. Phase 4: theme scaffold (functions.php, theme.json, tokens.css, fonts, header/footer, mobile nav).
4. Phase 5–7: auth → cabinet → add/edit listing (acf_form + moderation).
5. Phase 8–10: catalog + filters → single car → city pages.
6. Phase 11–14: WPML → notifications → Yoast SEO → security + backups.
7. Phase 15: QA + deploy.

Do not jump ahead or scaffold OUT-of-scope screens. After each phase, summarize what changed
and commit with a conventional message (e.g. `feat(core): register car_listing CPT and taxonomies`).

## Docs (source of truth, in /docs)

- `PLAN_taxi_rental_SK.md` — full plan, phases, screen registry, screen→template map.
- `theme.json`, `tokens.css` — design tokens.
- `TZ_Avto_Orenda.docx` — original spec.

## Environment (fill in locally — do not commit secrets)

- Local URL: `____`  |  WP version: `____`  |  PHP: `____`
- Use a `.env` / `wp-config-local.php` for DB + SMTP creds; keep out of git.
