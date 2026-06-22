<?php
/**
 * Local maintenance tools for NERV CONTROL.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_tools_refresh_markdown_cache(): array {
	if ( ! function_exists( 'nerv_core_geo_public_post_types' ) || ! function_exists( 'nerv_core_geo_write_markdown_cache' ) ) {
		return array(
			'written' => 0,
			'failed'  => 0,
			'message' => __( 'Markdown mirror tools are unavailable.', 'nerv-core' ),
		);
	}

	$written = 0;
	$failed  = 0;
	$page    = 1;
	do {
		$posts = get_posts(
			array(
				'post_type'              => nerv_core_geo_public_post_types(),
				'post_status'            => 'publish',
				'posts_per_page'         => 250,
				'paged'                  => $page,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
			)
		);

		foreach ( $posts as $post ) {
			if ( $post instanceof WP_Post && nerv_core_geo_write_markdown_cache( $post ) ) {
				++$written;
			} else {
				++$failed;
			}
		}
		++$page;
	} while ( count( $posts ) >= 250 );

	return array(
		'written' => $written,
		'failed'  => $failed,
		'message' => sprintf(
			/* translators: 1: written cache count, 2: failed cache count. */
			__( 'Markdown mirrors refreshed: %1$d written, %2$d failed.', 'nerv-core' ),
			$written,
			$failed
		),
	);
}

function nerv_core_tools_flush_related_cache(): array {
	if ( ! function_exists( 'nerv_core_related_flush_cache' ) ) {
		return array(
			'message' => __( 'Related-entry cache tools are unavailable.', 'nerv-core' ),
		);
	}

	nerv_core_related_flush_cache();

	return array(
		'message' => __( 'Related-entry cache flushed.', 'nerv-core' ),
	);
}

function nerv_core_tools_refresh_social_covers(): array {
	if ( ! function_exists( 'nerv_core_image_optimizer_queue_social_covers' ) || ! function_exists( 'nerv_core_image_optimizer_run_social_cover_queue' ) || ! function_exists( 'nerv_core_image_optimizer_social_cover_queue_status' ) ) {
		return array(
			'generated' => 0,
			'skipped'   => 0,
			'failed'    => 0,
			'message'   => __( 'Social WebP cover tools are unavailable.', 'nerv-core' ),
		);
	}

	nerv_core_image_optimizer_queue_social_covers( true );
	nerv_core_image_optimizer_run_social_cover_queue();
	$status = nerv_core_image_optimizer_social_cover_queue_status();

	return array(
		'generated' => absint( $status['generated'] ?? 0 ),
		'skipped'   => absint( $status['skipped'] ?? 0 ),
		'failed'    => absint( $status['failed'] ?? 0 ),
		'pending'   => absint( $status['pending'] ?? 0 ),
		'status'    => sanitize_key( (string) ( $status['status'] ?? 'idle' ) ),
		'message'   => sprintf(
			/* translators: 1: generated count, 2: skipped count, 3: failed count, 4: pending count. */
			__( 'Social WebP cover queue started: %1$d generated, %2$d already existed, %3$d failed, %4$d pending.', 'nerv-core' ),
			absint( $status['generated'] ?? 0 ),
			absint( $status['skipped'] ?? 0 ),
			absint( $status['failed'] ?? 0 ),
			absint( $status['pending'] ?? 0 )
		),
	);
}

function nerv_core_tools_refresh_media_webp(): array {
	if ( ! function_exists( 'nerv_core_image_optimizer_queue_media_webp' ) || ! function_exists( 'nerv_core_image_optimizer_run_media_webp_queue' ) || ! function_exists( 'nerv_core_image_optimizer_media_webp_queue_status' ) ) {
		return array(
			'generated' => 0,
			'skipped'   => 0,
			'failed'    => 0,
			'message'   => __( 'Media WebP tools are unavailable.', 'nerv-core' ),
		);
	}

	nerv_core_image_optimizer_queue_media_webp( true );
	nerv_core_image_optimizer_run_media_webp_queue();
	$status = nerv_core_image_optimizer_media_webp_queue_status();

	return array(
		'generated' => absint( $status['generated'] ?? 0 ),
		'skipped'   => absint( $status['skipped'] ?? 0 ),
		'failed'    => absint( $status['failed'] ?? 0 ),
		'pending'   => absint( $status['pending'] ?? 0 ),
		'status'    => sanitize_key( (string) ( $status['status'] ?? 'idle' ) ),
		'lastError' => sanitize_text_field( (string) ( $status['lastError'] ?? '' ) ),
		'message'   => sprintf(
			/* translators: 1: generated count, 2: skipped count, 3: failed count, 4: pending count. */
			__( 'Media WebP queue started: %1$d generated, %2$d already existed, %3$d failed, %4$d pending.', 'nerv-core' ),
			absint( $status['generated'] ?? 0 ),
			absint( $status['skipped'] ?? 0 ),
			absint( $status['failed'] ?? 0 ),
			absint( $status['pending'] ?? 0 )
		),
	);
}

