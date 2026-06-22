<?php
/**
 * IndexNow integration and logging.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_indexnow_default_options(): array {
	return array(
		'enabled' => true,
		'key'     => '',
		'endpoint'=> 'https://api.indexnow.org/indexnow',
		'dry_run' => true,
	);
}

function nerv_core_indexnow_options(): array {
	$options = get_option( 'nerv_core_indexnow_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	$options = wp_parse_args( $options, nerv_core_indexnow_default_options() );
	if ( '' === $options['key'] ) {
		$options['key'] = nerv_core_indexnow_generate_key();
		update_option( 'nerv_core_indexnow_options', $options, false );
	}

	return $options;
}

function nerv_core_indexnow_sanitize_options( $input ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	$defaults = nerv_core_indexnow_default_options();
	$key      = isset( $input['key'] ) ? preg_replace( '/[^A-Za-z0-9_-]/', '', (string) wp_unslash( $input['key'] ) ) : '';
	$endpoint = isset( $input['endpoint'] ) ? esc_url_raw( wp_unslash( $input['endpoint'] ) ) : $defaults['endpoint'];

	return array(
		'enabled' => ! empty( $input['enabled'] ),
		'key'     => '' !== $key ? substr( $key, 0, 128 ) : nerv_core_indexnow_generate_key(),
		'endpoint'=> $endpoint ?: $defaults['endpoint'],
		'dry_run' => ! empty( $input['dry_run'] ),
	);
}

add_action( 'admin_init', 'nerv_core_indexnow_register_settings' );
function nerv_core_indexnow_register_settings(): void {
	register_setting(
		'nerv_core_settings',
		'nerv_core_indexnow_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'nerv_core_indexnow_sanitize_options',
			'default'           => nerv_core_indexnow_default_options(),
		)
	);
}

add_action( 'init', 'nerv_core_indexnow_register_routes' );
function nerv_core_indexnow_register_routes(): void {
	add_rewrite_tag( '%nerv_indexnow_key%', '1' );
}

add_action( 'template_redirect', 'nerv_core_indexnow_template_redirect', 0 );
function nerv_core_indexnow_template_redirect(): void {
	if ( nerv_core_indexnow_is_key_request() ) {
		nerv_core_indexnow_output_key_file();
	}
}

function nerv_core_indexnow_is_key_request(): bool {
	if ( get_query_var( 'nerv_indexnow_key' ) || isset( $_GET['nerv_indexnow_key'] ) ) {
		return true;
	}

	$options = nerv_core_indexnow_options();
	$path    = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( (string) wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';

	return trim( rawurldecode( $path ), '/' ) === $options['key'] . '.txt';
}

function nerv_core_indexnow_output_key_file(): void {
	$options = nerv_core_indexnow_options();

	nocache_headers();
	header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );
	echo $options['key'];
	exit;
}

function nerv_core_indexnow_generate_key(): string {
	return wp_generate_password( 32, false, false );
}

function nerv_core_indexnow_key_url(): string {
	$options = nerv_core_indexnow_options();

	return home_url( '/' . $options['key'] . '.txt' );
}

function nerv_core_indexnow_log(): array {
	$log = get_option( 'nerv_core_indexnow_log', array() );

	return is_array( $log ) ? $log : array();
}

function nerv_core_indexnow_add_log( array $entry ): void {
	$log = nerv_core_indexnow_log();
	array_unshift(
		$log,
		wp_parse_args(
			$entry,
			array(
				'time'     => current_time( 'mysql' ),
				'status'   => 'unknown',
				'message'  => '',
				'urls'     => array(),
				'response' => '',
			)
		)
	);

	update_option( 'nerv_core_indexnow_log', array_slice( $log, 0, 30 ), false );
}

add_action( 'save_post', 'nerv_core_indexnow_ping_on_save', 40, 2 );
function nerv_core_indexnow_ping_on_save( int $post_id, WP_Post $post ): void {
	if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}

	if ( 'publish' !== $post->post_status || ! in_array( $post->post_type, nerv_core_geo_public_post_types(), true ) ) {
		return;
	}

	nerv_core_indexnow_submit_urls( array( get_permalink( $post ) ), 'save_post' );
}

function nerv_core_indexnow_submit_urls( array $urls, string $source = 'manual' ): array {
	$options = nerv_core_indexnow_options();
	$urls    = array_values( array_unique( array_filter( array_map( 'esc_url_raw', $urls ) ) ) );
	$host    = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );

	if ( ! $options['enabled'] ) {
		$result = array( 'status' => 'disabled', 'message' => __( 'IndexNow is disabled.', 'nerv-core' ) );
		nerv_core_indexnow_add_log( array_merge( $result, array( 'urls' => $urls, 'source' => $source ) ) );
		return $result;
	}

	if ( ! $urls || ! $host ) {
		$result = array( 'status' => 'skipped', 'message' => __( 'No URLs or host available.', 'nerv-core' ) );
		nerv_core_indexnow_add_log( array_merge( $result, array( 'urls' => $urls, 'source' => $source ) ) );
		return $result;
	}

	if ( $options['dry_run'] || nerv_core_indexnow_is_local_host( $host ) ) {
		$result = array( 'status' => 'dry-run', 'message' => __( 'Recorded without external submission.', 'nerv-core' ) );
		nerv_core_indexnow_add_log( array_merge( $result, array( 'urls' => $urls, 'source' => $source ) ) );
		return $result;
	}

	$response = wp_remote_post(
		$options['endpoint'],
		array(
			'timeout' => 10,
			'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'    => wp_json_encode(
				array(
					'host'        => $host,
					'key'         => $options['key'],
					'keyLocation' => nerv_core_indexnow_key_url(),
					'urlList'     => $urls,
				),
				JSON_UNESCAPED_SLASHES
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		$result = array( 'status' => 'error', 'message' => $response->get_error_message() );
		nerv_core_indexnow_add_log( array_merge( $result, array( 'urls' => $urls, 'source' => $source ) ) );
		return $result;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$result = array(
		'status'   => $code >= 200 && $code < 300 ? 'success' : 'error',
		'message'  => sprintf( 'HTTP %d', $code ),
		'response' => wp_remote_retrieve_body( $response ),
	);
	nerv_core_indexnow_add_log( array_merge( $result, array( 'urls' => $urls, 'source' => $source ) ) );

	return $result;
}

function nerv_core_indexnow_is_local_host( string $host ): bool {
	return in_array( strtolower( $host ), array( '127.0.0.1', 'localhost', '::1' ), true );
}

add_action( 'admin_post_nerv_core_indexnow_test', 'nerv_core_indexnow_admin_test' );
function nerv_core_indexnow_admin_test(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to test IndexNow.', 'nerv-core' ) );
	}

	check_admin_referer( 'nerv_core_indexnow_test' );
	$url = function_exists( 'nerv_core_ai_policy_exists' ) && nerv_core_ai_policy_exists() ? nerv_core_ai_policy_url() : home_url( '/' );
	$result = nerv_core_indexnow_submit_urls( array( $url ), 'manual-test' );
	$status = sanitize_key( (string) ( $result['status'] ?? 'unknown' ) );
	$redirect = add_query_arg(
		array(
			'page'                 => 'nerv-control',
			'nerv_indexnow_status' => $status,
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect );
	exit;
}
