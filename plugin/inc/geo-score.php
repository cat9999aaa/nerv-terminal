<?php
/**
 * GEO scoring engine and editor sidebar panel.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_geo_score_default_weights(): array {
	return array(
		'subtitle'       => 10,
		'key_points'     => 15,
		'h2'             => 10,
		'definition'     => 10,
		'faq'            => 10,
		'word_count'     => 10,
		'cover'          => 10,
		'internal_links' => 10,
		'freshness'      => 15,
	);
}

function nerv_core_geo_score_sanitize_weights( $input ): array {
	$defaults = nerv_core_geo_score_default_weights();
	if ( ! is_array( $input ) ) {
		return $defaults;
	}

	$output = array();
	foreach ( $defaults as $key => $default ) {
		$output[ $key ] = min( 30, max( 0, absint( $input[ $key ] ?? $default ) ) );
	}

	if ( 0 === array_sum( $output ) ) {
		return $defaults;
	}

	return $output;
}

function nerv_core_geo_score_weights(): array {
	return nerv_core_geo_score_sanitize_weights( get_option( 'nerv_core_geo_score_weights', array() ) );
}

function nerv_core_geo_score_post( WP_Post $post, array $context = array() ): array {
	$content      = array_key_exists( 'content', $context ) ? (string) $context['content'] : $post->post_content;
	$subtitle     = array_key_exists( 'subtitle', $context ) ? (string) $context['subtitle'] : (string) get_post_meta( $post->ID, '_nerv_subtitle', true );
	$featured_id  = absint( $context['featured_media'] ?? 0 );
	$weights      = nerv_core_geo_score_sanitize_weights( $context['weights'] ?? nerv_core_geo_score_weights() );
	$blocks       = parse_blocks( $content );
	$text         = trim( wp_strip_all_tags( do_blocks( $content ) ) );
	$word_count   = nerv_core_geo_score_word_count( $text );
	$internal     = nerv_core_geo_score_internal_links( $post, $content );
	$modified_raw = (string) ( $post->post_modified_gmt ?: $post->post_date_gmt );
	$modified_ts  = ( '' !== $modified_raw && ! str_starts_with( $modified_raw, '0000-' ) ) ? strtotime( $modified_raw ) : time();
	$fresh_days   = $modified_ts ? floor( ( time() - $modified_ts ) / DAY_IN_SECONDS ) : 9999;
	$has_subtitle = '' !== trim( $subtitle );
	$has_key      = nerv_core_geo_score_has_block( $blocks, 'nerv-core/key-points' );
	$has_faq      = nerv_core_geo_score_has_block( $blocks, 'nerv-core/faq' );
	$has_h2       = nerv_core_geo_score_has_heading_level( $blocks, 2 ) || preg_match( '~<h2[\s>]~i', $content );
	$has_definition = nerv_core_geo_score_has_definition_sentence( $text );
	$has_cover    = $featured_id > 0 || has_post_thumbnail( $post ) || ( function_exists( 'nerv_core_cover_url' ) && '' !== nerv_core_cover_url( (int) $post->ID, '5x2' ) );

	$checks = array(
		nerv_core_geo_score_check( 'subtitle', __( 'Subtitle', 'nerv-core' ), $weights['subtitle'], $has_subtitle, __( 'Add a NERV subtitle so search and AI summaries get a compact alternative headline.', 'nerv-core' ) ),
		nerv_core_geo_score_check( 'key_points', __( 'KEY POINTS', 'nerv-core' ), $weights['key_points'], $has_key, __( 'Add a KEY POINTS block with 3-5 concise takeaways near the top.', 'nerv-core' ) ),
		nerv_core_geo_score_check( 'h2', __( 'H2 structure', 'nerv-core' ), $weights['h2'], (bool) $has_h2, __( 'Add at least one H2 section so crawlers can understand the article structure.', 'nerv-core' ) ),
		nerv_core_geo_score_check( 'definition', __( 'Definition opening', 'nerv-core' ), $weights['definition'], $has_definition, __( 'Open with a direct definition or answer sentence in the first paragraph.', 'nerv-core' ) ),
		nerv_core_geo_score_check( 'faq', __( 'FAQ block', 'nerv-core' ), $weights['faq'], $has_faq, __( 'Add a FAQ block so FAQPage JSON-LD can be emitted.', 'nerv-core' ) ),
		nerv_core_geo_score_check( 'word_count', __( 'Word count', 'nerv-core' ), $weights['word_count'], $word_count >= 300, __( 'Expand the article toward at least 300 words for stronger standalone context.', 'nerv-core' ) ),
		nerv_core_geo_score_check( 'cover', __( 'Cover image', 'nerv-core' ), $weights['cover'], $has_cover, __( 'Set a featured image so the 5:2 and social cover pipeline has source media.', 'nerv-core' ) ),
		nerv_core_geo_score_check( 'internal_links', __( 'Internal links', 'nerv-core' ), $weights['internal_links'], $internal >= 2, __( 'Add at least two internal links to strengthen the semantic crawl path.', 'nerv-core' ) ),
		nerv_core_geo_score_check( 'freshness', __( 'Freshness', 'nerv-core' ), $weights['freshness'], $fresh_days <= 180, __( 'Refresh the article so dateModified stays recent.', 'nerv-core' ) ),
	);

	$raw_score = array_sum( array_column( array_filter( $checks, static function ( array $check ): bool {
		return (bool) $check['passed'];
	} ), 'points' ) );
	$max_score = max( 1, array_sum( $weights ) );
	$score     = (int) round( ( $raw_score / $max_score ) * 100 );

	return array(
		'score'          => min( 100, $score ),
		'max'            => 100,
		'grade'          => nerv_core_geo_score_grade( $score ),
		'raw_score'      => $raw_score,
		'raw_max'        => $max_score,
		'weights'        => $weights,
		'checks'         => $checks,
		'word_count'     => $word_count,
		'internal_links' => $internal,
		'fresh_days'     => $fresh_days,
	);
}

function nerv_core_geo_score_check( string $key, string $label, int $points, bool $passed, string $suggestion ): array {
	return array(
		'key'        => $key,
		'label'      => $label,
		'points'     => $points,
		'passed'     => $passed,
		'suggestion' => $suggestion,
	);
}

function nerv_core_geo_score_grade( int $score ): string {
	if ( $score >= 85 ) {
		return 'GREEN';
	}
	if ( $score >= 60 ) {
		return 'AMBER';
	}

	return 'RED';
}

function nerv_core_geo_score_has_block( array $blocks, string $name ): bool {
	foreach ( $blocks as $block ) {
		if ( $name === ( $block['blockName'] ?? '' ) ) {
			return true;
		}
		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && nerv_core_geo_score_has_block( $block['innerBlocks'], $name ) ) {
			return true;
		}
	}

	return false;
}

function nerv_core_geo_score_has_heading_level( array $blocks, int $level ): bool {
	foreach ( $blocks as $block ) {
		if ( 'core/heading' === ( $block['blockName'] ?? '' ) && (int) ( $block['attrs']['level'] ?? 2 ) === $level ) {
			return true;
		}
		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && nerv_core_geo_score_has_heading_level( $block['innerBlocks'], $level ) ) {
			return true;
		}
	}

	return false;
}

function nerv_core_geo_score_has_definition_sentence( string $text ): bool {
	$first = trim( preg_split( '/[.!?。！？]\s*/u', $text )[0] ?? '' );
	if ( '' === $first ) {
		return false;
	}

	return (bool) preg_match( '/\b(is|are|means|refers to|defines|helps|provides)\b|是|指|用于|帮助|提供/u', $first );
}

