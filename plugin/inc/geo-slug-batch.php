<?php
/**
 * Batch SEO/GEO slug optimizer.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_geo_slug_job_default(): array {
	return array(
		'id'          => '',
		'status'      => 'idle',
		'created'     => '',
		'updated'     => '',
		'total'       => 0,
		'processed'   => 0,
		'changed'     => 0,
		'failed'      => 0,
		'batch_size'  => 5,
		'concurrency' => 2,
		'cursor'      => 0,
		'items'       => array(),
		'log'         => array(),
	);
}

function nerv_core_geo_slug_job(): array {
	$job = get_option( 'nerv_core_geo_slug_job', array() );
	if ( ! is_array( $job ) ) {
		$job = array();
	}

	return wp_parse_args( $job, nerv_core_geo_slug_job_default() );
}

function nerv_core_geo_slug_save_job( array $job ): void {
	$job['updated'] = current_time( 'mysql' );
	update_option( 'nerv_core_geo_slug_job', wp_parse_args( $job, nerv_core_geo_slug_job_default() ), false );
}

function nerv_core_geo_slug_status(): array {
	$job = nerv_core_geo_slug_job();
	$remaining = max( 0, absint( $job['total'] ?? 0 ) - absint( $job['processed'] ?? 0 ) );

	return array(
		'id'          => sanitize_text_field( (string) ( $job['id'] ?? '' ) ),
		'status'      => sanitize_key( (string) ( $job['status'] ?? 'idle' ) ),
		'total'       => absint( $job['total'] ?? 0 ),
		'processed'   => absint( $job['processed'] ?? 0 ),
		'changed'     => absint( $job['changed'] ?? 0 ),
		'failed'      => absint( $job['failed'] ?? 0 ),
		'remaining'   => $remaining,
		'batchSize'   => absint( $job['batch_size'] ?? 5 ),
		'concurrency' => absint( $job['concurrency'] ?? 2 ),
		'updated'     => sanitize_text_field( (string) ( $job['updated'] ?? '' ) ),
		'log'         => array_slice( (array) ( $job['log'] ?? array() ), 0, 12 ),
	);
}

function nerv_core_geo_slug_start_job( int $batch_size = 5, int $concurrency = 2 ): array {
	$candidates = nerv_core_geo_title_candidate_posts( 500 );
	$items = array();
	foreach ( $candidates as $row ) {
		$items[] = array(
			'id'      => absint( $row['id'] ?? 0 ),
			'title'   => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
			'oldSlug' => sanitize_title( (string) ( $row['slug'] ?? '' ) ),
			'reason'  => sanitize_text_field( (string) ( $row['reason'] ?? '' ) ),
			'status'  => 'pending',
		);
	}

	$job = nerv_core_geo_slug_job_default();
	$job['id'] = 'geo-slug-' . gmdate( 'YmdHis' );
	$job['status'] = $items ? 'running' : 'complete';
	$job['created'] = current_time( 'mysql' );
	$job['updated'] = $job['created'];
	$job['total'] = count( $items );
	$job['batch_size'] = min( 25, max( 1, $batch_size ) );
	$job['concurrency'] = min( 8, max( 1, $concurrency ) );
	$job['items'] = $items;
	$job['log'][] = nerv_core_geo_slug_log_row( 'info', sprintf( '创建任务：%d 篇待优化。', count( $items ) ) );
	nerv_core_geo_slug_save_job( $job );
	nerv_core_geo_slug_schedule();

	return nerv_core_geo_slug_status();
}

function nerv_core_geo_slug_pause_job(): array {
	$job = nerv_core_geo_slug_job();
	if ( 'running' === (string) ( $job['status'] ?? '' ) ) {
		$job['status'] = 'paused';
		$job['log'][] = nerv_core_geo_slug_log_row( 'info', 'GEO slug 挂机任务已暂停。' );
		nerv_core_geo_slug_save_job( $job );
	}

	return nerv_core_geo_slug_status();
}

function nerv_core_geo_slug_resume_job(): array {
	$job = nerv_core_geo_slug_job();
	if ( 'paused' === (string) ( $job['status'] ?? '' ) ) {
		$job['status'] = 'running';
		$job['log'][] = nerv_core_geo_slug_log_row( 'info', 'GEO slug 挂机任务已恢复。' );
		nerv_core_geo_slug_save_job( $job );
		nerv_core_geo_slug_schedule();
	}

	return nerv_core_geo_slug_status();
}

function nerv_core_geo_slug_stop_job(): array {
	$job = nerv_core_geo_slug_job();
	if ( in_array( (string) ( $job['status'] ?? '' ), array( 'running', 'paused' ), true ) ) {
		$job['status'] = 'stopped';
		$job['log'][] = nerv_core_geo_slug_log_row( 'warning', 'GEO slug 挂机任务已停止。' );
		nerv_core_geo_slug_save_job( $job );
	}

	return nerv_core_geo_slug_status();
}

function nerv_core_geo_slug_schedule(): void {
	if ( ! wp_next_scheduled( 'nerv_core_geo_slug_batch_tick' ) ) {
		wp_schedule_single_event( time() + 5, 'nerv_core_geo_slug_batch_tick' );
	}
}

add_action( 'nerv_core_geo_slug_batch_tick', 'nerv_core_geo_slug_run_batch' );
function nerv_core_geo_slug_run_batch(): void {
	$job = nerv_core_geo_slug_job();
	if ( 'running' !== (string) ( $job['status'] ?? '' ) ) {
		return;
	}

	$limit = min( absint( $job['batch_size'] ?? 5 ) * absint( $job['concurrency'] ?? 2 ), 50 );
	$done = 0;
	foreach ( (array) ( $job['items'] ?? array() ) as $index => $item ) {
		if ( $done >= $limit ) {
			break;
		}
		if ( 'pending' !== (string) ( $item['status'] ?? '' ) ) {
			continue;
		}

		$result = nerv_core_geo_slug_process_post( absint( $item['id'] ?? 0 ) );
		$job['items'][ $index ]['status'] = empty( $result['ok'] ) ? 'failed' : 'changed';
		$job['items'][ $index ]['newSlug'] = sanitize_title( (string) ( $result['slug'] ?? '' ) );
		$job['items'][ $index ]['message'] = sanitize_text_field( (string) ( $result['message'] ?? '' ) );
		$job['processed'] = absint( $job['processed'] ?? 0 ) + 1;
		if ( empty( $result['ok'] ) ) {
			$job['failed'] = absint( $job['failed'] ?? 0 ) + 1;
			$job['log'][] = nerv_core_geo_slug_log_row( 'warning', (string) ( $result['message'] ?? '处理失败。' ) );
		} else {
			$job['changed'] = absint( $job['changed'] ?? 0 ) + 1;
			$job['log'][] = nerv_core_geo_slug_log_row( 'success', (string) ( $result['message'] ?? '已更新 slug。' ) );
		}
		++$done;
	}

	if ( absint( $job['processed'] ?? 0 ) >= absint( $job['total'] ?? 0 ) ) {
		$job['status'] = 'complete';
		$job['log'][] = nerv_core_geo_slug_log_row( 'info', 'GEO slug 挂机任务完成。' );
	} else {
		nerv_core_geo_slug_schedule();
	}

	$job['log'] = array_slice( array_reverse( array_reverse( (array) $job['log'] ) ), -60 );
	nerv_core_geo_slug_save_job( $job );
}

add_action( 'nerv_core_geo_slug_retry_post', 'nerv_core_geo_slug_retry_post', 10, 1 );
function nerv_core_geo_slug_retry_post( int $post_id ): void {
	$job = nerv_core_geo_slug_job();
	foreach ( (array) ( $job['items'] ?? array() ) as $index => $item ) {
		if ( absint( $item['id'] ?? 0 ) !== $post_id || 'failed' !== (string) ( $item['status'] ?? '' ) ) {
			continue;
		}
		$job['items'][ $index ]['status'] = 'pending';
		$job['items'][ $index ]['message'] = '429/5xx 后自动重拾，重新排队。';
		$job['processed'] = max( 0, absint( $job['processed'] ?? 0 ) - 1 );
		$job['failed'] = max( 0, absint( $job['failed'] ?? 0 ) - 1 );
		if ( ! in_array( (string) ( $job['status'] ?? '' ), array( 'running', 'paused' ), true ) ) {
			$job['status'] = 'running';
		}
		$job['log'][] = nerv_core_geo_slug_log_row( 'info', '文章 #' . $post_id . ' 已自动重拾到待处理队列。' );
		nerv_core_geo_slug_save_job( $job );
		nerv_core_geo_slug_schedule();
		return;
	}
}

function nerv_core_geo_slug_process_post( int $post_id ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		return array( 'ok' => false, 'message' => '文章不存在或不是已发布状态。' );
	}

	$old_slug = (string) $post->post_name;
	$evaluation = nerv_core_geo_title_evaluate_slug( $old_slug );
	if ( ! empty( $evaluation['compliant'] ) ) {
		return array( 'ok' => true, 'slug' => $old_slug, 'message' => '已符合 SEO/GEO slug 规则，跳过。' );
	}

	$suggestion = nerv_core_geo_title_suggest_for_post( $post );
	if ( is_wp_error( $suggestion ) ) {
		nerv_core_geo_slug_retry_maybe_schedule( $post_id, $suggestion );
		if ( function_exists( 'nerv_core_ai_usage_record' ) ) {
			nerv_core_ai_usage_record( 'geo_slug', 'error', true );
		}
		return array( 'ok' => false, 'message' => get_the_title( $post ) . '：' . $suggestion->get_error_message() );
	}

	$new_slug = nerv_core_geo_title_sanitize_slug( (string) $suggestion, $post_id );
	if ( '' === $new_slug ) {
		return array( 'ok' => false, 'message' => get_the_title( $post ) . '：无法生成有效 slug。' );
	}

	$old_url          = get_permalink( $post );
	$old_markdown_url = function_exists( 'nerv_core_geo_markdown_url' ) ? nerv_core_geo_markdown_url( $post_id ) : '';
	$updated          = wp_update_post(
		array(
			'ID'        => $post_id,
			'post_name' => $new_slug,
		),
		true
	);
	if ( is_wp_error( $updated ) ) {
		return array( 'ok' => false, 'message' => get_the_title( $post ) . '：' . $updated->get_error_message() );
	}

	add_post_meta( $post_id, '_wp_old_slug', sanitize_title( $old_slug ) );
	add_post_meta(
		$post_id,
		'_nerv_geo_slug_redirect',
		array(
			'old_slug' => sanitize_title( $old_slug ),
			'old_url'  => esc_url_raw( $old_url ),
			'new_slug' => sanitize_title( $new_slug ),
			'new_url'  => esc_url_raw( get_permalink( $post_id ) ),
			'time'     => current_time( 'mysql' ),
		)
	);
	nerv_core_geo_slug_redirect_map_add( $old_url, get_permalink( $post_id ), $post_id );
	if ( $old_markdown_url && function_exists( 'nerv_core_geo_markdown_url' ) ) {
		nerv_core_geo_slug_redirect_map_add( $old_markdown_url, nerv_core_geo_markdown_url( $post_id ), $post_id );
	}
	if ( function_exists( 'nerv_core_ai_usage_record' ) ) {
		nerv_core_ai_usage_record( 'geo_slug', 'success', true );
	}

	return array( 'ok' => true, 'slug' => $new_slug, 'message' => get_the_title( $post_id ) . '：' . $old_slug . ' -> ' . $new_slug );
}

function nerv_core_geo_slug_retry_maybe_schedule( int $post_id, WP_Error $error ): void {
	$data = $error->get_error_data();
	$status = is_array( $data ) ? absint( $data['status'] ?? 0 ) : 0;
	if ( 429 !== $status && $status < 500 ) {
		return;
	}

	$delay = function_exists( 'nerv_core_ai_retry_delay_seconds' ) ? nerv_core_ai_retry_delay_seconds( is_array( $data ) ? (string) ( $data['retry_after'] ?? '' ) : '', $post_id ) : min( 6 * HOUR_IN_SECONDS, 300 + ( $post_id % 180 ) );
	$args = array( $post_id );
	if ( ! wp_next_scheduled( 'nerv_core_geo_slug_retry_post', $args ) ) {
		wp_schedule_single_event( time() + $delay, 'nerv_core_geo_slug_retry_post', $args );
	}
}

function nerv_core_geo_slug_redirect_map(): array {
	$map = get_option( 'nerv_core_geo_slug_redirect_map', array() );
	return is_array( $map ) ? $map : array();
}

function nerv_core_geo_slug_redirect_map_add( string $old_url, string $new_url, int $post_id ): void {
	$old_path = nerv_core_geo_slug_url_path( $old_url );
	$new_path = nerv_core_geo_slug_url_path( $new_url );
	if ( '' === $old_path || '' === $new_path || $old_path === $new_path ) {
		return;
	}

	$map = nerv_core_geo_slug_redirect_map();
	$map[ $old_path ] = array(
		'post_id' => absint( $post_id ),
		'new_url' => esc_url_raw( $new_url ),
		'time'    => current_time( 'mysql' ),
	);
	update_option( 'nerv_core_geo_slug_redirect_map', array_slice( $map, -5000, null, true ), false );
}

function nerv_core_geo_slug_url_path( string $url ): string {
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	return trim( rawurldecode( $path ), '/' );
}

add_action( 'template_redirect', 'nerv_core_geo_slug_redirect_old_url', 1 );
function nerv_core_geo_slug_redirect_old_url(): void {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	$request_path = trim( rawurldecode( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ) ), '/' );
	if ( '' === $request_path ) {
		return;
	}

	$map = nerv_core_geo_slug_redirect_map();
	if ( empty( $map[ $request_path ]['new_url'] ) ) {
		return;
	}

	wp_safe_redirect( esc_url_raw( (string) $map[ $request_path ]['new_url'] ), 301 );
	exit;
}

function nerv_core_geo_title_suggest_for_post( WP_Post $post ) {
	$options = nerv_core_geo_title_options();
	$ai_options = function_exists( 'nerv_core_ai_feature_options' ) ? nerv_core_ai_feature_options( 'text' ) : array();
	$prompt = strtr( (string) ( $options['prompt_template'] ?? '' ), array( '{title}' => get_the_title( $post ) ) );
	$response = function_exists( 'nerv_core_ai_chat_request' ) ? nerv_core_ai_chat_request( 'You convert WordPress titles into clean English URL slugs. Output only the slug.', $prompt, $ai_options, array( 'service' => 'geo_slug', 'error_prefix' => 'nerv_geo_slug' ) ) : new WP_Error( 'nerv_geo_slug_ai_missing', 'AI 路由不可用。' );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$text = '';
	if ( isset( $response['choices'][0]['message']['content'] ) ) {
		$text = nerv_core_ai_response_text_value( $response['choices'][0]['message']['content'] );
	} elseif ( isset( $response['output_text'] ) ) {
		$text = (string) $response['output_text'];
	} elseif ( isset( $response['text'] ) ) {
		$text = nerv_core_ai_response_text_value( $response['text'] );
	} elseif ( isset( $response['output'] ) && is_array( $response['output'] ) ) {
		$text = nerv_core_ai_response_output_text( $response['output'] );
	}

	return '' === trim( $text ) ? new WP_Error( 'nerv_geo_slug_empty', 'AI 没有返回 slug。' ) : $text;
}

function nerv_core_geo_slug_log_row( string $state, string $message ): array {
	return array(
		'time'    => current_time( 'mysql' ),
		'state'   => sanitize_key( $state ),
		'message' => sanitize_text_field( $message ),
	);
}
