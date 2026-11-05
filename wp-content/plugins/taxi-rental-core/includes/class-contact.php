<?php
/**
 * Contact form AJAX handler.
 *
 * @package TaxiRental\Core
 */

namespace TaxiRental\Core;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the public contact form (/kontakt/).
 */
final class Contact {

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'wp_ajax_taxi_rental_contact', [ self::class, 'handle' ] );
		add_action( 'wp_ajax_nopriv_taxi_rental_contact', [ self::class, 'handle' ] );
		add_action( 'wp_ajax_taxi_rental_owner_message', [ self::class, 'owner_message' ] );
		add_action( 'wp_ajax_nopriv_taxi_rental_owner_message', [ self::class, 'owner_message' ] );
	}

	/**
	 * "Send Message" from a car listing — email the request to the owner.
	 * Per MVP there is no stored conversation: one email with Reply-To.
	 */
	public static function owner_message(): void {
		if ( ! check_ajax_referer( 'taxi_rental_ajax', 'nonce', false ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Relácia vypršala. Obnovte stránku a skúste znova.', 'taxi-rental' ) ],
				403
			);
		}

		$listing_id = absint( wp_unslash( $_POST['listing_id'] ?? 0 ) );
		$listing    = $listing_id ? get_post( $listing_id ) : null;

		if ( ! $listing || 'car_listing' !== $listing->post_type || 'publish' !== $listing->post_status ) {
			wp_send_json_error(
				[ 'message' => __( 'Inzerát nebol nájdený.', 'taxi-rental' ) ],
				404
			);
		}

		$first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
		$last_name  = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
		$email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$phone      = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$experience = sanitize_text_field( wp_unslash( $_POST['experience'] ?? '' ) );
		$term       = sanitize_text_field( wp_unslash( $_POST['term'] ?? '' ) );
		$start_date = sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) );
		$message    = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

		$errors = [];
		if ( '' === $first_name ) {
			$errors['first_name'] = __( 'Zadajte meno.', 'taxi-rental' );
		}
		if ( '' === $last_name ) {
			$errors['last_name'] = __( 'Zadajte priezvisko.', 'taxi-rental' );
		}
		if ( ! is_email( $email ) ) {
			$errors['email'] = __( 'Zadajte platný email.', 'taxi-rental' );
		}
		if ( '' === $phone ) {
			$errors['phone'] = __( 'Zadajte telefón.', 'taxi-rental' );
		}
		if ( '' === $experience ) {
			$errors['experience'] = __( 'Vyberte vodičskú prax.', 'taxi-rental' );
		}
		if ( '' === $term ) {
			$errors['term'] = __( 'Vyberte dobu prenájmu.', 'taxi-rental' );
		}
		if ( '' === $start_date ) {
			$errors['start_date'] = __( 'Zadajte požadovaný dátum začiatku.', 'taxi-rental' );
		} elseif ( $start_date < current_time( 'Y-m-d' ) ) {
			// #120 — the browser's [min] and the JS check are both bypassable;
			// a request can post any date. Reject past dates here as well.
			$errors['start_date'] = __( 'Zvoľte, prosím, dnešný alebo neskorší dátum.', 'taxi-rental' );
		}
		if ( '' === $message ) {
			$errors['message'] = __( 'Napíšte správu.', 'taxi-rental' );
		}

		if ( $errors ) {
			wp_send_json_error(
				[
					'message' => __( 'Skontrolujte vyznačené polia.', 'taxi-rental' ),
					'fields'  => $errors,
				],
				422
			);
		}

		$owner = get_userdata( (int) $listing->post_author );
		if ( ! $owner ) {
			wp_send_json_error(
				[ 'message' => __( 'Vlastník nebol nájdený.', 'taxi-rental' ) ],
				404
			);
		}

		// Record the request in the driver's cabinet ("Moje žiadosti") when the
		// sender is a logged-in driver. Delivery still happens by email below.
		if ( is_user_logged_in() ) {
			$current = wp_get_current_user();
			if ( in_array( 'driver', (array) $current->roles, true ) ) {
				Requests::create(
					(int) $current->ID,
					$listing_id,
					[
						'first_name' => $first_name,
						'last_name'  => $last_name,
						'email'      => $email,
						'phone'      => $phone,
						'experience' => $experience,
						'term'       => $term,
						'start_date' => $start_date,
						'message'    => $message,
					]
				);
			}
		}

		// Respect the owner's notification preference.
		if ( ! Notifications::wants( $owner->ID, 'tb_notify_messages' ) ) {
			wp_send_json_success(
				[ 'message' => __( 'Žiadosť bola odoslaná! Vlastník vás kontaktuje emailom alebo telefonicky.', 'taxi-rental' ) ]
			);
		}

		$checks_list = [];
		if ( ! empty( $_POST['has_license'] ) ) {
			$checks_list[] = __( 'Má platný vodičský preukaz', 'taxi-rental' );
		}
		if ( ! empty( $_POST['has_platform'] ) ) {
			$checks_list[] = __( 'Má platný kontrakt s Bolt / Uber', 'taxi-rental' );
		}
		if ( ! empty( $_POST['has_deposit'] ) ) {
			$checks_list[] = __( 'Pripravený zaplatiť depozit', 'taxi-rental' );
		}

		[ $subj, $html, $headers ] = Notifications::render(
			'contact_owner',
			[
				'title'       => $listing->post_title,
				'listing_url' => (string) get_permalink( $listing ),
				'first_name'  => $first_name,
				'last_name'   => $last_name,
				'email'       => $email,
				'phone'       => $phone,
				'experience'  => $experience,
				'term'        => $term,
				'start_date'  => $start_date,
				'checks'      => $checks_list ? '— ' . implode( "\n— ", $checks_list ) : '',
				'message'     => $message,
			]
		);

		$headers[] = 'Reply-To: ' . $email;

		$sent = wp_mail( $owner->user_email, $subj, $html, $headers );

		if ( ! $sent ) {
			wp_send_json_error(
				[ 'message' => __( 'Žiadosť sa nepodarilo odoslať. Skúste neskôr alebo použite kontakty vlastníka.', 'taxi-rental' ) ],
				500
			);
		}

		wp_send_json_success(
			[ 'message' => __( 'Žiadosť bola odoslaná! Vlastník vás kontaktuje emailom alebo telefonicky.', 'taxi-rental' ) ]
		);
	}

	/**
	 * Validate, sanitize and email the contact message to the site admin.
	 */
	public static function handle(): void {
		if ( ! check_ajax_referer( 'taxi_rental_ajax', 'nonce', false ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Relácia vypršala. Obnovte stránku a skúste znova.', 'taxi-rental' ) ],
				403
			);
		}

		$first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
		$last_name  = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
		$email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$phone      = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$user_type  = sanitize_text_field( wp_unslash( $_POST['user_type'] ?? '' ) );
		$message    = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

		$errors = [];

		if ( '' === $first_name ) {
			$errors['first_name'] = __( 'Zadajte meno.', 'taxi-rental' );
		}
		if ( '' === $last_name ) {
			$errors['last_name'] = __( 'Zadajte priezvisko.', 'taxi-rental' );
		}
		if ( ! is_email( $email ) ) {
			$errors['email'] = __( 'Zadajte platný email.', 'taxi-rental' );
		}
		if ( '' === $phone ) {
			$errors['phone'] = __( 'Zadajte telefón.', 'taxi-rental' );
		}
		if ( ! \in_array( $user_type, [ 'driver', 'owner', 'fleet', 'other' ], true ) ) {
			$errors['user_type'] = __( 'Vyberte typ používateľa.', 'taxi-rental' );
		}
		if ( '' === $message ) {
			$errors['message'] = __( 'Napíšte správu.', 'taxi-rental' );
		}

		if ( $errors ) {
			wp_send_json_error(
				[
					'message' => __( 'Skontrolujte vyznačené polia.', 'taxi-rental' ),
					'fields'  => $errors,
				],
				422
			);
		}

		$types = [
			'driver' => __( 'Vodič', 'taxi-rental' ),
			'owner'  => __( 'Vlastník auta', 'taxi-rental' ),
			'fleet'  => __( 'Autopárk / flotila', 'taxi-rental' ),
			'other'  => __( 'Iné', 'taxi-rental' ),
		];

		$body = sprintf(
			"%s: %s %s\n%s: %s\n%s: %s\n%s: %s\n\n%s:\n%s",
			__( 'Meno', 'taxi-rental' ),
			$first_name,
			$last_name,
			'Email',
			$email,
			__( 'Telefón', 'taxi-rental' ),
			$phone,
			__( 'Typ používateľa', 'taxi-rental' ),
			$types[ $user_type ],
			__( 'Správa', 'taxi-rental' ),
			$message
		);

		$sent = wp_mail(
			get_option( 'admin_email' ),
			sprintf(
				/* translators: %s: sender full name */
				__( '[TaxiBaza.sk] Správa z kontaktného formulára od %s', 'taxi-rental' ),
				$first_name . ' ' . $last_name
			),
			$body,
			[ 'Reply-To: ' . $email ]
		);

		if ( ! $sent ) {
			wp_send_json_error(
				[ 'message' => __( 'Správu sa nepodarilo odoslať. Skúste neskôr alebo nám napíšte na email.', 'taxi-rental' ) ],
				500
			);
		}

		wp_send_json_success(
			[ 'message' => __( 'Ďakujeme! Vaša správa bola odoslaná. Odpovieme čo najskôr.', 'taxi-rental' ) ]
		);
	}
}
