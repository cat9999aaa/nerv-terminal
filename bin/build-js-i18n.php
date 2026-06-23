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
$source_files = array_slice( $argv, 4 );

if ( '' === $po_file || '' === $json_file || '' === $domain || ! is_file( $po_file ) ) {
	fwrite( STDERR, "Usage: php build-js-i18n.php <po> <json> <domain> [source ...]\n" );
	exit( 1 );
}

$source_filters = array_map( 'normalize_source_path', $source_files );
$entries = parse_simple_po( file_get_contents( $po_file ) ?: '' );
$messages = array(
	'' => array(
		'domain' => $domain,
		'lang'   => '',
	),
);

foreach ( $entries as $entry ) {
	if ( '' === $entry['msgid'] || '' === $entry['msgstr'] || ! entry_matches_sources( $entry, $source_filters ) ) {
		continue;
	}
	$messages[ $entry['msgid'] ] = array( $entry['msgstr'] );
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
	$references = array();

	foreach ( $lines as $line ) {
		if ( '' === trim( $line ) ) {
			if ( null !== $msgid && null !== $msgstr ) {
				$entries[] = array(
					'msgid'      => $msgid,
					'msgstr'     => $msgstr,
					'references' => $references,
				);
			}
			$msgid = null;
			$msgstr = null;
			$field = null;
			$references = array();
			continue;
		}
		if ( str_starts_with( $line, '#:' ) ) {
			$references = array_merge( $references, preg_split( '/\s+/', trim( substr( $line, 2 ) ) ) ?: array() );
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
		$entries[] = array(
			'msgid'      => $msgid,
			'msgstr'     => $msgstr,
			'references' => $references,
		);
	}

	return $entries;
}

function entry_matches_sources( array $entry, array $source_filters ): bool {
	if ( array() === $source_filters ) {
		return true;
	}

	foreach ( $entry['references'] as $reference ) {
		$path = normalize_source_path( preg_replace( '/:\d+$/', '', (string) $reference ) ?: '' );
		foreach ( $source_filters as $source_filter ) {
			if ( '' !== $path && ( $path === $source_filter || str_ends_with( $path, '/' . $source_filter ) || str_ends_with( $source_filter, '/' . $path ) ) ) {
				return true;
			}
		}
	}

	return false;
}

function normalize_source_path( string $path ): string {
	return trim( str_replace( '\\', '/', $path ), '/' );
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
