<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id    = (int) ( $args['post_id']    ?? 0 );
$author_id  = (int) ( $args['author_id']  ?? 0 );
$price_week = $args['price_week'] ?? '';
$deposit    = $args['deposit']    ?? '';
$phone      = sanitize_text_field( $args['phone']    ?? '' );
$telegram   = sanitize_text_field( $args['telegram'] ?? '' );
$whatsapp   = sanitize_text_field( $args['whatsapp'] ?? '' );
$is_own_listing = (bool) ( $args['is_own_listing'] ?? false );

$author = get_userdata( $author_id );
$name   = $author ? $author->display_name : '';

$role_label = tb_opt( 'tb_cat_role_owner', __( 'Власник авто', 'taxi-rental' ) );
// Fleet accounts choose per listing whether it is shown as the fleet's (plugin taxibaza-fleets).
$fleet_owner = function_exists( 'tbf_listing_owner' ) ? tbf_listing_owner( $post_id ) : null;
if ( null !== $fleet_owner ) {
	if ( $fleet_owner['fleet'] ) {
		$role_label = tb_opt( 'tb_cat_role_fleet', __( 'Автопарк', 'taxi-rental' ) );
	}
	$name = '' !== $fleet_owner['name'] ? $fleet_owner['name'] : $name;
} elseif ( $author && in_array( 'fleet', (array) $author->roles, true ) ) {
	$role_label = tb_opt( 'tb_cat_role_fleet', __( 'Автопарк', 'taxi-rental' ) );
}

$initials = '';
if ( $name ) {
	$parts    = explode( ' ', trim( $name ) );
	$initials = mb_strtoupper( mb_substr( $parts[0], 0, 1 ) );
	if ( isset( $parts[1] ) ) {
		$initials .= mb_strtoupper( mb_substr( $parts[1], 0, 1 ) );
	}
}

$bio = '';
if ( $author_id && function_exists( 'get_field' ) ) {
	$bio = get_field( 'user_bio', 'user_' . $author_id );
}

