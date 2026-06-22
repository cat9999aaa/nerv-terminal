<?php
/**
 * Shared post meta fields for NERV content.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'nerv_core_register_meta_fields' );
function nerv_core_register_meta_fields(): void {
	$post_types = array( 'post', 'page', 'project', 'partner' );

	foreach ( $post_types as $post_type ) {
		register_post_meta(
			$post_type,
			'_nerv_subtitle',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	register_post_meta(
		'partner',
		'_nerv_partner_url',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
			'auth_callback'     => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	register_post_meta(
		'partner',
		'_nerv_partner_rel',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_key',
			'auth_callback'     => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	register_post_meta(
		'partner',
		'_nerv_partner_featured',
		array(
			'type'              => 'boolean',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'auth_callback'     => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}

add_action( 'add_meta_boxes', 'nerv_core_add_meta_boxes' );
function nerv_core_add_meta_boxes(): void {
	foreach ( array( 'post', 'page', 'project', 'partner' ) as $post_type ) {
		add_meta_box(
			'nerv-core-metadata',
			__( 'NERV METADATA', 'nerv-core' ),
			'nerv_core_render_metadata_box',
			$post_type,
			'side',
			'default'
		);
	}
}

function nerv_core_render_metadata_box( WP_Post $post ): void {
	wp_nonce_field( 'nerv_core_save_metadata', 'nerv_core_metadata_nonce' );
	$subtitle = get_post_meta( $post->ID, '_nerv_subtitle', true );
	$url      = get_post_meta( $post->ID, '_nerv_partner_url', true );
	$rel      = get_post_meta( $post->ID, '_nerv_partner_rel', true ) ?: 'follow';
	$featured = (bool) get_post_meta( $post->ID, '_nerv_partner_featured', true );
	?>
	<p>
		<label for="nerv-core-subtitle"><strong><?php esc_html_e( 'Subtitle', 'nerv-core' ); ?></strong></label>
		<input id="nerv-core-subtitle" class="widefat" type="text" name="nerv_core_subtitle" value="<?php echo esc_attr( $subtitle ); ?>" placeholder="<?php esc_attr_e( 'Alternative headline for entries and GEO metadata.', 'nerv-core' ); ?>">
	</p>
	<?php if ( 'partner' === $post->post_type ) : ?>
		<p>
			<label for="nerv-core-partner-url"><strong><?php esc_html_e( 'Partner URL', 'nerv-core' ); ?></strong></label>
			<input id="nerv-core-partner-url" class="widefat" type="url" name="nerv_core_partner_url" value="<?php echo esc_url( $url ); ?>" placeholder="https://example.com">
		</p>
		<p>
			<label for="nerv-core-partner-rel"><strong><?php esc_html_e( 'Link attribute', 'nerv-core' ); ?></strong></label>
			<select id="nerv-core-partner-rel" class="widefat" name="nerv_core_partner_rel">
				<option value="follow" <?php selected( 'follow', $rel ); ?>><?php esc_html_e( 'Follow', 'nerv-core' ); ?></option>
				<option value="nofollow" <?php selected( 'nofollow', $rel ); ?>><?php esc_html_e( 'Nofollow', 'nerv-core' ); ?></option>
			</select>
		</p>
		<p>
			<label>
				<input type="checkbox" name="nerv_core_partner_featured" value="1" <?php checked( $featured ); ?>>
				<?php esc_html_e( 'Show in footer featured row', 'nerv-core' ); ?>
			</label>
		</p>
	<?php endif; ?>
	<?php
}

add_action( 'save_post', 'nerv_core_save_metadata' );
function nerv_core_save_metadata( int $post_id ): void {
	if ( ! isset( $_POST['nerv_core_metadata_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nerv_core_metadata_nonce'] ) ), 'nerv_core_save_metadata' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['nerv_core_subtitle'] ) ) {
		update_post_meta( $post_id, '_nerv_subtitle', sanitize_text_field( wp_unslash( $_POST['nerv_core_subtitle'] ) ) );
	}

	if ( isset( $_POST['nerv_core_partner_url'] ) ) {
		update_post_meta( $post_id, '_nerv_partner_url', esc_url_raw( wp_unslash( $_POST['nerv_core_partner_url'] ) ) );
	}

	if ( isset( $_POST['nerv_core_partner_rel'] ) ) {
		$rel = sanitize_key( wp_unslash( $_POST['nerv_core_partner_rel'] ) );
		update_post_meta( $post_id, '_nerv_partner_rel', 'nofollow' === $rel ? 'nofollow' : 'follow' );
	}

	if ( 'partner' === get_post_type( $post_id ) ) {
		update_post_meta( $post_id, '_nerv_partner_featured', isset( $_POST['nerv_core_partner_featured'] ) ? '1' : '0' );
	}
}