function nerv_core_geo_score_word_count( string $text ): int {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) ?? $text );
	if ( '' === $text ) {
		return 0;
	}

	$words = preg_split( '/\s+/u', $text );
	$count = is_array( $words ) ? count( array_filter( $words ) ) : 0;
	if ( $count >= 20 ) {
		return $count;
	}

	return (int) floor( strlen( $text ) / 5 );
}

function nerv_core_geo_score_internal_links( WP_Post $post, ?string $content = null ): int {
	preg_match_all( '~<a\s+[^>]*href=["\']([^"\']+)["\']~i', null === $content ? $post->post_content : $content, $matches );
	if ( empty( $matches[1] ) ) {
		return 0;
	}

	$host  = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	$count = 0;
	foreach ( $matches[1] as $url ) {
		$url_host = (string) wp_parse_url( html_entity_decode( $url ), PHP_URL_HOST );
		if ( '' === $url_host || $host === $url_host ) {
			$count++;
		}
	}

	return $count;
}

add_action( 'rest_api_init', 'nerv_core_geo_score_register_rest' );
function nerv_core_geo_score_register_rest(): void {
	register_rest_route(
		'nerv-core/v1',
		'/geo-score/(?P<id>\d+)',
		array(
			'methods'             => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
			'callback'            => 'nerv_core_geo_score_rest_preview',
			'permission_callback' => static function ( WP_REST_Request $request ): bool {
				return current_user_can( 'edit_post', absint( $request['id'] ) );
			},
			'args'                => array(
				'id' => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/geo-score-weights',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'nerv_core_geo_score_rest_weights',
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'nerv_core_geo_score_rest_save_weights',
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
			),
		)
	);
}

