<?php
namespace VIDGALBLK;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ShortCode {
    function __construct() {
        add_shortcode('video_gallery', [$this, 'vidgalblk_shortcode']);
    }
    function vidgalblk_shortcode($atts){
        $atts = shortcode_atts( array(
            'id' => 0,
        ), $atts, 'video_gallery' );

        $post_id = absint( $atts['id'] );
        if ( ! $post_id ) {
            return '';
        }

        $post = get_post( $post_id );
        if ( !$post || $post->post_type !== 'video-gallery-block' ) {
            return '';
        }

        if ( post_password_required( $post ) ) {
            return get_the_password_form( $post );
        }

        switch ( $post->post_status ) {
            case 'publish':
                return $this->displayContent( $post );
                
            case 'private':
                if (current_user_can('read_private_posts')) {
                    return $this->displayContent( $post );
                }
                return '';
                
            case 'draft':
            case 'pending':
            case 'future':
                if ( current_user_can( 'edit_post', $post_id ) ) {
                    return $this->displayContent( $post );
                }
                return '';
                
            default:
                return '';
        }
    }
    /**
     * Render the gallery a Video Gallery post holds.
     *
     * The block name is checked rather than the output being run through
     * `wp_kses_post()`. That filter was doing two unhelpful things: it could not
     * make the output any safer -- `render.php` escapes everything it prints,
     * and the block declares `html: false` so there is no raw markup to launder
     * -- while it silently removed the `<script type="application/ld+json">`
     * that carries the video SEO markup, since kses allows no `<script>` at
     * all. So a gallery placed with the shortcode described none of its videos
     * to search engines, and one placed with the block described all of them.
     *
     * Checking the name is the stricter half of what kses was standing in for:
     * before, whatever block happened to be first got rendered.
     */
    function displayContent( $post ){
        $blocks = parse_blocks( $post->post_content );

        if ( empty( $blocks[0]['blockName'] ) || 'vgb/video-gallery' !== $blocks[0]['blockName'] ) {
            return '';
        }

        return render_block( $blocks[0] );
    }
}
