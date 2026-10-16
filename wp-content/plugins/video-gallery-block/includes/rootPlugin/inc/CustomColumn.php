<?php
namespace VIDGALBLK;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class CustomColumn {
    function __construct() {
        add_filter(
            'manage_video-gallery-block_posts_columns',
            [$this, 'manageColumns']
        );
        add_action(
            'manage_video-gallery-block_posts_custom_column',
            [$this, 'manageCustomColumns'],
            10,
            2
        );
    }
    
    function manageColumns($columns) {
        unset($columns['date']);
        $columns['shortcode'] = __('Shortcode', 'video-gallery-block');
        $columns['date'] = __('Date', 'video-gallery-block');
        return $columns;
    }

    function manageCustomColumns($column_name, $post_id) {
        if ($column_name === 'shortcode') {
            $shortcode = sprintf( '[video_gallery id=%d]', $post_id );
            printf(
                '<div class="bPlAdminShortcode" id="bPlAdminShortcode-%s">
                    <input value="%s" onclick="copyBPlAdminShortcode(\'%s\')" readonly>
                    <span class="tooltip">%s</span>
                </div>',
                esc_attr( $post_id ),
                esc_attr( $shortcode ),
                esc_js( $post_id ),
                esc_html__( 'Copy To Clipboard', 'video-gallery-block' )
            );
        }
    }
}
