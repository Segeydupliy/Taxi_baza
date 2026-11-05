<?php
/**
 * Site content settings — ACF options page.
 *
 * Registers the "Контент сайту" options page that holds globally reusable
 * content: Telegram community URL, support email, CTA band texts, FAQ items
 * and footer copyright. Field definitions live in acf-json/.
 *
 * @package TaxiRental\Core
 */

namespace TaxiRental\Core;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACF options page bootstrap.
 */
class Content {

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_action( 'acf/init', [ self::class, 'register_options_page' ] );
		add_action( 'acf/init', [ self::class, 'register_sub_pages' ], 20 );
		add_action( 'acf/init', [ self::class, 'register_email_fields' ], 30 );
		add_action( 'acf/init', [ self::class, 'register_add_listing_fields' ], 35 );
	}

	/**
	 * Register the global content options page under the TaxiBaza admin menu.
	 */
	public static function register_options_page(): void {
		if ( ! \function_exists( 'acf_add_options_page' ) ) {
			return;
		}

		acf_add_options_page(
			[
				'page_title'  => __( 'Site Content', 'taxi-rental' ),
				'menu_title'  => __( 'Site Content', 'taxi-rental' ),
				'menu_slug'   => 'tb-content',
				'parent_slug' => 'taxibaza',
				'capability'  => 'manage_options',
				'redirect'    => false,
				'autoload'    => true,
			]
		);
	}

	/**
	 * Register sub-pages under "Контент сайту" for each UI section.
	 * Priority 20 ensures the parent page is registered first.
	 */
	public static function register_sub_pages(): void {
		if ( ! \function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		$sub_pages = [
			[
				'page_title'  => __( 'Catalog', 'taxi-rental' ),
				'menu_title'  => __( 'Catalog', 'taxi-rental' ),
				'menu_slug'   => 'tb-catalog',
				'parent_slug' => 'taxibaza',
				'capability'  => 'manage_options',
				'autoload'    => true,
			],
			[
				'page_title'  => __( 'Car Card', 'taxi-rental' ),
				'menu_title'  => __( 'Car Card', 'taxi-rental' ),
				'menu_slug'   => 'tb-single',
				'parent_slug' => 'taxibaza',
				'capability'  => 'manage_options',
				'autoload'    => true,
			],
			[
				'page_title'  => __( 'Authorization', 'taxi-rental' ),
				'menu_title'  => __( 'Authorization', 'taxi-rental' ),
				'menu_slug'   => 'tb-auth',
				'parent_slug' => 'taxibaza',
				'capability'  => 'manage_options',
				'autoload'    => true,
			],
			[
				'page_title'  => __( 'Cabinet', 'taxi-rental' ),
				'menu_title'  => __( 'Cabinet', 'taxi-rental' ),
				'menu_slug'   => 'tb-cabinet',
				'parent_slug' => 'taxibaza',
				'capability'  => 'manage_options',
				'autoload'    => true,
			],
			[
				'page_title'  => __( '404 Page', 'taxi-rental' ),
				'menu_title'  => __( '404 Page', 'taxi-rental' ),
				'menu_slug'   => 'tb-404',
				'parent_slug' => 'taxibaza',
				'capability'  => 'manage_options',
				'autoload'    => true,
			],
			[
				'page_title'  => __( 'Blog', 'taxi-rental' ),
				'menu_title'  => __( 'Blog', 'taxi-rental' ),
				'menu_slug'   => 'tb-blog',
				'parent_slug' => 'taxibaza',
				'capability'  => 'manage_options',
				'autoload'    => true,
			],
			[
				'page_title'  => 'Front Admin UI',
				'menu_title'  => 'Front Admin UI',
				'menu_slug'   => 'tb-front-admin',
				'parent_slug' => 'taxibaza',
				'capability'  => 'manage_options',
				'autoload'    => true,
			],
			[
				'page_title'  => 'Email Templates',
				'menu_title'  => 'Email Templates',
				'menu_slug'   => 'tb-email-templates',
				'parent_slug' => 'taxibaza',
				'capability'  => 'manage_options',
				'autoload'    => true,
			],
			[
				'page_title'  => 'Add Listing',
				'menu_title'  => 'Add Listing',
				'menu_slug'   => 'tb-add-listing',
				'parent_slug' => 'taxibaza',
				'capability'  => 'manage_options',
				'autoload'    => true,
			],
		];

		foreach ( $sub_pages as $args ) {
			acf_add_options_sub_page( $args );
		}
	}

	/**
	 * Register email template fields as a local ACF field group on the Email-šablóny options page.
	 * Admin edits subject + body for each transactional email type.
	 * Placeholders ({{name}}, {{title}}, …) are listed in each field's instructions.
	 */
	public static function register_email_fields(): void {
		if ( ! \function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$fields = [];
		$order  = 10;

		$templates = [
			'welcome' => [
				'label'   => 'Vitajte (vlastník / flotila)',
				'subject' => 'tb_email_welcome_subject',
				'body'    => 'tb_email_welcome_body',
				'vars'    => '{{name}}, {{cabinet_url}}, {{add_listing_url}}',
			],
			'pending_admin' => [
				'label'   => 'Nový inzerát na moderáciu → admin',
				'subject' => 'tb_email_pending_admin_subject',
				'body'    => 'tb_email_pending_admin_body',
				'vars'    => '{{title}}, {{owner_name}}, {{moderation_url}}',
			],
			'pending_owner' => [
				'label'   => 'Inzerát v moderácii → vlastník',
				'subject' => 'tb_email_pending_owner_subject',
				'body'    => 'tb_email_pending_owner_body',
				'vars'    => '{{name}}, {{title}}',
			],
			'published' => [
				'label'   => 'Inzerát zverejnený → vlastník',
				'subject' => 'tb_email_published_subject',
				'body'    => 'tb_email_published_body',
				'vars'    => '{{name}}, {{title}}, {{listing_url}}',
			],
			'rejected' => [
				'label'   => 'Inzerát zamietnutý → vlastník',
				'subject' => 'tb_email_rejected_subject',
				'body'    => 'tb_email_rejected_body',
				'vars'    => '{{name}}, {{title}}, {{reason}}, {{edit_url}}',
			],
			'contact_owner' => [
				'label'   => 'Žiadosť o prenájom → vlastník',
				'subject' => 'tb_email_contact_owner_subject',
				'body'    => 'tb_email_contact_owner_body',
				'vars'    => '{{title}}, {{listing_url}}, {{first_name}}, {{last_name}}, {{email}}, {{phone}}, {{experience}}, {{term}}, {{start_date}}, {{checks}}, {{message}}',
			],
			'confirm' => [
				'label'   => 'Potvrdenie emailu → používateľ',
				'subject' => 'tb_email_confirm_subject',
				'body'    => 'tb_email_confirm_body',
				'vars'    => '{{site_name}}, {{name}}, {{confirm_url}}',
			],
			'recover' => [
				'label'   => 'Obnova hesla → používateľ',
				'subject' => 'tb_email_recover_subject',
				'body'    => 'tb_email_recover_body',
				'vars'    => '{{site_name}}, {{name}}, {{reset_url}}',
			],
			'new_user_admin' => [
				'label'   => 'Nový používateľ → admin',
				'subject' => 'tb_email_new_user_admin_subject',
				'body'    => 'tb_email_new_user_admin_body',
				'vars'    => '{{name}}, {{email}}, {{role}}, {{admin_url}}',
			],
		];

		foreach ( $templates as $tmpl ) {
			$fields[] = [
				'key'   => 'field_tab_' . $tmpl['subject'],
				'label' => $tmpl['label'],
				'type'  => 'tab',
				'order' => $order,
			];
			$order   += 10;

			$fields[] = [
				'key'          => 'field_' . $tmpl['subject'],
				'label'        => 'Predmet (subject)',
				'name'         => $tmpl['subject'],
				'type'         => 'text',
				'instructions' => 'Dostupné premenné: ' . $tmpl['vars'],
				'order'        => $order,
			];
			$order   += 10;

			$fields[] = [
				'key'          => 'field_' . $tmpl['body'],
				'label'        => 'Telo správy (body)',
				'name'         => $tmpl['body'],
				'type'         => 'textarea',
				'rows'         => 10,
				'instructions' => 'Dostupné premenné: ' . $tmpl['vars'],
				'order'        => $order,
			];
			$order   += 10;
		}

		acf_add_local_field_group(
			[
				'key'      => 'group_tb_email_templates',
				'title'    => 'Email-šablóny',
				'fields'   => $fields,
				'location' => [
					[
						[
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => 'tb-email-templates',
						],
					],
				],
				'active' => true,
			]
		);
	}

	/**
	 * Register ACF field group for the Add / Edit Listing page strings.
	 * All fields are text inputs stored as ACF options (tb_al_* keys).
	 */
	public static function register_add_listing_fields(): void {
		if ( ! \function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$sections = [
			'tb_al_tab_page'    => [
				'label'  => 'Страница / кнопки',
				'fields' => [
					'tb_al_toast_saved'     => 'Тост: Змены збережено',
					'tb_al_toast_submitted' => 'Тост: Оголошення відправлено',
					'tb_al_title_new'       => 'Заголовок: нове оголошення',
					'tb_al_title_edit'      => 'Заголовок: редагувати',
					'tb_al_btn_draft'       => 'Кнопка: зберегти чернетку',
					'tb_al_btn_publish'     => 'Кнопка: опублікувати (коротко)',
					'tb_al_btn_pub_listing' => 'Кнопка: опублікувати оголошення',
				],
			],
			'tb_al_tab_basic'   => [
				'label'  => 'Секція: Основна інформація',
				'fields' => [
					'tb_al_sec_basic'      => 'Заголовок секції',
					'tb_al_make'           => 'Марка авто',
					'tb_al_model'          => 'Модель',
					'tb_al_year'           => 'Рік випуску',
					'tb_al_engine'         => 'Тип двигуна',
					'tb_al_select_ph'      => 'Плейсхолдер select',
					'tb_al_trans'          => 'КПП',
					'tb_al_consumption'    => 'Розхід',
					'tb_al_consumption_ph' => 'Плейсхолдер розходу',
				],
			],
			'tb_al_tab_photos'  => [
				'label'  => 'Секція: Фото',
				'fields' => [
					'tb_al_sec_photos'   => 'Заголовок секції',
					'tb_al_photos_hint'  => 'Підказка',
					'tb_al_remove_photo' => 'Кнопка: видалити фото',
					'tb_al_add_photo'    => 'Кнопка: додати фото',
				],
			],
			'tb_al_tab_terms'   => [
				'label'  => 'Секція: Умови оренди',
				'fields' => [
					'tb_al_sec_terms'   => 'Заголовок секції',
					'tb_al_price'       => 'Ціна / тиждень',
					'tb_al_deposit'     => 'Депозит',
					'tb_al_mileage'     => 'Ліміт пробігу',
					'tb_al_mileage_ph'  => 'Плейсхолдер пробігу',
					'tb_al_min_term'    => 'Мінімальний термін',
					'tb_al_insurance'   => 'Страховка включена',
					'tb_al_casco'       => 'КАСКО',
					'tb_al_maintenance' => 'ТО включено',
					'tb_al_toll'        => 'Платні дороги',
				],
			],
			'tb_al_tab_location' => [
				'label'  => 'Секція: Локація',
				'fields' => [
					'tb_al_sec_location' => 'Заголовок секції',
					'tb_al_city'         => 'Місто',
				],
			],
			'tb_al_tab_extra'   => [
				'label'  => 'Секція: Додаткова інформація',
				'fields' => [
					'tb_al_sec_extra'   => 'Заголовок секції',
					'tb_al_description' => 'Опис оголошення',
					'tb_al_desc_ph'     => 'Плейсхолдер опису',
				],
			],
			'tb_al_tab_tips'    => [
				'label'  => 'Поради (sidebar)',
				'fields' => [
					'tb_al_tips_aria'    => 'aria-label блоку',
					'tb_al_tips_title'   => 'Заголовок блоку',
					'tb_al_tip_photos'   => 'Порада 1',
					'tb_al_tip_desc'     => 'Порада 2',
					'tb_al_tip_location' => 'Порада 3',
					'tb_al_tip_contacts' => 'Порада 4',
				],
			],
			'tb_al_tab_exit'    => [
				'label'  => 'Модал: вихід без збереження',
				'fields' => [
					'tb_al_exit_title' => 'Заголовок',
					'tb_al_exit_text'  => 'Текст',
					'tb_al_exit_stay'  => 'Кнопка: продовжити',
					'tb_al_exit_save'  => 'Кнопка: зберегти чернетку',
				],
			],
			'tb_al_tab_choices' => [
				'label'  => 'Výbery (engine / prevodovka)',
				'fields' => [
					'tb_al_engine_petrol'   => 'Motor: Benzín',
					'tb_al_engine_diesel'   => 'Motor: Nafta',
					'tb_al_engine_electric' => 'Motor: Elektrický',
					'tb_al_engine_hybrid'   => 'Motor: Hybrid',
					'tb_al_engine_lpg'      => 'Motor: LPG',
					'tb_al_trans_automatic' => 'Prevodovka: Automatická',
					'tb_al_trans_manual'    => 'Prevodovka: Manuálna',
				],
			],
		];

		$fields = [];
		$order  = 10;

		foreach ( $sections as $tab_key => $section ) {
			$fields[] = [
				'key'   => 'field_tab_' . $tab_key,
				'label' => $section['label'],
				'type'  => 'tab',
				'order' => $order,
			];
			$order   += 10;

			foreach ( $section['fields'] as $field_name => $field_label ) {
				$fields[] = [
					'key'   => 'field_' . $field_name,
					'label' => $field_label,
					'name'  => $field_name,
					'type'  => 'text',
					'order' => $order,
				];
				$order   += 10;
			}
		}

		acf_add_local_field_group(
			[
				'key'      => 'group_tb_add_listing',
				'title'    => 'Pridanie inzerátu — texty',
				'fields'   => $fields,
				'location' => [
					[
						[
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => 'tb-add-listing',
						],
					],
				],
				'active' => true,
			]
		);
	}

}