function nerv_core_tools_apply_geo_recommended_defaults(): array {
	$steps = array();

	if ( function_exists( 'nerv_core_indexnow_sanitize_options' ) && function_exists( 'nerv_core_indexnow_options' ) ) {
		$current_indexnow = nerv_core_indexnow_options();
		$indexnow_options = nerv_core_indexnow_sanitize_options(
			array(
				'enabled'  => true,
				'key'      => (string) ( $current_indexnow['key'] ?? '' ),
				'endpoint' => (string) ( $current_indexnow['endpoint'] ?? '' ),
				'dry_run'  => true,
			)
		);
		update_option( 'nerv_core_indexnow_options', $indexnow_options, false );
		$steps[] = array(
			'key'    => 'indexnow',
			'label'  => __( 'IndexNow safety mode', 'nerv-core' ),
			'state'  => 'pass',
			'detail' => __( 'IndexNow is enabled with dry-run safety on for local validation.', 'nerv-core' ),
		);
	} else {
		$steps[] = array(
			'key'    => 'indexnow',
			'label'  => __( 'IndexNow safety mode', 'nerv-core' ),
			'state'  => 'warning',
			'detail' => __( 'IndexNow settings are unavailable.', 'nerv-core' ),
		);
	}

	if ( function_exists( 'nerv_core_geo_crawler_default_options' ) && function_exists( 'nerv_core_geo_crawler_sanitize_options' ) ) {
		update_option( 'nerv_core_geo_crawler_options', nerv_core_geo_crawler_sanitize_options( nerv_core_geo_crawler_default_options() ), false );
		$steps[] = array(
			'key'    => 'crawlers',
			'label'  => __( 'AI crawler visibility', 'nerv-core' ),
			'state'  => 'pass',
			'detail' => __( 'AI crawler monitoring and the default bot policy are enabled.', 'nerv-core' ),
		);
	} else {
		$steps[] = array(
			'key'    => 'crawlers',
			'label'  => __( 'AI crawler visibility', 'nerv-core' ),
			'state'  => 'warning',
			'detail' => __( 'AI crawler settings are unavailable.', 'nerv-core' ),
		);
	}

	$policy_id = 0;
	if ( function_exists( 'nerv_core_ai_policy_generate_page' ) ) {
		$policy_id = nerv_core_ai_policy_generate_page();
	}
	$steps[] = array(
		'key'    => 'policy',
		'label'  => __( 'AI usage policy', 'nerv-core' ),
		'state'  => $policy_id ? 'pass' : 'warning',
		'detail' => $policy_id ? __( 'AI usage policy page is published and linked from machine-readable resources.', 'nerv-core' ) : __( 'AI usage policy page could not be generated.', 'nerv-core' ),
	);

	$markdown = function_exists( 'nerv_core_tools_refresh_markdown_cache' ) ? nerv_core_tools_refresh_markdown_cache() : array(
		'written' => 0,
		'failed'  => 0,
		'message' => __( 'Markdown mirror tools are unavailable.', 'nerv-core' ),
	);
	$steps[] = array(
		'key'    => 'markdown',
		'label'  => __( 'Markdown mirrors', 'nerv-core' ),
		'state'  => empty( $markdown['failed'] ) ? 'pass' : 'warning',
		'detail' => sanitize_text_field( (string) ( $markdown['message'] ?? '' ) ),
	);

	$warnings = 0;
	foreach ( $steps as $step ) {
		if ( 'pass' !== (string) ( $step['state'] ?? '' ) ) {
			++$warnings;
		}
	}

	return array(
		'message'  => $warnings
			? sprintf(
				/* translators: %d: warning count. */
				__( 'Recommended GEO setup completed with %d warnings.', 'nerv-core' ),
				$warnings
			)
			: __( 'Recommended GEO setup completed.', 'nerv-core' ),
		'status'   => $warnings ? 'warning' : 'pass',
		'warnings' => $warnings,
		'steps'    => $steps,
		'markdown' => $markdown,
	);
}

function nerv_core_tools_audit_item( string $key, string $label, string $state, string $detail ): array {
	return array(
		'key'    => sanitize_key( $key ),
		'label'  => sanitize_text_field( $label ),
		'state'  => in_array( $state, array( 'pass', 'warning', 'fail' ), true ) ? $state : 'warning',
		'detail' => sanitize_text_field( $detail ),
	);
}

