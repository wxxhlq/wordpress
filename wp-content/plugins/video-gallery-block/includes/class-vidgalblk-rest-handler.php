<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIDGALBLK_REST_Handler {

	public function __construct() {
		add_action( 'wp_ajax_vidgalblkSaveUninstallOption', array( $this, 'handle_uninstall_option' ) );
	}

	/**
	 * Handle AJAX for saving uninstall data deletion preference.
	 */
	public function handle_uninstall_option() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : null;
		if ( ! wp_verify_nonce( $nonce, 'vidgalblk_activation_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'video-gallery-block' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'video-gallery-block' ) ) );
		}

		// Support both string 'true' and actual boolean/numeric values
		$raw_enabled = isset( $_POST['enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) : '';
		$enabled = ( 'true' === $raw_enabled || '1' === $raw_enabled );
		update_option( 'vidgalblk_delete_data_on_uninstall', $enabled );

		wp_send_json_success( array(
			'enabled' => $enabled,
			'message' => __( 'Setting saved successfully.', 'video-gallery-block' ),
		) );
	}
}

new VIDGALBLK_REST_Handler();
