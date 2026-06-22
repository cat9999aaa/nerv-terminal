<?php
/**
 * Project content type.
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'nerv_core_register_project_cpt' );
function nerv_core_register_project_cpt(): void {
	register_post_type(
		'project',
		array(
			'labels'       => array(
				'name'          => __( 'NERV Theme · Projects', 'nerv-core' ),
				'singular_name' => __( 'NERV Theme · Project', 'nerv-core' ),
				'add_new_item'  => __( 'Add New NERV Theme Project', 'nerv-core' ),
				'edit_item'     => __( 'Edit NERV Theme Project', 'nerv-core' ),
				'menu_name'     => __( 'NERV Theme · Projects', 'nerv-core' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'menu_icon'    => 'dashicons-portfolio',
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
			'rewrite'      => array( 'slug' => 'projects' ),
		)
	);
}
