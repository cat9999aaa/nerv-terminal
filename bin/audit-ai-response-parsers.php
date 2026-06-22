<?php
/**
 * Parser audit for supported AI provider response shapes.
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

$cover_cases = array(
	'openai url' => array( 'data' => array( array( 'url' => 'https://example.com/cover.png' ) ) ),
	'openai base64' => array( 'data' => array( array( 'b64_json' => 'iVBORw0KGgo=', 'mime_type' => 'image/png' ) ) ),
	'stability artifact' => array( 'artifacts' => array( array( 'base64' => 'iVBORw0KGgo=', 'mime_type' => 'image/png' ) ) ),
	'responses output image url' => array( 'output' => array( array( 'content' => array( array( 'image_url' => 'https://example.com/generated.webp' ) ) ) ) ),
	'flat image url' => array( 'image_url' => 'https://example.com/image.jpg' ),
);

foreach ( $cover_cases as $label => $response ) {
	$payload = nerv_core_cover_extract_image_payload( $response );
	add_check( $checks, 'cover parser ' . $label, ! is_wp_error( $payload ) && in_array( (string) ( $payload['type'] ?? '' ), array( 'url', 'b64_json' ), true ), 'Cover parser accepts ' . $label . ' response shape.' );
}

$key_cases = array(
	'chat json string' => array( 'choices' => array( array( 'message' => array( 'content' => '["One point","Two point","Three point"]' ) ) ) ),
	'chat content parts' => array( 'choices' => array( array( 'message' => array( 'content' => array( array( 'text' => 'First point' ), array( 'text' => "Second point\nThird point" ) ) ) ) ) ),
	'responses output text' => array( 'output' => array( array( 'content' => array( array( 'text' => '["Alpha","Beta","Gamma"]' ) ) ) ) ),
	'data text lines' => array( 'data' => array( array( 'text' => "North signal\nSouth signal\nCentral signal" ) ) ),
	'flat output text' => array( 'output_text' => '["Primary","Secondary","Tertiary"]' ),
);

foreach ( $key_cases as $label => $response ) {
	$points = nerv_core_key_points_extract_response( $response );
	add_check( $checks, 'key points parser ' . $label, count( $points ) >= 3, 'KEY POINTS parser extracts at least three points from ' . $label . '.' );
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
