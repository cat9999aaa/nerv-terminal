<?php
/**
 * Seed local demo content for NERV Terminal development.
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

function nerv_seed_author_id(): int {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		$users = get_users(
			array(
				'role__in' => array( 'administrator', 'editor', 'author' ),
				'number'   => 1,
				'orderby'  => 'ID',
				'order'    => 'ASC',
			)
		);
		$user_id = $users ? (int) $users[0]->ID : 0;
	}

	return $user_id;
}

function nerv_seed_author_profile(): int {
	$user_id = nerv_seed_author_id();
	if ( ! $user_id ) {
		return 0;
	}

	wp_update_user(
		array(
			'ID'          => $user_id,
			'description' => 'NERV Terminal operator focused on WordPress, GEO publishing, and structured AI-readable content.',
		)
	);
	update_user_meta( $user_id, 'nerv_author_title', 'GEO Systems Operator' );
	update_user_meta( $user_id, 'nerv_author_social_github', 'https://github.com/cat9999sss' );
	update_user_meta( $user_id, 'nerv_author_social_x', 'https://x.com/dashenwang' );
	update_user_meta( $user_id, 'nerv_author_social_website', 'https://dashen.wang' );

	return $user_id;
}

function nerv_seed_secondary_author_profile(): int {
	$user = get_user_by( 'login', 'magi_operator' );
	if ( ! $user instanceof WP_User ) {
		$password = wp_generate_password( 24, true, true );
		$user_id = wp_insert_user(
			array(
				'user_login'   => 'magi_operator',
				'user_pass'    => $password,
				'user_email'   => 'magi.operator@example.test',
				'display_name' => 'MAGI Operator',
				'nickname'     => 'MAGI Operator',
				'role'         => 'author',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			fwrite( STDERR, $user_id->get_error_message() . "\n" );
			return 0;
		}
	} else {
		$user_id = (int) $user->ID;
		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => 'MAGI Operator',
				'nickname'     => 'MAGI Operator',
				'role'         => 'author',
			)
		);
	}

	wp_update_user(
		array(
			'ID'          => $user_id,
			'description' => 'Secondary demo author responsible for interface telemetry, mobile shell notes, and MAGI review trails.',
		)
	);
	update_user_meta( $user_id, 'nerv_author_title', 'Interface Telemetry Pilot' );
	update_user_meta( $user_id, 'nerv_author_social_github', 'https://github.com/magi-operator' );
	update_user_meta( $user_id, 'nerv_author_social_linkedin', 'https://www.linkedin.com/in/magi-operator' );
	update_user_meta( $user_id, 'nerv_author_social_website', 'https://example.test/magi-operator' );
	delete_user_meta( $user_id, 'nerv_author_social_x' );
	delete_user_meta( $user_id, 'nerv_author_social_youtube' );

	return $user_id;
}

function nerv_seed_post( string $type, string $title, string $content, array $meta = array(), int $author_id = 0 ): int {
	$existing = get_page_by_title( $title, OBJECT, $type );
	if ( $existing instanceof WP_Post ) {
		$post_id = (int) $existing->ID;
		$args = array(
			'ID'           => $post_id,
			'post_excerpt' => wp_trim_words( wp_strip_all_tags( $content ), 24 ),
			'post_content' => $content,
		);
		if ( $author_id ) {
			$args['post_author'] = $author_id;
		}
		wp_update_post( $args );
	} else {
		$args = array(
			'post_type'    => $type,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_excerpt' => wp_trim_words( wp_strip_all_tags( $content ), 24 ),
			'post_content' => $content,
		);
		if ( $author_id ) {
			$args['post_author'] = $author_id;
		}
		$post_id = wp_insert_post(
			$args,
			true
		);

		if ( is_wp_error( $post_id ) ) {
			fwrite( STDERR, $post_id->get_error_message() . "\n" );
			exit( 1 );
		}
	}

	foreach ( $meta as $key => $value ) {
		update_post_meta( $post_id, $key, $value );
	}

	return $post_id;
}

$author_id = nerv_seed_author_profile();
$secondary_author_id = nerv_seed_secondary_author_profile();

$projects = array(
	array( 'EVA-01', 'A controlled WordPress operation record for the first NERV Terminal demo project.', 'Web Design' ),
	array( 'TOKYO-3', 'A city-scale archive panel for CMS experiments, dashboard flow, and responsive shell testing.', 'CMS / WordPress' ),
	array( 'MAGI SYSTEM', 'Plugin-oriented diagnostics for data, services, and future GEO automation modules.', 'Plugin' ),
);

foreach ( $projects as $project ) {
	nerv_seed_post(
		'project',
		$project[0],
		'<p>' . esc_html( $project[1] ) . '</p><p><strong>Category:</strong> ' . esc_html( $project[2] ) . '</p>',
		array( '_nerv_subtitle' => $project[2] ),
		$author_id
	);
}

$cat = term_exists( 'Operations', 'category' );
if ( ! $cat ) {
	$cat = wp_insert_term( 'Operations', 'category' );
}
$cat_id = is_wp_error( $cat ) ? 0 : (int) ( is_array( $cat ) ? $cat['term_id'] : $cat );
$tag_ids = array();
foreach ( array( 'geo', 'wordpress', 'terminal' ) as $tag_name ) {
	$term = term_exists( $tag_name, 'post_tag' );
	if ( ! $term ) {
		$term = wp_insert_term( $tag_name, 'post_tag' );
	}
	if ( ! is_wp_error( $term ) ) {
		$tag_ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
	}
}

$posts = array(
	array(
		'NERV GEO Protocol',
		'<!-- wp:nerv-core/key-points {"points":["Markdown mirrors give AI crawlers a clean canonical reading path.","llms.txt exposes the site map in a format language models can consume quickly.","Structured blocks keep human pages and machine-readable metadata synchronized."]} /-->' .
		"\n\n" .
		'<p>A field note about markdown mirrors, structured feeds, and terminal-flavored publishing.</p>' .
		"\n\n" .
		'<!-- wp:nerv-core/faq {"items":[{"question":"Why does NERV Terminal publish Markdown mirrors?","answer":"Markdown mirrors give AI systems a compact, canonical version of each article without removing the human-facing WordPress page."},{"question":"How does the FAQ block help GEO?","answer":"The FAQ block turns clear questions and answers into FAQPage JSON-LD, which makes the article easier for search and AI answer engines to understand."}]} /-->',
		array( 'geo', 'wordpress' ),
		$author_id,
	),
	array(
		'Terminal Interface Notes',
		'<p>Responsive app shell observations for desktop dashboards and mobile bottom navigation.</p>',
		array( 'terminal', 'wordpress' ),
		$secondary_author_id ?: $author_id,
	),
);

foreach ( $posts as $demo_post ) {
	$post_id = nerv_seed_post(
		'post',
		$demo_post[0],
		$demo_post[1],
		array( '_nerv_subtitle' => 'Demo operation note' ),
		(int) $demo_post[3]
	);
	wp_set_post_categories( $post_id, array( $cat_id ), false );
	wp_set_post_terms( $post_id, $demo_post[2], 'post_tag', false );
}

$partners = array(
	array( 'OpenAI Research', 'AI systems and applied research signal source.', 'https://openai.com', 'follow', '1' ),
	array( 'WordPress.org', 'Publishing engine and open web infrastructure.', 'https://wordpress.org', 'follow', '1' ),
	array( 'Dashen Lab', 'Personal development and theme validation node.', 'https://dashen.wang', 'follow', '1' ),
	array( 'Offline Test Node', 'Intentionally reserved for future health-check red state testing.', 'https://127.0.0.1:1', 'nofollow', '0' ),
);

foreach ( $partners as $partner ) {
	nerv_seed_post(
		'partner',
		$partner[0],
		'<p>' . esc_html( $partner[1] ) . '</p>',
		array(
			'_nerv_subtitle'         => $partner[1],
			'_nerv_partner_url'      => $partner[2],
			'_nerv_partner_rel'      => $partner[3],
			'_nerv_partner_featured' => $partner[4],
		),
		$author_id
	);
}

flush_rewrite_rules();

echo "Seeded demo projects and partners.\n";