function nerv_core_tools_theme_header_data( string $theme_dir ): array {
	$style_file = trailingslashit( $theme_dir ) . 'style.css';
	if ( ! is_file( $style_file ) ) {
		return array();
	}

	if ( function_exists( 'get_file_data' ) ) {
		return get_file_data(
			$style_file,
			array(
				'name'             => 'Theme Name',
				'version'          => 'Version',
				'text_domain'      => 'Text Domain',
				'requires_at_least'=> 'Requires at least',
				'requires_php'     => 'Requires PHP',
				'license'          => 'License',
			),
			'theme'
		);
	}

	return array();
}

function nerv_core_tools_official_theme_check_status(): array {
	$theme_check_loaded = class_exists( 'Theme_Check' ) || function_exists( 'themecheck_main' );
	$wp_cli_runtime     = defined( 'WP_CLI' ) && WP_CLI;

	if ( $theme_check_loaded ) {
		$state  = 'pass';
		$detail = __( 'Theme Check plugin code appears to be loaded in this WordPress runtime.', 'nerv-core' );
	} elseif ( $wp_cli_runtime ) {
		$state  = 'pass';
		$detail = __( 'WP-CLI runtime is active; run the official Theme Check command from the shell for the full rule set.', 'nerv-core' );
	} else {
		$state  = 'warning';
		$detail = __( 'Official Theme Check is not loaded here. This panel is running the built-in release audit only.', 'nerv-core' );
	}

	return array(
		'available' => $theme_check_loaded || $wp_cli_runtime,
		'state'     => $state,
		'detail'    => $detail,
	);
}

function nerv_core_tools_run_theme_release_audit(): array {
	$theme_dir     = function_exists( 'get_stylesheet_directory' ) ? get_stylesheet_directory() : '';
	$headers       = nerv_core_tools_theme_header_data( $theme_dir );
	$official      = nerv_core_tools_official_theme_check_status();
	$required      = array(
		'style.css',
		'theme.json',
		'functions.php',
		'templates/index.html',
		'templates/front-page.html',
		'parts/header.html',
		'parts/footer.html',
	);
	$missing_files = array();
	foreach ( $required as $relative_path ) {
		if ( ! $theme_dir || ! is_file( trailingslashit( $theme_dir ) . $relative_path ) ) {
			$missing_files[] = $relative_path;
		}
	}

	$checks   = array();
	$checks[] = nerv_core_tools_audit_item(
		'official_theme_check',
		__( 'Official Theme Check runner', 'nerv-core' ),
		(string) $official['state'],
		(string) $official['detail']
	);
	$checks[] = nerv_core_tools_audit_item(
		'required_theme_files',
		__( 'Required block-theme files', 'nerv-core' ),
		empty( $missing_files ) ? 'pass' : 'fail',
		empty( $missing_files )
			? __( 'style.css, theme.json, functions.php, templates, and template parts are present.', 'nerv-core' )
			: sprintf(
				/* translators: %s: comma-separated missing file list. */
				__( 'Missing required files: %s.', 'nerv-core' ),
				implode( ', ', $missing_files )
			)
	);

	$required_headers = array(
		'name'             => 'Theme Name',
		'version'          => 'Version',
		'text_domain'      => 'Text Domain',
		'requires_at_least'=> 'Requires at least',
		'requires_php'     => 'Requires PHP',
		'license'          => 'License',
	);
	$missing_headers = array();
	foreach ( $required_headers as $key => $label ) {
		if ( '' === trim( (string) ( $headers[ $key ] ?? '' ) ) ) {
			$missing_headers[] = $label;
		}
	}
	$checks[] = nerv_core_tools_audit_item(
		'style_headers',
		__( 'style.css release headers', 'nerv-core' ),
		empty( $missing_headers ) ? 'pass' : 'fail',
		empty( $missing_headers )
			? __( 'Required theme headers are present.', 'nerv-core' )
			: sprintf(
				/* translators: %s: comma-separated missing header list. */
				__( 'Missing style.css headers: %s.', 'nerv-core' ),
				implode( ', ', $missing_headers )
			)
	);
	$checks[] = nerv_core_tools_audit_item(
		'text_domain',
		__( 'Theme text domain', 'nerv-core' ),
		'nerv-terminal' === (string) ( $headers['text_domain'] ?? '' ) ? 'pass' : 'fail',
		sprintf(
			/* translators: %s: detected text domain. */
			__( 'Detected text domain: %s.', 'nerv-core' ),
			(string) ( $headers['text_domain'] ?? __( 'missing', 'nerv-core' ) )
		)
	);

	$theme_json_file = trailingslashit( $theme_dir ) . 'theme.json';
	$theme_json_ok   = false;
	if ( is_file( $theme_json_file ) ) {
		$decoded       = json_decode( (string) file_get_contents( $theme_json_file ), true );
		$theme_json_ok = is_array( $decoded ) && JSON_ERROR_NONE === json_last_error();
	}
	$checks[] = nerv_core_tools_audit_item(
		'theme_json',
		__( 'theme.json parse', 'nerv-core' ),
		$theme_json_ok ? 'pass' : 'fail',
		$theme_json_ok ? __( 'theme.json parsed successfully.', 'nerv-core' ) : __( 'theme.json is missing or invalid JSON.', 'nerv-core' )
	);
	$checks[] = nerv_core_tools_audit_item(
		'minimum_versions',
		__( 'Minimum runtime versions', 'nerv-core' ),
		version_compare( (string) ( $headers['requires_php'] ?? '0' ), '8.1', '>=' ) && version_compare( (string) ( $headers['requires_at_least'] ?? '0' ), '6.7', '>=' ) ? 'pass' : 'warning',
		sprintf(
			/* translators: 1: WordPress version requirement, 2: PHP version requirement. */
			__( 'Requires WordPress %1$s and PHP %2$s.', 'nerv-core' ),
			(string) ( $headers['requires_at_least'] ?? __( 'missing', 'nerv-core' ) ),
			(string) ( $headers['requires_php'] ?? __( 'missing', 'nerv-core' ) )
		)
	);
	$checks[] = nerv_core_tools_audit_item(
		'license',
		__( 'GPL-compatible license header', 'nerv-core' ),
		false !== stripos( (string) ( $headers['license'] ?? '' ), 'GPL' ) || false !== stripos( (string) ( $headers['license'] ?? '' ), 'General Public License' ) ? 'pass' : 'warning',
		sprintf(
			/* translators: %s: detected license header. */
			__( 'Detected license: %s.', 'nerv-core' ),
			(string) ( $headers['license'] ?? __( 'missing', 'nerv-core' ) )
		)
	);

	$summary = array( 'pass' => 0, 'warning' => 0, 'fail' => 0 );
	foreach ( $checks as $check ) {
		++$summary[ $check['state'] ];
	}
	$status = $summary['fail'] > 0 ? 'fail' : ( $summary['warning'] > 0 ? 'warning' : 'pass' );

	return array(
		'message'     => sprintf(
			/* translators: 1: pass count, 2: warning count, 3: failure count. */
			__( 'Release audit completed: %1$d pass, %2$d warnings, %3$d failures.', 'nerv-core' ),
			$summary['pass'],
			$summary['warning'],
			$summary['fail']
		),
		'status'      => $status,
		'summary'     => $summary,
		'checks'      => $checks,
		'official'    => $official,
		'generatedAt' => current_time( 'mysql' ),
	);
}

