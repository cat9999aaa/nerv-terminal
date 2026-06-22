<?php
/**
 * Author profile extensions for E-E-A-T and Person schema.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nerv_core_author_social_fields(): array {
	return array(
		'github'   => __( 'GitHub', 'nerv-core' ),
		'x'        => __( 'X / Twitter', 'nerv-core' ),
		'youtube'  => __( 'YouTube', 'nerv-core' ),
		'linkedin' => __( 'LinkedIn', 'nerv-core' ),
		'website'  => __( 'Website', 'nerv-core' ),
	);
}

function nerv_core_author_title( int $user_id ): string {
	return (string) get_user_meta( $user_id, 'nerv_author_title', true );
}

function nerv_core_author_social_links( int $user_id ): array {
	$links = array();
	foreach ( nerv_core_author_social_fields() as $key => $label ) {
		$url = get_user_meta( $user_id, 'nerv_author_social_' . $key, true );
		if ( ! $url ) {
			continue;
		}

		$links[] = array(
			'key'   => $key,
			'label' => $label,
			'url'   => esc_url_raw( $url ),
		);
	}

	return $links;
}

function nerv_core_author_same_as( int $user_id ): array {
	$links = nerv_core_author_social_links( $user_id );

	return array_values( array_unique( array_filter( array_map( static function ( array $link ): string {
		return esc_url_raw( $link['url'] );
	}, $links ) ) ) );
}

add_action( 'show_user_profile', 'nerv_core_render_author_profile_fields' );
add_action( 'edit_user_profile', 'nerv_core_render_author_profile_fields' );
function nerv_core_render_author_profile_fields( WP_User $user ): void {
	$title = nerv_core_author_title( (int) $user->ID );
	?>
	<h2><?php esc_html_e( 'NERV Author Signal', 'nerv-core' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="nerv-author-title"><?php esc_html_e( 'Pilot title', 'nerv-core' ); ?></label></th>
			<td>
				<input id="nerv-author-title" class="regular-text" type="text" name="nerv_author_title" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'GEO Systems Operator', 'nerv-core' ); ?>">
				<p class="description"><?php esc_html_e( 'Shown on the NERV author card below the display name.', 'nerv-core' ); ?></p>
			</td>
		</tr>
		<?php foreach ( nerv_core_author_social_fields() as $key => $label ) : ?>
			<tr>
				<th><label for="nerv-author-social-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
				<td>
					<input id="nerv-author-social-<?php echo esc_attr( $key ); ?>" class="regular-text code" type="url" name="nerv_author_social[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_url( get_user_meta( (int) $user->ID, 'nerv_author_social_' . $key, true ) ); ?>" placeholder="https://example.com/profile">
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
}

add_action( 'personal_options_update', 'nerv_core_save_author_profile_fields' );
add_action( 'edit_user_profile_update', 'nerv_core_save_author_profile_fields' );
function nerv_core_save_author_profile_fields( int $user_id ): void {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	if ( isset( $_POST['nerv_author_title'] ) ) {
		update_user_meta( $user_id, 'nerv_author_title', sanitize_text_field( wp_unslash( $_POST['nerv_author_title'] ) ) );
	}

	$incoming = isset( $_POST['nerv_author_social'] ) && is_array( $_POST['nerv_author_social'] ) ? wp_unslash( $_POST['nerv_author_social'] ) : array();
	foreach ( nerv_core_author_social_fields() as $key => $label ) {
		$url = isset( $incoming[ $key ] ) ? esc_url_raw( $incoming[ $key ] ) : '';
		if ( $url ) {
			update_user_meta( $user_id, 'nerv_author_social_' . $key, $url );
		} else {
			delete_user_meta( $user_id, 'nerv_author_social_' . $key );
		}
	}
}
