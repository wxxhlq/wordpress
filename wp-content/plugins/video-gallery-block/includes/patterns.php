<?php
/**
 * Bundled block patterns.
 *
 * The block arrives from the inserter as a single sample video, which is a long
 * way from the gallery, course library or showreel people came here to build.
 * These patterns are those sections, ready made: insert one, swap the videos,
 * done.
 *
 * On the video URL: every pattern points at the same sample clip the block
 * already ships as its own default. Picking arbitrary third-party videos to
 * bundle would be worse — they rot, and they are not ours to feature. The
 * posters, which are what the grid actually shows, are drawn inline so a
 * pattern renders instantly and cannot 404.
 *
 * @package video-gallery-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The sample clip the block ships as its default video.
 */
const VIDGALBLK_PATTERN_VIDEO = 'https://files.vidstack.io/sprite-fight/720p.mp4';

/**
 * An inline poster: a two-tone diagonal composition, 640x360.
 *
 * Drawn rather than fetched, so a freshly inserted pattern never waits on a
 * network request and never shows a broken thumbnail.
 *
 * @param string $back  Background colour.
 * @param string $front Foreground colour.
 * @return string Data URI.
 */
function vidgalblk_pattern_poster( $back, $front ) {
	return 'data:image/svg+xml;charset=utf-8,'
		. '%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'640\' height=\'360\'%3E'
		. '%3Crect width=\'640\' height=\'360\' fill=\'' . rawurlencode( $back ) . '\'/%3E'
		. '%3Cpath d=\'M0 360 L280 96 L640 360 Z\' fill=\'' . rawurlencode( $front ) . '\' opacity=\'0.85\'/%3E'
		. '%3Ccircle cx=\'506\' cy=\'92\' r=\'44\' fill=\'' . rawurlencode( $front ) . '\' opacity=\'0.45\'/%3E'
		. '%3C/svg%3E';
}

/**
 * One video entry.
 *
 * @param string $caption Caption shown on the thumbnail.
 * @param string $back    Poster background colour.
 * @param string $front   Poster foreground colour.
 * @param array  $albs    Albums this video belongs to.
 * @return array
 */
function vidgalblk_pattern_video( $caption, $back, $front, $albs = array() ) {
	return array(
		'video'   => VIDGALBLK_PATTERN_VIDEO,
		'poster'  => vidgalblk_pattern_poster( $back, $front ),
		'caption' => $caption,
		'albs'    => $albs,
	);
}

/**
 * Serialise the gallery block into block markup.
 *
 * The block renders from render.php and saves nothing between its delimiters.
 *
 * @param array $attrs Block attributes.
 * @return string
 */
