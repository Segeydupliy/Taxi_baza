<?php

namespace TaxiRental\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Seeder {
	private const CITIES = [
		'Bratislava',
		'Košice',
		'Prešov',
		'Žilina',
		'Nitra',
		'Banská Bystrica',
		'Trnava',
		'Martin',
		'Trenčín',
		'Poprad',
	];

	private const PLATFORMS = [ 'Bolt', 'Uber' ];

	private const RIDE_CATEGORIES = [
		'Bolt' => [ 'Bolt', 'Comfort', 'Pet', 'Green', 'Premium', 'Luxury', 'XL' ],
		'Uber' => [ 'UberX', 'Comfort', 'XL' ],
	];

	/**
	 * Car makes → models. Hierarchical taxonomy car_make: make = parent term,
	 * model = child term. Curated for the EU / Slovak taxi & rideshare fleet
	 * (incl. EVs for Bolt Green / Uber Green). Admins manage the rest in wp-admin.
	 */
	private const CAR_MAKES = [
		'Škoda'         => [ 'Fabia', 'Octavia', 'Superb', 'Rapid', 'Scala', 'Citigo', 'Kamiq', 'Karoq', 'Kodiaq', 'Enyaq', 'Roomster' ],
		'Volkswagen'    => [ 'Up', 'Polo', 'Golf', 'Jetta', 'Bora', 'Passat', 'Arteon', 'Touran', 'Sharan', 'Caddy', 'T-Roc', 'Tiguan', 'ID.3', 'ID.4' ],
		'Toyota'        => [ 'Aygo', 'Yaris', 'Corolla', 'Auris', 'Avensis', 'Camry', 'Prius', 'Prius+', 'C-HR', 'RAV4', 'Verso', 'Proace' ],
		'Hyundai'       => [ 'i10', 'i20', 'i30', 'i40', 'Elantra', 'Sonata', 'Accent', 'Kona', 'Tucson', 'Santa Fe', 'Ioniq', 'Ioniq 5' ],
		'Kia'           => [ 'Picanto', 'Rio', 'Ceed', 'ProCeed', 'Optima', 'Stinger', 'Soul', 'Niro', 'e-Niro', 'Sportage', 'EV6' ],
		'Renault'       => [ 'Clio', 'Megane', 'Fluence', 'Talisman', 'Laguna', 'Captur', 'Kadjar', 'Scenic', 'Grand Scenic', 'Espace', 'Zoe' ],
		'Dacia'         => [ 'Logan', 'Sandero', 'Duster', 'Lodgy', 'Dokker', 'Jogger', 'Spring' ],
		'Peugeot'       => [ '208', '308', '508', '2008', '3008', '5008', 'Partner', 'Rifter', 'e-208' ],
		'Citroën'       => [ 'C1', 'C3', 'C4', 'C5', 'C-Elysée', 'Berlingo', 'C4 Picasso', 'C5 Aircross', 'ë-C4' ],
		'Opel'          => [ 'Corsa', 'Astra', 'Insignia', 'Vectra', 'Zafira', 'Combo', 'Crossland', 'Grandland', 'Mokka' ],
		'Ford'          => [ 'Fiesta', 'Focus', 'Mondeo', 'C-Max', 'S-Max', 'Galaxy', 'Kuga', 'Puma', 'Tourneo Connect', 'Transit Connect' ],
		'SEAT'          => [ 'Ibiza', 'Leon', 'Toledo', 'Arona', 'Ateca', 'Tarraco', 'Alhambra' ],
		'Cupra'         => [ 'Leon', 'Formentor', 'Born', 'Ateca' ],
		'Fiat'          => [ 'Panda', '500', '500L', '500X', 'Punto', 'Tipo', 'Doblo' ],
		'Nissan'        => [ 'Micra', 'Note', 'Pulsar', 'Primera', 'Leaf', 'Juke', 'Qashqai', 'X-Trail' ],
		'Mazda'         => [ 'Mazda2', 'Mazda3', 'Mazda6', 'CX-3', 'CX-30', 'CX-5', 'MX-30' ],
		'Honda'         => [ 'Jazz', 'Civic', 'Accord', 'Insight', 'HR-V', 'CR-V' ],
		'Mercedes-Benz' => [ 'A-Class', 'B-Class', 'C-Class', 'E-Class', 'S-Class', 'CLA', 'GLA', 'GLC', 'Vito', 'V-Class', 'EQB', 'EQC' ],
		'BMW'           => [ '1 Series', '2 Series', '3 Series', '4 Series', '5 Series', 'X1', 'X2', 'X3', 'i3', 'i4' ],
		'Audi'          => [ 'A1', 'A3', 'A4', 'A5', 'A6', 'Q2', 'Q3', 'Q5', 'e-tron' ],
		'Volvo'         => [ 'V40', 'V60', 'V90', 'S60', 'S90', 'XC40', 'XC60', 'XC90', 'C40' ],
		'Tesla'         => [ 'Model 3', 'Model Y', 'Model S', 'Model X' ],
		'Suzuki'        => [ 'Celerio', 'Swift', 'Baleno', 'Ignis', 'SX4 S-Cross', 'Vitara' ],
		'Mitsubishi'    => [ 'Space Star', 'Lancer', 'ASX', 'Eclipse Cross', 'Outlander' ],
		'Subaru'        => [ 'Impreza', 'Legacy', 'Outback', 'XV', 'Forester' ],
		'Mini'          => [ 'One', 'Cooper', 'Clubman', 'Countryman' ],
		'Jeep'          => [ 'Renegade', 'Compass', 'Cherokee' ],
		'Land Rover'    => [ 'Freelander', 'Discovery Sport', 'Range Rover Evoque' ],
		'Lexus'         => [ 'CT', 'IS', 'ES', 'UX', 'NX', 'RX' ],
		'MG'            => [ 'MG3', 'MG4', 'MG5', 'ZS', 'HS', 'Marvel R' ],
		'BYD'           => [ 'Atto 3', 'Dolphin', 'Seal', 'Han', 'Tang' ],
		'DS'            => [ 'DS 3', 'DS 4', 'DS 7' ],
		'Alfa Romeo'    => [ 'Mito', 'Giulietta', 'Giulia', 'Stelvio' ],
		'Chevrolet'     => [ 'Spark', 'Aveo', 'Cruze', 'Captiva' ],
	];

	/**
	 * Activation callback. Idempotent — safe to run multiple times.
	 */
	public static function run(): void {
		// Register CPT + taxonomies so term functions work during activation.
		CPT::register();
		Taxonomies::register();
		Roles::add_roles();

		// Force-update role display names in DB (add_role() only sets name on first creation).
		global $wp_roles;
		if ( isset( $wp_roles->roles['car_owner'] ) ) {
			$wp_roles->roles['car_owner']['name'] = 'Car Owner';
		}
		if ( isset( $wp_roles->roles['fleet'] ) ) {
			$wp_roles->roles['fleet']['name'] = 'Fleet';
		}
		if ( isset( $wp_roles->roles['driver'] ) ) {
			$wp_roles->roles['driver']['name'] = 'Driver';
		}
		update_option( 'wp_user_roles', $wp_roles->roles );

		self::seed_cities();
		self::seed_platforms();
		self::seed_ride_categories();
		self::seed_car_makes();
		self::seed_options_defaults();
		self::seed_email_templates();
		self::seed_add_listing_strings();
		self::seed_role_strings();
		self::seed_blog_posts();

		flush_rewrite_rules();
	}

