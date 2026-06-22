<?php
/**
 * Cover image pipeline: upload, generated URL, and SVG fallback.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_cover_default_options(): array {
	return array(
		'endpoint'         => '',
		'api_key'          => '',
		'model'            => '',
		'prompt_template'  => __( 'Create an original square editorial thumbnail for "{title}". Use a refined retro terminal interface mood, crisp typography space, no logos, no franchise references, no triangles. Subtitle: {subtitle}. Category: {category}. Excerpt: {excerpt}.', 'nerv-core' ),
		'auto_generate'    => false,
		'key_points_auto'  => false,
		'dry_run'          => true,
	);
}

function nerv_core_cover_options(): array {
	$options = get_option( 'nerv_core_cover_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	$options = wp_parse_args( $options, nerv_core_cover_default_options() );
	$options['endpoint'] = esc_url_raw( (string) ( $options['endpoint'] ?? '' ) );
	$options['api_key'] = nerv_core_cover_decrypt_secret( (string) ( $options['api_key'] ?? '' ) );
	$options['model'] = sanitize_text_field( (string) ( $options['model'] ?? '' ) );
	$options['prompt_template'] = sanitize_textarea_field( (string) ( $options['prompt_template'] ?? '' ) );
	$options['auto_generate'] = ! empty( $options['auto_generate'] );
	$options['key_points_auto'] = ! empty( $options['key_points_auto'] );
	$options['dry_run'] = ! empty( $options['dry_run'] );

	return $options;
}

function nerv_core_cover_sanitize_options( $input ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	$old_raw = get_option( 'nerv_core_cover_options', array() );
	if ( ! is_array( $old_raw ) ) {
		$old_raw = array();
	}
	$api_key = sanitize_text_field( (string) ( $input['api_key'] ?? '' ) );
	if ( '' === $api_key && ! empty( $old_raw['api_key'] ) ) {
		$api_key = (string) $old_raw['api_key'];
	} elseif ( '' !== $api_key ) {
		$api_key = nerv_core_cover_encrypt_secret( $api_key );
	}

	return array(
		'endpoint'         => esc_url_raw( (string) ( $input['endpoint'] ?? '' ) ),
		'api_key'          => $api_key,
		'model'            => sanitize_text_field( (string) ( $input['model'] ?? '' ) ),
		'prompt_template'  => sanitize_textarea_field( (string) ( $input['prompt_template'] ?? '' ) ),
		'auto_generate'    => ! empty( $input['auto_generate'] ),
		'key_points_auto'  => ! empty( $input['key_points_auto'] ),
		'dry_run'          => ! empty( $input['dry_run'] ),
	);
}

function nerv_core_cover_secret_key(): string {
	$material = ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' ) . '|' . ( defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '' ) . '|' . wp_salt( 'auth' );
	return hash( 'sha256', $material, true );
}

function nerv_core_cover_encrypt_secret( string $secret ): string {
	if ( '' === $secret ) {
		return '';
	}

	if ( str_starts_with( $secret, 'enc:v1:' ) ) {
		return $secret;
	}

	if ( ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'random_bytes' ) ) {
		return '';
	}

	$iv = random_bytes( 12 );
	$tag = '';
	$ciphertext = openssl_encrypt( $secret, 'aes-256-gcm', nerv_core_cover_secret_key(), OPENSSL_RAW_DATA, $iv, $tag );
	if ( false === $ciphertext || '' === $tag ) {
		return '';
	}

	return 'enc:v1:' . base64_encode( $iv . $tag . $ciphertext );
}

function nerv_core_cover_decrypt_secret( string $value ): string {
	if ( '' === $value ) {
		return '';
	}

	if ( ! str_starts_with( $value, 'enc:v1:' ) ) {
		return sanitize_text_field( $value );
	}

	if ( ! function_exists( 'openssl_decrypt' ) ) {
		return '';
	}

	$payload = base64_decode( substr( $value, 7 ), true );
	if ( false === $payload || strlen( $payload ) < 29 ) {
		return '';
	}

	$iv = substr( $payload, 0, 12 );
	$tag = substr( $payload, 12, 16 );
	$ciphertext = substr( $payload, 28 );
	$secret = openssl_decrypt( $ciphertext, 'aes-256-gcm', nerv_core_cover_secret_key(), OPENSSL_RAW_DATA, $iv, $tag );

	return false === $secret ? '' : sanitize_text_field( $secret );
}

add_action( 'admin_init', 'nerv_core_cover_register_settings' );
function nerv_core_cover_register_settings(): void {
	register_setting(
		'nerv_core_settings',
		'nerv_core_cover_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'nerv_core_cover_sanitize_options',
			'default'           => nerv_core_cover_default_options(),
		)
	);

	foreach ( nerv_core_cover_post_types() as $post_type ) {
		register_post_meta(
			$post_type,
			'_nerv_cover_generated_url',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}

add_action( 'init', 'nerv_core_cover_register_routes' );
function nerv_core_cover_register_routes(): void {
	add_rewrite_tag( '%nerv_cover%', '([0-9]+)' );
	add_rewrite_tag( '%ratio%', '([a-zA-Z0-9-]+)' );
}

add_action( 'template_redirect', 'nerv_core_cover_template_redirect', 0 );
function nerv_core_cover_template_redirect(): void {
	$post_id = absint( get_query_var( 'nerv_cover' ) ?: ( $_GET['nerv_cover'] ?? 0 ) );
	if ( ! $post_id ) {
		return;
	}

	$ratio = sanitize_key( (string) ( get_query_var( 'ratio' ) ?: ( $_GET['ratio'] ?? '5x2' ) ) );
	nerv_core_cover_output_svg( $post_id, $ratio );
}

function nerv_core_cover_post_types(): array {
	return function_exists( 'nerv_core_geo_public_post_types' ) ? nerv_core_geo_public_post_types() : array( 'post', 'project' );
}

function nerv_core_cover_status(): array {
	$options = nerv_core_cover_options();
	$ready = '' !== $options['endpoint'] && '' !== $options['api_key'] && '' !== $options['model'];

	return array(
		'ready'   => $ready,
		'dryRun'  => ! empty( $options['dry_run'] ),
		'label'   => $ready ? __( 'Configured', 'nerv-core' ) : __( 'Not configured', 'nerv-core' ),
		'message' => $ready ? __( 'AI cover settings are present.', 'nerv-core' ) : __( 'Upload and SVG fallback covers remain active until API settings are completed.', 'nerv-core' ),
	);
}

function nerv_core_ai_usage_current_month(): string {
	return current_time( 'Y-m' );
}

function nerv_core_ai_usage_empty_month( string $month ): array {
	return array(
		'month'    => sanitize_text_field( $month ),
		'total'    => 0,
		'external' => 0,
		'services' => array(
			'cover'      => array( 'total' => 0, 'external' => 0 ),
			'key_points' => array( 'total' => 0, 'external' => 0 ),
		),
		'statuses' => array(),
		'last'     => '',
	);
}

function nerv_core_ai_usage_log(): array {
	$log = get_option( 'nerv_core_ai_usage_log', array() );
	return is_array( $log ) ? $log : array();
}

function nerv_core_ai_usage_record( string $service, string $status, bool $external ): void {
	$service = sanitize_key( $service );
	if ( ! in_array( $service, array( 'cover', 'key_points' ), true ) ) {
		return;
	}

	$status = sanitize_key( $status ?: 'unknown' );
	$month  = nerv_core_ai_usage_current_month();
	$log    = nerv_core_ai_usage_log();
	$row    = isset( $log[ $month ] ) && is_array( $log[ $month ] ) ? wp_parse_args( $log[ $month ], nerv_core_ai_usage_empty_month( $month ) ) : nerv_core_ai_usage_empty_month( $month );

	if ( ! isset( $row['services'][ $service ] ) || ! is_array( $row['services'][ $service ] ) ) {
		$row['services'][ $service ] = array( 'total' => 0, 'external' => 0 );
	}

	$row['total'] = absint( $row['total'] ?? 0 ) + 1;
	$row['services'][ $service ]['total'] = absint( $row['services'][ $service ]['total'] ?? 0 ) + 1;
	if ( $external ) {
		$row['external'] = absint( $row['external'] ?? 0 ) + 1;
		$row['services'][ $service ]['external'] = absint( $row['services'][ $service ]['external'] ?? 0 ) + 1;
	}
	if ( ! isset( $row['statuses'] ) || ! is_array( $row['statuses'] ) ) {
		$row['statuses'] = array();
	}
	$row['statuses'][ $status ] = absint( $row['statuses'][ $status ] ?? 0 ) + 1;
	$row['last'] = current_time( 'mysql' );

	$log[ $month ] = $row;
	krsort( $log );
	$log = array_slice( $log, 0, 18, true );

	update_option( 'nerv_core_ai_usage_log', $log, false );
}

function nerv_core_ai_usage_summary( string $month = '' ): array {
	$month = sanitize_text_field( $month ?: nerv_core_ai_usage_current_month() );
	$log   = nerv_core_ai_usage_log();
	$row   = isset( $log[ $month ] ) && is_array( $log[ $month ] ) ? wp_parse_args( $log[ $month ], nerv_core_ai_usage_empty_month( $month ) ) : nerv_core_ai_usage_empty_month( $month );

	foreach ( array( 'cover', 'key_points' ) as $service ) {
		if ( ! isset( $row['services'][ $service ] ) || ! is_array( $row['services'][ $service ] ) ) {
			$row['services'][ $service ] = array( 'total' => 0, 'external' => 0 );
		}
		$row['services'][ $service ]['total'] = absint( $row['services'][ $service ]['total'] ?? 0 );
		$row['services'][ $service ]['external'] = absint( $row['services'][ $service ]['external'] ?? 0 );
	}

	return array(
		'month'    => $month,
		'total'    => absint( $row['total'] ?? 0 ),
		'external' => absint( $row['external'] ?? 0 ),
		'services' => $row['services'],
		'statuses' => array_map( 'absint', is_array( $row['statuses'] ?? null ) ? $row['statuses'] : array() ),
		'last'     => sanitize_text_field( (string) ( $row['last'] ?? '' ) ),
	);
}

function nerv_core_cover_generated_url( int $post_id ): string {
	$url = get_post_meta( $post_id, '_nerv_cover_generated_url', true );
	return $url ? esc_url_raw( (string) $url ) : '';
}

function nerv_core_cover_history( int $post_id ): array {
	$history = get_post_meta( $post_id, '_nerv_cover_history', true );
	if ( ! is_array( $history ) ) {
		return array();
	}

	return array_values( array_slice( $history, 0, 10 ) );
}

function nerv_core_cover_store_history( int $post_id, array $entry ): void {
	$history = nerv_core_cover_history( $post_id );
	array_unshift(
		$history,
		array(
			'time'          => sanitize_text_field( (string) ( $entry['time'] ?? current_time( 'mysql' ) ) ),
			'status'        => sanitize_key( (string) ( $entry['status'] ?? 'unknown' ) ),
			'message'       => sanitize_text_field( (string) ( $entry['message'] ?? '' ) ),
			'source'        => sanitize_key( (string) ( $entry['source'] ?? 'manual' ) ),
			'url'           => esc_url_raw( (string) ( $entry['url'] ?? '' ) ),
			'attachment_id' => absint( $entry['attachment_id'] ?? 0 ),
			'prompt'        => sanitize_textarea_field( (string) ( $entry['prompt'] ?? '' ) ),
		)
	);

	update_post_meta( $post_id, '_nerv_cover_history', array_values( array_slice( $history, 0, 10 ) ) );
}

function nerv_core_cover_generate( int $post_id, string $source = 'manual' ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, nerv_core_cover_post_types(), true ) ) {
		return nerv_core_cover_generation_result( 'error', __( 'Unsupported post.', 'nerv-core' ) );
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return nerv_core_cover_generation_result( 'error', __( 'You are not allowed to generate this cover.', 'nerv-core' ) );
	}

	$options = nerv_core_cover_options();
	$prompt = nerv_core_cover_render_prompt( $post );

	if ( ! empty( $options['dry_run'] ) ) {
		$result = nerv_core_cover_generation_result( 'dry-run', __( 'Dry-run recorded; no external image request was sent.', 'nerv-core' ), '', 0, $prompt );
		nerv_core_cover_store_history( $post_id, array_merge( $result, array( 'source' => $source ) ) );
		nerv_core_ai_usage_record( 'cover', 'dry-run', false );
		return $result;
	}

	$status = nerv_core_cover_status();
	if ( empty( $status['ready'] ) ) {
		$result = nerv_core_cover_generation_result( 'error', __( 'AI cover service is not configured.', 'nerv-core' ), '', 0, $prompt );
		nerv_core_cover_store_history( $post_id, array_merge( $result, array( 'source' => $source ) ) );
		nerv_core_ai_usage_record( 'cover', 'unconfigured', false );
		return $result;
	}

	$response = nerv_core_cover_request_image( $prompt, $options );
	if ( is_wp_error( $response ) ) {
		$result = nerv_core_cover_generation_result( 'error', $response->get_error_message(), '', 0, $prompt );
		nerv_core_cover_store_history( $post_id, array_merge( $result, array( 'source' => $source ) ) );
		nerv_core_ai_usage_record( 'cover', 'error', true );
		return $result;
	}

	$image_payload = nerv_core_cover_extract_image_payload( $response );
	if ( is_wp_error( $image_payload ) ) {
		$result = nerv_core_cover_generation_result( 'error', $image_payload->get_error_message(), '', 0, $prompt );
		nerv_core_cover_store_history( $post_id, array_merge( $result, array( 'source' => $source ) ) );
		nerv_core_ai_usage_record( 'cover', 'error', true );
		return $result;
	}

	$attachment_id = nerv_core_cover_import_image( $post_id, $image_payload );
	if ( is_wp_error( $attachment_id ) ) {
		$result = nerv_core_cover_generation_result( 'error', $attachment_id->get_error_message(), (string) ( $image_payload['url'] ?? '' ), 0, $prompt );
		nerv_core_cover_store_history( $post_id, array_merge( $result, array( 'source' => $source ) ) );
		nerv_core_ai_usage_record( 'cover', 'error', true );
		return $result;
	}

	set_post_thumbnail( $post_id, (int) $attachment_id );
	update_post_meta( $post_id, '_nerv_cover_generated_url', wp_get_attachment_url( (int) $attachment_id ) );

	$result = nerv_core_cover_generation_result( 'success', __( 'AI cover generated and attached.', 'nerv-core' ), (string) wp_get_attachment_url( (int) $attachment_id ), (int) $attachment_id, $prompt );
	nerv_core_cover_store_history( $post_id, array_merge( $result, array( 'source' => $source ) ) );
	nerv_core_ai_usage_record( 'cover', 'success', true );

	return $result;
}

function nerv_core_cover_restore_history( int $post_id, int $index ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, nerv_core_cover_post_types(), true ) ) {
		return nerv_core_cover_generation_result( 'error', __( 'Unsupported post.', 'nerv-core' ) );
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return nerv_core_cover_generation_result( 'error', __( 'You are not allowed to restore this cover.', 'nerv-core' ) );
	}

	$history = nerv_core_cover_history( $post_id );
	if ( ! isset( $history[ $index ] ) || ! is_array( $history[ $index ] ) ) {
		return nerv_core_cover_generation_result( 'error', __( 'Cover history item was not found.', 'nerv-core' ) );
	}

	$item = $history[ $index ];
	$attachment_id = absint( $item['attachment_id'] ?? 0 );
	$url = esc_url_raw( (string) ( $item['url'] ?? '' ) );

	if ( $attachment_id && 'attachment' === get_post_type( $attachment_id ) ) {
		set_post_thumbnail( $post_id, $attachment_id );
		$attachment_url = wp_get_attachment_url( $attachment_id );
		if ( $attachment_url ) {
			$url = esc_url_raw( $attachment_url );
			update_post_meta( $post_id, '_nerv_cover_generated_url', $url );
		}
	} elseif ( $url ) {
		delete_post_thumbnail( $post_id );
		update_post_meta( $post_id, '_nerv_cover_generated_url', $url );
	} else {
		return nerv_core_cover_generation_result( 'error', __( 'Cover history item has no reusable image.', 'nerv-core' ) );
	}

	$result = nerv_core_cover_generation_result( 'restored', __( 'Cover restored from history.', 'nerv-core' ), $url, $attachment_id, (string) ( $item['prompt'] ?? '' ) );
	nerv_core_cover_store_history( $post_id, array_merge( $result, array( 'source' => 'history' ) ) );

	return $result;
}

function nerv_core_cover_generation_result( string $status, string $message, string $url = '', int $attachment_id = 0, string $prompt = '' ): array {
	return array(
		'time'          => current_time( 'mysql' ),
		'status'        => sanitize_key( $status ),
		'message'       => sanitize_text_field( $message ),
		'url'           => esc_url_raw( $url ),
		'attachment_id' => absint( $attachment_id ),
		'prompt'        => sanitize_textarea_field( $prompt ),
	);
}

function nerv_core_key_points_generate( int $post_id, array $context = array() ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, nerv_core_cover_post_types(), true ) ) {
		return nerv_core_key_points_generation_result( 'error', __( 'Unsupported post.', 'nerv-core' ) );
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return nerv_core_key_points_generation_result( 'error', __( 'You are not allowed to generate KEY POINTS for this post.', 'nerv-core' ) );
	}

	$options = nerv_core_cover_options();
	$content = '' !== (string) ( $context['content'] ?? '' ) ? (string) $context['content'] : $post->post_content;
	$title = '' !== (string) ( $context['title'] ?? '' ) ? sanitize_text_field( (string) $context['title'] ) : get_the_title( $post );
	$subtitle = '' !== (string) ( $context['subtitle'] ?? '' ) ? sanitize_text_field( (string) $context['subtitle'] ) : (string) get_post_meta( $post_id, '_nerv_subtitle', true );
	$prompt = nerv_core_key_points_render_prompt( $title, $subtitle, $content );

	if ( ! empty( $options['dry_run'] ) ) {
		nerv_core_ai_usage_record( 'key_points', 'dry-run', false );
		return nerv_core_key_points_generation_result(
			'dry-run',
			__( 'Dry-run KEY POINTS generated locally; no external AI request was sent.', 'nerv-core' ),
			nerv_core_key_points_local_extract( $content, $title, $subtitle ),
			$prompt
		);
	}

	$status = nerv_core_cover_status();
	if ( empty( $status['ready'] ) ) {
		nerv_core_ai_usage_record( 'key_points', 'local', false );
		return nerv_core_key_points_generation_result(
			'local',
			__( 'AI service is not configured; generated local KEY POINTS instead.', 'nerv-core' ),
			nerv_core_key_points_local_extract( $content, $title, $subtitle ),
			$prompt
		);
	}

	$response = nerv_core_key_points_request( $prompt, $options );
	if ( is_wp_error( $response ) ) {
		nerv_core_ai_usage_record( 'key_points', 'error', true );
		return nerv_core_key_points_generation_result(
			'error',
			$response->get_error_message(),
			nerv_core_key_points_local_extract( $content, $title, $subtitle ),
			$prompt
		);
	}

	$points = nerv_core_key_points_extract_response( $response );
	if ( ! $points ) {
		nerv_core_ai_usage_record( 'key_points', 'error', true );
		return nerv_core_key_points_generation_result(
			'error',
			__( 'AI response did not include usable KEY POINTS.', 'nerv-core' ),
			nerv_core_key_points_local_extract( $content, $title, $subtitle ),
			$prompt
		);
	}

	nerv_core_ai_usage_record( 'key_points', 'success', true );
	return nerv_core_key_points_generation_result(
		'success',
		__( 'KEY POINTS generated.', 'nerv-core' ),
		$points,
		$prompt
	);
}

function nerv_core_key_points_generation_result( string $status, string $message, array $points = array(), string $prompt = '' ): array {
	return array(
		'time'    => current_time( 'mysql' ),
		'status'  => sanitize_key( $status ),
		'message' => sanitize_text_field( $message ),
		'points'  => nerv_core_key_points_clean_points( $points ),
		'prompt'  => sanitize_textarea_field( $prompt ),
	);
}

function nerv_core_key_points_render_prompt( string $title, string $subtitle, string $content ): string {
	$plain = nerv_core_key_points_plain_text( $content );
	$plain = wp_trim_words( $plain, 520, '' );

	return sprintf(
		"Generate 3 to 5 concise KEY POINTS for a GEO-ready WordPress article.\nReturn only a JSON array of strings.\nEach point must be a standalone sentence under 140 characters.\nTitle: %s\nSubtitle: %s\nArticle:\n%s",
		$title,
		$subtitle,
		$plain
	);
}

function nerv_core_key_points_plain_text( string $content ): string {
	$text = wp_strip_all_tags( do_blocks( $content ) );
	$text = html_entity_decode( $text, ENT_QUOTES, get_option( 'blog_charset' ) );
	return trim( preg_replace( '/\s+/u', ' ', $text ) ?? $text );
}

function nerv_core_key_points_local_extract( string $content, string $title = '', string $subtitle = '' ): array {
	$text = nerv_core_key_points_plain_text( $content );
	$sentences = preg_split( '/(?<=[.!?。！？])\s+/u', $text );
	$points = array();

	if ( '' !== trim( $subtitle ) ) {
		$points[] = trim( $subtitle );
	}

	if ( '' !== trim( $title ) ) {
		$points[] = sprintf(
			/* translators: %s: post title. */
			__( '%s defines the main operating context for readers and AI systems.', 'nerv-core' ),
			trim( $title )
		);
	}

	if ( is_array( $sentences ) ) {
		foreach ( $sentences as $sentence ) {
			$sentence = trim( wp_strip_all_tags( $sentence ) );
			if ( strlen( $sentence ) < 40 ) {
				continue;
			}
			$points[] = $sentence;
			if ( count( $points ) >= 5 ) {
				break;
			}
		}
	}

	if ( ! $points && '' !== $text ) {
		$points[] = wp_trim_words( $text, 24, '' );
	}

	if ( count( $points ) < 3 ) {
		$points[] = __( 'Add direct answers near the top so AI summaries can extract the article quickly.', 'nerv-core' );
		$points[] = __( 'Use structured headings, internal links, and FAQ content to strengthen GEO visibility.', 'nerv-core' );
	}

	return nerv_core_key_points_clean_points( $points );
}

