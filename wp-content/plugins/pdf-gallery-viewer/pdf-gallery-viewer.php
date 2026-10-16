<?php
/*
Plugin Name: PDF Gallery Viewer
Description: A WordPress plugin that creates a responsive PDF gallery with thumbnail previews via JavaScript.
Version: 1.8.1
Author: <a href="https://sergiocomella.it">Panezio</a>
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: pdf-gallery-viewer
*/

if (! defined('ABSPATH')) exit; // Exit if accessed directly

require_once plugin_dir_path(__FILE__) . 'admin-settings.php';

// Internal globals used to collect [pdf_gallery] items inside a [pdf_gallery_container].
global $pdf_gallery_collecting, $pdf_gallery_collected_items;
$pdf_gallery_collecting = false;
$pdf_gallery_collected_items = [];

/**
 * Render a single gallery item.
 */
function pdf_gallery_render_item(string $url, string $title, string $text_link = 'View'): string
{
    $default_icon = plugin_dir_url(__FILE__) . 'pdf-icon.png';
    $truncated = wp_trim_words($title, 3, '...');

    return '<div class="pdf-gallery-item" data-url="' . esc_url($url) . '">
        <div class="pdf-icon">
            <img src="' . esc_url($default_icon) . '" alt="Anteprima PDF">
        </div>
       <div class="pdf-info">
        <div class="pdf-title" title="' . esc_attr($title) . '">' . esc_html($truncated) . '</div>

        <div class="pdf-actions">
           <a href="' . esc_url($url) . '" target="_blank" class="pdf-view-link" aria-label="View PDF" title="View">
            <i class="fas fa-eye" aria-hidden="true"></i>
            </a>
            <a href="' . esc_url($url) . '" class="pdf-download-link" download aria-label="Download PDF" title="Download">
            <i class="fas fa-download" aria-hidden="true"></i>
            </a>
        </div>
        </div>
    </div>';
}

/**
 * Returns true when the current request needs gallery assets.
 * Checks shortcodes in post content and Gutenberg blocks.
 */
function pdf_gallery_page_has_gallery(): bool
{
    global $post;
    if (!is_a($post, 'WP_Post')) return false;
    return has_shortcode($post->post_content, 'pdf_gallery')
        || has_shortcode($post->post_content, 'pdf_gallery_container')
        || has_block('pdf-gallery/pdf-multi-block', $post);
}

// Enqueue style and scripts only on pages that contain a gallery.
function pdf_gallery_enqueue_styles()
{
    if (!pdf_gallery_page_has_gallery()) return;

    wp_enqueue_style(
        'pdf-gallery-style',
        plugin_dir_url(__FILE__) . 'assets/css/pdf-gallery-viewer.css',
        [],
        filemtime(plugin_dir_path(__FILE__) . 'assets/css/pdf-gallery-viewer.css')
    );

    // Site-wide appearance defaults from Settings > PDF Gallery Viewer (values are
    // sanitized to hex colors / clamped ints on save, safe to print as raw CSS).
    $settings = pdf_gallery_get_settings();
    wp_add_inline_style('pdf-gallery-style', sprintf(
        ':root{--pgv-accent:%s;--pgv-accent-soft:rgba(%s,0.12);--pgv-surface:%s;--pgv-ink:%s;--pgv-radius:%dpx;}',
        $settings['accent_color'],
        pdf_gallery_hex_to_rgb_triplet($settings['accent_color']),
        $settings['surface_color'],
        $settings['ink_color'],
        $settings['radius']
    ));

    wp_enqueue_style(
        'swiper-css',
        plugin_dir_url(__FILE__) . 'assets/css/swiper-bundle.min.css',
        [],
        '11.0.0'
    );

    // Font Awesome — served locally (no CDN dependency).
    wp_enqueue_style(
        'fontawesome',
        plugin_dir_url(__FILE__) . 'assets/css/fontawesome.min.css',
        [],
        '6.5.0'
    );

    wp_enqueue_script(
        'pdfjs-lib',
        plugin_dir_url(__FILE__) . 'assets/js/pdf.min.js',
        [],
        '3.11.174',
        true
    );

    wp_enqueue_script(
        'pdf-thumbnail-generator',
        plugin_dir_url(__FILE__) . 'assets/js/thumbnail-generator.js',
        ['pdfjs-lib'],
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/thumbnail-generator.js'),
        true
    );

    wp_enqueue_script(
        'pdf-modal-viewer',
        plugin_dir_url(__FILE__) . 'assets/js/modal-viewer.js',
        ['pdfjs-lib'],
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/modal-viewer.js'),
        true
    );

    wp_enqueue_script(
        'swiper-js',
        plugin_dir_url(__FILE__) . 'assets/js/swiper-bundle.min.js',
        [],
        '11.0.0',
        true
    );

    wp_enqueue_script(
        'pdf-slider-init',
        plugin_dir_url(__FILE__) . 'assets/js/slider-init.js',
        ['swiper-js'],
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/slider-init.js'),
        true
    );

    wp_enqueue_script(
        'pdf-gallery-sort-ui',
        plugin_dir_url(__FILE__) . 'assets/js/sort-ui.js',
        [],
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/sort-ui.js'),
        true
    );

    $post_id = (int) get_queried_object_id();
    $can_edit = $post_id > 0 && current_user_can('edit_post', $post_id);
    wp_localize_script('pdf-gallery-sort-ui', 'PDFGalleryViewer', [
        'ajaxUrl'   => admin_url('admin-ajax.php'),
        'nonce'     => wp_create_nonce('pdf_gallery_order_nonce'),
        'postId'    => $post_id,
        'canEdit'   => $can_edit,
        'workerSrc' => plugin_dir_url(__FILE__) . 'assets/js/pdf.worker.min.js',
        'i18n'      => [
            'save'       => __('Save order', 'pdf-gallery-viewer'),
            'saved'      => __('Order saved', 'pdf-gallery-viewer'),
            'saveError'  => __('Could not save order', 'pdf-gallery-viewer'),
            'notAllowed' => __('Not allowed', 'pdf-gallery-viewer'),
        ],
    ]);
}
add_action('wp_enqueue_scripts', 'pdf_gallery_enqueue_styles');