	/**
	 * Write default values for all options-page ACF fields to the database.
	 * v3: always overwrites all values (no per-field skip) so Slovak strings replace Ukrainian ones.
	 */
	public static function seed_options_defaults(): void {
		if ( ! function_exists( 'update_field' ) ) {
			return;
		}
		if ( get_option( 'tb_options_seeded_v5' ) ) {
			return;
		}

		$defaults = [

			// ---- Catalog (tb-catalog) ----
			'tb_cat_archive_heading'     => __( 'Katalóg automobilov', 'taxi-rental' ),
			'tb_cat_load_more'           => __( 'Načítať viac', 'taxi-rental' ),
			'tb_cat_empty_title'         => __( 'Nič sa nenašlo', 'taxi-rental' ),
			'tb_cat_empty_desc'          => __( 'Skúste zmeniť parametre filtra.', 'taxi-rental' ),
			'tb_cat_reset_filters'       => __( 'Resetovať filtre', 'taxi-rental' ),
			'tb_cat_city_count'          => __( 'Dostupné %d áut', 'taxi-rental' ),
			'tb_cat_city_empty'          => __( 'Zatiaľ žiadne inzeráty', 'taxi-rental' ),
			'tb_cat_city_empty_desc'     => __( 'V tomto meste zatiaľ nie sú žiadne inzeráty.', 'taxi-rental' ),
			'tb_cat_city_all_catalog'    => __( 'Celý katalóg', 'taxi-rental' ),
			'tb_cat_price_from'          => __( 'od', 'taxi-rental' ),
			'tb_cat_price_unit'          => __( '/ týždeň', 'taxi-rental' ),
			'tb_cat_card_more'           => __( 'Viac', 'taxi-rental' ),
			'tb_cat_filter_city'         => __( 'Mesto', 'taxi-rental' ),
			'tb_cat_filter_cat'          => __( 'Kategória', 'taxi-rental' ),
			'tb_cat_filter_platform'     => __( 'Platforma', 'taxi-rental' ),
			'tb_cat_filter_fuel'         => __( 'Palivo', 'taxi-rental' ),
			'tb_cat_filter_trans'        => __( 'Prevodovka', 'taxi-rental' ),
			'tb_cat_filter_price'        => __( 'Cena', 'taxi-rental' ),
			'tb_cat_filter_reset'        => __( 'Resetovať', 'taxi-rental' ),
			'tb_cat_filter_apply'        => __( 'Použiť', 'taxi-rental' ),
			'tb_cat_filter_price_min_ph' => __( 'od €', 'taxi-rental' ),
			'tb_cat_filter_price_max_ph' => __( 'do €', 'taxi-rental' ),
			'tb_cat_filter_price_reset'  => __( 'Resetovať cenu', 'taxi-rental' ),
			'tb_cat_filter_sort_label'   => __( 'Zoradenie', 'taxi-rental' ),
			'tb_cat_fuel_petrol'         => __( 'Benzín', 'taxi-rental' ),
			'tb_cat_fuel_diesel'         => __( 'Nafta', 'taxi-rental' ),
			'tb_cat_fuel_electric'       => __( 'Elektro', 'taxi-rental' ),
			'tb_cat_fuel_hybrid'         => __( 'Hybrid', 'taxi-rental' ),
			'tb_cat_fuel_lpg'            => 'LPG',
			'tb_cat_trans_automatic'     => __( 'Automat', 'taxi-rental' ),
			'tb_cat_trans_manual'        => __( 'Manuál', 'taxi-rental' ),
			'tb_cat_sort_date_desc'      => __( 'Najnovšie', 'taxi-rental' ),
			'tb_cat_sort_price_asc'      => __( 'Cena: od najnižšej', 'taxi-rental' ),
			'tb_cat_sort_price_desc'     => __( 'Cena: od najvyššej', 'taxi-rental' ),
			'tb_cat_deposit_label'       => __( 'Depozit', 'taxi-rental' ),
			'tb_cat_owner_contact_btn'   => __( 'Kontaktovať vlastníka', 'taxi-rental' ),
			'tb_cat_owner_tg_btn'        => __( 'Pripojiť sa k Telegram kanálu', 'taxi-rental' ),
			'tb_cat_owner_own_listing'   => __( 'Toto je váš vlastný inzerát', 'taxi-rental' ),
			'tb_cat_role_owner'          => __( 'Vlastník auta', 'taxi-rental' ),
			'tb_cat_role_fleet'          => __( 'Autopárk', 'taxi-rental' ),
			'tb_cat_mista_zero'          => __( '0 áut', 'taxi-rental' ),
			'tb_cat_mista_empty'         => __( 'Mestá ešte neboli pridané.', 'taxi-rental' ),

			// ---- Single car (tb-single) ----
			'tb_sng_breadcrumb_home'     => __( 'Domov', 'taxi-rental' ),
			'tb_sng_breadcrumb_catalog'  => __( 'Katalóg', 'taxi-rental' ),
			'tb_sng_specs_heading'       => __( 'Technické údaje', 'taxi-rental' ),
			'tb_sng_conditions_heading'  => __( 'Podmienky prenájmu', 'taxi-rental' ),
			'tb_sng_spec_make'           => __( 'Značka', 'taxi-rental' ),
			'tb_sng_spec_model'          => __( 'Model', 'taxi-rental' ),
			'tb_sng_spec_year'           => __( 'Rok', 'taxi-rental' ),
			'tb_sng_spec_engine'         => __( 'Typ motora', 'taxi-rental' ),
			'tb_sng_spec_trans'          => __( 'Prevodovka', 'taxi-rental' ),
			'tb_sng_spec_consumption'    => __( 'Spotreba na 100 km', 'taxi-rental' ),
			'tb_sng_cond_price'          => __( 'Cena / týždeň', 'taxi-rental' ),
			'tb_sng_cond_deposit'        => __( 'Depozit', 'taxi-rental' ),
			'tb_sng_cond_mileage'        => __( 'Limit najazdených km', 'taxi-rental' ),
			'tb_sng_cond_min_term'       => __( 'Min. doba', 'taxi-rental' ),
			'tb_sng_cond_insurance'      => __( 'Poistenie', 'taxi-rental' ),
			'tb_sng_cond_maintenance'    => __( 'Servis zahrnutý', 'taxi-rental' ),
			'tb_sng_bool_yes'            => __( 'Áno', 'taxi-rental' ),
			'tb_sng_bool_no'             => __( 'Nie', 'taxi-rental' ),
			'tb_sng_bool_details'        => __( 'Áno (overiť)', 'taxi-rental' ),
			'tb_sng_similar_badge'       => __( 'Podobné', 'taxi-rental' ),
			'tb_sng_similar_heading'     => __( 'Podobné inzeráty', 'taxi-rental' ),
			'tb_sng_similar_link'        => __( 'Celý katalóg áut', 'taxi-rental' ),
			'tb_sng_modal_title'         => __( 'Kontaktovať vlastníka', 'taxi-rental' ),
			'tb_sng_modal_close'         => __( 'Zavrieť', 'taxi-rental' ),
			'tb_sng_modal_required'      => __( 'Povinné polia', 'taxi-rental' ),
			'tb_sng_form_first_name'     => __( 'Meno', 'taxi-rental' ),
			'tb_sng_form_last_name'      => __( 'Priezvisko', 'taxi-rental' ),
			'tb_sng_form_email'          => 'Email',
			'tb_sng_form_phone'          => __( 'Telefón', 'taxi-rental' ),
			'tb_sng_form_exp'            => __( 'Vodičský stáž', 'taxi-rental' ),
			'tb_sng_form_term'           => __( 'Doba prenájmu', 'taxi-rental' ),
			'tb_sng_form_date'           => __( 'Požadovaný dátum začiatku', 'taxi-rental' ),
			'tb_sng_form_message'        => __( 'Správa', 'taxi-rental' ),
			'tb_sng_select_ph'           => __( 'Vyberte prosím', 'taxi-rental' ),
			'tb_sng_exp_lt1'             => __( 'Menej ako 1 rok', 'taxi-rental' ),
			'tb_sng_exp_1_3'             => __( '1–3 roky', 'taxi-rental' ),
			'tb_sng_exp_3_5'             => __( '3–5 rokov', 'taxi-rental' ),
			'tb_sng_exp_5plus'           => __( '5+ rokov', 'taxi-rental' ),
			'tb_sng_term_1_2w'           => __( '1–2 týždne', 'taxi-rental' ),
			'tb_sng_term_2_4w'           => __( '2–4 týždne', 'taxi-rental' ),
			'tb_sng_term_1_3m'           => __( '1–3 mesiace', 'taxi-rental' ),
			'tb_sng_term_3plus'          => __( '3+ mesiace', 'taxi-rental' ),
			'tb_sng_check_license'       => __( 'Mám platný vodičský preukaz', 'taxi-rental' ),
			'tb_sng_check_platform'      => __( 'Mám platný kontrakt s Bolt / Uber', 'taxi-rental' ),
			'tb_sng_check_deposit'       => __( 'Som pripravený zaplatiť depozit', 'taxi-rental' ),
			'tb_sng_consent_text'        => __( 'Odoslaním správy súhlasíte s %1$s a %2$s. Používame cookies na zlepšenie webu.', 'taxi-rental' ),
			'tb_sng_privacy_link'        => __( 'Zásadami ochrany osobných údajov', 'taxi-rental' ),
			'tb_sng_terms_link'          => __( 'Podmienkami používania', 'taxi-rental' ),
			'tb_sng_form_submit'         => __( 'Odoslať žiadosť', 'taxi-rental' ),

			// ---- Auth (tb-auth) ----
			'tb_auth_login_title'           => __( 'Prihlásiť sa', 'taxi-rental' ),
			'tb_auth_login_email_label'     => 'Email',
			'tb_auth_login_email_ph'        => 'your@email.com',
			'tb_auth_login_pass_label'      => __( 'Heslo', 'taxi-rental' ),
			'tb_auth_login_pass_ph'         => '••••••••',
			'tb_auth_login_forgot'          => __( 'Zabudli ste heslo?', 'taxi-rental' ),
			'tb_auth_login_submit'          => __( 'Prihlásiť sa', 'taxi-rental' ),
			'tb_auth_login_no_account'      => __( 'Ešte nemáte účet?', 'taxi-rental' ),
			'tb_auth_login_register_link'   => __( 'Zaregistrovať sa', 'taxi-rental' ),
			'tb_auth_reg_title'             => __( 'Registrácia účtu', 'taxi-rental' ),
			'tb_auth_reg_subtitle'          => __( 'Len pre vlastníkov áut a flotily.', 'taxi-rental' ),
			'tb_auth_req_fields_note'       => __( 'Povinné polia', 'taxi-rental' ),
			'tb_auth_reg_name_label'        => __( 'Meno / Názov flotily', 'taxi-rental' ),
			'tb_auth_reg_name_ph'           => __( 'Ján Novák', 'taxi-rental' ),
			'tb_auth_reg_email_label'       => __( 'Email / Telefón', 'taxi-rental' ),
			'tb_auth_reg_pass_ph'           => __( 'Minimálne 8 znakov', 'taxi-rental' ),
			'tb_auth_reg_confirm_label'     => __( 'Zopakujte heslo', 'taxi-rental' ),
			'tb_auth_reg_type_label'        => __( 'Vyberte typ účtu', 'taxi-rental' ),
			'tb_auth_reg_type_owner'        => __( 'Fyzická osoba — prenájom 1–2 áut.', 'taxi-rental' ),
			'tb_auth_reg_type_fleet'        => __( 'Flotila — prenájom 3+ áut, zobrazenie ako spoločnosť.', 'taxi-rental' ),
			'tb_auth_reg_submit'            => __( 'Zaregistrovať sa', 'taxi-rental' ),
			'tb_auth_reg_have_account'      => __( 'Už máte účet?', 'taxi-rental' ),
			'tb_auth_recover_title'         => __( 'Obnovenie hesla', 'taxi-rental' ),
			'tb_auth_recover_subtitle'      => __( 'Zadajte emailovú adresu — pošleme vám odkaz na obnovu hesla.', 'taxi-rental' ),
			'tb_auth_recover_submit'        => __( 'Odoslať odkaz', 'taxi-rental' ),
			'tb_auth_back_to_login'         => __( 'Späť na prihlásenie', 'taxi-rental' ),
			'tb_auth_recover_email_ph'      => __( 'Zadajte váš email *', 'taxi-rental' ),
			'tb_auth_recover_sent_title'    => __( 'List bol odoslaný!', 'taxi-rental' ),
			'tb_auth_recover_sent_desc'     => __( 'Skontrolujte email %s a kliknite na odkaz na obnovenie hesla. Odkaz je platný 30 minút.', 'taxi-rental' ),
			'tb_auth_recover_no_email'      => __( 'Nedostali ste ho?', 'taxi-rental' ),
			'tb_auth_recover_resend'        => __( 'Odoslať znova', 'taxi-rental' ),
			'tb_auth_reset_title'           => __( 'Nové heslo', 'taxi-rental' ),
			'tb_auth_reset_subtitle'        => __( 'Zadajte nové heslo pre váš účet.', 'taxi-rental' ),
			'tb_auth_reset_new_pass_label'  => __( 'Nové heslo', 'taxi-rental' ),
			'tb_auth_reset_new_pass_ph'     => __( 'Minimálne 8 znakov', 'taxi-rental' ),
			'tb_auth_reset_confirm_label'   => __( 'Zopakujte heslo', 'taxi-rental' ),
			'tb_auth_reset_submit'          => __( 'Uložiť heslo', 'taxi-rental' ),
			'tb_auth_pw_toggle_show'        => __( 'Zobraziť heslo', 'taxi-rental' ),
			'tb_auth_pw_toggle_hide'        => __( 'Skryť heslo', 'taxi-rental' ),
			'tb_auth_pw_strength_label'     => __( 'Sila hesla', 'taxi-rental' ),
			'tb_auth_pw_strength_weak'      => __( 'Slabé', 'taxi-rental' ),
			'tb_auth_pw_strength_medium'    => __( 'Stredné', 'taxi-rental' ),
			'tb_auth_pw_strength_strong'    => __( 'Silné', 'taxi-rental' ),
			'tb_auth_reset_done_title'      => __( 'Heslo bolo úspešne zmenené!', 'taxi-rental' ),
			'tb_auth_reset_done_desc'       => __( 'Teraz sa môžete prihlásiť do svojho účtu s novým heslom.', 'taxi-rental' ),
			'tb_auth_reset_done_btn'        => __( 'Prihlásiť sa do účtu', 'taxi-rental' ),
			'tb_auth_deleted_title'         => __( 'Váš účet bol vymazaný', 'taxi-rental' ),
			'tb_auth_deleted_desc'          => __( 'Hotovo. Váš účet už neexistuje. Škoda, že odchádzate — spolu by sme mohli zarábať ešte dlho. Ak si to rozmyslíte, registrácia zaberie len pár minút.', 'taxi-rental' ),
			'tb_auth_deleted_btn'           => __( 'Vytvoriť nový účet', 'taxi-rental' ),
			'tb_auth_confirm_title'         => __( 'Potvrďte váš email', 'taxi-rental' ),
			'tb_auth_confirm_email_text'    => __( 'Poslali sme správu na %s. Kliknite na odkaz v správe a aktivujte účet.', 'taxi-rental' ),
			'tb_auth_confirm_no_email_text' => __( 'Poslali sme potvrdzovací odkaz na váš email. Skontrolujte schránku.', 'taxi-rental' ),
			'tb_auth_confirm_hint'          => __( 'Nedostali ste správu? Skontrolujte priečinok „Spam" alebo pošlite znova.', 'taxi-rental' ),
			'tb_auth_confirm_resend'        => __( 'Odoslať správu znova', 'taxi-rental' ),
			'tb_auth_notice_confirmed'      => __( 'Email potvrdený. Teraz sa môžete prihlásiť.', 'taxi-rental' ),

			// ---- Cabinet (tb-cabinet) ----
			'tb_cab_toast_submitted'           => __( 'Odoslané na moderáciu', 'taxi-rental' ),
			'tb_cab_toast_draft'               => __( 'Uložené ako koncept', 'taxi-rental' ),
			'tb_cab_toast_updated'             => __( 'Zmeny uložené', 'taxi-rental' ),
			'tb_cab_greeting'                  => __( 'Vitajte %s', 'taxi-rental' ),
			'tb_cab_stats'                     => __( 'Máte %1$d zverejnených inzerátov, %2$d v moderácii, %3$d konceptov.', 'taxi-rental' ),
			'tb_cab_add_listing_btn'           => __( 'Pridať inzerát', 'taxi-rental' ),
			'tb_cab_nav_listings'              => __( 'Moje inzeráty', 'taxi-rental' ),
			'tb_cab_nav_messages'              => __( 'Správy', 'taxi-rental' ),
			'tb_cab_nav_profile'               => __( 'Môj profil', 'taxi-rental' ),
			'tb_cab_nav_settings'              => __( 'Nastavenia', 'taxi-rental' ),
			'tb_cab_filter_all'                => __( 'Všetky', 'taxi-rental' ),
			'tb_cab_filter_publish'            => __( 'Zverejnené', 'taxi-rental' ),
			'tb_cab_filter_draft'              => __( 'Koncepty', 'taxi-rental' ),
			'tb_cab_filter_pending'            => __( 'V moderácii', 'taxi-rental' ),
			'tb_cab_filter_rejected'           => __( 'Zamietnuté', 'taxi-rental' ),
			'tb_cab_filter_archived'           => __( 'Archivované', 'taxi-rental' ),
			'tb_cab_search_label'              => __( 'Hľadať inzeráty', 'taxi-rental' ),
			'tb_cab_search_ph'                 => __( 'Hľadať', 'taxi-rental' ),
			'tb_cab_load_more'                 => __( 'Načítať viac', 'taxi-rental' ),
			'tb_cab_empty_title'               => __( 'Váš zoznam inzerátov je prázdny', 'taxi-rental' ),
			'tb_cab_empty_desc'                => __( 'Ešte ste nepridali žiadny inzerát. Teraz je ideálny čas to zmeniť.', 'taxi-rental' ),
			'tb_cab_status_publish'            => __( 'Zverejnené', 'taxi-rental' ),
			'tb_cab_status_pending'            => __( 'V moderácii', 'taxi-rental' ),
			'tb_cab_status_draft'              => __( 'Koncept', 'taxi-rental' ),
			'tb_cab_status_rejected'           => __( 'Zamietnuté', 'taxi-rental' ),
			'tb_cab_status_archived'           => __( 'Archivované', 'taxi-rental' ),
			'tb_cab_archive_confirm'           => __( 'Archivovať tento inzerát?', 'taxi-rental' ),
			'tb_cab_archive_btn'               => __( 'Archivovať', 'taxi-rental' ),
			'tb_cab_restore_btn'               => __( 'Obnoviť', 'taxi-rental' ),
			'tb_cab_edit_btn'                  => __( 'Upraviť', 'taxi-rental' ),
			'tb_cab_profile_photo_title'       => __( 'Profilová fotografia', 'taxi-rental' ),
			'tb_cab_profile_upload_btn'        => __( 'Nahrať novú', 'taxi-rental' ),
			'tb_cab_profile_delete_photo'      => __( 'Odstrániť', 'taxi-rental' ),
			'tb_cab_profile_info_title'        => __( 'Základné informácie', 'taxi-rental' ),
			'tb_cab_profile_edit_btn'          => __( 'Upraviť', 'taxi-rental' ),
			'tb_cab_profile_first_name'        => __( 'Meno', 'taxi-rental' ),
			'tb_cab_profile_last_name'         => __( 'Priezvisko', 'taxi-rental' ),
			'tb_cab_profile_phone'             => __( 'Telefón', 'taxi-rental' ),
			'tb_cab_profile_city'              => __( 'Mesto', 'taxi-rental' ),
			'tb_cab_profile_account_type'      => __( 'Typ účtu', 'taxi-rental' ),
			'tb_cab_profile_type_owner'        => __( 'Fyzická osoba', 'taxi-rental' ),
			'tb_cab_profile_type_fleet'        => __( 'Autopárk / flotila', 'taxi-rental' ),
			'tb_cab_profile_telegram'          => __( 'Telegram', 'taxi-rental' ),
			'tb_cab_profile_whatsapp'          => 'WhatsApp',
			'tb_cab_profile_about'             => __( 'Krátky popis', 'taxi-rental' ),
			'tb_cab_profile_save'              => __( 'Uložiť', 'taxi-rental' ),
			'tb_cab_profile_cancel'            => __( 'Zrušiť', 'taxi-rental' ),
			'tb_cab_profile_error'             => __( 'Skontrolujte meno a email — polia sú povinné.', 'taxi-rental' ),
			'tb_cab_profile_email_taken'       => __( 'Tento email je už používaný iným účtom.', 'taxi-rental' ),
			'tb_cab_delete_account_btn'        => __( 'Vymazať účet', 'taxi-rental' ),
			'tb_cab_delete_modal_title'        => __( 'Naozaj chcete vymazať účet', 'taxi-rental' ),
			'tb_cab_delete_modal_close'        => __( 'Zavrieť', 'taxi-rental' ),
			'tb_cab_delete_modal_desc'         => __( 'Ste si istý? Majitelia áut na TaxiBaza.sk zarábajú až 600 € týždenne z jedného auta — po vymazaní účtu prídete o všetky inzeráty, príjem aj nazbieranú históriu. Túto akciu nie je možné vrátiť späť.', 'taxi-rental' ),
			'tb_cab_delete_confirm'            => __( 'Vymazať', 'taxi-rental' ),
			'tb_cab_delete_cancel'             => __( 'Zrušiť', 'taxi-rental' ),
			'tb_cab_settings_pass_title'       => __( 'Zmeniť heslo', 'taxi-rental' ),
			'tb_cab_settings_pass_update'      => __( 'Aktualizovať', 'taxi-rental' ),
			'tb_cab_settings_notif_title'      => __( 'Oznámenia', 'taxi-rental' ),
			'tb_cab_settings_notif_desc'       => __( 'Nastavte si oznámenia podľa seba. Vyberajte len to, čo je pre vás skutočne dôležité.', 'taxi-rental' ),
			'tb_cab_settings_pass_current_ph'  => __( 'Aktuálne heslo', 'taxi-rental' ),
			'tb_cab_settings_pass_new_ph'      => __( 'Nové heslo', 'taxi-rental' ),
			'tb_cab_settings_pass_confirm_ph'  => __( 'Zopakujte nové heslo', 'taxi-rental' ),
			'tb_cab_notif_messages_title'      => __( 'Emailové oznámenia o správach', 'taxi-rental' ),
			'tb_cab_notif_messages_desc'       => __( 'Dostávajte emaily vždy, keď vám príde nová správa.', 'taxi-rental' ),
			'tb_cab_notif_moderation_title'    => __( 'Oznámenia o moderácii', 'taxi-rental' ),
			'tb_cab_notif_moderation_desc'     => __( 'Dozvedajte sa o stave kontroly vášho obsahu moderátormi.', 'taxi-rental' ),
			'tb_cab_notif_marketing_title'     => __( 'Marketingový newsletter', 'taxi-rental' ),
			'tb_cab_notif_marketing_desc'      => __( 'Dostávajte novinky, akcie a užitočné tipy od nášho tímu.', 'taxi-rental' ),
			'tb_cab_notice_pass_changed'       => __( 'Heslo bolo aktualizované.', 'taxi-rental' ),
			'tb_cab_notice_pass_wrong'         => __( 'Aktuálne heslo je nesprávne.', 'taxi-rental' ),
			'tb_cab_notice_pass_short'         => __( 'Nové heslo musí mať aspoň 8 znakov.', 'taxi-rental' ),
			'tb_cab_notice_pass_mismatch'      => __( 'Heslá sa nezhodujú.', 'taxi-rental' ),
			'tb_cab_notice_notif_saved'        => __( 'Nastavenia oznámení boli uložené.', 'taxi-rental' ),

			// ---- 404 (tb-404) ----
			'tb_404_label'       => __( 'Chyba', 'taxi-rental' ),
			'tb_404_title'       => __( 'Stránka sa nenašla', 'taxi-rental' ),
			'tb_404_desc'        => __( 'Zdá sa, že táto adresa už neexistuje alebo bola presunutá. Skontrolujte odkaz alebo sa vráťte na hlavnú stránku.', 'taxi-rental' ),
			'tb_404_home_btn'    => __( 'Na hlavnú', 'taxi-rental' ),
			'tb_404_catalog_btn' => __( 'Katalóg áut', 'taxi-rental' ),

			// ---- Blog (tb-blog) ----
			'tb_blog_title'      => __( 'Užitočné materiály pre vodičov a vlastníkov áut', 'taxi-rental' ),
			'tb_blog_filter_all' => __( 'Všetky', 'taxi-rental' ),
			'tb_blog_empty'      => __( 'Články ešte neboli zverejnené.', 'taxi-rental' ),

			// ---- Header nav labels ----
			'tb_nav_all_cities' => __( 'Všetky mestá →', 'taxi-rental' ),
			'tb_nav_panel'      => __( 'Panel', 'taxi-rental' ),

			// ---- Footer extension (in global tb-content options) ----
			'tb_footer_privacy_label' => __( 'Zásady ochrany osobných údajov', 'taxi-rental' ),
			'tb_footer_privacy_url'   => '/konfidenzialnist/',
			'tb_footer_terms_label'   => __( 'Podmienky používania', 'taxi-rental' ),
			'tb_footer_terms_url'     => '/umovy/',
			'tb_footer_nav_heading'      => __( 'Navigácia', 'taxi-rental' ),
			'tb_footer_platform_heading' => __( 'Platforma', 'taxi-rental' ),
			'tb_footer_cities_heading'   => __( 'Mestá', 'taxi-rental' ),
			'tb_footer_support_heading'  => __( 'Podpora', 'taxi-rental' ),
			'tb_footer_cities_all'       => __( 'Všetky mestá', 'taxi-rental' ),
			'tb_footer_lang_label'       => __( 'Slovenčina', 'taxi-rental' ),

			// ---- Front Admin UI (tb-front-admin) ----

			// Navigation / page-front-admin
			'tb_fa_nav_dashboard'     => 'Prehľad',
			'tb_fa_nav_adverts'       => 'Inzeráty',
			'tb_fa_nav_users'         => 'Používatelia',
			'tb_fa_nav_cities'        => 'Mestá',
			'tb_fa_nav_categories'    => 'Kategórie',
			'tb_fa_nav_blog'          => 'Blog / Správy',
			'tb_fa_nav_wip'           => 'Táto sekcia je vo vývoji.',
			'tb_fa_link_site'         => 'Na web',
			'tb_fa_link_wpadmin'      => 'WordPress admin',

			// Dashboard
			'tb_fa_dash_stats_title'  => 'Celková štatistika',
			'tb_fa_dash_total'        => 'Celkom inzerátov',
			'tb_fa_dash_pending'      => 'V moderácii',
			'tb_fa_dash_users'        => 'Používateľov',
			'tb_fa_dash_cities'       => 'Miest',
			'tb_fa_dash_mod_heading'  => 'Čakajú na moderáciu',
			'tb_fa_dash_price'        => 'od %s€ / týždeň',
			'tb_fa_dash_reject_btn'   => 'Zamietnuť',
			'tb_fa_dash_edit_btn'     => 'Upraviť',
			'tb_fa_dash_publish_btn'  => 'Zverejniť',
			'tb_fa_dash_empty'        => 'Žiadne inzeráty čakajú na moderáciu.',

			// Adverts tab
			'tb_fa_adv_heading'       => 'Inzeráty',
			'tb_fa_adv_stats'         => 'Celkom %1$d, zverejnených %2$d, v moderácii %3$d, archivovaných %4$d, zamietnutých %5$d',
			'tb_fa_adv_all'           => 'Všetky',
			'tb_fa_adv_published'     => 'Zverejnené',
			'tb_fa_adv_pending'       => 'V moderácii',
			'tb_fa_adv_rejected'      => 'Zamietnuté',
			'tb_fa_adv_archived'      => 'Archivované',
			'tb_fa_adv_search_label'  => 'Hľadať',
			'tb_fa_adv_search_ph'     => 'Hľadať inzeráty',
			'tb_fa_adv_search_btn'    => 'Hľadať',
			'tb_fa_adv_search_reset'  => 'Vymazať hľadanie',
			'tb_fa_adv_sort_label'    => 'Zoradiť',
			'tb_fa_adv_sort_date_desc' => 'Najnovšie',
			'tb_fa_adv_sort_date_asc'  => 'Najstaršie',
			'tb_fa_adv_sort_price_asc'  => 'Cena: od najnižšej',
			'tb_fa_adv_sort_price_desc' => 'Cena: od najvyššej',
			'tb_fa_adv_price'         => 'od %s€ / týždeň',
			'tb_fa_adv_reject_btn'    => 'Zamietnuť',
			'tb_fa_adv_edit_btn'      => 'Upraviť',
			'tb_fa_adv_publish_btn'   => 'Zverejniť',
			'tb_fa_adv_archive_btn'   => 'Archivovať',
			'tb_fa_adv_review_btn'    => 'Na posúdenie',
			'tb_fa_adv_restore_btn'   => 'Obnoviť',
			'tb_fa_adv_delete_btn'    => 'Odstrániť',
			'tb_fa_adv_load_more'     => 'Načítať viac',
			'tb_fa_adv_empty_search'  => 'Pre hľadanie „%s" sa nič nenašlo.',
			'tb_fa_adv_empty'         => 'Žiadne inzeráty.',

			// Advert edit tab
			'tb_fa_ae_not_found'      => 'Inzerát sa nenašiel.',
			'tb_fa_ae_no_acf'         => 'ACF Pro nie je aktivované.',
			'tb_fa_ae_st_publish'     => 'Zverejnené',
			'tb_fa_ae_st_pending'     => 'V moderácii',
			'tb_fa_ae_st_rejected'    => 'Zamietnuté',
			'tb_fa_ae_st_archived'    => 'Archivované',
			'tb_fa_ae_st_draft'       => 'Koncept',
			'tb_fa_ae_cancel_btn'     => 'Zrušiť zmeny',
			'tb_fa_ae_archive_btn'    => 'Archivovať',
			'tb_fa_ae_restore_btn'    => 'Obnoviť',
			'tb_fa_ae_publish_btn'    => 'Zverejniť',
			'tb_fa_ae_section_basic'  => 'Základné informácie',
			'tb_fa_ae_make'           => 'Značka auta',
			'tb_fa_ae_model'          => 'Model',
			'tb_fa_ae_year'           => 'Rok výroby',
			'tb_fa_ae_engine'         => 'Typ motora',
			'tb_fa_ae_select_ph'      => 'Vyberte prosím',
			'tb_fa_ae_trans'          => 'Prevodovka',
			'tb_fa_ae_consumption'    => 'Spotreba (l/100km)',
			'tb_fa_ae_consumption_ph' => 'Napr. 8l/100km',
			'tb_fa_ae_photos'         => 'Fotografie',
			'tb_fa_ae_photos_hint'    => 'Prvá fotografia bude na obálke inzerátu. Poradie obrázkov zatiaľ nie je možné meniť.',
			'tb_fa_ae_add_photo'      => 'Pridať fotografiu',
			'tb_fa_ae_remove_photo'   => 'Odstrániť fotografiu',
			'tb_fa_ae_section_terms'  => 'Podmienky prenájmu',
			'tb_fa_ae_price'          => 'Cena / týždeň (€)',
			'tb_fa_ae_deposit'        => 'Depozit (€)',
			'tb_fa_ae_mileage'        => 'Limit najazdených km / mesiac (km)',
			'tb_fa_ae_mileage_ph'     => '0 — bez obmedzenia',
			'tb_fa_ae_min_term'       => 'Minimálna doba prenájmu (dní)',
			'tb_fa_ae_insurance'      => 'Poistenie zahrnuté',
			'tb_fa_ae_maintenance'    => 'Servis zahrnutý',
			'tb_fa_ae_section_loc'    => 'Lokalita a kategórie',
			'tb_fa_ae_city'           => 'Mesto',
			'tb_fa_ae_section_extra'  => 'Doplňujúce informácie',
			'tb_fa_ae_description'    => 'Popis inzerátu',
			'tb_fa_ae_desc_hint'      => 'Zamyslite sa, čo by ste chceli vedieť z inzerátu. A doplňte to do popisu.',

			// Users tab
			'tb_fa_usr_heading'       => 'Používatelia',
			'tb_fa_usr_stats'         => 'Celkom %1$d, aktívnych %2$d, zablokovaných %3$d',
			'tb_fa_usr_active'        => 'Aktívni',
			'tb_fa_usr_blocked'       => 'Zablokovaní',
			'tb_fa_usr_filter_city'   => 'Mesto',
			'tb_fa_usr_filter_type'   => 'Typ používateľa',
			'tb_fa_usr_filter_apply'  => 'Použiť',
			'tb_fa_usr_search_label'  => 'Hľadať',
			'tb_fa_usr_search_ph'     => 'Hľadať používateľov',
			'tb_fa_usr_sort_registered_desc' => 'Najnovší',
			'tb_fa_usr_sort_registered_asc'  => 'Najstarší',
			'tb_fa_usr_sort_name_asc'        => 'Meno: A-Z',
			'tb_fa_usr_sort_name_desc'       => 'Meno: Z-A',
			'tb_fa_usr_empty'         => 'Žiadni používatelia.',
			'tb_fa_usr_listings'      => '%d inzerátov',
			'tb_fa_usr_block_btn'     => 'Zablokovať',
			'tb_fa_usr_unblock_btn'   => 'Odblokovať',

			// User detail tab
			'tb_fa_ud_not_found'      => 'Používateľ sa nenašiel.',
			'tb_fa_ud_role_admin'     => 'Administrátor',
			'tb_fa_ud_st_blocked'     => 'Zablokovaný',
			'tb_fa_ud_st_active'      => 'Aktívny',
			'tb_fa_ud_unblock_btn'    => 'Odblokovať',
			'tb_fa_ud_block_btn'      => 'Zablokovať',
			'tb_fa_ud_edit_btn'       => 'Upraviť',
			'tb_fa_ud_photo_title'    => 'Profilová fotografia',
			'tb_fa_ud_upload_btn'     => 'Nahrať nové',
			'tb_fa_ud_delete_photo'   => 'Odstrániť',
			'tb_fa_ud_section_info'   => 'Základné informácie',
			'tb_fa_ud_first_name'     => 'Meno',
			'tb_fa_ud_last_name'      => 'Priezvisko',
			'tb_fa_ud_phone'          => 'Telefón',
			'tb_fa_ud_email'          => 'Email',
			'tb_fa_ud_city'           => 'Mesto',
			'tb_fa_ud_account_type'   => 'Typ účtu',
			'tb_fa_ud_telegram'       => 'Telegram',
			'tb_fa_ud_listings_title' => 'Inzeráty',
			'tb_fa_ud_stats'          => 'Celkom %1$d, zverejnených %2$d, v moderácii %3$d, archivovaných %4$d, zamietnutých %5$d',
			'tb_fa_ud_filter_all'     => 'Všetky',
			'tb_fa_ud_filter_publish' => 'Zverejnené',
			'tb_fa_ud_filter_pending' => 'V moderácii',
			'tb_fa_ud_filter_reject'  => 'Zamietnuté',
			'tb_fa_ud_filter_archive' => 'Archivované',
			'tb_fa_ud_search_ph'      => 'Hľadať...',
			'tb_fa_ud_search_label'   => 'Hľadať inzeráty',
			'tb_fa_ud_search_btn'     => 'Hľadať',
			'tb_fa_ud_reject_btn'     => 'Zamietnuť',
			'tb_fa_ud_publish_btn'    => 'Zverejniť',
			'tb_fa_ud_archive_btn'    => 'Archivovať',
			'tb_fa_ud_review_btn'     => 'Na posúdenie',
			'tb_fa_ud_view_btn'       => 'Zobraziť',
			'tb_fa_ud_empty'          => 'Žiadne inzeráty.',

			// User edit tab
			'tb_fa_ue_not_found'      => 'Používateľ sa nenašiel.',
			'tb_fa_ue_type_owner'     => 'Fyzická osoba',
			'tb_fa_ue_type_fleet'     => 'Flotila',
			'tb_fa_ue_cancel_btn'     => 'Zrušiť',
			'tb_fa_ue_save_btn'       => 'Uložiť',
			'tb_fa_ue_saved_notice'   => 'Zmeny uložené',
			'tb_fa_ue_photo_title'    => 'Profilová fotografia',
			'tb_fa_ue_upload_btn'     => 'Nahrať nové',
			'tb_fa_ue_delete_photo'   => 'Odstrániť',
			'tb_fa_ue_section_info'   => 'Základné informácie',
			'tb_fa_ue_first_name'     => 'Meno',
			'tb_fa_ue_last_name'      => 'Priezvisko',
			'tb_fa_ue_email'          => 'Email',
			'tb_fa_ue_phone'          => 'Telefón',
			'tb_fa_ue_user_type'      => 'Typ používateľa',
			'tb_fa_ue_telegram'       => 'Telegram',
			'tb_fa_ue_about'          => 'Krátky popis',

			// Cities tab
			'tb_fa_cit_heading'       => 'Mestá',
			'tb_fa_cit_count'         => '%d aktívnych miest',
			'tb_fa_cit_add_btn'       => 'Pridať mesto',
			'tb_fa_cit_delete_btn'    => 'Odstrániť',
			'tb_fa_cit_name_label'    => 'Názov mesta',
			'tb_fa_cit_name_ph'       => 'Napr.: Bratislava',
			'tb_fa_cit_empty_records' => 'Žiadne záznamy.',
			'tb_fa_cit_empty_first'   => 'Mestá ešte neexistujú. Pridajte prvé mesto.',

			// Categories tab
			'tb_fa_cat_heading'       => 'Kategórie',
			'tb_fa_cat_add_btn'       => 'Pridať kategóriu',
			'tb_fa_cat_empty'         => 'Žiadne kategórie. Kliknite na „Pridať kategóriu".',
			'tb_fa_cat_no_sub'        => 'Žiadne podkategórie',
			'tb_fa_cat_delete_btn'    => 'Odstrániť kategóriu',

			// Category edit modal
			'tb_fa_mce_close'         => 'Zavrieť',
			'tb_fa_mce_title'         => 'Upraviť',
			'tb_fa_mce_title_add'     => 'Pridať kategóriu',
			'tb_fa_mce_block_name'    => 'Názov bloku kategórií',
			'tb_fa_mce_block_ph'      => 'Napr.: Bolt',
			'tb_fa_mce_cats_label'    => 'Kategórie',
			'tb_fa_mce_add_more'      => 'Pridať ďalšie',
			'tb_fa_mce_cancel'        => 'Zrušiť',
			'tb_fa_mce_save'          => 'Uložiť',

			// Blog tab
			'tb_fa_blg_heading'       => 'Blog / Správy',
			'tb_fa_blg_stats'         => '%1$s článkov, %2$s kategórií',
			'tb_fa_blg_add_btn'       => 'Pridať článok',
			'tb_fa_blg_toast_added'   => 'Článok bol pridaný',
			'tb_fa_blg_all'           => 'Všetky',
			'tb_fa_blg_published'     => 'Zverejnené',
			'tb_fa_blg_drafts'        => 'Koncepty',
			'tb_fa_blg_review'        => 'Na posúdenie',
			'tb_fa_blg_search_label'  => 'Hľadať články',
			'tb_fa_blg_search_reset'  => 'Vymazať hľadanie',
			'tb_fa_blg_empty'         => 'Žiadne články nenájdené.',
			'tb_fa_blg_no_title'      => '(bez názvu)',
			'tb_fa_blg_trash'         => 'Kôš',
			'tb_fa_blg_delete_btn'    => 'Odstrániť',
			'tb_fa_blg_load_more'     => 'Načítať viac',
			'tb_fa_blg_cat_count'     => '%s kategórií',
			'tb_fa_blg_cat_empty'     => 'Žiadne kategórie.',

			// Blog category modal
			'tb_fa_mbc_close'         => 'Zavrieť',
			'tb_fa_mbc_title'         => 'Kategória blogu',
			'tb_fa_mbc_name_label'    => 'Názov kategórie',
			'tb_fa_mbc_name_ph'       => 'Napr.: Tipy pre vodičov',
			'tb_fa_mbc_cancel'        => 'Zrušiť',
			'tb_fa_mbc_save'          => 'Uložiť',

			// Blog edit tab
			'tb_fa_be_new_title'      => 'Nový článok',
			'tb_fa_be_edit_title'     => 'Upraviť článok',
			'tb_fa_be_save_draft'     => 'Uložiť koncept',
			'tb_fa_be_publish_btn'    => 'Zverejniť článok',
			'tb_fa_be_section_basic'  => 'Základné informácie',
			'tb_fa_be_art_title'      => 'Názov článku',
			'tb_fa_be_art_title_ph'   => 'Zadajte názov článku…',
			'tb_fa_be_category'       => 'Kategória',
			'tb_fa_be_category_ph'    => '— Vyberte kategóriu —',
			'tb_fa_be_pub_date'       => 'Dátum zverejnenia',
			'tb_fa_be_content'        => 'Obsah článku',
			'tb_fa_be_photo'          => 'Fotografia',
			'tb_fa_be_photo_hint'     => 'Fotografia bude na obálke článku.',
			'tb_fa_be_delete_photo'   => 'Odstrániť fotografiu',
			'tb_fa_be_add_photo'      => 'Pridať fotografiu',
			'tb_fa_be_toast_draft'      => 'Uložené ako koncept',
			'tb_fa_be_toast_published'  => 'Článok bol zverejnený',
			'tb_fa_be_err_banner'       => 'Prosím, opravte chyby pred odoslaním.',
			'tb_fa_be_err_field_required' => 'Toto pole je povinné',
			'tb_fa_be_err_title'        => 'Prosím, zadajte názov článku.',
			'tb_fa_be_err_category'     => 'Toto pole je povinné.',
			'tb_fa_be_err_content'      => 'Článok je príliš krátky (minimálne 500 znakov).',
			'tb_fa_be_err_photo'        => 'Fotografia je povinná.',

			// Confirm modals
			'tb_fa_mdl_text'          => 'Naozaj chcete zamietnuť tento inzerát?',
			'tb_fa_mdl_cancel'        => 'Zrušiť',
			'tb_fa_mdl_confirm'       => 'Zamietnuť',
			'tb_fa_mdd_title'         => 'Odstrániť inzerát „%s“?',
			'tb_fa_mdd_text'          => 'Inzerát bude presunutý do koša. Obnoviť ho môžete cez WordPress admin.',
			'tb_fa_mdd_cancel'        => 'Zrušiť',
			'tb_fa_mdd_confirm'       => 'Odstrániť',
			'tb_fa_mbl_text'          => 'Naozaj chcete zablokovať tohto používateľa?',
			'tb_fa_mbl_cancel'        => 'Zrušiť',
			'tb_fa_mbl_confirm'       => 'Zablokovať',
			'tb_fa_mdc_text'          => 'Naozaj chcete odstrániť mesto „%s“?',
			'tb_fa_mdc_cancel'        => 'Zrušiť',
			'tb_fa_mdc_confirm'       => 'Odstrániť',
			'tb_fa_mdk_text'          => 'Naozaj chcete odstrániť kategóriu „%s“?',
			'tb_fa_mdk_cancel'        => 'Zrušiť',
			'tb_fa_mdk_confirm'       => 'Odstrániť',
			'tb_fa_mdp_title'         => 'Odstrániť článok?',
			'tb_fa_mdp_text'          => 'Naozaj chcete odstrániť článok „%s“?',
			'tb_fa_mdp_cancel'        => 'Zrušiť',
			'tb_fa_mdp_confirm'       => 'Odstrániť',
		];

		foreach ( $defaults as $name => $value ) {
			update_field( $name, $value, 'option' );
		}

		// Seed footer nav repeater columns with default Slovak links.
		self::seed_footer_nav_defaults();

		update_option( 'tb_options_seeded_v5', true );
	}

