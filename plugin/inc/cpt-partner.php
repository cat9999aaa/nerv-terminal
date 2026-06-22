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
				'name'          => 'NERV主题 · 合作伙伴',
				'singular_name' => 'NERV主题 · 合作伙伴',
				'add_new_item'  => '添加 NERV主题 · 合作伙伴',
				'edit_item'     => '编辑 NERV主题 · 合作伙伴',
				'menu_name'     => 'NERV主题 · 合作伙伴',
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
