<?php
/**
 * GitHub Releases updater for NERV Core and NERV Terminal.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NERV_CORE_UPDATE_REPO          = 'cat9999aaa/nerv-terminal';
const NERV_CORE_UPDATE_TRANSIENT    = 'nerv_core_github_latest_release';
const NERV_CORE_UPDATE_CACHE_TTL    = 6 * HOUR_IN_SECONDS;
const NERV_CORE_UPDATE_PLUGIN_SLUG  = 'nerv-core';
const NERV_CORE_UPDATE_PLUGIN_FILE  = 'nerv-core/nerv-core.php';
const NERV_CORE_UPDATE_THEME_SLUG   = 'nerv-terminal';
const NERV_CORE_UPDATE_RELEASE_PAGE = 'nerv-control-updates';

add_filter( 'pre_set_site_transient_update_plugins', 'nerv_core_update_plugins_transient' );
add_filter( 'plugins_api', 'nerv_core_update_plugins_api', 20, 3 );
add_filter( 'pre_set_site_transient_update_themes', 'nerv_core_update_themes_transient' );
add_filter( 'themes_api', 'nerv_core_update_themes_api', 20, 3 );
add_filter( 'upgrader_source_selection', 'nerv_core_update_normalize_source_dir', 10, 4 );
add_action( 'admin_post_nerv_core_refresh_updates', 'nerv_core_update_handle_refresh' );

function nerv_core_update_repo(): string {
	return (string) apply_filters( 'nerv_core_update_repo', NERV_CORE_UPDATE_REPO );
}

function nerv_core_update_release_api_url(): string {
	$repo = nerv_core_update_repo();
	return (string) apply_filters( 'nerv_core_update_release_api_url', 'https://api.github.com/repos/' . $repo . '/releases/latest' );
}

function nerv_core_update_release_url(): string {
	return 'https://github.com/' . nerv_core_update_repo() . '/releases/latest';
}

function nerv_core_update_latest_release( bool $force = false ) {
	$override = apply_filters( 'nerv_core_update_latest_release_override', null, $force );
	if ( is_array( $override ) || $override instanceof WP_Error ) {
		return $override;
	}

	if ( ! $force ) {
		$cached = get_site_transient( NERV_CORE_UPDATE_TRANSIENT );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$response = wp_remote_get(
		nerv_core_update_release_api_url(),
		array(
			'timeout'    => 12,
			'headers'    => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'NERV-Core-Updater/' . NERV_CORE_VERSION . '; ' . home_url( '/' ),
			),
			'sslverify'  => true,
			'redirection'=> 3,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		return new WP_Error( 'nerv_core_update_http_error', sprintf( 'GitHub Releases returned HTTP %d.', $code ) );
	}

	$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $body ) ) {
		return new WP_Error( 'nerv_core_update_invalid_json', 'GitHub Releases response is not valid JSON.' );
	}

	$release = nerv_core_update_normalize_release( $body );
	if ( empty( $release['version'] ) ) {
		return new WP_Error( 'nerv_core_update_missing_version', 'GitHub Release tag does not contain a usable version.' );
	}

	set_site_transient( NERV_CORE_UPDATE_TRANSIENT, $release, NERV_CORE_UPDATE_CACHE_TTL );
	return $release;
}

function nerv_core_update_normalize_release( array $body ): array {
	$tag     = sanitize_text_field( (string) ( $body['tag_name'] ?? '' ) );
	$version = preg_replace( '/^v/i', '', $tag );
	$assets  = array();

	foreach ( (array) ( $body['assets'] ?? array() ) as $asset ) {
		if ( ! is_array( $asset ) ) {
			continue;
		}
		$name = sanitize_file_name( (string) ( $asset['name'] ?? '' ) );
		$url  = esc_url_raw( (string) ( $asset['browser_download_url'] ?? '' ) );
		if ( ! $name || ! $url ) {
			continue;
		}
		$assets[ $name ] = array(
			'name' => $name,
			'url'  => $url,
			'size' => absint( $asset['size'] ?? 0 ),
		);
	}

	return array(
		'tag'          => $tag,
		'version'      => sanitize_text_field( (string) $version ),
		'name'         => sanitize_text_field( (string) ( $body['name'] ?? $tag ) ),
		'body'         => wp_kses_post( (string) ( $body['body'] ?? '' ) ),
		'html_url'     => esc_url_raw( (string) ( $body['html_url'] ?? nerv_core_update_release_url() ) ),
		'published_at' => sanitize_text_field( (string) ( $body['published_at'] ?? '' ) ),
		'assets'       => $assets,
	);
}

function nerv_core_update_asset_url( array $release, string $type ): string {
	$prefix = 'plugin' === $type ? 'nerv-core-plugin-' : 'nerv-terminal-theme-';
	foreach ( (array) ( $release['assets'] ?? array() ) as $asset ) {
		$name = (string) ( $asset['name'] ?? '' );
		$url  = (string) ( $asset['url'] ?? '' );
		if ( str_starts_with( $name, $prefix ) && str_ends_with( $name, '.zip' ) && nerv_core_update_allowed_download_url( $url ) ) {
			return $url;
		}
	}

	return '';
}

function nerv_core_update_allowed_download_url( string $url ): bool {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( ! is_string( $host ) ) {
		return false;
	}

	return in_array( strtolower( $host ), array( 'github.com', 'objects.githubusercontent.com', 'github-releases.githubusercontent.com' ), true );
}

function nerv_core_update_plugins_transient( $transient ) {
	if ( ! is_object( $transient ) ) {
		$transient = new stdClass();
	}
	if ( empty( $transient->checked ) || ! is_array( $transient->checked ) ) {
		$transient->checked = array();
	}

	$release = nerv_core_update_latest_release();
	if ( is_wp_error( $release ) || empty( $release['version'] ) ) {
		return $transient;
	}

	$package = nerv_core_update_asset_url( $release, 'plugin' );
	if ( $package && version_compare( (string) $release['version'], NERV_CORE_VERSION, '>' ) ) {
		$transient->response[ NERV_CORE_UPDATE_PLUGIN_FILE ] = nerv_core_update_plugin_payload( $release, $package );
	} else {
		$transient->no_update[ NERV_CORE_UPDATE_PLUGIN_FILE ] = nerv_core_update_plugin_payload( $release, $package );
	}

	return $transient;
}

function nerv_core_update_plugin_payload( array $release, string $package ): stdClass {
	return (object) array(
		'id'            => NERV_CORE_UPDATE_PLUGIN_FILE,
		'slug'          => NERV_CORE_UPDATE_PLUGIN_SLUG,
		'plugin'        => NERV_CORE_UPDATE_PLUGIN_FILE,
		'new_version'   => (string) $release['version'],
		'url'           => (string) ( $release['html_url'] ?? nerv_core_update_release_url() ),
		'package'       => $package,
		'requires'      => '6.7',
		'requires_php'  => '8.1',
		'tested'        => '6.9',
	);
}

function nerv_core_update_plugins_api( $result, string $action, $args ) {
	if ( 'plugin_information' !== $action || empty( $args->slug ) || NERV_CORE_UPDATE_PLUGIN_SLUG !== $args->slug ) {
		return $result;
	}

	$release = nerv_core_update_latest_release();
	if ( is_wp_error( $release ) ) {
		return $result;
	}

	return (object) array(
		'name'          => 'NERV Core',
		'slug'          => NERV_CORE_UPDATE_PLUGIN_SLUG,
		'version'       => (string) $release['version'],
		'author'        => 'Wang Dashen',
		'homepage'      => (string) ( $release['html_url'] ?? nerv_core_update_release_url() ),
		'download_link' => nerv_core_update_asset_url( $release, 'plugin' ),
		'requires'      => '6.7',
		'requires_php'  => '8.1',
		'tested'        => '6.9',
		'sections'      => array(
			'description' => 'NERV Terminal 主题的数据和服务层。',
			'changelog'   => nerv_core_update_release_notes_html( $release ),
		),
	);
}

function nerv_core_update_themes_transient( $transient ) {
	if ( ! is_object( $transient ) ) {
		$transient = new stdClass();
	}
	if ( empty( $transient->checked ) || ! is_array( $transient->checked ) ) {
		$transient->checked = array();
	}

	$current = defined( 'NERV_TERMINAL_VERSION' ) ? NERV_TERMINAL_VERSION : wp_get_theme( NERV_CORE_UPDATE_THEME_SLUG )->get( 'Version' );
	$release = nerv_core_update_latest_release();
	if ( is_wp_error( $release ) || empty( $release['version'] ) ) {
		return $transient;
	}

	$package = nerv_core_update_asset_url( $release, 'theme' );
	if ( $package && version_compare( (string) $release['version'], (string) $current, '>' ) ) {
		$transient->response[ NERV_CORE_UPDATE_THEME_SLUG ] = nerv_core_update_theme_payload( $release, $package );
	} else {
		$transient->no_update[ NERV_CORE_UPDATE_THEME_SLUG ] = nerv_core_update_theme_payload( $release, $package );
	}

	return $transient;
}

function nerv_core_update_theme_payload( array $release, string $package ): array {
	return array(
		'theme'        => NERV_CORE_UPDATE_THEME_SLUG,
		'new_version'  => (string) $release['version'],
		'url'          => (string) ( $release['html_url'] ?? nerv_core_update_release_url() ),
		'package'      => $package,
		'requires'     => '6.7',
		'requires_php' => '8.1',
	);
}

function nerv_core_update_themes_api( $result, string $action, $args ) {
	$slug = is_object( $args ) && isset( $args->slug ) ? (string) $args->slug : '';
	if ( 'theme_information' !== $action || NERV_CORE_UPDATE_THEME_SLUG !== $slug ) {
		return $result;
	}

	$release = nerv_core_update_latest_release();
	if ( is_wp_error( $release ) ) {
		return $result;
	}

	return (object) array(
		'name'          => 'NERV Terminal',
		'slug'          => NERV_CORE_UPDATE_THEME_SLUG,
		'version'       => (string) $release['version'],
		'author'        => 'Wang Dashen',
		'homepage'      => (string) ( $release['html_url'] ?? nerv_core_update_release_url() ),
		'download_link' => nerv_core_update_asset_url( $release, 'theme' ),
		'requires'      => '6.7',
		'requires_php'  => '8.1',
		'sections'      => array(
			'description' => 'NERV Terminal WordPress 主题。',
			'changelog'   => nerv_core_update_release_notes_html( $release ),
		),
	);
}

function nerv_core_update_normalize_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
	if ( is_wp_error( $source ) || ! is_string( $source ) || ! is_string( $remote_source ) || ! is_array( $hook_extra ) ) {
		return $source;
	}
	if ( empty( $hook_extra['plugin'] ) && empty( $hook_extra['theme'] ) ) {
		return $source;
	}

	$target = '';
	if ( ! empty( $hook_extra['plugin'] ) && NERV_CORE_UPDATE_PLUGIN_FILE === $hook_extra['plugin'] ) {
		$target = NERV_CORE_UPDATE_PLUGIN_SLUG;
	} elseif ( ! empty( $hook_extra['theme'] ) && NERV_CORE_UPDATE_THEME_SLUG === $hook_extra['theme'] ) {
		$target = NERV_CORE_UPDATE_THEME_SLUG;
	}

	if ( ! $target ) {
		return $source;
	}

	global $wp_filesystem;
	if ( ! $wp_filesystem || ! $wp_filesystem->is_dir( $source ) ) {
		return $source;
	}

	$desired = trailingslashit( $remote_source ) . $target;
	if ( trailingslashit( $source ) === trailingslashit( $desired ) ) {
		return $source;
	}

	if ( ! $wp_filesystem->exists( $desired ) ) {
		$wp_filesystem->move( $source, $desired );
		return $desired;
	}

	return $source;
}

function nerv_core_update_release_notes_html( array $release ): string {
	$body = trim( (string) ( $release['body'] ?? '' ) );
	if ( '' === $body ) {
		return '<p>此版本没有填写更新说明。</p>';
	}

	if ( function_exists( 'wpautop' ) ) {
		return wpautop( esc_html( $body ) );
	}

	return '<pre>' . esc_html( $body ) . '</pre>';
}

function nerv_core_update_status(): array {
	$release = nerv_core_update_latest_release();
	if ( is_wp_error( $release ) ) {
		return array(
			'error'   => $release->get_error_message(),
			'release' => null,
			'theme'   => null,
			'plugin'  => null,
		);
	}

	$theme_version  = defined( 'NERV_TERMINAL_VERSION' ) ? NERV_TERMINAL_VERSION : wp_get_theme( NERV_CORE_UPDATE_THEME_SLUG )->get( 'Version' );
	$plugin_package = nerv_core_update_asset_url( $release, 'plugin' );
	$theme_package  = nerv_core_update_asset_url( $release, 'theme' );

	return array(
		'error'   => '',
		'release' => $release,
		'theme'   => array(
			'name'       => 'NERV Terminal',
			'current'    => (string) $theme_version,
			'latest'     => (string) $release['version'],
			'package'    => $theme_package,
			'update_url' => self_admin_url( 'update.php?action=upgrade-theme&theme=' . rawurlencode( NERV_CORE_UPDATE_THEME_SLUG ) . '&_wpnonce=' . wp_create_nonce( 'upgrade-theme_' . NERV_CORE_UPDATE_THEME_SLUG ) ),
			'available'  => $theme_package && version_compare( (string) $release['version'], (string) $theme_version, '>' ),
		),
		'plugin'  => array(
			'name'       => 'NERV Core',
			'current'    => NERV_CORE_VERSION,
			'latest'     => (string) $release['version'],
			'package'    => $plugin_package,
			'update_url' => self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( NERV_CORE_UPDATE_PLUGIN_FILE ) . '&_wpnonce=' . wp_create_nonce( 'upgrade-plugin_' . NERV_CORE_UPDATE_PLUGIN_FILE ) ),
			'available'  => $plugin_package && version_compare( (string) $release['version'], NERV_CORE_VERSION, '>' ),
		),
	);
}

function nerv_core_update_handle_refresh(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to refresh NERV updates.', 'nerv-core' ) );
	}

	check_admin_referer( 'nerv_core_refresh_updates' );
	delete_site_transient( NERV_CORE_UPDATE_TRANSIENT );
	delete_site_transient( 'update_plugins' );
	delete_site_transient( 'update_themes' );
	wp_safe_redirect( add_query_arg( 'nerv-updates-refreshed', '1', admin_url( 'admin.php?page=' . NERV_CORE_UPDATE_RELEASE_PAGE ) ) );
	exit;
}

function nerv_core_render_updates_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$status = nerv_core_update_status();
	?>
	<div class="wrap nerv-update-wrap">
		<h1>NERV主题 · 在线更新</h1>
		<p>从 GitHub Releases 检查 NERV Terminal 主题和 NERV Core 插件的新版本，后台更新提示会同步使用这里的版本信息。</p>
		<?php if ( isset( $_GET['nerv-updates-refreshed'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>已刷新 GitHub Releases 更新缓存。</p></div>
		<?php endif; ?>
		<?php if ( ! empty( $status['error'] ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $status['error'] ); ?></p></div>
		<?php endif; ?>
		<div class="nerv-update-actions">
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="nerv_core_refresh_updates">
				<?php wp_nonce_field( 'nerv_core_refresh_updates' ); ?>
				<button type="submit" class="button button-secondary">重新检查更新</button>
			</form>
			<a class="button" href="<?php echo esc_url( nerv_core_update_release_url() ); ?>" target="_blank" rel="noreferrer">打开 GitHub Releases</a>
		</div>
		<div class="nerv-update-grid">
			<?php nerv_core_render_update_card( (array) ( $status['theme'] ?? array() ), '主题' ); ?>
			<?php nerv_core_render_update_card( (array) ( $status['plugin'] ?? array() ), '插件' ); ?>
		</div>
		<?php if ( ! empty( $status['release'] ) ) : ?>
			<section class="nerv-update-release">
				<h2><?php echo esc_html( (string) ( $status['release']['name'] ?? 'Latest Release' ) ); ?></h2>
				<p>
					<span>最新版本：<?php echo esc_html( (string) ( $status['release']['version'] ?? '' ) ); ?></span>
					<?php if ( ! empty( $status['release']['published_at'] ) ) : ?>
						<span>发布时间：<?php echo esc_html( mysql2date( 'Y-m-d H:i', (string) $status['release']['published_at'] ) ); ?></span>
					<?php endif; ?>
				</p>
				<div class="nerv-update-notes">
					<h3>更新了什么</h3>
					<?php echo wp_kses_post( nerv_core_update_release_notes_html( (array) $status['release'] ) ); ?>
				</div>
				<?php if ( ! empty( $status['release']['html_url'] ) ) : ?>
					<p><a href="<?php echo esc_url( (string) $status['release']['html_url'] ); ?>" target="_blank" rel="noreferrer">查看完整 Release</a></p>
				<?php endif; ?>
			</section>
		<?php endif; ?>
	</div>
	<?php
}

function nerv_core_render_update_card( array $item, string $type ): void {
	$available = ! empty( $item['available'] );
	$package   = ! empty( $item['package'] );
	?>
	<section class="nerv-update-card">
		<h2><?php echo esc_html( $type . '：' . (string) ( $item['name'] ?? '' ) ); ?></h2>
		<dl>
			<div><dt>当前版本</dt><dd><?php echo esc_html( (string) ( $item['current'] ?? '' ) ); ?></dd></div>
			<div><dt>最新版本</dt><dd><?php echo esc_html( (string) ( $item['latest'] ?? '' ) ); ?></dd></div>
			<div><dt>安装包</dt><dd><?php echo $package ? '已找到' : '未找到'; ?></dd></div>
		</dl>
		<?php if ( $available ) : ?>
			<a class="button button-primary" href="<?php echo esc_url( (string) ( $item['update_url'] ?? '' ) ); ?>">立即更新<?php echo esc_html( $type ); ?></a>
		<?php else : ?>
			<span class="nerv-update-badge"><?php echo $package ? '已是最新' : '等待 Release 安装包'; ?></span>
		<?php endif; ?>
	</section>
	<?php
}
