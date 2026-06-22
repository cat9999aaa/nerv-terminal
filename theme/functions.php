<?php
/**
 * NERV Terminal theme bootstrap.
 *
 * @package NervTerminal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NERV_TERMINAL_VERSION', '0.1.7' );
define( 'NERV_TERMINAL_DIR', get_template_directory() );
define( 'NERV_TERMINAL_URI', get_template_directory_uri() );
define( 'NERV_TERMINAL_REWRITE_VERSION', '20260623-blog-md-routes' );

require_once NERV_TERMINAL_DIR . '/inc/defaults.php';
require_once NERV_TERMINAL_DIR . '/inc/dashboard-render.php';
require_once NERV_TERMINAL_DIR . '/inc/updater.php';

add_action( 'after_setup_theme', 'nerv_terminal_setup' );
function nerv_terminal_setup(): void {
	load_theme_textdomain( 'nerv-terminal', NERV_TERMINAL_DIR . '/languages' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/frontend.css' );
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'nerv-thumb-square', 900, 900, true );
	add_image_size( 'nerv-cover', 1500, 600, true );
	add_image_size( 'nerv-og', 1200, 600, true );
}

add_action( 'wp_enqueue_scripts', 'nerv_terminal_enqueue_assets' );
function nerv_terminal_enqueue_assets(): void {
	$desktop_effects   = function_exists( 'nerv_terminal_effect_view_options' ) ? nerv_terminal_effect_view_options( 'desktop' ) : nerv_terminal_effect_default_options();
	$mobile_effects    = function_exists( 'nerv_terminal_effect_view_options' ) ? nerv_terminal_effect_view_options( 'mobile' ) : nerv_terminal_effect_default_options();
	$desktop_intensity = (float) ( $desktop_effects['intensity'] ?? 65 ) / 100;
	$mobile_intensity  = (float) ( $mobile_effects['intensity'] ?? 35 ) / 100;
	$font_css_url      = nerv_terminal_string( 'font_css_url' );

	wp_enqueue_style(
		'nerv-terminal-frontend',
		NERV_TERMINAL_URI . '/assets/css/frontend.bundle.css',
		array(),
		nerv_terminal_asset_version( NERV_TERMINAL_DIR . '/assets/css/frontend.bundle.css' )
	);

	if ( $font_css_url && wp_http_validate_url( $font_css_url ) ) {
		wp_enqueue_style( 'nerv-terminal-custom-fonts', esc_url_raw( $font_css_url ), array(), null );
	}

	wp_add_inline_style(
		'nerv-terminal-frontend',
		':root{--nerv-effect-intensity:' . esc_attr( (string) $desktop_intensity ) . ';--nerv-effect-intensity-mobile:' . esc_attr( (string) $mobile_intensity ) . ';--nerv-font-body:' . nerv_terminal_font_stack( 'font_body_family' ) . ';--nerv-font-heading:' . nerv_terminal_font_stack( 'font_heading_family' ) . ';--nerv-font-mono:' . nerv_terminal_font_stack( 'font_mono_family' ) . ';}@media (max-width:767px){:root{--nerv-effect-intensity:var(--nerv-effect-intensity-mobile);}}'
	);

	wp_enqueue_script(
		'nerv-terminal-frontend',
		NERV_TERMINAL_URI . '/assets/js/frontend.js',
		array(),
		nerv_terminal_asset_version( NERV_TERMINAL_DIR . '/assets/js/frontend.js' ),
		true
	);
}

add_filter( 'language_attributes', 'nerv_terminal_language_attributes' );
function nerv_terminal_language_attributes( string $output ): string {
	if ( str_contains( $output, 'data-theme=' ) ) {
		return $output;
	}

	$theme   = function_exists( 'nerv_terminal_appearance_theme_attribute' ) ? nerv_terminal_appearance_theme_attribute() : 'void';
	$palette = function_exists( 'nerv_terminal_appearance_palette_attribute' ) ? nerv_terminal_appearance_palette_attribute() : 'hazard';

	return trim( $output . ' data-theme="' . esc_attr( $theme ) . '" data-palette="' . esc_attr( $palette ) . '"' );
}

function nerv_terminal_asset_version( string $path ): string {
	$mtime = is_file( $path ) ? (int) filemtime( $path ) : 0;
	if ( is_dir( $path ) ) {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $iterator as $file ) {
			if ( $file instanceof SplFileInfo && $file->isFile() ) {
				$mtime = max( $mtime, (int) $file->getMTime() );
			}
		}
	}

	return NERV_TERMINAL_VERSION . '-' . (string) $mtime;
}

function nerv_terminal_font_stack( string $key ): string {
	$value = nerv_terminal_string( $key );
	$value = preg_replace( '/[{};<>]/', '', $value ) ?: '';

	return trim( $value ) ?: '"JetBrains Mono", "Noto Sans SC", "Microsoft YaHei", monospace';
}

add_action( 'init', 'nerv_terminal_register_blocks' );
function nerv_terminal_register_blocks(): void {
	register_block_type(
		'nerv-terminal/dashboard',
		array(
			'api_version'     => 3,
			'render_callback' => 'nerv_terminal_render_dashboard_block',
			'attributes'      => array(),
		)
	);
}

add_action( 'wp_head', 'nerv_terminal_head_meta', 1 );
function nerv_terminal_head_meta(): void {
	$apple_icon_size = nerv_terminal_icon_size_setting( 'pwa_icon_apple_size', 180 );
	$pwa_icon        = nerv_terminal_manifest_icon_url( $apple_icon_size );
	echo '<meta name="theme-color" content="' . esc_attr( nerv_terminal_string( 'pwa_theme_color' ) ) . '">' . "\n";
	echo '<link rel="manifest" href="' . esc_url( add_query_arg( 'nerv_manifest', '1', home_url( '/' ) ) ) . '">' . "\n";
	echo '<link rel="apple-touch-icon" sizes="' . esc_attr( $apple_icon_size . 'x' . $apple_icon_size ) . '" href="' . esc_url( $pwa_icon ) . '">' . "\n";
	echo '<link rel="icon" href="' . esc_url( $pwa_icon ?: 'data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 64 64%27%3E%3Crect width=%2764%27 height=%2764%27 fill=%27%23050403%27/%3E%3Cpath d=%27M12 48 28 8l24 48H40l-4-10H24l-4 10z%27 fill=%27%23ff3b30%27/%3E%3C/svg%3E' ) . '">' . "\n";

	if ( ! nerv_terminal_should_defer_seo_meta() ) {
		$description = nerv_terminal_meta_description();
		if ( $description ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		}
		echo nerv_terminal_social_meta_tags( $description );
	}

	if ( function_exists( 'nerv_core_geo_json_feed_url' ) ) {
		echo '<link rel="alternate" type="application/feed+json" href="' . esc_url( nerv_core_geo_json_feed_url() ) . '">' . "\n";
	}

	if ( is_singular() && function_exists( 'nerv_core_geo_markdown_url' ) ) {
		$post_id = get_queried_object_id();
		if ( $post_id ) {
			echo '<link rel="alternate" type="text/markdown" href="' . esc_url( nerv_core_geo_markdown_url( $post_id ) ) . '">' . "\n";
		}
	}
}

function nerv_terminal_should_defer_seo_meta(): bool {
	$seo_options = function_exists( 'nerv_core_seo_options' ) ? nerv_core_seo_options() : array();
	if ( array_key_exists( 'enabled', $seo_options ) && empty( $seo_options['enabled'] ) ) {
		return true;
	}
	if ( ! empty( $seo_options['defer_to_seo_plugin'] ) ) {
		return nerv_terminal_detect_seo_plugin();
	}
	if ( function_exists( 'nerv_core_geo_should_defer_to_seo_plugin' ) && nerv_core_geo_should_defer_to_seo_plugin() ) {
		return true;
	}

	return nerv_terminal_detect_seo_plugin();
}

function nerv_terminal_detect_seo_plugin(): bool {
	$active = (array) get_option( 'active_plugins', array() );
	$seo_plugins = array(
		'wordpress-seo/wp-seo.php',
		'wordpress-seo-premium/wp-seo-premium.php',
		'seo-by-rank-math/rank-math.php',
		'all-in-one-seo-pack/all_in_one_seo_pack.php',
		'autodescription/autodescription.php',
	);

	return (bool) array_intersect( $active, $seo_plugins );
}

function nerv_terminal_social_meta_tags( string $description ): string {
	$title = is_singular() ? wp_get_document_title() : get_bloginfo( 'name' );
	$url   = is_singular() ? get_permalink( get_queried_object_id() ) : home_url( '/' );
	$image = nerv_terminal_social_image_url();
	$tags  = array(
		'og:type'          => is_singular() ? 'article' : 'website',
		'og:title'         => $title,
		'og:description'   => $description,
		'og:url'           => $url,
		'og:site_name'     => get_bloginfo( 'name' ),
		'og:image'         => $image,
		'og:image:width'   => '1200',
		'og:image:height'  => '600',
		'twitter:card'     => 'summary_large_image',
		'twitter:title'    => $title,
		'twitter:description' => $description,
		'twitter:image'    => $image,
	);

	$html = '';
	foreach ( $tags as $property => $content ) {
		if ( '' === (string) $content ) {
			continue;
		}
		if ( str_starts_with( $property, 'twitter:' ) ) {
			$html .= '<meta name="' . esc_attr( $property ) . '" content="' . esc_attr( (string) $content ) . '">' . "\n";
		} else {
			$html .= '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( (string) $content ) . '">' . "\n";
		}
	}

	return $html;
}

function nerv_terminal_social_image_url(): string {
	$seo_options = function_exists( 'nerv_core_seo_options' ) ? nerv_core_seo_options() : array();
	if ( ! empty( $seo_options['default_og_image_id'] ) ) {
		$image = wp_get_attachment_image_url( absint( $seo_options['default_og_image_id'] ), 'nerv-og' );
		if ( $image ) {
			return $image;
		}
	}

	if ( is_singular() ) {
		$post_id = get_queried_object_id();
		if ( $post_id ) {
			$thumbnail_id = get_post_thumbnail_id( $post_id );
			if ( $thumbnail_id && function_exists( 'nerv_core_image_optimizer_attachment_social_url' ) ) {
				$image = nerv_core_image_optimizer_attachment_social_url( (int) $thumbnail_id );
				if ( $image ) {
					return $image;
				}
			}
			$thumbnail = get_the_post_thumbnail_url( $post_id, 'nerv-og' );
			if ( $thumbnail ) {
				return $thumbnail;
			}
		}
		if ( $post_id && function_exists( 'nerv_core_cover_url' ) ) {
			if ( function_exists( 'nerv_core_image_optimizer_social_cover_url' ) ) {
				$image = nerv_core_image_optimizer_social_cover_url( $post_id );
				if ( $image ) {
					return $image;
				}
			}

			return nerv_core_cover_url( $post_id, '2x1' );
		}
	}

	return nerv_terminal_manifest_icon_url( 512 );
}

function nerv_terminal_meta_description(): string {
	if ( is_singular() ) {
		$post_id = get_queried_object_id();
		if ( $post_id ) {
			return nerv_terminal_trim_meta_description( nerv_terminal_excerpt( $post_id, 36 ) );
		}
	}

	$description = get_bloginfo( 'description' );
	$seo_options = function_exists( 'nerv_core_seo_options' ) ? nerv_core_seo_options() : array();
	if ( '' === trim( $description ) ) {
		$description = (string) ( $seo_options['site_description'] ?? '' );
	}
	if ( '' === trim( $description ) ) {
		$description = nerv_terminal_string( 'meta_description' );
	}

	return nerv_terminal_trim_meta_description( $description );
}

function nerv_terminal_trim_meta_description( string $description ): string {
	$description = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $description ) ) ?: '' );
	if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
		return mb_strlen( $description ) > 155 ? mb_substr( $description, 0, 152 ) . '...' : $description;
	}

	return strlen( $description ) > 155 ? substr( $description, 0, 152 ) . '...' : $description;
}

add_action( 'send_headers', 'nerv_terminal_send_geo_headers' );
function nerv_terminal_send_geo_headers(): void {
	if ( ! is_singular() || ! function_exists( 'nerv_core_geo_markdown_url' ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return;
	}

	header( 'Link: <' . esc_url_raw( nerv_core_geo_markdown_url( $post_id ) ) . '>; rel="alternate"; type="text/markdown"', false );
}

add_action( 'init', 'nerv_terminal_register_runtime_routes' );
function nerv_terminal_register_runtime_routes(): void {
	add_rewrite_tag( '%nerv_manifest%', '1' );
	add_rewrite_tag( '%nerv_icon%', '1' );
	add_rewrite_tag( '%nerv_more%', '1' );
	add_rewrite_tag( '%nerv_view%', '([^&]+)' );
	add_rewrite_tag( '%size%', '[0-9]+' );
	foreach ( array( 'blog', 'about', 'gallery', 'contact', 'partners', 'projects' ) as $view ) {
		add_rewrite_rule( '^' . $view . '/?$', 'index.php?nerv_view=' . $view, 'top' );
		add_rewrite_rule( '^' . $view . '/page/([0-9]+)/?$', 'index.php?nerv_view=' . $view . '&paged=$matches[1]', 'top' );
	}
}

add_action( 'wp_loaded', 'nerv_terminal_maybe_flush_runtime_routes', 20 );
function nerv_terminal_maybe_flush_runtime_routes(): void {
	if ( get_option( 'nerv_terminal_rewrite_version' ) === NERV_TERMINAL_REWRITE_VERSION ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'nerv_terminal_rewrite_version', NERV_TERMINAL_REWRITE_VERSION, false );
}

add_action( 'pre_get_posts', 'nerv_terminal_prepare_runtime_view_query', 0 );
function nerv_terminal_prepare_runtime_view_query( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$view = sanitize_key( (string) $query->get( 'nerv_view' ) );
	if ( ! in_array( $view, array( 'blog', 'projects', 'partners' ), true ) ) {
		return;
	}

	$paged = max( 1, absint( $query->get( 'paged' ) ?: $query->get( 'page' ) ) );
	$query->set( 'paged', $paged );
	$query->set( 'page', 0 );
	$query->set( 'post_status', 'publish' );
	$query->set( 'ignore_sticky_posts', 'blog' === $view ? false : true );

	if ( 'blog' === $view ) {
		$query->set( 'post_type', 'post' );
		$query->set( 'posts_per_page', max( 1, (int) get_option( 'posts_per_page', 10 ) ) );
	} elseif ( 'projects' === $view ) {
		$query->set( 'post_type', post_type_exists( 'project' ) ? 'project' : 'post' );
		$query->set( 'posts_per_page', 12 );
	} elseif ( 'partners' === $view ) {
		$query->set( 'post_type', post_type_exists( 'partner' ) ? 'partner' : 'post' );
		$query->set( 'posts_per_page', 12 );
	}

	$query->is_home     = false;
	$query->is_archive  = true;
	$query->is_post_type_archive = false;
	$query->is_page     = false;
	$query->is_singular = false;
	$query->is_404      = false;
}

add_action( 'wp', 'nerv_terminal_clear_runtime_view_404', 0 );
function nerv_terminal_clear_runtime_view_404(): void {
	global $wp_query;

	if ( ! $wp_query instanceof WP_Query ) {
		return;
	}

	$view = sanitize_key( (string) get_query_var( 'nerv_view' ) );
	if ( ! in_array( $view, array( 'blog', 'projects', 'partners' ), true ) ) {
		return;
	}

	if ( $wp_query->is_404() && ( $wp_query->have_posts() || nerv_terminal_view_max_pages( $view ) > 0 ) ) {
		$wp_query->is_404 = false;
		status_header( 200 );
	}
}

add_action( 'template_redirect', 'nerv_terminal_runtime_responses', 0 );
function nerv_terminal_runtime_responses(): void {
	if ( get_query_var( 'nerv_manifest' ) ) {
		nerv_terminal_output_manifest();
	}

	if ( get_query_var( 'nerv_icon' ) ) {
		nerv_terminal_output_icon();
	}

	nerv_terminal_maybe_redirect_overflow_view_page();
}

add_filter( 'pre_handle_404', 'nerv_terminal_pre_handle_view_overflow_404', 0, 2 );
function nerv_terminal_pre_handle_view_overflow_404( bool $preempt, WP_Query $query ): bool {
	if ( ! $query->is_main_query() ) {
		return $preempt;
	}

	$view = nerv_terminal_view_from_request_path();
	if ( ! in_array( $view, array( 'blog', 'projects', 'partners' ), true ) ) {
		return $preempt;
	}

	$paged = max( 1, absint( $query->get( 'paged' ) ?: get_query_var( 'paged' ) ?: get_query_var( 'page' ) ) );
	if ( $paged < 2 ) {
		return $preempt;
	}

	$max_pages = nerv_terminal_view_max_pages( $view );
	if ( $max_pages > 0 && $paged > $max_pages ) {
		wp_safe_redirect( nerv_terminal_view_page_url( $view, $max_pages ), 301 );
		exit;
	}

	return $preempt;
}

function nerv_terminal_view_from_request_path(): string {
	$path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
	if ( preg_match( '~^(blog|projects|partners)(?:/page/[0-9]+)?/?$~', $path, $matches ) ) {
		return sanitize_key( (string) $matches[1] );
	}

	return '';
}

function nerv_terminal_maybe_redirect_overflow_view_page(): void {
	$view = nerv_terminal_current_view();
	if ( ! in_array( $view, array( 'blog', 'projects', 'partners' ), true ) ) {
		$view = nerv_terminal_view_from_request_path();
		if ( ! in_array( $view, array( 'blog', 'projects', 'partners' ), true ) ) {
			return;
		}
	}

	$paged = max( 1, absint( get_query_var( 'paged' ) ?: get_query_var( 'page' ) ) );
	if ( $paged < 2 ) {
		$request_path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
		if ( preg_match( '~^(?:blog|projects|partners)/page/([0-9]+)$~', $request_path, $matches ) ) {
			$paged = absint( $matches[1] );
		}
	}

	if ( $paged < 2 ) {
		return;
	}

	$max_pages = nerv_terminal_view_max_pages( $view );
	if ( $max_pages > 0 && $paged > $max_pages ) {
		wp_safe_redirect( nerv_terminal_view_page_url( $view, $max_pages ), 301 );
		exit;
	}
}

function nerv_terminal_view_max_pages( string $view ): int {
	$args = array(
		'post_status'         => 'publish',
		'fields'              => 'ids',
		'no_found_rows'       => false,
		'ignore_sticky_posts' => true,
	);

	if ( 'blog' === $view ) {
		$args['post_type'] = 'post';
		$args['posts_per_page'] = max( 1, (int) get_option( 'posts_per_page', 10 ) );
	} elseif ( 'projects' === $view ) {
		$args['post_type'] = post_type_exists( 'project' ) ? 'project' : 'post';
		$args['posts_per_page'] = 12;
	} elseif ( 'partners' === $view ) {
		$args['post_type'] = post_type_exists( 'partner' ) ? 'partner' : 'post';
		$args['posts_per_page'] = 12;
	} else {
		return 0;
	}

	$query = new WP_Query( $args );
	return max( 1, (int) $query->max_num_pages );
}

function nerv_terminal_view_page_url( string $view, int $page ): string {
	$base = nerv_terminal_view_url( $view );
	if ( $page <= 1 ) {
		return $base;
	}

	$url = trailingslashit( $base ) . 'page/' . $page;
	$permalink_structure = (string) get_option( 'permalink_structure' );
	if ( '' !== $permalink_structure && ! str_ends_with( $permalink_structure, '/' ) ) {
		return untrailingslashit( $url );
	}

	return user_trailingslashit( $url );
}

function nerv_terminal_view_url( string $view ): string {
	$url = home_url( '/' . trim( $view, '/' ) . '/' );
	$permalink_structure = (string) get_option( 'permalink_structure' );
	if ( '' !== $permalink_structure && ! str_ends_with( $permalink_structure, '/' ) ) {
		return untrailingslashit( $url );
	}

	return user_trailingslashit( $url );
}

function nerv_terminal_output_manifest(): void {
	$small_size = nerv_terminal_icon_size_setting( 'pwa_icon_small_size', 192 );
	$large_size = nerv_terminal_icon_size_setting( 'pwa_icon_large_size', 512 );
	$icon_192   = nerv_terminal_manifest_icon_url( $small_size );
	$icon_512   = nerv_terminal_manifest_icon_url( $large_size );
	$icon_type  = nerv_terminal_media_id( 'pwa_icon_id' ) && function_exists( 'nerv_terminal_media_mime_type' ) ? nerv_terminal_media_mime_type( 'pwa_icon_id' ) : 'image/svg+xml';
	$icon_type = $icon_type ?: 'image/png';
	$manifest = array(
		'name'             => nerv_terminal_string( 'pwa_name' ),
		'short_name'       => nerv_terminal_string( 'pwa_short_name' ),
		'start_url'        => home_url( '/' ),
		'scope'            => home_url( '/' ),
		'display'          => 'standalone',
		'background_color' => '#050403',
		'theme_color'      => nerv_terminal_string( 'pwa_theme_color' ),
		'orientation'      => 'portrait-primary',
		'icons'            => array(
			array(
				'src'     => esc_url_raw( $icon_192 ),
				'sizes'   => $small_size . 'x' . $small_size,
				'type'    => $icon_type,
				'purpose' => 'any',
			),
			array(
				'src'     => esc_url_raw( $icon_512 ),
				'sizes'   => $large_size . 'x' . $large_size,
				'type'    => $icon_type,
				'purpose' => 'any',
			),
		),
	);

	nocache_headers();
	header( 'Content-Type: application/manifest+json; charset=' . get_option( 'blog_charset' ) );
	echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}

function nerv_terminal_icon_size_setting( string $key, int $fallback ): int {
	$size = absint( nerv_terminal_string( $key ) );
	if ( ! $size ) {
		$size = $fallback;
	}

	return min( 1024, max( 64, $size ) );
}

function nerv_terminal_manifest_icon_url( int $size ): string {
	$attachment_id = function_exists( 'nerv_terminal_media_id' ) ? nerv_terminal_media_id( 'pwa_icon_id' ) : 0;
	if ( $attachment_id ) {
		$image = wp_get_attachment_image_src( $attachment_id, array( $size, $size ) );
		if ( is_array( $image ) && ! empty( $image[0] ) ) {
			return (string) $image[0];
		}
	}

	return add_query_arg( array( 'nerv_icon' => '1', 'size' => (string) $size ), home_url( '/' ) );
}

function nerv_terminal_output_icon(): void {
	$size = absint( get_query_var( 'size' ) );
	$size = $size > 0 ? min( 1024, max( 64, $size ) ) : 192;
	$svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 512 512" role="img" aria-label="NERV Terminal"><rect width="512" height="512" fill="#050403"/><path d="M96 390 224 80l192 352h-96l-32-78H192l-32 78H96Z" fill="#ff3b30"/><path d="M210 300h60l-30-74-30 74Z" fill="#050403"/><path d="M72 72h368v368H72V72Z" fill="none" stroke="#4ade80" stroke-width="10"/></svg>';

	nocache_headers();
	header( 'Content-Type: image/svg+xml; charset=UTF-8' );
	echo $svg;
	exit;
}

add_filter( 'body_class', 'nerv_terminal_body_class' );
function nerv_terminal_body_class( array $classes ): array {
	$effects = function_exists( 'nerv_terminal_effect_view_options' ) ? nerv_terminal_effect_view_options( wp_is_mobile() ? 'mobile' : 'desktop' ) : nerv_terminal_effect_default_options();

	$classes[] = 'nerv-terminal-theme';
	$classes[] = empty( $effects['enabled'] ) ? 'nerv-effects-disabled' : 'nerv-effects-enabled';
	$classes[] = wp_is_mobile() ? 'nerv-effects-device-mobile' : 'nerv-effects-device-desktop';

	foreach ( array( 'background_grid', 'scanlines', 'panel_glow', 'motion' ) as $key ) {
		if ( ! empty( $effects['enabled'] ) && ! empty( $effects[ $key ] ) ) {
			$classes[] = 'nerv-effect-' . str_replace( '_', '-', $key );
		}
	}

	return $classes;
}
