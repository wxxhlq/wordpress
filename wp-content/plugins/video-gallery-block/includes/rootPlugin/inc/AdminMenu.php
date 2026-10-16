<?php
namespace VIDGALBLK;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AdminMenu  {
    function __construct() {
        add_action('admin_menu', [$this, 'adminMenu']);
    }
    function adminMenu(){
        add_submenu_page(
            'edit.php?post_type=video-gallery-block',
            __('Help - bPlugins', 'video-gallery-block'),
            __('Help & Demos', 'video-gallery-block'),
            'manage_options',
            'vgb-help-demo',
            [$this, 'renderDashboardPage']
        );
    }

    function renderDashboardPage(){
        ?>
            <div
                id='vidgalblkDashboard'
                data-info='<?php echo esc_attr( wp_json_encode( [
                    'version' => VIDGALBLK_PLUGIN_VERSION,
                    'adminUrl' => admin_url(), 
                    'nonce' => wp_create_nonce('vidgalblk_activation_nonce'),
                    'uninstallNonce' => wp_create_nonce('vidgalblk_activation_nonce'),
                    'deleteDataOnUninstall' => get_option('vidgalblk_delete_data_on_uninstall', false),
                ] ) ); ?>'
            ></div>
        <?php
    }
}