	/**
	 * Write default footer navigation repeater rows (always overwrites).
	 */
	private static function seed_footer_nav_defaults(): void {
		if ( ! function_exists( 'update_field' ) ) {
			return;
		}

		update_field(
			'tb_footer_nav_links',
			[
				[ 'label' => __( 'Domov', 'taxi-rental' ),       'url' => '/' ],
				[ 'label' => __( 'Katalóg áut', 'taxi-rental' ), 'url' => '/avto/' ],
				[ 'label' => __( 'Mestá', 'taxi-rental' ),       'url' => '/mista/' ],
				[ 'label' => __( 'O službe', 'taxi-rental' ),    'url' => '/pro-nas/' ],
				[ 'label' => __( 'Blog', 'taxi-rental' ),        'url' => '/blog/' ],
				[ 'label' => __( 'Kontakt', 'taxi-rental' ),     'url' => '/kontakt/' ],
			],
			'option'
		);

		update_field(
			'tb_footer_platform_links',
			[
				[ 'label' => __( 'Ako to funguje?', 'taxi-rental' ), 'url' => '/pro-nas/' ],
				[ 'label' => __( 'Pridať auto', 'taxi-rental' ),     'url' => '/cabinet/add-listing/' ],
				[ 'label' => __( 'Pre vlastníkov', 'taxi-rental' ),  'url' => '/login/?action=register&type=owner' ],
				[ 'label' => __( 'Pre vodičov', 'taxi-rental' ),     'url' => '/avto/' ],
			],
			'option'
		);

		update_field(
			'tb_footer_support_links',
			[
				[ 'label' => __( 'Kontakt', 'taxi-rental' ),                          'url' => '/kontakt/' ],
				[ 'label' => __( 'Časté otázky', 'taxi-rental' ),                    'url' => '/pro-nas/#faq' ],
				[ 'label' => 'Telegram',                                               'url' => 'https://t.me/taxibaza_sk' ],
				[ 'label' => __( 'Zásady ochrany osobných údajov', 'taxi-rental' ),  'url' => '/konfidenzialnist/' ],
				[ 'label' => __( 'Podmienky používania', 'taxi-rental' ),             'url' => '/umovy/' ],
			],
			'option'
		);
	}

