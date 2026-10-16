<?php
namespace VIDGALBLK;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Enqueue {
    function __construct() {
        add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
    }
    function adminEnqueueScripts($screen) {
        global $typenow;
        // For post type screens
        if ('video-gallery-block' === $typenow) {
            wp_enqueue_script('vidgalblk-admin-post-js', VIDGALBLK_DIR_URL . 'build/admin/post.js', [], VIDGALBLK_PLUGIN_VERSION, true);
            wp_enqueue_style('vidgalblk-admin-post-css', VIDGALBLK_DIR_URL . 'build/admin/post.css', [], VIDGALBLK_PLUGIN_VERSION);
        }
        // For dashboard/menu page screen
        if ($screen === "video-gallery-block_page_vgb-help-demo") {
            wp_enqueue_script('vidgalblk-admin-dashboard-js', VIDGALBLK_DIR_URL . 'build/admin/dashboard.js', ['react', 'react-dom', 'wp-util'], VIDGALBLK_PLUGIN_VERSION, true);
            wp_enqueue_style('vidgalblk-admin-dashboard-css', VIDGALBLK_DIR_URL . 'build/admin/dashboard.css', [], VIDGALBLK_PLUGIN_VERSION);
            wp_set_script_translations( 'vidgalblk-admin-dashboard-js', 'video-gallery-block', VIDGALBLK_DIR_PATH . 'languages' );
            wp_localize_script('vidgalblk-admin-dashboard-js', 'vidgalblkAdmin', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
            ]);
        }
    }
}