function nerv_core_key_points_clean_points( array $points ): array {
	$clean = array();
	foreach ( $points as $point ) {
		$value = sanitize_text_field( (string) $point );
		$value = preg_replace( '/^\s*[-*\d.)]+\s*/u', '', $value ) ?? $value;
		$value = trim( $value, " \t\n\r\0\x0B\"'" );
		if ( '' === $value ) {
			continue;
		}
		if ( function_exists( 'mb_strlen' ) && mb_strlen( $value ) > 160 ) {
			$value = rtrim( mb_substr( $value, 0, 157 ), " \t\n\r\0\x0B.,;:。！？" ) . '...';
		} elseif ( strlen( $value ) > 220 ) {
			$value = rtrim( substr( $value, 0, 217 ), " \t\n\r\0\x0B.,;:" ) . '...';
		}
		if ( ! in_array( $value, $clean, true ) ) {
			$clean[] = $value;
		}
		if ( count( $clean ) >= 5 ) {
			break;
		}
	}

	return array_values( array_slice( $clean, 0, 5 ) );
}

function nerv_core_key_points_request( string $prompt, array $options ) {
	$body = array(
		'model'       => (string) $options['model'],
		'temperature' => 0.2,
		'messages'    => array(
			array(
				'role'    => 'system',
				'content' => 'You generate concise article key points. Return only a JSON array of strings.',
			),
			array(
				'role'    => 'user',
				'content' => $prompt,
			),
		),
	);

	$response = wp_remote_post(
		(string) $options['endpoint'],
		array(
			'timeout' => 45,
			'headers' => array(
				'Authorization' => 'Bearer ' . (string) $options['api_key'],
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'nerv_key_points_http_error', sprintf( __( 'AI service returned HTTP %d.', 'nerv-core' ), $code ) );
	}

	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'nerv_key_points_bad_json', __( 'AI service returned invalid JSON.', 'nerv-core' ) );
	}

	return $data;
}

