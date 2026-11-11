<?php
/**
 * Fleet cabinet — "Мій профіль" (view + edit).
 * Figma: Flotilla Profile (994:40), Flotilla - Edit (991:11118), Register Step 2.x.
 *
 * @package TaxiBaza\Fleets
 */

use TaxiBaza\Fleets\Cabinet;
use TaxiBaza\Fleets\Profile;
use TaxiBaza\Fleets\Status;
use function TaxiBaza\Fleets\t;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tbf_user    = wp_get_current_user();
$tbf_uid     = (int) $tbf_user->ID;
$tbf_status  = Status::get( $tbf_uid );
$tbf_edits   = Profile::has_pending_edits( $tbf_uid );
$tbf_live    = Profile::get_live( $tbf_uid );
$tbf_can     = Profile::can_edit( $tbf_uid );
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only UI flags.
$tbf_saved   = isset( $_GET['tbf_saved'] ) ? sanitize_key( wp_unslash( $_GET['tbf_saved'] ) ) : '';
$tbf_err     = isset( $_GET['tbf_error'] ) ? sanitize_key( wp_unslash( $_GET['tbf_error'] ) ) : '';
$tbf_is_edit = $tbf_can && ( ! empty( $_GET['edit'] ) || Status::DRAFT === $tbf_status );
// phpcs:enable

// Values for the form: values from a failed submit, else draft/live/core data.
$tbf_form   = Profile::get_for_form( $tbf_uid );
$tbf_errors = [];
$tbf_flash  = get_transient( 'tbf_form_' . $tbf_uid );
if ( is_array( $tbf_flash ) && 'form' === $tbf_err ) {
	$tbf_form   = array_merge( $tbf_form, (array) $tbf_flash['values'] );
	$tbf_errors = (array) $tbf_flash['errors'];
	delete_transient( 'tbf_form_' . $tbf_uid );
}
// What drivers see / what the view shows: live, or the submitted draft while nothing is live yet.
$tbf_show = $tbf_live ?: ( Profile::get_draft( $tbf_uid ) ?: Profile::get_for_form( $tbf_uid ) );

$tbf_published = (int) count_user_posts( $tbf_uid, 'car_listing', true );
$tbf_greeting  = function_exists( 'tb_opt' ) ? tb_opt( 'tb_cab_greeting', 'Вітаємо %s' ) : 'Вітаємо %s';
$tbf_lbl       = static fn( string $k, string $fb ) => function_exists( 'tb_opt' ) ? tb_opt( $k, $fb ) : $fb;
$tbf_locked    = ! Status::can_add_listings( $tbf_uid );

// Status banner.
$tbf_banner = '';
$tbf_banner_class = 'pending';
if ( Status::DRAFT === $tbf_status ) {
	$tbf_banner = t( 'st_draft' );
} elseif ( Status::PENDING === $tbf_status ) {
	$tbf_banner = t( 'st_pending' );
} elseif ( Status::REJECTED === $tbf_status ) {
	$tbf_banner       = t( 'st_rejected' );
	$tbf_banner_class = 'rejected';
} elseif ( Status::BLOCKED === $tbf_status ) {
	$tbf_banner       = t( 'st_blocked' );
	$tbf_banner_class = 'blocked';
} elseif ( $tbf_edits ) {
	$tbf_banner = t( 'st_edits' );
}

