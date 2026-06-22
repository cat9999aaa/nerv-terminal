<?php
/**
 * Frontend acceptance audit for motion, contrast, and no-JS readability.
 *
 * @package NervTerminal
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$root = dirname( __DIR__ );
$site = rtrim( getenv( 'NERV_SITE_URL' ) ?: 'http://127.0.0.1', '/' );
$css_file = $root . '/theme/assets/css/frontend.css';
$js_file  = $root . '/theme/assets/js/frontend.js';
$checks = array();

$css = nerv_frontend_read_css_graph( $css_file );
$js  = file_get_contents( $js_file );

add_check( $checks, 'reduced-motion css', is_string( $css ) && str_contains( $css, '@media (prefers-reduced-motion: reduce)' ) && str_contains( $css, 'body.nerv-terminal-theme::before' ) && str_contains( $css, 'transform: none !important' ), 'CSS disables transitions, scanlines, transforms, and glow under reduced motion.' );
add_check( $checks, 'view transition guard', is_string( $js ) && str_contains( $js, 'prefers-reduced-motion: reduce' ) && str_contains( $js, '!reduceMotion' ), 'View Transitions API is skipped for reduced-motion visitors.' );
add_check( $checks, 'aa contrast text/panel', contrast_ratio( '#e8e4dc', '#0a0807' ) >= 4.5, 'Warm white text over panel background passes AA.' );
add_check( $checks, 'aa contrast green/panel', contrast_ratio( '#4ade80', '#0a0807' ) >= 4.5, 'Magi green text over panel background passes AA.' );
add_check( $checks, 'aa contrast amber/panel', contrast_ratio( '#ffb000', '#0a0807' ) >= 4.5, 'Amber text over panel background passes AA.' );
add_check( $checks, 'aa contrast red/black', contrast_ratio( '#ff3b30', '#050403' ) >= 4.5, 'NERV red over black passes AA.' );
add_check( $checks, 'aa contrast mobile tab inactive', contrast_ratio( '#ff8a80', '#050403' ) >= 4.5, 'Inactive mobile tab labels pass AA over the bottom bar.' );

$urls = array(
	'home'     => $site . '/',
	'more'     => $site . '/?nerv_more=1',
	'manifest' => $site . '/?nerv_manifest=1',
	'icon'     => $site . '/?nerv_icon=1&size=192',
);

foreach ( $urls as $label => $url ) {
	$response = http_get( $url );
	add_check( $checks, 'http ' . $label, 200 === $response['status'], $url . ' returned HTTP ' . $response['status'] . '.' );
	if ( 'home' === $label && 200 === $response['status'] ) {
		$html = $response['body'];
		add_check( $checks, 'no-js main readable', str_contains( $html, '<main class="nerv-main" id="main">' ) && str_contains( $html, 'nerv-column--center' ), 'Homepage renders primary content in HTML before frontend JS runs.' );
		add_check( $checks, 'semantic navigation', str_contains( $html, 'aria-label="Primary navigation"' ) && str_contains( $html, 'aria-label="Mobile app navigation"' ), 'Desktop and mobile navigation landmarks are present.' );
		add_check( $checks, 'no-js clock fallback', (bool) preg_match( '/data-nerv-clock>\d{2}:\d{2}:\d{2}</', $html ) && (bool) preg_match( '/data-nerv-clock-short>\d{2}:\d{2}</', $html ), 'Clock text renders as server-side time before JavaScript runs.' );
		add_check( $checks, 'seo meta description', (bool) preg_match( '/<meta name="description" content="[^"]{50,160}">/', $html ), 'Homepage outputs a concise meta description.' );
		add_check( $checks, 'card image link names', ! preg_match( '/<a class="nerv-card-image"(?![^>]*aria-label=)/', $html ), 'Decorative card image links have accessible names.' );
	} elseif ( 'more' === $label && 200 === $response['status'] ) {
		$html = $response['body'];
		add_check( $checks, 'mobile more status panels', str_contains( $html, 'nerv-panel--more' ) && str_contains( $html, 'nerv-panel--status' ) && str_contains( $html, 'nerv-panel--monitor' ) && str_contains( $html, 'nerv-panel--alert' ), 'Mobile MORE renders navigation plus status, monitor, and alert panels.' );
		add_check( $checks, 'mobile more footer copyright', str_contains( $html, 'nerv-panel--more-footer' ) && str_contains( $html, 'POWERED BY WORDPRESS' ), 'Mobile MORE includes footer copyright content.' );
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

function add_check( array &$checks, string $label, bool $passed, string $detail ): void {
	$checks[] = array(
		'label'  => $label,
		'state'  => $passed ? 'pass' : 'fail',
		'detail' => $detail,
	);
}

function nerv_frontend_read_css_graph( string $entry, array &$seen = array() ): string {
	$real = realpath( $entry );
	if ( false === $real || isset( $seen[ $real ] ) ) {
		return '';
	}

	$seen[ $real ] = true;
	$css = file_get_contents( $real );
	if ( ! is_string( $css ) ) {
		return '';
	}

	$base = dirname( $real );
	$output = $css;
	if ( preg_match_all( '/@import\s+url\(["\']?([^"\')]+)["\']?\);/', $css, $matches ) ) {
		foreach ( $matches[1] as $import ) {
			if ( str_starts_with( $import, 'http:' ) || str_starts_with( $import, 'https:' ) ) {
				continue;
			}
			$output .= "\n" . nerv_frontend_read_css_graph( $base . '/' . $import, $seen );
		}
	}

	return $output;
}

function http_get( string $url ): array {
	$context = stream_context_create(
		array(
			'http' => array(
				'ignore_errors' => true,
				'timeout'       => 8,
				'header'        => "User-Agent: NERV-Frontend-Audit/1.0\r\n",
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

function contrast_ratio( string $foreground, string $background ): float {
	$fg = relative_luminance( hex_to_rgb( $foreground ) );
	$bg = relative_luminance( hex_to_rgb( $background ) );
	$light = max( $fg, $bg );
	$dark  = min( $fg, $bg );

	return ( $light + 0.05 ) / ( $dark + 0.05 );
}

function hex_to_rgb( string $hex ): array {
	$hex = ltrim( $hex, '#' );
	return array(
		hexdec( substr( $hex, 0, 2 ) ),
		hexdec( substr( $hex, 2, 2 ) ),
		hexdec( substr( $hex, 4, 2 ) ),
	);
}

function relative_luminance( array $rgb ): float {
	$channels = array_map(
		static function ( int $value ): float {
			$value = $value / 255;
			return $value <= 0.03928 ? $value / 12.92 : ( ( $value + 0.055 ) / 1.055 ) ** 2.4;
		},
		$rgb
	);

	return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
}
