<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! have_posts() ) {
	wp_redirect( get_post_type_archive_link( 'car_listing' ) );
	exit;
}

the_post();

$post_id   = get_the_ID();
$author_id = (int) get_post_field( 'post_author', $post_id );

// Owners can't send themselves a contact request — disable the CTA on own listings.
$is_own_listing = is_user_logged_in() && get_current_user_id() === $author_id;

$make         = get_field( 'make',                 $post_id );
$model        = get_field( 'model',                $post_id );
$year         = get_field( 'year',                 $post_id );
$engine_type  = get_field( 'engine_type',          $post_id );
$transmission = get_field( 'transmission',         $post_id );
$consumption  = get_field( 'consumption_100km',    $post_id );
$price_week   = get_field( 'price_week',           $post_id );
$deposit      = get_field( 'deposit',              $post_id );
$mileage      = get_field( 'mileage_limit_month',  $post_id );
$min_term     = get_field( 'min_term_days',        $post_id );
$insurance    = get_field( 'insurance',            $post_id );
$ins_details  = get_field( 'insurance_details',    $post_id );
$maintenance  = get_field( 'maintenance_included', $post_id );
$maint_details= get_field( 'maintenance_details',  $post_id );
$short_desc   = get_field( 'short_description',    $post_id );
$gallery      = get_field( 'gallery',              $post_id );
$phone        = (string) get_user_meta( $author_id, 'phone',    true );
$telegram     = (string) get_user_meta( $author_id, 'telegram', true );
$whatsapp     = (string) get_user_meta( $author_id, 'whatsapp', true );

$city_terms     = get_the_terms( $post_id, 'city' );
$platform_terms = get_the_terms( $post_id, 'platform' );
$city_term      = ( $city_terms && ! is_wp_error( $city_terms ) ) ? $city_terms[0] : null;

$fuel_labels = [
	'petrol'   => tb_opt( 'tb_cat_fuel_petrol',   __( 'Бензин',  'taxi-rental' ) ),
	'diesel'   => tb_opt( 'tb_cat_fuel_diesel',   __( 'Дизель',  'taxi-rental' ) ),
	'electric' => tb_opt( 'tb_cat_fuel_electric', __( 'Електро', 'taxi-rental' ) ),
	'hybrid'   => tb_opt( 'tb_cat_fuel_hybrid',   __( 'Гібрид',  'taxi-rental' ) ),
	'lpg'      => tb_opt( 'tb_cat_fuel_lpg',      __( 'LPG',     'taxi-rental' ) ),
];
$trans_labels = [
	'automatic' => tb_opt( 'tb_cat_trans_automatic', __( 'Автомат',  'taxi-rental' ) ),
	'manual'    => tb_opt( 'tb_cat_trans_manual',    __( 'Механіка', 'taxi-rental' ) ),
];
$bool_labels = [
	'yes'     => tb_opt( 'tb_sng_bool_yes',     __( 'Так',         'taxi-rental' ) ),
	'no'      => tb_opt( 'tb_sng_bool_no',      __( 'Ні',          'taxi-rental' ) ),
	'details' => tb_opt( 'tb_sng_bool_details', __( 'Є (уточнити)', 'taxi-rental' ) ),
];

$ins_val = $insurance ? ( $bool_labels[ $insurance ] ?? $insurance ) : '';
if ( 'details' === $insurance && $ins_details ) {
	$ins_val = $ins_details;
}
$maint_val = $maintenance ? ( $bool_labels[ $maintenance ] ?? $maintenance ) : '';
if ( 'details' === $maintenance && $maint_details ) {
	$maint_val = $maint_details;
}

// Build gallery images array: featured thumbnail first, then ACF gallery.
$gallery_images = [];
$thumb_id       = get_post_thumbnail_id( $post_id );
if ( $thumb_id ) {
	$thumb_src        = wp_get_attachment_image_src( $thumb_id, 'large' );
	$gallery_images[] = [
		'url' => $thumb_src ? $thumb_src[0] : '',
		'alt' => get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ),
	];
}
if ( is_array( $gallery ) ) {
	foreach ( $gallery as $img ) {
		if ( ! empty( $img['url'] ) ) {
			$gallery_images[] = [
				'url' => $img['url'],
				'alt' => $img['alt'] ?? '',
			];
		}
	}
}
// Fallback placeholder if no images.
if ( empty( $gallery_images ) ) {
	$gallery_images[] = [
		'url' => TAXI_THEME_URL . '/assets/img/car-placeholder.jpg',
		'alt' => get_the_title(),
	];
}

$similar_args = [
	'post_type'      => 'car_listing',
	'post_status'    => 'publish',
	'posts_per_page' => 4,
	'post__not_in'   => [ $post_id ],
];
if ( $city_term ) {
	$similar_args['tax_query'] = [
		[
			'taxonomy' => 'city',
			'field'    => 'slug',
			'terms'    => $city_term->slug,
		],
	];
}
$similar_query = new WP_Query( $similar_args );

