<?php
/**
 * Dynamic dashboard rendering.
 *
 * @package NervTerminal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_terminal_render_dashboard_block(): string {
	$brand_logo = function_exists( 'nerv_terminal_media_url' ) ? nerv_terminal_media_url( 'brand_logo_id', 'thumbnail' ) : '';
	$brand_logo_style = $brand_logo && function_exists( 'nerv_terminal_image_focus' )
		? '--nerv-logo-fit:' . esc_attr( nerv_terminal_image_fit( 'brand_logo_fit' ) ) . ';--nerv-logo-position:' . esc_attr( nerv_terminal_image_focus( 'brand_logo_focus_x', 'brand_logo_focus_y' ) ) . ';'
		: '';
	$clock_full  = current_time( 'H:i:s' );
	$clock_short = current_time( 'H:i' );
	$theme_mode  = function_exists( 'nerv_terminal_appearance_theme_attribute' ) ? nerv_terminal_appearance_theme_attribute() : 'void';
	$palette     = function_exists( 'nerv_terminal_appearance_palette_attribute' ) ? nerv_terminal_appearance_palette_attribute() : 'hazard';
	$is_reading  = nerv_terminal_is_reading_layout();
	$shell_class = $is_reading ? 'nerv-app-shell nerv-app-shell--reading' : 'nerv-app-shell';
	$main_class  = $is_reading ? 'nerv-main nerv-main--reading' : 'nerv-main';
	ob_start();
	?>
	<div class="<?php echo esc_attr( $shell_class ); ?>" data-nerv-terminal data-theme="<?php echo esc_attr( $theme_mode ); ?>" data-palette="<?php echo esc_attr( $palette ); ?>">
		<div class="crt-vignette" aria-hidden="true"></div>
		<div class="crt-roll" aria-hidden="true"></div>
		<div class="crt-scan" aria-hidden="true"></div>
		<header class="nerv-header status-bar">
			<a class="nerv-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<span class="nerv-brand__title"><?php echo esc_html( nerv_terminal_string( 'brand_title' ) ); ?></span>
				<span class="nerv-brand__subtitle"><?php echo esc_html( nerv_terminal_string( 'brand_subtitle' ) ); ?></span>
			</a>
			<div class="nerv-mark" aria-hidden="true">
				<?php if ( $brand_logo ) : ?>
					<img src="<?php echo esc_url( $brand_logo ); ?>" alt="" style="<?php echo esc_attr( $brand_logo_style ); ?>">
				<?php else : ?>
					<?php echo esc_html( nerv_terminal_string( 'brand_mark' ) ); ?>
				<?php endif; ?>
			</div>
			<nav class="nerv-topnav exo-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'nerv-terminal' ); ?>">
				<?php foreach ( nerv_terminal_nav_items() as $item ) : ?>
					<a class="<?php echo esc_attr( $item['active'] ? 'is-active' : '' ); ?>" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo ! empty( $item['new_window'] ) ? nerv_terminal_new_window_attrs() : ''; ?><?php echo $item['active'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $item['label'] ); ?></a>
				<?php endforeach; ?>
			</nav>
			<div class="nerv-clock">
				<span><?php echo esc_html( nerv_terminal_string( 'clock_label' ) ); ?></span>
				<strong data-nerv-clock><?php echo esc_html( $clock_full ); ?></strong>
				<small><?php echo esc_html( nerv_terminal_string( 'clock_timezone' ) ); ?></small>
				<em><?php echo esc_html( nerv_terminal_string( 'active_label' ) ); ?></em>
			</div>
		</header>

		<?php $more_sections = nerv_terminal_is_more_view() ? nerv_terminal_mobile_more_sections() : array(); ?>
			<main class="<?php echo esc_attr( $main_class ); ?>" id="main">
				<?php if ( $is_reading ) : ?>
					<section class="nerv-column nerv-column--center nerv-column--reading" aria-label="<?php esc_attr_e( 'Reading page', 'nerv-terminal' ); ?>">
						<?php echo nerv_terminal_render_center_content(); ?>
					</section>
				<?php else : ?>
					<aside class="nerv-column nerv-column--left">
						<?php echo nerv_terminal_core_notice(); ?>
						<?php echo nerv_terminal_render_panel_column( 'left', $more_sections ); ?>
					</aside>

					<section class="nerv-column nerv-column--center" aria-label="<?php esc_attr_e( 'Main terminal panels', 'nerv-terminal' ); ?>">
						<?php echo nerv_terminal_render_center_content(); ?>
					</section>

					<aside class="nerv-column nerv-column--right">
						<?php echo nerv_terminal_render_panel_column( 'right', $more_sections ); ?>
					</aside>
				<?php endif; ?>
			</main>

		<footer class="nerv-footer">
			<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			<span><?php echo nerv_terminal_footer_partner_row() ?: esc_html( nerv_terminal_string( 'powered_by' ) ); ?></span>
			<span>THEME: NERV TERMINAL VERSION: <?php echo esc_html( NERV_TERMINAL_VERSION ); ?></span>
			<?php echo nerv_terminal_footer_extra_segments(); ?>
		</footer>

		<header class="nerv-mobile-appbar">
			<span><?php echo esc_html( nerv_terminal_string( 'brand_title' ) ); ?></span>
			<strong><span data-nerv-clock-short><?php echo esc_html( $clock_short ); ?></span> <i></i><?php echo esc_html( nerv_terminal_string( 'active_label' ) ); ?></strong>
		</header>

		<?php $mobile_tabs = nerv_terminal_mobile_tabs(); ?>
		<?php if ( nerv_terminal_mobile_app_enabled() && $mobile_tabs ) : ?>
		<nav class="nerv-mobile-tabs" style="--nerv-mobile-tab-count: <?php echo esc_attr( (string) count( $mobile_tabs ) ); ?>;" aria-label="<?php esc_attr_e( 'Mobile app navigation', 'nerv-terminal' ); ?>">
			<?php foreach ( $mobile_tabs as $tab ) : ?>
				<a class="<?php echo esc_attr( $tab['active'] ? 'is-active' : '' ); ?>" href="<?php echo esc_url( $tab['url'] ); ?>">
					<span aria-hidden="true"><?php echo nerv_terminal_icon_svg( $tab['icon'] ); ?></span>
					<strong><?php echo esc_html( $tab['label'] ); ?></strong>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function nerv_terminal_core_is_active(): bool {
	return function_exists( 'nerv_core_tools_refresh_markdown_cache' ) || defined( 'NERV_CORE_VERSION' );
}

function nerv_terminal_core_notice(): string {
	if ( nerv_terminal_core_is_active() ) {
		return '';
	}

	$plugin_file = WP_PLUGIN_DIR . '/nerv-core/nerv-core.php';
	$action_url  = is_file( $plugin_file )
		? wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=nerv-core/nerv-core.php' ), 'activate-plugin_nerv-core/nerv-core.php' )
		: admin_url( 'plugin-install.php' );
	$button_text = is_file( $plugin_file ) ? __( 'Activate NERV Core', 'nerv-terminal' ) : __( 'Install NERV Core', 'nerv-terminal' );
	$button      = current_user_can( 'activate_plugins' )
		? '<a class="nerv-button nerv-button--small exo-button" href="' . esc_url( $action_url ) . '">' . esc_html( $button_text ) . '</a>'
		: '';

	return nerv_terminal_panel(
		'nerv-panel--core-missing',
		'<div class="nerv-panel__heading"><h2>' . esc_html__( 'NERV Core Offline', 'nerv-terminal' ) . '</h2><span>' . esc_html__( 'Companion plugin required', 'nerv-terminal' ) . '</span></div>' .
		'<div class="nerv-warning exo-alert" data-tone="danger" data-label="WARNING"><span aria-hidden="true">!</span><strong>' . esc_html__( 'Theme is running in safe visual mode.', 'nerv-terminal' ) . '</strong><p>' . esc_html__( 'Activate NERV Core to restore GEO mirrors, AI cover tools, partners, crawler monitoring, related entries, and NERV CONTROL.', 'nerv-terminal' ) . '</p>' . $button . '</div>'
	);
}

function nerv_terminal_nav_items(): array {
	return array(
		array( 'label' => nerv_terminal_string( 'nav_home' ), 'url' => home_url( '/' ), 'active' => is_front_page() && ! nerv_terminal_is_more_view(), 'new_window' => false ),
		array( 'label' => nerv_terminal_string( 'nav_about' ), 'url' => home_url( '/about/' ), 'active' => nerv_terminal_is_view( 'about' ) || is_author(), 'new_window' => true ),
		array( 'label' => nerv_terminal_string( 'nav_projects' ), 'url' => home_url( '/projects/' ), 'active' => nerv_terminal_is_view( 'projects' ) || is_post_type_archive( 'project' ), 'new_window' => true ),
		array( 'label' => nerv_terminal_string( 'nav_blog' ), 'url' => home_url( '/blog/' ), 'active' => nerv_terminal_is_view( 'blog' ) || ( is_home() && ! is_front_page() ), 'new_window' => true ),
		array( 'label' => nerv_terminal_string( 'nav_partners' ), 'url' => home_url( '/partners/' ), 'active' => nerv_terminal_is_view( 'partners' ) || is_post_type_archive( 'partner' ) || is_singular( 'partner' ), 'new_window' => true ),
		array( 'label' => nerv_terminal_string( 'nav_gallery' ), 'url' => home_url( '/gallery/' ), 'active' => nerv_terminal_is_view( 'gallery' ), 'new_window' => true ),
		array( 'label' => nerv_terminal_string( 'nav_contact' ), 'url' => home_url( '/contact/' ), 'active' => nerv_terminal_is_view( 'contact' ), 'new_window' => true ),
	);
}

function nerv_terminal_post_type_url( string $post_type ): string {
	$url = get_post_type_archive_link( $post_type );
	return $url ?: add_query_arg( 'post_type', $post_type, home_url( '/' ) );
}

function nerv_terminal_dashboard_is_panel_layout(): bool {
	return ( is_front_page() || is_home() ) && ! is_paged() && ! nerv_terminal_is_more_view();
}

function nerv_terminal_is_reading_layout(): bool {
	if ( nerv_terminal_is_more_view() || nerv_terminal_dashboard_is_panel_layout() ) {
		return false;
	}

	return is_singular() || is_archive() || is_search() || is_404() || nerv_terminal_current_view();
}

function nerv_terminal_panel_renderers(): array {
	return array(
		'user'    => 'nerv_terminal_panel_user',
		'status'  => 'nerv_terminal_panel_system_status',
		'mission' => 'nerv_terminal_panel_mission',
		'server'  => 'nerv_terminal_panel_server',
		'hero'    => 'nerv_terminal_panel_hero',
		'latest'  => 'nerv_terminal_panel_latest_projects',
		'log'     => 'nerv_terminal_panel_system_log',
		'pilot'   => 'nerv_terminal_panel_pilot',
		'monitor' => 'nerv_terminal_panel_monitor',
		'alert'   => 'nerv_terminal_panel_alert',
	);
}

function nerv_terminal_render_panel_column( string $column, array $more_sections = array() ): string {
	if ( ! nerv_terminal_dashboard_is_panel_layout() ) {
		if ( 'left' === $column ) {
			return
				( nerv_terminal_panel_enabled( 'user' ) ? nerv_terminal_panel_user() : '' ) .
				( nerv_terminal_panel_enabled( 'status' ) && nerv_terminal_more_section_enabled( $more_sections, 'status' ) ? nerv_terminal_panel_system_status() : '' ) .
				( nerv_terminal_panel_enabled( 'mission' ) ? nerv_terminal_panel_mission() : '' ) .
				( nerv_terminal_panel_enabled( 'server' ) ? nerv_terminal_panel_server() : '' );
		}

		if ( 'right' === $column ) {
			return
				( nerv_terminal_panel_enabled( 'pilot' ) ? nerv_terminal_panel_pilot() : '' ) .
				( nerv_terminal_panel_enabled( 'monitor' ) && nerv_terminal_more_section_enabled( $more_sections, 'monitor' ) ? nerv_terminal_panel_monitor() : '' ) .
				( nerv_terminal_panel_enabled( 'alert' ) && nerv_terminal_more_section_enabled( $more_sections, 'alert' ) ? nerv_terminal_panel_alert() : '' );
		}
	}

	$output    = '';
	$renderers = nerv_terminal_panel_renderers();
	foreach ( nerv_terminal_panels_for_column( $column ) as $panel_id ) {
		if ( str_starts_with( $panel_id, 'custom:' ) ) {
			$output .= nerv_terminal_panel_custom( substr( $panel_id, 7 ) );
		} elseif ( isset( $renderers[ $panel_id ] ) && is_callable( $renderers[ $panel_id ] ) ) {
			$output .= call_user_func( $renderers[ $panel_id ] );
		}
	}

	return $output;
}

function nerv_terminal_bottom_nav_items(): array {
	$subtitles = array( 'ホーム', '自己紹介', 'プロジェクト', 'ブログ', 'ギャラリー', '連絡先' );
	$items     = nerv_terminal_nav_items();

	return array_map(
		static function ( array $item, int $index ) use ( $subtitles ): array {
			$item['number']   = str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT );
			$item['subtitle'] = $subtitles[ $index ] ?? '';
			return $item;
		},
		$items,
		array_keys( $items )
	);
}

function nerv_terminal_mobile_tabs(): array {
	$options = nerv_terminal_mobile_options();
	if ( empty( $options['enabled'] ) ) {
		return array();
	}

	$tabs = array();
	foreach ( (array) $options['tabs'] as $tab ) {
		if ( empty( $tab['enabled'] ) ) {
			continue;
		}

		if ( 'more' === (string) ( $tab['target'] ?? '' ) && empty( $options['more_enabled'] ) ) {
			continue;
		}

		$tabs[] = array(
			'label'  => (string) ( $tab['label'] ?? '' ),
			'url'    => nerv_terminal_mobile_tab_url( (string) ( $tab['target'] ?? 'custom' ), (string) ( $tab['url'] ?? '' ) ),
			'icon'   => (string) ( $tab['icon'] ?? 'grid' ),
			'target' => (string) ( $tab['target'] ?? 'custom' ),
			'active' => nerv_terminal_mobile_tab_is_active( (string) ( $tab['target'] ?? 'custom' ), (string) ( $tab['url'] ?? '' ) ),
		);
	}

	return array_slice( $tabs, 0, 5 );
}

function nerv_terminal_mobile_app_enabled(): bool {
	$options = nerv_terminal_mobile_options();

	return ! empty( $options['enabled'] );
}

function nerv_terminal_mobile_more_sections(): array {
	$options = nerv_terminal_mobile_options();

	return (array) ( $options['more_sections'] ?? array() );
}

function nerv_terminal_more_section_enabled( array $sections, string $section ): bool {
	if ( ! nerv_terminal_is_more_view() ) {
		return true;
	}

	return ! empty( $sections[ $section ] );
}

function nerv_terminal_mobile_tab_url( string $target, string $url ): string {
	switch ( $target ) {
		case 'home':
			return home_url( '/' );
		case 'blog':
			return home_url( '/blog/' );
		case 'projects':
			return nerv_terminal_post_type_url( 'project' );
		case 'pilot':
			return home_url( '/about/' );
		case 'more':
			return add_query_arg( 'nerv_more', '1', home_url( '/' ) );
		case 'partners':
			return nerv_terminal_post_type_url( 'partner' );
		case 'search':
			return home_url( '/?s=' );
		default:
			return $url ?: home_url( '/' );
	}
}

function nerv_terminal_mobile_tab_is_active( string $target, string $url ): bool {
	if ( 'more' === $target ) {
		return nerv_terminal_is_more_view();
	}

	if ( nerv_terminal_is_more_view() ) {
		return false;
	}

	switch ( $target ) {
		case 'home':
			return is_front_page();
		case 'blog':
			return ( nerv_terminal_is_view( 'blog' ) || is_home() || is_singular( 'post' ) ) && ! is_front_page();
		case 'projects':
			return nerv_terminal_is_view( 'projects' ) || is_post_type_archive( 'project' ) || is_singular( 'project' );
		case 'pilot':
			return nerv_terminal_is_view( 'about' ) || is_page( 'about' ) || is_author();
		case 'partners':
			return nerv_terminal_is_view( 'partners' ) || is_post_type_archive( 'partner' ) || is_singular( 'partner' ) || is_page( 'partners' );
		case 'search':
			return is_search();
		default:
			return $url && untrailingslashit( home_url( add_query_arg( null, null ) ) ) === untrailingslashit( $url );
	}
}

function nerv_terminal_render_center_content(): string {
	if ( nerv_terminal_is_more_view() ) {
		return nerv_terminal_panel_mobile_more();
	}

	if ( is_404() && ! nerv_terminal_current_view() ) {
		return nerv_terminal_panel_404();
	}

	if ( is_search() ) {
		return nerv_terminal_panel_archive( nerv_terminal_string( 'content_search_title' ), get_search_query() );
	}

	if ( ( is_home() && ! is_front_page() ) || is_page( 'blog' ) || nerv_terminal_is_view( 'blog' ) ) {
		return nerv_terminal_panel_blog_archive();
	}

	if ( is_post_type_archive( 'partner' ) || nerv_terminal_is_missing_post_type_request( 'partner' ) || is_page_template( 'partners' ) || is_page( 'partners' ) || nerv_terminal_is_view( 'partners' ) ) {
		return nerv_terminal_panel_partners();
	}

	if ( nerv_terminal_is_view( 'projects' ) || nerv_terminal_is_missing_post_type_request( 'project' ) ) {
		return nerv_terminal_panel_archive( nerv_terminal_string( 'content_archive_title' ), nerv_terminal_string( 'latest_title' ) );
	}

	$queried_object = get_queried_object();
	if ( is_singular() || $queried_object instanceof WP_Post ) {
		return nerv_terminal_panel_singular( $queried_object instanceof WP_Post ? $queried_object : null );
	}

	if ( is_archive() ) {
		return nerv_terminal_panel_archive( nerv_terminal_string( 'content_archive_title' ), get_the_archive_title() );
	}

	if ( nerv_terminal_dashboard_is_panel_layout() ) {
		return nerv_terminal_render_panel_column( 'center' );
	}

	return nerv_terminal_panel_archive( nerv_terminal_string( 'content_archive_title' ), wp_get_document_title() );
}

function nerv_terminal_is_more_view(): bool {
	return (bool) get_query_var( 'nerv_more' ) || isset( $_GET['nerv_more'] );
}

function nerv_terminal_current_view(): string {
	return sanitize_key( (string) get_query_var( 'nerv_view' ) );
}

function nerv_terminal_is_view( string $view ): bool {
	return $view === nerv_terminal_current_view();
}

function nerv_terminal_is_missing_post_type_request( string $post_type ): bool {
	return ! post_type_exists( $post_type )
		&& isset( $_GET['post_type'] )
		&& $post_type === sanitize_key( wp_unslash( $_GET['post_type'] ) );
}

function nerv_terminal_panel( string $class, string $html ): string {
	$label = nerv_terminal_panel_label_from_class( $class );
	return '<section class="' . esc_attr( trim( 'nerv-panel panel ' . $class ) ) . '" data-label="' . esc_attr( $label ) . '">' . $html . '</section>';
}

function nerv_terminal_new_window_attrs(): string {
	return ' target="_blank" rel="noopener noreferrer"';
}

function nerv_terminal_rel_with_noopener( string $rel ): string {
	$tokens = preg_split( '/\s+/', strtolower( trim( $rel ) ) ) ?: array();
	$tokens = array_filter( array_unique( array_merge( $tokens, array( 'noopener', 'noreferrer' ) ) ) );

	return implode( ' ', $tokens );
}

function nerv_terminal_pagination( ?WP_Query $query = null ): string {
	$query = $query ?: $GLOBALS['wp_query'];
	if ( ! $query instanceof WP_Query || (int) $query->max_num_pages < 2 ) {
		return '';
	}

	$current = max( 1, absint( get_query_var( 'paged' ) ?: get_query_var( 'page' ) ) );
	$links = paginate_links(
		array(
			'total'     => (int) $query->max_num_pages,
			'current'   => $current,
			'type'      => 'array',
			'prev_text' => __( 'Prev', 'nerv-terminal' ),
			'next_text' => __( 'Next', 'nerv-terminal' ),
		)
	);

	if ( ! is_array( $links ) || ! $links ) {
		return '';
	}

	return '<nav class="nerv-pagination exo-pagination" aria-label="' . esc_attr__( 'Pagination', 'nerv-terminal' ) . '">' . implode( '', array_map( 'wp_kses_post', $links ) ) . '</nav>';
}

function nerv_terminal_prepare_entry_content( string $content ): string {
	if ( '' === trim( $content ) ) {
		return $content;
	}

	return preg_replace_callback(
		'~<pre([^>]*)>\s*<code([^>]*)>(.*?)</code>\s*</pre>~is',
		static function ( array $matches ): string {
			$pre_attrs  = $matches[1] ?? '';
			$code_attrs = $matches[2] ?? '';
			$code       = $matches[3] ?? '';
			$lang       = 'CODE';
			if ( preg_match( '/language-([a-z0-9_-]+)/i', $pre_attrs . ' ' . $code_attrs, $lang_match ) ) {
				$lang = strtoupper( $lang_match[1] );
			}

			return '<div class="code-shell wp-block-code"><div class="codebar"><span class="filename">ARTICLE</span><span class="language">' . esc_html( $lang ) . '</span></div><pre' . $pre_attrs . '><code' . $code_attrs . '>' . $code . '</code></pre></div>';
		},
		$content
	) ?: $content;
}

function nerv_terminal_panel_label_from_class( string $class ): string {
	$labels = array(
		'nerv-panel--user'          => 'IDENTITY',
		'nerv-panel--status'        => 'STATUS',
		'nerv-panel--mission'       => 'MISSION',
		'nerv-panel--server'        => 'SERVER',
		'nerv-panel--hero'          => 'COMMAND',
		'nerv-panel--projects'      => 'PROJECTS',
		'nerv-panel--log'           => 'LOG',
		'nerv-panel--pilot'         => 'PILOT',
		'nerv-panel--monitor'       => 'MONITOR',
		'nerv-panel--alert'         => 'ALERT',
		'nerv-panel--entry'         => 'ARTICLE',
		'nerv-panel--archive'       => 'ARCHIVE',
		'nerv-panel--author'        => 'AUTHOR',
		'nerv-panel--related'       => 'RELATED',
		'nerv-panel--partners'      => 'ALLIES',
		'nerv-panel--partner-apply' => 'CONTACT',
		'nerv-panel--more'          => 'MOBILE',
	);

	foreach ( $labels as $needle => $label ) {
		if ( str_contains( $class, $needle ) ) {
			return $label;
		}
	}

	return 'EXOFRAME';
}

function nerv_terminal_panel_heading( string $title_key, string $subtitle_key = '' ): string {
	$html = '<div class="nerv-panel__heading"><h2>' . esc_html( nerv_terminal_string( $title_key ) ) . '</h2>';
	if ( $subtitle_key ) {
		$html .= '<span>' . esc_html( nerv_terminal_string( $subtitle_key ) ) . '</span>';
	}
	$html .= '</div>';
	return $html;
}

function nerv_terminal_panel_custom( string $panel_id ): string {
	if ( ! function_exists( 'nerv_terminal_custom_panels' ) ) {
		return '';
	}

	$panel = null;
	foreach ( nerv_terminal_custom_panels() as $custom_panel ) {
		if ( $panel_id === (string) ( $custom_panel['id'] ?? '' ) ) {
			$panel = $custom_panel;
			break;
		}
	}
	if ( ! is_array( $panel ) || empty( $panel['enabled'] ) ) {
		return '';
	}

	$content = (string) ( $panel['content'] ?? '' );
	if ( 'shortcode' === (string) ( $panel['content_type'] ?? '' ) ) {
		$content = do_shortcode( $content );
	} elseif ( 'richtext' === (string) ( $panel['content_type'] ?? '' ) ) {
		$content = wpautop( $content );
	}

	$html = '<div class="nerv-panel__heading"><h2>' . esc_html( (string) ( $panel['title'] ?? '' ) ) . '</h2>';
	if ( ! empty( $panel['subtitle'] ) ) {
		$html .= '<span>' . esc_html( (string) $panel['subtitle'] ) . '</span>';
	}
	$html .= '</div>';
	$html .= '<div class="nerv-panel__custom-content">' . wp_kses_post( $content ) . '</div>';

	return nerv_terminal_panel( 'nerv-panel--custom nerv-panel--custom-' . sanitize_html_class( (string) ( $panel['id'] ?? '' ) ), $html );
}

function nerv_terminal_panel_user(): string {
	$user = wp_get_current_user();
	$name = $user->exists() ? sprintf( 'USER: %s', $user->display_name ) : nerv_terminal_string( 'user_title' );
	$auth = $user->exists() ? __( 'AUTHORIZATION: MEMBER', 'nerv-terminal' ) : nerv_terminal_string( 'authorization' );
	$code = $user->exists() ? sprintf( 'CODE: 0x%04X', (int) $user->ID ) : nerv_terminal_string( 'user_code' );

	return nerv_terminal_panel(
		'nerv-panel--user',
		'<p class="nerv-user-name">' . esc_html( $name ) . '</p>' .
		'<span>' . esc_html( nerv_terminal_string( 'clearance_label' ) ) . '</span>' .
		'<strong>' . esc_html( nerv_terminal_string( 'clearance_value' ) ) . '</strong>' .
		'<p>' . esc_html( $auth ) . '</p>' .
		'<p>' . esc_html( $code ) . '</p>' .
		'<i class="nerv-hazard" aria-hidden="true"></i>'
	);
}

function nerv_terminal_panel_system_status(): string {
	$rows = '';
	$status_rows = 'probes' === nerv_terminal_status_source() ? nerv_terminal_status_probe_rows() : nerv_terminal_status_rows();
	foreach ( $status_rows as $row ) {
		$state = sanitize_html_class( (string) ( $row['state'] ?? 'green' ) );
		if ( ! in_array( $state, array( 'green', 'amber', 'red' ), true ) ) {
			$state = 'green';
		}
		$rows .= '<li><i class="nerv-status-light nerv-status-light--' . esc_attr( $state ) . '"></i><span>' . esc_html( $row['label'] ) . '</span><strong>' . esc_html( $row['value'] ) . '</strong></li>';
	}
	return nerv_terminal_panel( 'nerv-panel--status', nerv_terminal_panel_heading( 'system_status_title' ) . '<ul class="nerv-status-list">' . $rows . '</ul>' );
}

function nerv_terminal_panel_mission(): string {
	return nerv_terminal_panel(
		'nerv-panel--mission',
		nerv_terminal_panel_heading( 'mission_title' ) .
		'<p>' . esc_html( nerv_terminal_string( 'mission_purpose' ) ) . '</p>' .
		'<p>' . esc_html( nerv_terminal_string( 'mission_state' ) ) . '</p>' .
		'<div class="nerv-nominal"><strong>' . esc_html( nerv_terminal_string( 'all_systems' ) ) . '</strong><span>' . esc_html( nerv_terminal_string( 'all_systems_sub' ) ) . '</span></div>'
	);
}

function nerv_terminal_panel_server(): string {
	return nerv_terminal_panel(
		'nerv-panel--server',
		nerv_terminal_panel_heading( 'server_title' ) .
		'<strong>' . esc_html( nerv_terminal_string( 'server_location' ) ) . '</strong>' .
		'<p>' . esc_html( nerv_terminal_string( 'server_lat' ) ) . '</p>' .
		'<p>' . esc_html( nerv_terminal_string( 'server_lon' ) ) . '</p>' .
		'<div class="nerv-map" aria-hidden="true">' . nerv_terminal_icon_svg( 'map' ) . '</div>'
	);
}

function nerv_terminal_panel_hero(): string {
	return nerv_terminal_panel(
		'nerv-panel--hero',
		'<div><p class="nerv-kicker">' . esc_html( nerv_terminal_string( 'hero_kicker' ) ) . '</p>' .
		'<h1>' . esc_html( nerv_terminal_string( 'hero_title' ) ) . '</h1>' .
		'<p>' . esc_html( nerv_terminal_string( 'hero_desc_1' ) ) . '</p>' .
		'<p>' . esc_html( nerv_terminal_string( 'hero_desc_2' ) ) . '</p>' .
		'<a class="nerv-button exo-button" href="' . esc_url( home_url( '/about/' ) ) . '"' . nerv_terminal_new_window_attrs() . '>' . esc_html( nerv_terminal_string( 'hero_button' ) ) . '</a></div>' .
		'<div class="nerv-watermark" aria-hidden="true">NERV</div>'
	);
}

function nerv_terminal_panel_singular( ?WP_Post $queried_post = null ): string {
	if ( $queried_post instanceof WP_Post ) {
		$GLOBALS['post'] = $queried_post;
		setup_postdata( $queried_post );
	} elseif ( have_posts() ) {
		the_post();
	} else {
		return nerv_terminal_panel_archive( nerv_terminal_string( 'content_archive_title' ), '' );
	}

	$post_id     = get_the_ID();
	$subtitle    = get_post_meta( $post_id, '_nerv_subtitle', true );
	$title_key   = is_singular( 'post' ) ? 'content_entry_title' : 'content_page_title';
	$image       = function_exists( 'nerv_core_cover_image' ) ? nerv_core_cover_image( $post_id, '5x2', 'nerv-entry-cover' ) : get_the_post_thumbnail( $post_id, 'nerv-cover', array( 'class' => 'nerv-entry-cover' ) );
	$meta        = sprintf(
		'%1$s / %2$s / %3$s',
		nerv_terminal_string( 'content_meta_prefix' ),
		get_the_date( 'Y-m-d', $post_id ),
		get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) )
	);
	$content     = nerv_terminal_prepare_entry_content( apply_filters( 'the_content', get_the_content() ) );
	$entry_panel = nerv_terminal_panel(
		'nerv-panel--entry nerv-panel--reading',
		'<div class="nerv-panel__heading nerv-panel__heading--split"><div><h2>' . esc_html( nerv_terminal_string( $title_key ) ) . '</h2><span>' . esc_html( $meta ) . '</span></div></div>' .
		'<article class="nerv-entry article"><h1>' . esc_html( get_the_title() ) . '</h1>' .
		( $subtitle ? '<p class="nerv-entry-subtitle">' . esc_html( $subtitle ) . '</p>' : '' ) .
		( $image ? '<figure class="nerv-entry-cover-wrap">' . $image . '</figure>' : '<div class="nerv-entry-cover-fallback" aria-hidden="true">ENTRY: 0x' . esc_html( strtoupper( dechex( $post_id ) ) ) . '</div>' ) .
		'<div class="nerv-entry-content">' . $content . '</div>' . nerv_terminal_geo_hidden_links( $post_id ) . '</article>'
	);

	$extra = '';
	if ( is_singular( 'post' ) ) {
		$extra = nerv_terminal_panel_author_card( $post_id ) . nerv_terminal_panel_related_entries( $post_id );
	}

	wp_reset_postdata();
	return $entry_panel . $extra;
}

function nerv_terminal_panel_archive( string $title, string $subtitle ): string {
	$cards = '';
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			$cards .= nerv_terminal_archive_card( get_the_ID() );
		}
		rewind_posts();
	} else {
		$cards = '<p class="nerv-empty">' . esc_html( nerv_terminal_string( 'no_entries' ) ) . '</p>';
	}

	$pagination = nerv_terminal_pagination();

	return nerv_terminal_panel(
		'nerv-panel--archive',
		'<div class="nerv-panel__heading nerv-panel__heading--split"><div><h2>' . esc_html( $title ) . '</h2><span>' . esc_html( wp_strip_all_tags( $subtitle ) ) . '</span></div></div>' .
		'<div class="nerv-archive-list">' . $cards . '</div>' . $pagination
	);
}

function nerv_terminal_panel_blog_archive(): string {
	$paged = max( 1, absint( get_query_var( 'paged' ) ?: get_query_var( 'page' ) ) );
	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => (int) get_option( 'posts_per_page', 10 ),
			'paged'               => $paged,
			'ignore_sticky_posts' => false,
		)
	);

	$cards = '';
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$cards .= nerv_terminal_archive_card( get_the_ID() );
		}
		wp_reset_postdata();
	} else {
		$cards = '<p class="nerv-empty">' . esc_html( nerv_terminal_string( 'no_entries' ) ) . '</p>';
	}

	$pagination = nerv_terminal_pagination( $query );

	return nerv_terminal_panel(
		'nerv-panel--archive nerv-panel--blog',
		'<div class="nerv-panel__heading nerv-panel__heading--split"><div><h2>' . esc_html( nerv_terminal_string( 'nav_blog' ) ) . '</h2><span>' . esc_html( nerv_terminal_string( 'content_archive_title' ) ) . '</span></div></div>' .
		'<div class="nerv-archive-list">' . $cards . '</div>' . $pagination
	);
}

function nerv_terminal_archive_card( int $post_id ): string {
	$image = function_exists( 'nerv_core_cover_url' ) ? nerv_core_cover_url( $post_id, '5x2' ) : get_the_post_thumbnail_url( $post_id, 'nerv-cover' );
	$style = $image ? ' style="background-image:url(' . esc_url( $image ) . ')"' : '';
	$title = get_the_title( $post_id );
	$link_attrs = nerv_terminal_new_window_attrs();
	return '<article class="nerv-archive-card exo-card">' .
		'<a class="nerv-card-image"' . $style . ' href="' . esc_url( get_permalink( $post_id ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Open %s', 'nerv-terminal' ), $title ) ) . '"' . $link_attrs . '></a>' .
		'<div><time>' . esc_html( get_the_date( 'Y-m-d', $post_id ) ) . '</time><h3><a href="' . esc_url( get_permalink( $post_id ) ) . '"' . $link_attrs . '>' . esc_html( $title ) . '</a></h3><p>' . esc_html( nerv_terminal_excerpt( $post_id, 26 ) ) . '</p></div>' .
		'</article>';
}

function nerv_terminal_panel_404(): string {
	return nerv_terminal_panel(
		'nerv-panel--entry nerv-panel--lost',
		'<div class="nerv-panel__heading"><h2>' . esc_html( nerv_terminal_string( 'content_404_title' ) ) . '</h2></div>' .
		'<div class="nerv-lost-code">404</div><p>' . esc_html( nerv_terminal_string( 'content_404_text' ) ) . '</p><a class="nerv-button exo-button" href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( nerv_terminal_string( 'mobile_tab_home' ) ) . '</a>'
	);
}

function nerv_terminal_panel_author_card( int $post_id ): string {
	$author_id = (int) get_post_field( 'post_author', $post_id );
	$name      = get_the_author_meta( 'display_name', $author_id );
	$bio       = get_the_author_meta( 'description', $author_id );
	$title     = function_exists( 'nerv_core_author_title' ) ? nerv_core_author_title( $author_id ) : '';
	$socials   = function_exists( 'nerv_core_author_social_links' ) ? nerv_core_author_social_links( $author_id ) : array();
	$avatar    = '<div class="nerv-author-avatar" aria-hidden="true">' . nerv_terminal_icon_svg( 'pilot' ) . '</div>';
	$social_html = '';

	if ( $socials ) {
		$social_links = '';
		foreach ( $socials as $social ) {
			$social_links .= '<a href="' . esc_url( $social['url'] ) . '" rel="me noopener noreferrer" target="_blank">' . esc_html( nerv_terminal_social_abbr( (string) $social['key'], (string) $social['label'] ) ) . '</a>';
		}
		$social_html = '<div class="nerv-author-socials" aria-label="' . esc_attr__( 'Author social links', 'nerv-terminal' ) . '">' . $social_links . '</div>';
	}

	return nerv_terminal_panel(
		'nerv-panel--author',
		'<div class="nerv-panel__heading"><h2>' . esc_html( nerv_terminal_string( 'author_card_title' ) ) . '</h2></div>' .
		'<div class="nerv-author-card">' . $avatar . '<div><h3>' . esc_html( $name ) . '</h3>' .
		( $title ? '<p class="nerv-author-title">' . esc_html( $title ) . '</p>' : '' ) .
		'<p>' . esc_html( $bio ?: nerv_terminal_string( 'pilot_bio' ) ) . '</p>' . $social_html .
		'<a class="nerv-button nerv-button--small exo-button" href="' . esc_url( get_author_posts_url( $author_id ) ) . '"' . nerv_terminal_new_window_attrs() . '>' . esc_html( nerv_terminal_string( 'author_more_entries' ) ) . '</a></div></div>'
	);
}

function nerv_terminal_social_abbr( string $key, string $label ): string {
	$abbr = array(
		'github'    => 'GH',
		'x'         => 'X',
		'youtube'   => 'YT',
		'linkedin'  => 'IN',
		'instagram' => 'IG',
		'bilibili'  => 'BB',
		'weibo'     => 'WB',
		'wechat'    => 'WX',
		'website'   => 'WEB',
		'email'     => '@',
		'rss'       => 'RSS',
	);

	return $abbr[ $key ] ?? strtoupper( substr( $label, 0, 3 ) );
}

function nerv_terminal_panel_related_entries( int $post_id ): string {
	if ( function_exists( 'nerv_core_related_is_enabled' ) && ! nerv_core_related_is_enabled() ) {
		return '';
	}

	$limit   = function_exists( 'nerv_core_related_limit' ) ? nerv_core_related_limit() : 3;
	$title   = function_exists( 'nerv_core_related_title' ) ? nerv_core_related_title() : nerv_terminal_string( 'related_title' );
	$related = nerv_terminal_related_posts( $post_id, $limit );
	$cards = '';
	if ( $related ) {
		foreach ( $related as $post ) {
			$cards .= nerv_terminal_archive_card( (int) $post->ID );
		}
	} else {
		$cards = '<p class="nerv-empty">' . esc_html( nerv_terminal_string( 'no_entries' ) ) . '</p>';
	}

	return nerv_terminal_panel(
		'nerv-panel--related',
		'<div class="nerv-panel__heading"><h2>' . esc_html( $title ) . '</h2></div><div class="nerv-archive-list nerv-archive-list--compact">' . $cards . '</div>'
	);
}

function nerv_terminal_panel_partners(): string {
	$application = nerv_terminal_partner_application_panel();

	return nerv_terminal_panel(
		'nerv-panel--partners',
		'<div class="nerv-panel__heading"><h2>' . esc_html( nerv_terminal_string( 'partners_title' ) ) . '</h2><span>' . esc_html( nerv_terminal_string( 'partners_subtitle' ) ) . '</span></div>' . nerv_terminal_partner_grid( 12, false, true )
	) . $application;
}

function nerv_terminal_partner_grid( int $limit = 12, bool $featured_only = false, bool $paginate = false ): string {
	$cards = '';
	$posts = array();
	$pagination = '';

	if ( $paginate && post_type_exists( 'partner' ) ) {
		$paged = max( 1, absint( get_query_var( 'paged' ) ?: get_query_var( 'page' ) ) );
		$query = new WP_Query(
			array(
				'post_type'      => 'partner',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'paged'          => $paged,
			)
		);
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$posts[] = get_post();
			}
			wp_reset_postdata();
		}
		$pagination = nerv_terminal_pagination( $query );
	} elseif ( function_exists( 'nerv_core_partner_query' ) && post_type_exists( 'partner' ) ) {
		$posts = nerv_core_partner_query( $limit, $featured_only );
	} elseif ( post_type_exists( 'partner' ) ) {
		$posts = get_posts(
			array(
				'post_type'      => 'partner',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
			)
		);
	}

	if ( $posts ) {
		foreach ( $posts as $partner ) {
			$cards .= nerv_terminal_partner_card( (int) $partner->ID );
		}
	} else {
		$cards = '<p class="nerv-empty">' . esc_html( nerv_terminal_string( 'no_entries' ) ) . '</p>';
	}

	return '<div class="nerv-partner-grid">' . $cards . '</div>' . $pagination;
}

function nerv_terminal_partner_card( int $post_id ): string {
	$url    = get_post_meta( $post_id, '_nerv_partner_url', true );
	$health = function_exists( 'nerv_core_partner_health_status' ) ? nerv_core_partner_health_status( $post_id ) : array( 'status' => 'online', 'message' => '' );
	$status = (string) ( $health['status'] ?? 'online' );
	$label  = function_exists( 'nerv_core_partner_health_status_label' ) ? nerv_core_partner_health_status_label( $status ) : strtoupper( $status );
	$rel    = function_exists( 'nerv_core_partner_link_rel' ) ? nerv_core_partner_link_rel( $post_id ) : 'noopener noreferrer';
	$target = ' target="_blank"';
	$tone   = 'offline' === $status ? 'danger' : ( 'slow' === $status ? 'signal' : 'success' );
	$href   = $url ?: get_permalink( $post_id );

	return '<article class="nerv-partner-card exo-card">' .
		'<div class="nerv-partner-logo">' . ( get_the_post_thumbnail( $post_id, 'thumbnail' ) ?: nerv_terminal_icon_svg( 'grid' ) ) . '</div>' .
		'<h3>' . esc_html( get_the_title( $post_id ) ) . '</h3>' .
		'<p>' . esc_html( nerv_terminal_excerpt( $post_id, 18 ) ) . '</p>' .
		'<span class="nerv-partner-status exo-badge nerv-partner-status--' . esc_attr( $status ) . '" data-tone="' . esc_attr( $tone ) . '" title="' . esc_attr( (string) ( $health['message'] ?? '' ) ) . '"><i></i>' . esc_html( $label ) . '</span>' .
		'<a class="nerv-button nerv-button--small exo-button" href="' . esc_url( $href ) . '" rel="' . esc_attr( nerv_terminal_rel_with_noopener( $rel ) ) . '"' . $target . '>' . esc_html( nerv_terminal_string( 'partner_visit' ) ) . '</a>' .
		'</article>';
}

function nerv_terminal_partner_application_panel(): string {
	if ( function_exists( 'nerv_core_partner_display_application_enabled' ) && ! nerv_core_partner_display_application_enabled() ) {
		return '';
	}

	$email = function_exists( 'nerv_core_partner_display_application_email' ) ? nerv_core_partner_display_application_email() : get_option( 'admin_email' );
	$text  = function_exists( 'nerv_core_partner_display_application_text' ) ? nerv_core_partner_display_application_text() : '';
	if ( ! $email ) {
		return '';
	}

	$text = $text ?: __( 'Want to establish an allied link? Send your site name, URL, and a short description for review.', 'nerv-terminal' );

	return nerv_terminal_panel(
		'nerv-panel--partner-apply',
		'<div class="nerv-panel__heading"><h2>' . esc_html( nerv_terminal_string( 'partner_apply_title' ) ) . '</h2><span>' . esc_html( nerv_terminal_string( 'partner_apply_subtitle' ) ) . '</span></div>' .
		'<div class="nerv-partner-apply exo-alert" data-tone="signal" data-label="ALLY"><p>' . esc_html( $text ) . '</p><a class="nerv-button nerv-button--small exo-button" href="mailto:' . esc_attr( $email ) . '">' . esc_html( nerv_terminal_string( 'partner_apply_button' ) ) . '</a></div>'
	);
}

function nerv_terminal_footer_partner_row(): string {
	if ( ! function_exists( 'nerv_core_partner_display_footer_enabled' ) || ! nerv_core_partner_display_footer_enabled() || ! function_exists( 'nerv_core_partner_query' ) ) {
		return '';
	}

	$limit = function_exists( 'nerv_core_partner_display_footer_limit' ) ? nerv_core_partner_display_footer_limit() : 4;
	$partners = nerv_core_partner_query( $limit, true );
	if ( ! $partners ) {
		$partners = nerv_core_partner_query( $limit, false );
	}

	if ( ! $partners ) {
		return '';
	}

	$links = '';
	foreach ( $partners as $partner ) {
		$url = get_post_meta( (int) $partner->ID, '_nerv_partner_url', true ) ?: get_permalink( $partner );
		$rel = function_exists( 'nerv_core_partner_link_rel' ) ? nerv_core_partner_link_rel( (int) $partner->ID ) : 'noopener noreferrer';
		$links .= '<a href="' . esc_url( $url ) . '" rel="' . esc_attr( $rel ) . '" target="_blank">' . esc_html( get_the_title( $partner ) ) . '</a>';
	}

	return '<span class="nerv-footer-partners"><b>' . esc_html( nerv_terminal_string( 'footer_partners_label' ) ) . '</b>' . $links . '</span>';
}

function nerv_terminal_footer_extra_segments(): string {
	$segments = '';
	if ( '1' === nerv_terminal_string( 'footer_record_enabled' ) && '' !== trim( nerv_terminal_string( 'footer_record_text' ) ) ) {
		$label = nerv_terminal_string( 'footer_record_label' );
		$text  = nerv_terminal_string( 'footer_record_text' );
		$url   = esc_url_raw( nerv_terminal_string( 'footer_record_url' ) );
		$content = esc_html( $label ? $label . ': ' . $text : $text );
		if ( $url ) {
			$content = '<a href="' . esc_url( $url ) . '" rel="noopener noreferrer" target="_blank">' . $content . '</a>';
		}
		$segments .= '<span class="nerv-footer-record">' . $content . '</span>';
	}

	if ( '1' === nerv_terminal_string( 'footer_extra_enabled' ) && '' !== trim( nerv_terminal_string( 'footer_extra_text' ) ) ) {
		$segments .= '<span class="nerv-footer-extra">' . esc_html( nerv_terminal_string( 'footer_extra_text' ) ) . '</span>';
	}

	return $segments;
}

function nerv_terminal_panel_mobile_more(): string {
	$options  = nerv_terminal_mobile_options();
	$sections = (array) ( $options['more_sections'] ?? array() );
	$links = '';
	foreach ( nerv_terminal_bottom_nav_items() as $item ) {
		$links .= '<a href="' . esc_url( $item['url'] ) . '"><span>' . esc_html( $item['number'] ) . '</span><strong>' . esc_html( $item['label'] ) . '</strong><small>' . esc_html( $item['subtitle'] ) . '</small></a>';
	}

	$search = ! empty( $sections['search'] ) ? '<form class="nerv-more-search exo-form" role="search" method="get" action="' . esc_url( home_url( '/' ) ) . '"><label><span>' . esc_html( nerv_terminal_string( 'mobile_more_search' ) ) . '</span><input class="exo-field" type="search" name="s" value="' . esc_attr( get_search_query() ) . '" placeholder="MAGI QUERY"></label><button class="nerv-button nerv-button--small exo-button" type="submit">&gt; RUN</button></form>' : '';
	$footer = ! empty( $sections['footer'] ) ? '<p class="nerv-more-footer">&copy; ' . esc_html( gmdate( 'Y' ) ) . ' ' . esc_html( get_bloginfo( 'name' ) ) . ' / ' . esc_html( nerv_terminal_string( 'powered_by' ) ) . '</p>' : '';

	$output = nerv_terminal_panel(
		'nerv-panel--more',
		'<div class="nerv-panel__heading"><h2>' . esc_html( nerv_terminal_string( 'mobile_more_title' ) ) . '</h2><span>' . esc_html( nerv_terminal_string( 'mobile_more_navigation' ) ) . '</span></div>' .
		'<div class="nerv-more-links">' . $links . '</div>' .
		$search .
		'<div class="nerv-more-status-heading">' . esc_html( nerv_terminal_string( 'mobile_more_status' ) ) . '</div>'
	);
	$output .= ! empty( $sections['status'] ) && nerv_terminal_panel_enabled( 'status' ) ? nerv_terminal_panel_system_status() : '';
	$output .= ! empty( $sections['monitor'] ) && nerv_terminal_panel_enabled( 'monitor' ) ? nerv_terminal_panel_monitor() : '';
	$output .= ! empty( $sections['alert'] ) && nerv_terminal_panel_enabled( 'alert' ) ? nerv_terminal_panel_alert() : '';
	$output .= $footer ? nerv_terminal_panel( 'nerv-panel--more-footer', '<div class="nerv-panel__heading"><h2>' . esc_html( nerv_terminal_string( 'mobile_more_footer' ) ) . '</h2></div>' . $footer ) : '';

	return $output;
}

function nerv_terminal_panel_latest_projects(): string {
	$query = new WP_Query(
		array(
			'post_type'      => post_type_exists( 'project' ) ? 'project' : 'post',
			'posts_per_page' => 3,
			'post_status'    => 'publish',
		)
	);

	$cards = '';
	$count = 0;
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$cards .= nerv_terminal_project_card( get_the_ID() );
			$count++;
		}
		wp_reset_postdata();
	}

	if ( $count < 3 ) {
		for ( $i = $count + 1; $i <= 3; $i++ ) {
			$cards .= nerv_terminal_placeholder_card( $i );
		}
	}

	return nerv_terminal_panel(
		'nerv-panel--projects',
		'<div class="nerv-panel__heading nerv-panel__heading--split"><div><h2>' . esc_html( nerv_terminal_string( 'latest_title' ) ) . '</h2><span>' . esc_html( nerv_terminal_string( 'latest_subtitle' ) ) . '</span></div><a href="' . esc_url( nerv_terminal_post_type_url( 'project' ) ) . '"' . nerv_terminal_new_window_attrs() . '>' . esc_html( nerv_terminal_string( 'view_all' ) ) . '</a></div>' .
		'<div class="nerv-project-grid">' . $cards . '</div>'
	);
}

function nerv_terminal_project_card( int $post_id ): string {
	$image = function_exists( 'nerv_core_cover_url' ) ? nerv_core_cover_url( $post_id, '5x2' ) : get_the_post_thumbnail_url( $post_id, 'nerv-cover' );
	$style = $image ? ' style="background-image:url(' . esc_url( $image ) . ')"' : '';
	$cat   = get_the_category( $post_id );
	$cat_name = $cat ? $cat[0]->name : __( 'WordPress', 'nerv-terminal' );
	$title = get_the_title( $post_id );
	$link_attrs = nerv_terminal_new_window_attrs();

	return '<article class="nerv-project-card exo-card">' .
		'<a class="nerv-card-image"' . $style . ' href="' . esc_url( get_permalink( $post_id ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Open project %s', 'nerv-terminal' ), $title ) ) . '"' . $link_attrs . '></a>' .
		'<h3>' . esc_html( nerv_terminal_string( 'project_prefix' ) . ' ' . $title ) . '</h3>' .
		'<p class="nerv-card-cat">' . esc_html( nerv_terminal_string( 'category_label' ) . ' ' . $cat_name ) . '</p>' .
		'<p>' . esc_html( nerv_terminal_excerpt( $post_id, 18 ) ) . '</p>' .
		'<a class="nerv-button nerv-button--small exo-button" href="' . esc_url( get_permalink( $post_id ) ) . '"' . $link_attrs . '>' . esc_html( nerv_terminal_string( 'detail_button' ) ) . '</a>' .
		'</article>';
}

function nerv_terminal_excerpt( int $post_id, int $words = 24 ): string {
	$excerpt = get_post_field( 'post_excerpt', $post_id );
	if ( '' === trim( $excerpt ) ) {
		$excerpt = get_post_field( 'post_content', $post_id );
	}

	$excerpt = html_entity_decode( wp_strip_all_tags( $excerpt ), ENT_QUOTES, get_bloginfo( 'charset' ) );
	return wp_trim_words( $excerpt, $words, '...' );
}

function nerv_terminal_related_posts( int $post_id, int $limit = 3 ): array {
	if ( function_exists( 'nerv_core_related_entries' ) ) {
		return nerv_core_related_entries( $post_id, $limit );
	}

	$category_ids = wp_get_post_categories( $post_id );
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'post__not_in'        => array( $post_id ),
		'posts_per_page'      => $limit,
		'ignore_sticky_posts' => true,
	);
	if ( $category_ids ) {
		$args['category__in'] = $category_ids;
	}

	return get_posts( $args );
}

function nerv_terminal_geo_hidden_links( int $post_id ): string {
	if ( ! function_exists( 'nerv_core_geo_markdown_url' ) || ! function_exists( 'nerv_core_geo_llms_url' ) ) {
		return '';
	}
	if ( function_exists( 'nerv_core_geo_public_post_types' ) && ! in_array( get_post_type( $post_id ), nerv_core_geo_public_post_types(), true ) ) {
		return '';
	}

	$links = array(
		'<a href="' . esc_url( nerv_core_geo_markdown_url( $post_id ) ) . '" rel="alternate" type="text/markdown">Markdown mirror</a>',
		'<a href="' . esc_url( nerv_core_geo_llms_url( false ) ) . '">llms.txt</a>',
		'<a href="' . esc_url( get_author_posts_url( (int) get_post_field( 'post_author', $post_id ) ) ) . '">Author archive</a>',
	);

	if ( ! function_exists( 'nerv_core_related_is_enabled' ) || nerv_core_related_is_enabled() ) {
		$limit   = function_exists( 'nerv_core_related_limit' ) ? nerv_core_related_limit() : 3;
		$related = nerv_terminal_related_posts( $post_id, $limit );
		foreach ( $related as $post ) {
			$links[] = '<a href="' . esc_url( get_permalink( $post ) ) . '">Related: ' . esc_html( get_the_title( $post ) ) . '</a>';
		}
	}

	return '<section hidden class="nerv-geo-links" aria-hidden="true"><h2>Machine-readable resources</h2>' . implode( '', $links ) . '</section>';
}

function nerv_terminal_placeholder_card( int $index ): string {
	$titles = array( 'EVA-01', 'TOKYO-3', 'MAGI SYSTEM' );
	$cats   = array( 'Web Design', 'CMS / WordPress', 'Plugin' );
	return '<article class="nerv-project-card exo-card">' .
		'<div class="nerv-card-image nerv-card-image--placeholder nerv-card-image--' . esc_attr( (string) $index ) . '"></div>' .
		'<h3>' . esc_html( nerv_terminal_string( 'project_prefix' ) . ' ' . $titles[ $index - 1 ] ) . '</h3>' .
		'<p class="nerv-card-cat">' . esc_html( nerv_terminal_string( 'category_label' ) . ' ' . $cats[ $index - 1 ] ) . '</p>' .
		'<p>' . esc_html__( 'WordPress terminal operation record initialized.', 'nerv-terminal' ) . '</p>' .
		'<a class="nerv-button nerv-button--small exo-button" href="' . esc_url( nerv_terminal_post_type_url( 'project' ) ) . '"' . nerv_terminal_new_window_attrs() . '>' . esc_html( nerv_terminal_string( 'detail_button' ) ) . '</a>' .
		'</article>';
}

function nerv_terminal_panel_system_log(): string {
	$rows = '';
	if ( 'posts' === nerv_terminal_log_source() ) {
		$posts = get_posts(
			array(
				'numberposts'      => 5,
				'post_type'        => 'post',
				'post_status'      => 'publish',
				'suppress_filters' => false,
			)
		);
		foreach ( $posts as $offset => $post ) {
			$rows .= '<li><time datetime="' . esc_attr( get_the_date( DATE_W3C, $post ) ) . '">' . esc_html( get_the_date( 'H:i:s', $post ) ) . '</time> <strong>[POST]</strong> <a href="' . esc_url( get_permalink( $post ) ) . '"' . nerv_terminal_new_window_attrs() . '>' . esc_html( get_the_title( $post ) ) . '</a></li>';
		}
	}

	if ( '' === $rows ) {
		foreach ( nerv_terminal_log_rows() as $offset => $row ) {
			$level = strtoupper( (string) ( $row['label'] ?? 'INFO' ) );
			$rows .= '<li><time data-nerv-log-time data-offset="' . esc_attr( (string) $offset ) . '">' . esc_html( current_time( 'H:i:s' ) ) . '</time> <strong>[' . esc_html( $level ?: 'INFO' ) . ']</strong> ' . esc_html( (string) ( $row['value'] ?? '' ) ) . '</li>';
		}
	}
	return nerv_terminal_panel(
		'nerv-panel--log',
		'<div class="nerv-panel__heading nerv-panel__heading--split"><div><h2>' . esc_html( nerv_terminal_string( 'log_title' ) ) . '</h2><span>' . esc_html( nerv_terminal_string( 'log_subtitle' ) ) . '</span></div><small>' . esc_html( nerv_terminal_string( 'log_level' ) ) . '</small></div>' .
		'<ul class="nerv-log-list">' . $rows . '</ul><a class="nerv-log-more" href="' . esc_url( get_post_type_archive_link( 'post' ) ?: home_url( '/blog/' ) ) . '"' . nerv_terminal_new_window_attrs() . '>' . esc_html( nerv_terminal_string( 'more_logs' ) ) . '</a>'
	);
}

function nerv_terminal_panel_pilot(): string {
	return nerv_terminal_panel(
		'nerv-panel--pilot',
		nerv_terminal_panel_heading( 'pilot_title', 'pilot_subtitle' ) .
		'<div class="nerv-pilot-layout"><div class="nerv-avatar">' . nerv_terminal_icon_svg( 'pilot' ) . '</div><div><strong>' . esc_html( nerv_terminal_string( 'pilot_role' ) ) . '</strong><p>' . esc_html( nerv_terminal_string( 'pilot_bio' ) ) . '</p><p>' . esc_html( nerv_terminal_string( 'pilot_base' ) ) . '</p><p>' . esc_html( nerv_terminal_string( 'pilot_duty' ) ) . '</p></div></div>' .
		nerv_terminal_global_social_links()
	);
}

function nerv_terminal_global_social_links(): string {
	$socials = function_exists( 'nerv_core_social_links' ) ? nerv_core_social_links() : array(
		array( 'key' => 'github', 'label' => 'GitHub', 'url' => home_url( '/' ), 'rel' => 'me noopener noreferrer' ),
		array( 'key' => 'x', 'label' => 'X / Twitter', 'url' => home_url( '/' ), 'rel' => 'me noopener noreferrer' ),
		array( 'key' => 'youtube', 'label' => 'YouTube', 'url' => home_url( '/' ), 'rel' => 'me noopener noreferrer' ),
		array( 'key' => 'email', 'label' => 'Email', 'url' => 'mailto:' . get_option( 'admin_email' ), 'rel' => 'nofollow' ),
	);

	if ( ! $socials ) {
		return '';
	}

	$open_new_tab = true;
	if ( function_exists( 'nerv_core_social_options' ) ) {
		$options      = nerv_core_social_options();
		$open_new_tab = ! empty( $options['open_new_tab'] );
	}

	$links = '';
	foreach ( $socials as $social ) {
		$url = (string) ( $social['url'] ?? '' );
		$key   = (string) ( $social['key'] ?? 'website' );
		$label = (string) ( $social['label'] ?? $key );
		$rel   = trim( (string) ( $social['rel'] ?? 'me noopener noreferrer' ) );
		$qr_url = esc_url_raw( (string) ( $social['qr_url'] ?? '' ) );
		if ( 'wechat' === $key && $qr_url ) {
			$links .= '<details class="nerv-social-qr tooltip"><summary aria-label="' . esc_attr( $label ) . '">' . esc_html( nerv_terminal_social_abbr( $key, $label ) ) . '</summary><span><img src="' . esc_url( $qr_url ) . '" alt="' . esc_attr( $label ) . '"></span></details>';
			continue;
		}
		if ( '' === $url ) {
			continue;
		}
		$target = $open_new_tab && ! str_starts_with( $url, 'mailto:' ) ? ' target="_blank"' : '';
		$links .= '<a href="' . esc_url( $url ) . '" rel="' . esc_attr( $rel ?: 'me noopener noreferrer' ) . '"' . $target . ' aria-label="' . esc_attr( $label ) . '">' . esc_html( nerv_terminal_social_abbr( $key, $label ) ) . '</a>';
	}

	return $links ? '<div class="nerv-social" aria-label="' . esc_attr__( 'Social links', 'nerv-terminal' ) . '">' . $links . '</div>' : '';
}

function nerv_terminal_panel_monitor(): string {
	$rows = '';
	$monitor_rows = 'probes' === nerv_terminal_monitor_source() ? nerv_terminal_monitor_probe_rows() : nerv_terminal_monitor_rows();
	foreach ( $monitor_rows as $row ) {
		$bars = '';
		for ( $i = 0; $i < 28; $i++ ) {
			$height = 18 + ( ( $i * 7 + (int) $row['level'] ) % 24 );
			$bars .= '<i style="height:' . esc_attr( (string) $height ) . 'px"></i>';
		}
		$rows .= '<li><div><span>' . esc_html( $row['label'] ) . '</span><strong>' . esc_html( $row['value'] ) . '</strong></div><em><b style="width:' . esc_attr( (string) $row['level'] ) . '%"></b></em><p>' . $bars . '</p></li>';
	}
	return nerv_terminal_panel( 'nerv-panel--monitor', nerv_terminal_panel_heading( 'monitor_title', 'monitor_subtitle' ) . '<ul class="nerv-monitor-list">' . $rows . '</ul>' );
}

function nerv_terminal_panel_alert(): string {
	return nerv_terminal_panel(
		'nerv-panel--alert',
		nerv_terminal_panel_heading( 'alert_title', 'alert_subtitle' ) .
		'<div class="nerv-warning exo-alert" data-tone="danger" data-label="WARNING"><span aria-hidden="true">!</span><strong>' . esc_html( nerv_terminal_string( 'warning_level' ) ) . '</strong><p>' . esc_html( nerv_terminal_string( 'warning_text' ) ) . '</p><p>' . esc_html( nerv_terminal_string( 'warning_ip' ) ) . '</p><p>TIME: <span data-nerv-clock-short>' . esc_html( current_time( 'H:i' ) ) . '</span></p><a class="nerv-button nerv-button--small exo-button" href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html( nerv_terminal_string( 'warning_button' ) ) . '</a></div>'
	);
}

function nerv_terminal_icon_svg( string $name ): string {
	$common = 'fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="square" stroke-linejoin="miter"';
	switch ( $name ) {
		case 'home':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="M3 11 12 3l9 8v9h-6v-6H9v6H3z"/></svg>';
		case 'blog':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg>';
		case 'grid':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></svg>';
		case 'pilot':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="M12 3 5 7v6c0 4 3 7 7 8 4-1 7-4 7-8V7zM8 12h8M10 16h4"/></svg>';
		case 'more':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="M4 7h16M4 12h16M4 17h16"/></svg>';
		case 'search':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="m15 15 5 5M10 17a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z"/></svg>';
		case 'user':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21c1-5 15-5 16 0"/></svg>';
		case 'status':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="M4 12h4l2-6 4 12 2-6h4"/></svg>';
		case 'monitor':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="M4 19V5M4 19h16M8 16V9M12 16V4M16 16v-6M20 16V7"/></svg>';
		case 'alert':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="M12 3 3 20h18L12 3ZM12 9v5M12 17h.01"/></svg>';
		case 'contact':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg>';
		case 'gallery':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="M4 5h16v14H4zM7 15l3-4 3 3 2-2 3 3M8 9h.01"/></svg>';
		case 'tools':
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="m14 6 4 4M4 20l6-6M13 7l4-4 4 4-4 4zM5 5l4 4"/></svg>';
		case 'map':
			return '<svg viewBox="0 0 240 120" aria-hidden="true"><path ' . $common . ' d="M10 95 92 48l42 22 96-55v82L146 69l-50 34zM92 48v55M146 69v42M10 95l136 16 84-42"/><path ' . $common . ' d="M119 29l18 15-9 18 19 16-31 12-18-20 13-18-19-9z"/></svg>';
		default:
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path ' . $common . ' d="M4 4h16v16H4z"/></svg>';
	}
}
