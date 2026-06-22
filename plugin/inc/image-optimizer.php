<?php
/**
 * WebP conversion and social image helpers.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_image_optimizer_default_options(): array {
	return array(
		'enabled'       => true,
		'quality'       => 82,
		'keep_original' => true,
	);
}

function nerv_core_image_optimizer_options(): array {
	$options = get_option( 'nerv_core_image_optimizer_options', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	$options = wp_parse_args( $options, nerv_core_image_optimizer_default_options() );
	$options['enabled'] = ! empty( $options['enabled'] );
	$options['quality'] = min( 95, max( 40, absint( $options['quality'] ?? 82 ) ) );
	$options['keep_original'] = ! empty( $options['keep_original'] );

	return $options;
}

add_filter( 'wp_generate_attachment_metadata', 'nerv_core_image_optimizer_generate_webp', 20, 2 );
function nerv_core_image_optimizer_generate_webp( array $metadata, int $attachment_id ): array {
	$options = nerv_core_image_optimizer_options();
	if ( empty( $options['enabled'] ) || ! wp_attachment_is_image( $attachment_id ) ) {
		return $metadata;
	}

	$file = get_attached_file( $attachment_id );
	if ( ! $file || ! is_file( $file ) ) {
		return $metadata;
	}

	$converted = nerv_core_image_optimizer_convert_file( $file, $options['quality'] );
	if ( $converted ) {
		update_post_meta( $attachment_id, '_nerv_webp_full', $converted );
	}

	$upload_dir = wp_get_upload_dir();
	$base_dir = trailingslashit( (string) ( $upload_dir['basedir'] ?? '' ) );
	$subdir = isset( $metadata['file'] ) ? trailingslashit( dirname( (string) $metadata['file'] ) ) : '';
	if ( '.' === trim( $subdir, '/' ) ) {
		$subdir = '';
	}

	foreach ( (array) ( $metadata['sizes'] ?? array() ) as $size => $row ) {
		if ( ! is_array( $row ) || empty( $row['file'] ) ) {
			continue;
		}
		$size_file = $base_dir . $subdir . (string) $row['file'];
		$size_webp = nerv_core_image_optimizer_convert_file( $size_file, $options['quality'] );
		if ( $size_webp ) {
			$metadata['sizes'][ $size ]['nerv_webp_file'] = basename( $size_webp );
			$metadata['sizes'][ $size ]['nerv_webp_mime_type'] = 'image/webp';
		}
	}

	return $metadata;
}

function nerv_core_image_optimizer_convert_file( string $file, int $quality = 82 ): string {
	if ( ! is_file( $file ) || ! function_exists( 'wp_get_image_editor' ) ) {
		return '';
	}

	$mime = wp_check_filetype( $file );
	if ( 'image/webp' === (string) ( $mime['type'] ?? '' ) ) {
		return $file;
	}
	if ( ! in_array( (string) ( $mime['type'] ?? '' ), array( 'image/jpeg', 'image/png' ), true ) ) {
		return '';
	}

	$target = preg_replace( '/\.[^.]+$/', '.webp', $file ) ?: ( $file . '.webp' );
	$editor = wp_get_image_editor( $file );
	if ( is_wp_error( $editor ) ) {
		return '';
	}
	if ( method_exists( $editor, 'set_quality' ) ) {
		$editor->set_quality( $quality );
	}

	$result = $editor->save( $target, 'image/webp' );
	return is_wp_error( $result ) || empty( $result['path'] ) ? '' : (string) $result['path'];
}

function nerv_core_image_optimizer_webp_url( int $attachment_id, string $size = 'full' ): string {
	$source = wp_get_attachment_image_src( $attachment_id, $size );
	if ( ! is_array( $source ) || empty( $source[0] ) ) {
		return '';
	}

	$url = (string) $source[0];
	$path = nerv_core_image_optimizer_path_from_url( $url );
	if ( ! $path || ! is_file( $path ) ) {
		return esc_url_raw( $url );
	}

	$webp = preg_replace( '/\.[^.]+$/', '.webp', $path ) ?: '';
	if ( $webp && is_file( $webp ) ) {
		$upload_dir = wp_get_upload_dir();
		$base_dir = trailingslashit( (string) ( $upload_dir['basedir'] ?? '' ) );
		$base_url = trailingslashit( (string) ( $upload_dir['baseurl'] ?? '' ) );
		if ( str_starts_with( $webp, $base_dir ) ) {
			return esc_url_raw( $base_url . ltrim( substr( $webp, strlen( $base_dir ) ), '/' ) );
		}
	}

	return esc_url_raw( $url );
}

function nerv_core_image_optimizer_path_from_url( string $url ): string {
	$upload_dir = wp_get_upload_dir();
	$base_url = trailingslashit( (string) ( $upload_dir['baseurl'] ?? '' ) );
	$base_dir = trailingslashit( (string) ( $upload_dir['basedir'] ?? '' ) );
	if ( '' === $base_url || '' === $base_dir || ! str_starts_with( $url, $base_url ) ) {
		return '';
	}

	return $base_dir . ltrim( substr( $url, strlen( $base_url ) ), '/' );
}

function nerv_core_image_optimizer_attachment_social_url( int $attachment_id ): string {
	if ( ! $attachment_id ) {
		return '';
	}

	$webp = nerv_core_image_optimizer_webp_url( $attachment_id, 'nerv-og' );
	if ( $webp ) {
		return $webp;
	}

	$url = wp_get_attachment_image_url( $attachment_id, 'nerv-og' );
	return $url ? esc_url_raw( $url ) : '';
}

function nerv_core_image_optimizer_attachment_url( int $attachment_id, string $size = 'full' ): string {
	if ( ! $attachment_id ) {
		return '';
	}

	$webp = nerv_core_image_optimizer_webp_url( $attachment_id, $size );
	if ( $webp ) {
		return $webp;
	}

	$url = wp_get_attachment_image_url( $attachment_id, $size );
	return $url ? esc_url_raw( $url ) : '';
}

function nerv_core_image_optimizer_social_cover_url( int $post_id ): string {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$upload_dir = wp_get_upload_dir();
	$base_dir = trailingslashit( (string) ( $upload_dir['basedir'] ?? '' ) ) . 'nerv-social-covers';
	$base_url = trailingslashit( (string) ( $upload_dir['baseurl'] ?? '' ) ) . 'nerv-social-covers';
	if ( '' === $base_dir || '' === $base_url ) {
		return '';
	}
	if ( ! wp_mkdir_p( $base_dir ) ) {
		return '';
	}

	$modified = (string) get_post_modified_time( 'U', true, $post );
	$filename = 'post-' . $post_id . '-' . substr( md5( $post->post_title . ':' . $modified ), 0, 10 ) . '.webp';
	$path = trailingslashit( $base_dir ) . $filename;
	if ( ! is_file( $path ) && ! nerv_core_image_optimizer_render_social_cover( $post, $path ) ) {
		return '';
	}

	return esc_url_raw( trailingslashit( $base_url ) . $filename );
}

function nerv_core_image_optimizer_render_social_cover( WP_Post $post, string $path ): bool {
	if ( ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'imagewebp' ) ) {
		return false;
	}

	$width = 1200;
	$height = 600;
	$image = imagecreatetruecolor( $width, $height );
	if ( ! $image ) {
		return false;
	}

	$bg = imagecolorallocate( $image, 246, 247, 247 );
	$ink = imagecolorallocate( $image, 29, 35, 39 );
	$muted = imagecolorallocate( $image, 80, 87, 94 );
	$line = imagecolorallocate( $image, 220, 220, 222 );
	$accent = imagecolorallocate( $image, 34, 113, 177 );
	imagefilledrectangle( $image, 0, 0, $width, $height, $bg );
	imagefilledrectangle( $image, 0, 0, 22, $height, $accent );
	imagefilledrectangle( $image, 88, 90, $width - 88, 92, $line );
	imagefilledrectangle( $image, 88, $height - 92, $width - 88, $height - 90, $line );

	$font = nerv_core_image_optimizer_social_font();
	$title = html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, get_option( 'blog_charset' ) );
	$site = html_entity_decode( wp_strip_all_tags( get_bloginfo( 'name' ) ), ENT_QUOTES, get_option( 'blog_charset' ) );
	$date = get_the_date( 'Y-m-d', $post );

	if ( $font && function_exists( 'imagettftext' ) ) {
		nerv_core_image_optimizer_ttf_lines( $image, $title, $font, 42, 96, 185, 980, $ink, 3 );
		imagettftext( $image, 20, 0, 96, 122, $muted, $font, $site );
		imagettftext( $image, 18, 0, 96, 510, $muted, $font, $date . ' / GEO READY' );
	} else {
		imagestring( $image, 5, 96, 120, substr( $site, 0, 48 ), $muted );
		imagestring( $image, 5, 96, 210, substr( $title, 0, 90 ), $ink );
		imagestring( $image, 4, 96, 510, $date . ' / GEO READY', $muted );
	}

	$result = imagewebp( $image, $path, nerv_core_image_optimizer_options()['quality'] );
	imagedestroy( $image );

	return (bool) $result;
}

function nerv_core_image_optimizer_social_font(): string {
	$fonts = array(
		'/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',
		'/usr/share/fonts/truetype/noto/NotoSansCJK-Regular.ttc',
		'/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
		'/System/Library/Fonts/PingFang.ttc',
	);
	foreach ( $fonts as $font ) {
		if ( is_file( $font ) ) {
			return $font;
		}
	}

	return '';
}

function nerv_core_image_optimizer_ttf_lines( $image, string $text, string $font, int $size, int $x, int $y, int $max_width, int $color, int $max_lines ): void {
	$words = preg_split( '/\s+/u', trim( $text ) );
	if ( ! is_array( $words ) || count( $words ) < 2 ) {
		$words = preg_split( '//u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) ?: array( $text );
	}

	$lines = array();
	$current = '';
	foreach ( $words as $word ) {
		$test = '' === $current ? $word : $current . ( strlen( $word ) > 1 ? ' ' : '' ) . $word;
		$box = imagettfbbox( $size, 0, $font, $test );
		$line_width = is_array( $box ) ? absint( ( $box[2] ?? 0 ) - ( $box[0] ?? 0 ) ) : 0;
		if ( $line_width > $max_width && '' !== $current ) {
			$lines[] = $current;
			$current = $word;
			if ( count( $lines ) >= $max_lines ) {
				break;
			}
		} else {
			$current = $test;
		}
	}
	if ( '' !== $current && count( $lines ) < $max_lines ) {
		$lines[] = $current;
	}

	foreach ( $lines as $index => $line ) {
		imagettftext( $image, $size, 0, $x, $y + ( $index * ( $size + 16 ) ), $color, $font, $line );
	}
}
