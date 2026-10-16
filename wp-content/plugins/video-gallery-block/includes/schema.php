<?php
/**
 * Video SEO markup for the gallery.
 *
 * A gallery of videos is invisible to search without this. Google needs a
 * VideoObject per video to show it as a video result -- thumbnail, duration,
 * key moments -- and in 2026 the same markup is what AI search reads to work
 * out what a video is about. Every comparable free plugin puts this behind its
 * paid tier: All-in-One Video Gallery, Embed Plus and Automatic YouTube Gallery
 * all list Schema.org markup as a premium feature.
 *
 * Only videos with a real title are described. Google requires `name`, and a
 * made-up one ("Video 3") is worse than no markup at all -- it fills Search
 * Console with entries nobody searched for. Since a pasted YouTube or Vimeo URL
 * now fills the caption in from the provider automatically, most videos have a
 * title without anyone typing one.
 *
 * @package video-gallery-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turn one video entry into a VideoObject node.
 *
 * @param array  $video       One entry from the `videos` attribute.
 * @param string $upload_date ISO 8601 date to use when the entry has none.
 * @return array|null The node, or null if the video cannot be described.
 */
function vidgalblk_video_schema_node( $video, $upload_date ) {
	if ( ! is_array( $video ) ) {
		return null;
	}

	$name = isset( $video['caption'] ) ? trim( wp_strip_all_tags( (string) $video['caption'] ) ) : '';
	$url  = isset( $video['video'] ) ? (string) $video['video'] : '';

	// No title, no node. See the note at the top of this file.
	if ( '' === $name || '' === $url ) {
		return null;
	}

	$thumbnail = vidgalblk_thumbnail_url( $video );

	// `thumbnailUrl` is required too, and a data: URI is not a thumbnail Google
	// can fetch -- the bundled patterns draw their posters inline.
	if ( ! $thumbnail || 0 === strpos( $thumbnail, 'data:' ) ) {
		return null;
	}

	$node = array(
		'@type'        => 'VideoObject',
		'name'         => $name,
		'thumbnailUrl' => $thumbnail,
		'uploadDate'   => $upload_date,
	);

	$youtube_id = vidgalblk_youtube_id( $url );
	$vimeo_id   = vidgalblk_vimeo_id( $url );

	if ( $youtube_id ) {
		// embedUrl for a hosted video, not contentUrl: contentUrl has to be a
		// file Google can fetch, and a YouTube watch page is not one.
		$node['embedUrl'] = 'https://www.youtube.com/embed/' . $youtube_id;
	} elseif ( $vimeo_id ) {
		$node['embedUrl'] = 'https://player.vimeo.com/video/' . $vimeo_id;
	} elseif ( vidgalblk_is_facebook_video( $url ) ) {
		$node['embedUrl'] = vidgalblk_facebook_embed_url( $url );
	} else {
		$node['contentUrl'] = $url;
	}

	/**
	 * Filter a single video's VideoObject node.
	 *
	 * Use this to add `description`, `duration` (ISO 8601, e.g. PT4M35S) or
	 * `hasPart` clips when the site knows them.
	 *
	 * @param array $node  The VideoObject node.
	 * @param array $video The video entry it came from.
	 */
	return apply_filters( 'vidgalblk_video_schema_node', $node, $video );
}

/**
 * The VideoObject nodes for a gallery's attributes.
 *
 * @param array $attributes Block attributes.
 * @return array
 */
function vidgalblk_video_schema_nodes( $attributes ) {
	$videos = isset( $attributes['videos'] ) && is_array( $attributes['videos'] ) ? $attributes['videos'] : array();

	if ( empty( $videos ) ) {
		return array();
	}

	/*
	 * When the video itself does not say when it was published, the date the
	 * page was is the honest stand-in -- it is the date this video became
	 * available here. Sites that know better can say so through the filter.
	 */
	$post        = get_post();
	$upload_date = $post ? get_post_time( 'c', true, $post ) : current_time( 'c', true );

	/**
	 * Filter the fallback `uploadDate` used for videos that carry no date.
	 *
	 * @param string   $upload_date ISO 8601 date.
	 * @param array    $attributes  Block attributes.
	 * @param \WP_Post $post        Post the gallery is rendering in, if any.
	 */
	$upload_date = apply_filters( 'vidgalblk_video_schema_upload_date', $upload_date, $attributes, $post );

	$nodes = array();

	foreach ( $videos as $video ) {
		$node = vidgalblk_video_schema_node( $video, $upload_date );

		if ( $node ) {
			$nodes[] = $node;
		}
	}

	return $nodes;
}

/**
 * Should this gallery emit video markup at all?
 *
 * @param array $attributes Block attributes.
 * @return bool
 */
function vidgalblk_video_schema_enabled( $attributes ) {
	// Not in the editor's block preview, not in feeds: in both places the
	// markup is either never read or actively unwanted.
	if ( is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	// Per-gallery switch, for a site whose SEO plugin already describes the
	// same videos and would end up saying it twice.
	if ( isset( $attributes['options']['videoSchema'] ) && false === $attributes['options']['videoSchema'] ) {
		return false;
	}

	/**
	 * Filter whether video markup is printed, site-wide.
	 *
	 * @param bool  $enabled    Whether to print the markup.
	 * @param array $attributes Block attributes.
	 */
	return (bool) apply_filters( 'vidgalblk_video_schema_enabled', true, $attributes );
}

/**
 * Print the gallery's video markup.
 *
 * @param array $attributes Block attributes.
 * @return void
 */
function vidgalblk_print_video_schema( $attributes ) {
	if ( ! vidgalblk_video_schema_enabled( $attributes ) ) {
		return;
	}

	$nodes = vidgalblk_video_schema_nodes( $attributes );

	if ( empty( $nodes ) ) {
		return;
	}

	foreach ( $nodes as $node ) {
		$node = array_merge( array( '@context' => 'https://schema.org' ), $node );

		/*
		 * JSON_HEX_TAG matters: a caption containing `</script>` would
		 * otherwise close this element and turn a video title into an
		 * injection point. Slashes stay escaped for the same reason, so no
		 * JSON_UNESCAPED_SLASHES here. Unicode is left as-is so that a title
		 * in Bengali or Japanese stays readable in the source.
		 */
		printf(
			'<script type="application/ld+json">%s</script>',
			wp_json_encode( $node, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- encoded for a JSON-LD context.
		);
	}
}
