<?php
/**
 * Plugin Name: Video Gallery Block
 * Description: Display your videos as gallery in a professional way.
 * Version: 1.5.4
 * Requires PHP: 7.4
 * Requires at least: 6.5
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: video-gallery-block
 */

if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('vgb_fs')) {
    vgb_fs()->set_basename(true, __FILE__);
} else {
    /*
     * Constants.
     *
     * The version doubles as the asset cache-buster. Timestamping it defeats
     * caching entirely, so that is now limited to a site that is both on
     * `localhost` and actually in debug mode -- it used to happen on any site
     * served from that host name, cached or not.
     *
     * The host is compared without its port, because a local dev server (like
     * WordPress Studio, on `localhost:8881`) sends `HTTP_HOST` as
     * `localhost:8881`, not `localhost` -- an exact-string match against the
     * bare word silently never matched here, so this constant stayed frozen
     * at the release version on every rebuild, browsers kept serving the
     * cached JS/CSS from the first page load, and a source fix could rebuild
     * successfully yet never visibly appear.
     */
    define('VIDGALBLK_PLUGIN_VERSION', (
        defined('WP_DEBUG') && WP_DEBUG
        && isset($_SERVER['HTTP_HOST'])
        && 'localhost' === strtok(sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])), ':')
    ) ? time() : '1.5.4');
    define('VIDGALBLK_DIR_URL', plugin_dir_url(__FILE__));
    define('VIDGALBLK_PUBLIC_DIR', VIDGALBLK_DIR_URL . 'public/');
    define('VIDGALBLK_DIR_PATH', plugin_dir_path(__FILE__));

	require_once VIDGALBLK_DIR_PATH . '/includes/fs-lite.php';

    // Used by the block's server-side render; loaded before anything can render.
    require_once VIDGALBLK_DIR_PATH . '/includes/helpers.php';

    require_once VIDGALBLK_DIR_PATH . '/includes/rootPlugin/plugin.php';

    // Ready-made sections for the inserter.
    require_once VIDGALBLK_DIR_PATH . '/includes/patterns.php';

    // Main plugin class
    if (!class_exists('VidGalBlkPlugin')) {
        class VidGalBlkPlugin {
            public function __construct() {
                add_action('enqueue_block_assets', [$this, 'enqueueBlockAssets']);
                add_action( 'enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets'] );
                add_action('enqueue_block_editor_assets', [$this, 'vidgalblkEnqueueBlockEditorAssets']);

                // Redirect to the Help & Demos page on first activation.
                register_activation_hook( __FILE__, [$this, 'onActivation'] );
                add_action( 'admin_init', [$this, 'maybeRedirectAfterActivation'] );
            }

            /**
             * Set a flag so we know a redirect is needed on the next admin page load.
             */
            public function onActivation() {
                update_option( 'vidgalblk_activation_redirect', true );
            }

            /**
             * Redirect to the Help & Demos dashboard page once after activation.
             */
            public function maybeRedirectAfterActivation() {
                if ( ! get_option( 'vidgalblk_activation_redirect', false ) ) {
                    return;
                }

                delete_option( 'vidgalblk_activation_redirect' );

                // Don't redirect on bulk activate or WP-CLI.
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $activate_multi = isset( $_GET['activate-multi'] ) ? sanitize_text_field( wp_unslash( $_GET['activate-multi'] ) ) : '';
                if ( wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) || ! empty( $activate_multi ) ) {
                    return;
                }

                wp_safe_redirect( admin_url( 'edit.php?post_type=video-gallery-block&page=vgb-help-demo' ) );
                exit;
            }

            public function enqueueBlockAssets() {
                wp_register_script('plyr', VIDGALBLK_PUBLIC_DIR . 'js/plyr.js', [], '3.8.4', true);
                wp_register_style('plyr', VIDGALBLK_PUBLIC_DIR . 'css/plyr.css', [], '3.8.4');

                // The front-end script has translatable strings of its own now
                // (the accessible label on a thumbnail with no caption).
                wp_set_script_translations( 'vgb-video-gallery-view-script', 'video-gallery-block', VIDGALBLK_DIR_PATH . 'languages' );
            }


            	/**
			 * Enqueues assets for the block editor.
			 * 
			 * @return void
			 */
			public function enqueueBlockEditorAssets(){
				/*
				 * The handle is the one core generates from the block name and
				 * the block.json field: vgb/video-gallery + editorScript. It was
				 * written here as `vgb-video-gallery-block-editor-script`, which
				 * is not a handle that exists -- so this URL was never injected
				 * and the editor never loaded its translations either.
				 */
				wp_add_inline_script( 'vgb-video-gallery-editor-script', sprintf(
					'const vidgalblkpricingurl = %s;',
					wp_json_encode( admin_url( 'edit.php?post_type=video-gallery-block&page=vgb-help-demo#pricing' ) )
				), 'before' );
			}


            public function vidgalblkEnqueueBlockEditorAssets() {
                wp_enqueue_script('plyr');
                wp_enqueue_style('plyr');

				wp_set_script_translations( 'vgb-video-gallery-editor-script', 'video-gallery-block', VIDGALBLK_DIR_PATH . 'languages' );
            }
        }
        new VidGalBlkPlugin();
    }

    // Add custom block category.
    // function vidgalblk_register_block_category( $categories ) {
    //     array_unshift($categories, [
    //         'slug'  => 'videoblocks',
    //         'title' => __('Video Gallery', 'video-gallery-block'),
    //         'icon'  => 'video-alt',
    //     ]);
    //     return $categories;
    // }
    // add_filter( 'block_categories_all', 'vidgalblk_register_block_category' );
}
