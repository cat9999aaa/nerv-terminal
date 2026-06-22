<?php
/**
 * Early NERV CONTROL placeholder.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'nerv_core_register_admin_page' );
function nerv_core_register_admin_page(): void {
	add_menu_page(
		__( 'NERV Theme · Control', 'nerv-core' ),
		__( 'NERV Theme · Control', 'nerv-core' ),
		'manage_options',
		'nerv-control',
		'nerv_core_render_admin_page',
		'dashicons-admin-appearance',
		58
	);
}

add_action( 'admin_enqueue_scripts', 'nerv_core_enqueue_admin_control_assets' );
function nerv_core_enqueue_admin_control_assets( string $hook_suffix ): void {
	if ( 'toplevel_page_nerv-control' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_script(
		'nerv-core-admin-control',
		NERV_CORE_URL . 'assets/js/admin-control.js',
		array( 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n' ),
		NERV_CORE_VERSION . '-' . (string) filemtime( NERV_CORE_DIR . 'assets/js/admin-control.js' ),
		true
	);

	wp_set_script_translations( 'nerv-core-admin-control', 'nerv-core', NERV_CORE_DIR . 'languages' );
	if ( function_exists( 'nerv_core_zh_cn_js_locale_data' ) && nerv_core_should_use_zh_cn_fallback() ) {
		wp_add_inline_script(
			'nerv-core-admin-control',
			'wp.i18n.setLocaleData(' . wp_json_encode( nerv_core_zh_cn_js_locale_data(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ', "nerv-core");',
			'before'
		);
	}

	wp_enqueue_style(
		'nerv-core-admin-control',
		NERV_CORE_URL . 'assets/css/admin-control.css',
		array( 'wp-components' ),
		NERV_CORE_VERSION . '-' . (string) filemtime( NERV_CORE_DIR . 'assets/css/admin-control.css' )
	);

	wp_localize_script(
		'nerv-core-admin-control',
		'nervCoreControl',
		array(
			'restPath'       => '/nerv-core/v1/control-dashboard',
			'brandPath'      => '/nerv-core/v1/control-brand',
			'seoPath'        => '/nerv-core/v1/control-seo',
			'panelsPath'     => '/nerv-core/v1/control-panels',
			'aiServicesPath' => '/nerv-core/v1/control-ai-services',
			'articlesPath'   => '/nerv-core/v1/control-articles',
			'mobilePath'     => '/nerv-core/v1/control-mobile',
			'socialPath'     => '/nerv-core/v1/control-social',
				'geoPath'        => '/nerv-core/v1/control-geo',
				'effectsPath'    => '/nerv-core/v1/control-effects',
				'appearancePath' => '/nerv-core/v1/control-appearance',
				'partnersPath'   => '/nerv-core/v1/control-partners',
			'aiPolicyPath'   => '/nerv-core/v1/control-ai-policy-generate',
			'indexnowPath'   => '/nerv-core/v1/control-indexnow-test',
			'partnerTestPath'=> '/nerv-core/v1/control-partner-health-test',
			'toolsActionPath'=> '/nerv-core/v1/control-tools-action',
			'nonce'          => wp_create_nonce( 'wp_rest' ),
		)
	);
}

add_action( 'rest_api_init', 'nerv_core_register_control_rest' );
function nerv_core_register_control_rest(): void {
	register_rest_route(
		'nerv-core/v1',
		'/control-dashboard',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'nerv_core_rest_control_dashboard',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-ai-services',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_ai_services_save',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-brand',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_brand_save',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-seo',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_seo_save',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-panels',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_panels_save',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-articles',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_articles_save',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-mobile',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_mobile_save',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-social',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_social_save',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-geo',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_geo_save',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-effects',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_effects_save',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-appearance',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_appearance_save',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-partners',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_partners_save',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-ai-policy-generate',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_ai_policy_generate',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-indexnow-test',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_indexnow_test',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-partner-health-test',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_partner_health_test',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/control-tools-action',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_control_tools_action',
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		)
	);
}

function nerv_core_rest_control_mobile_save( WP_REST_Request $request ): WP_REST_Response {
	if ( ! function_exists( 'nerv_terminal_mobile_sanitize_options' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'Mobile app settings are unavailable.', 'nerv-core' ) ), 500 );
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	update_option(
		'nerv_terminal_mobile_options',
		nerv_terminal_mobile_sanitize_options(
			array(
				'enabled'       => ! empty( $params['enabled'] ),
				'more_enabled'  => ! empty( $params['moreEnabled'] ),
				'more_sections' => is_array( $params['moreSections'] ?? null ) ? $params['moreSections'] : array(),
				'tabs'          => is_array( $params['tabs'] ?? null ) ? $params['tabs'] : array(),
			)
		),
		false
	);

	return new WP_REST_Response(
		array(
			'message'   => __( 'Mobile App settings saved.', 'nerv-core' ),
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_social_save( WP_REST_Request $request ): WP_REST_Response {
	if ( ! function_exists( 'nerv_core_social_sanitize_options' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'Social settings are unavailable.', 'nerv-core' ) ), 500 );
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	update_option(
		'nerv_core_social_options',
		nerv_core_social_sanitize_options(
			array(
				'enabled'      => ! empty( $params['enabled'] ),
				'open_new_tab' => ! empty( $params['openNewTab'] ),
				'links'        => is_array( $params['links'] ?? null ) ? $params['links'] : array(),
			)
		),
		false
	);

	return new WP_REST_Response(
		array(
			'message'   => __( 'Social settings saved.', 'nerv-core' ),
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_dashboard(): WP_REST_Response {
	return new WP_REST_Response( nerv_core_control_dashboard_data() );
}

function nerv_core_rest_control_ai_services_save( WP_REST_Request $request ): WP_REST_Response {
	if ( ! function_exists( 'nerv_core_cover_sanitize_options' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'AI services settings are unavailable.', 'nerv-core' ) ), 500 );
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$input = array(
		'endpoint'        => (string) ( $params['endpoint'] ?? '' ),
		'api_key'         => (string) ( $params['apiKey'] ?? '' ),
		'model'           => (string) ( $params['model'] ?? '' ),
		'prompt_template' => (string) ( $params['promptTemplate'] ?? '' ),
		'auto_generate'   => ! empty( $params['autoGenerate'] ),
		'key_points_auto' => ! empty( $params['keyPointsAuto'] ),
		'dry_run'         => ! empty( $params['dryRun'] ),
	);

	update_option( 'nerv_core_cover_options', nerv_core_cover_sanitize_options( $input ) );

	return new WP_REST_Response(
		array(
			'message'   => __( 'AI Services settings saved.', 'nerv-core' ),
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_brand_save( WP_REST_Request $request ): WP_REST_Response {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$current = get_option( 'nerv_terminal_strings', array() );
	if ( ! is_array( $current ) ) {
		$current = array();
	}

	$fields = array(
		'brand_title'     => (string) ( $params['brandTitle'] ?? '' ),
		'brand_subtitle'  => (string) ( $params['brandSubtitle'] ?? '' ),
		'brand_mark'      => (string) ( $params['brandMark'] ?? '' ),
		'clock_label'     => (string) ( $params['clockLabel'] ?? '' ),
		'clock_timezone'  => (string) ( $params['clockTimezone'] ?? '' ),
		'active_label'    => (string) ( $params['activeLabel'] ?? '' ),
		'pwa_name'        => (string) ( $params['pwaName'] ?? '' ),
		'pwa_short_name'  => (string) ( $params['pwaShortName'] ?? '' ),
		'pwa_theme_color' => (string) ( $params['themeColor'] ?? '' ),
		'brand_logo_id'   => (string) absint( $params['brandLogoId'] ?? 0 ),
		'brand_logo_fit'  => (string) ( $params['brandLogoFit'] ?? 'contain' ),
		'brand_logo_focus_x' => (string) absint( $params['brandLogoFocusX'] ?? 50 ),
		'brand_logo_focus_y' => (string) absint( $params['brandLogoFocusY'] ?? 50 ),
		'pwa_icon_id'     => (string) absint( $params['pwaIconId'] ?? 0 ),
		'pwa_icon_fit'    => (string) ( $params['pwaIconFit'] ?? 'cover' ),
		'pwa_icon_focus_x'=> (string) absint( $params['pwaIconFocusX'] ?? 50 ),
		'pwa_icon_focus_y'=> (string) absint( $params['pwaIconFocusY'] ?? 50 ),
		'pwa_icon_small_size' => (string) absint( $params['pwaIconSmallSize'] ?? 192 ),
		'pwa_icon_large_size' => (string) absint( $params['pwaIconLargeSize'] ?? 512 ),
		'pwa_icon_apple_size' => (string) absint( $params['pwaIconAppleSize'] ?? 180 ),
		'font_css_url'    => (string) ( $params['fontCssUrl'] ?? '' ),
		'font_body_family' => (string) ( $params['fontBodyFamily'] ?? '' ),
		'font_heading_family' => (string) ( $params['fontHeadingFamily'] ?? '' ),
		'font_mono_family' => (string) ( $params['fontMonoFamily'] ?? '' ),
	);

	$sanitized = array();
	foreach ( $fields as $key => $value ) {
		if ( 'pwa_theme_color' === $key ) {
			$sanitized[ $key ] = nerv_core_control_sanitize_hex_color( $value );
		} elseif ( in_array( $key, array( 'brand_logo_id', 'pwa_icon_id' ), true ) ) {
			$sanitized[ $key ] = (string) absint( $value );
		} elseif ( in_array( $key, array( 'brand_logo_focus_x', 'brand_logo_focus_y', 'pwa_icon_focus_x', 'pwa_icon_focus_y' ), true ) ) {
			$sanitized[ $key ] = (string) min( 100, max( 0, absint( $value ) ) );
		} elseif ( in_array( $key, array( 'pwa_icon_small_size', 'pwa_icon_large_size', 'pwa_icon_apple_size' ), true ) ) {
			$sanitized[ $key ] = (string) min( 1024, max( 64, absint( $value ) ) );
		} elseif ( in_array( $key, array( 'brand_logo_fit', 'pwa_icon_fit' ), true ) ) {
			$sanitized[ $key ] = in_array( $value, array( 'contain', 'cover' ), true ) ? $value : 'contain';
		} elseif ( 'font_css_url' === $key ) {
			$sanitized[ $key ] = '' === trim( $value ) ? '' : esc_url_raw( $value );
		} elseif ( str_starts_with( $key, 'font_' ) ) {
			$sanitized[ $key ] = nerv_core_control_sanitize_font_stack( $value );
		} else {
			$sanitized[ $key ] = sanitize_text_field( wp_unslash( $value ) );
		}
	}

	update_option( 'nerv_terminal_strings', array_merge( $current, $sanitized ), false );
	update_option( 'blogname', $sanitized['pwa_name'] ?: get_bloginfo( 'name' ) );

	return new WP_REST_Response(
		array(
			'message'   => __( 'Brand settings saved.', 'nerv-core' ),
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_seo_save( WP_REST_Request $request ): WP_REST_Response {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	update_option(
		'nerv_core_seo_options',
		nerv_core_seo_sanitize_options(
			array(
				'enabled'             => ! empty( $params['enabled'] ),
				'defer_to_seo_plugin' => ! empty( $params['deferToSeoPlugin'] ),
				'site_description'    => (string) ( $params['siteDescription'] ?? '' ),
				'default_og_image_id' => absint( $params['defaultOgImageId'] ?? 0 ),
				'noindex_markdown'    => ! empty( $params['noindexMarkdown'] ),
			)
		),
		false
	);

	return new WP_REST_Response(
		array(
			'message'   => __( 'SEO settings saved.', 'nerv-core' ),
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_panels_save( WP_REST_Request $request ): WP_REST_Response {
	if ( ! function_exists( 'nerv_terminal_panel_sanitize_options' ) || ! function_exists( 'nerv_terminal_panel_definitions' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'Panel settings are unavailable.', 'nerv-core' ) ), 500 );
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$panels = is_array( $params['panels'] ?? null ) ? $params['panels'] : array();
	$panel_input = array();
	foreach ( $panels as $panel ) {
		if ( ! is_array( $panel ) ) {
			continue;
		}

		$id = sanitize_key( (string) ( $panel['id'] ?? '' ) );
		if ( '' === $id ) {
			continue;
		}

		$panel_input[ $id ] = array(
			'enabled' => ! empty( $panel['enabled'] ),
			'column'  => sanitize_key( (string) ( $panel['column'] ?? '' ) ),
			'order'   => absint( $panel['order'] ?? 0 ),
		);
		if ( in_array( $id, array( 'log', 'status', 'monitor' ), true ) ) {
			$panel_input[ $id ]['source'] = sanitize_key( (string) ( $panel['source'] ?? 'decorative' ) );
		}
	}

	update_option( 'nerv_terminal_panel_options', nerv_terminal_panel_sanitize_options( $panel_input ), false );

	if ( function_exists( 'nerv_terminal_custom_panel_sanitize_options' ) ) {
		$custom_panels = is_array( $params['customPanels'] ?? null ) ? $params['customPanels'] : array();
		update_option( 'nerv_terminal_custom_panels', nerv_terminal_custom_panel_sanitize_options( $custom_panels ), false );
	}

	if ( function_exists( 'nerv_terminal_panel_repeater_sanitize_options' ) ) {
		$repeater_input = array();
		foreach ( $panels as $panel ) {
			if ( ! is_array( $panel ) ) {
				continue;
			}
			$id = sanitize_key( (string) ( $panel['id'] ?? '' ) );
			if ( in_array( $id, array( 'status', 'monitor', 'log' ), true ) && is_array( $panel['rows'] ?? null ) ) {
				$repeater_input[ $id ] = $panel['rows'];
			}
		}
		if ( $repeater_input ) {
			$current_repeaters = get_option( 'nerv_terminal_panel_repeater_options', array() );
			if ( ! is_array( $current_repeaters ) ) {
				$current_repeaters = array();
			}
			update_option( 'nerv_terminal_panel_repeater_options', nerv_terminal_panel_repeater_sanitize_options( array_merge( $current_repeaters, $repeater_input ) ), false );
		}
	}

	$current = get_option( 'nerv_terminal_strings', array() );
	if ( ! is_array( $current ) ) {
		$current = array();
	}

	$definitions = nerv_terminal_panel_definitions();
	foreach ( $panels as $panel ) {
		if ( ! is_array( $panel ) ) {
			continue;
		}
		$id = sanitize_key( (string) ( $panel['id'] ?? '' ) );
		if ( ! isset( $definitions[ $id ] ) || ! is_array( $panel['fields'] ?? null ) ) {
			continue;
		}

		$allowed_fields = (array) ( $definitions[ $id ]['fields'] ?? array() );
		foreach ( $allowed_fields as $field_key ) {
			if ( array_key_exists( $field_key, $panel['fields'] ) ) {
				$current[ $field_key ] = sanitize_text_field( wp_unslash( (string) $panel['fields'][ $field_key ] ) );
			}
		}
	}

	update_option( 'nerv_terminal_strings', $current, false );

	return new WP_REST_Response(
		array(
			'message'   => __( 'Panel settings saved.', 'nerv-core' ),
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_articles_save( WP_REST_Request $request ): WP_REST_Response {
	if ( ! function_exists( 'nerv_core_related_sanitize_options' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'Article settings are unavailable.', 'nerv-core' ) ), 500 );
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	update_option(
		'nerv_core_related_options',
		nerv_core_related_sanitize_options(
			array(
				'enabled'             => ! empty( $params['enabled'] ),
				'title'               => (string) ( $params['title'] ?? '' ),
				'limit'               => absint( $params['limit'] ?? 3 ),
				'category_weight'     => absint( $params['categoryWeight'] ?? 2 ),
				'tag_weight'          => absint( $params['tagWeight'] ?? 1 ),
				'recent_weight'       => absint( $params['recentWeight'] ?? 1 ),
				'recent_days'         => absint( $params['recentDays'] ?? 180 ),
				'cache_hours'         => absint( $params['cacheHours'] ?? 12 ),
				'excluded_categories' => is_array( $params['excludedCategories'] ?? null ) ? $params['excludedCategories'] : array(),
			)
		)
	);

	return new WP_REST_Response(
		array(
			'message'   => __( 'Article settings saved.', 'nerv-core' ),
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_geo_save( WP_REST_Request $request ): WP_REST_Response {
	if ( ! function_exists( 'nerv_core_indexnow_sanitize_options' ) || ! function_exists( 'nerv_core_geo_crawler_sanitize_options' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'GEO settings are unavailable.', 'nerv-core' ) ), 500 );
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$indexnow = is_array( $params['indexnow'] ?? null ) ? $params['indexnow'] : array();
	$crawler  = is_array( $params['crawler'] ?? null ) ? $params['crawler'] : array();

	update_option(
		'nerv_core_indexnow_options',
		nerv_core_indexnow_sanitize_options(
			array(
				'enabled'  => ! empty( $indexnow['enabled'] ),
				'key'      => (string) ( $indexnow['key'] ?? '' ),
				'endpoint' => (string) ( $indexnow['endpoint'] ?? '' ),
				'dry_run'  => ! empty( $indexnow['dryRun'] ),
			)
		)
	);

	update_option(
		'nerv_core_geo_crawler_options',
		nerv_core_geo_crawler_sanitize_options(
			array(
				'enabled'        => ! empty( $crawler['enabled'] ),
				'retention_days' => absint( $crawler['retentionDays'] ?? 30 ),
				'bots'           => is_array( $crawler['bots'] ?? null ) ? $crawler['bots'] : array(),
			)
		)
	);

	return new WP_REST_Response(
		array(
			'message'   => __( 'GEO settings saved.', 'nerv-core' ),
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_effects_save( WP_REST_Request $request ): WP_REST_Response {
	if ( ! function_exists( 'nerv_terminal_effect_sanitize_options' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'Effect settings are unavailable.', 'nerv-core' ) ), 500 );
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	update_option(
		'nerv_terminal_effect_options',
		nerv_terminal_effect_sanitize_options(
			array(
				'enabled'         => ! empty( $params['enabled'] ),
				'background_grid' => ! empty( $params['backgroundGrid'] ),
				'scanlines'       => ! empty( $params['scanlines'] ),
				'panel_glow'      => ! empty( $params['panelGlow'] ),
				'motion'          => ! empty( $params['motion'] ),
				'intensity'       => absint( $params['intensity'] ?? 65 ),
				'preset'          => sanitize_key( (string) ( $params['preset'] ?? 'balanced' ) ),
				'desktop'         => array(
					'enabled'   => ! empty( $params['desktop']['enabled'] ),
					'intensity' => absint( $params['desktop']['intensity'] ?? ( $params['intensity'] ?? 65 ) ),
				),
				'mobile'          => array(
					'enabled'         => ! empty( $params['mobile']['enabled'] ),
					'background_grid' => ! empty( $params['mobile']['backgroundGrid'] ),
					'scanlines'       => ! empty( $params['mobile']['scanlines'] ),
					'panel_glow'      => ! empty( $params['mobile']['panelGlow'] ),
					'motion'          => ! empty( $params['mobile']['motion'] ),
					'intensity'       => absint( $params['mobile']['intensity'] ?? 35 ),
				),
			)
		),
		false
	);

	return new WP_REST_Response(
		array(
			'message'   => __( 'Effect settings saved.', 'nerv-core' ),
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_appearance_save( WP_REST_Request $request ): WP_REST_Response {
	if ( ! function_exists( 'nerv_terminal_appearance_sanitize_options' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'Appearance settings are unavailable.', 'nerv-core' ) ), 500 );
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	update_option(
		'nerv_terminal_appearance_options',
		nerv_terminal_appearance_sanitize_options(
			array(
				'palette' => sanitize_key( (string) ( $params['palette'] ?? 'hazard' ) ),
				'mode'    => sanitize_key( (string) ( $params['mode'] ?? 'void' ) ),
			)
		),
		false
	);

	return new WP_REST_Response(
		array(
			'message'   => __( 'Appearance settings saved.', 'nerv-core' ),
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_partners_save( WP_REST_Request $request ): WP_REST_Response {
	if ( ! function_exists( 'nerv_core_partner_display_sanitize_options' ) || ! function_exists( 'nerv_core_partner_health_sanitize_options' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'Partner settings are unavailable.', 'nerv-core' ) ), 500 );
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$display = is_array( $params['display'] ?? null ) ? $params['display'] : array();
	$health  = is_array( $params['health'] ?? null ) ? $params['health'] : array();

	update_option(
		'nerv_core_partner_display_options',
		nerv_core_partner_display_sanitize_options(
			array(
				'footer_enabled'      => ! empty( $display['footerEnabled'] ),
				'footer_limit'        => absint( $display['footerLimit'] ?? 4 ),
				'application_enabled' => ! empty( $display['applicationEnabled'] ),
				'application_email'   => (string) ( $display['applicationEmail'] ?? '' ),
				'application_text'    => (string) ( $display['applicationText'] ?? '' ),
				'llms_include'        => ! empty( $display['llmsInclude'] ),
			)
		)
	);

	update_option(
		'nerv_core_partner_health_options',
		nerv_core_partner_health_sanitize_options(
			array(
				'enabled'      => ! empty( $health['enabled'] ),
				'timeout'      => absint( $health['timeout'] ?? 5 ),
				'slow_seconds' => (float) ( $health['slowSeconds'] ?? 2.5 ),
			)
		)
	);

	return new WP_REST_Response(
		array(
			'message'   => __( 'Partner settings saved.', 'nerv-core' ),
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_ai_policy_generate(): WP_REST_Response {
	if ( ! function_exists( 'nerv_core_ai_policy_generate_page' ) || ! function_exists( 'nerv_core_ai_policy_url' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'AI usage policy generation is unavailable.', 'nerv-core' ) ), 500 );
	}

	$page_id = nerv_core_ai_policy_generate_page();
	if ( ! $page_id ) {
		return new WP_REST_Response( array( 'message' => __( 'AI usage policy page could not be generated.', 'nerv-core' ) ), 500 );
	}

	return new WP_REST_Response(
		array(
			'message'   => __( 'AI usage policy page generated.', 'nerv-core' ),
			'result'    => array(
				'pageId' => $page_id,
				'url'    => nerv_core_ai_policy_url(),
			),
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_indexnow_test(): WP_REST_Response {
	if ( ! function_exists( 'nerv_core_indexnow_submit_urls' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'IndexNow testing is unavailable.', 'nerv-core' ) ), 500 );
	}

	$url    = function_exists( 'nerv_core_ai_policy_exists' ) && nerv_core_ai_policy_exists() ? nerv_core_ai_policy_url() : home_url( '/' );
	$result = nerv_core_indexnow_submit_urls( array( $url ), 'manual-test' );
	$status = sanitize_key( (string) ( $result['status'] ?? 'unknown' ) );

	return new WP_REST_Response(
		array(
			'message'   => sprintf(
				/* translators: %s: IndexNow test status. */
				__( 'IndexNow TEST completed with status: %s.', 'nerv-core' ),
				$status
			),
			'result'    => $result,
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_partner_health_test(): WP_REST_Response {
	if ( ! function_exists( 'nerv_core_partner_health_check_all' ) || ! function_exists( 'nerv_core_partner_health_summary' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'Partner health testing is unavailable.', 'nerv-core' ) ), 500 );
	}

	$results = nerv_core_partner_health_check_all();
	$summary = nerv_core_partner_health_summary();

	return new WP_REST_Response(
		array(
			'message'   => __( 'Partner health TEST completed.', 'nerv-core' ),
			'result'    => array(
				'checked' => count( $results ),
				'summary' => $summary,
			),
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_rest_control_tools_action( WP_REST_Request $request ): WP_REST_Response {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$action = sanitize_key( (string) ( $params['toolAction'] ?? '' ) );
	switch ( $action ) {
		case 'refresh_markdown':
			$result = function_exists( 'nerv_core_tools_refresh_markdown_cache' ) ? nerv_core_tools_refresh_markdown_cache() : array( 'message' => __( 'Markdown refresh is unavailable.', 'nerv-core' ) );
			break;
		case 'flush_related':
			$result = function_exists( 'nerv_core_tools_flush_related_cache' ) ? nerv_core_tools_flush_related_cache() : array( 'message' => __( 'Related cache flushing is unavailable.', 'nerv-core' ) );
			break;
		case 'apply_geo_defaults':
			$result = function_exists( 'nerv_core_tools_apply_geo_recommended_defaults' ) ? nerv_core_tools_apply_geo_recommended_defaults() : array( 'message' => __( 'Recommended GEO setup is unavailable.', 'nerv-core' ) );
			break;
		case 'import_demo':
			$result = function_exists( 'nerv_core_tools_import_demo_content' ) ? nerv_core_tools_import_demo_content() : array( 'message' => __( 'Demo import is unavailable.', 'nerv-core' ) );
			break;
		case 'run_theme_check':
			$result = function_exists( 'nerv_core_tools_run_theme_release_audit' ) ? nerv_core_tools_run_theme_release_audit() : array( 'message' => __( 'Theme release audit is unavailable.', 'nerv-core' ) );
			break;
		case 'export_preset':
			$result = function_exists( 'nerv_core_tools_export_settings_preset' ) ? nerv_core_tools_export_settings_preset() : array( 'message' => __( 'Settings preset export is unavailable.', 'nerv-core' ) );
			break;
		case 'import_preset':
			$result = function_exists( 'nerv_core_tools_import_settings_preset' ) ? nerv_core_tools_import_settings_preset( $params['preset'] ?? '' ) : array( 'message' => __( 'Settings preset import is unavailable.', 'nerv-core' ) );
			break;
		default:
			return new WP_REST_Response( array( 'message' => __( 'Unknown tool action.', 'nerv-core' ) ), 400 );
	}

	return new WP_REST_Response(
		array(
			'message'   => sanitize_text_field( (string) ( $result['message'] ?? __( 'Tool action completed.', 'nerv-core' ) ) ),
			'result'    => $result,
			'dashboard' => nerv_core_control_dashboard_data(),
		)
	);
}

function nerv_core_control_dashboard_data(): array {
	$cover_status = function_exists( 'nerv_core_cover_status' ) ? nerv_core_cover_status() : array( 'ready' => false, 'dryRun' => false, 'label' => __( 'Unavailable', 'nerv-core' ), 'message' => '' );
	$cover_options = function_exists( 'nerv_core_cover_options' ) ? nerv_core_cover_options() : array();
	$indexnow_options = function_exists( 'nerv_core_indexnow_options' ) ? nerv_core_indexnow_options() : array();
	$crawler_options = function_exists( 'nerv_core_geo_crawler_options' ) ? nerv_core_geo_crawler_options() : array();
	$crawler_summary = function_exists( 'nerv_core_geo_crawler_summary' ) ? nerv_core_geo_crawler_summary( 7 ) : array( 'total' => 0, 'window' => array(), 'totals' => array(), 'recent' => array() );
	$partner_summary = function_exists( 'nerv_core_partner_health_summary' ) ? nerv_core_partner_health_summary() : array( 'online' => 0, 'slow' => 0, 'offline' => 0, 'total' => 0 );
	$partner_display_options = function_exists( 'nerv_core_partner_display_options' ) ? nerv_core_partner_display_options() : array();
	$partner_health_options = function_exists( 'nerv_core_partner_health_options' ) ? nerv_core_partner_health_options() : array();
	$related_options = function_exists( 'nerv_core_related_options' ) ? nerv_core_related_options() : array();
	$seo_options = nerv_core_seo_options();
	$effect_options = function_exists( 'nerv_terminal_effect_options' ) ? nerv_terminal_effect_options() : array();
	$appearance_options = function_exists( 'nerv_terminal_appearance_options' ) ? nerv_terminal_appearance_options() : array();
	$brand_options = get_option( 'nerv_terminal_strings', array() );
	if ( ! is_array( $brand_options ) ) {
		$brand_options = array();
	}
	$categories = get_categories(
		array(
			'hide_empty' => false,
			'orderby'   => 'name',
			'order'     => 'ASC',
		)
	);
	$policy_exists = function_exists( 'nerv_core_ai_policy_exists' ) && nerv_core_ai_policy_exists();
	$markdown_stats = nerv_core_control_markdown_stats();

	return array(
		'site'       => array(
			'name'      => get_bloginfo( 'name' ),
			'url'       => home_url( '/' ),
			'theme'     => wp_get_theme()->get( 'Name' ),
			'wpVersion' => get_bloginfo( 'version' ),
			'core'      => NERV_CORE_VERSION,
		),
		'tabs'       => nerv_core_control_tabs(),
		'steps'      => nerv_core_control_wizard_steps( $policy_exists, $markdown_stats ),
		'health'     => nerv_core_control_health_items( $cover_status, $indexnow_options, $crawler_options, $crawler_summary, $partner_summary, $related_options, $policy_exists, $markdown_stats ),
		'metrics'    => array(
			array(
				'label' => __( 'AI crawls / 7D', 'nerv-core' ),
				'value' => (string) absint( $crawler_summary['total'] ?? 0 ),
			),
			array(
				'label' => __( 'Partners online', 'nerv-core' ),
				'value' => sprintf(
					'%1$d/%2$d',
					absint( $partner_summary['online'] ?? 0 ),
					absint( $partner_summary['total'] ?? 0 )
				),
			),
			array(
				'label' => __( 'Markdown mirrors', 'nerv-core' ),
				'value' => sprintf(
					'%1$d/%2$d',
					absint( $markdown_stats['cached'] ?? 0 ),
					absint( $markdown_stats['eligible'] ?? 0 )
				),
			),
			array(
				'label' => __( 'AI cover mode', 'nerv-core' ),
				'value' => ! empty( $cover_options['dry_run'] ) ? __( 'Dry-run', 'nerv-core' ) : ( ! empty( $cover_status['ready'] ) ? __( 'Live', 'nerv-core' ) : __( 'Fallback', 'nerv-core' ) ),
			),
		),
		'activity'   => array(
			'indexnow' => array_slice( function_exists( 'nerv_core_indexnow_log' ) ? nerv_core_indexnow_log() : array(), 0, 5 ),
			'crawlers' => array_slice( (array) ( $crawler_summary['recent'] ?? array() ), 0, 5 ),
		),
		'links'      => array(
			'llms'       => function_exists( 'nerv_core_geo_llms_url' ) ? nerv_core_geo_llms_url( false ) : home_url( '/llms.txt' ),
			'llmsFull'   => function_exists( 'nerv_core_geo_llms_url' ) ? nerv_core_geo_llms_url( true ) : home_url( '/llms-full.txt' ),
			'jsonFeed'   => function_exists( 'nerv_core_geo_json_feed_url' ) ? nerv_core_geo_json_feed_url() : home_url( '/feed/json' ),
			'aiPolicy'   => function_exists( 'nerv_core_ai_policy_url' ) ? nerv_core_ai_policy_url() : home_url( '/ai-policy/' ),
			'partners'   => get_post_type_archive_link( 'partner' ) ?: home_url( '/partners/' ),
			'projects'   => get_post_type_archive_link( 'project' ) ?: home_url( '/projects/' ),
		),
		'legacy'     => array(
			'anchor' => '#nerv-control-legacy-settings',
			'label'  => __( 'Open current settings forms', 'nerv-core' ),
		),
		'forms'      => array(
			'aiServices' => nerv_core_control_ai_services_form_data( $cover_options, $cover_status ),
			'brand'      => nerv_core_control_brand_form_data( $brand_options ),
			'seo'        => nerv_core_control_seo_form_data( $seo_options ),
			'panels'     => nerv_core_control_panels_form_data( $brand_options ),
			'articles'   => nerv_core_control_articles_form_data( $related_options, $categories ),
			'mobile'     => nerv_core_control_mobile_form_data(),
			'social'     => nerv_core_control_social_form_data(),
			'geo'        => nerv_core_control_geo_form_data( $indexnow_options, $crawler_options, $crawler_summary, $policy_exists, $markdown_stats ),
			'effects'    => nerv_core_control_effects_form_data( $effect_options ),
			'appearance' => nerv_core_control_appearance_form_data( $appearance_options ),
			'partners'   => nerv_core_control_partners_form_data( $partner_display_options, $partner_health_options, $partner_summary ),
			'tools'      => nerv_core_control_tools_form_data( $markdown_stats, $partner_summary ),
		),
	);
}

function nerv_core_seo_default_options(): array {
	return array(
		'enabled'             => true,
		'defer_to_seo_plugin' => true,
		'site_description'    => '',
		'default_og_image_id' => 0,
		'noindex_markdown'    => true,
	);
}

function nerv_core_seo_options(): array {
	$options = get_option( 'nerv_core_seo_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	return nerv_core_seo_sanitize_options( $options );
}

function nerv_core_seo_sanitize_options( $input ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	$defaults = nerv_core_seo_default_options();

	return array(
		'enabled'             => array_key_exists( 'enabled', $input ) ? ! empty( $input['enabled'] ) : (bool) $defaults['enabled'],
		'defer_to_seo_plugin' => array_key_exists( 'defer_to_seo_plugin', $input ) ? ! empty( $input['defer_to_seo_plugin'] ) : (bool) $defaults['defer_to_seo_plugin'],
		'site_description'    => sanitize_textarea_field( (string) ( $input['site_description'] ?? $defaults['site_description'] ) ),
		'default_og_image_id' => absint( $input['default_og_image_id'] ?? $defaults['default_og_image_id'] ),
		'noindex_markdown'    => array_key_exists( 'noindex_markdown', $input ) ? ! empty( $input['noindex_markdown'] ) : (bool) $defaults['noindex_markdown'],
	);
}

function nerv_core_control_panels_form_data( array $string_options ): array {
	if ( ! function_exists( 'nerv_terminal_panel_definitions' ) || ! function_exists( 'nerv_terminal_panel_options' ) || ! function_exists( 'nerv_terminal_default_strings' ) ) {
		return array( 'panels' => array(), 'customPanels' => array() );
	}

	$definitions = nerv_terminal_panel_definitions();
	$options     = nerv_terminal_panel_options();
	$defaults    = nerv_terminal_default_strings();
	$repeaters   = function_exists( 'nerv_terminal_panel_repeater_options' ) ? nerv_terminal_panel_repeater_options() : array();
	$panels      = array();

	foreach ( $definitions as $id => $definition ) {
		$fields = array();
		foreach ( (array) ( $definition['fields'] ?? array() ) as $field_key ) {
			$fields[] = array(
				'key'     => $field_key,
				'label'   => nerv_core_control_panel_field_label( (string) $field_key ),
				'value'   => sanitize_text_field( (string) ( $string_options[ $field_key ] ?? $defaults[ $field_key ] ?? '' ) ),
				'default' => sanitize_text_field( (string) ( $defaults[ $field_key ] ?? '' ) ),
			);
		}

		$panel_options = is_array( $options[ $id ] ?? null ) ? $options[ $id ] : array();
		$panel_data = array(
			'id'      => $id,
			'label'   => sanitize_text_field( (string) ( $definition['label'] ?? $id ) ),
			'column'  => sanitize_key( (string) ( $panel_options['column'] ?? $definition['column'] ?? 'center' ) ),
			'enabled' => ! empty( $panel_options['enabled'] ),
			'order'   => absint( $panel_options['order'] ?? count( $panels ) ),
			'fields'  => $fields,
		);
		if ( 'log' === $id ) {
			$panel_data['source'] = sanitize_key( (string) ( $panel_options['source'] ?? 'decorative' ) );
			$panel_data['sourceOptions'] = array(
				array( 'value' => 'decorative', 'label' => __( 'Decorative rows', 'nerv-core' ) ),
				array( 'value' => 'posts', 'label' => __( 'Recent posts', 'nerv-core' ) ),
			);
		} elseif ( 'status' === $id ) {
			$panel_data['source'] = sanitize_key( (string) ( $panel_options['source'] ?? 'decorative' ) );
			$panel_data['sourceOptions'] = array(
				array( 'value' => 'decorative', 'label' => __( 'Decorative rows', 'nerv-core' ) ),
				array( 'value' => 'probes', 'label' => __( 'WordPress probes', 'nerv-core' ) ),
			);
		} elseif ( 'monitor' === $id ) {
			$panel_data['source'] = sanitize_key( (string) ( $panel_options['source'] ?? 'decorative' ) );
			$panel_data['sourceOptions'] = array(
				array( 'value' => 'decorative', 'label' => __( 'Decorative rows', 'nerv-core' ) ),
				array( 'value' => 'probes', 'label' => __( 'WordPress probes', 'nerv-core' ) ),
				array( 'value' => 'crawlers', 'label' => __( 'AI crawler data', 'nerv-core' ) ),
			);
		}

		if ( in_array( $id, array( 'status', 'monitor', 'log' ), true ) ) {
			$panel_data['rowType'] = $id;
			$panel_data['rows']    = array_values( is_array( $repeaters[ $id ] ?? null ) ? $repeaters[ $id ] : array() );
			$panel_data['rowFields'] = array( 'label', 'value' );
			if ( 'monitor' === $id ) {
				$panel_data['rowFields'][] = 'level';
			} elseif ( 'status' === $id ) {
				$panel_data['rowFields'][] = 'state';
				$panel_data['stateOptions'] = array(
					array( 'value' => 'green', 'label' => __( 'Green', 'nerv-core' ) ),
					array( 'value' => 'amber', 'label' => __( 'Amber', 'nerv-core' ) ),
					array( 'value' => 'red', 'label' => __( 'Red', 'nerv-core' ) ),
				);
			}
		}

		$panels[] = $panel_data;
	}

	usort(
		$panels,
		static function ( array $a, array $b ): int {
			if ( $a['order'] === $b['order'] ) {
				return strcmp( (string) $a['id'], (string) $b['id'] );
			}

			return (int) $a['order'] <=> (int) $b['order'];
		}
	);

	return array(
		'panels'     => $panels,
		'customPanels' => function_exists( 'nerv_terminal_custom_panels' ) ? array_map(
			static function ( array $panel ): array {
				return array(
					'id'          => sanitize_key( (string) ( $panel['id'] ?? '' ) ),
					'label'       => sanitize_text_field( (string) ( $panel['title'] ?? '' ) ),
					'title'       => sanitize_text_field( (string) ( $panel['title'] ?? '' ) ),
					'subtitle'    => sanitize_text_field( (string) ( $panel['subtitle'] ?? '' ) ),
					'content'     => wp_kses_post( (string) ( $panel['content'] ?? '' ) ),
					'contentType' => sanitize_key( (string) ( $panel['content_type'] ?? 'richtext' ) ),
					'column'      => sanitize_key( (string) ( $panel['column'] ?? 'center' ) ),
					'enabled'     => ! empty( $panel['enabled'] ),
					'order'       => absint( $panel['order'] ?? 10 ),
				);
			},
			nerv_terminal_custom_panels()
		) : array(),
		'columns'    => array(
			array( 'value' => 'left', 'label' => __( 'Left column', 'nerv-core' ) ),
			array( 'value' => 'center', 'label' => __( 'Center column', 'nerv-core' ) ),
			array( 'value' => 'right', 'label' => __( 'Right column', 'nerv-core' ) ),
		),
		'contentTypes' => array(
			array( 'value' => 'richtext', 'label' => __( 'Rich text', 'nerv-core' ) ),
			array( 'value' => 'html', 'label' => __( 'Safe HTML', 'nerv-core' ) ),
			array( 'value' => 'shortcode', 'label' => __( 'Shortcode', 'nerv-core' ) ),
		),
		'previewUrl' => home_url( '/' ),
	);
}

function nerv_core_control_panel_field_label( string $field_key ): string {
	$labels = array(
		'user_title'          => __( 'Visitor title', 'nerv-core' ),
		'clearance_label'     => __( 'Clearance label', 'nerv-core' ),
		'clearance_value'     => __( 'Clearance value', 'nerv-core' ),
		'authorization'       => __( 'Guest authorization', 'nerv-core' ),
		'user_code'           => __( 'Guest code', 'nerv-core' ),
		'system_status_title' => __( 'Panel title', 'nerv-core' ),
		'mission_title'       => __( 'Panel title', 'nerv-core' ),
		'mission_purpose'     => __( 'Purpose line', 'nerv-core' ),
		'mission_state'       => __( 'State line', 'nerv-core' ),
		'all_systems'         => __( 'Nominal headline', 'nerv-core' ),
		'all_systems_sub'     => __( 'Nominal subline', 'nerv-core' ),
		'server_title'        => __( 'Panel title', 'nerv-core' ),
		'server_location'     => __( 'Location', 'nerv-core' ),
		'server_lat'          => __( 'Latitude', 'nerv-core' ),
		'server_lon'          => __( 'Longitude', 'nerv-core' ),
		'hero_kicker'         => __( 'Kicker', 'nerv-core' ),
		'hero_title'          => __( 'Headline', 'nerv-core' ),
		'hero_desc_1'         => __( 'Description line 1', 'nerv-core' ),
		'hero_desc_2'         => __( 'Description line 2', 'nerv-core' ),
		'hero_button'         => __( 'Button label', 'nerv-core' ),
		'latest_title'        => __( 'Panel title', 'nerv-core' ),
		'latest_subtitle'     => __( 'Subtitle', 'nerv-core' ),
		'view_all'            => __( 'View-all label', 'nerv-core' ),
		'log_title'           => __( 'Panel title', 'nerv-core' ),
		'log_subtitle'        => __( 'Subtitle', 'nerv-core' ),
		'log_level'           => __( 'Level badge', 'nerv-core' ),
		'more_logs'           => __( 'Archive link label', 'nerv-core' ),
		'pilot_title'         => __( 'Panel title', 'nerv-core' ),
		'pilot_subtitle'      => __( 'Subtitle', 'nerv-core' ),
		'pilot_role'          => __( 'Role', 'nerv-core' ),
		'pilot_bio'           => __( 'Bio', 'nerv-core' ),
		'pilot_base'          => __( 'Base line', 'nerv-core' ),
		'pilot_duty'          => __( 'Duty line', 'nerv-core' ),
		'monitor_title'       => __( 'Panel title', 'nerv-core' ),
		'monitor_subtitle'    => __( 'Subtitle', 'nerv-core' ),
		'alert_title'         => __( 'Panel title', 'nerv-core' ),
		'alert_subtitle'      => __( 'Subtitle', 'nerv-core' ),
		'warning_level'       => __( 'Warning level', 'nerv-core' ),
		'warning_text'        => __( 'Warning text', 'nerv-core' ),
		'warning_ip'          => __( 'Warning IP', 'nerv-core' ),
		'warning_button'      => __( 'Button label', 'nerv-core' ),
	);

	return $labels[ $field_key ] ?? $field_key;
}

function nerv_core_control_social_form_data(): array {
	$options = function_exists( 'nerv_core_social_options' ) ? nerv_core_social_options() : array( 'enabled' => false, 'open_new_tab' => true, 'links' => array() );
	$choices = function_exists( 'nerv_core_social_platform_choices' ) ? nerv_core_social_platform_choices() : array();
	$links   = array();

	foreach ( (array) ( $options['links'] ?? array() ) as $link ) {
		$links[] = array(
			'key'     => sanitize_key( (string) ( $link['key'] ?? 'website' ) ),
			'label'   => sanitize_text_field( (string) ( $link['label'] ?? '' ) ),
			'url'     => esc_url_raw( (string) ( $link['url'] ?? '' ) ),
			'qrUrl'   => esc_url_raw( (string) ( $link['qr_url'] ?? '' ) ),
			'enabled' => ! empty( $link['enabled'] ),
			'rel'     => sanitize_text_field( (string) ( $link['rel'] ?? '' ) ),
		);
	}

	return array(
		'enabled'     => ! empty( $options['enabled'] ),
		'openNewTab'  => ! empty( $options['open_new_tab'] ),
		'links'       => $links,
		'platforms'   => array_map(
			static function ( string $key, string $label ): array {
				return array(
					'value' => $key,
					'label' => $label,
				);
			},
			array_keys( $choices ),
			array_values( $choices )
		),
		'sameAsCount' => function_exists( 'nerv_core_social_same_as' ) ? count( nerv_core_social_same_as() ) : 0,
		'previewUrl'  => home_url( '/' ),
	);
}

function nerv_core_control_mobile_form_data(): array {
	$options = function_exists( 'nerv_terminal_mobile_options' ) ? nerv_terminal_mobile_options() : array();
	$icons   = function_exists( 'nerv_terminal_mobile_icon_choices' ) ? nerv_terminal_mobile_icon_choices() : array( 'home', 'blog', 'grid', 'pilot', 'more' );
	$tabs    = array();
	foreach ( (array) ( $options['tabs'] ?? array() ) as $tab ) {
		$tabs[] = array(
			'id'      => sanitize_key( (string) ( $tab['id'] ?? '' ) ),
			'label'   => sanitize_text_field( (string) ( $tab['label'] ?? '' ) ),
			'icon'    => sanitize_key( (string) ( $tab['icon'] ?? 'grid' ) ),
			'url'     => esc_url_raw( (string) ( $tab['url'] ?? '' ) ),
			'target'  => sanitize_key( (string) ( $tab['target'] ?? 'custom' ) ),
			'enabled' => ! empty( $tab['enabled'] ),
		);
	}

	return array(
		'enabled'      => ! empty( $options['enabled'] ),
		'moreEnabled'  => ! empty( $options['more_enabled'] ),
		'moreSections' => array(
			'status'  => ! empty( $options['more_sections']['status'] ),
			'monitor' => ! empty( $options['more_sections']['monitor'] ),
			'alert'   => ! empty( $options['more_sections']['alert'] ),
			'search'  => ! empty( $options['more_sections']['search'] ),
			'footer'  => ! empty( $options['more_sections']['footer'] ),
		),
		'tabs'         => $tabs,
		'icons'        => array_values( $icons ),
		'targets'      => array(
			array( 'value' => 'home', 'label' => __( 'Home', 'nerv-core' ) ),
			array( 'value' => 'blog', 'label' => __( 'Blog', 'nerv-core' ) ),
			array( 'value' => 'projects', 'label' => __( 'Projects', 'nerv-core' ) ),
			array( 'value' => 'pilot', 'label' => __( 'Pilot / About', 'nerv-core' ) ),
			array( 'value' => 'partners', 'label' => __( 'Partners', 'nerv-core' ) ),
			array( 'value' => 'search', 'label' => __( 'Search', 'nerv-core' ) ),
			array( 'value' => 'more', 'label' => __( 'MORE', 'nerv-core' ) ),
			array( 'value' => 'custom', 'label' => __( 'Custom URL', 'nerv-core' ) ),
		),
		'moreUrl'      => add_query_arg( 'nerv_more', '1', home_url( '/' ) ),
	);
}

function nerv_core_control_brand_form_data( array $brand_options ): array {
	$defaults = function_exists( 'nerv_terminal_default_strings' ) ? nerv_terminal_default_strings() : array();
	$get = static function ( string $key ) use ( $brand_options, $defaults ): string {
		return sanitize_text_field( (string) ( $brand_options[ $key ] ?? $defaults[ $key ] ?? '' ) );
	};

	return array(
		'brandTitle'    => $get( 'brand_title' ),
		'brandSubtitle' => $get( 'brand_subtitle' ),
		'brandMark'     => $get( 'brand_mark' ),
		'clockLabel'    => $get( 'clock_label' ),
		'clockTimezone' => $get( 'clock_timezone' ),
		'activeLabel'   => $get( 'active_label' ),
		'pwaName'       => $get( 'pwa_name' ),
		'pwaShortName'  => $get( 'pwa_short_name' ),
		'themeColor'    => nerv_core_control_sanitize_hex_color( (string) ( $brand_options['pwa_theme_color'] ?? $defaults['pwa_theme_color'] ?? '#0A0807' ) ),
		'brandLogo'     => nerv_core_control_media_field_data( absint( $brand_options['brand_logo_id'] ?? 0 ), 'thumbnail' ),
		'brandLogoFit'  => in_array( (string) ( $brand_options['brand_logo_fit'] ?? $defaults['brand_logo_fit'] ?? 'contain' ), array( 'contain', 'cover' ), true ) ? (string) ( $brand_options['brand_logo_fit'] ?? $defaults['brand_logo_fit'] ?? 'contain' ) : 'contain',
		'brandLogoFocusX' => min( 100, max( 0, absint( $brand_options['brand_logo_focus_x'] ?? $defaults['brand_logo_focus_x'] ?? 50 ) ) ),
		'brandLogoFocusY' => min( 100, max( 0, absint( $brand_options['brand_logo_focus_y'] ?? $defaults['brand_logo_focus_y'] ?? 50 ) ) ),
		'pwaIcon'       => nerv_core_control_media_field_data( absint( $brand_options['pwa_icon_id'] ?? 0 ), 'thumbnail' ),
		'pwaIconFit'    => in_array( (string) ( $brand_options['pwa_icon_fit'] ?? $defaults['pwa_icon_fit'] ?? 'cover' ), array( 'contain', 'cover' ), true ) ? (string) ( $brand_options['pwa_icon_fit'] ?? $defaults['pwa_icon_fit'] ?? 'cover' ) : 'cover',
		'pwaIconFocusX' => min( 100, max( 0, absint( $brand_options['pwa_icon_focus_x'] ?? $defaults['pwa_icon_focus_x'] ?? 50 ) ) ),
		'pwaIconFocusY' => min( 100, max( 0, absint( $brand_options['pwa_icon_focus_y'] ?? $defaults['pwa_icon_focus_y'] ?? 50 ) ) ),
		'pwaIconSmallSize' => min( 1024, max( 64, absint( $brand_options['pwa_icon_small_size'] ?? $defaults['pwa_icon_small_size'] ?? 192 ) ) ),
		'pwaIconLargeSize' => min( 1024, max( 64, absint( $brand_options['pwa_icon_large_size'] ?? $defaults['pwa_icon_large_size'] ?? 512 ) ) ),
		'pwaIconAppleSize' => min( 1024, max( 64, absint( $brand_options['pwa_icon_apple_size'] ?? $defaults['pwa_icon_apple_size'] ?? 180 ) ) ),
		'fontCssUrl'    => esc_url_raw( (string) ( $brand_options['font_css_url'] ?? $defaults['font_css_url'] ?? '' ) ),
		'fontBodyFamily' => sanitize_text_field( (string) ( $brand_options['font_body_family'] ?? $defaults['font_body_family'] ?? '' ) ),
		'fontHeadingFamily' => sanitize_text_field( (string) ( $brand_options['font_heading_family'] ?? $defaults['font_heading_family'] ?? '' ) ),
		'fontMonoFamily' => sanitize_text_field( (string) ( $brand_options['font_mono_family'] ?? $defaults['font_mono_family'] ?? '' ) ),
		'homeUrl'       => home_url( '/' ),
		'manifestUrl'   => add_query_arg( 'nerv_manifest', '1', home_url( '/' ) ),
		'pwaIconFallbackUrl' => add_query_arg( array( 'nerv_icon' => '1', 'size' => '512' ), home_url( '/' ) ),
	);
}

function nerv_core_control_seo_form_data( array $seo_options ): array {
	return array(
		'enabled'          => ! empty( $seo_options['enabled'] ),
		'deferToSeoPlugin' => ! empty( $seo_options['defer_to_seo_plugin'] ),
		'siteDescription'  => sanitize_textarea_field( (string) ( $seo_options['site_description'] ?? '' ) ),
		'defaultOgImage'   => nerv_core_control_media_field_data( absint( $seo_options['default_og_image_id'] ?? 0 ), 'thumbnail' ),
		'noindexMarkdown'  => ! empty( $seo_options['noindex_markdown'] ),
		'detectedSeoPlugin'=> function_exists( 'nerv_core_geo_detect_seo_plugin' ) && nerv_core_geo_detect_seo_plugin(),
		'homeUrl'          => home_url( '/' ),
	);
}

function nerv_core_control_media_field_data( int $attachment_id, string $size = 'thumbnail' ): array {
	$url = $attachment_id ? wp_get_attachment_image_url( $attachment_id, $size ) : '';

	return array(
		'id'       => $attachment_id,
		'url'      => $url ? esc_url_raw( $url ) : '',
		'title'    => $attachment_id ? sanitize_text_field( get_the_title( $attachment_id ) ) : '',
		'editUrl'  => $attachment_id ? get_edit_post_link( $attachment_id, 'raw' ) : '',
		'mimeType' => $attachment_id ? sanitize_text_field( (string) get_post_mime_type( $attachment_id ) ) : '',
	);
}

function nerv_core_control_sanitize_hex_color( string $value ): string {
	$value = trim( $value );
	if ( preg_match( '/^#[0-9A-Fa-f]{6}$/', $value ) ) {
		return strtoupper( $value );
	}

	return '#0A0807';
}

function nerv_core_control_sanitize_font_stack( string $value ): string {
	$value = trim( wp_strip_all_tags( wp_unslash( $value ) ) );
	$value = preg_replace( '/[{};<>]/', '', $value ) ?: '';

	return sanitize_text_field( $value );
}

function nerv_core_control_ai_services_form_data( array $cover_options, array $cover_status ): array {
	$usage = function_exists( 'nerv_core_ai_usage_summary' ) ? nerv_core_ai_usage_summary() : array();

	return array(
		'endpoint'       => esc_url_raw( (string) ( $cover_options['endpoint'] ?? '' ) ),
		'model'          => sanitize_text_field( (string) ( $cover_options['model'] ?? '' ) ),
		'promptTemplate' => sanitize_textarea_field( (string) ( $cover_options['prompt_template'] ?? '' ) ),
		'autoGenerate'   => ! empty( $cover_options['auto_generate'] ),
		'keyPointsAuto'  => ! empty( $cover_options['key_points_auto'] ),
		'dryRun'         => ! empty( $cover_options['dry_run'] ),
		'hasApiKey'      => '' !== (string) ( $cover_options['api_key'] ?? '' ),
		'status'         => array(
			'ready'   => ! empty( $cover_status['ready'] ),
			'dryRun'  => ! empty( $cover_status['dryRun'] ),
			'label'   => (string) ( $cover_status['label'] ?? '' ),
			'message' => (string) ( $cover_status['message'] ?? '' ),
		),
		'usage'          => $usage,
	);
}

function nerv_core_control_articles_form_data( array $related_options, array $categories ): array {
	$excluded = array_values( array_filter( array_map( 'absint', (array) ( $related_options['excluded_categories'] ?? array() ) ) ) );
	$post_counts = wp_count_posts( 'post' );
	$category_rows = array();
	foreach ( $categories as $category ) {
		if ( ! $category instanceof WP_Term ) {
			continue;
		}

		$category_rows[] = array(
			'id'       => (int) $category->term_id,
			'name'     => sanitize_text_field( $category->name ),
			'slug'     => sanitize_key( $category->slug ),
			'count'    => absint( $category->count ),
			'excluded' => in_array( (int) $category->term_id, $excluded, true ),
		);
	}

	return array(
		'enabled'            => ! empty( $related_options['enabled'] ),
		'title'              => sanitize_text_field( (string) ( $related_options['title'] ?? '' ) ),
		'limit'              => absint( $related_options['limit'] ?? 3 ),
		'categoryWeight'     => absint( $related_options['category_weight'] ?? 2 ),
		'tagWeight'          => absint( $related_options['tag_weight'] ?? 1 ),
		'recentWeight'       => absint( $related_options['recent_weight'] ?? 1 ),
		'recentDays'         => absint( $related_options['recent_days'] ?? 180 ),
		'cacheHours'         => absint( $related_options['cache_hours'] ?? 12 ),
		'excludedCategories' => $excluded,
		'categories'         => $category_rows,
		'postCount'          => is_object( $post_counts ) ? absint( $post_counts->publish ?? 0 ) : 0,
		'previewPostUrl'     => nerv_core_control_first_post_url(),
	);
}

function nerv_core_control_partners_form_data( array $display_options, array $health_options, array $partner_summary ): array {
	$partners = get_posts(
		array(
			'post_type'      => 'partner',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
		)
	);
	$rows = array();
	foreach ( $partners as $partner ) {
		$health = function_exists( 'nerv_core_partner_health_status' ) ? nerv_core_partner_health_status( (int) $partner->ID ) : array();
		$rows[] = array(
			'id'       => (int) $partner->ID,
			'title'    => get_the_title( $partner ),
			'url'      => esc_url_raw( (string) get_post_meta( (int) $partner->ID, '_nerv_partner_url', true ) ),
			'featured' => function_exists( 'nerv_core_partner_is_featured' ) && nerv_core_partner_is_featured( (int) $partner->ID ),
			'rel'      => sanitize_key( (string) get_post_meta( (int) $partner->ID, '_nerv_partner_rel', true ) ),
			'status'   => sanitize_key( (string) ( $health['status'] ?? 'online' ) ),
			'label'    => function_exists( 'nerv_core_partner_health_status_label' ) ? nerv_core_partner_health_status_label( (string) ( $health['status'] ?? 'online' ) ) : strtoupper( (string) ( $health['status'] ?? 'online' ) ),
			'message'  => sanitize_text_field( (string) ( $health['message'] ?? '' ) ),
			'checked'  => sanitize_text_field( (string) ( $health['checked'] ?? '' ) ),
		);
	}

	return array(
		'display' => array(
			'footerEnabled'      => ! empty( $display_options['footer_enabled'] ),
			'footerLimit'        => absint( $display_options['footer_limit'] ?? 4 ),
			'applicationEnabled' => ! empty( $display_options['application_enabled'] ),
			'applicationEmail'   => sanitize_email( (string) ( $display_options['application_email'] ?? get_option( 'admin_email' ) ) ),
			'applicationText'    => sanitize_text_field( (string) ( $display_options['application_text'] ?? '' ) ),
			'llmsInclude'        => ! empty( $display_options['llms_include'] ),
		),
		'health'  => array(
			'enabled'     => ! empty( $health_options['enabled'] ),
			'timeout'     => absint( $health_options['timeout'] ?? 5 ),
			'slowSeconds' => (float) ( $health_options['slow_seconds'] ?? 2.5 ),
			'summary'     => array(
				'online'  => absint( $partner_summary['online'] ?? 0 ),
				'slow'    => absint( $partner_summary['slow'] ?? 0 ),
				'offline' => absint( $partner_summary['offline'] ?? 0 ),
				'total'   => absint( $partner_summary['total'] ?? 0 ),
			),
		),
		'rows'    => $rows,
		'links'   => array(
			'archive' => get_post_type_archive_link( 'partner' ) ?: home_url( '/partners/' ),
			'new'     => admin_url( 'post-new.php?post_type=partner' ),
			'list'    => admin_url( 'edit.php?post_type=partner' ),
		),
	);
}

function nerv_core_control_geo_form_data( array $indexnow_options, array $crawler_options, array $crawler_summary, bool $policy_exists, array $markdown_stats ): array {
	$bots = array();
	if ( function_exists( 'nerv_core_geo_crawler_default_bots' ) ) {
		foreach ( nerv_core_geo_crawler_default_bots() as $key => $bot ) {
			$bots[] = array(
				'key'     => $key,
				'label'   => (string) ( $bot['label'] ?? $key ),
				'pattern' => (string) ( $bot['pattern'] ?? '' ),
				'enabled' => ! empty( $crawler_options['bots'][ $key ] ),
				'window'  => absint( $crawler_summary['window'][ $key ] ?? 0 ),
				'total'   => absint( $crawler_summary['totals'][ $key ] ?? 0 ),
			);
		}
	}

	return array(
		'indexnow' => array(
			'enabled' => ! empty( $indexnow_options['enabled'] ),
			'key'     => sanitize_text_field( (string) ( $indexnow_options['key'] ?? '' ) ),
			'endpoint'=> esc_url_raw( (string) ( $indexnow_options['endpoint'] ?? '' ) ),
			'dryRun'  => ! empty( $indexnow_options['dry_run'] ),
			'keyUrl'  => function_exists( 'nerv_core_indexnow_key_url' ) ? nerv_core_indexnow_key_url() : '',
		),
		'crawler'  => array(
			'enabled'       => ! empty( $crawler_options['enabled'] ),
			'retentionDays' => absint( $crawler_options['retention_days'] ?? 30 ),
			'bots'          => $bots,
			'total7Days'    => absint( $crawler_summary['total'] ?? 0 ),
		),
		'resources'=> array(
			'llms'       => function_exists( 'nerv_core_geo_llms_url' ) ? nerv_core_geo_llms_url( false ) : home_url( '/llms.txt' ),
			'llmsFull'   => function_exists( 'nerv_core_geo_llms_url' ) ? nerv_core_geo_llms_url( true ) : home_url( '/llms-full.txt' ),
			'jsonFeed'   => function_exists( 'nerv_core_geo_json_feed_url' ) ? nerv_core_geo_json_feed_url() : home_url( '/feed/json' ),
			'aiPolicy'   => function_exists( 'nerv_core_ai_policy_url' ) ? nerv_core_ai_policy_url() : home_url( '/ai-policy/' ),
			'policyReady'=> $policy_exists,
			'markdown'   => $markdown_stats,
		),
	);
}

function nerv_core_control_effects_form_data( array $effect_options ): array {
	$defaults = function_exists( 'nerv_terminal_effect_default_options' ) ? nerv_terminal_effect_default_options() : array(
		'enabled'         => true,
		'background_grid' => true,
		'scanlines'       => true,
		'panel_glow'      => true,
		'motion'          => true,
		'intensity'       => 65,
		'preset'          => 'balanced',
		'desktop'         => array( 'enabled' => true, 'intensity' => 65 ),
		'mobile'          => array( 'enabled' => true, 'background_grid' => false, 'scanlines' => true, 'panel_glow' => false, 'motion' => true, 'intensity' => 35 ),
	);
	$options = function_exists( 'nerv_terminal_effect_sanitize_options' ) ? nerv_terminal_effect_sanitize_options( $effect_options ) : array_merge( $defaults, $effect_options );
	$presets = array();
	if ( function_exists( 'nerv_terminal_effect_presets' ) ) {
		foreach ( nerv_terminal_effect_presets() as $key => $preset ) {
			$presets[] = array(
				'value' => sanitize_key( (string) $key ),
				'label' => sanitize_text_field( (string) ( $preset['label'] ?? $key ) ),
				'data'  => array(
					'enabled'        => ! empty( $preset['enabled'] ),
					'backgroundGrid' => ! empty( $preset['background_grid'] ),
					'scanlines'      => ! empty( $preset['scanlines'] ),
					'panelGlow'      => ! empty( $preset['panel_glow'] ),
					'motion'         => ! empty( $preset['motion'] ),
					'intensity'      => absint( $preset['intensity'] ?? 65 ),
					'mobile'         => array(
						'enabled'        => ! empty( $preset['mobile']['enabled'] ),
						'backgroundGrid' => ! empty( $preset['mobile']['background_grid'] ),
						'scanlines'      => ! empty( $preset['mobile']['scanlines'] ),
						'panelGlow'      => ! empty( $preset['mobile']['panel_glow'] ),
						'motion'         => ! empty( $preset['mobile']['motion'] ),
						'intensity'      => absint( $preset['mobile']['intensity'] ?? 35 ),
					),
				),
			);
		}
	}

	return array(
		'enabled'        => ! empty( $options['enabled'] ),
		'backgroundGrid' => ! empty( $options['background_grid'] ),
		'scanlines'      => ! empty( $options['scanlines'] ),
		'panelGlow'      => ! empty( $options['panel_glow'] ),
		'motion'         => ! empty( $options['motion'] ),
		'intensity'      => absint( $options['intensity'] ?? $defaults['intensity'] ),
		'preset'         => sanitize_key( (string) ( $options['preset'] ?? $defaults['preset'] ) ),
		'presets'        => $presets,
		'desktop'        => array(
			'enabled'   => ! empty( $options['desktop']['enabled'] ),
			'intensity' => absint( $options['desktop']['intensity'] ?? $options['intensity'] ?? 65 ),
		),
		'mobile'         => array(
			'enabled'        => ! empty( $options['mobile']['enabled'] ),
			'backgroundGrid' => ! empty( $options['mobile']['background_grid'] ),
			'scanlines'      => ! empty( $options['mobile']['scanlines'] ),
			'panelGlow'      => ! empty( $options['mobile']['panel_glow'] ),
			'motion'         => ! empty( $options['mobile']['motion'] ),
			'intensity'      => absint( $options['mobile']['intensity'] ?? $defaults['mobile']['intensity'] ?? 35 ),
		),
		'previewUrl'     => home_url( '/' ),
	);
}

function nerv_core_control_appearance_form_data( array $appearance_options ): array {
	$options = function_exists( 'nerv_terminal_appearance_sanitize_options' ) ? nerv_terminal_appearance_sanitize_options( $appearance_options ) : array(
		'palette' => 'hazard',
		'mode'    => 'void',
	);
	$palette_choices = function_exists( 'nerv_terminal_appearance_palette_choices' ) ? nerv_terminal_appearance_palette_choices() : array( 'hazard' => __( '09 Hazard', 'nerv-core' ) );
	$mode_choices    = function_exists( 'nerv_terminal_appearance_mode_choices' ) ? nerv_terminal_appearance_mode_choices() : array( 'void' => __( 'Night / Void', 'nerv-core' ), 'paper' => __( 'Day / Paper', 'nerv-core' ) );

	$palettes = array();
	foreach ( $palette_choices as $value => $label ) {
		$palettes[] = array(
			'value' => sanitize_key( (string) $value ),
			'label' => sanitize_text_field( (string) $label ),
		);
	}

	$modes = array();
	foreach ( $mode_choices as $value => $label ) {
		$modes[] = array(
			'value' => sanitize_key( (string) $value ),
			'label' => sanitize_text_field( (string) $label ),
		);
	}

	return array(
		'palette'    => sanitize_key( (string) ( $options['palette'] ?? 'hazard' ) ),
		'mode'       => sanitize_key( (string) ( $options['mode'] ?? 'void' ) ),
		'palettes'   => $palettes,
		'modes'      => $modes,
		'previewUrl' => home_url( '/' ),
	);
}

function nerv_core_control_tools_form_data( array $markdown_stats, array $partner_summary ): array {
	$root = dirname( dirname( NERV_CORE_DIR ) );
	if ( false === $root || '' === $root ) {
		$root = NERV_CORE_DIR;
	}

	$build_script = trailingslashit( $root ) . 'build.sh';
	$package_status = function_exists( 'nerv_core_tools_release_package_status' ) ? nerv_core_tools_release_package_status( $root ) : array(
		'distDir'  => trailingslashit( $root ) . 'dist',
		'status'   => 'missing',
		'complete' => false,
		'packages' => array(),
	);
	$seed_script  = trailingslashit( $root ) . 'bin/seed-demo.php';
	$theme_dir    = trailingslashit( $root ) . 'theme';
	$plugin_dir   = trailingslashit( $root ) . 'plugin';
	$demo_counts  = array(
		'projects' => post_type_exists( 'project' ) ? absint( wp_count_posts( 'project' )->publish ?? 0 ) : 0,
		'posts'    => absint( wp_count_posts( 'post' )->publish ?? 0 ),
		'partners' => post_type_exists( 'partner' ) ? absint( wp_count_posts( 'partner' )->publish ?? 0 ) : 0,
	);

	return array(
		'markdown' => array(
			'eligible' => absint( $markdown_stats['eligible'] ?? 0 ),
			'cached'   => absint( $markdown_stats['cached'] ?? 0 ),
			'dir'      => sanitize_text_field( (string) ( $markdown_stats['dir'] ?? '' ) ),
		),
		'related'  => array(
			'enabled' => function_exists( 'nerv_core_related_is_enabled' ) && nerv_core_related_is_enabled(),
		),
		'partners' => array(
			'total'   => absint( $partner_summary['total'] ?? 0 ),
			'online'  => absint( $partner_summary['online'] ?? 0 ),
			'slow'    => absint( $partner_summary['slow'] ?? 0 ),
			'offline' => absint( $partner_summary['offline'] ?? 0 ),
		),
		'build'    => array(
			'available' => is_file( $build_script ),
			'script'    => sanitize_text_field( $build_script ),
			'distDir'   => sanitize_text_field( (string) ( $package_status['distDir'] ?? trailingslashit( $root ) . 'dist' ) ),
			'status'    => sanitize_key( (string) ( $package_status['status'] ?? 'missing' ) ),
			'complete'  => ! empty( $package_status['complete'] ),
			'packages'  => is_array( $package_status['packages'] ?? null ) ? $package_status['packages'] : array(),
			'commands'  => array(
				'bundle' => './build.sh --bundle',
				'split'  => './build.sh --split',
			),
			'themeDir'  => sanitize_text_field( $theme_dir ),
			'pluginDir' => sanitize_text_field( $plugin_dir ),
		),
		'demo'     => array(
			'available' => function_exists( 'nerv_core_tools_import_demo_content' ) || is_file( $seed_script ),
			'command'   => 'php bin/seed-demo.php /path/to/wp-load.php',
			'counts'    => $demo_counts,
			'ready'     => $demo_counts['projects'] > 0 && $demo_counts['posts'] > 0 && $demo_counts['partners'] > 0,
			'summary'   => array(
				'created' => 0,
				'updated' => 0,
				'failed'  => 0,
			),
			'steps'     => array(
				array(
					'key'    => 'ready',
					'label'  => __( 'Current demo records', 'nerv-core' ),
					'state'  => $demo_counts['projects'] > 0 && $demo_counts['posts'] > 0 && $demo_counts['partners'] > 0 ? 'pass' : 'warning',
					'detail' => sprintf(
						/* translators: 1: project count, 2: post count, 3: partner count. */
						__( '%1$d projects, %2$d posts, %3$d partners are published.', 'nerv-core' ),
						$demo_counts['projects'],
						$demo_counts['posts'],
						$demo_counts['partners']
					),
				),
			),
		),
		'preset'   => array(
			'schema'       => 'nerv-terminal-settings-preset/v1',
			'optionGroups' => array_keys( function_exists( 'nerv_core_tools_preset_registry' ) ? nerv_core_tools_preset_registry() : array() ),
		),
		'themeCheck'=> array(
			'available' => function_exists( 'nerv_core_tools_run_theme_release_audit' ),
			'status'    => 'pending',
			'summary'   => array( 'pass' => 0, 'warning' => 0, 'fail' => 0 ),
			'checks'    => array(),
			'message'   => __( 'Run the release audit before packaging.', 'nerv-core' ),
		),
	);
}

function nerv_core_control_first_post_url(): string {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'fields'         => 'ids',
		)
	);

	return $posts ? get_permalink( (int) $posts[0] ) : admin_url( 'edit.php' );
}

function nerv_core_control_tabs(): array {
	return array(
		array( 'id' => 'dashboard', 'label' => __( '00 Dashboard', 'nerv-core' ), 'status' => 'active' ),
		array( 'id' => 'brand', 'label' => __( '01 Brand', 'nerv-core' ), 'status' => 'partial' ),
		array( 'id' => 'seo', 'label' => __( '02 SEO', 'nerv-core' ), 'status' => 'partial' ),
		array( 'id' => 'layout', 'label' => __( '03 Panels', 'nerv-core' ), 'status' => 'partial' ),
		array( 'id' => 'mobile', 'label' => __( '04 Mobile App', 'nerv-core' ), 'status' => 'partial' ),
		array( 'id' => 'social', 'label' => __( '05 Social', 'nerv-core' ), 'status' => 'partial' ),
		array( 'id' => 'articles', 'label' => __( '06 Articles', 'nerv-core' ), 'status' => 'partial' ),
		array( 'id' => 'geo', 'label' => __( '07 GEO', 'nerv-core' ), 'status' => 'partial' ),
		array( 'id' => 'appearance', 'label' => __( '08 Appearance', 'nerv-core' ), 'status' => 'partial' ),
		array( 'id' => 'effects', 'label' => __( '09 Effects', 'nerv-core' ), 'status' => 'partial' ),
		array( 'id' => 'tools', 'label' => __( '10 Tools', 'nerv-core' ), 'status' => 'partial' ),
		array( 'id' => 'ai', 'label' => __( '11 AI Services', 'nerv-core' ), 'status' => 'partial' ),
		array( 'id' => 'partners', 'label' => __( '12 Partners', 'nerv-core' ), 'status' => 'partial' ),
	);
}

function nerv_core_control_wizard_steps( bool $policy_exists, array $markdown_stats ): array {
	$has_demo_content = wp_count_posts( 'project' )->publish > 0 && wp_count_posts( 'partner' )->publish > 0;
	$has_social_profile = ( function_exists( 'nerv_core_social_links' ) && count( nerv_core_social_links() ) > 0 ) || (bool) get_user_meta( get_current_user_id(), 'nerv_author_title', true );

	return array(
		array(
			'key'    => 'identity',
			'label'  => __( 'Site identity', 'nerv-core' ),
			'done'   => '' !== get_bloginfo( 'name' ),
			'tab'    => 'brand',
			'action' => '',
			'button' => __( 'Open Brand', 'nerv-core' ),
			'detail' => __( 'Set the public site name, terminal mark, logo, PWA color, and app icons.', 'nerv-core' ),
		),
		array(
			'key'    => 'style',
			'label'  => __( 'Terminal style', 'nerv-core' ),
			'done'   => 'nerv-terminal' === get_stylesheet(),
			'tab'    => 'effects',
			'action' => '',
			'button' => __( 'Tune Effects', 'nerv-core' ),
			'detail' => __( 'Choose the visual intensity, motion budget, and desktop/mobile terminal effects.', 'nerv-core' ),
		),
		array(
			'key'    => 'social',
			'label'  => __( 'Social profile', 'nerv-core' ),
			'done'   => $has_social_profile,
			'tab'    => 'social',
			'action' => '',
			'button' => __( 'Open Social', 'nerv-core' ),
			'detail' => __( 'Add global and author identity links used by pilot cards and Person JSON-LD.', 'nerv-core' ),
		),
		array(
			'key'    => 'geo',
			'label'  => __( 'GEO launch', 'nerv-core' ),
			'done'   => $policy_exists && absint( $markdown_stats['eligible'] ?? 0 ) > 0,
			'tab'    => 'geo',
			'action' => 'apply_geo_defaults',
			'button' => __( 'Apply GEO Defaults', 'nerv-core' ),
			'detail' => __( 'Enable recommended IndexNow safety mode, crawler visibility, AI policy, and Markdown mirrors.', 'nerv-core' ),
		),
		array(
			'key'    => 'demo',
			'label'  => __( 'Demo content', 'nerv-core' ),
			'done'   => $has_demo_content,
			'tab'    => 'tools',
			'action' => 'import_demo',
			'button' => __( 'Import Demo', 'nerv-core' ),
			'detail' => __( 'Seed the projects, posts, partner records, authors, taxonomy, and markdown mirrors used for validation.', 'nerv-core' ),
		),
	);
}

function nerv_core_control_health_items( array $cover_status, array $indexnow_options, array $crawler_options, array $crawler_summary, array $partner_summary, array $related_options, bool $policy_exists, array $markdown_stats ): array {
		return array(
			array(
				'key'    => 'llms',
				'label'  => __( 'llms.txt', 'nerv-core' ),
				'state'  => function_exists( 'nerv_core_geo_llms_url' ) ? 'green' : 'red',
				'value'  => function_exists( 'nerv_core_geo_llms_url' ) ? __( 'Online', 'nerv-core' ) : __( 'Missing', 'nerv-core' ),
				'detail' => function_exists( 'nerv_core_geo_llms_url' ) ? nerv_core_geo_llms_url( false ) : '',
			),
			array(
				'key'    => 'markdown',
				'label'  => __( 'Markdown mirrors', 'nerv-core' ),
				'state'  => absint( $markdown_stats['eligible'] ?? 0 ) > 0 ? ( absint( $markdown_stats['cached'] ?? 0 ) > 0 ? 'green' : 'amber' ) : 'amber',
				'value'  => sprintf(
				'%1$d/%2$d',
				absint( $markdown_stats['cached'] ?? 0 ),
				absint( $markdown_stats['eligible'] ?? 0 )
			),
			'detail' => (string) ( $markdown_stats['dir'] ?? '' ),
			),
			array(
				'key'    => 'indexnow',
				'label'  => __( 'IndexNow', 'nerv-core' ),
				'state'  => ! empty( $indexnow_options['enabled'] ) ? 'green' : 'amber',
				'value'  => ! empty( $indexnow_options['enabled'] ) ? __( 'Enabled', 'nerv-core' ) : __( 'Disabled', 'nerv-core' ),
				'detail' => ! empty( $indexnow_options['dry_run'] ) ? __( 'Dry-run safety is on.', 'nerv-core' ) : __( 'Live submission mode.', 'nerv-core' ),
			),
			array(
				'key'    => 'cover',
				'label'  => __( 'AI cover API', 'nerv-core' ),
				'state'  => ! empty( $cover_status['ready'] ) ? 'green' : 'red',
				'value'  => (string) ( $cover_status['label'] ?? '' ),
				'detail' => (string) ( $cover_status['message'] ?? '' ),
			),
			array(
				'key'    => 'crawlers',
				'label'  => __( 'AI crawler monitor', 'nerv-core' ),
				'state'  => ! empty( $crawler_options['enabled'] ) ? 'green' : 'amber',
				'value'  => sprintf(
				/* translators: %d: crawler hits. */
				__( '%d hits / 7D', 'nerv-core' ),
				absint( $crawler_summary['total'] ?? 0 )
			),
			'detail' => __( 'GPTBot, ClaudeBot, PerplexityBot, Google-Extended and more.', 'nerv-core' ),
			),
			array(
				'key'    => 'partners',
				'label'  => __( 'Partner health', 'nerv-core' ),
				'state'  => absint( $partner_summary['offline'] ?? 0 ) > 0 ? 'amber' : 'green',
				'value'  => sprintf(
				'ONLINE %1$d / SLOW %2$d / OFFLINE %3$d',
				absint( $partner_summary['online'] ?? 0 ),
				absint( $partner_summary['slow'] ?? 0 ),
				absint( $partner_summary['offline'] ?? 0 )
			),
			'detail' => sprintf(
				/* translators: %d: partner count. */
				__( '%d partner records tracked.', 'nerv-core' ),
				absint( $partner_summary['total'] ?? 0 )
			),
			),
			array(
				'key'    => 'policy',
				'label'  => __( 'AI usage policy', 'nerv-core' ),
				'state'  => $policy_exists ? 'green' : 'red',
				'value'  => $policy_exists ? __( 'Published', 'nerv-core' ) : __( 'Missing', 'nerv-core' ),
				'detail' => function_exists( 'nerv_core_ai_policy_url' ) ? nerv_core_ai_policy_url() : home_url( '/ai-policy/' ),
			),
			array(
				'key'    => 'related',
				'label'  => __( 'Related entries', 'nerv-core' ),
				'state'  => ! empty( $related_options['enabled'] ) ? 'green' : 'amber',
				'value'  => ! empty( $related_options['enabled'] ) ? __( 'Enabled', 'nerv-core' ) : __( 'Disabled', 'nerv-core' ),
			'detail' => sprintf(
				/* translators: %d: related entry count. */
				__( '%d entries per article.', 'nerv-core' ),
				absint( $related_options['limit'] ?? 3 )
			),
		),
	);
}

function nerv_core_control_markdown_stats(): array {
	$eligible = count(
		get_posts(
			array(
				'post_type'      => function_exists( 'nerv_core_geo_public_post_types' ) ? nerv_core_geo_public_post_types() : array( 'post', 'project' ),
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'ids',
			)
		)
	);
	$dir = function_exists( 'nerv_core_geo_markdown_cache_dir' ) ? nerv_core_geo_markdown_cache_dir() : '';
	$cached = 0;
	if ( $dir && is_dir( $dir ) ) {
		$files = glob( trailingslashit( $dir ) . '*.md' );
		$cached = is_array( $files ) ? count( $files ) : 0;
	}

	return array(
		'eligible' => $eligible,
		'cached'   => $cached,
		'dir'      => $dir,
	);
}

function nerv_core_render_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$related_options = function_exists( 'nerv_core_related_options' ) ? nerv_core_related_options() : array();
	$indexnow_options = function_exists( 'nerv_core_indexnow_options' ) ? nerv_core_indexnow_options() : array();
	$indexnow_log     = function_exists( 'nerv_core_indexnow_log' ) ? nerv_core_indexnow_log() : array();
	$crawler_options  = function_exists( 'nerv_core_geo_crawler_options' ) ? nerv_core_geo_crawler_options() : array();
	$crawler_bots     = function_exists( 'nerv_core_geo_crawler_default_bots' ) ? nerv_core_geo_crawler_default_bots() : array();
	$crawler_summary  = function_exists( 'nerv_core_geo_crawler_summary' ) ? nerv_core_geo_crawler_summary( 7 ) : array( 'total' => 0, 'window' => array(), 'totals' => array(), 'recent' => array() );
	$ai_policy_exists = function_exists( 'nerv_core_ai_policy_exists' ) && nerv_core_ai_policy_exists();
	$ai_policy_url    = function_exists( 'nerv_core_ai_policy_url' ) ? nerv_core_ai_policy_url() : home_url( '/ai-policy/' );
	$partner_health_options = function_exists( 'nerv_core_partner_health_options' ) ? nerv_core_partner_health_options() : array();
	$partner_health_summary = function_exists( 'nerv_core_partner_health_summary' ) ? nerv_core_partner_health_summary() : array( 'online' => 0, 'slow' => 0, 'offline' => 0, 'total' => 0 );
	$partner_display_options = function_exists( 'nerv_core_partner_display_options' ) ? nerv_core_partner_display_options() : array();
	$cover_options = function_exists( 'nerv_core_cover_options' ) ? nerv_core_cover_options() : array();
	$cover_status  = function_exists( 'nerv_core_cover_status' ) ? nerv_core_cover_status() : array( 'ready' => false, 'label' => __( 'Not configured', 'nerv-core' ), 'message' => '' );
	$partner_health_posts = get_posts(
		array(
			'post_type'      => 'partner',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
		)
	);
	$categories      = get_categories(
		array(
			'hide_empty' => false,
			'orderby'   => 'name',
			'order'     => 'ASC',
		)
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'NERV Theme · Control', 'nerv-core' ); ?></h1>
		<p><?php esc_html_e( 'Manage theme identity, SEO, publishing resources, partners, and release tools from one WordPress-friendly dashboard.', 'nerv-core' ); ?></p>
		<?php settings_errors( 'nerv_core_settings' ); ?>
		<?php if ( isset( $_GET['nerv_ai_policy_status'] ) ) : ?>
			<?php if ( 'generated' === (string) wp_unslash( $_GET['nerv_ai_policy_status'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'AI usage policy page generated.', 'nerv-core' ); ?></p></div>
			<?php else : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'AI usage policy page could not be generated.', 'nerv-core' ); ?></p></div>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( isset( $_GET['nerv_indexnow_status'] ) ) : ?>
			<?php $indexnow_status = sanitize_key( (string) wp_unslash( $_GET['nerv_indexnow_status'] ) ); ?>
			<div class="notice <?php echo in_array( $indexnow_status, array( 'success', 'dry-run' ), true ) ? 'notice-success' : 'notice-warning'; ?> is-dismissible">
				<p>
					<?php
					printf(
						/* translators: %s: IndexNow test status. */
						esc_html__( 'IndexNow TEST completed with status: %s. See the log below.', 'nerv-core' ),
						esc_html( $indexnow_status )
					);
					?>
				</p>
			</div>
		<?php endif; ?>
		<?php if ( isset( $_GET['nerv_partner_health_status'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Partner health TEST completed. See the partner status table below.', 'nerv-core' ); ?></p></div>
		<?php endif; ?>
		<div id="nerv-control-app" class="nerv-control-app">
			<div class="nerv-control-shell nerv-control-shell--loading">
				<p><?php esc_html_e( 'Loading NERV Theme control dashboard...', 'nerv-core' ); ?></p>
			</div>
		</div>
		<div id="nerv-control-legacy-settings" class="nerv-control-legacy-settings">
			<h2><?php esc_html_e( 'Current Settings Forms', 'nerv-core' ); ?></h2>
			<p><?php esc_html_e( 'These server-rendered forms remain the authoritative save surface while the React control center is expanded tab by tab.', 'nerv-core' ); ?></p>
		</div>
		<table class="widefat striped" style="max-width: 760px;">
			<tbody>
				<tr>
					<th><?php esc_html_e( 'Theme layer', 'nerv-core' ); ?></th>
					<td><?php esc_html_e( 'NERV Terminal visual shell is expected to be active.', 'nerv-core' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Project CPT', 'nerv-core' ); ?></th>
					<td><?php esc_html_e( 'Registered as projects archive.', 'nerv-core' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Partner CPT', 'nerv-core' ); ?></th>
					<td><?php esc_html_e( 'Registered as partners archive.', 'nerv-core' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Partner health', 'nerv-core' ); ?></th>
					<td>
						<?php
						printf(
							/* translators: 1: online count, 2: slow count, 3: offline count, 4: total count. */
							esc_html__( 'ONLINE %1$d / SLOW %2$d / OFFLINE %3$d / TOTAL %4$d', 'nerv-core' ),
							absint( $partner_health_summary['online'] ?? 0 ),
							absint( $partner_health_summary['slow'] ?? 0 ),
							absint( $partner_health_summary['offline'] ?? 0 ),
							absint( $partner_health_summary['total'] ?? 0 )
						);
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'AI usage policy', 'nerv-core' ); ?></th>
					<td>
						<?php if ( $ai_policy_exists ) : ?>
							<span style="color:#008a20;"><?php esc_html_e( 'Published', 'nerv-core' ); ?></span>
							&mdash;
							<a href="<?php echo esc_url( $ai_policy_url ); ?>"><?php echo esc_html( $ai_policy_url ); ?></a>
						<?php else : ?>
							<span style="color:#b32d2e;"><?php esc_html_e( 'Missing', 'nerv-core' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'AI cover API', 'nerv-core' ); ?></th>
					<td>
						<span style="color:<?php echo ! empty( $cover_status['ready'] ) ? '#008a20' : '#b32d2e'; ?>;"><?php echo esc_html( (string) ( $cover_status['label'] ?? '' ) ); ?></span>
						&mdash;
						<?php echo esc_html( (string) ( $cover_status['message'] ?? '' ) ); ?>
					</td>
				</tr>
			</tbody>
		</table>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width: 920px; margin-top: 24px;">
			<input type="hidden" name="action" value="nerv_core_generate_ai_policy">
			<?php wp_nonce_field( 'nerv_core_generate_ai_policy' ); ?>
			<h2><?php esc_html_e( 'AI Usage Policy', 'nerv-core' ); ?></h2>
			<p><?php esc_html_e( 'Generate or refresh the /ai-policy/ page so llms.txt can declare licensing, citation, and contact rules for AI systems.', 'nerv-core' ); ?></p>
			<?php submit_button( $ai_policy_exists ? __( 'Refresh AI Policy Page', 'nerv-core' ) : __( 'Generate AI Policy Page', 'nerv-core' ), 'secondary' ); ?>
		</form>

		<form method="post" action="options.php" style="max-width: 920px; margin-top: 24px;">
			<?php settings_fields( 'nerv_core_settings' ); ?>
			<h2><?php esc_html_e( 'Related Entries', 'nerv-core' ); ?></h2>
			<p><?php esc_html_e( 'Tune the semantic link engine used by article pages and hidden GEO resource links.', 'nerv-core' ); ?></p>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'nerv-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="nerv_core_related_options[enabled]" value="1" <?php checked( ! empty( $related_options['enabled'] ) ); ?>>
								<?php esc_html_e( 'Enable related entries panel and GEO related links', 'nerv-core' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nerv-core-related-title"><?php esc_html_e( 'Panel title', 'nerv-core' ); ?></label>
						</th>
						<td>
							<input id="nerv-core-related-title" class="regular-text" type="text" name="nerv_core_related_options[title]" value="<?php echo esc_attr( (string) ( $related_options['title'] ?? '' ) ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nerv-core-related-limit"><?php esc_html_e( 'Entries count', 'nerv-core' ); ?></label>
						</th>
						<td>
							<input id="nerv-core-related-limit" class="small-text" type="number" min="1" max="12" name="nerv_core_related_options[limit]" value="<?php echo esc_attr( (string) ( $related_options['limit'] ?? 3 ) ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Algorithm weights', 'nerv-core' ); ?></th>
						<td>
							<label for="nerv-core-related-category-weight"><?php esc_html_e( 'Same category', 'nerv-core' ); ?></label>
							<input id="nerv-core-related-category-weight" class="small-text" type="number" min="0" max="20" name="nerv_core_related_options[category_weight]" value="<?php echo esc_attr( (string) ( $related_options['category_weight'] ?? 2 ) ); ?>">
							&nbsp;
							<label for="nerv-core-related-tag-weight"><?php esc_html_e( 'Shared tag', 'nerv-core' ); ?></label>
							<input id="nerv-core-related-tag-weight" class="small-text" type="number" min="0" max="20" name="nerv_core_related_options[tag_weight]" value="<?php echo esc_attr( (string) ( $related_options['tag_weight'] ?? 1 ) ); ?>">
							&nbsp;
							<label for="nerv-core-related-recent-weight"><?php esc_html_e( 'Recent post', 'nerv-core' ); ?></label>
							<input id="nerv-core-related-recent-weight" class="small-text" type="number" min="0" max="20" name="nerv_core_related_options[recent_weight]" value="<?php echo esc_attr( (string) ( $related_options['recent_weight'] ?? 1 ) ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Time windows', 'nerv-core' ); ?></th>
						<td>
							<label for="nerv-core-related-recent-days"><?php esc_html_e( 'Recent days', 'nerv-core' ); ?></label>
							<input id="nerv-core-related-recent-days" class="small-text" type="number" min="1" max="3650" name="nerv_core_related_options[recent_days]" value="<?php echo esc_attr( (string) ( $related_options['recent_days'] ?? 180 ) ); ?>">
							&nbsp;
							<label for="nerv-core-related-cache-hours"><?php esc_html_e( 'Cache hours', 'nerv-core' ); ?></label>
							<input id="nerv-core-related-cache-hours" class="small-text" type="number" min="1" max="168" name="nerv_core_related_options[cache_hours]" value="<?php echo esc_attr( (string) ( $related_options['cache_hours'] ?? 12 ) ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Excluded categories', 'nerv-core' ); ?></th>
						<td>
							<?php if ( $categories ) : ?>
								<fieldset>
									<?php foreach ( $categories as $category ) : ?>
										<label style="display: inline-block; min-width: 180px; margin: 0 16px 8px 0;">
											<input type="checkbox" name="nerv_core_related_options[excluded_categories][]" value="<?php echo esc_attr( (string) $category->term_id ); ?>" <?php checked( in_array( (int) $category->term_id, (array) ( $related_options['excluded_categories'] ?? array() ), true ) ); ?>>
											<?php echo esc_html( $category->name ); ?>
										</label>
									<?php endforeach; ?>
								</fieldset>
							<?php else : ?>
								<p><?php esc_html_e( 'No categories available yet.', 'nerv-core' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button( __( 'Save NERV Settings', 'nerv-core' ) ); ?>
		</form>

		<form method="post" action="options.php" style="max-width: 920px; margin-top: 28px;">
			<?php settings_fields( 'nerv_core_settings' ); ?>
			<h2><?php esc_html_e( 'AI Services / Cover Pipeline', 'nerv-core' ); ?></h2>
			<p><?php esc_html_e( 'Configure an OpenAI images-compatible service. Without credentials, uploaded covers and SVG fallback remain active and publishing is never blocked.', 'nerv-core' ); ?></p>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="nerv-core-cover-endpoint"><?php esc_html_e( 'API endpoint', 'nerv-core' ); ?></label>
						</th>
						<td>
							<input id="nerv-core-cover-endpoint" class="regular-text code" type="url" name="nerv_core_cover_options[endpoint]" value="<?php echo esc_url( (string) ( $cover_options['endpoint'] ?? '' ) ); ?>" placeholder="https://api.openai.com/v1/images/generations">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nerv-core-cover-key"><?php esc_html_e( 'API key', 'nerv-core' ); ?></label>
						</th>
						<td>
							<input id="nerv-core-cover-key" class="regular-text code" type="password" name="nerv_core_cover_options[api_key]" value="" autocomplete="new-password" placeholder="<?php echo ! empty( $cover_options['api_key'] ) ? esc_attr__( 'Saved; leave blank to keep current key.', 'nerv-core' ) : ''; ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nerv-core-cover-model"><?php esc_html_e( 'Model', 'nerv-core' ); ?></label>
						</th>
						<td>
							<input id="nerv-core-cover-model" class="regular-text code" type="text" name="nerv_core_cover_options[model]" value="<?php echo esc_attr( (string) ( $cover_options['model'] ?? '' ) ); ?>" placeholder="gpt-image-1">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nerv-core-cover-prompt"><?php esc_html_e( 'Prompt template', 'nerv-core' ); ?></label>
						</th>
						<td>
							<textarea id="nerv-core-cover-prompt" class="large-text code" rows="4" name="nerv_core_cover_options[prompt_template]"><?php echo esc_textarea( (string) ( $cover_options['prompt_template'] ?? '' ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Available placeholders: {title}, {subtitle}, {excerpt}, {category}.', 'nerv-core' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Automation', 'nerv-core' ); ?></th>
						<td>
							<label style="display:block;margin-bottom:8px;">
								<input type="checkbox" name="nerv_core_cover_options[auto_generate]" value="1" <?php checked( ! empty( $cover_options['auto_generate'] ) ); ?>>
								<?php esc_html_e( 'Auto-generate covers when no featured image exists', 'nerv-core' ); ?>
							</label>
							<label style="display:block;margin-bottom:8px;">
								<input type="checkbox" name="nerv_core_cover_options[key_points_auto]" value="1" <?php checked( ! empty( $cover_options['key_points_auto'] ) ); ?>>
								<?php esc_html_e( 'Enable KEY POINTS AI generation', 'nerv-core' ); ?>
							</label>
							<label style="display:block;">
								<input type="checkbox" name="nerv_core_cover_options[dry_run]" value="1" <?php checked( ! empty( $cover_options['dry_run'] ) ); ?>>
								<?php esc_html_e( 'Dry-run AI calls until production credentials are ready', 'nerv-core' ); ?>
							</label>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button( __( 'Save AI Service Settings', 'nerv-core' ) ); ?>
		</form>

		<form method="post" action="options.php" style="max-width: 920px; margin-top: 28px;">
			<?php settings_fields( 'nerv_core_settings' ); ?>
			<h2><?php esc_html_e( 'IndexNow', 'nerv-core' ); ?></h2>
			<p><?php esc_html_e( 'Ping search engines when public posts and projects are published or updated. Localhost runs are recorded as dry-run logs.', 'nerv-core' ); ?></p>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'nerv-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="nerv_core_indexnow_options[enabled]" value="1" <?php checked( ! empty( $indexnow_options['enabled'] ) ); ?>>
								<?php esc_html_e( 'Enable IndexNow pings on publish/update', 'nerv-core' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nerv-core-indexnow-key"><?php esc_html_e( 'Key', 'nerv-core' ); ?></label>
						</th>
						<td>
							<input id="nerv-core-indexnow-key" class="regular-text code" type="text" name="nerv_core_indexnow_options[key]" value="<?php echo esc_attr( (string) ( $indexnow_options['key'] ?? '' ) ); ?>">
							<?php if ( function_exists( 'nerv_core_indexnow_key_url' ) ) : ?>
								<p class="description"><?php echo esc_html( nerv_core_indexnow_key_url() ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nerv-core-indexnow-endpoint"><?php esc_html_e( 'Endpoint', 'nerv-core' ); ?></label>
						</th>
						<td>
							<input id="nerv-core-indexnow-endpoint" class="regular-text code" type="url" name="nerv_core_indexnow_options[endpoint]" value="<?php echo esc_url( (string) ( $indexnow_options['endpoint'] ?? '' ) ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Local safety', 'nerv-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="nerv_core_indexnow_options[dry_run]" value="1" <?php checked( ! empty( $indexnow_options['dry_run'] ) ); ?>>
								<?php esc_html_e( 'Dry-run only; record logs without external submission', 'nerv-core' ); ?>
							</label>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button( __( 'Save IndexNow Settings', 'nerv-core' ) ); ?>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width: 920px;">
			<input type="hidden" name="action" value="nerv_core_indexnow_test">
			<?php wp_nonce_field( 'nerv_core_indexnow_test' ); ?>
			<p><?php esc_html_e( 'Send a manual IndexNow TEST ping for the AI policy URL when available, otherwise the site home URL. Localhost remains dry-run only.', 'nerv-core' ); ?></p>
			<?php submit_button( __( 'Run IndexNow TEST', 'nerv-core' ), 'secondary' ); ?>
		</form>

		<form method="post" action="options.php" style="max-width: 920px; margin-top: 28px;">
			<?php settings_fields( 'nerv_core_settings' ); ?>
			<h2><?php esc_html_e( 'AI Crawler Monitor', 'nerv-core' ); ?></h2>
			<p><?php esc_html_e( 'Count known AI crawler user agents and show the latest pages they requested. This proves GEO discovery is happening.', 'nerv-core' ); ?></p>
			<p><?php esc_html_e( 'Checked bots are monitored and allowed in robots.txt; unchecked bots are blocked with Disallow: /.', 'nerv-core' ); ?></p>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'nerv-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="nerv_core_geo_crawler_options[enabled]" value="1" <?php checked( ! empty( $crawler_options['enabled'] ) ); ?>>
								<?php esc_html_e( 'Enable AI crawler monitoring', 'nerv-core' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nerv-core-crawler-retention-days"><?php esc_html_e( 'Data retention', 'nerv-core' ); ?></label>
						</th>
						<td>
							<input id="nerv-core-crawler-retention-days" class="small-text" type="number" min="1" max="365" name="nerv_core_geo_crawler_options[retention_days]" value="<?php echo esc_attr( (string) ( $crawler_options['retention_days'] ?? 30 ) ); ?>">
							<?php esc_html_e( 'days', 'nerv-core' ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Tracked bots', 'nerv-core' ); ?></th>
						<td>
							<fieldset>
								<?php foreach ( $crawler_bots as $key => $bot ) : ?>
									<label style="display: inline-block; min-width: 180px; margin: 0 16px 8px 0;">
										<input type="checkbox" name="nerv_core_geo_crawler_options[bots][<?php echo esc_attr( (string) $key ); ?>]" value="1" <?php checked( ! empty( $crawler_options['bots'][ $key ] ) ); ?>>
										<?php echo esc_html( (string) ( $bot['label'] ?? $key ) ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button( __( 'Save Crawler Monitor Settings', 'nerv-core' ) ); ?>
		</form>

		<h2 style="margin-top: 28px;"><?php esc_html_e( 'AI Crawler Stats', 'nerv-core' ); ?></h2>
		<table class="widefat striped" style="max-width: 920px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Bot', 'nerv-core' ); ?></th>
					<th><?php esc_html_e( 'Last 7 Days', 'nerv-core' ); ?></th>
					<th><?php esc_html_e( 'Total', 'nerv-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $crawler_bots as $key => $bot ) : ?>
					<tr>
						<td><?php echo esc_html( (string) ( $bot['label'] ?? $key ) ); ?></td>
						<td><?php echo esc_html( (string) absint( $crawler_summary['window'][ $key ] ?? 0 ) ); ?></td>
						<td><?php echo esc_html( (string) absint( $crawler_summary['totals'][ $key ] ?? 0 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th><?php esc_html_e( 'All tracked bots', 'nerv-core' ); ?></th>
					<th><?php echo esc_html( (string) absint( $crawler_summary['total'] ?? 0 ) ); ?></th>
					<th><?php echo esc_html( (string) array_sum( array_map( 'absint', (array) ( $crawler_summary['totals'] ?? array() ) ) ) ); ?></th>
				</tr>
			</tbody>
		</table>

		<h2 style="margin-top: 28px;"><?php esc_html_e( 'Recent AI Crawls', 'nerv-core' ); ?></h2>
		<table class="widefat striped" style="max-width: 920px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Time', 'nerv-core' ); ?></th>
					<th><?php esc_html_e( 'Bot', 'nerv-core' ); ?></th>
					<th><?php esc_html_e( 'Page', 'nerv-core' ); ?></th>
					<th><?php esc_html_e( 'User Agent', 'nerv-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $crawler_summary['recent'] ) ) : ?>
					<?php foreach ( array_slice( (array) $crawler_summary['recent'], 0, 8 ) as $entry ) : ?>
						<tr>
							<td><?php echo esc_html( (string) ( $entry['time'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $entry['label'] ?? $entry['bot'] ?? '' ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( (string) ( $entry['url'] ?? '' ) ); ?>">
									<?php echo esc_html( (string) ( $entry['title'] ?? $entry['url'] ?? '' ) ); ?>
								</a>
							</td>
							<td><code><?php echo esc_html( (string) ( $entry['ua'] ?? '' ) ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No AI crawler visits recorded yet.', 'nerv-core' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>

		<form method="post" action="options.php" style="max-width: 920px; margin-top: 28px;">
			<?php settings_fields( 'nerv_core_settings' ); ?>
			<h2><?php esc_html_e( 'Partner Display', 'nerv-core' ); ?></h2>
			<p><?php esc_html_e( 'Control the partner footer row, shortcode panel, application block, and llms.txt partner index.', 'nerv-core' ); ?></p>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Footer featured row', 'nerv-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="nerv_core_partner_display_options[footer_enabled]" value="1" <?php checked( ! empty( $partner_display_options['footer_enabled'] ) ); ?>>
								<?php esc_html_e( 'Show featured partners in the terminal footer', 'nerv-core' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nerv-core-partner-footer-limit"><?php esc_html_e( 'Footer count', 'nerv-core' ); ?></label>
						</th>
						<td>
							<input id="nerv-core-partner-footer-limit" class="small-text" type="number" min="1" max="12" name="nerv_core_partner_display_options[footer_limit]" value="<?php echo esc_attr( (string) ( $partner_display_options['footer_limit'] ?? 4 ) ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Application block', 'nerv-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="nerv_core_partner_display_options[application_enabled]" value="1" <?php checked( ! empty( $partner_display_options['application_enabled'] ) ); ?>>
								<?php esc_html_e( 'Show an allied-link application block on the partners page', 'nerv-core' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nerv-core-partner-application-email"><?php esc_html_e( 'Application email', 'nerv-core' ); ?></label>
						</th>
						<td>
							<input id="nerv-core-partner-application-email" class="regular-text" type="email" name="nerv_core_partner_display_options[application_email]" value="<?php echo esc_attr( (string) ( $partner_display_options['application_email'] ?? get_option( 'admin_email' ) ) ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nerv-core-partner-application-text"><?php esc_html_e( 'Application text', 'nerv-core' ); ?></label>
						</th>
						<td>
							<textarea id="nerv-core-partner-application-text" class="large-text" rows="3" name="nerv_core_partner_display_options[application_text]"><?php echo esc_textarea( (string) ( $partner_display_options['application_text'] ?? '' ) ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'GEO partner index', 'nerv-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="nerv_core_partner_display_options[llms_include]" value="1" <?php checked( ! empty( $partner_display_options['llms_include'] ) ); ?>>
								<?php esc_html_e( 'Include partners in llms.txt', 'nerv-core' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Default is off so llms.txt stays focused on owned content unless you opt in.', 'nerv-core' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<p>
				<code>[nerv_partners]</code>
				<code>[nerv_partners limit="4" featured="1"]</code>
			</p>
			<?php submit_button( __( 'Save Partner Display Settings', 'nerv-core' ) ); ?>
		</form>

		<form method="post" action="options.php" style="max-width: 920px; margin-top: 28px;">
			<?php settings_fields( 'nerv_core_settings' ); ?>
			<h2><?php esc_html_e( 'Partner Health', 'nerv-core' ); ?></h2>
			<p><?php esc_html_e( 'Probe partner URLs and show ONLINE, SLOW, or OFFLINE status lights on the partner grid.', 'nerv-core' ); ?></p>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'nerv-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="nerv_core_partner_health_options[enabled]" value="1" <?php checked( ! empty( $partner_health_options['enabled'] ) ); ?>>
								<?php esc_html_e( 'Enable scheduled partner health checks', 'nerv-core' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nerv-core-partner-health-timeout"><?php esc_html_e( 'Timeout', 'nerv-core' ); ?></label>
						</th>
						<td>
							<input id="nerv-core-partner-health-timeout" class="small-text" type="number" min="1" max="20" name="nerv_core_partner_health_options[timeout]" value="<?php echo esc_attr( (string) ( $partner_health_options['timeout'] ?? 5 ) ); ?>">
							<?php esc_html_e( 'seconds', 'nerv-core' ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="nerv-core-partner-health-slow"><?php esc_html_e( 'Slow threshold', 'nerv-core' ); ?></label>
						</th>
						<td>
							<input id="nerv-core-partner-health-slow" class="small-text" type="number" min="0.5" max="10" step="0.5" name="nerv_core_partner_health_options[slow_seconds]" value="<?php echo esc_attr( (string) ( $partner_health_options['slow_seconds'] ?? 2.5 ) ); ?>">
							<?php esc_html_e( 'seconds', 'nerv-core' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button( __( 'Save Partner Health Settings', 'nerv-core' ) ); ?>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width: 920px;">
			<input type="hidden" name="action" value="nerv_core_partner_health_test">
			<?php wp_nonce_field( 'nerv_core_partner_health_test' ); ?>
			<p><?php esc_html_e( 'Run a partner health TEST now and refresh the status lights.', 'nerv-core' ); ?></p>
			<?php submit_button( __( 'Run Partner Health TEST', 'nerv-core' ), 'secondary' ); ?>
		</form>

		<h2 style="margin-top: 28px;"><?php esc_html_e( 'Partner Health Status', 'nerv-core' ); ?></h2>
		<table class="widefat striped" style="max-width: 920px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Partner', 'nerv-core' ); ?></th>
					<th><?php esc_html_e( 'Status', 'nerv-core' ); ?></th>
					<th><?php esc_html_e( 'Message', 'nerv-core' ); ?></th>
					<th><?php esc_html_e( 'Checked', 'nerv-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $partner_health_posts ) : ?>
					<?php foreach ( $partner_health_posts as $partner ) : ?>
						<?php $health = function_exists( 'nerv_core_partner_health_status' ) ? nerv_core_partner_health_status( (int) $partner->ID ) : array(); ?>
						<tr>
							<td><?php echo esc_html( get_the_title( $partner ) ); ?></td>
							<td><?php echo esc_html( function_exists( 'nerv_core_partner_health_status_label' ) ? nerv_core_partner_health_status_label( (string) ( $health['status'] ?? 'online' ) ) : (string) ( $health['status'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $health['message'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $health['checked'] ?? '' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No partners available yet.', 'nerv-core' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>

		<h2 style="margin-top: 28px;"><?php esc_html_e( 'IndexNow Log', 'nerv-core' ); ?></h2>
		<table class="widefat striped" style="max-width: 920px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Time', 'nerv-core' ); ?></th>
					<th><?php esc_html_e( 'Status', 'nerv-core' ); ?></th>
					<th><?php esc_html_e( 'Message', 'nerv-core' ); ?></th>
					<th><?php esc_html_e( 'URLs', 'nerv-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $indexnow_log ) : ?>
					<?php foreach ( array_slice( $indexnow_log, 0, 8 ) as $entry ) : ?>
						<tr>
							<td><?php echo esc_html( (string) ( $entry['time'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $entry['status'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $entry['message'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( implode( ', ', (array) ( $entry['urls'] ?? array() ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No IndexNow events recorded yet.', 'nerv-core' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
