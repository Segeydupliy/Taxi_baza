<?php
/**
 * Consent screen after the first Google sign-in (account created through Google).
 *
 * @package TaxiBaza\Fleets
 */

use TaxiBaza\Fleets\Consent;
use function TaxiBaza\Fleets\t;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tbf_posted = 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
get_header();
?>
<main class="tb-auth tbf-consent">
	<div class="tb-container">
		<div class="tb-auth-card" style="max-width:560px;margin:80px auto">
			<h1 class="tb-auth-card__title"><?php echo esc_html( t( 'terms_title' ) ); ?></h1>
			<p class="tb-auth-card__subtitle"><?php echo esc_html( t( 'terms_lead' ) ); ?></p>
			<form method="post" action="<?php echo esc_url( add_query_arg( Consent::QUERY, 1 ) ); ?>" class="tb-auth-form" style="margin-top:24px">
				<?php wp_nonce_field( Consent::NONCE ); ?>
				<label class="tbf-terms__label" style="display:flex;gap:10px;align-items:flex-start;font-size:15px;line-height:1.5;cursor:pointer">
					<input type="checkbox" name="<?php echo esc_attr( Consent::FIELD ); ?>" value="1" required style="margin-top:4px;width:18px;height:18px;flex:none;accent-color:#02f797">
					<span><?php echo Consent::label_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in label_html(). ?></span>
				</label>
				<?php if ( $tbf_posted ) : ?>
					<p class="tb-field-error" role="alert" style="color:#d0021b;margin:8px 0 0"><?php echo esc_html( t( 'terms_error' ) ); ?></p>
				<?php endif; ?>
				<button type="submit" class="tb-btn tb-btn--primary tb-btn--block" style="margin-top:24px"><?php echo esc_html( t( 'terms_accept' ) ); ?></button>
			</form>
			<p class="tb-auth-card__footer" style="margin-top:16px;text-align:center">
				<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php echo esc_html( t( 'terms_logout' ) ); ?></a>
			</p>
		</div>
	</div>
</main>
<?php
get_footer();