function nerv_core_tools_release_package_status( string $root ): array {
	$dist_dir = trailingslashit( $root ) . 'dist';
	$build_script = trailingslashit( $root ) . 'build.sh';
	$targets  = array(
		'bundle' => array(
			'label'   => __( 'Bundle package', 'nerv-core' ),
			'pattern' => 'nerv-terminal-bundle-*.zip',
		),
		'theme'  => array(
			'label'   => __( 'Theme package', 'nerv-core' ),
			'pattern' => 'nerv-terminal-theme-*.zip',
		),
		'plugin' => array(
			'label'   => __( 'Core plugin package', 'nerv-core' ),
			'pattern' => 'nerv-core-plugin-*.zip',
		),
	);
	$packages = array();
	$complete = true;
	$available = is_file( $build_script ) || is_dir( $dist_dir );

	foreach ( $targets as $key => $target ) {
		if ( ! $available ) {
			$complete = false;
			$packages[] = array(
				'key'      => $key,
				'label'    => (string) $target['label'],
				'state'    => 'unavailable',
				'file'     => '',
				'size'     => '',
				'modified' => '',
			);
			continue;
		}

		$matches = is_dir( $dist_dir ) ? glob( trailingslashit( $dist_dir ) . $target['pattern'] ) : array();
		$matches = is_array( $matches ) ? $matches : array();
		usort(
			$matches,
			static function ( string $a, string $b ): int {
				return (int) filemtime( $b ) <=> (int) filemtime( $a );
			}
		);

		$file = $matches[0] ?? '';
		if ( '' === $file || ! is_file( $file ) ) {
			$complete = false;
			$packages[] = array(
				'key'      => $key,
				'label'    => (string) $target['label'],
				'state'    => 'missing',
				'file'     => '',
				'size'     => '',
				'modified' => '',
			);
			continue;
		}

		$packages[] = array(
			'key'      => $key,
			'label'    => (string) $target['label'],
			'state'    => 'ready',
			'file'     => basename( $file ),
			'size'     => size_format( (int) filesize( $file ), 1 ),
			'modified' => wp_date( 'Y-m-d H:i', (int) filemtime( $file ) ),
		);
	}

	return array(
		'distDir'  => sanitize_text_field( $dist_dir ),
		'status'   => $complete ? 'ready' : ( $available ? 'missing' : 'unavailable' ),
		'complete' => $complete,
		'packages' => $packages,
	);
}

