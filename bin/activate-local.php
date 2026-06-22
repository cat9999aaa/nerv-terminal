<?php
/**
 * Activate the local NERV runtime safely through WordPress APIs.
 */

if ( PHP_SAPI !== 'cli' ) {
	fwrite( STDERR, "CLI only.\n" );
	exit( 1 );
}

$wp_load = $argv[1] ?? '/www/wwwroot/127_0_0_1/wp-load.php';
if ( ! is_file( $wp_load ) ) {
	fwrite( STDERR, "Missing wp-load.php: {$wp_load}\n" );
	exit( 1 );
}

require_once $wp_load;
require_once ABSPATH . 'wp-admin/includes/plugin.php';

switch_theme( 'nerv-terminal' );

if ( ! is_plugin_active( 'nerv-core/nerv-core.php' ) ) {
	$result = activate_plugin( 'nerv-core/nerv-core.php' );
	if ( is_wp_error( $result ) ) {
		fwrite( STDERR, $result->get_error_message() . "\n" );
		exit( 1 );
	}
}

delete_option( 'rewrite_rules' );
flush_rewrite_rules();

echo "Activated NERV Terminal and NERV Core for local WordPress.\n";
