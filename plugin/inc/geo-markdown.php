<?php
/**
 * GEO markdown mirrors and llms.txt output.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'nerv_core_geo_register_routes' );
function nerv_core_geo_register_routes(): void {
	add_rewrite_tag( '%nerv_md%', '([0-9]+)' );
	add_rewrite_tag( '%nerv_md_path%', '(.+)' );
	add_rewrite_tag( '%nerv_llms%', '1' );
	add_rewrite_tag( '%nerv_llms_full%', '1' );
	add_rewrite_tag( '%nerv_json_feed%', '1' );
	add_rewrite_rule( '^llms\.txt/?$', 'index.php?nerv_llms=1', 'top' );
	add_rewrite_rule( '^llms-full\.txt/?$', 'index.php?nerv_llms_full=1', 'top' );
	add_rewrite_rule( '^feed/json/?$', 'index.php?nerv_json_feed=1', 'top' );
	add_rewrite_rule( '^(.+)\.md$', 'index.php?nerv_md_path=$matches[1]', 'top' );
}

add_action( 'template_redirect', 'nerv_core_geo_template_redirect', 0 );
function nerv_core_geo_template_redirect(): void {
	$request_path = nerv_core_geo_request_path();
	if ( 'llms.txt' === $request_path ) {
		nerv_core_geo_output_llms( false );
	}

	if ( 'llms-full.txt' === $request_path ) {
		nerv_core_geo_output_llms( true );
	}

	if ( 'feed/json' === $request_path ) {
		nerv_core_geo_output_json_feed();
	}

	if ( str_ends_with( $request_path, '.md' ) ) {
		nerv_core_geo_output_markdown( nerv_core_geo_post_id_from_markdown_path( substr( $request_path, 0, -3 ) ) );
	}

	$md_post_id = absint( get_query_var( 'nerv_md' ) ?: ( $_GET['nerv_md'] ?? 0 ) );
	if ( $md_post_id ) {
		nerv_core_geo_output_markdown( $md_post_id );
	}

	$md_path = (string) ( get_query_var( 'nerv_md_path' ) ?: ( $_GET['nerv_md_path'] ?? '' ) );
	if ( $md_path ) {
		nerv_core_geo_output_markdown( nerv_core_geo_post_id_from_markdown_path( $md_path ) );
	}

	if ( is_singular( nerv_core_geo_public_post_types() ) && nerv_core_geo_accepts_markdown() ) {
		nerv_core_geo_output_markdown( get_queried_object_id() );
	}

	if ( get_query_var( 'nerv_llms' ) || isset( $_GET['nerv_llms'] ) ) {
		nerv_core_geo_output_llms( false );
	}

	if ( get_query_var( 'nerv_llms_full' ) || isset( $_GET['nerv_llms_full'] ) ) {
		nerv_core_geo_output_llms( true );
	}

	if ( get_query_var( 'nerv_json_feed' ) || isset( $_GET['nerv_json_feed'] ) ) {
		nerv_core_geo_output_json_feed();
	}
}

add_action( 'wp_head', 'nerv_core_geo_output_json_ld', 20 );
function nerv_core_geo_output_json_ld(): void {
	if ( nerv_core_geo_should_defer_to_seo_plugin() ) {
		return;
	}

	$graph = nerv_core_geo_json_ld_graph();
	if ( ! $graph ) {
		return;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

add_action( 'save_post', 'nerv_core_geo_refresh_markdown_cache', 20, 2 );
function nerv_core_geo_refresh_markdown_cache( int $post_id, WP_Post $post ): void {
	if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}

	if ( ! in_array( $post->post_type, nerv_core_geo_public_post_types(), true ) ) {
		return;
	}

	if ( 'publish' !== $post->post_status ) {
		nerv_core_geo_delete_markdown_cache( $post_id );
		return;
	}

	nerv_core_geo_write_markdown_cache( $post );
}

add_action( 'deleted_post', 'nerv_core_geo_delete_markdown_cache' );
function nerv_core_geo_delete_markdown_cache( int $post_id ): void {
	$file = nerv_core_geo_markdown_cache_file( $post_id );
	if ( $file && is_file( $file ) ) {
		wp_delete_file( $file );
	}
}

function nerv_core_geo_markdown_url( int $post_id ): string {
	$post = get_post( $post_id );
	if ( $post instanceof WP_Post && get_option( 'permalink_structure' ) ) {
		$permalink = get_permalink( $post );
		if ( $permalink && false === strpos( $permalink, '?' ) ) {
			$permalink = untrailingslashit( $permalink );
			$parts = wp_parse_url( $permalink );
			$path  = isset( $parts['path'] ) ? (string) $parts['path'] : '';
			if ( str_ends_with( $path, '.html' ) ) {
				return substr( $permalink, 0, -5 ) . '.md';
			}

			return $permalink . '.md';
		}
	}

	return add_query_arg( 'nerv_md', (string) $post_id, home_url( '/' ) );
}

function nerv_core_geo_llms_url( bool $full = false ): string {
	return home_url( $full ? '/llms-full.txt' : '/llms.txt' );
}

function nerv_core_geo_json_feed_url(): string {
	return home_url( '/feed/json' );
}

function nerv_core_geo_request_path(): string {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );

	return trim( $path, '/' );
}

function nerv_core_geo_accepts_markdown(): bool {
	$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? (string) wp_unslash( $_SERVER['HTTP_ACCEPT'] ) : '';

	return false !== stripos( $accept, 'text/markdown' );
}

function nerv_core_geo_post_id_from_markdown_path( string $path ): int {
	$raw_path = trim( $path, '/' );
	$path     = trim( rawurldecode( $path ), '/' );
	if ( '' === $path ) {
		return 0;
	}

	$path_candidates = array_values( array_unique( array_merge( nerv_core_geo_markdown_path_candidates( $raw_path ), nerv_core_geo_markdown_path_candidates( $path ) ) ) );
	$direct_post_ids = nerv_core_geo_post_ids_by_markdown_paths( $path_candidates );
	foreach ( $direct_post_ids as $post_id ) {
		$post = get_post( (int) $post_id );
		if ( $post instanceof WP_Post && array_intersect( $path_candidates, nerv_core_geo_post_markdown_paths( $post ) ) ) {
			return (int) $post_id;
		}
	}

	$slug            = sanitize_title( basename( $path ) );
	if ( $slug ) {
		$slug_posts = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => nerv_core_geo_public_post_types(),
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'fields'         => 'ids',
			)
		);
		foreach ( $slug_posts as $post_id ) {
			$post = get_post( (int) $post_id );
			if ( $post instanceof WP_Post && array_intersect( $path_candidates, nerv_core_geo_post_markdown_paths( $post ) ) ) {
				return (int) $post_id;
			}
		}
	}

	$query = new WP_Query(
		array(
			'post_type'              => nerv_core_geo_public_post_types(),
			'post_status'            => 'publish',
			'posts_per_page'         => 250,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $query->posts as $post_id ) {
		$post = get_post( (int) $post_id );
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		if ( array_intersect( $path_candidates, nerv_core_geo_post_markdown_paths( $post ) ) ) {
			return (int) $post_id;
		}
	}

	return 0;
}

function nerv_core_geo_post_ids_by_markdown_paths( array $path_candidates ): array {
	global $wpdb;

	$post_types = nerv_core_geo_public_post_types();
	$names      = array_values(
		array_unique(
			array_filter(
				array_map(
					static function ( string $path ): string {
						return basename( trim( $path, '/' ) );
					},
					$path_candidates
				),
				'strlen'
			)
		)
	);
	if ( ! $names || ! $post_types ) {
		return array();
	}

	$type_placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
	$name_placeholders = implode( ',', array_fill( 0, count( $names ), '%s' ) );
	$sql               = "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$type_placeholders}) AND post_name IN ({$name_placeholders}) LIMIT 20";

	return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( $sql, array_merge( $post_types, $names ) ) ) );
}

function nerv_core_geo_markdown_path_candidates( string $path ): array {
	$path = trim( $path, '/' );

	return array_values(
		array_unique(
			array_filter(
				array(
					$path,
					untrailingslashit( $path ),
					basename( $path ),
				),
				'strlen'
			)
		)
	);
}

function nerv_core_geo_post_markdown_paths( WP_Post $post ): array {
	$permalink_path = trim( (string) wp_parse_url( get_permalink( $post ), PHP_URL_PATH ), '/' );
	$html_free      = str_ends_with( $permalink_path, '.html' ) ? substr( $permalink_path, 0, -5 ) : untrailingslashit( $permalink_path );

	return array_values(
		array_unique(
			array_filter(
				array(
					$html_free,
					basename( $html_free ),
					$post->post_name,
					$post->post_type . '/' . $post->post_name,
				),
				'strlen'
			)
		)
	);
}

function nerv_core_geo_output_markdown( int $post_id ): void {
	if ( $post_id <= 0 ) {
		status_header( 404 );
		header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );
		echo "Not found.\n";
		exit;
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status || ! in_array( $post->post_type, nerv_core_geo_public_post_types(), true ) ) {
		status_header( 404 );
		header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );
		echo "Not found.\n";
		exit;
	}

	$markdown = nerv_core_geo_read_markdown_cache( $post );
	nocache_headers();
	header( 'Content-Type: text/markdown; charset=' . get_option( 'blog_charset' ) );
	$seo_options = function_exists( 'nerv_core_seo_options' ) ? nerv_core_seo_options() : array();
	if ( ! empty( $seo_options['noindex_markdown'] ) ) {
		header( 'X-Robots-Tag: noindex' );
	}
	header( 'Link: <' . esc_url_raw( get_permalink( $post ) ) . '>; rel="canonical"' );
	echo $markdown;
	exit;
}

function nerv_core_geo_markdown_cache_dir(): string {
	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
		return '';
	}

	return trailingslashit( $uploads['basedir'] ) . 'nerv-terminal/markdown';
}

function nerv_core_geo_markdown_cache_file( int $post_id ): string {
	$dir = nerv_core_geo_markdown_cache_dir();
	if ( '' === $dir ) {
		return '';
	}

	return trailingslashit( $dir ) . absint( $post_id ) . '.md';
}

function nerv_core_geo_write_markdown_cache( WP_Post $post ): bool {
	$file = nerv_core_geo_markdown_cache_file( (int) $post->ID );
	if ( '' === $file ) {
		return false;
	}

	$dir = dirname( $file );
	if ( ! wp_mkdir_p( $dir ) ) {
		return false;
	}

	return false !== file_put_contents( $file, nerv_core_geo_render_markdown( $post, true ), LOCK_EX );
}

function nerv_core_geo_read_markdown_cache( WP_Post $post ): string {
	$file = nerv_core_geo_markdown_cache_file( (int) $post->ID );
	if ( $file && is_file( $file ) ) {
		$content = file_get_contents( $file );
		if ( false !== $content ) {
			return $content;
		}
	}

	$markdown = nerv_core_geo_render_markdown( $post, true );
	nerv_core_geo_write_markdown_cache( $post );

	return $markdown;
}

function nerv_core_geo_output_llms( bool $full ): void {
	$posts = nerv_core_geo_index_posts( $full ? 50 : 24 );
	$lines = array(
		'# ' . get_bloginfo( 'name' ),
		'',
		wp_strip_all_tags( get_bloginfo( 'description' ) ),
		'',
		'Canonical site: ' . home_url( '/' ),
		'Markdown mirrors: ' . nerv_core_geo_llms_url( false ),
	);
	if ( function_exists( 'nerv_core_ai_policy_exists' ) && nerv_core_ai_policy_exists() ) {
		$lines[] = 'AI usage policy: ' . nerv_core_ai_policy_url();
	}
	$lines[] = '';
	$lines[] = '## Content Index';

	foreach ( $posts as $post ) {
		$line = '- [' . get_the_title( $post ) . '](' . nerv_core_geo_markdown_url( (int) $post->ID ) . ')';
		$subtitle = get_post_meta( $post->ID, '_nerv_subtitle', true );
		if ( $subtitle ) {
			$line .= ' - ' . wp_strip_all_tags( $subtitle );
		}
		$lines[] = $line;
	}

	if ( function_exists( 'nerv_core_partner_display_llms_enabled' ) && nerv_core_partner_display_llms_enabled() ) {
		$partners = function_exists( 'nerv_core_partner_query' ) ? nerv_core_partner_query( 50, false ) : array();
		if ( $partners ) {
			$lines[] = '';
			$lines[] = '## Partner Index';
			foreach ( $partners as $partner ) {
				$url = get_post_meta( (int) $partner->ID, '_nerv_partner_url', true ) ?: get_permalink( $partner );
				$subtitle = get_post_meta( (int) $partner->ID, '_nerv_subtitle', true );
				$line = '- [' . get_the_title( $partner ) . '](' . $url . ')';
				if ( $subtitle ) {
					$line .= ' - ' . wp_strip_all_tags( $subtitle );
				}
				$lines[] = $line;
			}
		}
	}

	if ( $full ) {
		$lines[] = '';
		$lines[] = '## Full Text';
		foreach ( $posts as $post ) {
			$lines[] = '';
			$lines[] = nerv_core_geo_render_markdown( $post, false );
		}
	}

	nocache_headers();
	header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );
	echo trim( implode( "\n", $lines ) ) . "\n";
	exit;
}

function nerv_core_geo_output_json_feed(): void {
	$posts = nerv_core_geo_index_posts( 20 );
	$items = array();
	foreach ( $posts as $post ) {
		$content_html = apply_filters( 'the_content', $post->post_content );
		$items[] = array_filter(
			array(
				'id'             => (string) $post->ID,
				'url'            => get_permalink( $post ),
				'external_url'   => nerv_core_geo_markdown_url( (int) $post->ID ),
				'title'          => get_the_title( $post ),
				'content_html'   => $content_html,
				'summary'        => nerv_core_geo_summary( $post, 38 ),
				'date_published' => get_the_date( DATE_ATOM, $post ),
				'date_modified'  => get_the_modified_date( DATE_ATOM, $post ),
				'author'         => array(
					'name' => get_the_author_meta( 'display_name', (int) $post->post_author ) ?: get_bloginfo( 'name' ),
					'url'  => get_author_posts_url( (int) $post->post_author ),
				),
				'tags'           => nerv_core_geo_post_terms( $post ),
				'_nerv'          => array(
					'markdown_url' => nerv_core_geo_markdown_url( (int) $post->ID ),
					'subtitle'     => get_post_meta( $post->ID, '_nerv_subtitle', true ),
				),
			)
		);
	}

	$feed = array(
		'version'      => 'https://jsonfeed.org/version/1.1',
		'title'        => get_bloginfo( 'name' ),
		'home_page_url'=> home_url( '/' ),
		'feed_url'     => nerv_core_geo_json_feed_url(),
		'description'  => wp_strip_all_tags( get_bloginfo( 'description' ) ),
		'language'     => get_bloginfo( 'language' ),
		'items'        => $items,
	);

	nocache_headers();
	header( 'Content-Type: application/feed+json; charset=' . get_option( 'blog_charset' ) );
	echo wp_json_encode( $feed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}

function nerv_core_geo_public_post_types(): array {
	return array( 'post', 'project' );
}

function nerv_core_geo_index_posts( int $limit ): array {
	return get_posts(
		array(
			'post_type'      => nerv_core_geo_public_post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		)
	);
}

function nerv_core_geo_json_ld_graph(): array {
	$site_id = home_url( '/#website' );
	$graph = array(
		array(
			'@type'      => 'WebSite',
			'@id'        => $site_id,
			'name'       => get_bloginfo( 'name' ),
			'url'        => home_url( '/' ),
			'inLanguage' => get_bloginfo( 'language' ),
			'description'=> wp_strip_all_tags( get_bloginfo( 'description' ) ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => add_query_arg( 's', '{search_term_string}', home_url( '/' ) ),
				'query-input' => 'required name=search_term_string',
			),
		),
	);

	if ( is_singular() ) {
		$post = get_post( get_queried_object_id() );
		if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
			if ( in_array( $post->post_type, nerv_core_geo_public_post_types(), true ) ) {
				$graph[] = nerv_core_geo_person_schema( (int) $post->post_author );
				$graph[] = nerv_core_geo_post_schema( $post, $site_id );
				$graph[] = nerv_core_geo_faq_schema( $post );
			}
			$graph[] = nerv_core_geo_breadcrumb_schema( $post );
		}
	}

	return array_values( array_filter( $graph ) );
}

function nerv_core_geo_post_schema( WP_Post $post, string $site_id ): array {
	$post_id = (int) $post->ID;
	$subtitle = get_post_meta( $post_id, '_nerv_subtitle', true );
	$thumbnail_id = get_post_thumbnail_id( $post_id );
	$image = $thumbnail_id && function_exists( 'nerv_core_image_optimizer_attachment_social_url' ) ? nerv_core_image_optimizer_attachment_social_url( (int) $thumbnail_id ) : '';
	if ( ! $image && function_exists( 'nerv_core_image_optimizer_social_cover_url' ) ) {
		$image = nerv_core_image_optimizer_social_cover_url( $post_id );
	}
	if ( ! $image ) {
		$image = function_exists( 'nerv_core_cover_url' ) ? nerv_core_cover_url( $post_id, '2x1' ) : ( get_the_post_thumbnail_url( $post_id, 'nerv-og' ) ?: get_the_post_thumbnail_url( $post_id, 'nerv-cover' ) );
	}
	$type = 'post' === $post->post_type ? 'BlogPosting' : 'CreativeWork';

	return array_filter(
		array(
			'@type'               => $type,
			'@id'                 => get_permalink( $post ) . '#entry',
			'isPartOf'            => array( '@id' => $site_id ),
			'mainEntityOfPage'    => get_permalink( $post ),
			'headline'            => get_the_title( $post ),
			'alternativeHeadline' => $subtitle ?: null,
			'description'         => nerv_core_geo_summary( $post, 36 ),
			'image'               => $image ? array( esc_url_raw( $image ) ) : null,
			'datePublished'       => get_the_date( DATE_ATOM, $post ),
			'dateModified'        => get_the_modified_date( DATE_ATOM, $post ),
			'author'              => array( '@id' => get_author_posts_url( (int) $post->post_author ) . '#person' ),
			'publisher'           => array( '@id' => $site_id ),
			'url'                 => get_permalink( $post ),
			'inLanguage'          => get_bloginfo( 'language' ),
			'encodingFormat'      => array( 'text/html', 'text/markdown' ),
			'sameAs'              => array( nerv_core_geo_markdown_url( $post_id ) ),
		)
	);
}

function nerv_core_geo_faq_schema( WP_Post $post ): array {
	if ( ! function_exists( 'nerv_core_extract_faq_items_from_post' ) ) {
		return array();
	}

	$items = nerv_core_extract_faq_items_from_post( $post );
	if ( ! $items ) {
		return array();
	}

	$entities = array();
	foreach ( $items as $item ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $item['question'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $item['answer'],
			),
		);
	}

	return array(
		'@type'      => 'FAQPage',
		'@id'        => get_permalink( $post ) . '#faq',
		'mainEntity' => $entities,
	);
}

function nerv_core_geo_person_schema( int $author_id ): array {
	$same_as = function_exists( 'nerv_core_author_same_as' ) ? nerv_core_author_same_as( $author_id ) : array();
	if ( ! $same_as && function_exists( 'nerv_core_social_same_as' ) ) {
		$same_as = nerv_core_social_same_as();
	}
	$title   = function_exists( 'nerv_core_author_title' ) ? nerv_core_author_title( $author_id ) : '';

	return array_filter(
		array(
			'@type'       => 'Person',
			'@id'         => get_author_posts_url( $author_id ) . '#person',
			'name'        => get_the_author_meta( 'display_name', $author_id ) ?: get_bloginfo( 'name' ),
			'jobTitle'    => $title ?: null,
			'description' => get_the_author_meta( 'description', $author_id ),
			'url'         => get_author_posts_url( $author_id ),
			'sameAs'      => $same_as ?: null,
		)
	);
}

function nerv_core_geo_breadcrumb_schema( WP_Post $post ): array {
	$items = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => get_bloginfo( 'name' ),
			'item'     => home_url( '/' ),
		),
		array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => get_the_title( $post ),
			'item'     => get_permalink( $post ),
		),
	);

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => get_permalink( $post ) . '#breadcrumb',
		'itemListElement' => $items,
	);
}

function nerv_core_geo_should_defer_to_seo_plugin(): bool {
	$seo_options = function_exists( 'nerv_core_seo_options' ) ? nerv_core_seo_options() : array();
	if ( ! empty( $seo_options['defer_to_seo_plugin'] ) ) {
		return nerv_core_geo_detect_seo_plugin();
	}

	return false;
}

function nerv_core_geo_detect_seo_plugin(): bool {
	$active = (array) get_option( 'active_plugins', array() );
	$seo_plugins = array(
		'wordpress-seo/wp-seo.php',
		'wordpress-seo-premium/wp-seo-premium.php',
		'seo-by-rank-math/rank-math.php',
		'all-in-one-seo-pack/all_in_one_seo_pack.php',
		'autodescription/autodescription.php',
	);

	return (bool) array_intersect( $active, $seo_plugins );
}

function nerv_core_geo_render_markdown( WP_Post $post, bool $with_front_matter ): string {
	$subtitle = get_post_meta( $post->ID, '_nerv_subtitle', true );
	$author   = get_the_author_meta( 'display_name', (int) $post->post_author );
	$content  = apply_filters( 'the_content', $post->post_content );
	$text     = nerv_core_geo_html_to_markdownish( $content );
	$lines    = array();

	if ( $with_front_matter ) {
		$lines[] = '---';
		$lines[] = 'title: "' . nerv_core_geo_yaml_escape( get_the_title( $post ) ) . '"';
		if ( $subtitle ) {
			$lines[] = 'subtitle: "' . nerv_core_geo_yaml_escape( $subtitle ) . '"';
		}
		$lines[] = 'date: "' . get_the_date( DATE_ATOM, $post ) . '"';
		$lines[] = 'modified: "' . get_the_modified_date( DATE_ATOM, $post ) . '"';
		$categories = nerv_core_geo_post_terms( $post );
		if ( $categories ) {
			$lines[] = 'category: "' . nerv_core_geo_yaml_escape( implode( ', ', $categories ) ) . '"';
		}
		$lines[] = 'author: "' . nerv_core_geo_yaml_escape( $author ) . '"';
		$lines[] = 'canonical: "' . esc_url_raw( get_permalink( $post ) ) . '"';
		$lines[] = 'markdown: "' . esc_url_raw( nerv_core_geo_markdown_url( (int) $post->ID ) ) . '"';
		$lines[] = '---';
		$lines[] = '';
	}

	$lines[] = '# ' . get_the_title( $post );
	if ( $subtitle ) {
		$lines[] = '';
		$lines[] = '> ' . $subtitle;
	}
	$lines[] = '';
	$lines[] = trim( $text );
	$lines[] = '';
	$lines[] = 'Canonical: ' . get_permalink( $post );

	return trim( implode( "\n", $lines ) ) . "\n";
}

function nerv_core_geo_html_to_markdownish( string $html ): string {
	$replacements = array(
		'~<h1[^>]*>(.*?)</h1>~is' => "\n# $1\n",
		'~<h2[^>]*>(.*?)</h2>~is' => "\n## $1\n",
		'~<h3[^>]*>(.*?)</h3>~is' => "\n### $1\n",
		'~<summary[^>]*>(.*?)</summary>~is' => "\n### $1\n",
		'~<li[^>]*>(.*?)</li>~is' => "\n- $1",
		'~</p>~i'                 => "\n\n",
		'~<br\\s*/?>~i'           => "\n",
	);
	foreach ( $replacements as $pattern => $replacement ) {
		$html = preg_replace( $pattern, $replacement, $html ) ?? $html;
	}
	$text = wp_strip_all_tags( $html );
	$text = html_entity_decode( $text, ENT_QUOTES, get_option( 'blog_charset' ) );
	$text = preg_replace( "/[ \t]+\n/", "\n", $text ) ?? $text;
	$text = preg_replace( "/\n{3,}/", "\n\n", $text ) ?? $text;
	return trim( $text );
}

function nerv_core_geo_yaml_escape( string $value ): string {
	return str_replace( array( "\\", '"' ), array( "\\\\", '\"' ), wp_strip_all_tags( $value ) );
}

function nerv_core_geo_summary( WP_Post $post, int $words = 36 ): string {
	$text = $post->post_excerpt ?: $post->post_content;
	$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, get_option( 'blog_charset' ) );
	return wp_trim_words( $text, $words, '...' );
}

function nerv_core_geo_post_terms( WP_Post $post ): array {
	$terms = array();
	if ( 'post' === $post->post_type ) {
		$terms = array_merge( $terms, wp_get_post_terms( $post->ID, 'category', array( 'fields' => 'names' ) ) );
		$terms = array_merge( $terms, wp_get_post_terms( $post->ID, 'post_tag', array( 'fields' => 'names' ) ) );
	}

	return array_values( array_unique( array_filter( array_map( 'strval', $terms ) ) ) );
}
