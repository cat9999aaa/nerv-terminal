<?php
/**
 * Build WordPress JavaScript translation JSON from a PO file.
 *
 * @package NervTerminal
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$po_file = $argv[1] ?? '';
$json_file = $argv[2] ?? '';
$domain = $argv[3] ?? '';

if ( '' === $po_file || '' === $json_file || '' === $domain || ! is_file( $po_file ) ) {
	fwrite( STDERR, "Usage: php build-js-i18n.php <po> <json> <domain>\n" );
	exit( 1 );
}

$entries = parse_simple_po( file_get_contents( $po_file ) ?: '' );
$messages = array(
	'' => array(
		'domain' => $domain,
		'lang'   => '',
	),
);

foreach ( $entries as $msgid => $msgstr ) {
	if ( '' === $msgid || '' === $msgstr ) {
		continue;
	}
	$messages[ $msgid ] = array( $msgstr );
}

$json = array(
	'translation-revision-date' => gmdate( 'c' ),
	'generator'                 => 'nerv-terminal build-js-i18n.php',
	'domain'                    => $domain,
	'locale_data'               => array(
		$domain => $messages,
	),
);

file_put_contents(
	$json_file,
	wp_json_encode_compat( $json ) . "\n"
);

function parse_simple_po( string $content ): array {
	$lines = preg_split( '/\R/', $content );
	$entries = array();
	$msgid = null;
	$msgstr = null;
	$field = null;

	foreach ( $lines as $line ) {
		if ( '' === trim( $line ) ) {
			if ( null !== $msgid && null !== $msgstr ) {
				$entries[ $msgid ] = $msgstr;
			}
			$msgid = null;
			$msgstr = null;
			$field = null;
			continue;
		}
		if ( str_starts_with( $line, '#' ) ) {
			continue;
		}
		if ( str_starts_with( $line, 'msgid ' ) ) {
			$msgid = po_unquote( substr( $line, 6 ) );
			$field = 'msgid';
			continue;
		}
		if ( str_starts_with( $line, 'msgstr ' ) ) {
			$msgstr = po_unquote( substr( $line, 7 ) );
			$field = 'msgstr';
			continue;
		}
		if ( str_starts_with( $line, '"' ) ) {
			if ( 'msgid' === $field && null !== $msgid ) {
				$msgid .= po_unquote( $line );
			} elseif ( 'msgstr' === $field && null !== $msgstr ) {
				$msgstr .= po_unquote( $line );
			}
		}
	}

	if ( null !== $msgid && null !== $msgstr ) {
		$entries[ $msgid ] = $msgstr;
	}

	return $entries;
}

function po_unquote( string $value ): string {
	$value = trim( $value );
	if ( str_starts_with( $value, '"' ) && str_ends_with( $value, '"' ) ) {
		$value = substr( $value, 1, -1 );
	}

	return str_replace(
		array( '\n', '\t', '\"', '\\\\' ),
		array( "\n", "\t", '"', '\\' ),
		$value
	);
}

function wp_json_encode_compat( array $data ): string {
	return (string) json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}
