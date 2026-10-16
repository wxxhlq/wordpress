<?php
/**
 * Svgib Blocks Registration
 *
 * @since 1.0.0
 * @package SvgibBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Svgib_Block_Registration' ) ) {

	/**
	 * Svgib Blocks Registration Class
	 *
	 * @since 1.0.0
	 * @package SvgibBlock
	 */
	class Svgib_Block_Registration {

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Initialize the Class
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function init() {
			add_action( 'init', array( $this, 'register_blocks' ) );

			if ( ! wp_is_block_theme() ) {
				add_filter( 'should_load_separate_core_block_assets', '__return_true' );
			}
		}

		/**
		 * Register Blocks
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function register_blocks() {
			$blocks_dir    = trailingslashit( SVGIB_PLUGIN_DIR ) . 'build/blocks';
			$manifest_file = trailingslashit( SVGIB_PLUGIN_DIR ) . 'build/blocks-manifest.php';

			/**
			 * Registers multiple block types from metadata collection.
			 * Added in WordPress 6.9 to further simplify the block registration process.
			 */
			if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
				wp_register_block_types_from_metadata_collection( $blocks_dir, $manifest_file );
				return;
			}

			/**
			 * Registers the block(s) metadata from the `blocks-manifest.php`.
			 * Added in WordPress 6.7 to simplify the block metadata registration process.
			 */
			if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
				wp_register_block_metadata_collection( trailingslashit( SVGIB_PLUGIN_DIR ) . 'build', $manifest_file );
			}

			/**
			 * Registers each block type individually based on the `blocks-manifest.php` file.
			 */
			$manifest_data = require $manifest_file;
			foreach ( array_keys( $manifest_data ) as $block_type ) {
				register_block_type( trailingslashit( $blocks_dir ) . $block_type );
			}
		}
	}

	new Svgib_Block_Registration(); // Initialize the class.
}

