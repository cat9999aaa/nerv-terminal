<?php
/**
 * Static audit for the NERV Core admin control surface.
 *
 * @package NervTerminal
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$root = dirname( __DIR__ );
$css_file = $root . '/plugin/assets/css/admin-control.css';
$js_file = $root . '/plugin/assets/js/admin-control.js';
$admin_file = $root . '/plugin/inc/admin-page.php';
$tools_file = $root . '/plugin/inc/tools.php';
$image_optimizer_file = $root . '/plugin/inc/image-optimizer.php';
$checks = array();

$css = is_file( $css_file ) ? file_get_contents( $css_file ) : '';
$js = is_file( $js_file ) ? file_get_contents( $js_file ) : '';
$admin = is_file( $admin_file ) ? file_get_contents( $admin_file ) : '';
$tools = is_file( $tools_file ) ? file_get_contents( $tools_file ) : '';
$image_optimizer = is_file( $image_optimizer_file ) ? file_get_contents( $image_optimizer_file ) : '';
$geo_slug_batch = is_file( $root . '/plugin/inc/geo-slug-batch.php' ) ? file_get_contents( $root . '/plugin/inc/geo-slug-batch.php' ) : '';

add_check( $checks, 'admin css exists', is_string( $css ) && '' !== $css, 'admin-control.css is readable.' );
add_check( $checks, 'admin js exists', is_string( $js ) && '' !== $js, 'admin-control.js is readable.' );
add_check( $checks, 'light shell override', str_contains( (string) $css, '.nerv-control-wrap .nerv-control-shell' ) && str_contains( (string) $css, 'background: #fff;' ), 'Control shell has a white WordPress-native override.' );
add_check( $checks, 'loading shell override', str_contains( (string) $css, '.nerv-control-wrap .nerv-control-shell--loading' ), 'Loading state is included in the white override.' );
add_check( $checks, 'slug batch controls', str_contains( (string) $js, '每批数量' ) && str_contains( (string) $js, '并发线程' ) && str_contains( (string) $js, "runSlugBatch( 'pause' )" ), 'GEO slug batch exposes batch size, concurrency, pause, resume, and stop controls.' );
add_check( $checks, 'slug batch primary entry', str_contains( (string) $js, '旧文章链接 GEO 化' ) && str_contains( (string) $js, '批量多线程改文章链接' ) && str_contains( (string) $css, '.nerv-control-primary-task' ), 'GEO slug batch is promoted as a visible primary task on the GEO settings page.' );
add_check( $checks, 'slug batch runtime lock', str_contains( (string) $geo_slug_batch, 'nerv_core_geo_slug_acquire_lock' ) && str_contains( (string) $geo_slug_batch, '已有 GEO slug 批次运行中' ), 'GEO slug batch has a runtime lock to prevent overlapping cron/manual batches.' );
add_check( $checks, 'slug batch token release', str_contains( (string) $geo_slug_batch, 'nerv_core_geo_slug_release_lock( string $token )' ) && str_contains( (string) $geo_slug_batch, '(string) ( $lock[\'token\'] ?? \'\' ) !== $token' ), 'GEO slug batch only releases the lock token it acquired.' );
add_check( $checks, 'model chip fallback picker', str_contains( (string) $js, 'nerv-control-model-chip' ) && str_contains( (string) $js, '全部加入备用' ), 'AI fallback models can be selected from cached model chips.' );
add_check( $checks, 'ai bulk fallback actions', str_contains( (string) $js, '全部供应商模型加入备用' ) && str_contains( (string) $js, '文本模型复制到图片模型' ) && str_contains( (string) $css, '.nerv-control-ai-quick-actions' ), 'AI providers expose one-click fallback setup and text-to-image model copy actions.' );
add_check( $checks, 'admin visible copy localized', ! preg_match( '/Theme Settings Page|This month|External calls|Tools Ready|Partner Display|Color Palette|Day \/ Night Mode|Save [A-Za-z ]+ Settings/', (string) $js ), 'Previously mixed English admin labels are localized in the React control surface.' );
add_check( $checks, 'partner redirect data', str_contains( (string) $admin, "'redirects'" ) && str_contains( (string) $admin, "'finalUrl'" ), 'Partner health rows expose redirect count and final URL.' );
add_check( $checks, 'social webp batch tool', str_contains( (string) $js, 'refresh_social_covers' ) && str_contains( (string) $admin, 'nerv_core_tools_refresh_social_covers' ) && str_contains( (string) $admin, 'nerv_core_image_optimizer_social_cover_queue_status' ), 'Tools page can pre-generate WebP social sharing images and show queue status.' );
add_check( $checks, 'media webp batch tool', str_contains( (string) $js, 'refresh_media_webp' ) && str_contains( (string) $admin, 'nerv_core_tools_refresh_media_webp' ) && str_contains( (string) $admin, 'nerv_core_image_optimizer_media_webp_queue_status' ), 'Tools page can backfill uploaded JPEG/PNG media WebP files and show queue status.' );
add_check( $checks, 'media webp failure details', str_contains( (string) $js, 'mediaQueue.lastError' ) && str_contains( (string) $tools, 'lastError' ) && str_contains( (string) $image_optimizer, 'last_error' ), 'Tools page exposes the last failed media WebP conversion reason.' );

$dark_rules = preg_match_all( '/background\s*:\s*(#0[0-9a-f]{2,6}|#1[0-9a-f]{2,6}|black|rgba\(\s*0\s*,\s*0\s*,\s*0)/i', (string) $css, $matches );
$late_light_override = strpos( (string) $css, '.nerv-control-wrap :where(' );
add_check( $checks, 'dark rules capped by light override', false !== $late_light_override && $dark_rules < 80, 'Dark legacy rules: ' . (int) $dark_rules . '; light override is present late in the file.' );
foreach ( array( 'nerv-control-primary-task', 'nerv-control-panel-row', 'nerv-control-social-row', 'nerv-control-mobile-tab-row', 'nerv-control-partner-row', 'nerv-control-tools-grid', 'nerv-control-effects-preview', 'nerv-control-appearance-grid', 'nerv-control-ai-quick-actions', 'nerv-control-model-cache', 'nerv-control-model-picker' ) as $class_name ) {
	add_check( $checks, 'light override for ' . $class_name, str_contains( (string) $css, '.' . $class_name ), $class_name . ' is covered by the WordPress light admin override.' );
}

$js_lines = substr_count( (string) $js, "\n" ) + 1;
add_check( $checks, 'admin js size tracked', $js_lines <= 4500, 'admin-control.js has ' . $js_lines . ' lines; split into modules if it grows past 4500.' );

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
