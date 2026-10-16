<?php
/**
 * Server-side render for the Video Gallery block.
 *
 * The gallery is rendered here in full -- thumbnails, links, captions and the
 * filter bar -- and the block's script then replaces it with the interactive
 * version. This used to output nothing but an empty `<div>` carrying the
 * attributes, which meant nobody and nothing saw the gallery until the
 * JavaScript had run: not search engines, not a visitor on a slow connection
 * watching a blank space, and not anyone whose browser never got the script.
 *
 * What the markup below gives up on without JavaScript is the lightbox and the
 * filter buttons. The links still go to the videos, so the page still works.
 *
 * @package video-gallery-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$vidgalblk_id     = wp_unique_id( 'vidgalblkVideoGallery-' );
$vidgalblk_videos = isset( $attributes['videos'] ) && is_array( $attributes['videos'] ) ? $attributes['videos'] : array();

/*
 * Shorts, included, excluded, or kept as the only videos -- the owner's
 * choice, applied once, here, rather than as something a visitor's browser
 * would otherwise have to filter out itself. `$attributes['videos']` is
 * overwritten with the result so that the JSON a visitor's browser hydrates
 * from, and the video-schema markup fired below, both agree with what the
 * markup below actually shows -- none of the three ever disagree about which
 * videos this gallery contains.
 */
$vidgalblk_videos     = vidgalblk_apply_shorts_filter(
	$vidgalblk_videos,
	$attributes['options']['shortsFilter'] ?? 'all'
);
$vidgalblk_videos     = vidgalblk_apply_sort_order(
	$vidgalblk_videos,
	$attributes['options']['sortOrder'] ?? 'manual'
);
$attributes['videos'] = $vidgalblk_videos;

$vidgalblk_albums = isset( $attributes['albums'] ) && is_array( $attributes['albums'] ) ? $attributes['albums'] : array();
$vidgalblk_filter = isset( $attributes['filter'] ) && is_array( $attributes['filter'] ) ? $attributes['filter'] : array();
$vidgalblk_show_caption   = ! empty( $attributes['options']['showCaptionOnThumbnail'] );
$vidgalblk_show_play_icon = false !== ( $attributes['options']['showPlayIcon'] ?? true );

// The filter bar is worth showing only when there is something to filter by.
$vidgalblk_show_filter = ( ! isset( $vidgalblk_filter['show'] ) || false !== $vidgalblk_filter['show'] )
	&& ! empty( $vidgalblk_albums )
	&& ! empty( $vidgalblk_filter['commonLabel'] );

/*
 * Thumbnails resolved here and handed to the script, index-aligned with
 * `videos`. This is how a Vimeo poster reaches the browser without every
 * visitor's browser having to ask Vimeo for it.
 */
$vidgalblk_thumbs = array();

foreach ( $vidgalblk_videos as $vidgalblk_video ) {
	$vidgalblk_thumbs[] = vidgalblk_thumbnail_url( is_array( $vidgalblk_video ) ? $vidgalblk_video : array() );
}

/*
 * Everything goes through `get_block_wrapper_attributes()` rather than being
 * written out alongside it, so that the layout properties merge with whatever
 * `style` the block supports contribute instead of silently colliding with it.
 * It escapes every value it is given.
 */
$vidgalblk_wrapper = get_block_wrapper_attributes(
	array(
		'id'              => $vidgalblk_id,
		'style'           => vidgalblk_layout_vars( $attributes ),
		'data-attributes' => wp_json_encode( $attributes ),
		'data-thumbs'     => wp_json_encode( $vidgalblk_thumbs ),
	)
);

/**
 * Fires before a gallery's markup is printed.
 *
 * Mirrors `vgb_before_block` in the Pro plugin, so anything hooked to one can
 * be hooked to the other.
 *
 * @param array  $attributes Block attributes.
 * @param string $id         Unique wrapper element id.
 */
