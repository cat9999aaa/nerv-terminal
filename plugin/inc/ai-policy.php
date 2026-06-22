<?php
/**
 * AI usage policy page generator.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_ai_policy_page_slug(): string {
	return 'ai-policy';
}

function nerv_core_ai_policy_page_id(): int {
	$page = get_page_by_path( nerv_core_ai_policy_page_slug(), OBJECT, 'page' );

	return $page instanceof WP_Post ? (int) $page->ID : 0;
}

function nerv_core_ai_policy_url(): string {
	$page_id = nerv_core_ai_policy_page_id();

	return $page_id ? get_permalink( $page_id ) : home_url( '/' . nerv_core_ai_policy_page_slug() . '/' );
}

function nerv_core_ai_policy_exists(): bool {
	$page_id = nerv_core_ai_policy_page_id();
	if ( ! $page_id ) {
		return false;
	}

	$status = get_post_status( $page_id );

	return in_array( $status, array( 'publish', 'private' ), true );
}

function nerv_core_ai_policy_content(): string {
	$site_name = wp_strip_all_tags( get_bloginfo( 'name' ) );
	$home_url  = home_url( '/' );
	$contact   = get_option( 'admin_email' );

	return '<!-- wp:heading --><h2>AI Usage Policy</h2><!-- /wp:heading -->' .
		'<!-- wp:paragraph --><p>' . esc_html( $site_name ) . ' publishes human-readable pages and machine-readable GEO resources for responsible search, indexing, summarization, and citation.</p><!-- /wp:paragraph -->' .
		'<!-- wp:heading {"level":3} --><h3>Allowed Use</h3><!-- /wp:heading -->' .
		'<!-- wp:list --><ul>' .
		'<li>AI systems may crawl public pages, Markdown mirrors, JSON Feed, and llms.txt for indexing, summarization, and citation.</li>' .
		'<li>Short excerpts may be used when they clearly attribute the source and link to the canonical page.</li>' .
		'<li>Metadata, titles, subtitles, author information, and structured data may be used to improve source understanding.</li>' .
		'</ul><!-- /wp:list -->' .
		'<!-- wp:heading {"level":3} --><h3>Attribution Requirements</h3><!-- /wp:heading -->' .
		'<!-- wp:list --><ul>' .
		'<li>Use the canonical URL from the page or Markdown front matter.</li>' .
		'<li>Preserve the article title and author when available.</li>' .
		'<li>Do not present generated summaries as the original article.</li>' .
		'</ul><!-- /wp:list -->' .
		'<!-- wp:heading {"level":3} --><h3>Restricted Use</h3><!-- /wp:heading -->' .
		'<!-- wp:list --><ul>' .
		'<li>Do not republish full articles without permission.</li>' .
		'<li>Do not use the content to impersonate the site, authors, or affiliated projects.</li>' .
		'<li>Do not bypass access controls, rate limits, or robots directives.</li>' .
		'</ul><!-- /wp:list -->' .
		'<!-- wp:heading {"level":3} --><h3>Machine-Readable Resources</h3><!-- /wp:heading -->' .
		'<!-- wp:paragraph --><p>Canonical site: <a href="' . esc_url( $home_url ) . '">' . esc_html( $home_url ) . '</a></p><!-- /wp:paragraph -->' .
		( function_exists( 'nerv_core_geo_llms_url' ) ? '<!-- wp:paragraph --><p>llms.txt: <a href="' . esc_url( nerv_core_geo_llms_url( false ) ) . '">' . esc_html( nerv_core_geo_llms_url( false ) ) . '</a></p><!-- /wp:paragraph -->' : '' ) .
		( function_exists( 'nerv_core_geo_json_feed_url' ) ? '<!-- wp:paragraph --><p>JSON Feed: <a href="' . esc_url( nerv_core_geo_json_feed_url() ) . '">' . esc_html( nerv_core_geo_json_feed_url() ) . '</a></p><!-- /wp:paragraph -->' : '' ) .
		'<!-- wp:heading {"level":3} --><h3>Contact</h3><!-- /wp:heading -->' .
		'<!-- wp:paragraph --><p>For licensing, removal, or AI indexing questions, contact <a href="mailto:' . esc_attr( $contact ) . '">' . esc_html( $contact ) . '</a>.</p><!-- /wp:paragraph -->';
}

function nerv_core_ai_policy_generate_page(): int {
	$page_id = nerv_core_ai_policy_page_id();
	$data = array(
		'post_title'   => __( 'AI Usage Policy', 'nerv-core' ),
		'post_name'    => nerv_core_ai_policy_page_slug(),
		'post_content' => nerv_core_ai_policy_content(),
		'post_status'  => 'publish',
		'post_type'    => 'page',
	);

	if ( $page_id ) {
		$data['ID'] = $page_id;
		$result = wp_update_post( wp_slash( $data ), true );
	} else {
		$result = wp_insert_post( wp_slash( $data ), true );
	}

	if ( is_wp_error( $result ) ) {
		return 0;
	}

	update_post_meta( (int) $result, '_nerv_subtitle', __( 'Content licensing and citation rules for AI systems.', 'nerv-core' ) );

	return (int) $result;
}

add_action( 'admin_post_nerv_core_generate_ai_policy', 'nerv_core_ai_policy_admin_generate' );
function nerv_core_ai_policy_admin_generate(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to generate the AI policy page.', 'nerv-core' ) );
	}

	check_admin_referer( 'nerv_core_generate_ai_policy' );
	$page_id = nerv_core_ai_policy_generate_page();
	$status  = $page_id ? 'generated' : 'error';
	$url     = add_query_arg(
		array(
			'page'                  => 'nerv-control',
			'nerv_ai_policy_status' => $status,
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $url );
	exit;
}