	/**
	 * Seed demo blog posts in Slovak.
	 * Idempotent: skips any post whose slug already exists.
	 */
	public static function seed_blog_posts(): void {
		$posts = [
			[
				'slug'     => 'preukaz-vodica-taxi-ako-ziskat',
				'title'    => 'Ako získať preukaz vodiča taxi na Slovensku',
				'category' => 'Tipy pre vodičov',
				'date'     => '2026-05-15 10:00:00',
				'content'  => '<p>Práca v taxislužbe (cez aplikácie Uber, Bolt alebo v klasickej taxislužbe) na Slovensku je skvelý spôsob, ako získať hlavný alebo vedľajší príjem. No nestačí len stiahnuť si aplikáciu a sadnúť za volant. Podľa slovenského zákona musí každý vodič, ktorý vykonáva komerčnú prepravu osôb, mať špeciálny doklad — Preukaz vodiča vozidla taxislužby. V tomto článku si krok za krokom prejdeme, ako rýchlo a bez zbytočných starostí vybaviť tento doklad v roku 2026.</p>

<h2>Základné požiadavky na uchádzača</h2>
<p>Skôr než začnete zbierať potvrdenia, uistite sa, že spĺňate základné podmienky:</p>
<ul>
<li>Máte 18 rokov (niektoré služby alebo autopárky môžu vyžadovať 21 rokov, ale pre samotný preukaz stačí 18).</li>
<li>Ste platným držiteľom vodičského preukazu skupiny „B".</li>
<li>Máte čistý register trestov.</li>
<li>Ste plne spôsobilý na právne úkony.</li>
</ul>
<p>Ak toto všetko platí — pokračujeme so zbieraním dokumentov.</p>

<h2>Krok 1. Zdravotná spôsobilosť</h2>
<p>Prvú návštevu absolvujte u svojho praktického lekára (obvodný lekár). Lekár vykoná štandardné vyšetrenie a vydá potvrdenie, že ste zdravotne spôsobilý pracovať ako vodič taxislužby. Cena: približne 20–30 eur (závisí od zdravotného zariadenia).</p>
<p><strong>Dôležité:</strong> Potvrdenie je platné len 3 mesiace odo dňa vydania, preto s podaním žiadosti neotáľajte.</p>

<h2>Krok 2. Psychická spôsobilosť</h2>
<p>Toto je povinný krok pre všetkých profesionálnych vodičov. Je potrebné sa objednať k dopravnému alebo pracovnému psychológovi (dopravný psychológ). Test zvyčajne trvá 1–2 hodiny a preveruje vašu pozornosť, reakčný čas, odolnosť voči stresu a schopnosť adekvátne reagovať v krízových situáciách. Cena: približne 50–70 eur.</p>
<p><strong>Dôležité:</strong> Rovnako ako lekárske potvrdenie, aj tento doklad je platný len 3 mesiace.</p>

<h2>Krok 3. Príprava ďalších dokumentov</h2>
<p>Okrem lekárskeho a psychologického potvrdenia budete na podanie žiadosti potrebovať:</p>
<ul>
<li>Kópiu vodičského preukazu (obe strany).</li>
<li>Farebnú fotografiu (rozmer 3,5 × 4,5 cm, ako do pasu).</li>
<li>Výpis z registra trestov. Môžete ho získať na každej pobočke Slovenskej pošty. Poznámka: v žiadosti môžete udeliť súhlas, aby si úrad overenie bezúhonnosti zabezpečil sám, no ak výpis prinesiete osobne, vybavenie preukazu pôjde rýchlejšie.</li>
<li>Výpis z evidenčnej karty vodiča — potvrdzuje absenciu závažných priestupkov v cestnej premávke. Vydáva ho polícia v mieste trvalého bydliska.</li>
</ul>

<h2>Krok 4. Podanie žiadosti na Okresný úrad</h2>
<p>Keď sú všetky dokumenty pripravené, navštívte Klientske centrum pri Okresnom úrade v odbore cestnej dopravy príslušnom podľa vášho trvalého bydliska. Budete potrebovať:</p>
<ul>
<li>Vyplniť štandardné tlačivo žiadosti o vydanie preukazu.</li>
<li>Zaplatiť správny poplatok vo výške 50 eur. Platba prebieha cez špeciálny terminál (kiosk) priamo v stredisku (zakúpte si tzv. e-kolok).</li>
</ul>

<h2>Krok 5. Prevzatie preukazu</h2>
<p>Vybavenie žiadosti a výroba plastového preukazu zvyčajne trvá 1 až 3 týždne. Po prevzatí je Preukaz vodiča taxi platný 5 rokov.</p>
<p><strong>Stručný kontrolný zoznam:</strong></p>
<ul>
<li>Vodičský preukaz sk. B</li>
<li>Lekárske potvrdenie (Zdravotná spôsobilosť)</li>
<li>Psychologické potvrdenie (Psychotest)</li>
<li>Fotografia 3,5 × 4,5 cm</li>
<li>Výpis z registra trestov</li>
<li>50 eur správny poplatok</li>
</ul>

<h2>Čo robiť ďalej?</h2>
<p>S preukazom v ruke ste pripravení na prácu! Zostáva nájsť vhodný automobil. Na našej platforme si môžete prenajať spoľahlivé auto, ktoré má licenciu taxislužby a je plne pripravené na prácu v Uber alebo Bolt. <a href="/avto/">Prezrite si dostupné vozidlá na prenájom</a> alebo sa pripojte k našej <a href="https://t.me/taxibaza_sk">Telegram komunite vodičov</a>, kde vám pomôžu s akýmikoľvek otázkami!</p>',
			],
			[
				'slug'     => 'bolt-alebo-uber-2026',
				'title'    => 'Bolt alebo Uber v roku 2026: kde sa dá viac zarobiť?',
				'category' => 'Tipy pre vodičov',
				'date'     => '2026-05-12 10:00:00',
				'content'  => '<p>Otázka, ktorú si kladie každý vodič na Slovensku: na ktorej platforme sa jazdí výhodnejšie? Porovnali sme provízie, tarify a dopyt v Bolt a Uber k roku 2026, aby ste sa mohli rozhodnúť informovane.</p>

<h2>Provízie a tarify</h2>
<p>Bolt tradične udržiava o niečo nižšiu províziu pre vodičov a častejšie organizuje akcie so znížením provízií pre nováčikov. Uber kompenzuje vyššiu províziu stabilnejším tokom objednávok v špičkách a na letisku v Bratislave.</p>

<h2>Dopyt v mestách</h2>
<p>V Bratislave sú obe platformy zaťažené rovnomerne, zatiaľ čo v Košiciach a menších mestách Bolt výrazne dominuje. Ak plánujete pracovať mimo hlavného mesta — Bolt vám prinesie viac objednávok.</p>

<h2>Záver: jazdte na oboch</h2>
<p>Väčšina skúsených vodičov kombinuje obe platformy: držia obe aplikácie zapnuté a prijímajú výhodnejšie objednávky. Pri prenájme auta cez našu platformu sa uistite, že majiteľ dovoľuje jazdu na oboch službách — to je zvyčajne uvedené v inzeráte.</p>',
			],
			[
				'slug'     => 'pasivny-prijem-prenajom-auta-taxikarom',
				'title'    => 'Pasívny príjem: ako bezpečne prenajať svoje auto taxikárom',
				'category' => 'Pre vlastníkov',
				'date'     => '2026-05-10 10:00:00',
				'content'  => '<p>Váš automobil môže pre vás zarábať 300–500 eur mesačne čistého, kým ho nepoužívate. Povedáme vám, ako prenajať auto taxikárom bezpečne a bez zbytočných starostí.</p>

<h2>Správna zmluva — základ bezpečnosti</h2>
<p>Nikdy neodovzdávajte auto bez písomnej nájomnej zmluvy (zmluva o nájme). V zmluve zakotvite: výšku týždennej platby, zodpovednosť za pokuty a škody, postup pri technickej údržbe a podmienky ukončenia zmluvy.</p>

<h2>Depozit a poistenie</h2>
<p>Požiadajte o vratný depozit (zvyčajne 300–500 eur) — pokryje drobné škody a pokuty. Povinne si zriaďte havarijné poistenie (KASKO): pre auto pracujúce v taxislužbe to nie je voliteľná, ale nevyhnutná vec.</p>

<h2>Preverujte vodiča</h2>
<p>Požiadajte kandidáta o preukaz vodiča taxi, vodičský preukaz a potvrdenie o skúsenostiach na platformách. Stretnite sa osobne pred odovzdaním auta a odfotografujte stav vozidla.</p>

<h2>Zverejnite inzerát</h2>
<p>Zaregistrujte sa na platforme ako majiteľ a <a href="/cabinet/add-listing/">zverejnite inzerát</a> — je to bezplatné. Uveďte čestné podmienky, pridajte kvalitné fotografie a prvé záujmy od vodičov na seba nenechajú dlho čakať.</p>',
			],
			[
				'slug'     => 'nove-nastroje-pre-flotily',
				'title'    => 'Spúšťame nové nástroje pre flotily',
				'category' => 'Novinky',
				'date'     => '2026-05-05 10:00:00',
				'content'  => '<p>Veľká aktualizácia pre majiteľov autopárkov: správa desiatok vozidiel na platforme je teraz oveľa jednoduchšia.</p>

<h2>Čo je nové</h2>
<p>V kabinete flotily pribudli nástroje hromadnej správy inzerátov: duplikovanie karty auta, rýchla úprava cien pre viacero vozidiel naraz a súhrnná štatistika zobrazení celého parku.</p>

<h2>Pre koho je to určené</h2>
<p>Aktualizácia je dostupná všetkým účtom typu „flotila" (3 a viac áut) bezplatne. Ak máte účet majiteľa a váš park narástol — napíšte nám cez <a href="/kontakt/">kontaktný formulár</a> a prepneme typ účtu.</p>

<h2>Čo ďalej</h2>
<p>Ďalším krokom pripravujeme emailové oznámenia o nových žiadostiach od vodičov a rozšírenú analytiku. Sledujte novinky v našej <a href="https://t.me/taxibaza_sk">Telegram komunite</a>.</p>',
			],
			[
				'slug'     => 'top-5-avto-bolt-uber',
				'title'    => 'TOP 5 najvýhodnejších áut pre prácu v Bolt a Uber: hybrid, elektro alebo plyn?',
				'category' => 'Recenzie áut',
				'date'     => '2026-05-03 10:00:00',
				'content'  => '<p>Náklady na palivo sú hlavnou výdavkovou položkou vodiča taxislužby. Spočítali sme reálnu ekonomiku hybridov, elektromobilov a vozidiel na plyn v podmienkach slovenských miest a zostavili TOP 5 modelov pre prácu v Bolt a Uber v roku 2026.</p>

<h2>1. Toyota Corolla Hybrid</h2>
<p>Nespochybniteľný líder medzi taxikármi: spotreba 4–4,5 l v meste, legendárna spoľahlivosť a prístup do kategórie Comfort. Ideálna rovnováha ceny prenájmu a nákladov.</p>

<h2>2. Tesla Model 3</h2>
<p>Elektromobil otvára kategóriu Bolt Green s vyšším tarifom. Náklady na „tankovanie" sú dvoj- až trojnásobne nižšie ako benzín, no trasy plánujte s ohľadom na nabíjacie stanice.</p>

<h2>3. Škoda Octavia G-TEC (CNG)</h2>
<p>Zemný plyn zostáva na Slovensku najlacnejším palivom. Veľký kufor a priestor pre cestujúcich — výhoda pre kategóriu XL vo verzii kombi.</p>

<h2>4. Hyundai Ioniq Hybrid</h2>
<p>Priamy konkurent Corolly s ešte nižšou spotrebou na trase. Niečo drahší na servis, no veľmi komfortný na 10-hodinové zmeny.</p>

<h2>5. Volkswagen Passat 2.0 TDI</h2>
<p>Naftová klasika pre tých, ktorí veľa jazdia medzi mestami a na letisko. Spotreba 5 l na trase a prémiový priestor pre kategórie Comfort a Premium.</p>

<h2>Záver</h2>
<p>Pre mesto si zvoľte hybrid, pre Bolt Green — elektro, pre medzimiestne trasy — naftu. Všetky tieto modely sa pravidelne objavujú v <a href="/avto/">katalógu prenájmu</a> — vyberte auto vo svojom meste.</p>',
			],
			[
				'slug'     => 'recenzia-tesla-model-3-bolt-green',
				'title'    => 'Recenzia: Tesla Model 3 pre prácu v Bolt Green',
				'category' => 'Recenzie áut',
				'date'     => '2026-04-28 10:00:00',
				'content'  => '<p>Tesla Model 3 je najpopulárnejší elektromobil v kategórii Bolt Green na Slovensku. Rozobrali sme, koľko vodič na „Tesla-taxi" reálne zarobí a aké úskalia treba zvážiť.</p>

<h2>Prečo Bolt Green</h2>
<p>Kategória Green má tarif o 10–15 % vyšší ako štandardný Bolt a cestujúci si elektrické auto chętnie vyberajú. V Bratislave je dopyt po Green počas pracovných dní stabilne vysoký.</p>

<h2>Ekonomika za týždeň</h2>
<p>Nočné nabíjanie doma stojí približne 8–10 eur na 400 km dojazd — oproti 35–40 eurám benzínu na rovnakú vzdialenosť. Za celý pracovný týždeň úspora na palive dosahuje 120–150 eur, čo fakticky pokrýva tretinu prenájmu.</p>

<h2>Na čo si dať pozor</h2>
<p>Plánujte zmeny s ohľadom na nabíjanie: rýchla stanica doplní 80 % batérie za pol hodiny. V zime klesá dojazd o 20–25 %. Overte si, či prenájom zahŕňa nabíjaciu kartu — u časti majiteľov je to zahrnuté v týždennej cene.</p>

<h2>Záver</h2>
<p>Model 3 v prenájme sa oplatí vodičom s nájazdom od 1 000 km týždenne. Hľadajte dostupné elektromobily v <a href="/avto/">katalógu</a> vo svojom meste.</p>',
			],
		];

		foreach ( $posts as $post ) {
			// Skip if a post with this slug already exists.
			if ( null !== get_page_by_path( $post['slug'], OBJECT, 'post' ) ) {
				continue;
			}

			// Get or create the category.
			$cat_obj = get_term_by( 'name', $post['category'], 'category' );
			if ( $cat_obj ) {
				$cat_id = (int) $cat_obj->term_id;
			} else {
				$result = wp_insert_term( $post['category'], 'category' );
				$cat_id = is_wp_error( $result ) ? 0 : (int) $result['term_id'];
			}

			$cat_ids = $cat_id ? [ $cat_id ] : [];

			wp_insert_post(
				[
					'post_title'    => $post['title'],
					'post_name'     => $post['slug'],
					'post_content'  => $post['content'],
					'post_status'   => 'publish',
					'post_type'     => 'post',
					'post_date'     => $post['date'],
					'post_date_gmt' => $post['date'],
					'post_category' => $cat_ids,
				]
			);
		}
	}

