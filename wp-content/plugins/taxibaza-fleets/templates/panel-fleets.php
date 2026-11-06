<?php
/**
 * /panel/?tab=fleets — fleets list and fleet detail with moderation.
 * Figma: Admin Flotillias (952:1148), Admin Flotilla Info (952:1955).
 *
 * @package TaxiBaza\Fleets
 */

use TaxiBaza\Fleets\Admin_Panel;
use TaxiBaza\Fleets\Profile;
use TaxiBaza\Fleets\Status;
use function TaxiBaza\Fleets\t;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

nocache_headers();
add_filter( 'show_admin_bar', '__return_false' );
add_action(
	'wp_head',
	static function (): void {
		echo '<meta name="robots" content="noindex,nofollow">' . "\n";
	}
);

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filters.
$tbf_fleet  = isset( $_GET['fleet'] ) ? absint( $_GET['fleet'] ) : 0;
$tbf_filter = isset( $_GET['fstatus'] ) ? sanitize_key( wp_unslash( $_GET['fstatus'] ) ) : 'all';
$tbf_q      = isset( $_GET['fs'] ) ? sanitize_text_field( wp_unslash( $_GET['fs'] ) ) : '';
$tbf_done   = isset( $_GET['tbf_done'] ) ? sanitize_key( wp_unslash( $_GET['tbf_done'] ) ) : '';
$tbf_edit   = ! empty( $_GET['edit'] );
$tbf_err    = isset( $_GET['tbf_error'] ) ? sanitize_key( wp_unslash( $_GET['tbf_error'] ) ) : '';
// phpcs:enable

$tbf_opt   = static fn( string $k, string $fb ) => function_exists( 'tb_opt' ) ? tb_opt( $k, $fb ) : $fb;
$panel     = function_exists( 'tb_url' ) ? tb_url( '/panel/' ) : home_url( '/panel/' );
$tab_url   = static fn( string $tab ) => add_query_arg( 'tab', $tab, $panel );
$pending_n = Admin_Panel::pending_count();
$nav_items = [
	'dashboard'  => $tbf_opt( 'tb_fa_nav_dashboard', 'Prehľad' ),
	'listings'   => $tbf_opt( 'tb_fa_nav_adverts', 'Inzeráty' ),
	'users'      => $tbf_opt( 'tb_fa_nav_users', 'Používatelia' ),
	'fleets'     => t( 'fa_nav' ) . ( $pending_n ? ' (' . $pending_n . ')' : '' ),
	'cities'     => $tbf_opt( 'tb_fa_nav_cities', 'Mestá' ),
	'categories' => $tbf_opt( 'tb_fa_nav_categories', 'Kategórie' ),
	'blog'       => $tbf_opt( 'tb_fa_nav_blog', 'Blog / Správy' ),
];

$badge = static function ( int $id ): string {
	$st = Status::get( $id ) ?: Status::DRAFT;
	if ( Status::APPROVED === $st && Profile::has_pending_edits( $id ) ) {
		return '<span class="tb-fa-badge tbf-badge tbf-badge--pending">' . esc_html( t( 'fa_badge_edits' ) ) . '</span>';
	}
	$map = [ 'pending' => 'pending', 'approved' => 'approved', 'rejected' => 'rejected', 'blocked' => 'blocked', 'draft' => 'draft' ];
	return '<span class="tb-fa-badge tbf-badge tbf-badge--' . esc_attr( $map[ $st ] ) . '">' . esc_html( t( 'fa_badge_' . $st ) ) . '</span>';
};
$profile_of = static function ( int $id ): array {
	return Profile::get_live( $id ) ?: Profile::get_draft( $id );
};
$initials = static function ( string $name ): string {
	$words = preg_split( '/\s+/u', trim( $name ) ) ?: [];
	$out   = '';
	foreach ( array_slice( $words, 0, 2 ) as $w ) {
		$out .= mb_strtoupper( mb_substr( $w, 0, 1 ) );
	}
	return $out ?: '?';
};
$listings_of = static function ( int $id ): int {
	return function_exists( 'tb_listing_count' ) ? (int) tb_listing_count( [ 'publish', 'pending', 'draft', 'rejected', 'archived' ], $id ) : (int) count_user_posts( $id, 'car_listing' );
};