function nerv_core_tools_demo_author_id(): int {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		$users = get_users(
			array(
				'role__in' => array( 'administrator', 'editor', 'author' ),
				'number'   => 1,
				'orderby'  => 'ID',
				'order'    => 'ASC',
			)
		);
		$user_id = $users ? (int) $users[0]->ID : 0;
	}

	return $user_id;
}

function nerv_core_tools_seed_demo_author_profile(): int {
	$user_id = nerv_core_tools_demo_author_id();
	if ( ! $user_id ) {
		return 0;
	}

	wp_update_user(
		array(
			'ID'          => $user_id,
			'description' => 'NERV Terminal operator focused on WordPress, GEO publishing, and structured AI-readable content.',
		)
	);
	update_user_meta( $user_id, 'nerv_author_title', 'GEO Systems Operator' );
	update_user_meta( $user_id, 'nerv_author_social_github', 'https://github.com/cat9999sss' );
	update_user_meta( $user_id, 'nerv_author_social_x', 'https://x.com/dashenwang' );
	update_user_meta( $user_id, 'nerv_author_social_website', 'https://dashen.wang' );

	return $user_id;
}

function nerv_core_tools_seed_secondary_demo_author_profile(): int {
	$user = get_user_by( 'login', 'magi_operator' );
	if ( ! $user instanceof WP_User ) {
		$user_id = wp_insert_user(
			array(
				'user_login'   => 'magi_operator',
				'user_pass'    => wp_generate_password( 24, true, true ),
				'user_email'   => 'magi.operator@example.test',
				'display_name' => 'MAGI Operator',
				'nickname'     => 'MAGI Operator',
				'role'         => 'author',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return 0;
		}
	} else {
		$user_id = (int) $user->ID;
		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => 'MAGI Operator',
				'nickname'     => 'MAGI Operator',
				'role'         => 'author',
			)
		);
	}

	wp_update_user(
		array(
			'ID'          => $user_id,
			'description' => 'Secondary demo author responsible for interface telemetry, mobile shell notes, and MAGI review trails.',
		)
	);
	update_user_meta( $user_id, 'nerv_author_title', 'Interface Telemetry Pilot' );
	update_user_meta( $user_id, 'nerv_author_social_github', 'https://github.com/magi-operator' );
	update_user_meta( $user_id, 'nerv_author_social_linkedin', 'https://www.linkedin.com/in/magi-operator' );
	update_user_meta( $user_id, 'nerv_author_social_website', 'https://example.test/magi-operator' );
	delete_user_meta( $user_id, 'nerv_author_social_x' );
	delete_user_meta( $user_id, 'nerv_author_social_youtube' );

	return $user_id;
}

function nerv_core_tools_seed_demo_post( string $type, string $title, string $content, array $meta = array(), int $author_id = 0, ?string &$status = null ): int {
	$existing = get_page_by_title( $title, OBJECT, $type );
	if ( $existing instanceof WP_Post ) {
		$post_id = (int) $existing->ID;
		$status  = 'updated';
		$args    = array(
			'ID'           => $post_id,
			'post_excerpt' => wp_trim_words( wp_strip_all_tags( $content ), 24 ),
			'post_content' => $content,
		);
		if ( $author_id ) {
			$args['post_author'] = $author_id;
		}
		wp_update_post( $args );
	} else {
		$status = 'created';
		$args = array(
			'post_type'    => $type,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_excerpt' => wp_trim_words( wp_strip_all_tags( $content ), 24 ),
			'post_content' => $content,
		);
		if ( $author_id ) {
			$args['post_author'] = $author_id;
		}
		$post_id = wp_insert_post( $args, true );
		if ( is_wp_error( $post_id ) ) {
			$status = 'failed';
			return 0;
		}
	}

	foreach ( $meta as $key => $value ) {
		update_post_meta( $post_id, $key, $value );
	}

	return (int) $post_id;
}