	private static function seed_cities(): void {
		foreach ( self::CITIES as $city ) {
			if ( ! term_exists( $city, 'city' ) ) {
				wp_insert_term( $city, 'city' );
			}
		}
	}

	private static function seed_platforms(): void {
		foreach ( self::PLATFORMS as $platform ) {
			if ( ! term_exists( $platform, 'platform' ) ) {
				wp_insert_term( $platform, 'platform' );
			}
		}
	}

	private static function seed_ride_categories(): void {
		foreach ( self::RIDE_CATEGORIES as $parent_name => $children ) {
			$existing_parent = term_exists( $parent_name, 'ride_category' );

			if ( ! $existing_parent ) {
				$result           = wp_insert_term( $parent_name, 'ride_category' );
				$existing_parent  = is_wp_error( $result ) ? null : $result;
			}

			if ( ! $existing_parent || is_wp_error( $existing_parent ) ) {
				continue;
			}

			$parent_id = (int) ( is_array( $existing_parent ) ? $existing_parent['term_id'] : $existing_parent );

			foreach ( $children as $child ) {
				if ( ! term_exists( $child, 'ride_category', $parent_id ) ) {
					wp_insert_term( $child, 'ride_category', [ 'parent' => $parent_id ] );
				}
			}
		}
	}