function nerv_core_key_points_extract_response( array $response ): array {
	$content = '';
	if ( isset( $response['choices'][0]['message']['content'] ) ) {
		$content = nerv_core_ai_response_text_value( $response['choices'][0]['message']['content'] );
	} elseif ( isset( $response['output_text'] ) ) {
		$content = (string) $response['output_text'];
	} elseif ( isset( $response['data'][0]['text'] ) ) {
		$content = (string) $response['data'][0]['text'];
	} elseif ( isset( $response['text'] ) ) {
		$content = nerv_core_ai_response_text_value( $response['text'] );
	} elseif ( isset( $response['message']['content'] ) ) {
		$content = nerv_core_ai_response_text_value( $response['message']['content'] );
	} elseif ( isset( $response['output'] ) && is_array( $response['output'] ) ) {
		$content = nerv_core_ai_response_output_text( $response['output'] );
	}

	$content = trim( preg_replace( '/^```(?:json)?|```$/m', '', $content ) ?? $content );
	$decoded = json_decode( $content, true );
	if ( is_array( $decoded ) ) {
		return nerv_core_key_points_clean_points( $decoded );
	}

	$lines = preg_split( '/\r\n|\r|\n/u', $content );
	return nerv_core_key_points_clean_points( is_array( $lines ) ? $lines : array() );
}