function vidgalblk_pattern_block( $attrs ) {
	return '<!-- wp:vgb/video-gallery ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * A centred core heading.
 *
 * @param string $text  Heading text.
 * @param int    $level Heading level.
 * @return string
 */
function vidgalblk_pattern_heading( $text, $level = 2 ) {
	$level = (int) $level;

	return '<!-- wp:heading {"textAlign":"center","level":' . $level . '} -->'
		. '<h' . $level . ' class="wp-block-heading has-text-align-center">' . esc_html( $text ) . '</h' . $level . '>'
		. '<!-- /wp:heading -->';
}

/**
 * A centred core paragraph.
 *
 * @param string $text Paragraph text.
 * @return string
 */
function vidgalblk_pattern_paragraph( $text ) {
	return '<!-- wp:paragraph {"align":"center"} -->'
		. '<p class="has-text-align-center">' . esc_html( $text ) . '</p>'
		. '<!-- /wp:paragraph -->';
}

/**
 * A vertical spacer.
 *
 * @param int $px Height in pixels.
 * @return string
 */
function vidgalblk_pattern_spacer( $px = 32 ) {
	$px = (int) $px;

	return '<!-- wp:spacer {"height":"' . $px . 'px"} -->'
		. '<div style="height:' . $px . 'px" aria-hidden="true" class="wp-block-spacer"></div>'
		. '<!-- /wp:spacer -->';
}

/**
 * Wrap a pattern's blocks in a group.
 *
 * @param string $inner Inner block markup.
 * @param string $align full, wide or empty.
 * @return string
 */
function vidgalblk_pattern_group( $inner, $align = '' ) {
	$attrs   = $align ? '{"align":"' . $align . '","layout":{"type":"constrained"}}' : '{"layout":{"type":"constrained"}}';
	$classes = $align ? 'wp-block-group align' . $align : 'wp-block-group';

	return '<!-- wp:group ' . $attrs . ' -->'
		. '<div class="' . esc_attr( $classes ) . '">' . $inner . '</div>'
		. '<!-- /wp:group -->';
}

/**
 * Register the pattern category and the patterns themselves.
 *
 * @return void
 */
function vidgalblk_register_patterns() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	$category = 'video-gallery-block';

	register_block_pattern_category(
		$category,
		array( 'label' => __( 'Video Galleries', 'video-gallery-block' ) )
	);

	$patterns = array();

	/* Filterable gallery -------------------------------------------------- */
	$albums = array(
		__( 'Tutorials', 'video-gallery-block' ),
		__( 'Customer stories', 'video-gallery-block' ),
		__( 'Product demos', 'video-gallery-block' ),
	);

	$patterns['filterable-gallery'] = array(
		'title'       => __( 'Video gallery with album filters', 'video-gallery-block' ),
		'description' => __( 'A three-column gallery with a working filter bar above it, grouped into three albums.', 'video-gallery-block' ),
		'keywords'    => array( 'gallery', 'filter', 'albums', 'video' ),
		'content'     => vidgalblk_pattern_group(
			vidgalblk_pattern_heading( __( 'Watch how it works', 'video-gallery-block' ) )
			. vidgalblk_pattern_paragraph( __( 'Short walkthroughs, real customer stories and a look at the product itself. Filter to whatever you came for.', 'video-gallery-block' ) )
			. vidgalblk_pattern_spacer( 24 )
			. vidgalblk_pattern_block(
				array(
					'albums'  => $albums,
					'videos'  => array(
						vidgalblk_pattern_video( __( 'Getting started in five minutes', 'video-gallery-block' ), '#0f2b33', '#2BC69C', array( $albums[0] ) ),
						vidgalblk_pattern_video( __( 'Building your first gallery', 'video-gallery-block' ), '#132038', '#5B8DEF', array( $albums[0] ) ),
						vidgalblk_pattern_video( __( 'How Northwind cut load time in half', 'video-gallery-block' ), '#2e1a12', '#E8874F', array( $albums[1] ) ),
						vidgalblk_pattern_video( __( 'Latitude Studio on switching over', 'video-gallery-block' ), '#301a24', '#D97BA6', array( $albums[1] ) ),
						vidgalblk_pattern_video( __( 'The editor, end to end', 'video-gallery-block' ), '#1b2419', '#8FBF6A', array( $albums[2] ) ),
						vidgalblk_pattern_video( __( 'Filters, albums and the lightbox', 'video-gallery-block' ), '#241f33', '#9B87E0', array( $albums[2] ) ),
					),
					'columns' => array( 'desktop' => 3, 'tablet' => 2, 'mobile' => 1 ),
					'filter'  => array( 'show' => true, 'commonLabel' => __( 'All videos', 'video-gallery-block' ) ),
					'align'   => 'wide',
				)
			),
			'wide'
		),
	);

	/* Course library ------------------------------------------------------ */
	$modules = array(
		__( 'Module 1 — Basics', 'video-gallery-block' ),
		__( 'Module 2 — Layouts', 'video-gallery-block' ),
	);

	$patterns['course-library'] = array(
		'title'       => __( 'Course video library', 'video-gallery-block' ),
		'description' => __( 'A four-column lesson library filtered by module, for a course or documentation page.', 'video-gallery-block' ),
		'keywords'    => array( 'course', 'lessons', 'library', 'tutorial', 'lms' ),
		'content'     => vidgalblk_pattern_group(
			vidgalblk_pattern_heading( __( 'Lesson library', 'video-gallery-block' ) )
			. vidgalblk_pattern_spacer( 16 )
			. vidgalblk_pattern_block(
				array(
					'albums'     => $modules,
					'videos'     => array(
						vidgalblk_pattern_video( __( '1.1 Installing the plugin', 'video-gallery-block' ), '#101c2b', '#5B8DEF', array( $modules[0] ) ),
						vidgalblk_pattern_video( __( '1.2 Your first block', 'video-gallery-block' ), '#0f2430', '#3FA9C9', array( $modules[0] ) ),
						vidgalblk_pattern_video( __( '1.3 Saving and reusing', 'video-gallery-block' ), '#12242a', '#2BC69C', array( $modules[0] ) ),
						vidgalblk_pattern_video( __( '2.1 Columns and spacing', 'video-gallery-block' ), '#231c2e', '#9B87E0', array( $modules[1] ) ),
						vidgalblk_pattern_video( __( '2.2 Captions and posters', 'video-gallery-block' ), '#2c1d19', '#E8874F', array( $modules[1] ) ),
						vidgalblk_pattern_video( __( '2.3 Albums and filtering', 'video-gallery-block' ), '#1d2718', '#8FBF6A', array( $modules[1] ) ),
						vidgalblk_pattern_video( __( '2.4 The lightbox', 'video-gallery-block' ), '#2b1a24', '#D97BA6', array( $modules[1] ) ),
						vidgalblk_pattern_video( __( '2.5 Going live', 'video-gallery-block' ), '#1a1f2e', '#7C9BE8', array( $modules[1] ) ),
					),
					'columns'    => array( 'desktop' => 4, 'tablet' => 2, 'mobile' => 1 ),
					'columnGap'  => 14,
					'rowGap'     => 14,
					'itemHeight' => '160px',
					'filter'     => array( 'show' => true, 'commonLabel' => __( 'All lessons', 'video-gallery-block' ) ),
					'align'      => 'wide',
				)
			),
			'wide'
		),
	);

	/* Product demos ------------------------------------------------------- */
	$patterns['product-demos'] = array(
		'title'       => __( 'Product demo showcase', 'video-gallery-block' ),
		'description' => __( 'Three large tiles for a landing page, with no filter bar and taller thumbnails.', 'video-gallery-block' ),
		'keywords'    => array( 'demo', 'product', 'landing', 'showcase' ),
		'content'     => vidgalblk_pattern_group(
			vidgalblk_pattern_heading( __( 'See it in action', 'video-gallery-block' ) )
			. vidgalblk_pattern_paragraph( __( 'Three minutes each. No sign-up, no sales call first.', 'video-gallery-block' ) )
			. vidgalblk_pattern_spacer( 24 )
			. vidgalblk_pattern_block(
				array(
					'videos'     => array(
						vidgalblk_pattern_video( __( 'Set up a gallery', 'video-gallery-block' ), '#101c2b', '#5B8DEF' ),
						vidgalblk_pattern_video( __( 'Organise with albums', 'video-gallery-block' ), '#12242a', '#2BC69C' ),
						vidgalblk_pattern_video( __( 'Publish and measure', 'video-gallery-block' ), '#2c1d19', '#E8874F' ),
					),
					'columns'    => array( 'desktop' => 3, 'tablet' => 1, 'mobile' => 1 ),
					'itemHeight' => '260px',
					'filter'     => array( 'show' => false, 'commonLabel' => '' ),
					'align'      => 'wide',
				)
			),
			'wide'
		),
	);

	/* Customer stories ---------------------------------------------------- */
	$patterns['customer-stories'] = array(
		'title'       => __( 'Customer video stories', 'video-gallery-block' ),
		'description' => __( 'Video testimonials in three columns, captioned with who is speaking.', 'video-gallery-block' ),
		'keywords'    => array( 'testimonial', 'customer', 'stories', 'social proof' ),
		'content'     => vidgalblk_pattern_group(
			vidgalblk_pattern_heading( __( 'In their own words', 'video-gallery-block' ) )
			. vidgalblk_pattern_spacer( 24 )
			. vidgalblk_pattern_block(
				array(
					'videos'     => array(
						vidgalblk_pattern_video( __( 'Sarah Jenkins — Northwind', 'video-gallery-block' ), '#0f2b33', '#2BC69C' ),
						vidgalblk_pattern_video( __( 'Michael Chang — Latitude Studio', 'video-gallery-block' ), '#2e1a12', '#E8874F' ),
						vidgalblk_pattern_video( __( 'Emily Watson — Meridian', 'video-gallery-block' ), '#241f33', '#9B87E0' ),
					),
					'columns'    => array( 'desktop' => 3, 'tablet' => 2, 'mobile' => 1 ),
					'itemHeight' => '220px',
					'filter'     => array( 'show' => false, 'commonLabel' => '' ),
					'align'      => 'wide',
				)
			),
			'wide'
		),
	);

	/* Showreel wall ------------------------------------------------------- */
	$patterns['showreel-wall'] = array(
		'title'       => __( 'Portfolio showreel wall', 'video-gallery-block' ),
		'description' => __( 'A dense four-column wall with tight gaps and no captions on the thumbnails, for a portfolio or reel.', 'video-gallery-block' ),
		'keywords'    => array( 'portfolio', 'showreel', 'wall', 'grid' ),
		'content'     => vidgalblk_pattern_group(
			vidgalblk_pattern_block(
				array(
					'videos'     => array(
						vidgalblk_pattern_video( __( 'Aurora — title sequence', 'video-gallery-block' ), '#131318', '#8FBF6A' ),
						vidgalblk_pattern_video( __( 'Meridian — brand film', 'video-gallery-block' ), '#181318', '#D97BA6' ),
						vidgalblk_pattern_video( __( 'Northwind — product spot', 'video-gallery-block' ), '#131a18', '#2BC69C' ),
						vidgalblk_pattern_video( __( 'Latitude — case study', 'video-gallery-block' ), '#181613', '#E8874F' ),
						vidgalblk_pattern_video( __( 'Harbour — documentary cut', 'video-gallery-block' ), '#13161f', '#5B8DEF' ),
						vidgalblk_pattern_video( __( 'Sable — motion tests', 'video-gallery-block' ), '#1a1520', '#9B87E0' ),
						vidgalblk_pattern_video( __( 'Foundry — launch reel', 'video-gallery-block' ), '#131e1f', '#3FA9C9' ),
						vidgalblk_pattern_video( __( 'Kestrel — short', 'video-gallery-block' ), '#1c1a13', '#C9B45B' ),
					),
					'columns'    => array( 'desktop' => 4, 'tablet' => 2, 'mobile' => 1 ),
					'columnGap'  => 6,
					'rowGap'     => 6,
					'itemHeight' => '180px',
					'filter'     => array( 'show' => false, 'commonLabel' => '' ),
					'options'    => array( 'objectFit' => 'cover', 'showCaptionOnThumbnail' => false ),
					'align'      => 'full',
				)
			),
			'full'
		),
	);

	/*
	 * Pattern search matches title, description and keywords — not the category
	 * label. Someone who has just read "Video Galleries" in the category list
	 * and types it into the search box gets nothing back, so the category's own
	 * wording goes on every pattern as a keyword, singular and plural.
	 */
	$base_keywords = array(
		__( 'video', 'video-gallery-block' ),
		__( 'video gallery', 'video-gallery-block' ),
		__( 'video galleries', 'video-gallery-block' ),
	);

	foreach ( $patterns as $slug => $pattern ) {
		register_block_pattern(
			'video-gallery-block/' . $slug,
			array(
				'title'       => $pattern['title'],
				'description' => $pattern['description'],
				'categories'  => array( $category ),
				'keywords'    => array_values( array_unique( array_merge( $base_keywords, $pattern['keywords'] ) ) ),
				'content'     => $pattern['content'],
			)
		);
	}
}

/**
 * Late on init: register_block_pattern() silently drops a pattern whose blocks
 * are not registered yet, and the block registers on init at the default
 * priority.
 */
add_action( 'init', 'vidgalblk_register_patterns', 20 );