$label_unit    = tb_opt( 'tb_cat_price_unit', __( '/ тиждень', 'taxi-rental' ) );
$label_deposit = tb_opt( 'tb_cat_deposit_label', __( 'Депозит', 'taxi-rental' ) );
$label_contact = tb_opt( 'tb_cat_owner_contact_btn', __( 'Зв\'язатись із власником', 'taxi-rental' ) );
$label_own_listing = tb_opt( 'tb_cat_owner_own_listing', __( 'Toto je váš vlastný inzerát', 'taxi-rental' ) );
$label_tg_join = tb_opt( 'tb_cat_owner_tg_btn', __( 'Приєднатись до Telegram каналу', 'taxi-rental' ) );
?>
<aside class="tb-owner-box">

  <div class="tb-owner-box__price-row">
    <div class="tb-owner-box__price-wrap">
      <span class="tb-owner-box__price"><?php echo $price_week ? esc_html( $price_week ) . ' €' : '—'; ?></span>
      <span class="tb-owner-box__price-unit"><?php echo esc_html( $label_unit ); ?></span>
    </div>
    <?php if ( $deposit ) : ?>
    <span class="tb-owner-box__deposit"><?php echo esc_html( $label_deposit ); ?> <?php echo esc_html( $deposit ); ?> €</span>
    <?php endif; ?>
  </div>

  <hr class="tb-owner-box__divider">

  <div class="tb-owner-box__owner">
    <?php $owner_logo = tb_fleet_logo_url( $post_id ); ?>
    <div class="tb-owner-box__avatar<?php echo $owner_logo ? ' tb-owner-box__avatar--logo' : ''; ?>" aria-hidden="true"><?php
      if ( $owner_logo ) {
        echo '<img src="' . esc_url( $owner_logo ) . '" alt="" width="40" height="40" loading="lazy">';
      } else {
        echo esc_html( $initials ?: '?' );
      }
    ?></div>
    <div>
      <span class="tb-owner-box__name"><?php echo esc_html( $name ); ?></span>
      <span class="tb-owner-box__role"><?php echo esc_html( $role_label ); ?></span>
    </div>
  </div>

  <?php if ( $bio ) : ?>
  <p class="tb-owner-box__bio"><?php echo esc_html( $bio ); ?></p>
  <?php endif; ?>

  <button
    type="button"
    class="tb-owner-box__cta"
    <?php if ( $is_own_listing ) : ?>
      disabled aria-disabled="true" title="<?php echo esc_attr( $label_own_listing ); ?>"
    <?php else : ?>
      data-modal-open="tb-owner-modal"
    <?php endif; ?>
  ><?php echo esc_html( $label_contact ); ?></button>

  <?php if ( $phone || $telegram || $whatsapp ) : ?>
  <ul class="tb-owner-box__contacts">
    <?php if ( $phone ) :
      $tel_href = 'tel:' . preg_replace( '/[^\d+]/', '', $phone );
    ?>
    <li>
      <svg width="20" height="20" viewBox="0 0 14 14" fill="none" aria-hidden="true" focusable="false">
        <path d="M1.00488 0.5H3.83887C3.96241 0.500078 4.05236 0.534805 4.13184 0.601562C4.22361 0.678718 4.28123 0.770843 4.31152 0.891602L4.80273 3.0625C4.81861 3.17181 4.81168 3.26943 4.78516 3.36035C4.75757 3.45471 4.71839 3.51792 4.67285 3.56348L4.6709 3.56445L2.64941 5.60645L2.3916 5.86719L2.5625 6.19141C2.85244 6.74229 3.18629 7.27091 3.5625 7.77734C3.9383 8.28321 4.35692 8.7592 4.81738 9.20605V9.20703C5.25115 9.64065 5.71402 10.0419 6.20508 10.4102C6.69873 10.7804 7.22146 11.1104 7.77246 11.4004L8.09277 11.5693L8.35352 11.3174L10.416 9.31738L10.4209 9.31152C10.4665 9.26597 10.5296 9.2268 10.624 9.19922C10.7136 9.1731 10.8097 9.16475 10.917 9.17969L13.0996 9.64844C13.2447 9.69227 13.3392 9.75989 13.4062 9.84375C13.4696 9.9229 13.5048 10.0156 13.5049 10.1455V12.9795C13.5048 13.1343 13.4546 13.2462 13.3389 13.3496V13.3506C13.2186 13.458 13.0985 13.4958 12.9482 13.4814L12.9385 13.4805L12.334 13.4219C10.9345 13.2557 9.60091 12.8771 8.33105 12.2861C6.87797 11.6098 5.5747 10.6958 4.4209 9.54199C3.26759 8.38863 2.36131 7.09008 1.69922 5.64551C1.03651 4.19951 0.637137 2.6659 0.50293 1.04199H0.503906L0.50293 1.03613L0.501953 0.929688C0.511399 0.829286 0.552346 0.745179 0.628906 0.662109C0.730725 0.551591 0.84441 0.500049 1.00488 0.5ZM1.05078 1.56543C1.12266 2.11167 1.2235 2.64145 1.35352 3.1543C1.48408 3.66927 1.65115 4.17962 1.85352 4.68555L2.13477 5.38965L2.6709 4.85352L4.12988 3.39551L4.32617 3.19922L4.2627 2.92871L3.90527 1.38672L3.81543 1H0.976562L1.05078 1.56543ZM13.0049 10.1367L12.6094 10.0527L11.0469 9.71973L10.7812 9.66309L10.5889 9.85449L9.13086 11.3135L8.59766 11.8467L9.2959 12.1299C9.80772 12.3375 10.3308 12.5026 10.8633 12.625C11.3843 12.7448 11.9095 12.8406 12.4385 12.9121L13.0049 12.9893V10.1367Z" fill="currentColor" stroke="currentColor" stroke-width="0.3"/>
      </svg>
      <a href="<?php echo esc_url( $tel_href ); ?>"><?php echo esc_html( $phone ); ?></a>
    </li>
    <?php endif; ?>
    <?php if ( $telegram ) :
      $tg_handle = ltrim( $telegram, '@' );
    ?>
    <li>
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
        <path d="M10 1.03125C14.8164 1.03125 18.7188 4.93359 18.7188 9.75C18.7188 14.5664 14.8164 18.4688 10 18.4688C5.18359 18.4688 1.28125 14.5664 1.28125 9.75C1.28125 4.93359 5.18359 1.03125 10 1.03125ZM14.043 6.97266C14.043 6.83203 14.043 6.72656 14.0078 6.62109C14.0078 6.51562 13.9727 6.44531 13.9023 6.375C13.7969 6.30469 13.6211 6.26953 13.5508 6.26953C13.2344 6.26953 12.7422 6.44531 10.3164 7.46484C9.47266 7.81641 7.78516 8.55469 5.21875 9.64453C4.83203 9.82031 4.58594 9.99609 4.58594 10.1367C4.55078 10.4531 4.97266 10.5234 5.53516 10.6992C5.95703 10.8398 6.55469 11.0156 6.87109 11.0156C7.15234 11.0508 7.46875 10.9102 7.82031 10.6992C10.2109 9.08203 11.4414 8.27344 11.5117 8.23828C11.5469 8.23828 11.6172 8.23828 11.6875 8.27344C11.7227 8.30859 11.7227 8.41406 11.7227 8.41406C11.6875 8.625 9.4375 10.6289 9.33203 10.7695C8.83984 11.2969 8.27734 11.5781 9.12109 12.1758C9.89453 12.668 10.3164 12.9492 11.0898 13.4766C11.582 13.793 11.9688 14.1797 12.4609 14.1094C12.707 14.1094 12.918 13.8984 13.0586 13.2305C13.3398 11.6836 13.9023 8.34375 14.043 6.97266Z" fill="currentColor"/>
      </svg>
      <a href="https://t.me/<?php echo esc_attr( $tg_handle ); ?>" target="_blank" rel="noopener noreferrer">@<?php echo esc_html( $tg_handle ); ?></a>
    </li>
    <?php endif; ?>
    <?php if ( $whatsapp ) :
      $wa_href = 'https://wa.me/' . preg_replace( '/[^\d]/', '', $whatsapp );
    ?>
    <li>
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
        <path d="M16.1328 3.78906C17.7734 5.42969 18.75 7.61719 18.75 9.92188C18.75 14.7266 14.7656 18.5938 10 18.5938C8.55469 18.5938 7.10938 18.2422 5.85938 17.5391L1.25 18.75L2.5 14.2578C1.71875 12.9297 1.32812 11.4453 1.32812 9.92188C1.32812 5.15625 5.23438 1.25 10 1.25C12.3047 1.25 14.4922 2.14844 16.1328 3.78906ZM10 17.1484C13.9844 17.1484 17.3047 13.9062 17.3047 9.92188C17.3047 8.00781 16.4453 6.21094 15.0781 4.84375C13.75 3.47656 11.9141 2.73438 10 2.73438C6.01562 2.73438 2.77344 5.9375 2.77344 9.92188C2.77344 11.2891 3.16406 12.6172 3.90625 13.75L4.0625 14.0234L3.32031 16.6797L6.05469 15.9766L6.32812 16.1328C7.42188 16.7969 8.71094 17.1484 10 17.1484ZM13.9453 11.7578C14.1797 11.8359 14.2969 11.9141 14.375 11.9922C14.4141 12.0703 14.4141 12.5391 14.2188 13.0469C14.0625 13.5547 13.2031 13.9844 12.7734 14.0625C12.0703 14.1797 11.5625 14.1016 10.1562 13.5156C8.00781 12.5781 6.5625 10.3906 6.48438 10.2344C6.36719 10.1172 5.58594 9.0625 5.58594 8.00781C5.58594 6.95312 6.13281 6.40625 6.32812 6.21094C6.5625 5.97656 6.79688 5.9375 6.91406 5.9375C7.07031 5.9375 7.22656 5.9375 7.34375 5.9375C7.46094 5.9375 7.65625 5.89844 7.8125 6.28906C8.00781 6.71875 8.4375 7.8125 8.47656 7.92969C8.55469 8.00781 8.59375 8.16406 8.51562 8.28125C8.08594 9.10156 7.65625 9.10156 7.89062 9.49219C8.71094 10.8984 9.57031 11.4062 10.8203 12.0703C11.0547 12.1484 11.1719 12.1484 11.2891 11.9922C11.4062 11.8359 11.8359 11.3672 11.9922 11.1328C12.1094 10.9375 12.2656 10.9766 12.4609 11.0547C12.6562 11.0938 13.75 11.6406 13.9453 11.7578Z" fill="currentColor"/>
      </svg>
      <a href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $whatsapp ); ?></a>
    </li>
    <?php endif; ?>
  </ul>
  <?php endif; ?>

  <hr class="tb-owner-box__divider">

  <a
    href="<?php echo esc_url( tb_telegram_url() ); ?>"
    target="_blank"
    rel="noopener noreferrer"
    class="tb-owner-box__tg-join"
  >
    <?php echo esc_html( $label_tg_join ); ?>
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false">
      <path d="M6 3L11 8L6 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </a>

</aside>