function nerv_core_ai_response_text_value( $value ): string {
	if ( is_string( $value ) ) {
		return $value;
	}

	if ( is_array( $value ) ) {
		$parts = array();
		foreach ( $value as $item ) {
			if ( is_string( $item ) ) {
				$parts[] = $item;
			} elseif ( is_array( $item ) ) {
				if ( isset( $item['text'] ) && is_string( $item['text'] ) ) {
					$parts[] = $item['text'];
				} elseif ( isset( $item['content'] ) ) {
					$parts[] = nerv_core_ai_response_text_value( $item['content'] );
				}
			}
		}
		return trim( implode( "\n", array_filter( $parts ) ) );
	}

	return '';
}

function nerv_core_ai_response_output_text( array $output ): string {
	$parts = array();
	foreach ( $output as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		if ( isset( $item['text'] ) && is_string( $item['text'] ) ) {
			$parts[] = $item['text'];
		}
		if ( isset( $item['content'] ) ) {
			$parts[] = nerv_core_ai_response_text_value( $item['content'] );
		}
	}

	return trim( implode( "\n", array_filter( $parts ) ) );
}

function nerv_core_cover_request_image( string $prompt, array $options ) {
	$body = array(
		'model'  => (string) $options['model'],
		'prompt' => $prompt,
		'n'      => 1,
		'size'   => '1024x1024',
	);

	$response = wp_remote_post(
		(string) $options['endpoint'],
		array(
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'Bearer ' . (string) $options['api_key'],
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'nerv_cover_http_error', sprintf( __( 'AI service returned HTTP %d.', 'nerv-core' ), $code ) );
	}

	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'nerv_cover_bad_json', __( 'AI service returned invalid JSON.', 'nerv-core' ) );
	}

	return $data;
}

