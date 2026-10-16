<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Global appearance defaults, overridable per-gallery via shortcode/block attributes.
 */
function pdf_gallery_get_settings(): array
{
    $defaults = [
        'accent_color'  => '#7db3da',
        'surface_color' => '#ffffff',
        'ink_color'     => '#1b1b1b',
        'radius'        => 16,
        'default_layout' => 'grid',
    ];

    $saved = get_option('pdf_gallery_viewer_settings', []);
    if (!is_array($saved)) {
        $saved = [];
    }

    return array_merge($defaults, $saved);
}

/**
 * "r, g, b" components for a #rrggbb hex color, for use inside rgba(...).
 * Falls back to the default accent's components if $hex is invalid.
 */
function pdf_gallery_hex_to_rgb_triplet(string $hex): string
{
    $hex = ltrim($hex, '#');
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        return '125, 179, 218'; // default accent (#7db3da)
    }
    return implode(', ', [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ]);
}

function pdf_gallery_sanitize_settings($input): array
{
    $current = pdf_gallery_get_settings();
    $input = is_array($input) ? $input : [];

    $hex = function ($value, $fallback) {
        $sanitized = sanitize_hex_color((string) $value);
        return $sanitized ? $sanitized : $fallback;
    };

    $layout = isset($input['default_layout']) ? (string) $input['default_layout'] : $current['default_layout'];
    if (!in_array($layout, ['grid', 'list', 'slider'], true)) {
        $layout = $current['default_layout'];
    }

    return [
        'accent_color'   => $hex($input['accent_color'] ?? '', $current['accent_color']),
        'surface_color'  => $hex($input['surface_color'] ?? '', $current['surface_color']),
        'ink_color'      => $hex($input['ink_color'] ?? '', $current['ink_color']),
        'radius'         => isset($input['radius']) ? max(0, min(40, (int) $input['radius'])) : $current['radius'],
        'default_layout' => $layout,
    ];
}

function pdf_gallery_register_settings()
{
    register_setting('pdf_gallery_viewer_settings_group', 'pdf_gallery_viewer_settings', [
        'type'              => 'array',
        'sanitize_callback' => 'pdf_gallery_sanitize_settings',
        'default'           => pdf_gallery_get_settings(),
    ]);

    add_settings_section(
        'pdf_gallery_appearance_section',
        __('Appearance', 'pdf-gallery-viewer'),
        function () {
            echo '<p>' . esc_html__('Default look for all galleries. Individual galleries can override the accent color and layout via shortcode/block attributes.', 'pdf-gallery-viewer') . '</p>';
        },
        'pdf-gallery-viewer'
    );

    $fields = [
        'accent_color'  => __('Accent color', 'pdf-gallery-viewer'),
        'surface_color' => __('Surface (card background) color', 'pdf-gallery-viewer'),
        'ink_color'     => __('Text color', 'pdf-gallery-viewer'),
    ];

    foreach ($fields as $key => $label) {
        add_settings_field(
            'pdf_gallery_' . $key,
            $label,
            function () use ($key) {
                $settings = pdf_gallery_get_settings();
                printf(
                    '<input type="text" class="pgv-color-field" name="pdf_gallery_viewer_settings[%1$s]" value="%2$s" data-default-color="%2$s" />',
                    esc_attr($key),
                    esc_attr($settings[$key])
                );
            },
            'pdf-gallery-viewer',
            'pdf_gallery_appearance_section'
        );
    }

    add_settings_field(
        'pdf_gallery_radius',
        __('Corner radius (px)', 'pdf-gallery-viewer'),
        function () {
            $settings = pdf_gallery_get_settings();
            printf(
                '<input type="number" min="0" max="40" name="pdf_gallery_viewer_settings[radius]" value="%d" class="small-text" />',
                (int) $settings['radius']
            );
        },
        'pdf-gallery-viewer',
        'pdf_gallery_appearance_section'
    );

    add_settings_field(
        'pdf_gallery_default_layout',
        __('Default layout', 'pdf-gallery-viewer'),
        function () {
            $settings = pdf_gallery_get_settings();
            $options = [
                'grid'   => __('Grid', 'pdf-gallery-viewer'),
                'list'   => __('List', 'pdf-gallery-viewer'),
                'slider' => __('Slider', 'pdf-gallery-viewer'),
            ];
            echo '<select name="pdf_gallery_viewer_settings[default_layout]">';
            foreach ($options as $value => $label) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($value),
                    selected($settings['default_layout'], $value, false),
                    esc_html($label)
                );
            }
            echo '</select>';
            echo '<p class="description">' . esc_html__('Used when a [pdf_gallery_container] or block does not specify a layout.', 'pdf-gallery-viewer') . '</p>';
        },
        'pdf-gallery-viewer',
        'pdf_gallery_appearance_section'
    );
}
add_action('admin_init', 'pdf_gallery_register_settings');

function pdf_gallery_add_settings_page()
{
    add_options_page(
        __('PDF Gallery Viewer', 'pdf-gallery-viewer'),
        __('PDF Gallery Viewer', 'pdf-gallery-viewer'),
        'manage_options',
        'pdf-gallery-viewer',
        'pdf_gallery_render_settings_page'
    );
}
add_action('admin_menu', 'pdf_gallery_add_settings_page');

function pdf_gallery_render_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }
?>
    <div class="wrap">
        <h1><?php esc_html_e('PDF Gallery Viewer', 'pdf-gallery-viewer'); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('pdf_gallery_viewer_settings_group');
            do_settings_sections('pdf-gallery-viewer');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

function pdf_gallery_settings_enqueue_color_picker($hook)
{
    if ($hook !== 'settings_page_pdf-gallery-viewer') {
        return;
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_add_inline_script('wp-color-picker', 'jQuery(function($){ $(".pgv-color-field").wpColorPicker(); });');
}
add_action('admin_enqueue_scripts', 'pdf_gallery_settings_enqueue_color_picker');
