<?php
/**
 * Svgib Blocks Main Loader
 *
 * @since 1.0.0
 * @package SvgibBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Svgib_Block_Loader' ) ) {

	/**
	 * Svgib Blocks Loader Class
	 *
	 * @since 1.0.0
	 * @package SvgibBlock
	 */

	class Svgib_Block_Loader {

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function __construct() {
			$this->includes();
		}

		/**
		 * Include Files
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function includes() {
			require_once trailingslashit( SVGIB_PLUGIN_DIR ) . '/inc/classes/register-blocks.php';
			require_once trailingslashit( SVGIB_PLUGIN_DIR ) . '/inc/classes/dynamic-style.php';
			require_once trailingslashit( SVGIB_PLUGIN_DIR ) . '/inc/classes/svg-sanitizer.php';
			require_once trailingslashit( SVGIB_PLUGIN_DIR ) . '/inc/classes/svg-upload.php';
			require_once trailingslashit( SVGIB_PLUGIN_DIR ) . '/inc/classes/my-icons.php';
		}
	}
	
	new Svgib_Block_Loader(); // Initialize the loader class.
}