	/**
	 * Seed car makes (parent) and models (children) into the car_make taxonomy.
	 * Idempotent — safe to run repeatedly. Has its own version flag so it can be
	 * re-run from the Tools page without touching the main seeder.
	 */
	public static function seed_car_makes(): void {
		if ( get_option( 'tb_makes_seeded_v1' ) ) {
			return;
		}

		foreach ( self::CAR_MAKES as $make_name => $models ) {
			$parent = term_exists( $make_name, 'car_make' );

			if ( ! $parent ) {
				$result = wp_insert_term( $make_name, 'car_make' );
				$parent = is_wp_error( $result ) ? null : $result;
			}

			if ( ! $parent || is_wp_error( $parent ) ) {
				continue;
			}

			$parent_id = (int) ( is_array( $parent ) ? $parent['term_id'] : $parent );

			foreach ( $models as $model ) {
				if ( ! term_exists( $model, 'car_make', $parent_id ) ) {
					wp_insert_term( $model, 'car_make', [ 'parent' => $parent_id ] );
				}
			}
		}

		update_option( 'tb_makes_seeded_v1', true );
	}

	/**
	 * Backfill the car_make taxonomy for listings created before it existed.
	 * Reads the existing `make` / `model` text meta, finds or creates matching
	 * terms (make = parent, model = child) and assigns them. The text meta is
	 * left intact as a display fallback. Idempotent via a version flag.
	 */
	public static function migrate_listing_makes(): void {
		if ( get_option( 'tb_makes_migrated_v1' ) ) {
			return;
		}

		$ids = get_posts(
			[
				'post_type'      => 'car_listing',
				'post_status'    => [ 'publish', 'pending', 'draft', 'rejected', 'archived' ],
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			]
		);

		foreach ( $ids as $post_id ) {
			$make  = trim( (string) get_post_meta( $post_id, 'make', true ) );
			$model = trim( (string) get_post_meta( $post_id, 'model', true ) );

			if ( '' === $make ) {
				continue;
			}

			$term_ids  = [];
			$parent_id = self::ensure_make_term( $make, 0 );
			if ( $parent_id ) {
				$term_ids[] = $parent_id;

				if ( '' !== $model ) {
					$child_id = self::ensure_make_term( $model, $parent_id );
					if ( $child_id ) {
						$term_ids[] = $child_id;
					}
				}
			}

			if ( $term_ids ) {
				wp_set_object_terms( $post_id, $term_ids, 'car_make' );
			}
		}

		update_option( 'tb_makes_migrated_v1', true );
	}

