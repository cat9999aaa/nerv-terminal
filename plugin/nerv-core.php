<?php
/**
 * Plugin Name: NERV Core
 * Plugin URI: https://dashen.wang/
 * Description: Data and service layer for the NERV Terminal theme.
 * Version: 0.1.5
 * Requires at least: 6.7
 * Requires PHP: 8.1
 * Author: Wang Dashen
 * License: GPL-2.0-or-later
 * Text Domain: nerv-core
 * Domain Path: /languages
 *
 * @package NervCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NERV_CORE_VERSION', '0.1.5' );
define( 'NERV_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'NERV_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once NERV_CORE_DIR . 'inc/i18n.php';
require_once NERV_CORE_DIR . 'inc/cpt-project.php';
require_once NERV_CORE_DIR . 'inc/cpt-partner.php';
require_once NERV_CORE_DIR . 'inc/meta-fields.php';
require_once NERV_CORE_DIR . 'inc/partner-healthcheck.php';
require_once NERV_CORE_DIR . 'inc/partner-display.php';
require_once NERV_CORE_DIR . 'inc/social-store.php';
require_once NERV_CORE_DIR . 'inc/author-profile.php';
require_once NERV_CORE_DIR . 'inc/blocks.php';
require_once NERV_CORE_DIR . 'inc/cover-pipeline.php';
require_once NERV_CORE_DIR . 'inc/geo-markdown.php';
require_once NERV_CORE_DIR . 'inc/geo-score.php';
require_once NERV_CORE_DIR . 'inc/geo-crawler-stats.php';
require_once NERV_CORE_DIR . 'inc/geo-title.php';
require_once NERV_CORE_DIR . 'inc/ai-policy.php';
require_once NERV_CORE_DIR . 'inc/indexnow.php';
require_once NERV_CORE_DIR . 'inc/related-engine.php';
require_once NERV_CORE_DIR . 'inc/tools.php';
require_once NERV_CORE_DIR . 'inc/update.php';
require_once NERV_CORE_DIR . 'inc/admin-page.php';

register_activation_hook( __FILE__, 'nerv_core_activate' );
function nerv_core_activate(): void {
	if ( function_exists( 'nerv_core_register_project_cpt' ) ) {
		nerv_core_register_project_cpt();
	}
	if ( function_exists( 'nerv_core_register_partner_cpt' ) ) {
		nerv_core_register_partner_cpt();
	}
	nerv_core_apply_preferred_permalink_structure( true );
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'nerv_core_deactivate' );
function nerv_core_deactivate(): void {
	flush_rewrite_rules();
}

add_action( 'plugins_loaded', 'nerv_core_load_textdomain' );
function nerv_core_load_textdomain(): void {
	load_plugin_textdomain( 'nerv-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'admin_init', 'nerv_core_apply_preferred_permalink_structure' );
function nerv_core_apply_preferred_permalink_structure( bool $force_flush = false ): void {
	$preferred = '/%postname%.html';
	if ( $preferred === (string) get_option( 'permalink_structure' ) ) {
		return;
	}

	update_option( 'permalink_structure', $preferred );
	update_option( 'rewrite_rules', '' );
	if ( $force_flush ) {
		flush_rewrite_rules();
	}
}