do_action( 'vidgalblk_before_block', $attributes, $vidgalblk_id );
?>
<div <?php echo $vidgalblk_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>><div class="vidgalblkVideoGallery">
	<?php if ( $vidgalblk_show_filter ) : ?>
		<div id="<?php echo esc_attr( $vidgalblk_id ); ?>-filter" class="filter" role="group">
			<button type="button" class="current" data-filter="*" aria-pressed="true"><?php echo esc_html( $vidgalblk_filter['commonLabel'] ); ?></button>
			<?php foreach ( $vidgalblk_albums as $vidgalblk_album ) : ?>
				<?php if ( '' === trim( (string) $vidgalblk_album ) ) { continue; } ?>
				<button type="button" data-filter="<?php echo esc_attr( vidgalblk_album_class( $vidgalblk_albums, $vidgalblk_album ) ); ?>" aria-pressed="false"><?php echo esc_html( $vidgalblk_album ); ?></button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div id="<?php echo esc_attr( $vidgalblk_id ); ?>-gallery" class="videoGallery">
		<?php
		foreach ( $vidgalblk_videos as $vidgalblk_index => $vidgalblk_video ) :
			if ( ! is_array( $vidgalblk_video ) ) {
				continue;
			}

			$vidgalblk_src     = ! empty( $vidgalblk_video['video'] ) ? $vidgalblk_video['video'] : ( $vidgalblk_video['poster'] ?? '' );
			$vidgalblk_thumb   = $vidgalblk_thumbs[ $vidgalblk_index ];
			$vidgalblk_caption = isset( $vidgalblk_video['caption'] ) ? (string) $vidgalblk_video['caption'] : '';
			$vidgalblk_alt     = trim( wp_strip_all_tags( $vidgalblk_caption ) );
			$vidgalblk_label   = '' !== $vidgalblk_alt
				? $vidgalblk_alt
				/* translators: %d: video number within the gallery. */
				: sprintf( __( 'Play video %d', 'video-gallery-block' ), (int) $vidgalblk_index + 1 );

			if ( '' === $vidgalblk_src ) {
				continue;
			}

			/*
			 * `href` stays the real Facebook URL -- so a visitor with no
			 * JavaScript still lands on an actual, working page. `data-src` is
			 * what Fancybox opens instead once JS has hydrated: Facebook's
			 * public embed iframe, which (unlike its oEmbed API) needs no app
			 * credentials. See `vidgalblk_facebook_embed_url()`.
			 */
			$vidgalblk_is_facebook = vidgalblk_is_facebook_video( $vidgalblk_src );
			?>
			<a
				class="galleryItem <?php echo esc_attr( vidgalblk_album_classes( $vidgalblk_albums, $vidgalblk_video['albs'] ?? array() ) ); ?>"
				href="<?php echo esc_url( $vidgalblk_src ); ?>"
				aria-label="<?php echo esc_attr( $vidgalblk_label ); ?>"
				data-fancybox
				<?php if ( $vidgalblk_is_facebook ) : ?>
					data-src="<?php echo esc_url( vidgalblk_facebook_embed_url( $vidgalblk_src ) ); ?>"
					data-type="iframe"
				<?php endif; ?>
				<?php /*
				 * The real video URL, for GA4 tracking: by the time
				 * `Carousel.contentReady` fires, Fancybox has already
				 * rewritten a YouTube or Vimeo URL into its own player-embed
				 * URL, and a Facebook video's `data-src` above is its embed
				 * iframe URL -- neither is the clean URL pushVideoEvent()
				 * needs for `video_url`.
				 */ ?>
				data-video-url="<?php echo esc_url( $vidgalblk_src ); ?>"
				data-caption="<?php echo esc_attr( wp_kses( $vidgalblk_caption, vidgalblk_caption_tags() ) ); ?>"
			>
				<?php if ( $vidgalblk_thumb ) : ?>
					<figure class="galleryFigure">
						<img src="<?php echo esc_url( $vidgalblk_thumb ); ?>" alt="<?php echo esc_attr( $vidgalblk_alt ); ?>" loading="lazy" decoding="async" />
					</figure>
				<?php endif; ?>

				<?php if ( $vidgalblk_show_caption && '' !== $vidgalblk_caption ) : ?>
					<div class="galleryItemCaption"><?php echo wp_kses( $vidgalblk_caption, vidgalblk_caption_tags() ); ?></div>
				<?php endif; ?>

				<?php if ( $vidgalblk_show_play_icon ) : ?>
					<span class="playIcon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg></span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>
</div></div>
<?php
/** This document's counterpart -- see `vidgalblk_before_block` above. */
do_action( 'vidgalblk_after_block', $attributes, $vidgalblk_id );
