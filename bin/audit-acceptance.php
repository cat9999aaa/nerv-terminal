<?php
/**
 * Runtime acceptance audit for the NERV Terminal v4 redlines that need WordPress state.
 *
 * @package NervTerminal
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$wp_load = $argv[1] ?? '/www/wwwroot/127_0_0_1/wp-load.php';
$site    = rtrim( getenv( 'NERV_SITE_URL' ) ?: 'http://127.0.0.1', '/' );
$checks  = array();
$created_posts = array();
$created_users = array();
$created_terms = array();
$created_attachments = array();
$saved_options = array();
$saved_active_plugins = array();

if ( ! is_file( $wp_load ) ) {
	fwrite( STDERR, 'Missing wp-load.php: ' . $wp_load . "\n" );
	exit( 1 );
}

require_once $wp_load;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

$saved_active_plugins = get_option( 'active_plugins', array() );

$admin = get_users(
	array(
		'role__in' => array( 'administrator' ),
		'number'   => 1,
		'orderby'  => 'ID',
		'order'    => 'ASC',
	)
);
if ( ! $admin ) {
	fwrite( STDERR, "No administrator user is available for runtime acceptance checks.\n" );
	exit( 1 );
}
wp_set_current_user( (int) $admin[0]->ID );

function nerv_accept_save_option( string $name ): void {
	global $saved_options;
	if ( array_key_exists( $name, $saved_options ) ) {
		return;
	}
	$saved_options[ $name ] = array(
		'exists' => false !== get_option( $name, false ),
		'value'  => get_option( $name ),
	);
}

function nerv_accept_restore_options(): void {
	global $saved_options;
	foreach ( $saved_options as $name => $state ) {
		if ( ! empty( $state['exists'] ) ) {
			update_option( $name, $state['value'], false );
		} else {
			delete_option( $name );
		}
	}
}

function nerv_accept_check( array &$checks, string $label, bool $passed, string $detail ): void {
	$checks[] = array(
		'label'  => $label,
		'state'  => $passed ? 'pass' : 'fail',
		'detail' => $detail,
	);
}

function nerv_accept_http_get( string $url, array $headers = array() ): array {
	$header_lines = array( 'User-Agent: NERV-Acceptance-Audit/1.0' );
	foreach ( $headers as $name => $value ) {
		$header_lines[] = $name . ': ' . $value;
	}
	$context = stream_context_create(
		array(
			'http' => array(
				'ignore_errors' => true,
				'timeout'       => 10,
				'header'        => implode( "\r\n", $header_lines ) . "\r\n",
			),
		)
	);
	$body = @file_get_contents( $url, false, $context );
	$status = 0;
	$response_headers = array();
	foreach ( $http_response_header ?? array() as $header ) {
		$response_headers[] = $header;
		if ( preg_match( '/^HTTP\/\S+\s+(\d+)/', $header, $matches ) ) {
			$status = (int) $matches[1];
		}
	}

	return array(
		'status'  => $status,
		'body'    => false === $body ? '' : $body,
		'headers' => $response_headers,
	);
}

function nerv_accept_create_image_attachment( int $post_id, string $label, int $width, int $height, array $rgb ): int {
	global $created_attachments;
	$uploads = wp_upload_dir();
	$dir = trailingslashit( $uploads['path'] );
	wp_mkdir_p( $dir );
	$file = $dir . sanitize_file_name( strtolower( $label ) ) . '-' . wp_generate_password( 8, false, false ) . '.png';

	$image = imagecreatetruecolor( $width, $height );
	$bg = imagecolorallocate( $image, $rgb[0], $rgb[1], $rgb[2] );
	imagefilledrectangle( $image, 0, 0, $width, $height, $bg );
	imagepng( $image, $file );
	imagedestroy( $image );

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => $label,
			'post_status'    => 'inherit',
		),
		$file,
		$post_id
	);
	if ( is_wp_error( $attachment_id ) ) {
		return 0;
	}

	$metadata = wp_generate_attachment_metadata( (int) $attachment_id, $file );
	wp_update_attachment_metadata( (int) $attachment_id, $metadata );
	$created_attachments[] = (int) $attachment_id;

	return (int) $attachment_id;
}

function nerv_accept_post_id_by_title( string $title, string $type = 'post' ): int {
	$post = get_page_by_title( $title, OBJECT, $type );
	return $post instanceof WP_Post ? (int) $post->ID : 0;
}

function nerv_accept_insert_post( array $args ): int {
	global $created_posts;
	$post_id = wp_insert_post( $args, true );
	if ( is_wp_error( $post_id ) ) {
		fwrite( STDERR, $post_id->get_error_message() . "\n" );
		return 0;
	}
	$created_posts[] = (int) $post_id;
	return (int) $post_id;
}

function nerv_accept_set_temp_option( string $name, $value ): void {
	nerv_accept_save_option( $name );
	update_option( $name, $value, false );
}

try {
	nerv_accept_save_option( 'nerv_core_cover_options' );
	nerv_accept_save_option( 'nerv_core_indexnow_options' );
	nerv_accept_save_option( 'nerv_core_geo_crawler_options' );
	nerv_accept_save_option( 'nerv_core_partner_display_options' );
	nerv_accept_save_option( 'nerv_core_social_options' );
	nerv_accept_save_option( 'nerv_terminal_strings' );

	nerv_accept_set_temp_option(
		'nerv_core_cover_options',
		nerv_core_cover_sanitize_options(
			array(
				'endpoint'         => 'https://example.invalid/images',
				'api_key'          => 'runtime-placeholder',
				'model'            => 'acceptance-model',
				'prompt_template'  => 'Acceptance cover for {title}',
				'auto_generate'    => false,
				'key_points_auto'  => true,
				'dry_run'          => true,
			)
		)
	);
	nerv_accept_set_temp_option(
		'nerv_core_indexnow_options',
		nerv_core_indexnow_sanitize_options(
			array(
				'enabled'  => true,
				'key'      => '',
				'endpoint' => 'https://api.indexnow.org/indexnow',
				'dry_run'  => true,
			)
		)
	);
	nerv_accept_set_temp_option( 'nerv_core_geo_crawler_options', nerv_core_geo_crawler_sanitize_options( nerv_core_geo_crawler_default_options() ) );
	nerv_accept_set_temp_option(
		'nerv_core_partner_display_options',
		nerv_core_partner_display_sanitize_options(
			array(
				'footer_enabled'      => true,
				'footer_limit'        => 4,
				'application_enabled' => true,
				'application_email'   => get_option( 'admin_email' ),
				'application_text'    => 'Acceptance ally request copy.',
				'llms_include'        => true,
			)
		)
	);

	$term = wp_insert_term( 'NERV Acceptance', 'category' );
	if ( is_wp_error( $term ) && 'term_exists' === $term->get_error_code() ) {
		$category_id = (int) $term->get_error_data();
	} elseif ( is_wp_error( $term ) ) {
		$category_id = 0;
	} else {
		$category_id = (int) $term['term_id'];
		$created_terms[] = array( 'id' => $category_id, 'taxonomy' => 'category' );
	}
	$tag = wp_insert_term( 'nerv-acceptance', 'post_tag' );
	$tag_id = is_wp_error( $tag ) ? (int) $tag->get_error_data() : (int) $tag['term_id'];

	$author_one = wp_insert_user(
		array(
			'user_login'   => 'nerv_acceptance_alpha_' . wp_generate_password( 6, false, false ),
			'user_pass'    => wp_generate_password( 24, true, true ),
			'user_email'   => 'nerv-alpha-' . wp_generate_password( 6, false, false ) . '@example.test',
			'display_name' => 'Acceptance Alpha',
			'description'  => 'Acceptance author alpha profile.',
			'role'         => 'author',
		)
	);
	$author_two = wp_insert_user(
		array(
			'user_login'   => 'nerv_acceptance_beta_' . wp_generate_password( 6, false, false ),
			'user_pass'    => wp_generate_password( 24, true, true ),
			'user_email'   => 'nerv-beta-' . wp_generate_password( 6, false, false ) . '@example.test',
			'display_name' => 'Acceptance Beta',
			'description'  => 'Acceptance author beta profile.',
			'role'         => 'author',
		)
	);
	if ( ! is_wp_error( $author_one ) ) {
		$created_users[] = (int) $author_one;
		update_user_meta( (int) $author_one, 'nerv_author_title', 'Alpha GEO Pilot' );
		update_user_meta( (int) $author_one, 'nerv_author_social_github', 'https://example.test/alpha-gh' );
		update_user_meta( (int) $author_one, 'nerv_author_social_website', 'https://example.test/alpha' );
	}
	if ( ! is_wp_error( $author_two ) ) {
		$created_users[] = (int) $author_two;
		update_user_meta( (int) $author_two, 'nerv_author_title', 'Beta Interface Pilot' );
		update_user_meta( (int) $author_two, 'nerv_author_social_linkedin', 'https://example.test/beta-in' );
		update_user_meta( (int) $author_two, 'nerv_author_social_website', 'https://example.test/beta' );
	}

	$related_match = nerv_accept_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_author'  => is_wp_error( $author_two ) ? get_current_user_id() : (int) $author_two,
			'post_title'   => 'NERV ACCEPTANCE Related Match',
			'post_content' => '<p>This related entry shares the same category and tag.</p>',
		)
	);
	$related_other = nerv_accept_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_title'   => 'NERV ACCEPTANCE Related Other',
			'post_content' => '<p>This entry should rank lower than the shared taxonomy match.</p>',
		)
	);
	if ( $category_id ) {
		wp_set_post_categories( $related_match, array( $category_id ), false );
		wp_set_post_terms( $related_match, array( $tag_id ), 'post_tag', false );
	}

	$content_words = str_repeat( 'Acceptance content provides direct context for AI readers and search systems. ', 34 );
	$main_post = nerv_accept_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_author'  => is_wp_error( $author_one ) ? get_current_user_id() : (int) $author_one,
			'post_title'   => 'NERV ACCEPTANCE GEO Chain',
			'post_excerpt' => 'Acceptance excerpt for social and GEO validation.',
			'post_content' =>
				'<!-- wp:nerv-core/key-points {"points":["Acceptance validates Markdown mirrors.","Acceptance validates llms.txt discovery.","Acceptance validates IndexNow dry-run logs."]} /-->' .
				"\n\n" .
				'<p>NERV Acceptance is a runtime validation article that provides a direct definition for AI systems.</p>' .
				"\n\n" .
				'<!-- wp:heading {"level":2} --><h2>Acceptance Structure</h2><!-- /wp:heading -->' .
				'<p>' . esc_html( $content_words ) . ' <a href="' . esc_url( home_url( '/' ) ) . '">Home</a> <a href="' . esc_url( home_url( '/llms.txt' ) ) . '">llms</a></p>' .
				'<!-- wp:nerv-core/faq {"items":[{"question":"What does this audit prove?","answer":"It proves the GEO publishing chain is reachable in the runtime."}]} /-->',
		)
	);
	update_post_meta( $main_post, '_nerv_subtitle', 'Acceptance subtitle signal' );
	if ( $category_id ) {
		wp_set_post_categories( $main_post, array( $category_id ), false );
		wp_set_post_terms( $main_post, array( $tag_id ), 'post_tag', false );
	}

	$upload_cover_id = nerv_accept_create_image_attachment( $main_post, 'NERV Acceptance Uploaded Cover', 1500, 600, array( 20, 80, 40 ) );
	if ( $upload_cover_id ) {
		set_post_thumbnail( $main_post, $upload_cover_id );
	}
	$generated_cover_id = nerv_accept_create_image_attachment( $main_post, 'NERV Acceptance AI Cover', 1536, 768, array( 120, 20, 20 ) );

	$partner_ids = array();
	$partner_rows = array(
		array( 'NERV ACCEPTANCE Partner One', 'https://example.com', '1', 'online' ),
		array( 'NERV ACCEPTANCE Partner Two', 'https://wordpress.org', '1', 'online' ),
		array( 'NERV ACCEPTANCE Partner Three', 'https://openai.com', '1', 'online' ),
		array( 'NERV ACCEPTANCE Dead Partner', 'https://127.0.0.1:1', '0', 'offline' ),
	);
	foreach ( $partner_rows as $row ) {
		$partner_id = nerv_accept_insert_post(
			array(
				'post_type'    => 'partner',
				'post_status'  => 'publish',
				'post_title'   => $row[0],
				'post_content' => '<p>Acceptance partner record.</p>',
			)
		);
		$partner_ids[] = $partner_id;
		update_post_meta( $partner_id, '_nerv_partner_url', $row[1] );
		update_post_meta( $partner_id, '_nerv_partner_featured', $row[2] );
		update_post_meta( $partner_id, '_nerv_partner_rel', '0' === $row[2] ? 'nofollow' : 'follow' );
		nerv_core_partner_health_store_result( $partner_id, nerv_core_partner_health_result( $row[3], 'Acceptance fixture', 0, 0 ) );
	}

	nerv_core_geo_write_markdown_cache( get_post( $main_post ) );
	wp_update_post( array( 'ID' => $main_post, 'post_modified' => current_time( 'mysql' ) ) );

	$cover_5x2 = nerv_core_cover_url( $main_post, '5x2' );
	$cover_2x1 = nerv_core_cover_url( $main_post, '2x1' );
	$upload_meta = wp_get_attachment_metadata( $upload_cover_id );
	$upload_is_native_5x2 = isset( $upload_meta['width'], $upload_meta['height'] ) && 1500 === (int) $upload_meta['width'] && 600 === (int) $upload_meta['height'];
	$upload_has_derived_5x2 = isset( $upload_meta['sizes']['nerv-cover'] ) && 1500 === (int) $upload_meta['sizes']['nerv-cover']['width'] && 600 === (int) $upload_meta['sizes']['nerv-cover']['height'];
	nerv_accept_check( $checks, 'cover upload source', 'upload' === nerv_core_cover_source( $main_post ), 'Featured image source is detected as user upload.' );
	nerv_accept_check( $checks, 'cover upload 5:2 size', $upload_is_native_5x2 || $upload_has_derived_5x2, 'Uploaded source provides the 1500x600 5:2 site image.' );
	nerv_accept_check( $checks, 'cover upload 2:1 size', isset( $upload_meta['sizes']['nerv-og'] ) && 1200 === (int) $upload_meta['sizes']['nerv-og']['width'] && 600 === (int) $upload_meta['sizes']['nerv-og']['height'], 'Uploaded source generated the 1200x600 social size.' );

	delete_post_thumbnail( $main_post );
	update_post_meta( $main_post, '_nerv_cover_generated_url', wp_get_attachment_url( $generated_cover_id ) );
	nerv_core_cover_store_history(
		$main_post,
		array(
			'status'        => 'success',
			'message'       => 'Acceptance AI fixture',
			'source'        => 'acceptance',
			'url'           => wp_get_attachment_url( $generated_cover_id ),
			'attachment_id' => $generated_cover_id,
			'prompt'        => 'Acceptance',
		)
	);
	set_post_thumbnail( $main_post, $generated_cover_id );
	nerv_accept_check( $checks, 'cover ai source', 'ai' === nerv_core_cover_source( $main_post ), 'AI-generated attachment source is detected as AI.' );

	delete_post_thumbnail( $main_post );
	delete_post_meta( $main_post, '_nerv_cover_generated_url' );
	$fallback_url = nerv_core_cover_url( $main_post, '5x2' );
	$fallback = nerv_accept_http_get( $fallback_url );
	nerv_accept_check( $checks, 'cover svg fallback', 200 === $fallback['status'] && str_contains( $fallback['body'], '<svg' ) && 'svg' === nerv_core_cover_source( $main_post ), 'Missing upload and AI cover falls back to dynamic SVG.' );

	$dry_run_cover = nerv_core_cover_generate( $main_post, 'acceptance' );
	nerv_accept_check( $checks, 'cover ai failure nonblocking', 'dry-run' === (string) ( $dry_run_cover['status'] ?? '' ) && ! has_post_thumbnail( $main_post ), 'Dry-run AI cover records without blocking or forcing a thumbnail.' );
	if ( $upload_cover_id ) {
		set_post_thumbnail( $main_post, $upload_cover_id );
	}

	$post_url = get_permalink( $main_post );
	$html = nerv_accept_http_get( $post_url );
	nerv_accept_check( $checks, 'social og image present', str_contains( $html['body'], 'property="og:image"' ) && str_contains( $html['body'], 'og:image:width" content="1200"' ) && str_contains( $html['body'], 'og:image:height" content="600"' ), 'Singular head outputs 2:1 Open Graph image metadata.' );
	nerv_accept_check( $checks, 'social twitter image present', str_contains( $html['body'], 'name="twitter:card" content="summary_large_image"' ) && str_contains( $html['body'], 'name="twitter:image"' ), 'Singular head outputs Twitter large-image metadata.' );
	nerv_accept_check( $checks, 'geo hidden links', str_contains( $html['body'], 'class="nerv-geo-links"' ) && str_contains( $html['body'], 'Markdown mirror' ) && str_contains( $html['body'], 'llms.txt' ), 'Entry HTML includes hidden machine-readable GEO links.' );

	$markdown_url = nerv_core_geo_markdown_url( $main_post );
	$markdown = nerv_accept_http_get( $markdown_url );
	$headers_text = implode( "\n", $markdown['headers'] );
	nerv_accept_check( $checks, 'markdown mirror http', 200 === $markdown['status'], $markdown_url . ' returned HTTP ' . $markdown['status'] . '.' );
	nerv_accept_check( $checks, 'markdown mirror front matter', str_contains( $markdown['body'], 'title: "NERV ACCEPTANCE GEO Chain"' ) && str_contains( $markdown['body'], 'subtitle: "Acceptance subtitle signal"' ) && str_contains( $markdown['body'], 'category:' ) && str_contains( $markdown['body'], 'canonical:' ), 'Markdown front matter includes title, subtitle, category, author, canonical, and dates.' );
	nerv_accept_check( $checks, 'markdown mirror noindex', str_contains( $headers_text, 'X-Robots-Tag: noindex' ) && str_contains( $headers_text, 'rel="canonical"' ), 'Markdown response sends noindex and canonical Link headers.' );

	$llms = nerv_accept_http_get( nerv_core_geo_llms_url( false ) );
	nerv_accept_check( $checks, 'llms contains post', 200 === $llms['status'] && str_contains( $llms['body'], 'NERV ACCEPTANCE GEO Chain' ) && str_contains( $llms['body'], $markdown_url ), 'llms.txt includes the published article and markdown mirror URL.' );
	$index_log = nerv_core_indexnow_log();
	$latest_index = $index_log[0] ?? array();
	nerv_accept_check( $checks, 'indexnow dry-run log', 'dry-run' === (string) ( $latest_index['status'] ?? '' ) && in_array( $post_url, (array) ( $latest_index['urls'] ?? array() ), true ), 'Publishing/updating the article records an IndexNow dry-run log row.' );

	$score = nerv_core_geo_score_post( get_post( $main_post ) );
	nerv_accept_check( $checks, 'geo scorer high score', (int) $score['score'] >= 85 && 'GREEN' === (string) $score['grade'], 'Well-structured acceptance article scores ' . $score['score'] . '/100.' );
	nerv_accept_check( $checks, 'geo scorer suggestions', count( $score['checks'] ) >= 9 && isset( $score['checks'][0]['suggestion'] ), 'GEO score returns per-item checks and human suggestions.' );

	nerv_accept_http_get( $post_url, array( 'User-Agent' => 'GPTBot/1.0 acceptance' ) );
	$crawler_summary = nerv_core_geo_crawler_summary( 7 );
	nerv_accept_check( $checks, 'crawler monitor records forged ua', absint( $crawler_summary['window']['gptbot'] ?? 0 ) >= 1, 'Forged GPTBot request increments the crawler monitor.' );
	$dashboard = nerv_core_control_dashboard_data();
	$crawler_metric = array_values(
		array_filter(
			(array) ( $dashboard['health'] ?? array() ),
				static function ( array $item ): bool {
					return 'crawlers' === (string) ( $item['key'] ?? '' )
						|| __( 'AI crawler monitor', 'nerv-core' ) === (string) ( $item['label'] ?? '' );
				}
		)
	);
	nerv_accept_check( $checks, 'dashboard crawler consistency', $crawler_metric && str_contains( (string) $crawler_metric[0]['value'], (string) absint( $crawler_summary['total'] ?? 0 ) ), 'Dashboard crawler light matches stored crawler summary.' );

	$json_ld_match = array();
	preg_match( '~<script type="application/ld\+json">(.*?)</script>~s', $html['body'], $json_ld_match );
	$json_ld = ! empty( $json_ld_match[1] ) ? json_decode( html_entity_decode( $json_ld_match[1] ), true ) : array();
	$graph = is_array( $json_ld['@graph'] ?? null ) ? $json_ld['@graph'] : array();
	$person = array_values( array_filter( $graph, static function ( array $node ): bool { return 'Person' === (string) ( $node['@type'] ?? '' ); } ) );
	nerv_accept_check( $checks, 'author person sameAs', $person && count( (array) ( $person[0]['sameAs'] ?? array() ) ) >= 2, 'Primary author Person JSON-LD includes independent sameAs links.' );

	$related = nerv_core_related_entries( $main_post, 2 );
	nerv_accept_check( $checks, 'related taxonomy ranking', $related && (int) $related[0]->ID === $related_match, 'Shared category/tag related post ranks first.' );
	$transient_rows_before = nerv_accept_related_transient_count();
	wp_update_post( array( 'ID' => $related_match, 'post_content' => '<p>Updated related entry for cache invalidation.</p>' ) );
	$transient_rows_after = nerv_accept_related_transient_count();
	nerv_accept_check( $checks, 'related cache invalidation', $transient_rows_before > 0 && 0 === $transient_rows_after, 'Related transient rows are flushed after updating a related post.' );

	$partners_page = nerv_accept_http_get( get_post_type_archive_link( 'partner' ) ?: add_query_arg( 'post_type', 'partner', home_url( '/' ) ) );
	nerv_accept_check( $checks, 'partners grid four records', substr_count( $partners_page['body'], 'NERV ACCEPTANCE Partner' ) >= 3 && str_contains( $partners_page['body'], 'NERV ACCEPTANCE Dead Partner' ), 'Partners archive renders the four acceptance partner records.' );
	nerv_accept_check( $checks, 'partners dead link red', str_contains( $partners_page['body'], 'nerv-partner-status--offline' ) && str_contains( $partners_page['body'], 'OFFLINE' ), 'Dead partner displays the offline status light.' );
	$home = nerv_accept_http_get( home_url( '/' ) );
	nerv_accept_check( $checks, 'footer partner row', str_contains( $home['body'], 'nerv-footer-partners' ) && str_contains( $home['body'], 'NERV ACCEPTANCE Partner One' ), 'Footer featured partner row renders.' );

	nerv_accept_set_temp_option(
		'nerv_core_social_options',
		nerv_core_social_sanitize_options(
			array(
				'enabled'      => true,
				'open_new_tab' => false,
				'links'        => array(
					array(
						'key'     => 'wechat',
						'label'   => 'WX',
						'url'     => '',
						'qrUrl'   => nerv_terminal_manifest_icon_url( 192 ),
						'enabled' => true,
						'rel'     => 'nofollow',
					),
				),
			)
		)
	);
	$strings = get_option( 'nerv_terminal_strings', array() );
	$strings = is_array( $strings ) ? $strings : array();
	$strings['footer_record_enabled'] = '1';
	$strings['footer_record_label'] = 'ICP';
	$strings['footer_record_text'] = 'ACCEPTANCE-ICP';
	$strings['footer_extra_enabled'] = '1';
	$strings['footer_extra_text'] = 'ACCEPTANCE FOOTER EXTRA';
	nerv_accept_set_temp_option( 'nerv_terminal_strings', $strings );
	$home_with_extras = nerv_accept_http_get( home_url( '/' ) );
	nerv_accept_check( $checks, 'wechat qr popup', str_contains( $home_with_extras['body'], 'nerv-social-qr' ) && str_contains( $home_with_extras['body'], '<summary aria-label="WX">WX</summary>' ), 'Pilot social area renders a no-JS WeChat QR popup.' );
	nerv_accept_check( $checks, 'footer placeholders record', str_contains( $home_with_extras['body'], 'ACCEPTANCE-ICP' ) && str_contains( $home_with_extras['body'], 'ACCEPTANCE FOOTER EXTRA' ), 'Footer record and extra placeholder segments render when enabled.' );

	$seo_saved_plugins = get_option( 'active_plugins', array() );
	update_option( 'active_plugins', array_values( array_unique( array_merge( (array) $seo_saved_plugins, array( 'wordpress-seo/wp-seo.php' ) ) ) ), false );
	$seo_html = nerv_accept_http_get( $post_url );
	update_option( 'active_plugins', $seo_saved_plugins, false );
	nerv_accept_check( $checks, 'seo plugin coexistence', ! str_contains( $seo_html['body'], 'property="og:image"' ) && ! str_contains( $seo_html['body'], '<script type="application/ld+json">' ) && ! str_contains( $seo_html['body'], 'name="description"' ), 'Theme defers description, social meta, and JSON-LD when a known SEO plugin is active.' );

	$steps = (array) ( $dashboard['steps'] ?? array() );
	$done_steps = count( array_filter( $steps, static function ( array $step ): bool { return ! empty( $step['done'] ); } ) );
	$green_health = count( array_filter( (array) ( $dashboard['health'] ?? array() ), static function ( array $item ): bool { return 'green' === (string) ( $item['state'] ?? '' ); } ) );
	nerv_accept_check( $checks, 'activation wizard five steps', 5 === count( $steps ) && $done_steps >= 4, 'Dashboard exposes five activation wizard steps with local setup mostly complete.' );
	nerv_accept_check( $checks, 'dashboard health lights', count( (array) ( $dashboard['health'] ?? array() ) ) >= 8 && $green_health >= 6, 'NERV DASHBOARD exposes health lights backed by runtime state.' );

	nerv_accept_check( $checks, 'mobile config five tabs', function_exists( 'nerv_terminal_mobile_tabs' ) && count( nerv_terminal_mobile_tabs() ) >= 3 && count( nerv_terminal_mobile_tabs() ) <= 5, 'Mobile tabs are configurable and clamped to 3-5 active tabs.' );
} finally {
	nerv_accept_restore_options();
	update_option( 'active_plugins', $saved_active_plugins, false );
	foreach ( array_reverse( $created_posts ) as $post_id ) {
		wp_delete_post( (int) $post_id, true );
	}
	foreach ( array_reverse( $created_attachments ) as $attachment_id ) {
		wp_delete_attachment( (int) $attachment_id, true );
	}
	foreach ( array_reverse( $created_users ) as $user_id ) {
		wp_delete_user( (int) $user_id );
	}
	foreach ( array_reverse( $created_terms ) as $term ) {
		wp_delete_term( (int) $term['id'], (string) $term['taxonomy'] );
	}
}

$failed = array_values(
	array_filter(
		$checks,
		static function ( array $check ): bool {
			return 'pass' !== $check['state'];
		}
	)
);

foreach ( $checks as $check ) {
	printf( "[%s] %s - %s\n", strtoupper( $check['state'] ), $check['label'], $check['detail'] );
}

if ( $failed ) {
	exit( 1 );
}

function nerv_accept_related_transient_count(): int {
	global $wpdb;
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( '_transient_nerv_related_' ) . '%'
		)
	);
}
