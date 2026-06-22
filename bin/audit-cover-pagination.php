<?php
/**
 * Static and runtime audit for pagination routes and cover ratios.
 *
 * @package NervTerminal
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$root = dirname( __DIR__ );
$checks = array();

$theme_functions = file_get_contents( $root . '/theme/functions.php' );
$dashboard = file_get_contents( $root . '/theme/inc/dashboard-render.php' );
$adapter = file_get_contents( $root . '/theme/assets/css/exoframe/theme-adapter.css' );
$bundle = file_get_contents( $root . '/theme/assets/css/frontend.bundle.css' );
$cover = file_get_contents( $root . '/plugin/inc/cover-pipeline.php' );
$blocks = file_get_contents( $root . '/plugin/inc/blocks.php' );
$editor_js = file_get_contents( $root . '/plugin/assets/js/blocks.js' );

add_check( $checks, 'view pagination rewrite', is_string( $theme_functions ) && str_contains( $theme_functions, "'^' . \$view . '/page/([0-9]+)/?$'" ), 'Runtime views register /page/N rewrite rules.' );
add_check( $checks, 'overflow pagination redirect', is_string( $theme_functions ) && str_contains( $theme_functions, 'nerv_terminal_maybe_redirect_overflow_view_page' ), 'Overflow view pages redirect to the last valid page.' );
add_check( $checks, 'pagination base uses view urls', is_string( $dashboard ) && str_contains( $dashboard, 'nerv_terminal_pagination_base' ) && str_contains( $dashboard, 'nerv_terminal_pagination_format' ), 'Pagination links use stable /view/page/N URLs and respect permalink trailing slash rules.' );

foreach ( array( 'theme adapter' => $adapter, 'frontend bundle' => $bundle ) as $label => $css ) {
	add_check( $checks, $label . ' archive 5x2', is_string( $css ) && preg_match( '/\\.nerv-card-image\\s*\\{[^}]*aspect-ratio:\\s*5\\s*\\/\\s*2/s', $css ), 'Default card image ratio remains 5:2.' );
	add_check( $checks, $label . ' project only 1x1', is_string( $css ) && preg_match( '/\\.nerv-project-card \\.nerv-card-image,\\s*\\n\\.nerv-partner-logo\\s*\\{\\s*aspect-ratio:\\s*1\\s*\\/\\s*1/s', $css ) && ! preg_match( '/(^|\\n)\\.nerv-card-image,\\s*\\n\\.nerv-partner-logo\\s*\\{\\s*aspect-ratio:\\s*1\\s*\\/\\s*1/s', $css ), '1:1 override is scoped to projects and partners only.' );
}

add_check( $checks, 'cover prompt ratio token', is_string( $cover ) && str_contains( $cover, '{ratio_label}' ) && ! str_contains( $cover, 'original square editorial thumbnail' ), 'Default AI prompt is ratio-aware and no longer square-only.' );
add_check( $checks, 'cover ai wide size', is_string( $cover ) && str_contains( $cover, "return '1536x640';" ) && str_contains( $cover, "return '1024x1024';" ), 'AI cover request size changes by target ratio.' );
add_check( $checks, 'cover history ratio model', is_string( $cover ) && str_contains( $cover, "'ratio'         => nerv_core_cover_normalize_ratio" ) && str_contains( $cover, "'model'         => sanitize_text_field" ), 'Cover history stores ratio and model.' );
add_check( $checks, 'cover auto hook', is_string( $cover ) && str_contains( $cover, "add_action( 'wp_after_insert_post', 'nerv_core_cover_maybe_auto_generate'" ) && str_contains( $cover, 'has_post_thumbnail' ), 'Auto-generation is hooked with featured-image protection.' );
add_check( $checks, 'partner cover supported', is_string( $cover ) && str_contains( $cover, "post_type_exists( 'partner' )" ), 'Partner records can use the 1:1 fallback cover pipeline.' );
add_check( $checks, 'editor 1x1 preview', is_string( $blocks ) && str_contains( $blocks, "'ratio1x1'" ) && is_string( $editor_js ) && str_contains( $editor_js, "coverRatioFrame( '1:1 / 1200x1200'" ), 'Editor preview exposes the 1:1 cover variant.' );

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
