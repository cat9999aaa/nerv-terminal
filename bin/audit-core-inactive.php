<?php
/**
 * Runtime degradation audit with the NERV Core companion plugin inactive.
 *
 * @package NervTerminal
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$root    = dirname( __DIR__ );
$wp_load = $argv[1] ?? '/www/wwwroot/127_0_0_1/wp-load.php';
$site    = rtrim( getenv( 'NERV_SITE_URL' ) ?: 'http://127.0.0.1', '/' );
$checks  = array();

define( 'NERV_CORE_AUDIT_PLUGIN', 'nerv-core/nerv-core.php' );

if ( ! is_file( $wp_load ) ) {
	fwrite( STDERR, 'Missing wp-load.php: ' . $wp_load . "\n" );
	exit( 1 );
}

require_once $wp_load;
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$was_active = is_plugin_active( NERV_CORE_AUDIT_PLUGIN );

try {
	if ( $was_active ) {
		deactivate_plugins( NERV_CORE_AUDIT_PLUGIN, true );
	}

	delete_option( 'rewrite_rules' );
	flush_rewrite_rules();

	$targets = array(
		'home'     => array(
			'url'      => $site . '/',
			'required' => array( 'nerv-panel--core-missing', 'NERV Core Offline', '<main class="nerv-main" id="main">' ),
		),
		'single'   => array(
			'url'      => $site . '/?p=13',
			'required' => array( 'nerv-panel--core-missing', 'nerv-panel--entry', '<main class="nerv-main" id="main">' ),
		),
		'archive'  => array(
			'url'      => $site . '/?post_type=project',
			'required' => array( 'nerv-panel--core-missing', 'nerv-panel--archive', '<main class="nerv-main" id="main">' ),
		),
		'partners' => array(
			'url'      => $site . '/?post_type=partner',
			'required' => array( 'nerv-panel--core-missing', 'nerv-panel--partners', '<main class="nerv-main" id="main">' ),
		),
		'more'     => array(
			'url'      => $site . '/?nerv_more=1',
			'required' => array( 'nerv-panel--core-missing', 'nerv-panel--more', 'Mobile app navigation' ),
		),
	);

	foreach ( $targets as $label => $target ) {
		$response = http_get( $target['url'] );
		add_check( $checks, $label . ' http 200', 200 === $response['status'], $target['url'] . ' returned HTTP ' . $response['status'] . '.' );
		add_check( $checks, $label . ' no fatal output', ! str_contains( $response['body'], 'Fatal error' ) && ! str_contains( $response['body'], 'Warning:' ), $label . ' page has no PHP fatal/warning output.' );

		foreach ( $target['required'] as $needle ) {
			add_check( $checks, $label . ' contains ' . $needle, str_contains( $response['body'], $needle ), $label . ' page contains required safe-mode signal `' . $needle . '`.' );
		}
	}
} finally {
	if ( $was_active && ! is_plugin_active( NERV_CORE_AUDIT_PLUGIN ) ) {
		$result = activate_plugin( NERV_CORE_AUDIT_PLUGIN );
		if ( is_wp_error( $result ) ) {
			fwrite( STDERR, 'Failed to reactivate NERV Core: ' . $result->get_error_message() . "\n" );
			exit( 1 );
		}
	}

	delete_option( 'rewrite_rules' );
	flush_rewrite_rules();
}

$failed = array_values(
	array_filter(
		$checks,
		static function ( array $check ): bool {
			return 'pass' !== $check['state'];
		}
	)
);

foreach ( $checks as $check ) {
	printf( "[%s] %s - %s\n", strtoupper( $check['state'] ), $check['label'], $check['detail'] );
}

if ( $failed ) {
	exit( 1 );
}

function add_check( array &$checks, string $label, bool $passed, string $detail ): void {
	$checks[] = array(
		'label'  => $label,
		'state'  => $passed ? 'pass' : 'fail',
		'detail' => $detail,
	);
}

function http_get( string $url ): array {
	$context = stream_context_create(
		array(
			'http' => array(
				'ignore_errors' => true,
				'timeout'       => 8,
				'header'        => "User-Agent: NERV-Core-Inactive-Audit/1.0\r\n",
			),
		)
	);
	$body = @file_get_contents( $url, false, $context );
	$status = 0;
	foreach ( $http_response_header ?? array() as $header ) {
		if ( preg_match( '/^HTTP\/\S+\s+(\d+)/', $header, $matches ) ) {
			$status = (int) $matches[1];
			break;
		}
	}

	return array(
		'status' => $status,
		'body'   => false === $body ? '' : $body,
	);
}