/**
 * Persist manual ordering for a specific gallery instance (per-page), managed by admins only.
 */
function pdf_gallery_save_order_ajax()
{
    if (!check_ajax_referer('pdf_gallery_order_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'invalid_nonce'], 403);
    }

    $post_id = isset($_POST['postId']) ? (int) $_POST['postId'] : 0;
    if ($post_id <= 0 || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }

    $gallery_id = isset($_POST['galleryId']) ? sanitize_key(wp_unslash($_POST['galleryId'])) : '';
    if ($gallery_id === '') {
        wp_send_json_error(['message' => 'missing_gallery_id'], 400);
    }

    $order = isset($_POST['order']) && is_array($_POST['order']) ? $_POST['order'] : [];
    $order = array_values(array_filter(array_map(function ($u) {
        return esc_url_raw(wp_unslash($u));
    }, $order)));

    $all_orders = get_post_meta($post_id, '_pdf_gallery_orders', true);
    if (!is_array($all_orders)) {
        $all_orders = [];
    }
    $all_orders[$gallery_id] = $order;

    update_post_meta($post_id, '_pdf_gallery_orders', $all_orders);

    wp_send_json_success(['message' => 'ok']);
}
add_action('wp_ajax_pdf_gallery_save_order', 'pdf_gallery_save_order_ajax');


// Shortcode for single PDF item
function pdf_gallery_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'url' => '',
        'title' => '',
        'text_link' => "View"
    ), $atts, 'pdf_gallery');

    if (empty($atts['url'])) {
        return '<p>PDF URL required</p>';
    }

    // If called inside [pdf_gallery_container], collect items and let the container render them.
    global $pdf_gallery_collecting, $pdf_gallery_collected_items;
    if ($pdf_gallery_collecting) {
        $pdf_gallery_collected_items[] = [
            'url' => esc_url_raw($atts['url']),
            'title' => (string) $atts['title'],
            'filename' => basename((string) $atts['url']),
            'text_link' => (string) $atts['text_link'],
        ];
        return '';
    }

    return pdf_gallery_render_item((string) $atts['url'], (string) $atts['title'], (string) $atts['text_link']);
}
add_shortcode('pdf_gallery', 'pdf_gallery_shortcode');

