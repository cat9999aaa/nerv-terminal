<?php
/**
 * Default strings and lightweight option accessors.
 *
 * Every visible default string starts here so later NERV CONTROL fields,
 * filters, i18n, and audit checks have one registry to inspect.
 *
 * @package NervTerminal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_terminal_default_strings(): array {
	$defaults = array(
		'brand_title'              => __( 'NERV TERMINAL', 'nerv-terminal' ),
		'brand_subtitle'           => __( 'PERSONAL PORTFOLIO', 'nerv-terminal' ),
		'brand_mark'               => __( 'NERV', 'nerv-terminal' ),
		'nav_home'                 => __( 'HOME', 'nerv-terminal' ),
		'nav_about'                => __( 'ABOUT', 'nerv-terminal' ),
		'nav_projects'             => __( 'PROJECTS', 'nerv-terminal' ),
		'nav_blog'                 => __( 'BLOG', 'nerv-terminal' ),
		'nav_partners'             => __( 'PARTNERS', 'nerv-terminal' ),
		'nav_gallery'              => __( 'GALLERY', 'nerv-terminal' ),
		'nav_contact'              => __( 'CONTACT', 'nerv-terminal' ),
		'clock_label'              => __( 'SYSTEM TIME', 'nerv-terminal' ),
		'clock_timezone'           => __( 'JST', 'nerv-terminal' ),
		'active_label'             => __( 'ACTIVE', 'nerv-terminal' ),
		'user_title'               => __( 'USER: VISITOR', 'nerv-terminal' ),
		'clearance_label'          => __( 'CLEARANCE LEVEL', 'nerv-terminal' ),
		'clearance_value'          => __( 'TOP SECRET', 'nerv-terminal' ),
		'authorization'            => __( 'AUTHORIZATION: GUEST', 'nerv-terminal' ),
		'user_code'                => __( 'CODE: 0x00A1', 'nerv-terminal' ),
		'system_status_title'      => __( 'SYSTEM STATUS', 'nerv-terminal' ),
		'mission_title'            => __( 'MISSION STATUS', 'nerv-terminal' ),
		'mission_purpose'          => __( 'PURPOSE: PERSONAL SITE', 'nerv-terminal' ),
		'mission_state'            => __( 'STATE: STANDBY', 'nerv-terminal' ),
		'all_systems'              => __( 'ALL SYSTEMS NOMINAL', 'nerv-terminal' ),
		'all_systems_sub'          => __( 'All systems are operating normally.', 'nerv-terminal' ),
		'server_title'             => __( 'SERVER LOCATION', 'nerv-terminal' ),
		'server_location'          => __( 'GLOBAL NODE', 'nerv-terminal' ),
		'server_lat'               => __( 'LAT: 35.6895° N', 'nerv-terminal' ),
		'server_lon'               => __( 'LON: 139.6917° E', 'nerv-terminal' ),
		'hero_kicker'              => __( '> WELCOME TO MY TERMINAL', 'nerv-terminal' ),
		'hero_title'               => __( 'Welcome to my operating space', 'nerv-terminal' ),
		'hero_desc_1'              => __( 'A personal WordPress site for writing, projects, and archives.', 'nerv-terminal' ),
		'hero_desc_2'              => __( 'Technology, cinema, design, and notes for the future.', 'nerv-terminal' ),
		'hero_button'              => __( '> ABOUT ME: OPEN', 'nerv-terminal' ),
		'latest_title'             => __( 'LATEST PROJECTS', 'nerv-terminal' ),
		'latest_subtitle'          => __( '> Latest operation records', 'nerv-terminal' ),
		'view_all'                 => __( '> VIEW ALL', 'nerv-terminal' ),
		'project_prefix'           => __( 'PROJECT:', 'nerv-terminal' ),
		'category_label'           => __( 'Category:', 'nerv-terminal' ),
		'detail_button'            => __( '> View details', 'nerv-terminal' ),
		'log_title'                => __( 'SYSTEM LOG', 'nerv-terminal' ),
		'log_subtitle'             => __( '> System log', 'nerv-terminal' ),
		'log_level'                => __( 'LOG LEVEL: INFO', 'nerv-terminal' ),
		'more_logs'                => __( '> MORE LOGS', 'nerv-terminal' ),
		'pilot_title'              => __( 'PILOT PROFILE', 'nerv-terminal' ),
		'pilot_subtitle'           => __( 'Profile information', 'nerv-terminal' ),
		'pilot_role'               => __( 'Independent developer / blogger', 'nerv-terminal' ),
		'pilot_bio'                => __( 'A WordPress-focused builder documenting code, design, and durable ideas.', 'nerv-terminal' ),
		'pilot_base'               => __( 'Base: GLOBAL NODE', 'nerv-terminal' ),
		'pilot_duty'               => __( 'Role: Site operator', 'nerv-terminal' ),
		'monitor_title'            => __( 'SYSTEM MONITOR', 'nerv-terminal' ),
		'monitor_subtitle'         => __( 'System monitoring', 'nerv-terminal' ),
		'alert_title'              => __( 'ALERT PANEL', 'nerv-terminal' ),
		'alert_subtitle'           => __( 'Alert panel', 'nerv-terminal' ),
		'warning_level'            => __( 'WARNING LEVEL 2', 'nerv-terminal' ),
		'warning_text'             => __( 'External anomaly detected', 'nerv-terminal' ),
		'warning_ip'               => __( 'IP: 192.168.0.99', 'nerv-terminal' ),
		'warning_button'           => __( '> Respond', 'nerv-terminal' ),
		'emergency_contact'        => __( '> EMERGENCY CONTACT', 'nerv-terminal' ),
		'emergency_code'           => __( 'CODE: 0xEVA007', 'nerv-terminal' ),
		'emergency_jp'             => __( 'Emergency contact network', 'nerv-terminal' ),
		'powered_by'               => __( 'POWERED BY WORDPRESS', 'nerv-terminal' ),
		'mobile_tab_home'          => __( 'HOME', 'nerv-terminal' ),
		'mobile_tab_blog'          => __( 'BLOG', 'nerv-terminal' ),
		'mobile_tab_projects'      => __( 'PROJECTS', 'nerv-terminal' ),
		'mobile_tab_pilot'         => __( 'PILOT', 'nerv-terminal' ),
		'mobile_tab_more'          => __( 'MORE', 'nerv-terminal' ),
		'mobile_more_title'        => __( 'MORE / Extra panels', 'nerv-terminal' ),
		'mobile_more_status'       => __( 'STATUS PANELS', 'nerv-terminal' ),
		'mobile_more_navigation'   => __( 'NAVIGATION', 'nerv-terminal' ),
		'mobile_more_search'       => __( 'SEARCH', 'nerv-terminal' ),
		'mobile_more_footer'       => __( 'TERMINAL COPYRIGHT', 'nerv-terminal' ),
		'content_entry_title'      => __( 'ENTRY DETAIL', 'nerv-terminal' ),
		'content_page_title'       => __( 'STATIC PAGE', 'nerv-terminal' ),
		'content_archive_title'    => __( 'ARCHIVE INDEX', 'nerv-terminal' ),
		'content_search_title'     => __( 'SEARCH RESULT', 'nerv-terminal' ),
		'content_404_title'        => __( 'SIGNAL LOST', 'nerv-terminal' ),
		'content_404_text'         => __( 'Requested operation record was not found in this terminal.', 'nerv-terminal' ),
		'content_meta_prefix'      => __( 'ENTRY META', 'nerv-terminal' ),
		'author_card_title'        => __( 'AUTHOR / Operator', 'nerv-terminal' ),
		'author_more_entries'      => __( '> MORE ENTRIES', 'nerv-terminal' ),
		'related_title'            => __( 'RELATED ENTRIES', 'nerv-terminal' ),
		'no_entries'               => __( 'No operation records available.', 'nerv-terminal' ),
		'partners_title'           => __( 'ALLIED ORGANIZATIONS', 'nerv-terminal' ),
		'partners_subtitle'        => __( 'Partner links', 'nerv-terminal' ),
		'partner_visit'            => __( '> VISIT SITE', 'nerv-terminal' ),
		'partner_apply_title'      => __( 'ALLY REQUEST', 'nerv-terminal' ),
		'partner_apply_subtitle'   => __( 'Link exchange request', 'nerv-terminal' ),
		'partner_apply_button'     => __( '> SEND REQUEST', 'nerv-terminal' ),
		'footer_partners_label'    => __( 'ALLIES:', 'nerv-terminal' ),
		'footer_record_enabled'    => '0',
		'footer_record_label'      => __( 'ICP RECORD', 'nerv-terminal' ),
		'footer_record_text'       => '',
		'footer_record_url'        => '',
		'footer_extra_enabled'     => '0',
		'footer_extra_text'        => '',
		'pwa_name'                 => __( 'NERV Terminal', 'nerv-terminal' ),
		'pwa_short_name'           => __( 'NERV', 'nerv-terminal' ),
		'meta_description'         => __( 'NERV Terminal is a WordPress theme for AI-era GEO publishing, portfolio archives, machine-readable feeds, and terminal-style personal sites.', 'nerv-terminal' ),
		'pwa_theme_color'          => '#0A0807',
		'brand_logo_id'            => '0',
		'brand_logo_fit'           => 'contain',
		'brand_logo_focus_x'       => '50',
		'brand_logo_focus_y'       => '50',
		'pwa_icon_id'              => '0',
		'pwa_icon_fit'             => 'cover',
		'pwa_icon_focus_x'         => '50',
		'pwa_icon_focus_y'         => '50',
		'pwa_icon_small_size'      => '192',
		'pwa_icon_large_size'      => '512',
		'pwa_icon_apple_size'      => '180',
		'font_css_url'             => '',
		'font_body_family'         => '"JetBrains Mono", "Noto Sans SC", "Microsoft YaHei", monospace',
		'font_heading_family'      => '"JetBrains Mono", "Noto Sans SC", "Microsoft YaHei", monospace',
		'font_mono_family'         => '"JetBrains Mono", "Noto Sans SC", "Microsoft YaHei", monospace',
	);

	return array_merge( $defaults, nerv_terminal_locale_default_overrides() );
}

function nerv_terminal_locale_default_overrides(): array {
	$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	$lang   = strtolower( str_replace( '-', '_', (string) $locale ) );
	if ( str_starts_with( $lang, 'zh' ) ) {
		return array(
			'clock_timezone'         => __( 'CST', 'nerv-terminal' ),
			'nav_home'               => __( '首页', 'nerv-terminal' ),
			'nav_about'              => __( '关于', 'nerv-terminal' ),
			'nav_projects'           => __( '项目', 'nerv-terminal' ),
			'nav_blog'               => __( '文章', 'nerv-terminal' ),
			'nav_partners'           => __( '合作伙伴', 'nerv-terminal' ),
			'nav_gallery'            => __( '图库', 'nerv-terminal' ),
			'nav_contact'            => __( '联系', 'nerv-terminal' ),
			'latest_title'           => __( '最新项目', 'nerv-terminal' ),
			'view_all'               => __( '> 查看全部', 'nerv-terminal' ),
			'project_prefix'         => __( '项目：', 'nerv-terminal' ),
			'mission_purpose'        => __( '目的：个人网站', 'nerv-terminal' ),
			'mission_state'          => __( '状态：待命', 'nerv-terminal' ),
			'all_systems_sub'        => __( '所有系统运行正常。', 'nerv-terminal' ),
			'server_location'        => __( '中国节点', 'nerv-terminal' ),
			'hero_title'             => __( '欢迎来到我的操作空间', 'nerv-terminal' ),
			'hero_desc_1'            => __( '这是一个用于文章、项目和资料归档的 WordPress 个人站点。', 'nerv-terminal' ),
			'hero_desc_2'            => __( '记录技术、电影、设计，以及面向未来的想法。', 'nerv-terminal' ),
			'hero_button'            => __( '> 关于我：打开', 'nerv-terminal' ),
			'latest_subtitle'        => __( '> 最新行动记录', 'nerv-terminal' ),
			'category_label'         => __( '分类：', 'nerv-terminal' ),
			'detail_button'          => __( '> 查看详情', 'nerv-terminal' ),
			'log_subtitle'           => __( '> 系统日志', 'nerv-terminal' ),
			'pilot_subtitle'         => __( '个人资料', 'nerv-terminal' ),
			'pilot_role'             => __( '独立开发者 / 博主', 'nerv-terminal' ),
			'pilot_bio'              => __( '专注 WordPress 的创作者，记录代码、设计和长期有价值的想法。', 'nerv-terminal' ),
			'pilot_base'             => __( '基地：中国节点', 'nerv-terminal' ),
			'pilot_duty'             => __( '职责：站点运营者', 'nerv-terminal' ),
			'monitor_subtitle'       => __( '系统监控', 'nerv-terminal' ),
			'alert_subtitle'         => __( '警报面板', 'nerv-terminal' ),
			'warning_text'           => __( '检测到外部异常', 'nerv-terminal' ),
			'warning_button'         => __( '> 处理', 'nerv-terminal' ),
			'emergency_jp'           => __( '紧急联系网络', 'nerv-terminal' ),
			'mobile_more_title'      => __( '更多 / 扩展面板', 'nerv-terminal' ),
			'author_card_title'      => __( '作者 / 操作者', 'nerv-terminal' ),
			'content_entry_title'    => __( '文章详情', 'nerv-terminal' ),
			'content_page_title'     => __( '页面详情', 'nerv-terminal' ),
			'content_archive_title'  => __( '归档索引', 'nerv-terminal' ),
			'content_search_title'   => __( '搜索结果', 'nerv-terminal' ),
			'related_title'          => __( '相关文章', 'nerv-terminal' ),
			'partners_title'         => __( '合作伙伴', 'nerv-terminal' ),
			'partners_subtitle'      => __( '合作伙伴链接', 'nerv-terminal' ),
			'partner_visit'          => __( '> 访问站点', 'nerv-terminal' ),
			'partner_apply_title'    => __( '友情链接申请', 'nerv-terminal' ),
			'partner_apply_subtitle' => __( '友情链接申请', 'nerv-terminal' ),
			'partner_apply_button'   => __( '> 发送申请', 'nerv-terminal' ),
			'footer_partners_label'  => __( '伙伴：', 'nerv-terminal' ),
		);
	}

	if ( str_starts_with( $lang, 'ja' ) ) {
		return array(
			'clock_timezone'         => __( 'JST', 'nerv-terminal' ),
			'mission_purpose'        => __( '目的: PERSONAL SITE', 'nerv-terminal' ),
			'mission_state'          => __( '状態: STANDBY', 'nerv-terminal' ),
			'all_systems_sub'        => __( 'すべてのシステムは正常です', 'nerv-terminal' ),
			'server_location'        => __( 'TOKYO-3, JAPAN', 'nerv-terminal' ),
			'hero_title'             => __( 'ようこそ、私の作戦領域へ', 'nerv-terminal' ),
			'hero_desc_1'            => __( 'このサイトは WordPress で構築された個人ポートフォリオです。', 'nerv-terminal' ),
			'hero_desc_2'            => __( '技術、映画、デザイン、そして未来の記録。', 'nerv-terminal' ),
			'hero_button'            => __( '> ABOUT ME: 起動', 'nerv-terminal' ),
			'latest_subtitle'        => __( '> 最新の作戦記録', 'nerv-terminal' ),
			'category_label'         => __( 'カテゴリー:', 'nerv-terminal' ),
			'detail_button'          => __( '> 詳細を表示', 'nerv-terminal' ),
			'log_subtitle'           => __( '> システムログ', 'nerv-terminal' ),
			'pilot_subtitle'         => __( 'パイロット情報', 'nerv-terminal' ),
			'pilot_role'             => __( '個人開発者 / ブロガー', 'nerv-terminal' ),
			'pilot_bio'              => __( 'WordPress を愛するエンジニア。コードとデザインで未来を構築中。', 'nerv-terminal' ),
			'pilot_base'             => __( '拠点: TOKYO-3', 'nerv-terminal' ),
			'pilot_duty'             => __( '役職: パイロット候補生', 'nerv-terminal' ),
			'monitor_subtitle'       => __( 'システム監視', 'nerv-terminal' ),
			'alert_subtitle'         => __( 'アラートパネル', 'nerv-terminal' ),
			'warning_text'           => __( '外部からの不正アクセスを検知', 'nerv-terminal' ),
			'warning_button'         => __( '> 対応する', 'nerv-terminal' ),
			'emergency_jp'           => __( '緊急連絡網', 'nerv-terminal' ),
			'mobile_more_title'      => __( 'MORE / 追加端末', 'nerv-terminal' ),
			'author_card_title'      => __( 'AUTHOR / 操縦者', 'nerv-terminal' ),
			'related_title'          => __( 'RELATED ENTRIES / 関連記録', 'nerv-terminal' ),
			'partners_subtitle'      => __( '同盟組織', 'nerv-terminal' ),
			'partner_apply_subtitle' => __( '相互リンク申請', 'nerv-terminal' ),
		);
	}

	return array();
}

function nerv_terminal_image_fit( string $key ): string {
	$value = nerv_terminal_string( $key );

	return in_array( $value, array( 'contain', 'cover' ), true ) ? $value : 'contain';
}

function nerv_terminal_image_focus( string $x_key, string $y_key ): string {
	$x = min( 100, max( 0, absint( nerv_terminal_string( $x_key ) ) ) );
	$y = min( 100, max( 0, absint( nerv_terminal_string( $y_key ) ) ) );

	return $x . '% ' . $y . '%';
}

function nerv_terminal_string( string $key ): string {
	$defaults = nerv_terminal_default_strings();
	$options  = get_option( 'nerv_terminal_strings', array() );
	$value    = $options[ $key ] ?? $defaults[ $key ] ?? $key;
	if ( isset( $defaults[ $key ] ) && nerv_terminal_is_legacy_default_string( (string) $value ) ) {
		$value = $defaults[ $key ];
	}

	return (string) apply_filters( 'nerv_terminal_string', $value, $key, $defaults );
}

function nerv_terminal_is_legacy_default_string( string $value ): bool {
	$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	if ( str_starts_with( strtolower( str_replace( '-', '_', (string) $locale ) ), 'ja' ) ) {
		return false;
	}

	return in_array(
		$value,
		array(
			'目的: PERSONAL SITE',
			'状態: STANDBY',
			'すべてのシステムは正常です',
			'TOKYO-3, JAPAN',
			'ようこそ、私の作戦領域へ',
			'このサイトは WordPress で構築された個人ポートフォリオです。',
			'技術、映画、デザイン、そして未来の記録。',
			'> ABOUT ME: 起動',
			'> 最新の作戦記録',
			'カテゴリー:',
			'> 詳細を表示',
			'> システムログ',
			'パイロット情報',
			'個人開発者 / ブロガー',
			'WordPress を愛するエンジニア。コードとデザインで未来を構築中。',
			'拠点: TOKYO-3',
			'役職: パイロット候補生',
			'システム監視',
			'アラートパネル',
			'外部からの不正アクセスを検知',
			'> 対応する',
			'緊急連絡網',
			'MORE / 追加端末',
			'AUTHOR / 操縦者',
			'RELATED ENTRIES / 関連記録',
			'同盟組織',
			'相互リンク申請',
			'正常',
			'接続中',
			'稼働中',
			'起動中',
		),
		true
	);
}

function nerv_terminal_media_id( string $key ): int {
	return absint( nerv_terminal_string( $key ) );
}

function nerv_terminal_media_url( string $key, string $size = 'full' ): string {
	$attachment_id = nerv_terminal_media_id( $key );
	if ( ! $attachment_id ) {
		return '';
	}

	$url = wp_get_attachment_image_url( $attachment_id, $size );

	return $url ? (string) $url : '';
}

function nerv_terminal_media_mime_type( string $key ): string {
	$attachment_id = nerv_terminal_media_id( $key );
	if ( ! $attachment_id ) {
		return '';
	}

	return (string) get_post_mime_type( $attachment_id );
}

function nerv_terminal_media_size_string( string $key ): string {
	$attachment_id = nerv_terminal_media_id( $key );
	if ( ! $attachment_id ) {
		return '';
	}

	$metadata = wp_get_attachment_metadata( $attachment_id );
	$width    = absint( $metadata['width'] ?? 0 );
	$height   = absint( $metadata['height'] ?? 0 );

	return $width && $height ? $width . 'x' . $height : '';
}

function nerv_terminal_panel_definitions(): array {
	return array(
		'user'    => array(
			'label'  => __( 'USER', 'nerv-terminal' ),
			'column' => 'left',
			'fields' => array( 'user_title', 'clearance_label', 'clearance_value', 'authorization', 'user_code' ),
		),
		'status'  => array(
			'label'  => __( 'SYSTEM STATUS', 'nerv-terminal' ),
			'column' => 'left',
			'fields' => array( 'system_status_title' ),
		),
		'mission' => array(
			'label'  => __( 'MISSION STATUS', 'nerv-terminal' ),
			'column' => 'left',
			'fields' => array( 'mission_title', 'mission_purpose', 'mission_state', 'all_systems', 'all_systems_sub' ),
		),
		'server'  => array(
			'label'  => __( 'SERVER LOCATION', 'nerv-terminal' ),
			'column' => 'left',
			'fields' => array( 'server_title', 'server_location', 'server_lat', 'server_lon' ),
		),
		'hero'    => array(
			'label'  => __( 'HERO', 'nerv-terminal' ),
			'column' => 'center',
			'fields' => array( 'hero_kicker', 'hero_title', 'hero_desc_1', 'hero_desc_2', 'hero_button' ),
		),
		'latest'  => array(
			'label'  => __( 'LATEST PROJECTS', 'nerv-terminal' ),
			'column' => 'center',
			'fields' => array( 'latest_title', 'latest_subtitle', 'view_all' ),
		),
		'log'     => array(
			'label'  => __( 'SYSTEM LOG', 'nerv-terminal' ),
			'column' => 'center',
			'fields' => array( 'log_title', 'log_subtitle', 'log_level', 'more_logs' ),
		),
		'pilot'   => array(
			'label'  => __( 'PILOT PROFILE', 'nerv-terminal' ),
			'column' => 'right',
			'fields' => array( 'pilot_title', 'pilot_subtitle', 'pilot_role', 'pilot_bio', 'pilot_base', 'pilot_duty' ),
		),
		'monitor' => array(
			'label'  => __( 'SYSTEM MONITOR', 'nerv-terminal' ),
			'column' => 'right',
			'fields' => array( 'monitor_title', 'monitor_subtitle' ),
		),
		'alert'   => array(
			'label'  => __( 'ALERT PANEL', 'nerv-terminal' ),
			'column' => 'right',
			'fields' => array( 'alert_title', 'alert_subtitle', 'warning_level', 'warning_text', 'warning_ip', 'warning_button' ),
		),
	);
}

function nerv_terminal_panel_default_options(): array {
	$options = array();
	$order   = 0;
	foreach ( nerv_terminal_panel_definitions() as $id => $definition ) {
		$options[ $id ] = array(
			'enabled' => true,
			'column'  => (string) ( $definition['column'] ?? 'center' ),
			'order'   => $order,
		);
		if ( 'log' === $id ) {
			$options[ $id ]['source'] = 'decorative';
		} elseif ( 'status' === $id ) {
			$options[ $id ]['source'] = 'decorative';
		} elseif ( 'monitor' === $id ) {
			$options[ $id ]['source'] = 'decorative';
		}
		++$order;
	}

	return $options;
}

function nerv_terminal_panel_options(): array {
	$options = get_option( 'nerv_terminal_panel_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	return nerv_terminal_panel_sanitize_options( $options );
}

function nerv_terminal_panel_sanitize_options( $input ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	$defaults = nerv_terminal_panel_default_options();
	$output   = array();
	foreach ( $defaults as $id => $default ) {
		$incoming = is_array( $input[ $id ] ?? null ) ? $input[ $id ] : array();
		$column   = sanitize_key( (string) ( $incoming['column'] ?? $default['column'] ) );
		if ( ! in_array( $column, array( 'left', 'center', 'right' ), true ) ) {
			$column = (string) $default['column'];
		}
		$output[ $id ] = array(
			'enabled' => array_key_exists( 'enabled', $incoming ) ? ! empty( $incoming['enabled'] ) : (bool) $default['enabled'],
			'column'  => $column,
			'order'   => isset( $incoming['order'] ) ? max( 0, absint( $incoming['order'] ) ) : (int) $default['order'],
		);
		if ( in_array( $id, array( 'log', 'status', 'monitor' ), true ) ) {
			$source = sanitize_key( (string) ( $incoming['source'] ?? $default['source'] ?? 'decorative' ) );
			if ( 'status' === $id ) {
				$allowed_sources = array( 'decorative', 'probes' );
			} elseif ( 'monitor' === $id ) {
				$allowed_sources = array( 'decorative', 'probes', 'crawlers' );
			} else {
				$allowed_sources = array( 'decorative', 'posts' );
			}
			if ( ! in_array( $source, $allowed_sources, true ) ) {
				$source = 'decorative';
			}
			$output[ $id ]['source'] = $source;
		}
	}

	return $output;
}

function nerv_terminal_custom_panel_default_options(): array {
	return array();
}

function nerv_terminal_custom_panel_sanitize_options( $input ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	$output       = array();
	$static_ids   = array_keys( nerv_terminal_panel_definitions() );
	$used_ids     = array();
	$content_types = array( 'richtext', 'html', 'shortcode' );
	foreach ( array_values( $input ) as $index => $panel ) {
		if ( ! is_array( $panel ) || count( $output ) >= 20 ) {
			continue;
		}

		$id = sanitize_key( (string) ( $panel['id'] ?? '' ) );
		if ( '' === $id ) {
			$id = 'custom_' . ( $index + 1 );
		}
		if ( in_array( $id, $static_ids, true ) || in_array( $id, $used_ids, true ) ) {
			$id = 'custom_' . substr( md5( $id . ':' . $index ), 0, 8 );
		}

		$column = sanitize_key( (string) ( $panel['column'] ?? 'center' ) );
		if ( ! in_array( $column, array( 'left', 'center', 'right' ), true ) ) {
			$column = 'center';
		}

		$content_type = sanitize_key( (string) ( $panel['content_type'] ?? $panel['contentType'] ?? 'richtext' ) );
		if ( ! in_array( $content_type, $content_types, true ) ) {
			$content_type = 'richtext';
		}

		$title = sanitize_text_field( (string) ( $panel['title'] ?? '' ) );
		if ( '' === $title ) {
			$title = sprintf( __( 'Custom Panel %d', 'nerv-terminal' ), count( $output ) + 1 );
		}

		$output[] = array(
			'id'           => $id,
			'enabled'      => array_key_exists( 'enabled', $panel ) ? ! empty( $panel['enabled'] ) : true,
			'column'       => $column,
			'order'        => isset( $panel['order'] ) ? max( 0, absint( $panel['order'] ) ) : 10 + count( $output ),
			'title'        => $title,
			'subtitle'     => sanitize_text_field( (string) ( $panel['subtitle'] ?? '' ) ),
			'content'      => wp_kses_post( (string) ( $panel['content'] ?? '' ) ),
			'content_type' => $content_type,
		);
		$used_ids[] = $id;
	}

	return $output;
}

function nerv_terminal_custom_panels(): array {
	$options = get_option( 'nerv_terminal_custom_panels', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	return nerv_terminal_custom_panel_sanitize_options( $options );
}

function nerv_terminal_panel_enabled( string $panel_id ): bool {
	$options = nerv_terminal_panel_options();

	return ! isset( $options[ $panel_id ] ) || ! empty( $options[ $panel_id ]['enabled'] );
}

function nerv_terminal_panels_for_column( string $column ): array {
	$options = nerv_terminal_panel_options();
	$panels  = array();
	foreach ( nerv_terminal_panel_definitions() as $id => $definition ) {
		$panel_options = is_array( $options[ $id ] ?? null ) ? $options[ $id ] : array();
		if ( empty( $panel_options['enabled'] ) || $column !== (string) ( $panel_options['column'] ?? $definition['column'] ?? 'center' ) ) {
			continue;
		}

		$panels[] = array(
			'id'    => $id,
			'order' => absint( $panel_options['order'] ?? 0 ),
		);
	}
	foreach ( nerv_terminal_custom_panels() as $panel ) {
		if ( empty( $panel['enabled'] ) || $column !== (string) ( $panel['column'] ?? 'center' ) ) {
			continue;
		}

		$panels[] = array(
			'id'    => 'custom:' . (string) $panel['id'],
			'order' => absint( $panel['order'] ?? 10 ),
		);
	}

	usort(
		$panels,
		static function ( array $a, array $b ): int {
			if ( $a['order'] === $b['order'] ) {
				return strcmp( (string) $a['id'], (string) $b['id'] );
			}

			return $a['order'] <=> $b['order'];
		}
	);

	return array_column( $panels, 'id' );
}

function nerv_terminal_effect_default_options(): array {
	return array(
		'enabled'         => true,
		'background_grid' => true,
		'scanlines'       => true,
		'panel_glow'      => true,
		'motion'          => true,
		'intensity'       => 65,
		'preset'          => 'balanced',
		'desktop'         => array(
			'enabled'   => true,
			'intensity' => 65,
		),
		'mobile'          => array(
			'enabled'         => true,
			'background_grid' => false,
			'scanlines'       => true,
			'panel_glow'      => false,
			'motion'          => true,
			'intensity'       => 35,
		),
	);
}

function nerv_terminal_effect_presets(): array {
	return array(
		'balanced' => array(
			'label'           => __( 'Balanced terminal', 'nerv-terminal' ),
			'enabled'         => true,
			'background_grid' => true,
			'scanlines'       => true,
			'panel_glow'      => true,
			'motion'          => true,
			'intensity'       => 65,
			'mobile'          => array(
				'enabled'         => true,
				'background_grid' => false,
				'scanlines'       => true,
				'panel_glow'      => false,
				'motion'          => true,
				'intensity'       => 35,
			),
		),
		'calm'     => array(
			'label'           => __( 'Calm reader', 'nerv-terminal' ),
			'enabled'         => true,
			'background_grid' => false,
			'scanlines'       => false,
			'panel_glow'      => false,
			'motion'          => true,
			'intensity'       => 25,
			'mobile'          => array(
				'enabled'         => true,
				'background_grid' => false,
				'scanlines'       => false,
				'panel_glow'      => false,
				'motion'          => true,
				'intensity'       => 15,
			),
		),
		'intense'  => array(
			'label'           => __( 'EVA intense', 'nerv-terminal' ),
			'enabled'         => true,
			'background_grid' => true,
			'scanlines'       => true,
			'panel_glow'      => true,
			'motion'          => true,
			'intensity'       => 90,
			'mobile'          => array(
				'enabled'         => true,
				'background_grid' => false,
				'scanlines'       => true,
				'panel_glow'      => true,
				'motion'          => true,
				'intensity'       => 55,
			),
		),
	);
}

function nerv_terminal_effect_options(): array {
	$options = get_option( 'nerv_terminal_effect_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	return nerv_terminal_effect_sanitize_options( $options );
}

function nerv_terminal_effect_sanitize_options( $input ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	$defaults = nerv_terminal_effect_default_options();
	$output   = array();
	foreach ( array( 'enabled', 'background_grid', 'scanlines', 'panel_glow', 'motion' ) as $key ) {
		$output[ $key ] = array_key_exists( $key, $input ) ? ! empty( $input[ $key ] ) : (bool) $defaults[ $key ];
	}

	$output['intensity'] = min( 100, max( 0, absint( $input['intensity'] ?? $defaults['intensity'] ) ) );
	$preset              = sanitize_key( (string) ( $input['preset'] ?? $defaults['preset'] ) );
	$output['preset']    = array_key_exists( $preset, nerv_terminal_effect_presets() ) ? $preset : (string) $defaults['preset'];
	$output['desktop']   = array(
		'enabled'   => array_key_exists( 'enabled', (array) ( $input['desktop'] ?? array() ) ) ? ! empty( $input['desktop']['enabled'] ) : (bool) $defaults['desktop']['enabled'],
		'intensity' => min( 100, max( 0, absint( $input['desktop']['intensity'] ?? $output['intensity'] ) ) ),
	);
	$output['mobile']    = array();
	foreach ( array( 'enabled', 'background_grid', 'scanlines', 'panel_glow', 'motion' ) as $key ) {
		$output['mobile'][ $key ] = array_key_exists( $key, (array) ( $input['mobile'] ?? array() ) ) ? ! empty( $input['mobile'][ $key ] ) : (bool) $defaults['mobile'][ $key ];
	}
	$output['mobile']['intensity'] = min( 100, max( 0, absint( $input['mobile']['intensity'] ?? $defaults['mobile']['intensity'] ) ) );

	return $output;
}

function nerv_terminal_effect_view_options( string $context = 'desktop' ): array {
	$options = nerv_terminal_effect_options();
	if ( 'mobile' === $context ) {
		$mobile = (array) ( $options['mobile'] ?? array() );
		foreach ( array( 'enabled', 'background_grid', 'scanlines', 'panel_glow', 'motion' ) as $key ) {
			$options[ $key ] = array_key_exists( $key, $mobile ) ? (bool) $mobile[ $key ] : (bool) $options[ $key ];
		}
		$options['intensity'] = absint( $mobile['intensity'] ?? $options['intensity'] );
	} else {
		$desktop = (array) ( $options['desktop'] ?? array() );
		if ( array_key_exists( 'enabled', $desktop ) ) {
			$options['enabled'] = (bool) $desktop['enabled'];
		}
		$options['intensity'] = absint( $desktop['intensity'] ?? $options['intensity'] );
	}

	return $options;
}

function nerv_terminal_appearance_palette_choices(): array {
	return array(
		'amethyst'   => __( '01 Amethyst', 'nerv-terminal' ),
		'azure'      => __( '02 Azure', 'nerv-terminal' ),
		'scarlet'    => __( '03 Scarlet', 'nerv-terminal' ),
		'obsidian'   => __( '04 Obsidian', 'nerv-terminal' ),
		'argent'     => __( '05 Argent', 'nerv-terminal' ),
		'osseous'    => __( '06 Osseous', 'nerv-terminal' ),
		'amber'      => __( '07 Amber', 'nerv-terminal' ),
		'phosphor'   => __( '08 Phosphor', 'nerv-terminal' ),
		'hazard'     => __( '09 Hazard', 'nerv-terminal' ),
		'monochrome' => __( '10 Monochrome', 'nerv-terminal' ),
	);
}

function nerv_terminal_appearance_mode_choices(): array {
	return array(
		'void'  => __( 'Night / Void', 'nerv-terminal' ),
		'paper' => __( 'Day / Paper', 'nerv-terminal' ),
	);
}

function nerv_terminal_appearance_default_options(): array {
	return array(
		'palette' => 'hazard',
		'mode'    => 'void',
	);
}

function nerv_terminal_appearance_options(): array {
	$options = get_option( 'nerv_terminal_appearance_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	return nerv_terminal_appearance_sanitize_options( $options );
}

function nerv_terminal_appearance_sanitize_options( $input ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	$defaults = nerv_terminal_appearance_default_options();
	$palette  = sanitize_key( (string) ( $input['palette'] ?? $defaults['palette'] ) );
	$mode     = sanitize_key( (string) ( $input['mode'] ?? $defaults['mode'] ) );

	return array(
		'palette' => array_key_exists( $palette, nerv_terminal_appearance_palette_choices() ) ? $palette : $defaults['palette'],
		'mode'    => array_key_exists( $mode, nerv_terminal_appearance_mode_choices() ) ? $mode : $defaults['mode'],
	);
}

function nerv_terminal_appearance_theme_attribute(): string {
	$options = nerv_terminal_appearance_options();

	return (string) ( $options['mode'] ?? 'void' );
}

function nerv_terminal_appearance_palette_attribute(): string {
	$options = nerv_terminal_appearance_options();

	return (string) ( $options['palette'] ?? 'hazard' );
}

function nerv_terminal_mobile_default_options(): array {
	return array(
		'enabled'       => true,
		'more_enabled'  => true,
		'more_sections' => array(
			'status'  => true,
			'monitor' => true,
			'alert'   => true,
			'search'  => true,
			'footer'  => true,
		),
		'tabs'          => array(
			array( 'id' => 'home', 'label' => nerv_terminal_string( 'mobile_tab_home' ), 'icon' => 'home', 'url' => home_url( '/' ), 'target' => 'home', 'enabled' => true ),
			array( 'id' => 'blog', 'label' => nerv_terminal_string( 'mobile_tab_blog' ), 'icon' => 'blog', 'url' => home_url( '/blog/' ), 'target' => 'blog', 'enabled' => true ),
			array( 'id' => 'projects', 'label' => nerv_terminal_string( 'mobile_tab_projects' ), 'icon' => 'grid', 'url' => nerv_terminal_post_type_url( 'project' ), 'target' => 'projects', 'enabled' => true ),
			array( 'id' => 'pilot', 'label' => nerv_terminal_string( 'mobile_tab_pilot' ), 'icon' => 'pilot', 'url' => home_url( '/about/' ), 'target' => 'pilot', 'enabled' => true ),
			array( 'id' => 'more', 'label' => nerv_terminal_string( 'mobile_tab_more' ), 'icon' => 'more', 'url' => add_query_arg( 'nerv_more', '1', home_url( '/' ) ), 'target' => 'more', 'enabled' => true ),
		),
	);
}

function nerv_terminal_mobile_icon_choices(): array {
	return array( 'home', 'blog', 'grid', 'pilot', 'more', 'search', 'user', 'status', 'monitor', 'alert', 'contact', 'gallery', 'tools' );
}

function nerv_terminal_mobile_options(): array {
	$options = get_option( 'nerv_terminal_mobile_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	return nerv_terminal_mobile_sanitize_options( $options, false );
}

function nerv_terminal_mobile_sanitize_options( $input, bool $persist_ready = true ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	$defaults = nerv_terminal_mobile_default_options();
	$icons    = nerv_terminal_mobile_icon_choices();
	$options  = array(
		'enabled'       => array_key_exists( 'enabled', $input ) ? ! empty( $input['enabled'] ) : (bool) $defaults['enabled'],
		'more_enabled'  => array_key_exists( 'more_enabled', $input ) ? ! empty( $input['more_enabled'] ) : (bool) $defaults['more_enabled'],
		'more_sections' => array(),
		'tabs'          => array(),
	);

	$sections = is_array( $input['more_sections'] ?? null ) ? $input['more_sections'] : array();
	foreach ( $defaults['more_sections'] as $key => $default_value ) {
		$options['more_sections'][ $key ] = array_key_exists( $key, $sections ) ? ! empty( $sections[ $key ] ) : (bool) $default_value;
	}

	$tabs = is_array( $input['tabs'] ?? null ) ? array_values( $input['tabs'] ) : $defaults['tabs'];
	foreach ( $tabs as $index => $tab ) {
		if ( ! is_array( $tab ) ) {
			continue;
		}

		$fallback = $defaults['tabs'][ $index ] ?? $defaults['tabs'][0];
		$icon     = sanitize_key( (string) ( $tab['icon'] ?? $fallback['icon'] ) );
		$target   = sanitize_key( (string) ( $tab['target'] ?? $fallback['target'] ) );
		$options['tabs'][] = array(
			'id'      => sanitize_key( (string) ( $tab['id'] ?? $fallback['id'] ?? 'tab-' . $index ) ),
			'label'   => sanitize_text_field( (string) ( $tab['label'] ?? $fallback['label'] ) ),
			'icon'    => in_array( $icon, $icons, true ) ? $icon : 'grid',
			'url'     => esc_url_raw( (string) ( $tab['url'] ?? $fallback['url'] ) ),
			'target'  => $target ?: 'custom',
			'enabled' => array_key_exists( 'enabled', $tab ) ? ! empty( $tab['enabled'] ) : true,
		);
	}

	$options['tabs'] = array_slice( $options['tabs'], 0, 5 );
	while ( count( $options['tabs'] ) < 3 ) {
		$options['tabs'][] = $defaults['tabs'][ count( $options['tabs'] ) ];
	}

	if ( $options['more_enabled'] ) {
		$has_more = false;
		foreach ( $options['tabs'] as $tab ) {
			if ( ! empty( $tab['enabled'] ) && 'more' === (string) $tab['target'] ) {
				$has_more = true;
				break;
			}
		}
		if ( ! $has_more && count( $options['tabs'] ) < 5 ) {
			$options['tabs'][] = $defaults['tabs'][4];
		}
	}

	return $options;
}

function nerv_terminal_panel_repeater_default_options(): array {
	$status_values = nerv_terminal_status_default_values();

	return array(
		'status'  => array(
			array( 'label' => __( 'SYSTEM ONLINE', 'nerv-terminal' ), 'value' => $status_values['normal'], 'state' => 'green' ),
			array( 'label' => __( 'NETWORK', 'nerv-terminal' ), 'value' => $status_values['connected'], 'state' => 'green' ),
			array( 'label' => __( 'DATABASE', 'nerv-terminal' ), 'value' => $status_values['connected'], 'state' => 'green' ),
			array( 'label' => __( 'SECURITY', 'nerv-terminal' ), 'value' => $status_values['normal'], 'state' => 'green' ),
			array( 'label' => __( 'WORDPRESS', 'nerv-terminal' ), 'value' => $status_values['running'], 'state' => 'green' ),
			array( 'label' => __( 'THEME ENGINE', 'nerv-terminal' ), 'value' => $status_values['started'], 'state' => 'green' ),
		),
		'monitor' => array(
			array( 'label' => __( 'CPU USAGE', 'nerv-terminal' ), 'value' => '23%', 'level' => 23 ),
			array( 'label' => __( 'MEMORY', 'nerv-terminal' ), 'value' => '45%', 'level' => 45 ),
			array( 'label' => __( 'DISK SPACE', 'nerv-terminal' ), 'value' => '62%', 'level' => 62 ),
			array( 'label' => __( 'NETWORK', 'nerv-terminal' ), 'value' => '12.5 KB/s', 'level' => 38 ),
		),
		'log'     => array(
			array( 'label' => 'INFO', 'value' => __( 'Terminal connection established.', 'nerv-terminal' ) ),
			array( 'label' => 'INFO', 'value' => __( 'WordPress core loaded successfully.', 'nerv-terminal' ) ),
			array( 'label' => 'INFO', 'value' => __( 'Theme "NERV Terminal" initialized.', 'nerv-terminal' ) ),
			array( 'label' => 'INFO', 'value' => __( 'All systems operational.', 'nerv-terminal' ) ),
			array( 'label' => 'INFO', 'value' => __( 'Welcome, Visitor.', 'nerv-terminal' ) ),
		),
	);
}

function nerv_terminal_status_default_values(): array {
	$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	$lang   = strtolower( str_replace( '-', '_', (string) $locale ) );
	if ( str_starts_with( $lang, 'zh' ) ) {
		return array(
			'normal'    => __( '正常', 'nerv-terminal' ),
			'connected' => __( '已连接', 'nerv-terminal' ),
			'running'   => __( '运行中', 'nerv-terminal' ),
			'started'   => __( '已启动', 'nerv-terminal' ),
		);
	}
	if ( str_starts_with( $lang, 'ja' ) ) {
		return array(
			'normal'    => __( '正常', 'nerv-terminal' ),
			'connected' => __( '接続中', 'nerv-terminal' ),
			'running'   => __( '稼働中', 'nerv-terminal' ),
			'started'   => __( '起動中', 'nerv-terminal' ),
		);
	}

	return array(
		'normal'    => __( 'Normal', 'nerv-terminal' ),
		'connected' => __( 'Connected', 'nerv-terminal' ),
		'running'   => __( 'Running', 'nerv-terminal' ),
		'started'   => __( 'Started', 'nerv-terminal' ),
	);
}

function nerv_terminal_panel_repeater_options(): array {
	$options = get_option( 'nerv_terminal_panel_repeater_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	return nerv_terminal_panel_repeater_sanitize_options( $options );
}

function nerv_terminal_panel_repeater_sanitize_options( $input ): array {
	if ( ! is_array( $input ) ) {
		$input = array();
	}

	$defaults = nerv_terminal_panel_repeater_default_options();
	$output   = array();
	foreach ( array( 'status', 'monitor', 'log' ) as $panel_id ) {
		$rows = is_array( $input[ $panel_id ] ?? null ) ? array_values( $input[ $panel_id ] ) : $defaults[ $panel_id ];
		$output[ $panel_id ] = array();
		foreach ( array_slice( $rows, 0, 12 ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = sanitize_text_field( (string) ( $row['label'] ?? '' ) );
			$value = sanitize_text_field( (string) ( $row['value'] ?? '' ) );
			if ( '' === $label && '' === $value ) {
				continue;
			}
			$clean = array(
				'label' => $label,
				'value' => $value,
			);
			if ( 'monitor' === $panel_id ) {
				$clean['level'] = min( 100, max( 0, absint( $row['level'] ?? 0 ) ) );
			}
			if ( 'status' === $panel_id ) {
				$state = sanitize_key( (string) ( $row['state'] ?? 'green' ) );
				if ( ! in_array( $state, array( 'green', 'amber', 'red' ), true ) ) {
					$state = 'green';
				}
				$clean['state'] = $state;
			}
			$output[ $panel_id ][] = $clean;
		}

		if ( ! $output[ $panel_id ] ) {
			$output[ $panel_id ] = $defaults[ $panel_id ];
		}
	}

	return $output;
}

function nerv_terminal_status_rows(): array {
	$options = nerv_terminal_panel_repeater_options();

	return apply_filters( 'nerv_terminal_status_rows', $options['status'] );
}

function nerv_terminal_status_source(): string {
	$options = nerv_terminal_panel_options();
	$source  = (string) ( $options['status']['source'] ?? 'decorative' );

	return in_array( $source, array( 'decorative', 'probes' ), true ) ? $source : 'decorative';
}

function nerv_terminal_status_probe_rows(): array {
	global $wpdb;

	$theme        = wp_get_theme();
	$active_theme = $theme->exists() && 'nerv-terminal' === get_stylesheet();
	$core_active  = defined( 'NERV_CORE_VERSION' );
	$db_ready     = isset( $wpdb ) && ! empty( $wpdb->dbh );
	$uploads      = wp_get_upload_dir();
	$uploads_ok   = empty( $uploads['error'] );
	$markdown_ok  = file_exists( trailingslashit( $uploads['basedir'] ) . 'nerv-terminal/markdown' );

	$rows = array(
		array(
			'label' => __( 'WORDPRESS', 'nerv-terminal' ),
			'value' => get_bloginfo( 'version' ),
			'state' => version_compare( get_bloginfo( 'version' ), '6.7', '>=' ) ? 'green' : 'amber',
		),
		array(
			'label' => __( 'PHP RUNTIME', 'nerv-terminal' ),
			'value' => PHP_VERSION,
			'state' => version_compare( PHP_VERSION, '8.1', '>=' ) ? 'green' : 'amber',
		),
		array(
			'label' => __( 'DATABASE', 'nerv-terminal' ),
			'value' => $db_ready ? __( 'CONNECTED', 'nerv-terminal' ) : __( 'OFFLINE', 'nerv-terminal' ),
			'state' => $db_ready ? 'green' : 'red',
		),
		array(
			'label' => __( 'THEME ENGINE', 'nerv-terminal' ),
			'value' => $active_theme ? $theme->get( 'Version' ) : __( 'INACTIVE', 'nerv-terminal' ),
			'state' => $active_theme ? 'green' : 'red',
		),
		array(
			'label' => __( 'NERV CORE', 'nerv-terminal' ),
			'value' => $core_active ? NERV_CORE_VERSION : __( 'MISSING', 'nerv-terminal' ),
			'state' => $core_active ? 'green' : 'red',
		),
		array(
			'label' => __( 'UPLOADS', 'nerv-terminal' ),
			'value' => $uploads_ok ? __( 'READY', 'nerv-terminal' ) : __( 'BLOCKED', 'nerv-terminal' ),
			'state' => $uploads_ok ? 'green' : 'amber',
		),
		array(
			'label' => __( 'MARKDOWN MIRROR', 'nerv-terminal' ),
			'value' => $markdown_ok ? __( 'READY', 'nerv-terminal' ) : __( 'PENDING', 'nerv-terminal' ),
			'state' => $markdown_ok ? 'green' : 'amber',
		),
	);

	return apply_filters( 'nerv_terminal_status_probe_rows', $rows );
}

function nerv_terminal_monitor_rows(): array {
	$options = nerv_terminal_panel_repeater_options();

	return apply_filters( 'nerv_terminal_monitor_rows', $options['monitor'] );
}

function nerv_terminal_monitor_source(): string {
	$options = nerv_terminal_panel_options();
	$source  = (string) ( $options['monitor']['source'] ?? 'decorative' );

	return in_array( $source, array( 'decorative', 'probes', 'crawlers' ), true ) ? $source : 'decorative';
}

function nerv_terminal_monitor_probe_rows(): array {
	$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
	$memory_peak  = memory_get_peak_usage( true );
	$memory_pct   = $memory_limit > 0 ? min( 100, max( 0, (int) round( ( $memory_peak / $memory_limit ) * 100 ) ) ) : 0;

	$upload_dir = wp_get_upload_dir();
	$disk_total = ! empty( $upload_dir['basedir'] ) ? @disk_total_space( $upload_dir['basedir'] ) : false;
	$disk_free  = ! empty( $upload_dir['basedir'] ) ? @disk_free_space( $upload_dir['basedir'] ) : false;
	$disk_pct   = $disk_total && $disk_free ? min( 100, max( 0, (int) round( ( ( $disk_total - $disk_free ) / $disk_total ) * 100 ) ) ) : 0;

	$posts    = wp_count_posts( 'post' );
	$projects = post_type_exists( 'project' ) ? wp_count_posts( 'project' ) : null;
	$partners = post_type_exists( 'partner' ) ? wp_count_posts( 'partner' ) : null;
	$cache_dir = trailingslashit( $upload_dir['basedir'] ?? '' ) . 'nerv-terminal/markdown';
	$mirror_count = is_dir( $cache_dir ) ? count( glob( trailingslashit( $cache_dir ) . '*.md' ) ?: array() ) : 0;

	$rows = array(
		array(
			'label' => __( 'PHP MEMORY', 'nerv-terminal' ),
			'value' => size_format( $memory_peak ),
			'level' => $memory_pct,
		),
		array(
			'label' => __( 'UPLOAD DISK', 'nerv-terminal' ),
			'value' => $disk_total && $disk_free ? (string) $disk_pct . '%' : __( 'UNKNOWN', 'nerv-terminal' ),
			'level' => $disk_pct,
		),
		array(
			'label' => __( 'POSTS', 'nerv-terminal' ),
			'value' => (string) absint( $posts->publish ?? 0 ),
			'level' => min( 100, absint( $posts->publish ?? 0 ) * 10 ),
		),
		array(
			'label' => __( 'PROJECTS', 'nerv-terminal' ),
			'value' => (string) absint( $projects->publish ?? 0 ),
			'level' => min( 100, absint( $projects->publish ?? 0 ) * 20 ),
		),
		array(
			'label' => __( 'PARTNERS', 'nerv-terminal' ),
			'value' => (string) absint( $partners->publish ?? 0 ),
			'level' => min( 100, absint( $partners->publish ?? 0 ) * 20 ),
		),
		array(
			'label' => __( 'MD MIRRORS', 'nerv-terminal' ),
			'value' => (string) $mirror_count,
			'level' => min( 100, $mirror_count * 16 ),
		),
	);

	return apply_filters( 'nerv_terminal_monitor_probe_rows', $rows );
}

function nerv_terminal_log_rows(): array {
	$options = nerv_terminal_panel_repeater_options();

	return apply_filters( 'nerv_terminal_log_rows', $options['log'] );
}

function nerv_terminal_log_source(): string {
	$options = nerv_terminal_panel_options();
	$source  = (string) ( $options['log']['source'] ?? 'decorative' );

	return in_array( $source, array( 'decorative', 'posts' ), true ) ? $source : 'decorative';
}
