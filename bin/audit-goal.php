<?php
/**
 * One-command audit for the user-facing NERV Terminal hardening goal.
 *
 * @package NervTerminal
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$root    = dirname( __DIR__ );
$site    = rtrim( $argv[1] ?? getenv( 'NERV_SITE_URL' ) ?: 'http://127.0.0.1', '/' );
$wp_load = $argv[2] ?? getenv( 'NERV_WP_LOAD' ) ?: '/www/wwwroot/127_0_0_1/wp-load.php';
$php     = PHP_BINARY;
$wp_php  = getenv( 'NERV_WP_PHP' ) ?: $php;
$checks  = array();

$social_url = discover_social_audit_url( $site );
$commands = array(
	'admin controls and AI UX'      => php_command( $php, $root . '/bin/audit-admin-control.php' ),
	'cover ratios and pagination'  => php_command( $php, $root . '/bin/audit-cover-pagination.php' ),
	'rewrite and markdown routes'  => php_command( $php, $root . '/bin/audit-rewrite-routes.php', array( $site ) ),
	'runtime performance/partners' => php_command( $php, $root . '/bin/audit-runtime-health.php', array( $site ) ),
	'frontend no-js/perf hygiene'  => 'NERV_SITE_URL=' . escapeshellarg( $site ) . ' ' . php_command( $php, $root . '/bin/audit-frontend.php' ),
	'social WebP sharing image'    => php_command( $php, $root . '/bin/audit-social-share.php', array( $social_url ) ),
);

if ( getenv( 'NERV_SKIP_WP_STATE' ) ) {
	add_check( $checks, 'legacy markdown redirect', true, 'Skipped by NERV_SKIP_WP_STATE; run bin/audit-legacy-markdown-redirect.php with wp-load.php for stateful verification.' );
} elseif ( is_file( $wp_load ) ) {
	$commands['legacy markdown redirect'] = php_command( $wp_php, $root . '/bin/audit-legacy-markdown-redirect.php', array( $wp_load ) );
} else {
	add_check( $checks, 'legacy markdown redirect', false, 'Missing wp-load.php: ' . $wp_load . '.' );
}

foreach ( $commands as $label => $command ) {
	$result = run_command( $command, $root );
	add_check( $checks, $label, 0 === $result['code'], trim( $result['output'] ) ?: 'No output.' );
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
	printf( "\n[%s] %s\n%s\n", strtoupper( $check['state'] ), $check['label'], indent( $check['detail'] ) );
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

function run_command( string $command, string $cwd ): array {
	$descriptor_spec = array(
		0 => array( 'pipe', 'r' ),
		1 => array( 'pipe', 'w' ),
		2 => array( 'pipe', 'w' ),
	);
	$process = proc_open( $command, $descriptor_spec, $pipes, $cwd );
	if ( ! is_resource( $process ) ) {
		return array( 'code' => 1, 'output' => 'Could not start command: ' . $command );
	}

	fclose( $pipes[0] );
	$output = stream_get_contents( $pipes[1] ) . stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	$code = proc_close( $process );

	return array( 'code' => $code, 'output' => $output );
}

function php_command( string $php, string $script, array $args = array() ): string {
	$parts = array_merge( array( $php, escapeshellarg( $script ) ), array_map( 'escapeshellarg', $args ) );
	return implode( ' ', $parts );
}

function discover_social_audit_url( string $site ): string {
	$llms = http_get( $site . '/llms.txt' );
	if ( preg_match( '~\((https?://[^)]+)\.md\)~i', $llms, $matches ) ) {
		return $matches[1] . '.html';
	}

	return $site . '/hello-world.html';
}

function http_get( string $url ): string {
	$context = stream_context_create(
		array(
			'http' => array(
				'ignore_errors' => true,
				'timeout'       => 8,
				'header'        => "User-Agent: NERV-Goal-Audit/1.0\r\n",
			),
		)
	);
	$body = @file_get_contents( $url, false, $context );

	return false === $body ? '' : $body;
}

function indent( string $text ): string {
	return '  ' . str_replace( "\n", "\n  ", $text );
}
