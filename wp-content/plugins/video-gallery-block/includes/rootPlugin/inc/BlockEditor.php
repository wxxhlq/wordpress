<?php
/**
 * Keeping the block editor on for the Video Gallery post type.
 *
 * A Video Gallery is not a document with some blocks in it. The post type is
 * registered with `template => [['vgb/video-gallery']]` and `template_lock =>
 * 'all'`: the whole editing experience IS that one block, inserted for you and
 * not removable. There is nothing else on the screen.
 *
 * So when something switches the block editor off, the screen does not fall
 * back to a lesser version of itself -- it falls back to an empty TinyMCE box
 * with a title field above it and no way to build anything. Which is exactly
 * what it looks like: a plugin that is broken, or missing its options.
 *
 * That is not hypothetical. It is the most common support report this plugin
 * gets, and it comes from sites running the Classic Editor plugin, one of the
 * "Disable Gutenberg" plugins, or a theme that turns the block editor off --
 * Flatsome among them. None of those is misbehaving; they were asked to switch
 * the block editor off and they did.
 *
 * This says: not for this one post type. Posts, pages and every other post type
 * keep whatever editor the site has chosen. Only `video-gallery-block` is
 * forced back, because for that one the alternative is not a different editor,
 * it is no editor at all.
 *
 * Late priority on purpose -- the whole point is to have the last word over the
 * plugin or theme that turned it off. `vidgalblk_force_block_editor` is there
 * for the site that genuinely wants the old behaviour back.
 *
 * @package video-gallery-block
 */

namespace VIDGALBLK;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\BlockEditor' ) ) {

class BlockEditor {

	/**
	 * The post type this is about.
	 */
	const POST_TYPE = 'video-gallery-block';

	/**
	 * Last word, deliberately. See the note at the top.
	 */
	const PRIORITY = 9999;

	public function __construct() {
		add_filter( 'use_block_editor_for_post_type', [ $this, 'for_post_type' ], self::PRIORITY, 2 );
		add_filter( 'use_block_editor_for_post', [ $this, 'for_post' ], self::PRIORITY, 2 );

		// The Gutenberg feature plugin's own older filter, for a site running it.
		add_filter( 'gutenberg_can_edit_post_type', [ $this, 'for_post_type' ], self::PRIORITY, 2 );

		/*
		 * The Classic Editor plugin asks this before deciding what to offer, and
		 * answering it is politer than only overriding the result: it stops that
		 * plugin from drawing an "Edit (Classic)" link on a screen where the
		 * classic editor cannot work.
		 */
		add_filter( 'classic_editor_enabled_editors_for_post_type', [ $this, 'classic_editor_choice' ], self::PRIORITY, 2 );

		add_action( 'admin_notices', [ $this, 'stranded_notice' ] );
	}

	/**
	 * Is the override switched on for this site?
	 *
	 * @return bool
	 */
	private function enabled() {
		/**
		 * Allow a site to stop this plugin insisting on the block editor.
		 *
		 * Switching it off leaves the Video Gallery screen showing whatever
		 * editor the site is configured for, which for this post type means an
		 * empty box. There is a reason it defaults to true.
		 *
		 * @param bool $force Whether to force the block editor on.
		 */
		return (bool) apply_filters( 'vidgalblk_force_block_editor', true );
	}

	/**
	 * Force the block editor on for this post type only.
	 *
	 * @param bool   $use       What has been decided so far.
	 * @param string $post_type Post type being decided.
	 * @return bool
	 */
	public function for_post_type( $use, $post_type ) {
		if ( self::POST_TYPE !== $post_type || ! $this->enabled() ) {
			return $use;
		}

		return true;
	}

	/**
	 * The same, for the per-post decision.
	 *
	 * A separate filter and it matters: the Classic Editor plugin, in its
	 * "allow users to switch" mode, leaves the post type alone and answers this
	 * one instead, off a `classic-editor` query argument. Overriding only the
	 * post-type filter would be silently undone by a link the user clicked.
	 *
	 * @param bool     $use  What has been decided so far.
	 * @param \WP_Post $post Post being edited.
	 * @return bool
	 */
	public function for_post( $use, $post ) {
		if ( empty( $post->post_type ) || self::POST_TYPE !== $post->post_type || ! $this->enabled() ) {
			return $use;
		}

		return true;
	}

	/**
	 * Tell the Classic Editor plugin this post type is block-only.
	 *
	 * @param array  $editors   { classic_editor: bool, block_editor: bool }.
	 * @param string $post_type Post type being asked about.
	 * @return array
	 */
	public function classic_editor_choice( $editors, $post_type ) {
		if ( self::POST_TYPE !== $post_type || ! $this->enabled() ) {
			return $editors;
		}

		return [
			'classic_editor' => false,
			'block_editor'   => true,
		];
	}

	/**
	 * If the classic editor loads here anyway, say why.
	 *
	 * The filters above handle every well-behaved way of turning the block
	 * editor off. A plugin that takes over the whole screen through
	 * `replace_editor` cannot be argued with from here -- but the user staring
	 * at an empty box can at least be told what happened, instead of being left
	 * to conclude the plugin does not work.
	 */
	public function stranded_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'post' !== $screen->base || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// `is_block_editor` is set by core once the screen has decided. On the
		// block editor there is nothing to warn about.
		if ( ! empty( $screen->is_block_editor ) ) {
			return;
		}

		$message = __( 'Video Gallery needs the WordPress block editor, and something on this site has switched it off for this screen — usually the Classic Editor plugin, a "Disable Gutenberg" plugin, or a theme option. The empty box below is the classic editor; the gallery builder cannot load into it. Switch the block editor back on for this site, or ask your developer to, and this screen will work.', 'video-gallery-block' );

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong></p><p>%s</p></div>',
			esc_html__( 'The gallery builder cannot load.', 'video-gallery-block' ),
			esc_html( $message )
		);
	}
}
}