function nerv_core_cover_extract_image_payload( array $response ) {
	$candidates = array();
	foreach ( array( $response, $response['data'][0] ?? null, $response['result'] ?? null, $response['image'] ?? null ) as $candidate ) {
		if ( is_array( $candidate ) ) {
			$candidates[] = $candidate;
		}
	}

	if ( isset( $response['artifacts'][0] ) && is_array( $response['artifacts'][0] ) ) {
		$candidates[] = $response['artifacts'][0];
	}

	if ( isset( $response['output'] ) && is_array( $response['output'] ) ) {
		foreach ( $response['output'] as $output ) {
			if ( is_array( $output ) ) {
				$candidates[] = $output;
				if ( isset( $output['content'] ) && is_array( $output['content'] ) ) {
					foreach ( $output['content'] as $content ) {
						if ( is_array( $content ) ) {
							$candidates[] = $content;
						}
					}
				}
			}
		}
	}

	foreach ( $candidates as $candidate ) {
		$url = nerv_core_cover_response_url( $candidate );
		if ( '' !== $url ) {
			return array(
				'type' => 'url',
				'url'  => $url,
			);
		}

		$base64 = nerv_core_cover_response_base64( $candidate );
		if ( '' !== $base64 ) {
			return array(
				'type'      => 'b64_json',
				'b64_json'  => $base64,
				'mime_type' => nerv_core_cover_response_mime_type( $candidate ),
			);
		}
	}

	return new WP_Error( 'nerv_cover_no_image', __( 'AI response did not include an image URL or base64 image.', 'nerv-core' ) );
}

