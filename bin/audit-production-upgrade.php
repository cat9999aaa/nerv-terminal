<?php
/**
 * Production upgrade readiness audit for public NERV releases and routes.
 *
 * @package NervTerminal
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$site = rtrim( $argv[1] ?? getenv( 'NERV_SITE_URL' ) ?: 'https://dashen.wang', '/' );
$repo = $argv[2] ?? getenv( 'NERV_RELEASE_REPO' ) ?: 'cat9999aaa/nerv-terminal';
$checks = array();

$release = github_latest_release( $repo );
add_check( $checks, 'github latest release', ! empty( $release['version'] ), 'Latest release is ' . ( $release['version'] ?? 'missing' ) . '.' );

if ( ! empty( $release['version'] ) ) {
	foreach ( array( 'theme' => 'nerv-terminal-theme-', 'plugin' => 'nerv-core-plugin-', 'bundle' => 'nerv-terminal-bundle-' ) as $label => $prefix ) {
		$asset = release_asset( $release, $prefix . $release['version'] . '.zip' );
		add_check( $checks, 'release asset ' . $label, ! empty( $asset['url'] ) && (int) ( $asset['size'] ?? 0 ) > 0, ( $asset['name'] ?? $prefix . $release['version'] . '.zip' ) . ' size ' . number_format( (int) ( $asset['size'] ?? 0 ) ) . ' bytes.' );
	}
}

$health = http_get_json( $site . '/?nerv_health=1' );
$home = http_get( $site . '/', true );
add_check( $checks, 'production home route', 200 === $home['status'], 'Home returned HTTP ' . $home['status'] . ' at ' . $home['url'] . '.' );
if ( ! empty( $release['version'] ) ) {
	$core_version = (string) ( $health['coreVersion'] ?? response_header_value( $home['headers'], 'x-nerv-core' ) );
	$theme_version = (string) ( $health['themeVersion'] ?? response_header_value( $home['headers'], 'x-nerv-theme' ) );
	$source = $health ? 'health endpoint' : 'response header';
	add_check( $checks, 'production health signal', ! empty( $health ) || '' !== response_header_value( $home['headers'], 'x-nerv-core' ) || '' !== response_header_value( $home['headers'], 'x-nerv-theme' ), 'Runtime version source is ' . $source . '.' );
	add_check( $checks, 'production core version', version_at_least( $core_version, $release['version'] ), 'Core version is ' . ( $core_version ?: 'missing' ) . '; latest is ' . $release['version'] . '.' );
	add_check( $checks, 'production theme version', version_at_least( $theme_version, $release['version'] ), 'Theme version is ' . ( $theme_version ?: 'missing' ) . '; latest is ' . $release['version'] . '.' );
}

$blog = http_get( $site . '/blog/page/444/', true );
add_check( $checks, 'production blog pagination', 200 === $blog['status'] && ! body_looks_like_404( $blog['body'] ), '/blog/page/444/ final HTTP ' . $blog['status'] . ' at ' . $blog['url'] . '.' );

$partners = http_get( $site . '/partners/', true );
add_check( $checks, 'production partners route', 200 === $partners['status'], '/partners/ final HTTP ' . $partners['status'] . ' at ' . $partners['url'] . '.' );
add_check( $checks, 'production partners no loop', ! $partners['loop'] && $partners['redirects'] <= 1, '/partners/ redirects=' . $partners['redirects'] . ', loop=' . ( $partners['loop'] ? 'true' : 'false' ) . '.' );

$llms = http_get( $site . '/llms.txt' );
add_check( $checks, 'production llms route', 200 === $llms['status'] && str_contains( strtolower( $llms['headers'] ), 'content-type: text/plain' ), '/llms.txt returned HTTP ' . $llms['status'] . '.' );

$markdown_url = markdown_url_from_llms( $llms['body'] );
add_check( $checks, 'production markdown discovered', '' !== $markdown_url, 'First Markdown URL is ' . ( $markdown_url ?: 'missing' ) . '.' );

if ( '' !== $markdown_url ) {
	$markdown = http_get( $markdown_url );
	$front_matter_markdown = front_matter_value( $markdown['body'], 'markdown' );
	add_check( $checks, 'production markdown route', 200 === $markdown['status'] && str_contains( strtolower( $markdown['headers'] ), 'content-type: text/markdown' ), $markdown_url . ' returned HTTP ' . $markdown['status'] . '.' );
	add_check( $checks, 'production markdown front matter current', $front_matter_markdown === $markdown_url, 'Front matter markdown URL is ' . ( $front_matter_markdown ?: 'missing' ) . '.' );
	if ( '' !== $front_matter_markdown && $front_matter_markdown !== $markdown_url ) {
		$stale = http_get( $front_matter_markdown, true );
		add_check( $checks, 'production stale markdown self-healed', 200 === $stale['status'] && $stale['url'] === $markdown_url, 'Stale front matter URL final HTTP ' . $stale['status'] . ' at ' . $stale['url'] . '.' );
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
	fwrite( STDERR, "\nProduction upgrade checklist:\n" );
	fwrite( STDERR, "1. Update NERV Core and NERV Terminal to the latest GitHub Release.\n" );
	fwrite( STDERR, "2. Open WordPress Settings -> Permalinks and save once.\n" );
	fwrite( STDERR, "3. Run NERV主题 · 工具 -> Refresh Markdown mirrors.\n" );
	fwrite( STDERR, "4. Clear Cloudflare, Baota, and page-cache plugin caches, then rerun this audit.\n" );
	exit( 1 );
}

function github_latest_release( string $repo ): array {
	$url = 'https://api.github.com/repos/' . trim( $repo, '/' ) . '/releases/latest';
	$response = http_get_json( $url );
	if ( ! is_array( $response ) ) {
		return array();
	}

	$tag = (string) ( $response['tag_name'] ?? '' );
	$version = preg_replace( '/^v/i', '', $tag ) ?: '';
	$assets = array();
	foreach ( (array) ( $response['assets'] ?? array() ) as $asset ) {
		if ( ! is_array( $asset ) ) {
			continue;
		}
		$name = (string) ( $asset['name'] ?? '' );
		if ( '' === $name ) {
			continue;
		}
		$assets[ $name ] = array(
			'name' => $name,
			'url'  => (string) ( $asset['browser_download_url'] ?? '' ),
			'size' => (int) ( $asset['size'] ?? 0 ),
		);
	}

	return array(
		'tag'     => $tag,
		'version' => $version,
		'assets'  => $assets,
	);
}

function release_asset( array $release, string $name ): array {
	return is_array( $release['assets'][ $name ] ?? null ) ? $release['assets'][ $name ] : array();
}

function version_at_least( string $current, string $latest ): bool {
	return '' !== $current && '' !== $latest && version_compare( $current, $latest, '>=' );
}

function http_get_json( string $url ): array {
	$context = stream_context_create(
		array(
			'http' => array(
				'ignore_errors' => true,
				'timeout'       => 12,
				'header'        => "Accept: application/vnd.github+json\r\nUser-Agent: NERV-Production-Audit/1.0\r\n",
			),
		)
	);
	$body = @file_get_contents( $url, false, $context );
	if ( false === $body ) {
		return array();
	}

	$data = json_decode( $body, true );
	return is_array( $data ) ? $data : array();
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
					'timeout'       => 12,
					'header'        => "User-Agent: NERV-Production-Audit/1.0\r\n",
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

function response_header_value( string $headers, string $name ): string {
	foreach ( preg_split( '/\R/', $headers ) ?: array() as $header ) {
		if ( preg_match( '/^' . preg_quote( $name, '/' ) . ':\s*(.+)$/i', $header, $matches ) ) {
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

function front_matter_value( string $body, string $key ): string {
	if ( preg_match( '~^' . preg_quote( $key, '~' ) . ':\s*"([^"]*)"~mi', $body, $matches ) ) {
		return html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	return '';
}

function html_title( string $body ): string {
	if ( preg_match( '~<title[^>]*>(.*?)</title>~is', $body, $matches ) ) {
		return trim( html_entity_decode( wp_strip_tags_fallback( $matches[1] ), ENT_QUOTES, 'UTF-8' ) );
	}

	return '(no title)';
}

function body_looks_like_404( string $body ): bool {
	$title = strtolower( html_title( $body ) );
	if ( str_contains( $title, '404' ) || str_contains( $title, 'not found' ) || str_contains( $title, '未找到' ) ) {
		return true;
	}

	return (bool) preg_match( '~<(?:body|main)[^>]*class=["\'][^"\']*(?:error404|not-found)[^"\']*["\']~i', $body );
}

function wp_strip_tags_fallback( string $html ): string {
	return preg_replace( '~<[^>]+>~', '', $html ) ?? $html;
}