function nerv_core_tools_import_demo_content(): array {
	$author_id           = nerv_core_tools_seed_demo_author_profile();
	$secondary_author_id = nerv_core_tools_seed_secondary_demo_author_profile();
	$summary             = array(
		'created' => 0,
		'updated' => 0,
		'failed'  => 0,
	);
	$counts              = array(
		'projects' => array( 'created' => 0, 'updated' => 0, 'failed' => 0, 'total' => 0 ),
		'posts'    => array( 'created' => 0, 'updated' => 0, 'failed' => 0, 'total' => 0 ),
		'partners' => array( 'created' => 0, 'updated' => 0, 'failed' => 0, 'total' => 0 ),
	);
	$steps               = array();
	$record_result       = static function ( string $group, int $post_id, string $status ) use ( &$counts, &$summary ): void {
		if ( ! isset( $counts[ $group ] ) ) {
			return;
		}
		if ( ! in_array( $status, array( 'created', 'updated', 'failed' ), true ) ) {
			$status = $post_id ? 'updated' : 'failed';
		}
		++$counts[ $group ][ $status ];
		++$counts[ $group ]['total'];
		++$summary[ $status ];
	};
	$step_state          = static function ( int $failed ): string {
		return $failed > 0 ? 'warning' : 'pass';
	};

	$steps[] = array(
		'key'    => 'authors',
		'label'  => __( 'Demo authors', 'nerv-core' ),
		'state'  => $author_id ? 'pass' : 'warning',
		'detail' => $author_id ? __( 'Primary and secondary demo author profiles were prepared.', 'nerv-core' ) : __( 'No administrator/editor/author account was available for demo ownership.', 'nerv-core' ),
	);

	$projects = array(
		array( 'EVA-01', 'A controlled WordPress operation record for the first NERV Terminal demo project.', 'Web Design' ),
		array( 'TOKYO-3', 'A city-scale archive panel for CMS experiments, dashboard flow, and responsive shell testing.', 'CMS / WordPress' ),
		array( 'MAGI SYSTEM', 'Plugin-oriented diagnostics for data, services, and future GEO automation modules.', 'Plugin' ),
	);

	foreach ( $projects as $project ) {
		$status  = '';
		$post_id = nerv_core_tools_seed_demo_post(
			'project',
			$project[0],
			'<p>' . esc_html( $project[1] ) . '</p><p><strong>Category:</strong> ' . esc_html( $project[2] ) . '</p>',
			array( '_nerv_subtitle' => $project[2] ),
			$author_id,
			$status
		);
		$record_result( 'projects', $post_id, $status );
	}
	$steps[] = array(
		'key'    => 'projects',
		'label'  => __( 'Demo projects', 'nerv-core' ),
		'state'  => $step_state( $counts['projects']['failed'] ),
		'detail' => sprintf(
			/* translators: 1: created count, 2: updated count, 3: failed count. */
			__( '%1$d created, %2$d updated, %3$d failed.', 'nerv-core' ),
			$counts['projects']['created'],
			$counts['projects']['updated'],
			$counts['projects']['failed']
		),
	);

	$cat = term_exists( 'Operations', 'category' );
	if ( ! $cat ) {
		$cat = wp_insert_term( 'Operations', 'category' );
	}
	$cat_id = is_wp_error( $cat ) ? 0 : (int) ( is_array( $cat ) ? $cat['term_id'] : $cat );

	$tag_total = 0;
	foreach ( array( 'geo', 'wordpress', 'terminal' ) as $tag_name ) {
		if ( ! term_exists( $tag_name, 'post_tag' ) ) {
			$term = wp_insert_term( $tag_name, 'post_tag' );
			if ( ! is_wp_error( $term ) ) {
				++$tag_total;
			}
		} else {
			++$tag_total;
		}
	}
	$steps[] = array(
		'key'    => 'taxonomies',
		'label'  => __( 'Demo taxonomy', 'nerv-core' ),
		'state'  => $cat_id && $tag_total >= 3 ? 'pass' : 'warning',
		'detail' => sprintf(
			/* translators: 1: category id, 2: tag count. */
			__( 'Operations category ID %1$d; %2$d tags ready.', 'nerv-core' ),
			$cat_id,
			$tag_total
		),
	);

	$posts = array(
		array(
			'NERV GEO Protocol',
			'<!-- wp:nerv-core/key-points {"points":["Markdown mirrors give AI crawlers a clean canonical reading path.","llms.txt exposes the site map in a format language models can consume quickly.","Structured blocks keep human pages and machine-readable metadata synchronized."]} /-->' .
			"\n\n" .
			'<p>A field note about markdown mirrors, structured feeds, and terminal-flavored publishing.</p>' .
			"\n\n" .
			'<!-- wp:nerv-core/faq {"items":[{"question":"Why does NERV Terminal publish Markdown mirrors?","answer":"Markdown mirrors give AI systems a compact, canonical version of each article without removing the human-facing WordPress page."},{"question":"How does the FAQ block help GEO?","answer":"The FAQ block turns clear questions and answers into FAQPage JSON-LD, which makes the article easier for search and AI answer engines to understand."}]} /-->',
			array( 'geo', 'wordpress' ),
			$author_id,
		),
		array(
			'Terminal Interface Notes',
			'<p>Responsive app shell observations for desktop dashboards and mobile bottom navigation.</p>',
			array( 'terminal', 'wordpress' ),
			$secondary_author_id ?: $author_id,
		),
	);

	foreach ( $posts as $demo_post ) {
		$status  = '';
		$post_id = nerv_core_tools_seed_demo_post(
			'post',
			$demo_post[0],
			$demo_post[1],
			array( '_nerv_subtitle' => 'Demo operation note' ),
			(int) $demo_post[3],
			$status
		);
		if ( $post_id ) {
			if ( $cat_id ) {
				wp_set_post_categories( $post_id, array( $cat_id ), false );
			}
			wp_set_post_terms( $post_id, $demo_post[2], 'post_tag', false );
		}
		$record_result( 'posts', $post_id, $status );
	}
	$steps[] = array(
		'key'    => 'posts',
		'label'  => __( 'Demo posts', 'nerv-core' ),
		'state'  => $step_state( $counts['posts']['failed'] ),
		'detail' => sprintf(
			/* translators: 1: created count, 2: updated count, 3: failed count. */
			__( '%1$d created, %2$d updated, %3$d failed.', 'nerv-core' ),
			$counts['posts']['created'],
			$counts['posts']['updated'],
			$counts['posts']['failed']
		),
	);

	$partners = array(
		array( 'OpenAI Research', 'AI systems and applied research signal source.', 'https://openai.com', 'follow', '1' ),
		array( 'WordPress.org', 'Publishing engine and open web infrastructure.', 'https://wordpress.org', 'follow', '1' ),
		array( 'Dashen Lab', 'Personal development and theme validation node.', 'https://dashen.wang', 'follow', '1' ),
		array( 'Offline Test Node', 'Intentionally reserved for future health-check red state testing.', 'https://127.0.0.1:1', 'nofollow', '0' ),
	);

	foreach ( $partners as $partner ) {
		$status  = '';
		$post_id = nerv_core_tools_seed_demo_post(
			'partner',
			$partner[0],
			'<p>' . esc_html( $partner[1] ) . '</p>',
			array(
				'_nerv_subtitle'         => $partner[1],
				'_nerv_partner_url'      => $partner[2],
				'_nerv_partner_rel'      => $partner[3],
				'_nerv_partner_featured' => $partner[4],
			),
			$author_id,
			$status
		);
		$record_result( 'partners', $post_id, $status );
	}
	$steps[] = array(
		'key'    => 'partners',
		'label'  => __( 'Demo partners', 'nerv-core' ),
		'state'  => $step_state( $counts['partners']['failed'] ),
		'detail' => sprintf(
			/* translators: 1: created count, 2: updated count, 3: failed count. */
			__( '%1$d created, %2$d updated, %3$d failed.', 'nerv-core' ),
			$counts['partners']['created'],
			$counts['partners']['updated'],
			$counts['partners']['failed']
		),
	);

	flush_rewrite_rules( false );

	$markdown_result = array();
	if ( function_exists( 'nerv_core_tools_refresh_markdown_cache' ) ) {
		$markdown_result = nerv_core_tools_refresh_markdown_cache();
		$steps[] = array(
			'key'    => 'markdown',
			'label'  => __( 'Markdown mirrors', 'nerv-core' ),
			'state'  => empty( $markdown_result['failed'] ) ? 'pass' : 'warning',
			'detail' => sanitize_text_field( (string) ( $markdown_result['message'] ?? '' ) ),
		);
	}

	return array(
		'message'  => sprintf(
			/* translators: 1: created count, 2: updated count, 3: failed count. */
			__( 'Demo import completed: %1$d created, %2$d updated, %3$d failed.', 'nerv-core' ),
			$summary['created'],
			$summary['updated'],
			$summary['failed']
		),
		'status'   => $summary['failed'] > 0 ? 'warning' : 'pass',
		'summary'  => $summary,
		'counts'   => $counts,
		'steps'    => $steps,
		'markdown' => $markdown_result,
	);
}

