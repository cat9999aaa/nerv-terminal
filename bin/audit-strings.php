<?php
/**
 * Placeholder for the v4 string audit.
 *
 * This first implementation confirms the defaults registry exists. The stricter
 * PHP/template literal scanner will be expanded as NERV CONTROL fields grow.
 */

$defaults = __DIR__ . '/../theme/inc/defaults.php';
if ( ! is_file( $defaults ) ) {
	fwrite( STDERR, "Missing defaults registry.\n" );
	exit( 1 );
}

$source = file_get_contents( $defaults );
if ( false === $source || ! str_contains( $source, 'function nerv_terminal_default_strings' ) ) {
	fwrite( STDERR, "Invalid defaults registry.\n" );
	exit( 1 );
}

echo "String registry present.\n";