get_header();
?>
<div class="tb-fadmin">
<nav class="tb-fa-nav" aria-label="Panel">
	<div class="tb-fa-nav__inner">
		<?php foreach ( $nav_items as $key => $label ) : ?>
			<a href="<?php echo esc_url( 'fleets' === $key ? Admin_Panel::panel_url() : $tab_url( $key ) ); ?>" class="tb-fa-nav__tab<?php echo 'fleets' === $key ? ' is-active' : ''; ?>"<?php echo 'fleets' === $key ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>
</nav>

<main class="tb-fa-content tbf-panel" id="tb-fa-main" tabindex="-1">
<div class="tb-fa-container">

<?php if ( $tbf_done ) : ?>
	<p class="tbf-notice<?php echo 'error' === $tbf_done ? ' tbf-notice--error' : ''; ?>" role="status"><?php echo esc_html( t( 'error' === $tbf_done ? 'fa_done_error' : 'fa_done_' . $tbf_done ) ); ?></p>
<?php endif; ?>

<?php
if ( $tbf_fleet && Status::is_fleet( $tbf_fleet ) ) :
	// ---------------- DETAIL ----------------
	$u     = get_userdata( $tbf_fleet );
	$live  = Profile::get_live( $tbf_fleet );
	$draft = Profile::get_draft( $tbf_fleet );
	$name  = ( $live['name'] ?? '' ) ?: ( $draft['name'] ?? '' ) ?: $u->display_name;
	$back  = Admin_Panel::panel_url( [ 'fleet' => $tbf_fleet ] );
	?>
	<p><a class="tbf-back" href="<?php echo esc_url( Admin_Panel::panel_url() ); ?>"><?php echo esc_html( t( 'fa_back' ) ); ?></a></p>
	<div class="tb-fa-page-head tbf-detail-head">
		<div>
			<h1 class="tb-fa-page-head__title"><?php echo esc_html( $name ); ?> <?php echo $badge( $tbf_fleet ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
			<p class="tb-fa-page-head__stats">ID: <?php echo (int) $tbf_fleet; ?> · <?php echo esc_html( $u->user_email ); ?> · <?php echo esc_html( t( 'fa_registered' ) . ': ' . mysql2date( 'd.m.Y', $u->user_registered ) ); ?> · <?php printf( esc_html( t( 'fa_listings' ) ), (int) $listings_of( $tbf_fleet ) ); ?></p>
		</div>
		<div class="tbf-actions">
			<a class="tb-fa-btn tb-fa-btn--outline" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'users', 'user' => $tbf_fleet ], $panel ) ); ?>"><?php echo esc_html( t( 'fa_user_page' ) ); ?></a>
			<?php if ( ! $tbf_edit ) : ?><a class="tb-fa-btn tb-fa-btn--outline" href="<?php echo esc_url( Admin_Panel::panel_url( [ 'fleet' => $tbf_fleet, 'edit' => 1 ] ) ); ?>"><?php echo esc_html( t( 'edit' ) ); ?></a><?php endif; ?>
			<?php
			foreach ( Admin_Panel::actions_for( $tbf_fleet ) as $act ) {
				$cls = 'approve' === $act ? 'tb-fa-btn tbf-btn-accent' : ( in_array( $act, [ 'block' ], true ) ? 'tb-fa-btn tb-fa-btn--dark' : 'tb-fa-btn tb-fa-btn--outline' );
				echo Admin_Panel::button( $tbf_fleet, $act, $back, $cls ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* inside.
			}
			?>
		</div>
	</div>

	<?php
	$tbf_email    = $u->user_email;
	$tbf_edit_url = '';
	if ( $tbf_edit ) :
		// ---------------- EDIT (admin) ----------------
		$tbf_user        = $u;
		$tbf_form        = Profile::get_for_form( $tbf_fleet );
		$tbf_errors      = [];
		$tbf_flash       = get_transient( 'tbf_form_admin_' . $tbf_fleet );
		if ( is_array( $tbf_flash ) && 'form' === $tbf_err ) {
			$tbf_form   = array_merge( $tbf_form, (array) $tbf_flash['values'] );
			$tbf_errors = (array) $tbf_flash['errors'];
			delete_transient( 'tbf_form_admin_' . $tbf_fleet );
		}
		$tbf_cancel_url  = $back;
		$tbf_admin_fleet = $tbf_fleet;
		?>
		<?php if ( 'form' === $tbf_err ) : ?><p class="tbf-notice tbf-notice--error" role="alert"><?php echo esc_html( t( 'err_form' ) ); ?></p><?php endif; ?>
		<p class="tbf-notice" role="status"><?php echo esc_html( t( $draft || ! $live ? 'fa_edit_draft_note' : 'fa_edit_live_note' ) ); ?></p>
		<div class="tbf-cab"><?php require __DIR__ . '/parts/profile-form.php'; ?></div>
	<?php elseif ( $draft && $live ) :
		?>
		<div class="tbf-versions">
			<div><h2 class="tbf-version-title"><?php echo esc_html( t( 'fa_new_version' ) ); ?></h2><div class="tbf-cab"><?php $tbf_p = $draft; require __DIR__ . '/parts/profile-view.php'; ?></div></div>
			<div><h2 class="tbf-version-title"><?php echo esc_html( t( 'fa_live_version' ) ); ?></h2><div class="tbf-cab"><?php $tbf_p = $live; require __DIR__ . '/parts/profile-view.php'; ?></div></div>
		</div>
	<?php elseif ( $draft || $live ) : ?>
		<div class="tbf-cab"><?php $tbf_p = $draft ?: $live; require __DIR__ . '/parts/profile-view.php'; ?></div>
	<?php else : ?>
		<div class="tb-fa-empty"><?php echo esc_html( t( 'fa_no_profile' ) ); ?></div>
	<?php endif; ?>

<?php
else :
	// ---------------- LIST ----------------
	$groups = Admin_Panel::grouped();
	$ids    = $groups[ $tbf_filter ] ?? $groups['all'];
	if ( '' !== $tbf_q ) {
		$needle = mb_strtolower( $tbf_q );
		$ids    = array_values(
			array_filter(
				$ids,
				static function ( $id ) use ( $needle, $profile_of ) {
					$u = get_userdata( $id );
					$p = $profile_of( $id );
					$hay = mb_strtolower( implode( ' ', [ $u->display_name, $u->user_email, $p['name'] ?? '', $p['ico'] ?? '', $p['phone'] ?? '' ] ) );
					return false !== mb_strpos( $hay, $needle );
				}
			)
		);
	}
	// Pending first, like the Figma list.
	usort( $ids, static fn( $a, $b ) => (int) in_array( $b, $groups['pending'], true ) <=> (int) in_array( $a, $groups['pending'], true ) );
	$pills = [ 'all' => 'fa_all', 'approved' => 'fa_f_approved', 'pending' => 'fa_f_pending', 'rejected' => 'fa_f_rejected', 'blocked' => 'fa_f_blocked', 'draft' => 'fa_f_draft' ];
	?>
	<div class="tb-fa-page-head">
		<h1 class="tb-fa-page-head__title"><?php echo esc_html( t( 'fa_heading' ) ); ?></h1>
		<p class="tb-fa-page-head__stats"><?php printf( esc_html( t( 'fa_stats' ) ), count( $groups['all'] ), count( $groups['approved'] ), count( $groups['pending'] ), count( $groups['rejected'] ), count( $groups['blocked'] ) ); ?></p>
	</div>
	<div class="tb-fa-toolbar">
		<div class="tb-fa-toolbar__left"><div class="tb-fa-pills">
			<?php foreach ( $pills as $key => $label ) : ?>
				<a href="<?php echo esc_url( Admin_Panel::panel_url( 'all' === $key ? [] : [ 'fstatus' => $key ] ) ); ?>" class="tb-fa-pill<?php echo $tbf_filter === $key ? ' is-active' : ''; ?>"><?php echo esc_html( t( $label ) . ' ' . count( $groups[ $key ] ) ); ?></a>
			<?php endforeach; ?>
		</div></div>
		<form method="get" action="<?php echo esc_url( $panel ); ?>" class="tbf-search">
			<input type="hidden" name="tab" value="fleets">
			<?php if ( 'all' !== $tbf_filter ) : ?><input type="hidden" name="fstatus" value="<?php echo esc_attr( $tbf_filter ); ?>"><?php endif; ?>
			<input type="search" name="fs" value="<?php echo esc_attr( $tbf_q ); ?>" placeholder="<?php echo esc_attr( t( 'fa_search' ) ); ?>" aria-label="<?php echo esc_attr( t( 'fa_search' ) ); ?>">
		</form>
	</div>

	<?php if ( ! $ids ) : ?>
		<div class="tb-fa-empty"><?php echo esc_html( t( 'fa_empty' ) ); ?></div>
	<?php endif; ?>

	<?php
	foreach ( $ids as $id ) :
		$u      = get_userdata( $id );
		$p      = $profile_of( $id );
		$name   = ( $p['name'] ?? '' ) ?: $u->display_name;
		$detail = Admin_Panel::panel_url( [ 'fleet' => $id ] );
		$back   = Admin_Panel::panel_url( array_filter( [ 'fstatus' => 'all' !== $tbf_filter ? $tbf_filter : null, 'fs' => $tbf_q ?: null ] ) );
		$logo   = ! empty( $p['logo_id'] ) ? wp_get_attachment_image_url( (int) $p['logo_id'], 'thumbnail' ) : '';
		?>
		<article class="tb-fa-user-row">
			<div class="tb-fa-user-row__body">
				<a class="tb-fa-user-avatar-link" href="<?php echo esc_url( $detail ); ?>"><div class="tb-fa-user-avatar tb-fa-user-avatar--lg"><?php if ( $logo ) : ?><img src="<?php echo esc_url( $logo ); ?>" alt="" width="100" height="100"><?php else : ?><?php echo esc_html( $initials( $name ) ); ?><?php endif; ?></div></a>
				<div class="tb-fa-user-row__info">
					<div class="tb-fa-user-row__name-wrap"><a href="<?php echo esc_url( $detail ); ?>" class="tb-fa-user-row__name"><?php echo esc_html( $name ); ?></a> <?php echo $badge( $id ); // phpcs:ignore ?></div>
					<div class="tb-fa-user-row__meta">
						<?php if ( ! empty( $p['ico'] ) ) : ?><span class="tb-fa-user-row__meta-item">IČO: <?php echo esc_html( $p['ico'] ); ?></span><?php endif; ?>
						<?php if ( ! empty( $p['phone'] ) ) : ?><span class="tb-fa-user-row__meta-item"><?php echo esc_html( $p['phone'] ); ?></span><?php endif; ?>
						<span class="tb-fa-user-row__meta-item"><?php echo esc_html( $u->user_email ); ?></span>
					</div>
				</div>
				<div class="tb-fa-user-row__count-col"><span class="tb-fa-user-row__count"><?php printf( esc_html( t( 'fa_listings' ) ), (int) $listings_of( $id ) ); ?></span></div>
			</div>
			<div class="tb-fa-user-row__bar">
				<span class="tb-fa-user-row__id">ID: <?php echo (int) $id; ?></span>
				<div class="tb-fa-user-row__actions">
					<?php
					foreach ( Admin_Panel::actions_for( $id ) as $act ) {
						$cls = 'approve' === $act ? 'tb-fa-btn tbf-btn-accent' : ( 'block' === $act ? 'tb-fa-btn tb-fa-btn--dark' : 'tb-fa-btn tb-fa-btn--outline' );
						echo Admin_Panel::button( $id, $act, $back, $cls ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
					<a href="<?php echo esc_url( Admin_Panel::panel_url( [ 'fleet' => $id, 'edit' => 1 ] ) ); ?>" class="tb-fa-btn tb-fa-btn--outline"><?php echo esc_html( t( 'edit' ) ); ?></a>
					<a href="<?php echo esc_url( $detail ); ?>" class="tb-fa-btn tb-fa-btn--outline"><?php echo esc_html( t( 'fa_view' ) ); ?></a>
				</div>
			</div>
		</article>
	<?php endforeach; ?>
<?php endif; ?>

</div>
</main>
</div>
<?php
get_footer();