function nerv_core_tools_preset_registry(): array {
	return array(
		'nerv_terminal_strings'                => array( 'sanitize' => 'nerv_core_tools_sanitize_terminal_strings_export' ),
		'nerv_terminal_panel_options'          => array( 'sanitize' => 'nerv_terminal_panel_sanitize_options' ),
		'nerv_terminal_panel_repeater_options' => array( 'sanitize' => 'nerv_terminal_panel_repeater_sanitize_options' ),
		'nerv_terminal_mobile_options'         => array( 'sanitize' => 'nerv_terminal_mobile_sanitize_options' ),
		'nerv_terminal_effect_options'         => array( 'sanitize' => 'nerv_terminal_effect_sanitize_options' ),
		'nerv_core_social_options'             => array( 'sanitize' => 'nerv_core_social_sanitize_options' ),
		'nerv_core_related_options'            => array( 'sanitize' => 'nerv_core_related_sanitize_options' ),
		'nerv_core_geo_score_weights'          => array( 'sanitize' => 'nerv_core_geo_score_sanitize_weights' ),
		'nerv_core_geo_crawler_options'        => array( 'sanitize' => 'nerv_core_geo_crawler_sanitize_options' ),
		'nerv_core_indexnow_options'           => array( 'sanitize' => 'nerv_core_indexnow_sanitize_options' ),
		'nerv_core_partner_display_options'    => array( 'sanitize' => 'nerv_core_partner_display_sanitize_options' ),
		'nerv_core_partner_health_options'     => array( 'sanitize' => 'nerv_core_partner_health_sanitize_options' ),
	);
}