function nerv_core_cover_response_url( array $candidate ): string {
	foreach ( array( 'url', 'image_url', 'output_url' ) as $key ) {
		if ( ! empty( $candidate[ $key ] ) && is_string( $candidate[ $key ] ) ) {
			return esc_url_raw( $candidate[ $key ] );
		}
	}

	if ( isset( $candidate['image']['url'] ) && is_string( $candidate['image']['url'] ) ) {
		return esc_url_raw( $candidate['image']['url'] );
	}

	return '';
}

function nerv_core_cover_response_base64( array $candidate ): string {
	foreach ( array( 'b64_json', 'base64', 'image_base64', 'binary' ) as $key ) {
		if ( ! empty( $candidate[ $key ] ) && is_string( $candidate[ $key ] ) ) {
			return (string) $candidate[ $key ];
		}
	}

	if ( isset( $candidate['image']['base64'] ) && is_string( $candidate['image']['base64'] ) ) {
		return (string) $candidate['image']['base64'];
	}

	return '';
}

function nerv_core_cover_response_mime_type( array $candidate ): string {
	foreach ( array( 'mime_type', 'mime', 'content_type' ) as $key ) {
		if ( ! empty( $candidate[ $key ] ) && is_string( $candidate[ $key ] ) ) {
			return sanitize_mime_type( $candidate[ $key ] ) ?: 'image/png';
		}
	}

	return 'image/png';
}

function nerv_core_cover_extract_image_url( array $response ): string {
	$payload = nerv_core_cover_extract_image_payload( $response );
	if ( is_wp_error( $payload ) || 'url' !== ( $payload['type'] ?? '' ) ) {
		return '';
	}

	return esc_url_raw( (string) ( $payload['url'] ?? '' ) );
}

function nerv_core_cover_import_image( int $post_id, $image_payload ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	if ( is_string( $image_payload ) ) {
		$image_payload = array(
			'type' => 'url',
			'url'  => $image_payload,
		);
	}

	if ( ! is_array( $image_payload ) ) {
		return new WP_Error( 'nerv_cover_bad_payload', __( 'Image payload is invalid.', 'nerv-core' ) );
	}

	if ( 'b64_json' === ( $image_payload['type'] ?? '' ) ) {
		return nerv_core_cover_import_base64_image( $post_id, (string) ( $image_payload['b64_json'] ?? '' ), (string) ( $image_payload['mime_type'] ?? 'image/png' ) );
	}

	$image_url = esc_url_raw( (string) ( $image_payload['url'] ?? '' ) );
	if ( '' === $image_url ) {
		return new WP_Error( 'nerv_cover_empty_url', __( 'Image URL is empty.', 'nerv-core' ) );
	}

	$tmp = download_url( $image_url, 60 );
	if ( is_wp_error( $tmp ) ) {
		return $tmp;
	}

	$file_array = array(
		'name'     => 'nerv-cover-' . $post_id . '-' . gmdate( 'Ymd-His' ) . '.png',
		'tmp_name' => $tmp,
	);

	$attachment_id = media_handle_sideload( $file_array, $post_id, __( 'NERV AI generated cover', 'nerv-core' ) );
	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
	}

	return $attachment_id;
}

