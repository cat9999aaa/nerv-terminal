<?php
/**
 * Weighted related entries engine.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_related_default_options(): array {
	return array(
		'enabled'             => true,
		'title'               => __( 'RELATED ENTRIES / 関連記録', 'nerv-core' ),
		'limit'               => 3,
		'category_weight'     => 2,
		'tag_weight'          => 1,
		'recent_weight'       => 1,
		'recent_days'         => 180,
		'cache_hours'         => 12,
		'excluded_categories' => array(),
	);
}

function nerv_core_related_options(): array {
	$options = get_option( 'nerv_core_related_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	return wp_parse_args( $options, nerv_core_related_default_options() );
}

function nerv_core_related_sanitize_options( $input ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	$defaults = nerv_core_related_default_options();
	$options  = array(
		'enabled'             => ! empty( $input['enabled'] ),
		'title'               => isset( $input['title'] ) ? sanitize_text_field( wp_unslash( $input['title'] ) ) : $defaults['title'],
		'limit'               => isset( $input['limit'] ) ? absint( $input['limit'] ) : $defaults['limit'],
		'category_weight'     => isset( $input['category_weight'] ) ? absint( $input['category_weight'] ) : $defaults['category_weight'],
		'tag_weight'          => isset( $input['tag_weight'] ) ? absint( $input['tag_weight'] ) : $defaults['tag_weight'],
		'recent_weight'       => isset( $input['recent_weight'] ) ? absint( $input['recent_weight'] ) : $defaults['recent_weight'],
		'recent_days'         => isset( $input['recent_days'] ) ? absint( $input['recent_days'] ) : $defaults['recent_days'],
		'cache_hours'         => isset( $input['cache_hours'] ) ? absint( $input['cache_hours'] ) : $defaults['cache_hours'],
		'excluded_categories' => array(),
	);

	if ( isset( $input['excluded_categories'] ) && is_array( $input['excluded_categories'] ) ) {
		$options['excluded_categories'] = array_values( array_unique( array_filter( array_map( 'absint', $input['excluded_categories'] ) ) ) );
	}

	$options['title']           = '' === $options['title'] ? $defaults['title'] : $options['title'];
	$options['limit']           = max( 1, min( 12, $options['limit'] ) );
	$options['category_weight'] = min( 20, $options['category_weight'] );
	$options['tag_weight']      = min( 20, $options['tag_weight'] );
	$options['recent_weight']   = min( 20, $options['recent_weight'] );
	$options['recent_days']     = max( 1, min( 3650, $options['recent_days'] ) );
	$options['cache_hours']     = max( 1, min( 168, $options['cache_hours'] ) );

	nerv_core_related_flush_cache();

	return $options;
}

add_action( 'admin_init', 'nerv_core_related_register_settings' );
function nerv_core_related_register_settings(): void {
	register_setting(
		'nerv_core_settings',
		'nerv_core_related_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'nerv_core_related_sanitize_options',
			'default'           => nerv_core_related_default_options(),
		)
	);
}

function nerv_core_related_is_enabled(): bool {
	$options = nerv_core_related_options();

	return (bool) $options['enabled'];
}

function nerv_core_related_limit(): int {
	$options = nerv_core_related_options();

	return max( 1, min( 12, (int) $options['limit'] ) );
}

function nerv_core_related_title(): string {
	$options = nerv_core_related_options();

	return (string) $options['title'];
}

function nerv_core_related_weights(): array {
	$options = nerv_core_related_options();

	return array(
		'category'            => (int) $options['category_weight'],
		'tag'                 => (int) $options['tag_weight'],
		'recent'              => (int) $options['recent_weight'],
		'recent_days'         => (int) $options['recent_days'],
		'cache_hours'         => (int) $options['cache_hours'],
		'excluded_categories' => array_map( 'absint', (array) $options['excluded_categories'] ),
	);
}

function nerv_core_related_entries( int $post_id, int $limit = 3, array $weights = array() ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'post' !== $post->post_type || ! nerv_core_related_is_enabled() ) {
		return array();
	}

	$limit = $limit > 0 ? max( 1, min( 12, $limit ) ) : nerv_core_related_limit();
	$weights = wp_parse_args(
		$weights,
		nerv_core_related_weights()
	);

	$cache_key = 'nerv_related_' . $post_id . '_' . md5( wp_json_encode( array( $limit, $weights ) ) );
	$cached = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return nerv_core_related_posts_from_ids( $cached );
	}

	$category_ids = wp_get_post_categories( $post_id );
	$tag_ids      = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );
	$excluded     = array_values( array_filter( array_map( 'absint', (array) $weights['excluded_categories'] ) ) );
	if ( $excluded ) {
		$category_ids = array_values( array_diff( $category_ids, $excluded ) );
	}

	$query_args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'post__not_in'        => array( $post_id ),
		'posts_per_page'      => 50,
		'ignore_sticky_posts' => true,
		'orderby'            => 'modified',
		'order'              => 'DESC',
	);

	if ( $excluded ) {
		$query_args['category__not_in'] = $excluded;
	}

	$candidates   = get_posts(
		$query_args
	);

	$scored = array();
	foreach ( $candidates as $candidate ) {
		$score = 0;
		$candidate_categories = wp_get_post_categories( $candidate->ID );
		$candidate_tags       = wp_get_post_tags( $candidate->ID, array( 'fields' => 'ids' ) );
		$common_categories    = array_intersect( $category_ids, $candidate_categories );
		$common_tags          = array_intersect( $tag_ids, $candidate_tags );

		if ( $common_categories ) {
			$score += count( $common_categories ) * (int) $weights['category'];
		}

		if ( $common_tags ) {
			$score += count( $common_tags ) * (int) $weights['tag'];
		}

		$modified = strtotime( $candidate->post_modified_gmt ?: $candidate->post_date_gmt );
		if ( $modified && $modified >= time() - ( (int) $weights['recent_days'] * DAY_IN_SECONDS ) ) {
			$score += (int) $weights['recent'];
		}

		$scored[] = array(
			'post'     => $candidate,
			'score'    => $score,
			'modified' => $modified ?: 0,
		);
	}

	usort(
		$scored,
		static function ( array $a, array $b ): int {
			if ( $a['score'] === $b['score'] ) {
				return $b['modified'] <=> $a['modified'];
			}

			return $b['score'] <=> $a['score'];
		}
	);

	$ids = array();
	foreach ( $scored as $item ) {
		$ids[] = (int) $item['post']->ID;
		if ( count( $ids ) >= $limit ) {
			break;
		}
	}

	if ( count( $ids ) < $limit ) {
		$fallback_args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'post__not_in'        => array_merge( array( $post_id ), $ids ),
			'posts_per_page'      => $limit - count( $ids ),
			'ignore_sticky_posts' => true,
			'orderby'            => 'date',
			'order'              => 'DESC',
		);

		if ( $excluded ) {
			$fallback_args['category__not_in'] = $excluded;
		}

		$fallback = get_posts(
			$fallback_args
		);
		foreach ( $fallback as $fallback_post ) {
			$ids[] = (int) $fallback_post->ID;
		}
	}

	set_transient( $cache_key, $ids, (int) $weights['cache_hours'] * HOUR_IN_SECONDS );

	return nerv_core_related_posts_from_ids( $ids );
}

function nerv_core_related_posts_from_ids( array $ids ): array {
	$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
	if ( ! $ids ) {
		return array();
	}

	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'post__in'       => $ids,
			'orderby'        => 'post__in',
			'posts_per_page' => count( $ids ),
		)
	);

	return $posts;
}

add_action( 'save_post_post', 'nerv_core_related_flush_cache' );
add_action( 'deleted_post', 'nerv_core_related_flush_cache' );
add_action( 'set_object_terms', 'nerv_core_related_flush_cache' );
function nerv_core_related_flush_cache( ...$ignored ): void {
	global $wpdb;

	$transient_like = $wpdb->esc_like( '_transient_nerv_related_' ) . '%';
	$timeout_like   = $wpdb->esc_like( '_transient_timeout_nerv_related_' ) . '%';

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$transient_like,
			$timeout_like
		)
	);
}
