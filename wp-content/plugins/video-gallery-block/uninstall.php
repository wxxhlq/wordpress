<?php
/**
 * Uninstall handler for Video Gallery Block.
 *
 * Cleans up plugin data when the plugin is deleted from the admin.
 * Only runs if the user has opted in via the "Delete data on uninstall" setting.
 *
 * @package VIDGALBLK
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$vidgalblk_is_delete_data = get_option( 'vidgalblk_delete_data_on_uninstall', false );

if ( ! $vidgalblk_is_delete_data ) {
	return;
}

// 1. Delete all 'video-gallery-block' custom post type posts and their meta/revisions efficiently.
$vidgalblk_post_ids = get_posts( array(
	'post_type'   => 'video-gallery-block',
	'post_status' => 'any',
	'numberposts' => -1,
	'fields'      => 'ids',
) );

if ( ! empty( $vidgalblk_post_ids ) ) {
	foreach ( $vidgalblk_post_ids as $post_id ) {
		wp_delete_post( $post_id, true ); // Force delete (bypass trash).
	}
}

// 2. Delete cached thumbnails: the Media Library attachments and the options
// that point at them. Named with a shared prefix specifically so this one
// query can find all of them without a table storing the list separately.
global $wpdb;
$vidgalblk_cached_attachment_ids = $wpdb->get_col(
	"SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE 'vidgalblk_cthumb_%'"
);
foreach ( $vidgalblk_cached_attachment_ids as $vidgalblk_attachment_id ) {
	wp_delete_attachment( (int) $vidgalblk_attachment_id, true );
}
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'vidgalblk_cthumb_%'" );

// 3. Delete plugin options.
delete_option( 'vidgalblk_delete_data_on_uninstall' );