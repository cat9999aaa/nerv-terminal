<?php
/**
 * Global social profile store for NERV CONTROL and front-end panels.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_social_platform_choices(): array {
	return array(
		'github'    => __( 'GitHub', 'nerv-core' ),
		'x'         => __( 'X / Twitter', 'nerv-core' ),
		'youtube'   => __( 'YouTube', 'nerv-core' ),
		'linkedin'  => __( 'LinkedIn', 'nerv-core' ),
		'instagram' => __( 'Instagram', 'nerv-core' ),
		'bilibili'  => __( 'Bilibili', 'nerv-core' ),
		'weibo'     => __( 'Weibo', 'nerv-core' ),
		'wechat'    => __( 'WeChat', 'nerv-core' ),
		'website'   => __( 'Website', 'nerv-core' ),
		'email'     => __( 'Email', 'nerv-core' ),
		'rss'       => __( 'RSS', 'nerv-core' ),
	);
}

function nerv_core_social_default_options(): array {
	return array(
		'enabled' => true,
		'open_new_tab' => true,
		'links'   => array(
			array( 'key' => 'github', 'label' => 'GH', 'url' => home_url( '/' ), 'enabled' => true, 'rel' => 'me noopener noreferrer' ),
			array( 'key' => 'x', 'label' => 'X', 'url' => home_url( '/' ), 'enabled' => true, 'rel' => 'me noopener noreferrer' ),
			array( 'key' => 'youtube', 'label' => 'YT', 'url' => home_url( '/' ), 'enabled' => true, 'rel' => 'me noopener noreferrer' ),
			array( 'key' => 'wechat', 'label' => 'WX', 'url' => '', 'qr_url' => '', 'enabled' => false, 'rel' => 'nofollow' ),
			array( 'key' => 'email', 'label' => '@', 'url' => 'mailto:' . get_option( 'admin_email' ), 'enabled' => true, 'rel' => 'nofollow' ),
		),
	);
}

function nerv_core_social_options(): array {
	$options = get_option( 'nerv_core_social_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	return nerv_core_social_sanitize_options( $options );
}

function nerv_core_social_sanitize_options( $input ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	$defaults = nerv_core_social_default_options();
	$choices  = nerv_core_social_platform_choices();
	$options  = array(
		'enabled'      => array_key_exists( 'enabled', $input ) ? ! empty( $input['enabled'] ) : (bool) $defaults['enabled'],
		'open_new_tab' => array_key_exists( 'open_new_tab', $input ) ? ! empty( $input['open_new_tab'] ) : (bool) $defaults['open_new_tab'],
		'links'        => array(),
	);

	$links = is_array( $input['links'] ?? null ) ? array_values( $input['links'] ) : $defaults['links'];
	foreach ( $links as $index => $link ) {
		if ( ! is_array( $link ) ) {
			continue;
		}

		$fallback = $defaults['links'][ $index ] ?? array( 'key' => 'website', 'label' => 'WEB', 'url' => '', 'enabled' => true, 'rel' => 'me noopener noreferrer' );
		$key      = sanitize_key( (string) ( $link['key'] ?? $fallback['key'] ) );
		$url      = trim( (string) ( $link['url'] ?? $fallback['url'] ) );
		if ( 'email' === $key && $url && ! str_starts_with( $url, 'mailto:' ) && is_email( $url ) ) {
			$url = 'mailto:' . $url;
		}

		$options['links'][] = array(
			'key'     => array_key_exists( $key, $choices ) ? $key : 'website',
			'label'   => sanitize_text_field( (string) ( $link['label'] ?? $fallback['label'] ) ),
			'url'     => esc_url_raw( $url ),
			'qr_url'  => esc_url_raw( (string) ( $link['qr_url'] ?? $link['qrUrl'] ?? $fallback['qr_url'] ?? '' ) ),
			'enabled' => array_key_exists( 'enabled', $link ) ? ! empty( $link['enabled'] ) : true,
			'rel'     => nerv_core_social_sanitize_rel( (string) ( $link['rel'] ?? $fallback['rel'] ?? '' ) ),
		);
	}

	return $options;
}

function nerv_core_social_sanitize_rel( string $rel ): string {
	$allowed = array( 'me', 'noopener', 'noreferrer', 'nofollow', 'ugc', 'sponsored' );
	$tokens  = preg_split( '/\s+/', strtolower( $rel ) ) ?: array();
	$tokens  = array_values( array_unique( array_intersect( $tokens, $allowed ) ) );

	return implode( ' ', $tokens );
}

function nerv_core_social_links( bool $enabled_only = true ): array {
	$options = nerv_core_social_options();
	if ( empty( $options['enabled'] ) ) {
		return array();
	}

	$links = array();
	foreach ( (array) $options['links'] as $link ) {
		if ( $enabled_only && empty( $link['enabled'] ) ) {
			continue;
		}
		if ( empty( $link['url'] ) ) {
			if ( 'wechat' !== (string) ( $link['key'] ?? '' ) || empty( $link['qr_url'] ) ) {
				continue;
			}
		}

		if ( 'wechat' === (string) ( $link['key'] ?? '' ) && empty( $link['url'] ) && empty( $link['qr_url'] ) ) {
			continue;
		}

		$links[] = $link;
	}

	return $links;
}

function nerv_core_social_same_as(): array {
	return array_values(
		array_unique(
			array_filter(
				array_map(
					static function ( array $link ): string {
						return 'email' === (string) ( $link['key'] ?? '' ) ? '' : esc_url_raw( (string) ( $link['url'] ?? '' ) );
					},
					nerv_core_social_links()
				)
			)
		)
	);
}