function nerv_core_tools_sanitize_terminal_strings_export( $input ): array {
	if ( ! is_array( $input ) ) {
		return array();
	}

	$defaults = function_exists( 'nerv_terminal_default_strings' ) ? nerv_terminal_default_strings() : array();
	$output   = array();
	foreach ( $defaults as $key => $default ) {
		if ( ! array_key_exists( $key, $input ) ) {
			continue;
		}
		if ( in_array( $key, array( 'brand_logo_id', 'pwa_icon_id' ), true ) ) {
			$output[ $key ] = (string) absint( $input[ $key ] );
		} elseif ( in_array( $key, array( 'brand_logo_focus_x', 'brand_logo_focus_y', 'pwa_icon_focus_x', 'pwa_icon_focus_y' ), true ) ) {
			$output[ $key ] = (string) min( 100, max( 0, absint( $input[ $key ] ) ) );
		} elseif ( in_array( $key, array( 'pwa_icon_small_size', 'pwa_icon_large_size', 'pwa_icon_apple_size' ), true ) ) {
			$output[ $key ] = (string) min( 1024, max( 64, absint( $input[ $key ] ) ) );
		} elseif ( in_array( $key, array( 'brand_logo_fit', 'pwa_icon_fit' ), true ) ) {
			$value = sanitize_key( (string) $input[ $key ] );
			$output[ $key ] = in_array( $value, array( 'contain', 'cover' ), true ) ? $value : (string) $default;
		} elseif ( 'pwa_theme_color' === $key ) {
			$output[ $key ] = sanitize_hex_color( (string) $input[ $key ] ) ?: (string) $default;
		} else {
			$output[ $key ] = sanitize_text_field( wp_unslash( (string) $input[ $key ] ) );
		}
	}

	return $output;
}

function nerv_core_tools_export_settings_preset(): array {
	$options = array();
	foreach ( nerv_core_tools_preset_registry() as $option_name => $config ) {
		$value = get_option( $option_name, null );
		if ( null === $value ) {
			continue;
		}
		$sanitize = (string) ( $config['sanitize'] ?? '' );
		if ( function_exists( $sanitize ) ) {
			$value = call_user_func( $sanitize, $value );
		}
		$options[ $option_name ] = $value;
	}

	return array(
		'message' => __( 'Settings preset exported.', 'nerv-core' ),
		'preset'  => array(
			'schema'    => 'nerv-terminal-settings-preset/v1',
			'site'      => get_bloginfo( 'name' ),
			'createdAt' => gmdate( 'c' ),
			'versions'  => array(
				'theme' => defined( 'NERV_TERMINAL_VERSION' ) ? NERV_TERMINAL_VERSION : '',
				'core'  => defined( 'NERV_CORE_VERSION' ) ? NERV_CORE_VERSION : '',
			),
			'options'   => $options,
		),
	);
}

function nerv_core_tools_import_settings_preset( $preset ): array {
	if ( is_string( $preset ) ) {
		$decoded = json_decode( $preset, true );
		$preset  = is_array( $decoded ) ? $decoded : array();
	}
	if ( ! is_array( $preset ) || 'nerv-terminal-settings-preset/v1' !== (string) ( $preset['schema'] ?? '' ) || ! is_array( $preset['options'] ?? null ) ) {
		return array(
			'message' => __( 'Settings preset import failed: invalid preset JSON.', 'nerv-core' ),
			'imported'=> array(),
			'failed'  => array( 'preset' ),
		);
	}

	$registry = nerv_core_tools_preset_registry();
	$imported = array();
	$failed   = array();
	foreach ( $preset['options'] as $option_name => $value ) {
		$option_name = sanitize_key( (string) $option_name );
		if ( ! isset( $registry[ $option_name ] ) ) {
			$failed[] = $option_name;
			continue;
		}
		$sanitize = (string) ( $registry[ $option_name ]['sanitize'] ?? '' );
		if ( function_exists( $sanitize ) ) {
			$value = call_user_func( $sanitize, $value );
		}
		update_option( $option_name, $value, false );
		$imported[] = $option_name;
	}

	return array(
		'message' => sprintf(
			/* translators: %d: imported option count. */
			__( 'Settings preset imported: %d option groups updated.', 'nerv-core' ),
			count( $imported )
		),
		'imported'=> $imported,
		'failed'  => $failed,
	);
}