// #89 — "Схожі оголошення" reported as missing. It was not hidden by CSS: the
// section is skipped entirely when the query is empty, and the query only ever
// looked at the SAME city. A listing that is the only one in its city therefore
// never showed the block. Fall back to the newest listings of any city.
if ( ! $similar_query->have_posts() && ! empty( $similar_args['tax_query'] ) ) {
	unset( $similar_args['tax_query'] );
	$similar_query = new WP_Query( $similar_args );
}

// Labels from ACF options.
$lbl_breadcrumb_home    = tb_opt( 'tb_sng_breadcrumb_home',    __( 'Головна', 'taxi-rental' ) );
$lbl_breadcrumb_catalog = tb_opt( 'tb_sng_breadcrumb_catalog', __( 'Каталог', 'taxi-rental' ) );
$lbl_specs              = tb_opt( 'tb_sng_specs_heading',      __( 'Характеристики', 'taxi-rental' ) );
$lbl_conditions         = tb_opt( 'tb_sng_conditions_heading', __( 'Умови оренди', 'taxi-rental' ) );
$lbl_spec_make          = tb_opt( 'tb_sng_spec_make',          __( 'Марка', 'taxi-rental' ) );
$lbl_spec_model         = tb_opt( 'tb_sng_spec_model',         __( 'Модель', 'taxi-rental' ) );
$lbl_spec_year          = tb_opt( 'tb_sng_spec_year',          __( 'Рік', 'taxi-rental' ) );
$lbl_spec_engine        = tb_opt( 'tb_sng_spec_engine',        __( 'Тип двигуна', 'taxi-rental' ) );
$lbl_spec_trans         = tb_opt( 'tb_sng_spec_trans',         __( 'КПП', 'taxi-rental' ) );
$lbl_spec_consumption   = tb_opt( 'tb_sng_spec_consumption',   __( 'Розхід на 100 км', 'taxi-rental' ) );
$lbl_cond_price         = tb_opt( 'tb_sng_cond_price',         __( 'Ціна / тиждень', 'taxi-rental' ) );
$lbl_cond_deposit       = tb_opt( 'tb_sng_cond_deposit',       __( 'Депозит', 'taxi-rental' ) );
$lbl_cond_mileage       = tb_opt( 'tb_sng_cond_mileage',       __( 'Ліміт пробігу', 'taxi-rental' ) );
$lbl_cond_min_term      = tb_opt( 'tb_sng_cond_min_term',      __( 'Мін. термін', 'taxi-rental' ) );
$lbl_cond_insurance     = tb_opt( 'tb_sng_cond_insurance',     __( 'Страховка', 'taxi-rental' ) );
$lbl_cond_maintenance   = tb_opt( 'tb_sng_cond_maintenance',   __( 'ТО включено', 'taxi-rental' ) );
$lbl_similar_badge      = tb_opt( 'tb_sng_similar_badge',      __( 'Схожі', 'taxi-rental' ) );
$lbl_similar_heading    = tb_opt( 'tb_sng_similar_heading',    __( 'Схожі оголошення', 'taxi-rental' ) );
$lbl_similar_link       = tb_opt( 'tb_sng_similar_link',       __( 'Весь каталог авто', 'taxi-rental' ) );

get_header();
?>