	/**
	 * Find or create a car_make term by name under a given parent (0 = top level).
	 *
	 * @return int Term ID, or 0 on failure.
	 */
	private static function ensure_make_term( string $name, int $parent_id ): int {
		$existing = term_exists( $name, 'car_make', $parent_id ?: null );
		if ( $existing ) {
			return (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
		}

		$args   = $parent_id ? [ 'parent' => $parent_id ] : [];
		$result = wp_insert_term( $name, 'car_make', $args );

		return is_wp_error( $result ) ? 0 : (int) $result['term_id'];
	}

	/**
	 * Update an existing post's title and content by its slug.
	 *
	 * Usage example (wp-admin → Tools admin action, WP-CLI eval, or seeder script):
	 *   \TaxiRental\Core\Seeder::update_post_by_slug( 'moj-clanok', 'Nový nadpis', '<p>Nový obsah</p>' );
	 *
	 * @param string $slug      post_name (URL slug) of the post to update.
	 * @param string $title     New post title.
	 * @param string $content   New post content (HTML; runs through wp_kses_post).
	 * @param string $post_type Post type to search. Defaults to 'post'.
	 * @return int|\WP_Error    Updated post ID on success, WP_Error on failure.
	 */
	public static function update_post_by_slug(
		string $slug,
		string $title,
		string $content,
		string $post_type = 'post'
	): int|\WP_Error {
		$post = get_page_by_path( $slug, OBJECT, $post_type );

		if ( ! $post instanceof \WP_Post ) {
			return new \WP_Error(
				'tb_post_not_found',
				sprintf( 'No %s found with slug "%s".', $post_type, $slug )
			);
		}

		return wp_update_post(
			[
				'ID'           => $post->ID,
				'post_title'   => sanitize_text_field( $title ),
				'post_content' => wp_kses_post( $content ),
			],
			true
		);
	}

	/**
	 * Seed default Slovak UI strings for the Add / Edit Listing page.
	 * Has its own version flag — runs independently of seed_options_defaults().
	 * Re-run by deleting option `tb_al_seeded_v1` in the DB, then clicking the button.
	 */
	public static function seed_add_listing_strings(): void {
		if ( ! function_exists( 'update_field' ) ) {
			return;
		}
		if ( get_option( 'tb_al_seeded_v2' ) ) {
			return;
		}

		$strings = [
			// Toasts / page title
			'tb_al_toast_saved'     => 'Zmeny uložené',
			'tb_al_toast_submitted' => 'Inzerát bol odoslaný na moderáciu',
			'tb_al_title_new'       => 'Nový inzerát',
			'tb_al_title_edit'      => 'Upraviť inzerát',

			// Action buttons
			'tb_al_btn_cancel'      => 'Zrušiť',
			'tb_al_btn_draft'       => 'Uložiť koncept',
			'tb_al_btn_publish'     => 'Zverejniť',
			'tb_al_btn_pub_listing' => 'Zverejniť inzerát',

			// Section: basic info
			'tb_al_sec_basic'       => 'Základné informácie',
			'tb_al_make'            => 'Značka auta',
			'tb_al_model'           => 'Model',
			'tb_al_year'            => 'Rok výroby',
			'tb_al_engine'          => 'Typ motora',
			'tb_al_select_ph'       => 'Vyberte prosím',
			'tb_al_trans'           => 'Prevodovka',
			'tb_al_consumption'     => 'Spotreba',
			'tb_al_consumption_ph'  => 'Napr. 8l/100km',

			// Section: photos
			'tb_al_sec_photos'      => 'Fotografie',
			'tb_al_photos_hint'     => 'Prvá fotografia bude na obálke inzerátu. Potiahnutím zmeňte poradie.',
			'tb_al_remove_photo'    => 'Odstrániť fotografiu',
			'tb_al_add_photo'       => 'Pridať fotografiu',

			// Section: rental terms
			'tb_al_sec_terms'       => 'Podmienky prenájmu',
			'tb_al_price'           => 'Cena / týždeň (€)',
			'tb_al_deposit'         => 'Depozit (€)',
			'tb_al_mileage'         => 'Limit najazdených km / mesiac (km)',
			'tb_al_mileage_ph'      => '0 — bez obmedzenia',
			'tb_al_min_term'        => 'Minimálna doba prenájmu',
			'tb_al_insurance'       => 'Poistenie zahrnuté',
			'tb_al_casco'           => 'KASKO',
			'tb_al_maintenance'     => 'Servis zahrnutý',
			'tb_al_toll'            => 'Platené cesty',

			// Section: location
			'tb_al_sec_location'    => 'Lokalita a kategórie',
			'tb_al_city'            => 'Mesto',

			// Section: extra
			'tb_al_sec_extra'       => 'Doplňujúce informácie',
			'tb_al_description'     => 'Popis inzerátu',
			'tb_al_desc_ph'         => 'Zamyslite sa, čo by ste chceli vedieť z inzerátu. Doplňte to do popisu.',

			// Tips sidebar
			'tb_al_tips_aria'       => 'Tipy',
			'tb_al_tips_title'      => 'Tipy pre lepší inzerát',
			'tb_al_tip_photos'      => 'Pridajte aspoň 4 kvalitné fotografie auta',
			'tb_al_tip_desc'        => 'Podrobne opíšte stav a podmienky prenájmu',
			'tb_al_tip_location'    => 'Uveďte presnú lokalitu',
			'tb_al_tip_contacts'    => 'Presvedčte sa, že kontakty sú aktuálne',

			// Exit-without-save modal
			'tb_al_exit_title'      => 'Naozaj chcete odísť bez uloženia?',
			'tb_al_exit_text'       => 'Všetky neuložené zmeny budú stratené.',
			'tb_al_exit_stay'       => 'Pokračovať',
			'tb_al_exit_save'       => 'Uložiť koncept',

			// Engine type choice labels
			'tb_al_engine_petrol'   => 'Benzín',
			'tb_al_engine_diesel'   => 'Nafta',
			'tb_al_engine_electric' => 'Elektrický',
			'tb_al_engine_hybrid'   => 'Hybrid',
			'tb_al_engine_lpg'      => 'LPG',

			// Transmission choice labels
			'tb_al_trans_automatic' => 'Automatická',
			'tb_al_trans_manual'    => 'Manuálna',
		];

		foreach ( $strings as $key => $value ) {
			update_field( $key, $value, 'option' );
		}

		update_option( 'tb_al_seeded_v2', true );
	}

	/**
	 * Seed default Slovak email templates into ACF options.
	 * Has its own version flag — runs independently of seed_options_defaults().
	 * Safe to call multiple times: skips if already seeded.
	 * Re-run via WP-Admin → TaxiBaza → Nástroje → «Zapustyty sid» after deleting
	 * the option `tb_email_templates_seeded_v1` if you need to reset to defaults.
	 */
	public static function seed_email_templates(): void {
		if ( ! function_exists( 'update_field' ) ) {
			return;
		}
		if ( get_option( 'tb_email_templates_seeded_v1' ) ) {
			return;
		}

		$templates = [
			'tb_email_welcome_subject' => '[TaxiBaza.sk] Vitajte, {{name}}!',
			'tb_email_welcome_body'    =>
				"Vítajte, {{name}}!\n\n" .
				"Váš účet na TaxiBaza.sk bol úspešne vytvorený.\n\n" .
				"Čo ďalej:\n" .
				"— Pridajte svoj prvý inzerát: {{add_listing_url}}\n" .
				"— Spravujte inzeráty v kabinete: {{cabinet_url}}\n\n" .
				"Po kontrole moderátorom sa inzerát zobrazí v katalógu.\n\n" .
				'S pozdravom, tím TaxiBaza.sk',

			'tb_email_pending_admin_subject' => '[TaxiBaza.sk] Nový inzerát na moderáciu: {{title}}',
			'tb_email_pending_admin_body'    =>
				"Inzerát «{{title}}» od {{owner_name}} čaká na kontrolu.\n\n" .
				'Moderácia: {{moderation_url}}',

			'tb_email_pending_owner_subject' => '[TaxiBaza.sk] Inzerát «{{title}}» je na moderácii',
			'tb_email_pending_owner_body'    =>
				"Vitajte, {{name}}!\n\n" .
				"Váš inzerát «{{title}}» bol prijatý a odoslaný na moderáciu.\n" .
				"Kontrola zvyčajne trvá do 24 hodín — informujeme vás o výsledku.\n\n" .
				'S pozdravom, tím TaxiBaza.sk',

			'tb_email_published_subject' => '[TaxiBaza.sk] Inzerát «{{title}}» bol zverejnený',
			'tb_email_published_body'    =>
				"Vitajte, {{name}}!\n\n" .
				"Váš inzerát «{{title}}» prešiel moderáciou a bol zverejnený v katalógu:\n" .
				"{{listing_url}}\n\n" .
				'S pozdravom, tím TaxiBaza.sk',

			'tb_email_rejected_subject' => '[TaxiBaza.sk] Inzerát «{{title}}» bol zamietnutý',
			'tb_email_rejected_body'    =>
				"Dobrý deň, {{name}}.\n\n" .
				"Bohužiaľ, váš inzerát «{{title}}» neprešiel moderáciou.\n\n" .
				"Dôvod: {{reason}}\n\n" .
				"Inzerát môžete opraviť a odoslať znova:\n{{edit_url}}\n\n" .
				'S pozdravom, tím TaxiBaza.sk',

			'tb_email_contact_owner_subject' => '[TaxiBaza.sk] Žiadosť o prenájom: {{title}}',
			'tb_email_contact_owner_body'    =>
				"Nová žiadosť o prenájom vášho auta na TaxiBaza.sk:\n{{listing_url}}\n\n" .
				"Meno: {{first_name}} {{last_name}}\n" .
				"Email: {{email}}\n" .
				"Telefón: {{phone}}\n" .
				"Vodičská prax: {{experience}}\n" .
				"Doba prenájmu: {{term}}\n" .
				"Požadovaný dátum začiatku: {{start_date}}\n" .
				"{{checks}}\n\n" .
				"Správa:\n{{message}}",

			'tb_email_confirm_subject' => '[{{site_name}}] Potvrďte váš email',
			'tb_email_confirm_body'    =>
				"Dobrý deň {{name}},\n\n" .
				"Ďakujeme za registráciu na TaxiBaza.sk.\n\n" .
				"Pre aktiváciu účtu kliknite na odkaz nižšie:\n{{confirm_url}}\n\n" .
				"Odkaz platí 48 hodín.\n\n" .
				"Ak ste sa neregistrovali na TaxiBaza.sk, ignorujte tento email.\n\n" .
				'S pozdravom, tím TaxiBaza.sk',

			'tb_email_recover_subject' => '[{{site_name}}] Obnovenie hesla',
			'tb_email_recover_body'    =>
				"Dobrý deň {{name}},\n\n" .
				"Bola požiadaná obnova hesla vášho účtu.\n\n" .
				"Kliknite na odkaz nižšie pre obnovu hesla:\n{{reset_url}}\n\n" .
				"Ak ste o obnovu nežiadali, tento email ignorujte.\n\n" .
				'S pozdravom, tím TaxiBaza.sk',

			'tb_email_new_user_admin_subject' => '[TaxiBaza.sk] Nový používateľ: {{name}}',
			'tb_email_new_user_admin_body'    =>
				"Na TaxiBaza.sk sa zaregistroval nový používateľ.\n\n" .
				"Meno: {{name}}\n" .
				"Email: {{email}}\n" .
				"Typ účtu: {{role}}\n\n" .
				'Správa používateľa: {{admin_url}}',
		];

		foreach ( $templates as $key => $value ) {
			update_field( $key, $value, 'option' );
			// Register with WPML String Translation so translators can find and
			// translate each subject/body string in WPML → String Translation.
			do_action( 'wpml_register_single_string', 'taxi-rental-options', $key, $value );
		}

		update_option( 'tb_email_templates_seeded_v1', true );
	}

	/**
	 * Seed default Slovak strings for the registration account-type dropdown
	 * (three roles: owner / fleet / driver) — each with a title, a description
	 * shown inside the dropdown, and a short label shown once selected.
	 * Has its own version flag; safe to re-run.
	 */
	public static function seed_role_strings(): void {
		if ( ! function_exists( 'update_field' ) ) {
			return;
		}
		if ( get_option( 'tb_roles_seeded_v1' ) ) {
			return;
		}

		$strings = [
			// Registration subtitle now covers all three roles (was owners/fleets only).
			'tb_auth_reg_subtitle'    => 'Zaregistrujte sa ako vlastník, flotila alebo vodič.',

			// Dropdown placeholder + validation
			'tb_auth_type_ph'         => 'Vyberte prosím',
			'tb_auth_type_error'      => 'Prosím, vyberte typ používateľa',

			// Google login + post-login account-type screen
			'tb_auth_google_btn'      => 'Pokračovať cez Google',
			'tb_auth_choose_title'    => 'Vyberte typ účtu',
			'tb_auth_choose_subtitle' => 'Zostáva posledný krok — vyberte, ako budete platformu používať.',
			'tb_auth_choose_submit'   => 'Pokračovať',

			// Owner (car_owner)
			'tb_auth_owner_title'     => 'Vlastník auta',
			'tb_auth_owner_desc'      => 'Som vlastník, plánujem prenajať svoje auto',
			'tb_auth_owner_short'     => 'Vlastník',

			// Fleet
			'tb_auth_fleet_title'     => 'Flotila / firma',
			'tb_auth_fleet_desc'      => 'Firma s viacerými autami na prenájom',
			'tb_auth_fleet_short'     => 'Flotila',

			// Driver
			'tb_auth_driver_title'    => 'Vodič',
			'tb_auth_driver_desc'     => 'Som vodič, chcem si prenajať auto',
			'tb_auth_driver_short'    => 'Vodič',

			// Driver cabinet
			'tb_cab_nav_requests'      => 'Moje žiadosti',
			'tb_cab_view_listings_btn' => 'Zobraziť inzeráty',
			'tb_cab_req_write_btn'     => 'Napísať vlastníkovi',
			'tb_cab_req_delete_btn'    => 'Odstrániť',
			'tb_cab_req_delete_confirm' => 'Odstrániť túto žiadosť?',
			'tb_cab_req_empty_title'   => 'Nič sa nenašlo',
			'tb_cab_req_empty_desc'    => 'Zdá sa, že ste ešte nepodali žiadnu žiadosť o prenájom. Teraz je ideálny čas to zmeniť.',
			'tb_cab_req_soon'          => 'Čoskoro',

			// Driver settings — blacklist stub
			'tb_cab_bl_title'          => 'Čierna listina',
			'tb_cab_bl_restore_all'    => 'Obnoviť všetkých',
			'tb_cab_bl_empty'          => 'Zatiaľ nemáte žiadnych blokovaných používateľov. Zoznam sa sprístupní po spustení správ.',

			// Profile — account type label for drivers
			'tb_cab_profile_type_driver' => 'Vodič',
		];

		foreach ( $strings as $key => $value ) {
			update_field( $key, $value, 'option' );
			do_action( 'wpml_register_single_string', 'taxi-rental-options', $key, $value );
		}

		update_option( 'tb_roles_seeded_v1', true );
	}
}
