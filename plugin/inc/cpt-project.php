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
				'name'          => 'NERV主题 · 项目',
				'singular_name' => 'NERV主题 · 项目',
				'add_new_item'  => '添加 NERV主题 · 项目',
				'edit_item'     => '编辑 NERV主题 · 项目',
				'menu_name'     => 'NERV主题 · 项目',
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