<main class="tb-single-car">
  <div class="tb-container">

    <nav class="tb-breadcrumb" aria-label="Breadcrumb">
      <ol>
        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $lbl_breadcrumb_home ); ?></a></li>
        <li><a href="<?php echo esc_url( get_post_type_archive_link( 'car_listing' ) ); ?>"><?php echo esc_html( $lbl_breadcrumb_catalog ); ?></a></li>
        <?php if ( $city_term ) : ?>
        <li><a href="<?php echo esc_url( get_term_link( $city_term ) ); ?>"><?php echo esc_html( $city_term->name ); ?></a></li>
        <?php endif; ?>
        <li aria-current="page"><?php the_title(); ?></li>
      </ol>
    </nav>

    <div class="tb-single-car__layout">

      <div class="tb-single-car__main">

        <div class="tb-gallery" data-gallery>
          <div class="tb-gallery__main-wrap">
            <img
              class="tb-gallery__main-img"
              src="<?php echo esc_url( $gallery_images[0]['url'] ); ?>"
              alt="<?php echo esc_attr( $gallery_images[0]['alt'] ?: get_the_title() ); ?>"
              width="760"
              height="473"
            />
          </div>
          <?php if ( count( $gallery_images ) > 1 ) : ?>
          <div class="tb-gallery__thumbs">
            <?php foreach ( $gallery_images as $i => $img ) : ?>
            <button
              class="tb-gallery__thumb<?php echo 0 === $i ? ' tb-gallery__thumb--active' : ''; ?>"
              type="button"
              data-gallery-src="<?php echo esc_attr( $img['url'] ); ?>"
              data-gallery-alt="<?php echo esc_attr( $img['alt'] ?: get_the_title() ); ?>"
              aria-label="<?php echo esc_attr( sprintf( __( 'Зображення %d', 'taxi-rental' ), $i + 1 ) ); ?>"
            >
              <img src="<?php echo esc_url( $img['url'] ); ?>" alt="" loading="lazy" width="180" height="135" />
            </button>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <div class="tb-single-car__header">
          <h1 class="tb-single-car__title"><?php the_title(); ?></h1>
          <?php if ( $city_term ) : ?>
          <p class="tb-single-car__city">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false"><path d="M8 1.5C5.65 1.5 3.75 3.4 3.75 5.75C3.75 9.25 8 14.5 8 14.5C8 14.5 12.25 9.25 12.25 5.75C12.25 3.4 10.35 1.5 8 1.5ZM8 7.5C6.9 7.5 6 6.6 6 5.5C6 4.4 6.9 3.5 8 3.5C9.1 3.5 10 4.4 10 5.5C10 6.6 9.1 7.5 8 7.5Z" fill="#555"/></svg>
            <?php echo esc_html( $city_term->name ); ?>
          </p>
          <?php endif; ?>
          <?php if ( $platform_terms && ! is_wp_error( $platform_terms ) ) : ?>
          <div class="tb-single-car__badges">
            <?php foreach ( $platform_terms as $pt ) :
              $badge_mod = str_starts_with( $pt->slug, 'bolt' ) ? 'tb-car-badge--bolt' : 'tb-car-badge--uber';
            ?>
            <span class="tb-car-badge <?php echo esc_attr( $badge_mod ); ?>"><?php echo esc_html( $pt->name ); ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <?php if ( $short_desc ) : ?>
        <div class="tb-single-car__desc"><?php echo wp_kses_post( $short_desc ); ?></div>
        <?php endif; ?>

        <?php
        $specs = array_filter( [
            $lbl_spec_make        => $make,
            $lbl_spec_model       => $model,
            $lbl_spec_year        => $year,
            $lbl_spec_engine      => $engine_type   ? ( $fuel_labels[ $engine_type ]   ?? $engine_type )   : '',
            $lbl_spec_trans       => $transmission  ? ( $trans_labels[ $transmission ] ?? $transmission )  : '',
            $lbl_spec_consumption => $consumption   ? $consumption . ' ' . __( 'l/100 km', 'taxi-rental' ) : '',
        ] );
        if ( $specs ) : ?>
        <div class="tb-single-car__section">
          <h2 class="tb-single-car__section-title"><?php echo esc_html( $lbl_specs ); ?></h2>
          <table class="tb-specs-table">
            <?php foreach ( $specs as $label => $value ) : ?>
            <tr>
              <td class="tb-specs-table__label"><?php echo esc_html( $label ); ?></td>
              <td class="tb-specs-table__value"><?php echo esc_html( $value ); ?></td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>
        <?php endif; ?>

        <?php
        $conditions = array_filter( [
            $lbl_cond_price       => $price_week ? $price_week . ' €' : '',
            $lbl_cond_deposit     => $deposit    ? $deposit    . ' €' : '',
            $lbl_cond_mileage     => $mileage    ? $mileage    . ' ' . __( 'km/mes.', 'taxi-rental' ) : '',
            $lbl_cond_min_term    => $min_term   ? $min_term . ' ' . tb_plural_opt( (int) $min_term, 'day', __( 'deň', 'taxi-rental' ), __( 'dni', 'taxi-rental' ), __( 'dní', 'taxi-rental' ) ) : '',
            $lbl_cond_insurance   => $ins_val,
            $lbl_cond_maintenance => $maint_val,
        ] );
        if ( $conditions ) : ?>
        <div class="tb-single-car__section">
          <h2 class="tb-single-car__section-title"><?php echo esc_html( $lbl_conditions ); ?></h2>
          <table class="tb-specs-table">
            <?php foreach ( $conditions as $label => $value ) : ?>
            <tr>
              <td class="tb-specs-table__label"><?php echo esc_html( $label ); ?></td>
              <td class="tb-specs-table__value"><?php echo esc_html( $value ); ?></td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>
        <?php endif; ?>

      </div>

      <?php
      get_template_part( 'template-parts/catalog/owner-box', null, [
          'post_id'        => $post_id,
          'author_id'      => $author_id,
          'price_week'     => $price_week,
          'deposit'        => $deposit,
          'phone'          => $phone,
          'telegram'       => $telegram,
          'whatsapp'       => $whatsapp,
          'is_own_listing' => $is_own_listing,
      ] );

      $cat_terms = get_the_terms( $post_id, 'ride_category' );
      $cat_name  = ( $cat_terms && ! is_wp_error( $cat_terms ) ) ? $cat_terms[0]->name : '';

      get_template_part( 'template-parts/catalog/contact-owner-modal', null, [
          'post_id'    => $post_id,
          'title'      => get_the_title( $post_id ),
          'price_week' => $price_week,
          'city'       => $city_term ? $city_term->name : '',
          'category'   => $cat_name,
      ] );
      ?>

    </div>

  </div>

  <?php if ( $similar_query->have_posts() ) : ?>
  <section class="tb-similar">
    <div class="tb-container">
      <p class="tb-similar__label"><?php echo esc_html( $lbl_similar_badge ); ?></p>
      <div class="tb-similar__head">
        <h2 class="tb-similar__title"><?php echo esc_html( $lbl_similar_heading ); ?></h2>
        <a href="<?php echo esc_url( get_post_type_archive_link( 'car_listing' ) ); ?>" class="tb-btn tb-btn--outline-dark"><?php echo esc_html( $lbl_similar_link ); ?></a>
      </div>
      <div class="tb-similar__track" id="tb-similar-track">
        <?php while ( $similar_query->have_posts() ) : $similar_query->the_post(); ?>
          <?php get_template_part( 'template-parts/catalog/card-car' ); ?>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="tb-cta-tg">
    <div class="tb-container">
      <div class="tb-cta-tg__inner">
        <div class="tb-cta-tg__content">
          <span class="tb-cta-tg__label"><?php echo esc_html( tb_opt( 'tb_cta_car_label', 'Telegram' ) ); ?></span>
          <h2 class="tb-cta-tg__title"><?php echo esc_html( tb_opt( 'tb_cta_car_title', __( 'Приєднуйтесь до нашого каналу', 'taxi-rental' ) ) ); ?></h2>
          <p class="tb-cta-tg__desc"><?php echo esc_html( tb_opt( 'tb_cta_car_sub', __( 'Нові оголошення, поради та новини ринку таксі-оренди', 'taxi-rental' ) ) ); ?></p>
        </div>
        <a href="<?php echo esc_url( tb_telegram_url() ); ?>" target="_blank" rel="noopener noreferrer" class="tb-btn tb-btn--dark tb-cta-tg__btn">
          <?php echo esc_html( tb_opt( 'tb_cta_car_button', __( 'Підписатись у Telegram', 'taxi-rental' ) ) ); ?>
        </a>
      </div>
    </div>
  </section>