function nerv_core_cover_import_base64_image( int $post_id, string $base64, string $mime_type = 'image/png' ) {
	$base64 = preg_replace( '/^data:image\/[a-z0-9.+-]+;base64,/i', '', trim( $base64 ) );
	if ( '' === $base64 ) {
		return new WP_Error( 'nerv_cover_empty_base64', __( 'Base64 image payload is empty.', 'nerv-core' ) );
	}

	$bytes = base64_decode( $base64, true );
	if ( false === $bytes || '' === $bytes ) {
		return new WP_Error( 'nerv_cover_bad_base64', __( 'Base64 image payload could not be decoded.', 'nerv-core' ) );
	}

	$allowed = array(
		'image/jpeg' => 'jpg',
		'image/png'  => 'png',
		'image/webp' => 'webp',
	);
	$mime_type = sanitize_mime_type( $mime_type );
	if ( ! isset( $allowed[ $mime_type ] ) ) {
		$mime_type = 'image/png';
	}

	$tmp = wp_tempnam( 'nerv-cover-' . $post_id . '.' . $allowed[ $mime_type ] );
	if ( ! $tmp ) {
		return new WP_Error( 'nerv_cover_tempfile_failed', __( 'Could not create a temporary image file.', 'nerv-core' ) );
	}

	if ( false === file_put_contents( $tmp, $bytes ) ) {
		@unlink( $tmp );
		return new WP_Error( 'nerv_cover_tempfile_write_failed', __( 'Could not write the generated image file.', 'nerv-core' ) );
	}

	$file_array = array(
		'name'     => 'nerv-cover-' . $post_id . '-' . gmdate( 'Ymd-His' ) . '.' . $allowed[ $mime_type ],
		'tmp_name' => $tmp,
		'type'     => $mime_type,
	);

	$attachment_id = media_handle_sideload( $file_array, $post_id, __( 'NERV AI generated cover', 'nerv-core' ) );
	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
	}

	return $attachment_id;
}

function nerv_core_cover_url( int $post_id, string $ratio = '5x2' ): string {
	$size = '1x1' === $ratio ? 'nerv-thumb-square' : ( '2x1' === $ratio ? 'nerv-og' : 'nerv-cover' );
	$uploaded = get_the_post_thumbnail_url( $post_id, $size );
	if ( $uploaded ) {
		return $uploaded;
	}

	$generated = nerv_core_cover_generated_url( $post_id );
	if ( $generated ) {
		return $generated;
	}

	return add_query_arg(
		array(
			'nerv_cover' => (string) $post_id,
			'ratio'      => $ratio,
		),
		home_url( '/' )
	);
}

function nerv_core_cover_source( int $post_id ): string {
	$thumbnail_id = get_post_thumbnail_id( $post_id );
	if ( $thumbnail_id && nerv_core_cover_attachment_from_ai_history( $post_id, (int) $thumbnail_id ) ) {
		return 'ai';
	}

	if ( $thumbnail_id ) {
		return 'upload';
	}

	if ( nerv_core_cover_generated_url( $post_id ) ) {
		return 'ai';
	}

	return 'svg';
}

function nerv_core_cover_attachment_from_ai_history( int $post_id, int $attachment_id ): bool {
	if ( ! $attachment_id ) {
		return false;
	}

	foreach ( nerv_core_cover_history( $post_id ) as $entry ) {
		if ( absint( $entry['attachment_id'] ?? 0 ) !== $attachment_id ) {
			continue;
		}
		if ( in_array( (string) ( $entry['status'] ?? '' ), array( 'success', 'restored' ), true ) ) {
			return true;
		}
	}

	return false;
}

function nerv_core_cover_image( int $post_id, string $ratio = '5x2', string $class = 'nerv-entry-cover' ): string {
	$url = nerv_core_cover_url( $post_id, $ratio );
	if ( ! $url ) {
		return '';
	}

	return '<img class="' . esc_attr( $class ) . ' nerv-cover-source--' . esc_attr( nerv_core_cover_source( $post_id ) ) . '" src="' . esc_url( $url ) . '" alt="' . esc_attr( get_the_title( $post_id ) ) . '">';
}

function nerv_core_cover_render_prompt( WP_Post $post ): string {
	$options = nerv_core_cover_options();
	$template = (string) $options['prompt_template'];
	$category = '';
	$terms = get_the_terms( $post, 'category' );
	if ( is_array( $terms ) && $terms ) {
		$category = $terms[0]->name;
	}

	return strtr(
		$template,
		array(
			'{title}'    => get_the_title( $post ),
			'{subtitle}' => (string) get_post_meta( $post->ID, '_nerv_subtitle', true ),
			'{excerpt}'  => wp_trim_words( wp_strip_all_tags( $post->post_excerpt ?: $post->post_content ), 36 ),
			'{category}' => $category,
		)
	);
}

function nerv_core_cover_dimensions( string $ratio ): array {
	if ( '1x1' === $ratio ) {
		return array( 1200, 1200 );
	}

	return '2x1' === $ratio ? array( 1200, 600 ) : array( 1500, 600 );
}

