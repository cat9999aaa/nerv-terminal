<?php
/**
 * Runtime health audit for public routes and lightweight response budgets.
 *
 * @package NervTerminal
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$site = rtrim( $argv[1] ?? getenv( 'NERV_SITE_URL' ) ?: 'http://127.0.0.1', '/' );
$checks = array();

$home = http_get( $site . '/', true );
add_check( $checks, 'home status', 200 === $home['status'], 'Home returned HTTP ' . $home['status'] . '.' );
add_check( $checks, 'home html budget', strlen( $home['body'] ) <= 250000, 'Home HTML is ' . number_format( strlen( $home['body'] ) ) . ' bytes.' );
add_check( $checks, 'home response budget', $home['duration'] <= 2.5, 'Home resolved in ' . number_format( $home['duration'], 3 ) . 's.' );

foreach ( array( '/partners', '/partners/' ) as $path ) {
	$response = http_get( $site . $path, true );
	add_check( $checks, 'partners route ' . $path, 200 === $response['status'], $path . ' final HTTP ' . $response['status'] . ' at ' . $response['url'] . '.' );
	add_check( $checks, 'partners redirect budget ' . $path, $response['redirects'] <= 1, $path . ' used ' . $response['redirects'] . ' redirects.' );
	add_check( $checks, 'partners not loop ' . $path, ! $response['loop'], $path . ' redirect loop flag is ' . ( $response['loop'] ? 'true' : 'false' ) . '.' );
	add_check( $checks, 'partners content ' . $path, false !== stripos( $response['body'], 'partner' ) || false !== stripos( $response['body'], '合作伙伴' ), $path . ' renders partner-facing content.' );
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

function http_get( string $url, bool $follow_redirects = false ): array {
	$headers = array();
	$body = '';
	$status = 0;
	$effective_url = $url;
	$seen = array();
	$redirects = 0;
	$loop = false;
	$limit = $follow_redirects ? 6 : 1;
	$started = microtime( true );

	for ( $i = 0; $i < $limit; $i++ ) {
		if ( isset( $seen[ $effective_url ] ) ) {
			$loop = true;
			break;
		}
		$seen[ $effective_url ] = true;
		$context = stream_context_create(
			array(
				'http' => array(
					'ignore_errors' => true,
					'timeout'       => 10,
					'header'        => "User-Agent: NERV-Runtime-Audit/1.0\r\n",
				),
			)
		);
		$body = @file_get_contents( $effective_url, false, $context );
		$headers = $http_response_header ?? array();
		$status = status_from_headers( $headers );
		$location = location_from_headers( $headers );
		if ( ! $follow_redirects || $status < 300 || $status >= 400 || '' === $location ) {
			break;
		}
		++$redirects;
		$effective_url = absolute_url( $location, $effective_url );
	}

	return array(
		'status'    => $status,
		'headers'   => implode( "\n", $headers ),
		'body'      => false === $body ? '' : $body,
		'url'       => $effective_url,
		'redirects' => $redirects,
		'loop'      => $loop,
		'duration'  => microtime( true ) - $started,
	);
}

function status_from_headers( array $headers ): int {
	foreach ( $headers as $header ) {
		if ( preg_match( '/^HTTP\/\S+\s+(\d+)/', $header, $matches ) ) {
			return (int) $matches[1];
		}
	}

	return 0;
}

function location_from_headers( array $headers ): string {
	foreach ( $headers as $header ) {
		if ( preg_match( '/^Location:\s*(.+)$/i', $header, $matches ) ) {
			return trim( $matches[1] );
		}
	}

	return '';
}

function absolute_url( string $location, string $base ): string {
	if ( preg_match( '~^https?://~i', $location ) ) {
		return $location;
	}

	$parts = parse_url( $base );
	$scheme = $parts['scheme'] ?? 'http';
	$host = $parts['host'] ?? '';
	$port = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
	if ( str_starts_with( $location, '/' ) ) {
		return $scheme . '://' . $host . $port . $location;
	}

	$path = isset( $parts['path'] ) ? dirname( $parts['path'] ) : '';
	return $scheme . '://' . $host . $port . rtrim( $path, '/' ) . '/' . $location;
}
