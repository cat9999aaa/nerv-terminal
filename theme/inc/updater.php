<?php
/**
 * GitHub Releases updater for the NERV Terminal theme.
 *
 * @package NervTerminal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NERV_TERMINAL_UPDATE_REPO       = 'cat9999aaa/nerv-terminal';
const NERV_TERMINAL_UPDATE_TRANSIENT = 'nerv_terminal_github_latest_release';
const NERV_TERMINAL_UPDATE_TTL       = 6 * HOUR_IN_SECONDS;
const NERV_TERMINAL_UPDATE_SLUG      = 'nerv-terminal';

add_action( 'after_setup_theme', 'nerv_terminal_update_register_hooks', 20 );

function nerv_terminal_update_register_hooks(): void {
	if ( function_exists( 'nerv_core_update_themes_transient' ) ) {
		return;
	}

	add_filter( 'pre_set_site_transient_update_themes', 'nerv_terminal_update_themes_transient' );
	add_filter( 'themes_api', 'nerv_terminal_update_themes_api', 20, 3 );
	add_filter( 'upgrader_source_selection', 'nerv_terminal_update_normalize_source_dir', 10, 4 );
}

function nerv_terminal_update_latest_release() {
	if ( function_exists( 'nerv_core_update_latest_release' ) ) {
		return nerv_core_update_latest_release();
	}

	$cached = get_site_transient( NERV_TERMINAL_UPDATE_TRANSIENT );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$response = wp_remote_get(
		'https://api.github.com/repos/' . NERV_TERMINAL_UPDATE_REPO . '/releases/latest',
		array(
			'timeout'     => 12,
			'headers'     => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'NERV-Terminal-Updater/' . NERV_TERMINAL_VERSION . '; ' . home_url( '/' ),
			),
			'sslverify'   => true,
			'redirection' => 3,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'nerv_terminal_update_http_error', 'GitHub Releases could not be loaded.' );
	}

	$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $body ) ) {
		return new WP_Error( 'nerv_terminal_update_invalid_json', 'GitHub Releases response is not valid JSON.' );
	}

	$release = nerv_terminal_update_normalize_release( $body );
	set_site_transient( NERV_TERMINAL_UPDATE_TRANSIENT, $release, NERV_TERMINAL_UPDATE_TTL );
	return $release;
}

function nerv_terminal_update_normalize_release( array $body ): array {
	$assets = array();
	foreach ( (array) ( $body['assets'] ?? array() ) as $asset ) {
		if ( ! is_array( $asset ) ) {
			continue;
		}
		$name = sanitize_file_name( (string) ( $asset['name'] ?? '' ) );
		$url  = esc_url_raw( (string) ( $asset['browser_download_url'] ?? '' ) );
		if ( $name && $url ) {
			$assets[ $name ] = array(
				'name' => $name,
				'url'  => $url,
			);
		}
	}

	$tag = sanitize_text_field( (string) ( $body['tag_name'] ?? '' ) );
	return array(
		'tag'      => $tag,
		'version'  => sanitize_text_field( (string) preg_replace( '/^v/i', '', $tag ) ),
		'name'     => sanitize_text_field( (string) ( $body['name'] ?? $tag ) ),
		'body'     => wp_kses_post( (string) ( $body['body'] ?? '' ) ),
		'html_url' => esc_url_raw( (string) ( $body['html_url'] ?? 'https://github.com/' . NERV_TERMINAL_UPDATE_REPO . '/releases/latest' ) ),
		'assets'   => $assets,
	);
}

function nerv_terminal_update_theme_package( array $release ): string {
	if ( function_exists( 'nerv_core_update_asset_url' ) ) {
		return nerv_core_update_asset_url( $release, 'theme' );
	}

	foreach ( (array) ( $release['assets'] ?? array() ) as $asset ) {
		$name = (string) ( $asset['name'] ?? '' );
		$url  = (string) ( $asset['url'] ?? '' );
		if ( str_starts_with( $name, 'nerv-terminal-theme-' ) && str_ends_with( $name, '.zip' ) && nerv_terminal_update_allowed_download_url( $url ) ) {
			return $url;
		}
	}

	return '';
}

function nerv_terminal_update_allowed_download_url( string $url ): bool {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	return is_string( $host ) && in_array( strtolower( $host ), array( 'github.com', 'objects.githubusercontent.com', 'github-releases.githubusercontent.com' ), true );
}

function nerv_terminal_update_themes_transient( $transient ) {
	if ( ! is_object( $transient ) ) {
		$transient = new stdClass();
	}
	if ( empty( $transient->checked ) || ! is_array( $transient->checked ) ) {
		$transient->checked = array();
	}

	$release = nerv_terminal_update_latest_release();
	if ( is_wp_error( $release ) || empty( $release['version'] ) ) {
		return $transient;
	}

	$package = nerv_terminal_update_theme_package( $release );
	$payload = array(
		'theme'        => NERV_TERMINAL_UPDATE_SLUG,
		'new_version'  => (string) $release['version'],
		'url'          => (string) ( $release['html_url'] ?? '' ),
		'package'      => $package,
		'requires'     => '6.7',
		'requires_php' => '8.1',
	);

	if ( $package && version_compare( (string) $release['version'], NERV_TERMINAL_VERSION, '>' ) ) {
		$transient->response[ NERV_TERMINAL_UPDATE_SLUG ] = $payload;
	} else {
		$transient->no_update[ NERV_TERMINAL_UPDATE_SLUG ] = $payload;
	}

	return $transient;
}

function nerv_terminal_update_themes_api( $result, string $action, $args ) {
	$slug = is_object( $args ) && isset( $args->slug ) ? (string) $args->slug : '';
	if ( 'theme_information' !== $action || NERV_TERMINAL_UPDATE_SLUG !== $slug ) {
		return $result;
	}

	$release = nerv_terminal_update_latest_release();
	if ( is_wp_error( $release ) ) {
		return $result;
	}

	return (object) array(
		'name'          => 'NERV Terminal',
		'slug'          => NERV_TERMINAL_UPDATE_SLUG,
		'version'       => (string) $release['version'],
		'author'        => 'Wang Dashen',
		'homepage'      => (string) ( $release['html_url'] ?? '' ),
		'download_link' => nerv_terminal_update_theme_package( $release ),
		'requires'      => '6.7',
		'requires_php'  => '8.1',
		'sections'      => array(
			'description' => 'NERV Terminal WordPress 主题。',
			'changelog'   => wpautop( esc_html( (string) ( $release['body'] ?? '' ) ) ),
		),
	);
}

function nerv_terminal_update_normalize_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
	if ( is_wp_error( $source ) || ! is_string( $source ) || ! is_string( $remote_source ) || ! is_array( $hook_extra ) ) {
		return $source;
	}
	if ( empty( $hook_extra['theme'] ) || NERV_TERMINAL_UPDATE_SLUG !== $hook_extra['theme'] ) {
		return $source;
	}

	global $wp_filesystem;
	$desired = trailingslashit( $remote_source ) . NERV_TERMINAL_UPDATE_SLUG;
	if ( $wp_filesystem && $wp_filesystem->is_dir( $source ) && trailingslashit( $source ) !== trailingslashit( $desired ) && ! $wp_filesystem->exists( $desired ) ) {
		$wp_filesystem->move( $source, $desired );
		return $desired;
	}

	return $source;
}
