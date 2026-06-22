<?php
/**
 * GEO-oriented editor blocks.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'nerv_core_register_blocks' );
function nerv_core_register_blocks(): void {
	wp_register_script(
		'nerv-core-blocks',
		NERV_CORE_URL . 'assets/js/blocks.js',
		array( 'wp-blocks', 'wp-components', 'wp-compose', 'wp-data', 'wp-edit-post', 'wp-element', 'wp-i18n', 'wp-plugins' ),
		NERV_CORE_VERSION,
		true
	);

	wp_set_script_translations( 'nerv-core-blocks', 'nerv-core', NERV_CORE_DIR . 'languages' );
	if ( function_exists( 'nerv_core_zh_cn_js_locale_data' ) && nerv_core_should_use_zh_cn_fallback() ) {
		wp_add_inline_script(
			'nerv-core-blocks',
			'wp.i18n.setLocaleData(' . wp_json_encode( nerv_core_zh_cn_js_locale_data(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ', "nerv-core");',
			'before'
		);
	}

	wp_localize_script(
		'nerv-core-blocks',
		'nervCoreEditor',
		array(
			'coverRestBase'     => esc_url_raw( rest_url( 'nerv-core/v1/cover-preview/' ) ),
			'coverGenerateBase' => esc_url_raw( rest_url( 'nerv-core/v1/cover-generate/' ) ),
			'coverRestoreBase'  => esc_url_raw( rest_url( 'nerv-core/v1/cover-restore/' ) ),
			'keyPointsGenerateBase' => esc_url_raw( rest_url( 'nerv-core/v1/key-points-generate/' ) ),
			'geoScoreBase'      => esc_url_raw( rest_url( 'nerv-core/v1/geo-score/' ) ),
			'geoWeightsPath'    => esc_url_raw( rest_url( 'nerv-core/v1/geo-score-weights' ) ),
			'nonce'             => wp_create_nonce( 'wp_rest' ),
		)
	);

	register_block_type(
		'nerv-core/key-points',
		array(
			'api_version'     => 3,
			'editor_script'   => 'nerv-core-blocks',
			'render_callback' => 'nerv_core_render_key_points_block',
			'attributes'      => array(
				'title'  => array(
					'type'    => 'string',
					'default' => __( 'KEY POINTS / 要点提取', 'nerv-core' ),
				),
				'points' => array(
					'type'    => 'array',
					'default' => array(
						__( 'State the main answer in one clear sentence.', 'nerv-core' ),
						__( 'Keep each point short enough for AI summaries.', 'nerv-core' ),
						__( 'Link the article to a concrete operating context.', 'nerv-core' ),
					),
					'items'   => array(
						'type' => 'string',
					),
				),
			),
		)
	);

	register_block_type(
		'nerv-core/faq',
		array(
			'api_version'     => 3,
			'editor_script'   => 'nerv-core-blocks',
			'render_callback' => 'nerv_core_render_faq_block',
			'attributes'      => array(
				'title' => array(
					'type'    => 'string',
					'default' => __( 'FAQ / よくある質問', 'nerv-core' ),
				),
				'items' => array(
					'type'    => 'array',
					'default' => array(
						array(
							'question' => __( 'What should readers remember first?', 'nerv-core' ),
							'answer'   => __( 'Give a direct answer that can stand alone in search and AI summaries.', 'nerv-core' ),
						),
					),
					'items'   => array(
						'type' => 'object',
					),
				),
			),
		)
	);
}

add_action( 'enqueue_block_editor_assets', 'nerv_core_enqueue_editor_assets' );
function nerv_core_enqueue_editor_assets(): void {
	wp_enqueue_script( 'nerv-core-blocks' );

	wp_enqueue_style(
		'nerv-core-editor',
		NERV_CORE_URL . 'assets/css/editor.css',
		array( 'wp-edit-blocks' ),
		NERV_CORE_VERSION
	);
}

add_action( 'rest_api_init', 'nerv_core_register_cover_preview_rest' );
function nerv_core_register_cover_preview_rest(): void {
	register_rest_route(
		'nerv-core/v1',
		'/cover-preview/(?P<id>\d+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'nerv_core_rest_cover_preview',
			'permission_callback' => static function ( WP_REST_Request $request ): bool {
				return current_user_can( 'edit_post', absint( $request['id'] ) );
			},
			'args'                => array(
				'id' => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/cover-generate/(?P<id>\d+)',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_cover_generate',
			'permission_callback' => static function ( WP_REST_Request $request ): bool {
				return current_user_can( 'edit_post', absint( $request['id'] ) );
			},
			'args'                => array(
				'id' => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/cover-restore/(?P<id>\d+)',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_cover_restore',
			'permission_callback' => static function ( WP_REST_Request $request ): bool {
				return current_user_can( 'edit_post', absint( $request['id'] ) );
			},
			'args'                => array(
				'id'    => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'index' => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		'nerv-core/v1',
		'/key-points-generate/(?P<id>\d+)',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nerv_core_rest_key_points_generate',
			'permission_callback' => static function ( WP_REST_Request $request ): bool {
				return current_user_can( 'edit_post', absint( $request['id'] ) );
			},
			'args'                => array(
				'id' => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}

function nerv_core_rest_cover_preview( WP_REST_Request $request ): WP_REST_Response {
	$post_id = absint( $request['id'] );
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return new WP_REST_Response( array( 'message' => __( 'Post not found.', 'nerv-core' ) ), 404 );
	}

	$source = function_exists( 'nerv_core_cover_source' ) ? nerv_core_cover_source( $post_id ) : ( has_post_thumbnail( $post_id ) ? 'upload' : 'none' );
	$data = array(
		'postId'       => $post_id,
		'title'        => get_the_title( $post ),
		'source'       => $source,
		'sourceLabel'  => strtoupper( $source ),
		'ratio5x2'     => function_exists( 'nerv_core_cover_url' ) ? nerv_core_cover_url( $post_id, '5x2' ) : ( get_the_post_thumbnail_url( $post_id, 'nerv-cover' ) ?: '' ),
		'ratio2x1'     => function_exists( 'nerv_core_cover_url' ) ? nerv_core_cover_url( $post_id, '2x1' ) : ( get_the_post_thumbnail_url( $post_id, 'nerv-og' ) ?: '' ),
		'prompt'       => function_exists( 'nerv_core_cover_render_prompt' ) ? nerv_core_cover_render_prompt( $post ) : '',
		'status'       => function_exists( 'nerv_core_cover_status' ) ? nerv_core_cover_status() : array(),
		'generatedUrl' => function_exists( 'nerv_core_cover_generated_url' ) ? nerv_core_cover_generated_url( $post_id ) : '',
		'history'      => function_exists( 'nerv_core_cover_history' ) ? nerv_core_cover_history( $post_id ) : array(),
	);

	return new WP_REST_Response( $data );
}

function nerv_core_rest_cover_generate( WP_REST_Request $request ): WP_REST_Response {
	$post_id = absint( $request['id'] );
	if ( ! function_exists( 'nerv_core_cover_generate' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'Cover generator is unavailable.', 'nerv-core' ) ), 500 );
	}

	$result = nerv_core_cover_generate( $post_id, 'editor' );
	$status = 'success' === ( $result['status'] ?? '' ) || 'dry-run' === ( $result['status'] ?? '' ) ? 200 : 400;
	$preview = new WP_REST_Request( 'GET', '/nerv-core/v1/cover-preview/' . $post_id );
	$preview->set_param( 'id', $post_id );
	$preview_response = nerv_core_rest_cover_preview( $preview );
	$preview_data = $preview_response instanceof WP_REST_Response ? $preview_response->get_data() : array();

	return new WP_REST_Response(
		array(
			'result'  => $result,
			'preview' => is_array( $preview_data ) ? $preview_data : array(),
		),
		$status
	);
}

function nerv_core_rest_cover_restore( WP_REST_Request $request ): WP_REST_Response {
	$post_id = absint( $request['id'] );
	if ( ! function_exists( 'nerv_core_cover_restore_history' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'Cover history restore is unavailable.', 'nerv-core' ) ), 500 );
	}

	$result = nerv_core_cover_restore_history( $post_id, absint( $request['index'] ) );
	$status = 'restored' === ( $result['status'] ?? '' ) ? 200 : 400;
	$preview = new WP_REST_Request( 'GET', '/nerv-core/v1/cover-preview/' . $post_id );
	$preview->set_param( 'id', $post_id );
	$preview_response = nerv_core_rest_cover_preview( $preview );
	$preview_data = $preview_response instanceof WP_REST_Response ? $preview_response->get_data() : array();

	return new WP_REST_Response(
		array(
			'result'  => $result,
			'preview' => is_array( $preview_data ) ? $preview_data : array(),
		),
		$status
	);
}

function nerv_core_rest_key_points_generate( WP_REST_Request $request ): WP_REST_Response {
	$post_id = absint( $request['id'] );
	if ( ! function_exists( 'nerv_core_key_points_generate' ) ) {
		return new WP_REST_Response( array( 'message' => __( 'KEY POINTS generator is unavailable.', 'nerv-core' ) ), 500 );
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}

	$meta = is_array( $params['meta'] ?? null ) ? $params['meta'] : array();
	$result = nerv_core_key_points_generate(
		$post_id,
		array(
			'content'  => array_key_exists( 'content', $params ) ? (string) $params['content'] : '',
			'title'    => array_key_exists( 'title', $params ) ? (string) $params['title'] : '',
			'subtitle' => array_key_exists( 'subtitle', $params ) ? (string) $params['subtitle'] : (string) ( $meta['_nerv_subtitle'] ?? '' ),
		)
	);

	$status = in_array( (string) ( $result['status'] ?? '' ), array( 'success', 'dry-run', 'local' ), true ) ? 200 : 400;
	return new WP_REST_Response( $result, $status );
}

function nerv_core_render_key_points_block( array $attributes ): string {
	$title  = isset( $attributes['title'] ) ? sanitize_text_field( (string) $attributes['title'] ) : __( 'KEY POINTS / 要点提取', 'nerv-core' );
	$points = nerv_core_clean_string_list( $attributes['points'] ?? array() );
	if ( ! $points ) {
		return '';
	}

	$items = '';
	foreach ( $points as $point ) {
		$items .= '<li>' . esc_html( $point ) . '</li>';
	}

	return '<section class="nerv-geo-block nerv-key-points"><div class="nerv-geo-block__heading"><span>GEO</span><h2>' . esc_html( $title ) . '</h2></div><ol>' . $items . '</ol></section>';
}

function nerv_core_render_faq_block( array $attributes ): string {
	$title = isset( $attributes['title'] ) ? sanitize_text_field( (string) $attributes['title'] ) : __( 'FAQ / よくある質問', 'nerv-core' );
	$items = nerv_core_clean_faq_items( $attributes['items'] ?? array() );
	if ( ! $items ) {
		return '';
	}

	$html = '';
	foreach ( $items as $item ) {
		$html .= '<details class="nerv-faq-item" open><summary>' . esc_html( $item['question'] ) . '</summary><p>' . esc_html( $item['answer'] ) . '</p></details>';
	}

	return '<section class="nerv-geo-block nerv-faq"><div class="nerv-geo-block__heading"><span>SCHEMA</span><h2>' . esc_html( $title ) . '</h2></div>' . $html . '</section>';
}

function nerv_core_clean_string_list( $items ): array {
	if ( ! is_array( $items ) ) {
		return array();
	}

	$clean = array();
	foreach ( $items as $item ) {
		$value = sanitize_text_field( (string) $item );
		if ( '' !== $value ) {
			$clean[] = $value;
		}
	}

	return array_values( array_slice( $clean, 0, 8 ) );
}

function nerv_core_clean_faq_items( $items ): array {
	if ( ! is_array( $items ) ) {
		return array();
	}

	$clean = array();
	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$question = sanitize_text_field( (string) ( $item['question'] ?? '' ) );
		$answer   = sanitize_textarea_field( (string) ( $item['answer'] ?? '' ) );
		if ( '' === $question || '' === $answer ) {
			continue;
		}

		$clean[] = array(
			'question' => $question,
			'answer'   => $answer,
		);
	}

	return array_values( array_slice( $clean, 0, 12 ) );
}

function nerv_core_extract_faq_items_from_post( WP_Post $post ): array {
	$blocks = parse_blocks( $post->post_content );

	return nerv_core_extract_faq_items_from_blocks( $blocks );
}

function nerv_core_extract_faq_items_from_blocks( array $blocks ): array {
	$items = array();
	foreach ( $blocks as $block ) {
		if ( 'nerv-core/faq' === ( $block['blockName'] ?? '' ) ) {
			$items = array_merge( $items, nerv_core_clean_faq_items( $block['attrs']['items'] ?? array() ) );
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			$items = array_merge( $items, nerv_core_extract_faq_items_from_blocks( $block['innerBlocks'] ) );
		}
	}

	return array_values( $items );
}