get_header();
?>
<main class="tb-cabinet tbf-cab" id="main-content">
<div class="tb-container">

	<div class="tb-cabinet__hero">
		<div class="tb-cabinet__hero-text">
			<h1 class="tb-cabinet__greeting"><?php printf( esc_html( $tbf_greeting ), esc_html( '' !== (string) $tbf_show['name'] ? $tbf_show['name'] : $tbf_user->display_name ) ); ?></h1>
			<p class="tb-cabinet__stats"><?php echo esc_html( Status::DRAFT === $tbf_status ? t( 'complete_lead' ) : sprintf( t( 'stats_published' ), $tbf_published ) ); ?></p>
		</div>
		<a href="<?php echo esc_url( function_exists( 'tb_url' ) ? tb_url( '/cabinet/add-listing/' ) : home_url( '/cabinet/add-listing/' ) ); ?>" class="tb-btn tb-btn--primary tb-cabinet__add-btn"<?php echo $tbf_locked ? ' aria-disabled="true" tabindex="-1"' : ''; ?>>
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 13H13V19H11V13H5V11H11V5H13V11H19V13Z" fill="currentColor"/></svg>
			<?php echo esc_html( $tbf_lbl( 'tb_cab_add_listing_btn', 'Додати оголошення' ) ); ?>
		</a>
	</div>

	<?php if ( $tbf_banner ) : ?>
		<p class="tbf-status tbf-status--<?php echo esc_attr( $tbf_banner_class ); ?>" role="status"><?php echo esc_html( $tbf_banner ); ?></p>
	<?php endif; ?>
	<?php if ( 'server' === $tbf_err ) : ?>
		<p class="tbf-status tbf-status--rejected" role="alert"><?php echo esc_html( t( 'err_server' ) ); ?></p>
	<?php elseif ( 'form' === $tbf_err ) : ?>
		<p class="tbf-status tbf-status--rejected" role="alert"><?php echo esc_html( t( 'err_form' ) ); ?></p>
	<?php endif; ?>

	<nav class="tb-cabinet__tabs" role="tablist">
		<?php $tbf_tab_off = Status::DRAFT === $tbf_status ? ' tb-cabinet__tab--disabled" aria-disabled="true' : ''; ?>
		<a href="<?php echo esc_url( Cabinet::tab_url( 'listings' ) ); ?>" class="tb-cabinet__tab<?php echo $tbf_tab_off; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static string. ?>" role="tab" aria-selected="false"><?php echo esc_html( $tbf_lbl( 'tb_cab_nav_listings', 'Мої оголошення' ) ); ?></a>
		<span class="tb-cabinet__tab tb-cabinet__tab--disabled" aria-disabled="true"><?php echo esc_html( $tbf_lbl( 'tb_cab_nav_messages', 'Повідомлення' ) ); ?></span>
		<a href="<?php echo esc_url( Cabinet::profile_url() ); ?>" class="tb-cabinet__tab tb-cabinet__tab--active" role="tab" aria-selected="true"><?php echo esc_html( $tbf_lbl( 'tb_cab_nav_profile', 'Мій профіль' ) ); ?></a>
		<a href="<?php echo esc_url( Cabinet::tab_url( 'settings' ) ); ?>" class="tb-cabinet__tab" role="tab" aria-selected="false"><?php echo esc_html( $tbf_lbl( 'tb_cab_nav_settings', 'Налаштування' ) ); ?></a>
	</nav>

<?php if ( $tbf_is_edit ) : ?>
	<?php
	$tbf_cancel_url  = $tbf_live ? Cabinet::profile_url() : '';
	$tbf_admin_fleet = 0;
	require __DIR__ . '/parts/profile-form.php';
	?>

<?php else : // ---------------- VIEW ---------------- ?>
	<?php
	$tbf_p        = $tbf_show;
	$tbf_email    = $tbf_user->user_email;
	$tbf_edit_url = $tbf_can ? Cabinet::profile_url( [ 'edit' => 1 ] ) : '';
	require __DIR__ . '/parts/profile-view.php';
	?>
	<?php if ( $tbf_can ) : ?>
	<div class="tbf-savebar"><a class="tbf-btn tbf-btn--dark tbf-btn--lg" href="<?php echo esc_url( Cabinet::profile_url( [ 'edit' => 1 ] ) ); ?>"><?php echo esc_html( t( 'edit' ) ); ?></a></div>
	<?php endif; ?>
<?php endif; ?>

</div>
</main>

<?php if ( in_array( $tbf_saved, [ 'submitted', 'edits' ], true ) ) : ?>
<div class="tbf-modal" id="tbf-modal" role="dialog" aria-modal="true" aria-labelledby="tbf-modal-t">
	<div class="tbf-modal__box">
		<h2 class="tbf-modal__title" id="tbf-modal-t"><?php echo esc_html( t( 'mod_modal_title' ) ); ?></h2>
		<p class="tbf-modal__text"><?php echo esc_html( 'edits' === $tbf_saved ? t( 'mod_edits_modal_text' ) : t( 'mod_modal_text' ) ); ?></p>
		<button type="button" class="tbf-btn tbf-btn--dark" id="tbf-modal-close"><?php echo esc_html( t( 'close' ) ); ?></button>
	</div>
</div>
<?php endif; ?>

<?php
get_footer();
