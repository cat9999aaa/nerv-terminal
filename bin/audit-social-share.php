<?php
/**
 * Runtime audit for social sharing image metadata.
 *
 * @package NervTerminal
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$url = $argv[1] ?? 'http://127.0.0.1/hello-world.html';
$checks = array();
$page = http_get( $url );

add_check( $checks, 'page http 200', 200 === $page['status'], $url . ' returned HTTP ' . $page['status'] . '.' );

$html = $page['body'];
$og_image = meta_content( $html, 'property', 'og:image' );
$twitter_image = meta_content( $html, 'name', 'twitter:image' );
$og_width = meta_content( $html, 'property', 'og:image:width' );
$og_height = meta_content( $html, 'property', 'og:image:height' );
$schema_images = schema_images( $html );

add_check( $checks, 'og image exists', '' !== $og_image, 'og:image is ' . ( $og_image ?: 'missing' ) . '.' );
add_check( $checks, 'twitter image matches', '' !== $twitter_image && $twitter_image === $og_image, 'twitter:image is ' . ( $twitter_image ?: 'missing' ) . '.' );
add_check( $checks, 'og dimensions 1200x600', '1200' === $og_width && '600' === $og_height, 'og:image dimensions are ' . ( $og_width ?: '?' ) . 'x' . ( $og_height ?: '?' ) . '.' );
add_check( $checks, 'schema image matches', in_array( $og_image, $schema_images, true ), 'JSON-LD image includes og:image.' );

if ( '' !== $og_image ) {
	$image = http_get( $og_image );
	$content_type = header_value( $image['headers'], 'content-type' );
	add_check( $checks, 'image http 200', 200 === $image['status'], $og_image . ' returned HTTP ' . $image['status'] . '.' );
	add_check( $checks, 'image content type', (bool) preg_match( '~^image/(webp|jpeg|png)~i', $content_type ), 'Image content-type is ' . ( $content_type ?: 'missing' ) . '.' );
	add_check( $checks, 'image is webp preferred', str_contains( strtolower( $content_type ), 'image/webp' ) || str_ends_with( strtolower( parse_url( $og_image, PHP_URL_PATH ) ?: '' ), '.webp' ), 'Social image should prefer WebP.' );
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

function meta_content( string $html, string $attribute, string $name ): string {
	$pattern = '~<meta\s+[^>]*' . preg_quote( $attribute, '~' ) . '=["\']' . preg_quote( $name, '~' ) . '["\'][^>]*content=["\']([^"\']+)["\'][^>]*>~i';
	if ( preg_match( $pattern, $html, $matches ) ) {
		return html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5 );
	}

	$pattern = '~<meta\s+[^>]*content=["\']([^"\']+)["\'][^>]*' . preg_quote( $attribute, '~' ) . '=["\']' . preg_quote( $name, '~' ) . '["\'][^>]*>~i';
	if ( preg_match( $pattern, $html, $matches ) ) {
		return html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5 );
	}

	return '';
}

function schema_images( string $html ): array {
	$images = array();
	if ( ! preg_match_all( '~<script\s+[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>~is', $html, $matches ) ) {
		return $images;
	}

	foreach ( $matches[1] as $json ) {
		$data = json_decode( html_entity_decode( trim( $json ), ENT_QUOTES | ENT_HTML5 ), true );
		collect_schema_images( $data, $images );
	}

	return array_values( array_unique( array_filter( $images ) ) );
}

function collect_schema_images( $node, array &$images ): void {
	if ( is_array( $node ) ) {
		foreach ( $node as $key => $value ) {
			if ( 'image' === $key ) {
				foreach ( (array) $value as $image ) {
					if ( is_string( $image ) ) {
						$images[] = $image;
					} elseif ( is_array( $image ) && is_string( $image['url'] ?? null ) ) {
						$images[] = $image['url'];
					}
				}
			}
			collect_schema_images( $value, $images );
		}
	}
}

function http_get( string $url ): array {
	$ch = curl_init( $url );
	curl_setopt_array(
		$ch,
		array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS      => 5,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_USERAGENT      => 'NERV Social Share Audit/1.0',
		)
	);
	$response = curl_exec( $ch );
	if ( false === $response ) {
		$error = curl_error( $ch );
		curl_close( $ch );
		return array( 'status' => 0, 'headers' => '', 'body' => $error );
	}
	$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
	$status = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
	curl_close( $ch );

	return array(
		'status'  => $status,
		'headers' => substr( (string) $response, 0, $header_size ),
		'body'    => substr( (string) $response, $header_size ),
	);
}

function header_value( string $headers, string $name ): string {
	if ( preg_match( '~^' . preg_quote( $name, '~' ) . ':\s*(.+)$~im', $headers, $matches ) ) {
		return trim( $matches[1] );
	}

	return '';
}
