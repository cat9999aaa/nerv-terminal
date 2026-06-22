<?php
/**
 * Partner link health checks.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_partner_health_default_options(): array {
	return array(
		'enabled'      => true,
		'timeout'      => 5,
		'slow_seconds' => 2.5,
	);
}

function nerv_core_partner_health_options(): array {
	$options = get_option( 'nerv_core_partner_health_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	$options = wp_parse_args( $options, nerv_core_partner_health_default_options() );
	$options['timeout'] = max( 1, min( 20, absint( $options['timeout'] ?? 5 ) ) );
	$options['slow_seconds'] = max( 0.5, min( 10, (float) ( $options['slow_seconds'] ?? 2.5 ) ) );

	return $options;
}

function nerv_core_partner_health_sanitize_options( $input ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	return array(
		'enabled'      => ! empty( $input['enabled'] ),
		'timeout'      => max( 1, min( 20, absint( $input['timeout'] ?? 5 ) ) ),
		'slow_seconds' => max( 0.5, min( 10, (float) ( $input['slow_seconds'] ?? 2.5 ) ) ),
	);
}

add_action( 'admin_init', 'nerv_core_partner_health_register_settings' );
function nerv_core_partner_health_register_settings(): void {
	register_setting(
		'nerv_core_settings',
		'nerv_core_partner_health_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'nerv_core_partner_health_sanitize_options',
			'default'           => nerv_core_partner_health_default_options(),
		)
	);
}

add_action( 'init', 'nerv_core_partner_health_schedule' );
function nerv_core_partner_health_schedule(): void {
	if ( ! wp_next_scheduled( 'nerv_core_partner_health_daily' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'nerv_core_partner_health_daily' );
	}
}

add_action( 'nerv_core_partner_health_daily', 'nerv_core_partner_health_check_all' );
function nerv_core_partner_health_check_all(): array {
	$options = nerv_core_partner_health_options();
	if ( empty( $options['enabled'] ) ) {
		return array();
	}

	$partners = get_posts(
		array(
			'post_type'      => 'partner',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'fields'         => 'ids',
		)
	);

	$results = array();
	foreach ( $partners as $partner_id ) {
		$results[ (int) $partner_id ] = nerv_core_partner_health_check_one( (int) $partner_id );
	}

	return $results;
}

function nerv_core_partner_health_check_one( int $partner_id ): array {
	$url = get_post_meta( $partner_id, '_nerv_partner_url', true );
	if ( ! $url ) {
		$result = nerv_core_partner_health_result( 'offline', __( 'Missing URL', 'nerv-core' ), 0, 0, 0, '' );
		nerv_core_partner_health_store_result( $partner_id, $result );
		return $result;
	}

	$options = nerv_core_partner_health_options();
	$start = microtime( true );
	$trace = nerv_core_partner_health_trace_redirects( esc_url_raw( (string) $url ), (int) $options['timeout'] );
	$response = wp_remote_head(
		$url,
		array(
			'timeout'     => (int) $options['timeout'],
			'redirection' => 3,
			'user-agent'  => 'NERV-Core-Partner-Health/' . NERV_CORE_VERSION . '; ' . home_url( '/' ),
		)
	);

	if ( is_wp_error( $response ) ) {
		$result = nerv_core_partner_health_result( 'offline', $response->get_error_message(), 0, 0, absint( $trace['redirects'] ?? 0 ), (string) ( $trace['final_url'] ?? '' ) );
		nerv_core_partner_health_store_result( $partner_id, $result );
		return $result;
	}

	$duration = microtime( true ) - $start;
	$code     = (int) wp_remote_retrieve_response_code( $response );
	$status   = $code >= 200 && $code < 500 ? 'online' : 'offline';
	if ( 'online' === $status && $duration >= (float) $options['slow_seconds'] ) {
		$status = 'slow';
	}

	$message = sprintf(
		/* translators: 1: HTTP status code, 2: response seconds. */
		__( 'HTTP %1$d in %2$.2fs', 'nerv-core' ),
		$code,
		$duration
	);
	$redirects = absint( $trace['redirects'] ?? 0 );
	if ( $redirects > 0 ) {
		$message .= sprintf(
			/* translators: %d: redirect count. */
			__( ' · %d redirects', 'nerv-core' ),
			$redirects
		);
	}
	$result = nerv_core_partner_health_result( $status, $message, $code, $duration, $redirects, (string) ( $trace['final_url'] ?? '' ) );
	nerv_core_partner_health_store_result( $partner_id, $result );

	return $result;
}

