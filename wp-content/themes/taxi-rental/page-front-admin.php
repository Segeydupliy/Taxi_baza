<?php
/**
 * Template Name: Front Admin
 * Template Post Type: page
 *
 * Front-end admin panel for administrators.
 * Uses get_header() / get_footer() so the site header and footer are visible.
 * Tab routing via ?tab= query param.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prevent caching — PHP auth check must always run.
nocache_headers();

if ( ! current_user_can( 'manage_options' ) ) {
	wp_safe_redirect( wp_login_url( get_permalink() ) );
	exit;
}

// Disable WordPress admin bar on this page.
add_filter( 'show_admin_bar', '__return_false' );

// noindex — admin panel should not be indexed.
add_action( 'wp_head', static function (): void {
	echo '<meta name="robots" content="noindex,nofollow">' . "\n";
} );

// Edit sub-mode: ?tab=listings&edit=POST_ID
$edit_post_id = 0;
if ( isset( $_GET['tab'] ) && 'listings' === sanitize_key( wp_unslash( $_GET['tab'] ) ) ) {
	$edit_post_id = absint( wp_unslash( $_GET['edit'] ?? 0 ) );
}
$is_edit_mode = $edit_post_id > 0;

// User sub-mode: ?tab=users&user=USER_ID[&action=edit]
$view_user_id = 0;
$user_action  = '';
if ( isset( $_GET['tab'] ) && 'users' === sanitize_key( wp_unslash( $_GET['tab'] ) ) ) {
	$view_user_id = absint( wp_unslash( $_GET['user'] ?? 0 ) );
	$user_action  = sanitize_key( wp_unslash( $_GET['action'] ?? '' ) );
}
$is_user_detail = $view_user_id > 0 && 'edit' !== $user_action;
$is_user_edit   = $view_user_id > 0 && 'edit' === $user_action;

// Blog sub-mode: ?tab=blog&new=1  OR  ?tab=blog&edit=POST_ID
$blog_post_id  = 0;
$is_blog_new   = false;
if ( isset( $_GET['tab'] ) && 'blog' === sanitize_key( wp_unslash( $_GET['tab'] ) ) ) {
	$blog_post_id = absint( wp_unslash( $_GET['edit'] ?? 0 ) );
	$is_blog_new  = isset( $_GET['new'] ) && '1' === sanitize_key( wp_unslash( $_GET['new'] ) );
}
$is_blog_edit_mode = $blog_post_id > 0 || $is_blog_new;

// Listing edit uses a custom AJAX form (not acf_form) — no acf_form_head() needed.

$current_user = wp_get_current_user();
$avatar_char  = strtoupper( mb_substr( $current_user->display_name ?: $current_user->user_login, 0, 1 ) );

$allowed_tabs = [ 'dashboard', 'listings', 'users', 'cities', 'categories', 'blog' ];
$tab          = sanitize_key( wp_unslash( $_GET['tab'] ?? 'dashboard' ) );
if ( ! in_array( $tab, $allowed_tabs, true ) ) {
	$tab = 'dashboard';
}

$panel_url = get_permalink();
$tab_url   = static fn( string $t ): string => esc_url( add_query_arg( 'tab', $t, $panel_url ) );

$nav_items = [
	'dashboard'  => tb_opt( 'tb_fa_nav_dashboard', 'Prehľad' ),
	'listings'   => tb_opt( 'tb_fa_nav_adverts', 'Inzeráty' ),
	'users'      => tb_opt( 'tb_fa_nav_users', 'Používatelia' ),
	'cities'     => tb_opt( 'tb_fa_nav_cities', 'Mestá' ),
	'categories' => tb_opt( 'tb_fa_nav_categories', 'Kategórie' ),
	'blog'       => tb_opt( 'tb_fa_nav_blog', 'Blog / Správy' ),
];

// Tab → template file mapping (tab key differs from file name where needed).
$tab_file_map = [
	'listings' => 'adverts',
];

get_header();
?>

<!-- ─── Admin panel root (holds CSS custom properties --fa-*) ──── -->
<div class="tb-fadmin">

<!-- ─── Tab Navigation ─────────────────────────────────────────── -->
<nav class="tb-fa-nav" aria-label="<?php esc_attr_e( 'Розділи панелі', 'taxi-rental' ); ?>">
	<div class="tb-fa-nav__inner">
		<?php foreach ( $nav_items as $key => $label ) : ?>
			<a
				href="<?php echo $tab_url( $key ); ?>"
				class="tb-fa-nav__tab<?php echo $tab === $key ? ' is-active' : ''; ?>"
				<?php echo $tab === $key ? 'aria-current="page"' : ''; ?>
			>
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</div>
</nav>

<!-- ─── Main Content ──────────────────────────────────────────── -->
<main class="tb-fa-content" id="tb-fa-main" tabindex="-1">
	<div class="tb-fa-container">
		<?php
		if ( $is_edit_mode ) {
			$part = get_template_directory() . '/template-parts/front-admin/tab-advert-edit.php';
		} elseif ( $is_user_edit ) {
			$part = get_template_directory() . '/template-parts/front-admin/tab-user-edit.php';
		} elseif ( $is_user_detail ) {
			$part = get_template_directory() . '/template-parts/front-admin/tab-user-detail.php';
		} elseif ( $is_blog_edit_mode ) {
			$part = get_template_directory() . '/template-parts/front-admin/tab-blog-edit.php';
		} else {
			$tab_file = $tab_file_map[ $tab ] ?? $tab;
			$part     = get_template_directory() . '/template-parts/front-admin/tab-' . $tab_file . '.php';
		}
		if ( file_exists( $part ) ) {
			require $part;
		} else {
			echo '<div class="tb-fa-empty">' . esc_html( tb_opt( 'tb_fa_nav_wip', 'Táto sekcia je vo vývoji.' ) ) . '</div>';
		}
		?>
	</div>
</main>

<!-- ─── Decline Modal ─────────────────────────────────────────── -->
<?php require get_template_directory() . '/template-parts/front-admin/modal-decline.php'; ?>

<!-- ─── Delete Modal ──────────────────────────────────────────── -->
<?php require get_template_directory() . '/template-parts/front-admin/modal-delete.php'; ?>

<!-- ─── Block User Modal ───────────────────────────────────────── -->
<?php require get_template_directory() . '/template-parts/front-admin/modal-block.php'; ?>

<!-- ─── Delete City Modal ─────────────────────────────────────── -->
<?php require get_template_directory() . '/template-parts/front-admin/modal-delete-city.php'; ?>

<!-- ─── Category Edit/Add Modal ───────────────────────────────── -->
<?php require get_template_directory() . '/template-parts/front-admin/modal-category-edit.php'; ?>

<!-- ─── Delete Category Modal ────────────────────────────────── -->
<?php require get_template_directory() . '/template-parts/front-admin/modal-delete-category.php'; ?>

<!-- ─── Delete Post Modal ────────────────────────────────────── -->
<?php require get_template_directory() . '/template-parts/front-admin/modal-delete-post.php'; ?>

<!-- ─── Blog Category Modal ──────────────────────────────────── -->
<?php require get_template_directory() . '/template-parts/front-admin/modal-blog-cat.php'; ?>

<!-- ─── Mobile Nav Drawer ─────────────────────────────────────── -->
<div id="tb-fa-mobile-nav" class="tb-fa-mobile-nav" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Навігація адмін-панелі', 'taxi-rental' ); ?>" hidden>
	<div class="tb-fa-mobile-nav__backdrop"></div>
	<nav class="tb-fa-mobile-nav__drawer">
		<?php foreach ( $nav_items as $key => $label ) : ?>
			<a
				href="<?php echo $tab_url( $key ); ?>"
				class="tb-fa-mobile-nav__link<?php echo $tab === $key ? ' is-active' : ''; ?>"
			>
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
		<hr class="tb-fa-mobile-nav__sep">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="tb-fa-mobile-nav__link">
			<?php echo esc_html( tb_opt( 'tb_fa_link_site', 'Na web' ) ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url() ); ?>" class="tb-fa-mobile-nav__link">
			<?php echo esc_html( tb_opt( 'tb_fa_link_wpadmin', 'WordPress admin' ) ); ?>
		</a>
	</nav>
</div>

<!-- ─── Toast ─────────────────────────────────────────────────── -->
<div id="tb-fa-toast" class="tb-fa-toast" role="status" aria-live="polite" aria-atomic="true"></div>

</div><!-- /.tb-fadmin -->

<?php get_footer(); ?>