// Shortcode for dynamic container
function pdf_gallery_container_shortcode($atts, $content = null)
{
    $settings = pdf_gallery_get_settings();

    $atts = shortcode_atts([
        // Optional identifier to persist manual order on this page.
        // If omitted, a stable id is generated per-render (postId + instance index).
        'id' => '',
        'category' => '',
        'tag' => '',
        'post_id' => '',
        'layout' => $settings['default_layout'],
        // Optional per-gallery override of the accent color set in Settings > PDF Gallery Viewer.
        'accent_color' => '',

        // Sorting / ordering
        // order_by: none | title | filename | manual
        'order_by' => 'none',
        // order: asc | desc
        'order' => 'asc',
        // manual_mode: fifo | lifo (how to place newly added items when manual ordering is used)
        'manual_mode' => 'fifo',
        // ui: 1 | 0 (show sorting controls)
        'ui' => '1',
    ], $atts, 'pdf_gallery_container');

    // Identify this gallery instance (used to persist admin-defined manual order).
    static $instance = 0;
    $instance++;
    $current_post_id = (int) get_queried_object_id();
    $gallery_id = $atts['id'] !== '' ? sanitize_key($atts['id']) : ('pgv-' . $current_post_id . '-' . $instance);

    $items = [];

    if (empty($atts['category']) && empty($atts['tag']) && empty($atts['post_id'])) {
        // Collect items from nested [pdf_gallery] shortcodes.
        global $pdf_gallery_collecting, $pdf_gallery_collected_items;
        $pdf_gallery_collecting = true;
        $pdf_gallery_collected_items = [];
        do_shortcode($content);
        $pdf_gallery_collecting = false;

        $items = $pdf_gallery_collected_items;
    } else {
        $query_args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'post_mime_type' => 'application/pdf',
        ];

        if (!empty($atts['category'])) {
            $query_args['tax_query'][] = [
                'taxonomy' => 'category',
                'field' => 'slug',
                'terms' => explode(',', $atts['category'])
            ];
        }

        if (!empty($atts['tag'])) {
            $query_args['tax_query'][] = [
                'taxonomy' => 'post_tag',
                'field' => 'slug',
                'terms' => explode(',', $atts['tag'])
            ];
        }

        if (!empty($atts['post_id'])) {
            $query_args['post_parent'] = (int) $atts['post_id'];
        }

        $pdfs = get_posts($query_args);

        foreach ($pdfs as $pdf) {
            $url = wp_get_attachment_url($pdf->ID);
            $title = get_the_title($pdf->ID);
            $items[] = [
                'url' => (string) $url,
                'title' => (string) $title,
                'filename' => basename((string) $url),
                'text_link' => 'View',
            ];
        }
    }

    // Apply ordering server-side (authoritative for all visitors).
    $order_by = strtolower((string) $atts['order_by']);
    $order_dir = strtolower((string) $atts['order']);
    $manual_mode = strtolower((string) $atts['manual_mode']);

    if ($order_by === 'title' || $order_by === 'filename') {
        usort($items, function ($a, $b) use ($order_by, $order_dir) {
            $result = strcasecmp((string) ($a[$order_by] ?? ''), (string) ($b[$order_by] ?? ''));
            return $order_dir === 'desc' ? -$result : $result;
        });
    } elseif ($current_post_id > 0) {
        // Apply a previously saved manual order whenever one exists (order_by "none" or
        // "manual"). The Gutenberg block always emits order_by="none", so gating this on
        // order_by === "manual" would silently drop every drag & drop save on next load.
        $all_orders = get_post_meta($current_post_id, '_pdf_gallery_orders', true);
        $saved = (is_array($all_orders) && isset($all_orders[$gallery_id]) && is_array($all_orders[$gallery_id])) ? $all_orders[$gallery_id] : [];

        if (!empty($saved)) {
            $by_url = [];
            foreach ($items as $it) {
                $by_url[(string) $it['url']] = $it;
            }

            $ordered = [];
            foreach ($saved as $u) {
                if (isset($by_url[$u])) {
                    $ordered[] = $by_url[$u];
                    unset($by_url[$u]);
                }
            }

            // Items newly added since the last save: FIFO = append, LIFO = prepend.
            $leftovers = array_values($by_url);
            $items = ($manual_mode === 'lifo') ? array_merge($leftovers, $ordered) : array_merge($ordered, $leftovers);
        }
    }

    // Render items.
    $output = '';
    foreach ($items as $it) {
        $output .= pdf_gallery_render_item((string) $it['url'], (string) $it['title'], (string) ($it['text_link'] ?? 'View'));
    }

    // Wrap gallery + expose sorting settings for the UI layer.
    $layout_class = 'layout-' . esc_attr($atts['layout']);
    $data_attrs = sprintf(
        ' data-order-by="%s" data-order="%s" data-manual-mode="%s" data-sort-ui="%s" data-gallery-id="%s" data-post-id="%d"',
        esc_attr($atts['order_by']),
        esc_attr($atts['order']),
        esc_attr($atts['manual_mode']),
        esc_attr($atts['ui']),
        esc_attr($gallery_id),
        $current_post_id
    );

    $controls = '';
    // Controls are only meaningful for grid/list (slider is handled by Swiper and would need a more invasive refresh).
    $can_edit = $current_post_id > 0 && current_user_can('edit_post', $current_post_id);
    if ($atts['ui'] === '1' && $atts['layout'] !== 'slider' && $can_edit) {
        $controls = '<div class="pdf-gallery-controls" aria-label="PDF gallery sorting controls">
            <label class="pdf-gallery-control">
                <span class="pdf-gallery-control-label">Ordina per</span>
                <select class="pdf-gallery-sort-by">
                    <option value="none">Nessuno</option>
                    <option value="title">Titolo</option>
                    <option value="filename">Nome file</option>
                    <option value="manual">Manuale (trascina)</option>
                </select>
            </label>
            <label class="pdf-gallery-control">
                <span class="pdf-gallery-control-label">Direzione</span>
                <select class="pdf-gallery-sort-order">
                    <option value="asc">A → Z</option>
                    <option value="desc">Z → A</option>
                </select>
            </label>
            <label class="pdf-gallery-control pdf-gallery-manual-only">
                <span class="pdf-gallery-control-label">Aggiunte</span>
                <select class="pdf-gallery-manual-mode">
                    <option value="fifo">FIFO (in coda)</option>
                    <option value="lifo">LIFO (in testa)</option>
                </select>
            </label>
            <button type="button" class="pdf-gallery-save" aria-label="Salva ordine" style="display:none">Salva ordine</button>
            <button type="button" class="pdf-gallery-reset" aria-label="Reset ordinamento">Reset</button>
        </div>';
    }

    // Per-gallery accent color override, applied as a CSS custom property on the wrapper
    // so it cascades to child elements without needing a dedicated <style> block.
    $accent_style = '';
    $accent_override = sanitize_hex_color((string) $atts['accent_color']);
    if ($accent_override) {
        $accent_style = ' style="--pgv-accent:' . esc_attr($accent_override)
            . ';--pgv-accent-soft:rgba(' . esc_attr(pdf_gallery_hex_to_rgb_triplet($accent_override)) . ',0.12)"';
    }

    $wrapper = '<div class="pdf-gallery-wrapper"' . $accent_style . '>' . $controls . '<div class="pdf-gallery-container ' . $layout_class . '"' . $data_attrs . '>';

    if ($atts['layout'] === 'slider') {
        $output = '<div class="swiper layout-slider"' . $accent_style . '><div class="swiper-wrapper">'
            . str_replace('pdf-gallery-item', 'swiper-slide pdf-gallery-item', $output)
            . '</div><div class="swiper-pagination"></div><div class="swiper-button-next"></div><div class="swiper-button-prev"></div></div>';
        return $output;
    }

    return $wrapper . $output . '</div></div>';
}
add_shortcode('pdf_gallery_container', 'pdf_gallery_container_shortcode');