function nerv_core_partner_health_result( string $status, string $message, int $code, float $duration, int $redirects = 0, string $final_url = '' ): array {
	return array(
		'status'    => in_array( $status, array( 'online', 'slow', 'offline' ), true ) ? $status : 'offline',
		'message'   => sanitize_text_field( $message ),
		'code'      => $code,
		'duration'  => round( $duration, 3 ),
		'redirects' => max( 0, absint( $redirects ) ),
		'final_url' => esc_url_raw( $final_url ),
		'checked'   => current_time( 'mysql' ),
	);
}

function nerv_core_partner_health_trace_redirects( string $url, int $timeout ): array {
	if ( '' === $url ) {
		return array( 'redirects' => 0, 'final_url' => '' );
	}

	$response = wp_remote_head(
		$url,
		array(
			'timeout'     => $timeout,
			'redirection' => 0,
			'user-agent'  => 'NERV-Core-Partner-Trace/' . NERV_CORE_VERSION . '; ' . home_url( '/' ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return array( 'redirects' => 0, 'final_url' => $url );
	}

	$redirects = 0;
	$current = $url;
	for ( $i = 0; $i < 5; $i++ ) {
		$code = (int) wp_remote_retrieve_response_code( $response );
		$location = wp_remote_retrieve_header( $response, 'location' );
		if ( $code < 300 || $code >= 400 || '' === (string) $location ) {
			break;
		}

		$current = nerv_core_partner_health_absolute_url( (string) $location, $current );
		++$redirects;
		$response = wp_remote_head(
			$current,
			array(
				'timeout'     => $timeout,
				'redirection' => 0,
				'user-agent'  => 'NERV-Core-Partner-Trace/' . NERV_CORE_VERSION . '; ' . home_url( '/' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			break;
		}
	}

	return array( 'redirects' => $redirects, 'final_url' => esc_url_raw( $current ) );
}

function nerv_core_partner_health_absolute_url( string $location, string $base ): string {
	if ( preg_match( '~^https?://~i', $location ) ) {
		return esc_url_raw( $location );
	}

	$parts = wp_parse_url( $base );
	$scheme = (string) ( $parts['scheme'] ?? 'https' );
	$host = (string) ( $parts['host'] ?? '' );
	$port = isset( $parts['port'] ) ? ':' . absint( $parts['port'] ) : '';
	if ( str_starts_with( $location, '/' ) ) {
		return esc_url_raw( $scheme . '://' . $host . $port . $location );
	}

	$path = isset( $parts['path'] ) ? trailingslashit( dirname( (string) $parts['path'] ) ) : '/';
	return esc_url_raw( $scheme . '://' . $host . $port . $path . ltrim( $location, '/' ) );
}

function nerv_core_partner_health_store_result( int $partner_id, array $result ): void {
	update_post_meta( $partner_id, '_nerv_partner_health', $result );
}

function nerv_core_partner_health_status( int $partner_id ): array {
	$result = get_post_meta( $partner_id, '_nerv_partner_health', true );
	if ( ! is_array( $result ) ) {
		return nerv_core_partner_health_result( 'online', __( 'Not checked yet', 'nerv-core' ), 0, 0 );
	}

	return wp_parse_args( $result, nerv_core_partner_health_result( 'online', __( 'Not checked yet', 'nerv-core' ), 0, 0 ) );
}

function nerv_core_partner_health_status_label( string $status ): string {
	$labels = array(
		'online'  => 'ONLINE',
		'slow'    => 'SLOW',
		'offline' => 'OFFLINE',
	);

	return $labels[ $status ] ?? 'OFFLINE';
}

function nerv_core_partner_health_summary(): array {
	$summary = array(
		'online'  => 0,
		'slow'    => 0,
		'offline' => 0,
		'total'   => 0,
	);
	$partners = get_posts(
		array(
			'post_type'      => 'partner',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'fields'         => 'ids',
		)
	);

	foreach ( $partners as $partner_id ) {
		$status = nerv_core_partner_health_status( (int) $partner_id )['status'];
		$summary[ $status ] = absint( $summary[ $status ] ?? 0 ) + 1;
		$summary['total']++;
	}

	return $summary;
}

add_action( 'admin_post_nerv_core_partner_health_test', 'nerv_core_partner_health_admin_test' );
function nerv_core_partner_health_admin_test(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to test partner health.', 'nerv-core' ) );
	}

	check_admin_referer( 'nerv_core_partner_health_test' );
	nerv_core_partner_health_check_all();
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                       => 'nerv-control',
				'nerv_partner_health_status' => 'checked',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
