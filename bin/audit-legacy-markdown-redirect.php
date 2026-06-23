<?php
/**
 * Runtime regression audit for stale Markdown front matter redirects.
 *
 * @package NervTerminal
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$wp_load = $argv[1] ?? '/www/wwwroot/127_0_0_1/wp-load.php';
if ( ! is_file( $wp_load ) ) {
	fwrite( STDERR, 'Missing wp-load.php: ' . $wp_load . "\n" );
	exit( 1 );
}

require_once $wp_load;

$checks = array();
$post   = nerv_legacy_markdown_pick_post();
if ( ! $post instanceof WP_Post ) {
	fwrite( STDERR, "No published post/project is available for legacy Markdown redirect audit.\n" );
	exit( 1 );
}

$post_id      = (int) $post->ID;
$cache_file   = nerv_core_geo_markdown_cache_file( $post_id );
$old_content  = is_file( $cache_file ) ? (string) file_get_contents( $cache_file ) : '';
$map_existed  = false !== get_option( 'nerv_core_geo_slug_redirect_map', false );
$old_map      = get_option( 'nerv_core_geo_slug_redirect_map' );
$legacy_path  = 'nerv-legacy-markdown-audit-' . $post_id;
$legacy_url   = home_url( '/' . $legacy_path . '.md' );
$current_url  = nerv_core_geo_markdown_url( $post_id );

try {
	nerv_core_geo_write_markdown_cache( $post );
	$content = (string) file_get_contents( $cache_file );
	$content = preg_replace( '~^markdown:\s*"[^"]+"~m', 'markdown: "' . esc_url_raw( $legacy_url ) . '"', $content ) ?: $content;
	file_put_contents( $cache_file, $content, LOCK_EX );
	delete_option( 'nerv_core_geo_slug_redirect_map' );

	$legacy = http_get( $legacy_url, false );
	$location = header_value( $legacy['headers'], 'location' );
	add_check( $checks, 'legacy markdown redirects', in_array( $legacy['status'], array( 301, 302 ), true ), $legacy_url . ' returned HTTP ' . $legacy['status'] . '.' );
	add_check( $checks, 'legacy redirect target', untrailingslashit( $location ) === untrailingslashit( $current_url ), 'Location is ' . ( $location ?: 'missing' ) . '.' );

	$current = http_get( $current_url, false );
	add_check( $checks, 'current markdown stays 200', 200 === $current['status'], $current_url . ' returned HTTP ' . $current['status'] . '.' );
	add_check( $checks, 'current markdown content type', str_contains( strtolower( $current['headers'] ), 'content-type: text/markdown' ), 'Current Markdown response is text/markdown.' );
} finally {
	if ( '' !== $old_content ) {
		file_put_contents( $cache_file, $old_content, LOCK_EX );
	} else {
		nerv_core_geo_write_markdown_cache( $post );
	}

	if ( $map_existed ) {
		update_option( 'nerv_core_geo_slug_redirect_map', $old_map, false );
	} else {
		delete_option( 'nerv_core_geo_slug_redirect_map' );
	}
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

function nerv_legacy_markdown_pick_post(): ?WP_Post {
	$post_types = function_exists( 'nerv_core_geo_public_post_types' ) ? nerv_core_geo_public_post_types() : array( 'post' );
	$posts = get_posts(
		array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	return $posts[0] ?? null;
}

function add_check( array &$checks, string $label, bool $passed, string $detail ): void {
	$checks[] = array(
		'label'  => $label,
		'state'  => $passed ? 'pass' : 'fail',
		'detail' => $detail,
	);
}

function http_get( string $url, bool $follow_redirects ): array {
	$context = stream_context_create(
		array(
			'http' => array(
				'ignore_errors' => true,
				'timeout'       => 10,
				'follow_location' => $follow_redirects ? 1 : 0,
				'header'        => "User-Agent: NERV-Legacy-Markdown-Audit/1.0\r\n",
			),
		)
	);
	$body = @file_get_contents( $url, false, $context );
	$headers = $http_response_header ?? array();

	return array(
		'status'  => status_from_headers( $headers ),
		'headers' => implode( "\n", $headers ),
		'body'    => false === $body ? '' : $body,
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

function header_value( string $headers, string $name ): string {
	if ( preg_match( '~^' . preg_quote( $name, '~' ) . ':\s*(.+)$~im', $headers, $matches ) ) {
		return trim( $matches[1] );
	}

	return '';
}