</main>

<?php
/* ── Lightbox (Figma 364:31 desktop / 366:31 mobile) ── */
$lb_author     = get_userdata( $author_id );
$lb_name       = $lb_author ? $lb_author->display_name : '';
$lb_role_label = tb_opt( 'tb_cat_role_owner', __( 'Власник авто', 'taxi-rental' ) );
$lb_fleet      = function_exists( 'tbf_listing_owner' ) ? tbf_listing_owner( $post_id ) : null;
if ( null !== $lb_fleet ) {
	if ( $lb_fleet['fleet'] ) {
		$lb_role_label = tb_opt( 'tb_cat_role_fleet', __( 'Автопарк', 'taxi-rental' ) );
	}
	$lb_name = '' !== $lb_fleet['name'] ? $lb_fleet['name'] : $lb_name;
} elseif ( $lb_author && in_array( 'fleet', (array) $lb_author->roles, true ) ) {
	$lb_role_label = tb_opt( 'tb_cat_role_fleet', __( 'Автопарк', 'taxi-rental' ) );
}
$lb_initials = '';
if ( $lb_name ) {
	$lb_parts    = explode( ' ', trim( $lb_name ) );
	$lb_initials = mb_strtoupper( mb_substr( $lb_parts[0], 0, 1 ) );
	if ( isset( $lb_parts[1] ) ) {
		$lb_initials .= mb_strtoupper( mb_substr( $lb_parts[1], 0, 1 ) );
	}
}
$lb_bio           = ( $author_id && function_exists( 'get_field' ) ) ? (string) get_field( 'user_bio', 'user_' . $author_id ) : '';
$lb_label_unit    = tb_opt( 'tb_cat_price_unit',        __( '/ týždeň',                      'taxi-rental' ) );
$lb_label_deposit = tb_opt( 'tb_cat_deposit_label',     __( 'Depozit',                        'taxi-rental' ) );
$lb_label_contact = tb_opt( 'tb_cat_owner_contact_btn', __( 'Kontaktovať vlastníka',          'taxi-rental' ) );
$lb_label_tg_join = tb_opt( 'tb_cat_owner_tg_btn',      __( 'Pripojiť sa k Telegram kanálu', 'taxi-rental' ) );
$lb_tel_href      = $phone    ? 'tel:' . preg_replace( '/[^\d+]/', '', $phone )                              : '';
$lb_tg_handle     = $telegram ? ltrim( $telegram, '@' )                                                     : '';
$lb_wa_href       = $whatsapp ? 'https://wa.me/' . preg_replace( '/[^\d]/', '', $whatsapp )                 : '';
?>

