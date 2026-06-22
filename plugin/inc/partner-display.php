<?php
/**
 * Partner display settings, shortcode, and GEO helpers.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_partner_display_default_options(): array {
	return array(
		'footer_enabled'      => true,
		'footer_limit'        => 4,
		'application_enabled' => true,
		'application_email'   => get_option( 'admin_email' ),
		'application_text'    => __( 'Want to establish an allied link? Send your site name, URL, and a short description for review.', 'nerv-core' ),
		'llms_include'        => false,
	);
}

function nerv_core_partner_display_options(): array {
	$options = get_option( 'nerv_core_partner_display_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	$options = wp_parse_args( $options, nerv_core_partner_display_default_options() );
	$options['footer_enabled'] = ! empty( $options['footer_enabled'] );
	$options['footer_limit'] = max( 1, min( 12, absint( $options['footer_limit'] ?? 4 ) ) );
	$options['application_enabled'] = ! empty( $options['application_enabled'] );
	$options['application_email'] = sanitize_email( (string) ( $options['application_email'] ?? get_option( 'admin_email' ) ) );
	$options['application_text'] = sanitize_text_field( (string) ( $options['application_text'] ?? '' ) );
	$options['llms_include'] = ! empty( $options['llms_include'] );

	return $options;
}

function nerv_core_partner_display_sanitize_options( $input ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	return array(
		'footer_enabled'      => ! empty( $input['footer_enabled'] ),
		'footer_limit'        => max( 1, min( 12, absint( $input['footer_limit'] ?? 4 ) ) ),
		'application_enabled' => ! empty( $input['application_enabled'] ),
		'application_email'   => sanitize_email( (string) ( $input['application_email'] ?? get_option( 'admin_email' ) ) ),
		'application_text'    => sanitize_text_field( (string) ( $input['application_text'] ?? '' ) ),
		'llms_include'        => ! empty( $input['llms_include'] ),
	);
}

add_action( 'admin_init', 'nerv_core_partner_display_register_settings' );
function nerv_core_partner_display_register_settings(): void {
	register_setting(
		'nerv_core_settings',
		'nerv_core_partner_display_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'nerv_core_partner_display_sanitize_options',
			'default'           => nerv_core_partner_display_default_options(),
		)
	);
}

function nerv_core_partner_display_footer_enabled(): bool {
	$options = nerv_core_partner_display_options();
	return ! empty( $options['footer_enabled'] );
}

function nerv_core_partner_display_footer_limit(): int {
	$options = nerv_core_partner_display_options();
	return (int) $options['footer_limit'];
}

function nerv_core_partner_display_application_enabled(): bool {
	$options = nerv_core_partner_display_options();
	return ! empty( $options['application_enabled'] );
}

function nerv_core_partner_display_application_text(): string {
	$options = nerv_core_partner_display_options();
	return (string) $options['application_text'];
}

function nerv_core_partner_display_application_email(): string {
	$options = nerv_core_partner_display_options();
	return (string) $options['application_email'];
}

function nerv_core_partner_display_llms_enabled(): bool {
	$options = nerv_core_partner_display_options();
	return ! empty( $options['llms_include'] );
}

function nerv_core_partner_link_rel( int $partner_id ): string {
	$rel = get_post_meta( $partner_id, '_nerv_partner_rel', true );
	return 'nofollow' === $rel ? 'nofollow noopener noreferrer' : 'noopener noreferrer';
}

function nerv_core_partner_is_featured( int $partner_id ): bool {
	return (bool) get_post_meta( $partner_id, '_nerv_partner_featured', true );
}

function nerv_core_partner_query( int $limit = 12, bool $featured_only = false ): array {
	$args = array(
		'post_type'      => 'partner',
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, min( 100, $limit ) ),
		'orderby'        => 'menu_order date',
		'order'          => 'ASC',
	);

	if ( $featured_only ) {
		$args['meta_query'] = array(
			array(
				'key'   => '_nerv_partner_featured',
				'value' => '1',
			),
		);
	}

	return get_posts( $args );
}

add_shortcode( 'nerv_partners', 'nerv_core_partner_shortcode' );
function nerv_core_partner_shortcode( $atts = array() ): string {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$atts = shortcode_atts(
		array(
			'limit'    => 6,
			'featured' => '0',
		),
		$atts,
		'nerv_partners'
	);

	$limit = max( 1, min( 24, absint( $atts['limit'] ) ) );
	$featured_only = in_array( (string) $atts['featured'], array( '1', 'true', 'yes' ), true );

	if ( function_exists( 'nerv_terminal_partner_grid' ) ) {
		return nerv_terminal_partner_grid( $limit, $featured_only );
	}

	$items = '';
	foreach ( nerv_core_partner_query( $limit, $featured_only ) as $partner ) {
		$url = get_post_meta( (int) $partner->ID, '_nerv_partner_url', true ) ?: get_permalink( $partner );
		$items .= '<li><a href="' . esc_url( $url ) . '" rel="' . esc_attr( nerv_core_partner_link_rel( (int) $partner->ID ) ) . '">' . esc_html( get_the_title( $partner ) ) . '</a></li>';
	}

	return $items ? '<ul class="nerv-partner-shortcode">' . $items . '</ul>' : '';
}
