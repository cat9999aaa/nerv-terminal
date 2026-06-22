<?php
/**
 * Partner content type.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'nerv_core_register_partner_cpt' );
function nerv_core_register_partner_cpt(): void {
	register_post_type(
		'partner',
		array(
			'labels'       => array(
				'name'          => __( 'Partners', 'nerv-core' ),
				'singular_name' => __( 'Partner', 'nerv-core' ),
				'add_new_item'  => __( 'Add New Partner', 'nerv-core' ),
				'edit_item'     => __( 'Edit Partner', 'nerv-core' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'menu_icon'    => 'dashicons-groups',
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
			'rewrite'      => array( 'slug' => 'partners' ),
		)
	);
}
