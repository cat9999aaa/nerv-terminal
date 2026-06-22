<?php
/**
 * AI crawler monitoring for GEO visibility.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_geo_crawler_default_bots(): array {
	return array(
		'gptbot'           => array(
			'label'   => 'GPTBot',
			'pattern' => 'GPTBot',
		),
		'chatgpt-user'     => array(
			'label'   => 'ChatGPT-User',
			'pattern' => 'ChatGPT-User',
		),
		'claudebot'        => array(
			'label'   => 'ClaudeBot',
			'pattern' => 'ClaudeBot',
		),
		'perplexitybot'    => array(
			'label'   => 'PerplexityBot',
			'pattern' => 'PerplexityBot',
		),
		'google-extended'  => array(
			'label'   => 'Google-Extended',
			'pattern' => 'Google-Extended',
		),
		'googleother'      => array(
			'label'   => 'GoogleOther',
			'pattern' => 'GoogleOther',
		),
		'ccbot'            => array(
			'label'   => 'CCBot',
			'pattern' => 'CCBot',
		),
	);
}

function nerv_core_geo_crawler_default_options(): array {
	$bots = array();
	foreach ( nerv_core_geo_crawler_default_bots() as $key => $bot ) {
		$bots[ $key ] = true;
	}

	return array(
		'enabled'        => true,
		'retention_days' => 30,
		'bots'           => $bots,
	);
}

function nerv_core_geo_crawler_options(): array {
	$options = get_option( 'nerv_core_geo_crawler_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	$options = wp_parse_args( $options, nerv_core_geo_crawler_default_options() );
	$options['retention_days'] = max( 1, min( 365, absint( $options['retention_days'] ?? 30 ) ) );
	$options['bots'] = is_array( $options['bots'] ?? null ) ? $options['bots'] : array();

	foreach ( nerv_core_geo_crawler_default_bots() as $key => $bot ) {
		if ( ! array_key_exists( $key, $options['bots'] ) ) {
			$options['bots'][ $key ] = true;
		}
	}

	return $options;
}

function nerv_core_geo_crawler_sanitize_options( $input ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	$bots = array();
	foreach ( nerv_core_geo_crawler_default_bots() as $key => $bot ) {
		$bots[ $key ] = ! empty( $input['bots'][ $key ] );
	}

	return array(
		'enabled'        => ! empty( $input['enabled'] ),
		'retention_days' => max( 1, min( 365, absint( $input['retention_days'] ?? 30 ) ) ),
		'bots'           => $bots,
	);
}

add_action( 'template_redirect', 'nerv_core_geo_crawler_robots_request', -30 );
function nerv_core_geo_crawler_robots_request(): void {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( (string) wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( 'robots.txt' !== trim( rawurldecode( $path ), '/' ) ) {
		return;
	}

	nocache_headers();
	header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );
	echo apply_filters( 'robots_txt', nerv_core_geo_crawler_base_robots_txt(), (bool) get_option( 'blog_public' ) );
	exit;
}

add_filter( 'robots_txt', 'nerv_core_geo_crawler_robots_txt', 20, 2 );
function nerv_core_geo_crawler_robots_txt( string $output, bool $public ): string {
	$options = nerv_core_geo_crawler_options();
	if ( empty( $options['enabled'] ) ) {
		return $output;
	}

	$lines = array(
		'',
		'# NERV GEO AI crawler policy',
	);
	foreach ( nerv_core_geo_crawler_default_bots() as $key => $bot ) {
		$allowed = ! empty( $options['bots'][ $key ] );
		$lines[] = 'User-agent: ' . $bot['pattern'];
		$lines[] = $allowed ? 'Allow: /' : 'Disallow: /';
	}

	return rtrim( $output ) . "\n" . implode( "\n", $lines ) . "\n";
}

function nerv_core_geo_crawler_base_robots_txt(): string {
	$output = "User-agent: *\n";
	if ( (bool) get_option( 'blog_public' ) ) {
		$output .= "Disallow: /wp-admin/\n";
		$output .= "Allow: /wp-admin/admin-ajax.php\n";
	} else {
		$output .= "Disallow: /\n";
	}

	return $output;
}

add_action( 'admin_init', 'nerv_core_geo_crawler_register_settings' );
function nerv_core_geo_crawler_register_settings(): void {
	register_setting(
		'nerv_core_settings',
		'nerv_core_geo_crawler_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'nerv_core_geo_crawler_sanitize_options',
			'default'           => nerv_core_geo_crawler_default_options(),
		)
	);
}

add_action( 'template_redirect', 'nerv_core_geo_crawler_capture_request', -20 );
function nerv_core_geo_crawler_capture_request(): void {
	if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
		return;
	}

	$options = nerv_core_geo_crawler_options();
	if ( empty( $options['enabled'] ) ) {
		return;
	}

	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';
	if ( '' === $user_agent ) {
		return;
	}

	$bot = nerv_core_geo_crawler_detect_bot( $user_agent, $options );
	if ( ! $bot ) {
		return;
	}

	nerv_core_geo_crawler_record_hit( $bot, $user_agent );
}

function nerv_core_geo_crawler_detect_bot( string $user_agent, array $options = array() ): array {
	$options = $options ? $options : nerv_core_geo_crawler_options();
	$enabled = is_array( $options['bots'] ?? null ) ? $options['bots'] : array();

	foreach ( nerv_core_geo_crawler_default_bots() as $key => $bot ) {
		if ( empty( $enabled[ $key ] ) ) {
			continue;
		}
		if ( false !== stripos( $user_agent, $bot['pattern'] ) ) {
			return array(
				'key'   => $key,
				'label' => $bot['label'],
			);
		}
	}

	return array();
}

function nerv_core_geo_crawler_empty_stats(): array {
	return array(
		'totals' => array(),
		'recent' => array(),
	);
}

function nerv_core_geo_crawler_stats(): array {
	$stats = get_option( 'nerv_core_geo_crawler_stats', array() );
	if ( ! is_array( $stats ) ) {
		$stats = array();
	}

	return wp_parse_args( $stats, nerv_core_geo_crawler_empty_stats() );
}

function nerv_core_geo_crawler_record_hit( array $bot, string $user_agent ): void {
	$stats   = nerv_core_geo_crawler_stats();
	$options = nerv_core_geo_crawler_options();
	$key     = sanitize_key( (string) ( $bot['key'] ?? '' ) );
	if ( '' === $key ) {
		return;
	}

	$stats['totals'][ $key ] = absint( $stats['totals'][ $key ] ?? 0 ) + 1;
	$entry = array(
		'time'  => current_time( 'mysql' ),
		'bot'   => $key,
		'label' => sanitize_text_field( (string) ( $bot['label'] ?? $key ) ),
		'url'   => esc_url_raw( nerv_core_geo_crawler_current_url() ),
		'title' => sanitize_text_field( nerv_core_geo_crawler_current_title() ),
		'ua'    => sanitize_text_field( substr( $user_agent, 0, 240 ) ),
	);

	$recent = is_array( $stats['recent'] ?? null ) ? $stats['recent'] : array();
	array_unshift( $recent, $entry );
	$stats['recent'] = nerv_core_geo_crawler_prune_recent( $recent, absint( $options['retention_days'] ?? 30 ) );

	update_option( 'nerv_core_geo_crawler_stats', $stats, false );
}

function nerv_core_geo_crawler_prune_recent( array $recent, int $retention_days ): array {
	$cutoff = time() - ( max( 1, $retention_days ) * DAY_IN_SECONDS );
	$kept   = array();

	foreach ( $recent as $entry ) {
		$time = strtotime( (string) ( $entry['time'] ?? '' ) );
		if ( $time && $time < $cutoff ) {
			continue;
		}
		$kept[] = $entry;
		if ( count( $kept ) >= 100 ) {
			break;
		}
	}

	return $kept;
}

function nerv_core_geo_crawler_current_url(): string {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

	return home_url( $request_uri );
}

function nerv_core_geo_crawler_current_title(): string {
	if ( is_singular() ) {
		return get_the_title( get_queried_object_id() );
	}

	if ( is_post_type_archive() ) {
		return post_type_archive_title( '', false );
	}

	if ( is_category() || is_tag() || is_tax() ) {
		return single_term_title( '', false );
	}

	if ( is_search() ) {
		return __( 'Search results', 'nerv-core' );
	}

	if ( is_404() ) {
		return __( '404', 'nerv-core' );
	}

	return get_bloginfo( 'name' );
}

function nerv_core_geo_crawler_summary( int $days = 7 ): array {
	$stats  = nerv_core_geo_crawler_stats();
	$recent = is_array( $stats['recent'] ?? null ) ? $stats['recent'] : array();
	$cutoff = time() - ( max( 1, $days ) * DAY_IN_SECONDS );
	$window = array();
	$total  = 0;

	foreach ( $recent as $entry ) {
		$time = strtotime( (string) ( $entry['time'] ?? '' ) );
		if ( ! $time || $time < $cutoff ) {
			continue;
		}

		$key = sanitize_key( (string) ( $entry['bot'] ?? '' ) );
		if ( '' === $key ) {
			continue;
		}
		$window[ $key ] = absint( $window[ $key ] ?? 0 ) + 1;
		$total++;
	}

	return array(
		'total'  => $total,
		'window' => $window,
		'totals' => is_array( $stats['totals'] ?? null ) ? $stats['totals'] : array(),
		'recent' => $recent,
	);
}

add_filter( 'nerv_terminal_monitor_rows', 'nerv_core_geo_crawler_frontend_monitor_rows' );
function nerv_core_geo_crawler_frontend_monitor_rows( array $rows ): array {
	if ( ! function_exists( 'nerv_terminal_monitor_source' ) || 'crawlers' !== nerv_terminal_monitor_source() ) {
		return $rows;
	}

	$options = nerv_core_geo_crawler_options();
	if ( empty( $options['enabled'] ) ) {
		return $rows;
	}

	$summary = nerv_core_geo_crawler_summary( 7 );
	$window  = is_array( $summary['window'] ?? null ) ? $summary['window'] : array();
	$total   = absint( $summary['total'] ?? 0 );

	return array(
		array(
			'label' => __( 'AI CRAWLERS', 'nerv-core' ),
			'value' => sprintf(
				/* translators: %d: AI crawler hits in the last seven days. */
				__( '%d / 7D', 'nerv-core' ),
				$total
			),
			'level' => nerv_core_geo_crawler_monitor_level( $total, 8 ),
		),
		array(
			'label' => __( 'GPTBOT', 'nerv-core' ),
			'value' => (string) absint( $window['gptbot'] ?? 0 ),
			'level' => nerv_core_geo_crawler_monitor_level( absint( $window['gptbot'] ?? 0 ), 5 ),
		),
		array(
			'label' => __( 'CLAUDEBOT', 'nerv-core' ),
			'value' => (string) absint( $window['claudebot'] ?? 0 ),
			'level' => nerv_core_geo_crawler_monitor_level( absint( $window['claudebot'] ?? 0 ), 5 ),
		),
		array(
			'label' => __( 'PERPLEXITY', 'nerv-core' ),
			'value' => (string) absint( $window['perplexitybot'] ?? 0 ),
			'level' => nerv_core_geo_crawler_monitor_level( absint( $window['perplexitybot'] ?? 0 ), 5 ),
		),
	);
}

function nerv_core_geo_crawler_monitor_level( int $count, int $full_scale ): int {
	if ( $count <= 0 ) {
		return 4;
	}

	return min( 100, max( 8, (int) round( ( $count / max( 1, $full_scale ) ) * 100 ) ) );
}