function nerv_core_cover_svg_text_lines( string $text, int $max_chars, int $max_lines ): array {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) ?: '' );
	if ( '' === $text ) {
		return array();
	}

	$words = preg_split( '/\s+/u', $text ) ?: array();
	if ( count( $words ) > 1 && function_exists( 'mb_strlen' ) ) {
		$lines = array();
		$line  = '';
		foreach ( $words as $word ) {
			$next = '' === $line ? $word : $line . ' ' . $word;
			if ( mb_strlen( $next ) > $max_chars && '' !== $line ) {
				$lines[] = $line;
				$line = $word;
				if ( count( $lines ) >= $max_lines ) {
					break;
				}
				continue;
			}
			$line = $next;
		}
		if ( count( $lines ) < $max_lines && '' !== trim( $line ) ) {
			$lines[] = trim( $line );
		}

		return array_slice( array_filter( $lines ), 0, $max_lines );
	}

	if ( function_exists( 'mb_str_split' ) && function_exists( 'mb_strlen' ) ) {
		$chars = mb_str_split( $text );
		$lines = array();
		$line  = '';
		foreach ( $chars as $char ) {
			if ( mb_strlen( $line . $char ) > $max_chars ) {
				$lines[] = trim( $line );
				$line = $char;
				if ( count( $lines ) >= $max_lines ) {
					break;
				}
				continue;
			}
			$line .= $char;
		}
		if ( count( $lines ) < $max_lines && '' !== trim( $line ) ) {
			$lines[] = trim( $line );
		}

		return array_slice( array_filter( $lines ), 0, $max_lines );
	}

	return array_slice( explode( "\n", wordwrap( $text, $max_chars, "\n", true ) ), 0, $max_lines );
}

function nerv_core_cover_output_svg( int $post_id, string $ratio ): void {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status || ! in_array( $post->post_type, nerv_core_cover_post_types(), true ) ) {
		status_header( 404 );
		header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );
		echo "Cover not found.\n";
		exit;
	}

	$dimensions = nerv_core_cover_dimensions( $ratio );
	$title = get_the_title( $post );
	$subtitle = (string) get_post_meta( $post_id, '_nerv_subtitle', true );
	$code = strtoupper( dechex( $post_id ) );
	$width = $dimensions[0];
	$height = $dimensions[1];

	nocache_headers();
	header( 'Content-Type: image/svg+xml; charset=UTF-8' );
	echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( (string) $width ) . '" height="' . esc_attr( (string) $height ) . '" viewBox="0 0 ' . esc_attr( (string) $width ) . ' ' . esc_attr( (string) $height ) . '" role="img" aria-label="' . esc_attr( $title ) . '">';
	echo '<rect width="100%" height="100%" fill="#050403"/>';
	echo '<defs><linearGradient id="g" x1="0" x2="1"><stop offset="0" stop-color="#111827"/><stop offset=".54" stop-color="#090706"/><stop offset="1" stop-color="#17210f"/></linearGradient><pattern id="scan" width="28" height="28" patternUnits="userSpaceOnUse"><path d="M0 27H28M27 0V28" stroke="#4ade80" stroke-opacity=".14"/></pattern></defs>';
	echo '<rect width="100%" height="100%" fill="url(#g)"/><rect width="100%" height="100%" fill="url(#scan)"/>';
	echo '<text x="' . esc_attr( (string) ( $width * 0.08 ) ) . '" y="' . esc_attr( (string) ( $height * 0.22 ) ) . '" fill="#ffb000" font-family="monospace" font-size="' . esc_attr( (string) max( 18, (int) ( $width * 0.024 ) ) ) . '">NERV COVER: 0x' . esc_html( $code ) . '</text>';
	$title_size = max( 38, (int) ( $width * 0.046 ) );
	$title_lines = nerv_core_cover_svg_text_lines( $title, '1x1' === $ratio ? 20 : 34, 3 );
	foreach ( $title_lines as $index => $line ) {
		echo '<text x="' . esc_attr( (string) ( $width * 0.08 ) ) . '" y="' . esc_attr( (string) ( $height * 0.42 + ( $index * $title_size * 1.15 ) ) ) . '" fill="#e8e4dc" font-family="monospace" font-size="' . esc_attr( (string) $title_size ) . '" font-weight="700">' . esc_html( $line ) . '</text>';
	}
	$subtitle_lines = nerv_core_cover_svg_text_lines( $subtitle, '1x1' === $ratio ? 26 : 52, 2 );
	foreach ( $subtitle_lines as $index => $line ) {
		echo '<text x="' . esc_attr( (string) ( $width * 0.08 ) ) . '" y="' . esc_attr( (string) ( $height * 0.68 + ( $index * max( 20, (int) ( $width * 0.026 ) ) * 1.2 ) ) ) . '" fill="#4ade80" font-family="monospace" font-size="' . esc_attr( (string) max( 20, (int) ( $width * 0.026 ) ) ) . '">' . esc_html( $line ) . '</text>';
	}
	echo '<text x="' . esc_attr( (string) ( $width * 0.08 ) ) . '" y="' . esc_attr( (string) ( $height * 0.82 ) ) . '" fill="#4ade80" fill-opacity=".62" font-family="monospace" font-size="' . esc_attr( (string) max( 16, (int) ( $width * 0.018 ) ) ) . '">TEXT ONLY FALLBACK</text>';
	echo '</svg>';
	exit;
}
