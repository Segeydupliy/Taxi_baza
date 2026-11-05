<?php
/**
 * Account type on sign-up (Figma: Flotilla - Register Step 1.1 / 1.2).
 *
 * Core registration always creates a car owner and ignores any type field.
 * We add a "Typ účtu" select to the register form (script, theme untouched) and,
 * when "fleet" was chosen, switch the new account to the fleet role right after
 * core assigns car_owner. Role_Switch then gives it status "draft", so the fleet
 * fills its profile after confirming the e-mail.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Registration {

	public const FIELD = 'tbf_account_type';

	private static bool $switching = false;

	public static function init(): void {
		add_action( 'set_user_role', [ self::class, 'maybe_make_fleet' ], 5, 3 );
		add_action( 'added_user_meta', [ self::class, 'fix_account_type_meta' ], 10, 4 );
		add_action( 'updated_user_meta', [ self::class, 'fix_account_type_meta' ], 10, 4 );
		add_action( 'wp_footer', [ self::class, 'print_field_script' ], 50 );
	}

	/** Is the current request the core register form with "fleet" chosen? */
	public static function wants_fleet(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- core verified tb_register nonce before creating the user.
		$action = isset( $_POST['tb_action'] ) ? sanitize_key( wp_unslash( $_POST['tb_action'] ) ) : '';
		$type   = isset( $_POST[ self::FIELD ] ) ? sanitize_key( wp_unslash( $_POST[ self::FIELD ] ) ) : '';
		// phpcs:enable
		return 'register' === $action && 'fleet' === $type;
	}

	/**
	 * @param int      $user_id   User.
	 * @param string   $role      New role.
	 * @param string[] $old_roles Old roles.
	 */
	public static function maybe_make_fleet( $user_id, $role, $old_roles = [] ): void {
		if ( self::$switching || 'car_owner' !== $role || ! self::wants_fleet() ) {
			return;
		}
		if ( in_array( 'administrator', (array) $old_roles, true ) ) {
			return;
		}
		self::$switching = true;
		$user = new \WP_User( (int) $user_id );
		$user->set_role( 'fleet' );
		update_user_meta( (int) $user_id, 'account_type', 'fleet' );
		self::$switching = false;
	}

	/**
	 * Core writes account_type=car_owner after set_role(); keep it in sync for fleets.
	 *
	 * @param int    $meta_id  Meta ID.
	 * @param int    $user_id  User.
	 * @param string $key      Meta key.
	 * @param mixed  $value    Value.
	 */
	public static function fix_account_type_meta( $meta_id, $user_id, $key, $value ): void {
		if ( 'account_type' !== $key || 'fleet' === $value || self::$switching || ! self::wants_fleet() ) {
			return;
		}
		if ( Status::is_fleet( (int) $user_id ) ) {
			self::$switching = true;
			update_user_meta( (int) $user_id, 'account_type', 'fleet' );
			self::$switching = false;
		}
	}

	/** Adds the select to the register form on /login/?action=register. */
	public static function print_field_script(): void {
		if ( is_user_logged_in() || ! is_page_template( 'page-login.php' ) ) {
			return;
		}
		$data = [
			'name'  => self::FIELD,
			'label' => t( 'reg_type_label' ),
			'opts'  => [
				[ 'car_owner', t( 'reg_type_owner' ) ],
				[ 'fleet', t( 'reg_type_fleet' ) ],
			],
		];
		?>
<script id="tbf-reg-type">
(function () {
	var d = <?php echo wp_json_encode( $data ); ?>;
	var hidden = document.querySelector('form input[name="tb_action"][value="register"]');
	if (!hidden) { return; }
	var form = hidden.form, submit = form.querySelector('button[type="submit"]');
	if (!submit || form.querySelector('[name="' + d.name + '"]')) { return; }
	var wrap = document.createElement('div'); wrap.className = 'tb-auth-form__field';
	var label = document.createElement('label'); label.className = 'tb-auth-form__label'; label.htmlFor = 'tbf-account-type'; label.textContent = d.label;
	var sel = document.createElement('select'); sel.id = 'tbf-account-type'; sel.name = d.name; sel.className = 'tb-auth-form__input';
	d.opts.forEach(function (o) { var op = document.createElement('option'); op.value = o[0]; op.textContent = o[1]; sel.appendChild(op); });
	try { if (/[?&]type=fleet\b/.test(location.search)) { sel.value = 'fleet'; } } catch (e) {}
	wrap.appendChild(label); wrap.appendChild(sel);
	var anchor = form.querySelector('#tb-recaptcha-token') || submit;
	anchor.parentNode.insertBefore(wrap, anchor);
}());
</script>
		<?php
	}
}
