<?php
/**
 * Plugin Name: SVG Icon Block
 * Description: Easily add SVG icons to your Gutenberg block editor with the SVG Icon Block. 
 * Author: Zakaria Binsaifullah
 * Author URI: https://www.makegutenblock.com
 * Version: 1.1.0
 * Text Domain: svg-icon-block
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package SvgibBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Svgib_Block ' ) ) {

	/**
	 * Svgib Blocks Final Class
	 *
	 * @since 1.0.0
	 * @package SvgibBlock
	 */
	final class Svgib_Block {

		// Plugin version
		const VERSION = '1.1.0';

		/**
		 * Svgib Blocks Instance
		 *
		 * @since 1.0.0
		 */
		private static $instance;

		/**
		 * Svgib Blocks Constructor
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function __construct() {
			$this->constants();
			$this->includes();
		}

		/**
		 * Svgib Blocks Define Constants
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function constants() {
			if ( ! defined( 'SVGIB_VERSION' ) ) {
				define( 'SVGIB_VERSION', self::VERSION );
			}

			if ( ! defined( 'SVGIB__FILE__' ) ) {
				define( 'SVGIB__FILE__', __FILE__ );
			}

			if ( ! defined( 'SVGIB_URL_FILE' ) ) {
				define( 'SVGIB_URL_FILE', plugin_dir_url( __FILE__ ) );
			}

			if ( ! defined( 'SVGIB_PLUGIN_DIR' ) ) {
				define( 'SVGIB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'SVGIB_URL' ) ) {
				define( 'SVGIB_URL', plugins_url( '/', plugin_dir_path( __FILE__ ) ) );
			}
		}

		/**
		 * Svgib Blocks Instance
		 *
		 * @since 1.0.0
		 * @return SvgibBlock
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Svgib Blocks Includes Files
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function includes() {
			require_once trailingslashit( SVGIB_PLUGIN_DIR ) . 'inc/svg-icon-block-loader.php';
		}
	}

}

/**
 * Svgib Block Instance
 *
 * @since 1.0.0
 * @return SvgibBlock
 */
function svgib_block_init() {
	return Svgib_Block::get_instance();
}

/**
 * Initialize Svgib Block Plugin
 * 
 * @since 1.0.0
 */
svgib_block_init(); // Initialize the Svgib Blocks class.
