<?php
/**
 * Runtime audit for Baota/Nginx rewrite-sensitive public routes.
 *
 * @package NervTerminal
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$site = rtrim( getenv( 'NERV_SITE_URL' ) ?: 'http://127.0.0.1', '/' );
$checks = array();

$blog = http_get( $site . '/blog/page/444/', true );
add_check( $checks, 'overflow blog pagination', 200 === $blog['status'], 'Expected /blog/page/444/ to resolve after redirects, got HTTP ' . $blog['status'] . ' at ' . $blog['url'] . '.' );
add_check( $checks, 'overflow canonical target', (bool) preg_match( '~/blog(?:/page/[0-9]+)?/?$~', $blog['url'] ), 'Overflow pagination redirects to a stable blog URL: ' . $blog['url'] . '.' );

$llms = http_get( $site . '/llms.txt' );
add_check( $checks, 'llms route', 200 === $llms['status'] && str_contains( strtolower( $llms['headers'] ), 'content-type: text/plain' ), 'llms.txt returned HTTP ' . $llms['status'] . '.' );

$markdown_url = markdown_url_from_llms( $llms['body'] );
add_check( $checks, 'llms markdown link', '' !== $markdown_url, 'llms.txt exposes at least one .md mirror URL.' );

if ( '' !== $markdown_url ) {
	$markdown = http_get( $markdown_url );
	add_check( $checks, 'markdown mirror route', 200 === $markdown['status'] && str_contains( strtolower( $markdown['headers'] ), 'content-type: text/markdown' ), $markdown_url . ' returned HTTP ' . $markdown['status'] . '.' );
	add_check( $checks, 'markdown front matter', str_starts_with( ltrim( $markdown['body'] ), '---' ) && str_contains( $markdown['body'], 'canonical:' ), 'Markdown mirror includes front matter and canonical metadata.' );
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
	$limit = $follow_redirects ? 5 : 1;

	for ( $i = 0; $i < $limit; $i++ ) {
		$context = stream_context_create(
			array(
				'http' => array(
					'ignore_errors' => true,
					'timeout'       => 10,
					'header'        => "User-Agent: NERV-Rewrite-Audit/1.0\r\n",
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
		$effective_url = absolute_url( $location, $effective_url );
	}

	return array(
		'status'  => $status,
		'headers' => implode( "\n", $headers ),
		'body'    => false === $body ? '' : $body,
		'url'     => $effective_url,
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

function markdown_url_from_llms( string $body ): string {
	if ( preg_match( '~\((https?://[^)]+\.md)\)~i', $body, $matches ) ) {
		return $matches[1];
	}

	return '';
}
