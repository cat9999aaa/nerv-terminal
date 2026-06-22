<?php
/**
 * GEO title slug suggestions.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_geo_title_default_options(): array {
	return array(
		'enabled'         => true,
		'max_length'      => 60,
		'max_words'       => 6,
		'detect_encoded'  => true,
		'detect_nonascii' => true,
		'detect_long'     => true,
		'prompt_template' => "You are an SEO and GEO expert. Translate the following article title into a concise lowercase English URL slug. Use 3 to 6 meaningful keywords, hyphen-separated. Return only the slug.\n\nTitle: {title}",
	);
}

function nerv_core_geo_title_options(): array {
	$options = get_option( 'nerv_core_geo_title_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	return wp_parse_args( $options, nerv_core_geo_title_default_options() );
}

function nerv_core_geo_title_evaluate_slug( string $slug ): array {
	$options = nerv_core_geo_title_options();
	if ( ! empty( $options['detect_encoded'] ) && preg_match( '/%[0-9a-fA-F]{2}/', $slug ) ) {
		return array( 'compliant' => false, 'reason' => '含 %xx 编码' );
	}
	if ( ! empty( $options['detect_nonascii'] ) && preg_match( '/[^\x00-\x7F]/', $slug ) ) {
		return array( 'compliant' => false, 'reason' => '含非 ASCII 字符' );
	}
	if ( ! empty( $options['detect_long'] ) && strlen( $slug ) > absint( $options['max_length'] ?? 60 ) ) {
		return array( 'compliant' => false, 'reason' => 'slug 过长' );
	}
	if ( '' === $slug || preg_match( '/^[0-9\-]+$/', $slug ) ) {
		return array( 'compliant' => false, 'reason' => '空或纯数字 slug' );
	}

	return array( 'compliant' => true, 'reason' => '' );
}

function nerv_core_geo_title_sanitize_slug( string $raw, int $post_id = 0 ): string {
	$options = nerv_core_geo_title_options();
	$raw = trim( preg_replace( '/^```.*$/m', '', $raw ) ?? $raw );
	$raw = trim( preg_replace( '/^(slug|url)\s*[:：]\s*/i', '', $raw ) ?? $raw, " \t\n\r\"'`" );
	$raw = (string) ( preg_split( '/\r\n|\r|\n/', $raw )[0] ?? $raw );
	$slug = sanitize_title( $raw );

	$parts = array_values( array_filter( explode( '-', $slug ) ) );
	$max_words = max( 1, absint( $options['max_words'] ?? 6 ) );
	$slug = implode( '-', array_slice( $parts, 0, $max_words ) );

	$max_length = max( 24, absint( $options['max_length'] ?? 60 ) );
	if ( strlen( $slug ) > $max_length ) {
		$slug = substr( $slug, 0, $max_length );
		$slug = preg_replace( '/-[^-]*$/', '', $slug ) ?: $slug;
	}
	$slug = trim( $slug, '-' );
	if ( '' === $slug ) {
		return '';
	}

	$post = $post_id ? get_post( $post_id ) : null;
	return wp_unique_post_slug( $slug, $post_id, $post ? $post->post_status : 'publish', $post ? $post->post_type : 'post', $post ? $post->post_parent : 0 );
}

function nerv_core_geo_title_candidate_posts( int $limit = 20 ): array {
	$posts = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'posts_per_page'   => max( 1, min( 100, $limit ) ),
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
		)
	);

	$rows = array();
	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		$evaluation = nerv_core_geo_title_evaluate_slug( (string) $post->post_name );
		if ( ! empty( $evaluation['compliant'] ) ) {
			continue;
		}
		$rows[] = array(
			'id'     => (int) $post->ID,
			'title'  => get_the_title( $post ),
			'slug'   => (string) $post->post_name,
			'reason' => (string) $evaluation['reason'],
			'url'    => get_permalink( $post ),
		);
	}

	return $rows;
}

function nerv_core_geo_title_suggest_next() {
	$candidates = nerv_core_geo_title_candidate_posts( 50 );
	if ( ! $candidates ) {
		return new WP_Error( 'nerv_geo_title_empty', '没有发现需要优化的文章 slug。' );
	}

	$row = $candidates[0];
	$options = nerv_core_geo_title_options();
	$cover_options = function_exists( 'nerv_core_cover_options' ) ? nerv_core_cover_options() : array();
	$prompt = strtr( (string) ( $options['prompt_template'] ?? '' ), array( '{title}' => (string) $row['title'] ) );
	$system = 'You convert WordPress titles into clean English URL slugs. Output only the slug.';
	$response = function_exists( 'nerv_core_ai_chat_request' ) ? nerv_core_ai_chat_request( $system, $prompt, $cover_options ) : new WP_Error( 'nerv_geo_title_ai_missing', 'AI 路由不可用。' );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$suggested = nerv_core_geo_title_sanitize_slug( (string) ( $response['text'] ?? '' ), (int) $row['id'] );
	if ( '' === $suggested ) {
		return new WP_Error( 'nerv_geo_title_bad_slug', 'AI 返回内容无法生成有效 slug。' );
	}

	update_post_meta(
		(int) $row['id'],
		'_nerv_geo_title_suggestion',
		array(
			'time'      => current_time( 'mysql' ),
			'slug'      => $suggested,
			'model'     => sanitize_text_field( (string) ( $response['model'] ?? '' ) ),
			'old_slug'  => sanitize_title( (string) $row['slug'] ),
			'old_url'   => esc_url_raw( (string) $row['url'] ),
			'prompt'    => sanitize_textarea_field( $prompt ),
			'status'    => 'pending',
		)
	);

	$row['suggested'] = $suggested;
	$row['model'] = sanitize_text_field( (string) ( $response['model'] ?? '' ) );
	return $row;
}