// Modal container in footer — only when a gallery was actually rendered on this page.
function pdf_gallery_add_modal_container()
{
    if (!pdf_gallery_page_has_gallery()) return;
?>
    <div id="pdfModal" class="pdf-modal">
        <div class="pdf-modal-content">
            <span class="pdf-modal-close"><i class="fas fa-times"></i></span>
            <div class="pdf-modal-controls">
                <button id="pdfPrevPage"><i class="fas fa-chevron-left"></i></button>
                <span id="pdfPageInfo">1 / ?</span>
                <button id="pdfNextPage"><i class="fas fa-chevron-right"></i></button>
                <button id="pdfZoomIn"><i class="fas fa-search-plus"></i></button>
                <button id="pdfZoomOut"><i class="fas fa-search-minus"></i></button>
                <a id="pdfDownload" href="#" target="_blank" download><i class="fas fa-download"></i></a>
            </div>
            <div id="pdfModalViewer" class="pdf-viewer"></div>
        </div>
    </div>
<?php
}
add_action('wp_footer', 'pdf_gallery_add_modal_container');

// Register block
function pdf_gallery_register_block()
{
    wp_register_script(
        'pdf-gallery-editor',
        plugin_dir_url(__FILE__) . 'blocks/pdf-gallery/index.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
        filemtime(plugin_dir_path(__FILE__) . 'blocks/pdf-gallery/index.js'),
        true          // ← ensure it’s printed in the footer
    );
    register_block_type_from_metadata(__DIR__ . '/blocks/pdf-gallery');
}
add_action('init', 'pdf_gallery_register_block');