<div class="tb-lightbox" id="tb-lightbox" role="dialog" aria-modal="true"
     aria-label="<?php echo esc_attr( get_the_title() ); ?>" hidden>

  <!-- ─── DESKTOP: black left panel ─── -->
  <div class="tb-lightbox__left">

    <div class="tb-lightbox__thumbstrip">
      <button class="tb-lightbox__strip-scroll" type="button" data-lightbox-strip-up
              aria-label="<?php esc_attr_e( 'Прокрутити вгору', 'taxi-rental' ); ?>">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
          <path d="M5 12.5L10 7.5L15 12.5" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>

      <div class="tb-lightbox__strip-inner" id="tb-lb-strip">
        <?php foreach ( $gallery_images as $i => $img ) : ?>
        <button class="tb-lightbox__strip-thumb<?php echo 0 === $i ? ' is-active' : ''; ?>"
                type="button"
                data-lightbox-index="<?php echo (int) $i; ?>"
                aria-label="<?php echo esc_attr( sprintf( __( 'Фото %d', 'taxi-rental' ), $i + 1 ) ); ?>">
          <img src="<?php echo esc_url( $img['url'] ); ?>"
               alt=""
               loading="<?php echo 0 === $i ? 'eager' : 'lazy'; ?>">
        </button>
        <?php endforeach; ?>
      </div>

      <button class="tb-lightbox__strip-scroll" type="button" data-lightbox-strip-down
              aria-label="<?php esc_attr_e( 'Прокрутити вниз', 'taxi-rental' ); ?>">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
          <path d="M5 7.5L10 12.5L15 7.5" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>

    <div class="tb-lightbox__photozone">
      <img class="tb-lightbox__main-img" src="" alt="">
      <div class="tb-lightbox__nav-row">
        <button class="tb-lightbox__btn-prev" type="button" data-lightbox-prev
                aria-label="<?php esc_attr_e( 'Попереднє', 'taxi-rental' ); ?>">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
            <path d="M12.5 15L7.5 10L12.5 5" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
        <span class="tb-lightbox__counter" data-lightbox-counter>1 / <?php echo count( $gallery_images ); ?></span>
        <button class="tb-lightbox__btn-next" type="button" data-lightbox-next
                aria-label="<?php esc_attr_e( 'Наступне', 'taxi-rental' ); ?>">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
            <path d="M7.5 5L12.5 10L7.5 15" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </div>
    </div>

  </div><!-- /.tb-lightbox__left -->

  <!-- ─── DESKTOP: white right sidebar ─── -->
  <div class="tb-lightbox__sidebar">

    <div class="tb-lightbox__sb-top">
      <h2 class="tb-lightbox__sb-title"><?php the_title(); ?></h2>
      <button class="tb-lightbox__close" type="button" data-lightbox-close
              aria-label="<?php esc_attr_e( 'Закрити', 'taxi-rental' ); ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
          <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

    <?php if ( $city_term || ( $platform_terms && ! is_wp_error( $platform_terms ) ) ) : ?>
    <div class="tb-lightbox__sb-meta">
      <?php if ( $city_term ) : ?>
      <span class="tb-lightbox__sb-city">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
          <path d="M10 2C7.06 2 4.75 4.25 4.75 7.19C4.75 11.56 10 18.12 10 18.12C10 18.12 15.25 11.56 15.25 7.19C15.25 4.25 12.94 2 10 2ZM10 9.38C8.62 9.38 7.5 8.25 7.5 6.88C7.5 5.5 8.62 4.38 10 4.38C11.38 4.38 12.5 5.5 12.5 6.88C12.5 8.25 11.38 9.38 10 9.38Z" fill="#555"/>
        </svg>
        <?php echo esc_html( $city_term->name ); ?>
      </span>
      <?php endif; ?>
      <?php if ( $platform_terms && ! is_wp_error( $platform_terms ) ) : ?>
      <div class="tb-lightbox__sb-badges">
        <?php foreach ( $platform_terms as $pt ) :
          $lb_mod = str_starts_with( $pt->slug, 'bolt' ) ? 'tb-car-badge--bolt' : 'tb-car-badge--uber';
        ?>
        <span class="tb-car-badge <?php echo esc_attr( $lb_mod ); ?>"><?php echo esc_html( $pt->name ); ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <hr class="tb-lightbox__divider">

    <div class="tb-lightbox__sb-price">
      <div class="tb-lightbox__price-row">
        <span class="tb-lightbox__price-val"><?php echo $price_week ? esc_html( $price_week ) . '€' : '—'; ?></span>
        <span class="tb-lightbox__price-unit"><?php echo esc_html( $lb_label_unit ); ?></span>
      </div>
      <?php if ( $deposit ) : ?>
      <span class="tb-lightbox__deposit"><?php echo esc_html( $lb_label_deposit ); ?> <?php echo esc_html( $deposit ); ?> €</span>
      <?php endif; ?>
    </div>

    <hr class="tb-lightbox__divider">

    <div class="tb-lightbox__sb-owner">
      <div class="tb-lightbox__owner-row">
        <?php $lb_logo = tb_fleet_logo_url( $post_id ); ?>
        <div class="tb-lightbox__owner-avatar<?php echo $lb_logo ? ' tb-owner-box__avatar--logo' : ''; ?>" aria-hidden="true"><?php
          if ( $lb_logo ) {
            echo '<img src="' . esc_url( $lb_logo ) . '" alt="" width="40" height="40" loading="lazy">';
          } else {
            echo esc_html( $lb_initials ?: '?' );
          }
        ?></div>
        <div>
          <span class="tb-lightbox__owner-name"><?php echo esc_html( $lb_name ); ?></span>
          <span class="tb-lightbox__owner-role"><?php echo esc_html( $lb_role_label ); ?></span>
        </div>
      </div>
      <?php if ( $lb_bio ) : ?>
      <p class="tb-lightbox__owner-bio"><?php echo esc_html( $lb_bio ); ?></p>
      <?php endif; ?>
    </div>

    <div class="tb-lightbox__sb-cta">
      <button
        class="tb-btn tb-btn--primary"
        type="button"
        <?php if ( $is_own_listing ) : ?>
          disabled aria-disabled="true" title="<?php echo esc_attr( tb_opt( 'tb_cat_owner_own_listing', __( 'Toto je váš vlastný inzerát', 'taxi-rental' ) ) ); ?>"
        <?php else : ?>
          data-modal-open="tb-owner-modal"
        <?php endif; ?>
      >
        <?php echo esc_html( $lb_label_contact ); ?>
      </button>
    </div>

    <?php if ( $phone || $lb_tg_handle || $whatsapp ) : ?>
    <hr class="tb-lightbox__divider">
    <ul class="tb-lightbox__sb-contacts">
      <?php if ( $phone ) : ?>
      <li>
        <svg width="20" height="20" viewBox="0 0 14 14" fill="none" aria-hidden="true" focusable="false">
          <path d="M1.00488 0.5H3.83887C3.96241 0.500078 4.05236 0.534805 4.13184 0.601562C4.22361 0.678718 4.28123 0.770843 4.31152 0.891602L4.80273 3.0625C4.81861 3.17181 4.81168 3.26943 4.78516 3.36035C4.75757 3.45471 4.71839 3.51792 4.67285 3.56348L4.6709 3.56445L2.64941 5.60645L2.3916 5.86719L2.5625 6.19141C2.85244 6.74229 3.18629 7.27091 3.5625 7.77734C3.9383 8.28321 4.35692 8.7592 4.81738 9.20605V9.20703C5.25115 9.64065 5.71402 10.0419 6.20508 10.4102C6.69873 10.7804 7.22146 11.1104 7.77246 11.4004L8.09277 11.5693L8.35352 11.3174L10.416 9.31738L10.4209 9.31152C10.4665 9.26597 10.5296 9.2268 10.624 9.19922C10.7136 9.1731 10.8097 9.16475 10.917 9.17969L13.0996 9.64844C13.2447 9.69227 13.3392 9.75989 13.4062 9.84375C13.4696 9.9229 13.5048 10.0156 13.5049 10.1455V12.9795C13.5048 13.1343 13.4546 13.2462 13.3389 13.3496V13.3506C13.2186 13.458 13.0985 13.4958 12.9482 13.4814L12.9385 13.4805L12.334 13.4219C10.9345 13.2557 9.60091 12.8771 8.33105 12.2861C6.87797 11.6098 5.5747 10.6958 4.4209 9.54199C3.26759 8.38863 2.36131 7.09008 1.69922 5.64551C1.03651 4.19951 0.637137 2.6659 0.50293 1.04199H0.503906L0.50293 1.03613L0.501953 0.929688C0.511399 0.829286 0.552346 0.745179 0.628906 0.662109C0.730725 0.551591 0.84441 0.500049 1.00488 0.5ZM1.05078 1.56543C1.12266 2.11167 1.2235 2.64145 1.35352 3.1543C1.48408 3.66927 1.65115 4.17962 1.85352 4.68555L2.13477 5.38965L2.6709 4.85352L4.12988 3.39551L4.32617 3.19922L4.2627 2.92871L3.90527 1.38672L3.81543 1H0.976562L1.05078 1.56543ZM13.0049 10.1367L12.6094 10.0527L11.0469 9.71973L10.7812 9.66309L10.5889 9.85449L9.13086 11.3135L8.59766 11.8467L9.2959 12.1299C9.80772 12.3375 10.3308 12.5026 10.8633 12.625C11.3843 12.7448 11.9095 12.8406 12.4385 12.9121L13.0049 12.9893V10.1367Z" fill="currentColor" stroke="currentColor" stroke-width="0.3"/>
        </svg>
        <a href="<?php echo esc_url( $lb_tel_href ); ?>"><?php echo esc_html( $phone ); ?></a>
      </li>
      <?php endif; ?>
      <?php if ( $lb_tg_handle ) : ?>
      <li>
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
          <path d="M10 1.03125C14.8164 1.03125 18.7188 4.93359 18.7188 9.75C18.7188 14.5664 14.8164 18.4688 10 18.4688C5.18359 18.4688 1.28125 14.5664 1.28125 9.75C1.28125 4.93359 5.18359 1.03125 10 1.03125ZM14.043 6.97266C14.043 6.83203 14.043 6.72656 14.0078 6.62109C14.0078 6.51562 13.9727 6.44531 13.9023 6.375C13.7969 6.30469 13.6211 6.26953 13.5508 6.26953C13.2344 6.26953 12.7422 6.44531 10.3164 7.46484C9.47266 7.81641 7.78516 8.55469 5.21875 9.64453C4.83203 9.82031 4.58594 9.99609 4.58594 10.1367C4.55078 10.4531 4.97266 10.5234 5.53516 10.6992C5.95703 10.8398 6.55469 11.0156 6.87109 11.0156C7.15234 11.0508 7.46875 10.9102 7.82031 10.6992C10.2109 9.08203 11.4414 8.27344 11.5117 8.23828C11.5469 8.23828 11.6172 8.23828 11.6875 8.27344C11.7227 8.30859 11.7227 8.41406 11.7227 8.41406C11.6875 8.625 9.4375 10.6289 9.33203 10.7695C8.83984 11.2969 8.27734 11.5781 9.12109 12.1758C9.89453 12.668 10.3164 12.9492 11.0898 13.4766C11.582 13.793 11.9688 14.1797 12.4609 14.1094C12.707 14.1094 12.918 13.8984 13.0586 13.2305C13.3398 11.6836 13.9023 8.34375 14.043 6.97266Z" fill="currentColor"/>
        </svg>
        <a href="https://t.me/<?php echo esc_attr( $lb_tg_handle ); ?>" target="_blank" rel="noopener noreferrer">@<?php echo esc_html( $lb_tg_handle ); ?></a>
      </li>
      <?php endif; ?>
      <?php if ( $lb_wa_href ) : ?>
      <li>
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
          <path d="M16.1328 3.78906C17.7734 5.42969 18.75 7.61719 18.75 9.92188C18.75 14.7266 14.7656 18.5938 10 18.5938C8.55469 18.5938 7.10938 18.2422 5.85938 17.5391L1.25 18.75L2.5 14.2578C1.71875 12.9297 1.32812 11.4453 1.32812 9.92188C1.32812 5.15625 5.23438 1.25 10 1.25C12.3047 1.25 14.4922 2.14844 16.1328 3.78906ZM10 17.1484C13.9844 17.1484 17.3047 13.9062 17.3047 9.92188C17.3047 8.00781 16.4453 6.21094 15.0781 4.84375C13.75 3.47656 11.9141 2.73438 10 2.73438C6.01562 2.73438 2.77344 5.9375 2.77344 9.92188C2.77344 11.2891 3.16406 12.6172 3.90625 13.75L4.0625 14.0234L3.32031 16.6797L6.05469 15.9766L6.32812 16.1328C7.42188 16.7969 8.71094 17.1484 10 17.1484ZM13.9453 11.7578C14.1797 11.8359 14.2969 11.9141 14.375 11.9922C14.4141 12.0703 14.4141 12.5391 14.2188 13.0469C14.0625 13.5547 13.2031 13.9844 12.7734 14.0625C12.0703 14.1797 11.5625 14.1016 10.1562 13.5156C8.00781 12.5781 6.5625 10.3906 6.48438 10.2344C6.36719 10.1172 5.58594 9.0625 5.58594 8.00781C5.58594 6.95312 6.13281 6.40625 6.32812 6.21094C6.5625 5.97656 6.79688 5.9375 6.91406 5.9375C7.07031 5.9375 7.22656 5.9375 7.34375 5.9375C7.46094 5.9375 7.65625 5.89844 7.8125 6.28906C8.00781 6.71875 8.4375 7.8125 8.47656 7.92969C8.55469 8.00781 8.59375 8.16406 8.51562 8.28125C8.08594 9.10156 7.65625 9.10156 7.89062 9.49219C8.71094 10.8984 9.57031 11.4062 10.8203 12.0703C11.0547 12.1484 11.1719 12.1484 11.2891 11.9922C11.4062 11.8359 11.8359 11.3672 11.9922 11.1328C12.1094 10.9375 12.2656 10.9766 12.4609 11.0547C12.6562 11.0938 13.75 11.6406 13.9453 11.7578Z" fill="currentColor"/>
        </svg>
        <a href="<?php echo esc_url( $lb_wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $whatsapp ); ?></a>
      </li>
      <?php endif; ?>
    </ul>
    <?php endif; ?>

    <div class="tb-lightbox__sb-footer">
      <a href="<?php echo esc_url( tb_telegram_url() ); ?>" target="_blank" rel="noopener noreferrer"
         class="tb-lightbox__tg-join">
        <?php echo esc_html( $lb_label_tg_join ); ?>
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false">
          <path d="M6 3L11 8L6 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>
    </div>

  </div><!-- /.tb-lightbox__sidebar -->

  <!-- ─── MOBILE: sticky title bar ─── -->
  <div class="tb-lightbox__mobile-bar">
    <div class="tb-lightbox__mobile-info">
      <strong class="tb-lightbox__mobile-title"><?php the_title(); ?></strong>
      <?php if ( $price_week ) : ?>
      <div class="tb-lightbox__mobile-price">
        <strong><?php echo esc_html( $price_week ); ?>€</strong>
        <em><?php echo esc_html( $lb_label_unit ); ?></em>
      </div>
      <?php endif; ?>
    </div>
    <button class="tb-lightbox__close tb-lightbox__close--mobile" type="button" data-lightbox-close
            aria-label="<?php esc_attr_e( 'Закрити', 'taxi-rental' ); ?>">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </button>
  </div>

  <!-- ─── MOBILE: scrollable vertical photo stack ─── -->
  <div class="tb-lightbox__mobile-photos">
    <?php foreach ( $gallery_images as $i => $img ) : ?>
    <img src="<?php echo esc_url( $img['url'] ); ?>"
         alt="<?php echo esc_attr( $img['alt'] ?: get_the_title() ); ?>"
         loading="<?php echo 0 === $i ? 'eager' : 'lazy'; ?>">
    <?php if ( 0 === $i && count( $gallery_images ) > 1 ) : ?>
    <div class="tb-lightbox__mobile-counter">1 / <?php echo count( $gallery_images ); ?></div>
    <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <!-- ─── MOBILE: bottom action bar ─── -->
  <div class="tb-lightbox__mobile-bottom">
    <button class="tb-btn tb-btn--primary tb-lightbox__mobile-cta" type="button"
            data-modal-open="tb-owner-modal">
      <?php echo esc_html( $lb_label_contact ); ?>
    </button>
    <?php if ( $lb_tg_handle ) : ?>
    <a href="https://t.me/<?php echo esc_attr( $lb_tg_handle ); ?>"
       class="tb-lightbox__mobile-icon-btn"
       target="_blank" rel="noopener noreferrer"
       aria-label="Telegram">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
        <path d="M10 1.03125C14.8164 1.03125 18.7188 4.93359 18.7188 9.75C18.7188 14.5664 14.8164 18.4688 10 18.4688C5.18359 18.4688 1.28125 14.5664 1.28125 9.75C1.28125 4.93359 5.18359 1.03125 10 1.03125ZM14.043 6.97266C14.043 6.83203 14.043 6.72656 14.0078 6.62109C14.0078 6.51562 13.9727 6.44531 13.9023 6.375C13.7969 6.30469 13.6211 6.26953 13.5508 6.26953C13.2344 6.26953 12.7422 6.44531 10.3164 7.46484C9.47266 7.81641 7.78516 8.55469 5.21875 9.64453C4.83203 9.82031 4.58594 9.99609 4.58594 10.1367C4.55078 10.4531 4.97266 10.5234 5.53516 10.6992C5.95703 10.8398 6.55469 11.0156 6.87109 11.0156C7.15234 11.0508 7.46875 10.9102 7.82031 10.6992C10.2109 9.08203 11.4414 8.27344 11.5117 8.23828C11.5469 8.23828 11.6172 8.23828 11.6875 8.27344C11.7227 8.30859 11.7227 8.41406 11.7227 8.41406C11.6875 8.625 9.4375 10.6289 9.33203 10.7695C8.83984 11.2969 8.27734 11.5781 9.12109 12.1758C9.89453 12.668 10.3164 12.9492 11.0898 13.4766C11.582 13.793 11.9688 14.1797 12.4609 14.1094C12.707 14.1094 12.918 13.8984 13.0586 13.2305C13.3398 11.6836 13.9023 8.34375 14.043 6.97266Z" fill="currentColor"/>
      </svg>
    </a>
    <?php endif; ?>
    <?php if ( $lb_wa_href ) : ?>
    <a href="<?php echo esc_url( $lb_wa_href ); ?>"
       class="tb-lightbox__mobile-icon-btn"
       target="_blank" rel="noopener noreferrer"
       aria-label="WhatsApp">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
        <path d="M16.1328 3.78906C17.7734 5.42969 18.75 7.61719 18.75 9.92188C18.75 14.7266 14.7656 18.5938 10 18.5938C8.55469 18.5938 7.10938 18.2422 5.85938 17.5391L1.25 18.75L2.5 14.2578C1.71875 12.9297 1.32812 11.4453 1.32812 9.92188C1.32812 5.15625 5.23438 1.25 10 1.25C12.3047 1.25 14.4922 2.14844 16.1328 3.78906ZM10 17.1484C13.9844 17.1484 17.3047 13.9062 17.3047 9.92188C17.3047 8.00781 16.4453 6.21094 15.0781 4.84375C13.75 3.47656 11.9141 2.73438 10 2.73438C6.01562 2.73438 2.77344 5.9375 2.77344 9.92188C2.77344 11.2891 3.16406 12.6172 3.90625 13.75L4.0625 14.0234L3.32031 16.6797L6.05469 15.9766L6.32812 16.1328C7.42188 16.7969 8.71094 17.1484 10 17.1484ZM13.9453 11.7578C14.1797 11.8359 14.2969 11.9141 14.375 11.9922C14.4141 12.0703 14.4141 12.5391 14.2188 13.0469C14.0625 13.5547 13.2031 13.9844 12.7734 14.0625C12.0703 14.1797 11.5625 14.1016 10.1562 13.5156C8.00781 12.5781 6.5625 10.3906 6.48438 10.2344C6.36719 10.1172 5.58594 9.0625 5.58594 8.00781C5.58594 6.95312 6.13281 6.40625 6.32812 6.21094C6.5625 5.97656 6.79688 5.9375 6.91406 5.9375C7.07031 5.9375 7.22656 5.9375 7.34375 5.9375C7.46094 5.9375 7.65625 5.89844 7.8125 6.28906C8.00781 6.71875 8.4375 7.8125 8.47656 7.92969C8.55469 8.00781 8.59375 8.16406 8.51562 8.28125C8.08594 9.10156 7.65625 9.10156 7.89062 9.49219C8.71094 10.8984 9.57031 11.4062 10.8203 12.0703C11.0547 12.1484 11.1719 12.1484 11.2891 11.9922C11.4062 11.8359 11.8359 11.3672 11.9922 11.1328C12.1094 10.9375 12.2656 10.9766 12.4609 11.0547C12.6562 11.0938 13.75 11.6406 13.9453 11.7578Z" fill="currentColor"/>
      </svg>
    </a>
    <?php endif; ?>
  </div>

</div><!-- /.tb-lightbox -->

<?php get_footer(); ?>