function nerv_core_geo_score_rest_preview( WP_REST_Request $request ): WP_REST_Response {
	$post_id = absint( $request['id'] );
	$post    = get_post( $post_id );
	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, nerv_core_geo_public_post_types(), true ) ) {
		return new WP_REST_Response( array( 'message' => __( 'Post is not available for GEO scoring.', 'nerv-core' ) ), 404 );
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$meta     = is_array( $params['meta'] ?? null ) ? $params['meta'] : array();
	$subtitle = array_key_exists( 'subtitle', $params ) ? (string) $params['subtitle'] : (string) ( $meta['_nerv_subtitle'] ?? '' );
	$result   = nerv_core_geo_score_post(
		$post,
		array(
			'content'        => array_key_exists( 'content', $params ) ? (string) $params['content'] : $post->post_content,
			'subtitle'       => sanitize_text_field( wp_unslash( $subtitle ) ),
			'featured_media' => absint( $params['featuredMedia'] ?? $params['featured_media'] ?? 0 ),
			'weights'        => is_array( $params['weights'] ?? null ) ? $params['weights'] : nerv_core_geo_score_weights(),
		)
	);

	return new WP_REST_Response( $result );
}

function nerv_core_geo_score_rest_weights(): WP_REST_Response {
	return new WP_REST_Response(
		array(
			'weights'  => nerv_core_geo_score_weights(),
			'defaults' => nerv_core_geo_score_default_weights(),
		)
	);
}

function nerv_core_geo_score_rest_save_weights( WP_REST_Request $request ): WP_REST_Response {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$weights = nerv_core_geo_score_sanitize_weights( $params['weights'] ?? $params );
	update_option( 'nerv_core_geo_score_weights', $weights, false );

	return new WP_REST_Response(
		array(
			'message'  => __( 'GEO score weights saved.', 'nerv-core' ),
			'weights'  => $weights,
			'defaults' => nerv_core_geo_score_default_weights(),
		)
	);
}

add_action( 'add_meta_boxes', 'nerv_core_geo_score_add_meta_box' );
function nerv_core_geo_score_add_meta_box(): void {
	foreach ( nerv_core_geo_public_post_types() as $post_type ) {
		add_meta_box(
			'nerv-core-geo-score',
			__( 'NERV GEO SCORE', 'nerv-core' ),
			'nerv_core_geo_score_render_meta_box',
			$post_type,
			'side',
			'high'
		);
	}
}

function nerv_core_geo_score_render_meta_box( WP_Post $post ): void {
	$result = nerv_core_geo_score_post( $post );
	?>
	<div class="nerv-geo-score-box">
		<p style="margin:0 0 10px;">
			<strong style="font-size:28px;"><?php echo esc_html( (string) $result['score'] ); ?></strong>
			<span>/100 <?php echo esc_html( $result['grade'] ); ?></span>
		</p>
		<ul style="margin:0;">
			<?php foreach ( $result['checks'] as $check ) : ?>
				<li style="margin-bottom:8px;">
					<strong><?php echo $check['passed'] ? '●' : '○'; ?> <?php echo esc_html( $check['label'] ); ?></strong>
					<span><?php echo esc_html( '+' . $check['points'] ); ?></span>
					<?php if ( ! $check['passed'] ) : ?>
						<p style="margin:3px 0 0;color:#646970;"><?php echo esc_html( $check['suggestion'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<p style="margin:10px 0 0;color:#646970;">
			<?php
			printf(
				/* translators: 1: word count, 2: internal link count, 3: days since update. */
				esc_html__( 'Words: %1$d · Internal links: %2$d · Updated: %3$d days ago', 'nerv-core' ),
				(int) $result['word_count'],
				(int) $result['internal_links'],
				(int) $result['fresh_days']
			);
			?>
		</p>
	</div>
	<?php
}
