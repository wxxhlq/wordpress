<?php
/**
 * My Icons Library
 *
 * A site wide collection of custom SVG icons saved from the block editor.
 * Icons are shared by every user who can edit posts, and every icon is put
 * through the same sanitiser as an uploaded file before it is stored.
 *
 * @since 1.1.0
 * @package SvgibBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Svgib_My_Icons' ) ) {

	/**
	 * Svgib My Icons Class
	 *
	 * @since 1.1.0
	 * @package SvgibBlock
	 */
	class Svgib_My_Icons {

		/**
		 * Option holding the saved icons.
		 */
		const OPTION = 'svgib_my_icons';

		/**
		 * REST namespace and base.
		 */
		const REST_NAMESPACE = 'svgib/v1';
		const REST_BASE      = 'my-icons';

		/**
		 * Maximum number of icons that can be stored.
		 */
		const MAX_ICONS = 200;

		/**
		 * Maximum length of an icon label.
		 */
		const MAX_LABEL_LENGTH = 60;

		/**
		 * Constructor
		 *
		 * @since 1.1.0
		 * @return void
		 */
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		/**
		 * Register the REST routes.
		 *
		 * @since 1.1.0
		 * @return void
		 */
		public function register_routes() {
			register_rest_route(
				self::REST_NAMESPACE,
				'/' . self::REST_BASE,
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_icons' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'add_icon' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => array(
							'label' => array(
								'type'     => 'string',
								'required' => true,
							),
							'svg'   => array(
								'type'     => 'string',
								'required' => true,
							),
						),
					),
				)
			);

			register_rest_route(
				self::REST_NAMESPACE,
				'/' . self::REST_BASE . '/(?P<id>[A-Za-z0-9\-]+)',
				array(
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_icon' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => array(
							'id' => array(
								'type'     => 'string',
								'required' => true,
							),
						),
					),
				)
			);
		}

		/**
		 * Whether the current user may read and manage the icon library.
		 *
		 * The library is shared, so it follows the same bar as editing content.
		 *
		 * @since 1.1.0
		 * @return bool
		 */
		public function check_permission() {
			/**
			 * Filters the capability required to read and manage saved icons.
			 *
			 * @since 1.1.0
			 * @param string $capability Capability name.
			 */
			$capability = apply_filters( 'svgib_my_icons_capability', 'edit_posts' );

			return current_user_can( $capability );
		}

		/**
		 * Return every saved icon.
		 *
		 * @since 1.1.0
		 * @return WP_REST_Response
		 */
		public function get_icons() {
			return rest_ensure_response( self::get_saved_icons() );
		}

		/**
		 * Save a new icon and return the updated collection.
		 *
		 * @since 1.1.0
		 * @param WP_REST_Request $request Request object.
		 * @return WP_REST_Response|WP_Error
		 */
		public function add_icon( $request ) {
			$label = trim( sanitize_text_field( $request->get_param( 'label' ) ) );
			$svg   = (string) $request->get_param( 'svg' );

			if ( '' === $label ) {
				return new WP_Error(
					'svgib_missing_label',
					__( 'Please give the icon a label before saving it.', 'svg-icon-block' ),
					array( 'status' => 400 )
				);
			}

			if ( mb_strlen( $label ) > self::MAX_LABEL_LENGTH ) {
				$label = mb_substr( $label, 0, self::MAX_LABEL_LENGTH );
			}

			$clean = Svgib_SVG_Sanitizer::sanitize( $svg );

			if ( false === $clean ) {
				return new WP_Error(
					'svgib_invalid_svg',
					__( 'That markup could not be processed as a valid SVG image.', 'svg-icon-block' ),
					array( 'status' => 400 )
				);
			}

			$icons = self::get_saved_icons();

			if ( count( $icons ) >= self::MAX_ICONS ) {
				return new WP_Error(
					'svgib_library_full',
					sprintf(
						/* translators: %d: maximum number of saved icons. */
						__( 'You can save up to %d icons. Please remove one first.', 'svg-icon-block' ),
						self::MAX_ICONS
					),
					array( 'status' => 400 )
				);
			}

			// Saving the same markup twice just refreshes its label.
			foreach ( $icons as $index => $icon ) {
				if ( isset( $icon['svg'] ) && $icon['svg'] === $clean ) {
					$icons[ $index ]['label'] = $label;
					self::save_icons( $icons );

					return rest_ensure_response( $icons );
				}
			}

			array_unshift(
				$icons,
				array(
					'id'      => wp_generate_uuid4(),
					'label'   => $label,
					'svg'     => $clean,
					'created' => time(),
				)
			);

			self::save_icons( $icons );

			return rest_ensure_response( $icons );
		}

		/**
		 * Remove an icon and return the updated collection.
		 *
		 * @since 1.1.0
		 * @param WP_REST_Request $request Request object.
		 * @return WP_REST_Response|WP_Error
		 */
		public function delete_icon( $request ) {
			$id    = (string) $request->get_param( 'id' );
			$icons = self::get_saved_icons();

			$remaining = array_values(
				array_filter(
					$icons,
					function ( $icon ) use ( $id ) {
						return ! isset( $icon['id'] ) || $icon['id'] !== $id;
					}
				)
			);

			if ( count( $remaining ) === count( $icons ) ) {
				return new WP_Error(
					'svgib_icon_not_found',
					__( 'That icon no longer exists.', 'svg-icon-block' ),
					array( 'status' => 404 )
				);
			}

			self::save_icons( $remaining );

			return rest_ensure_response( $remaining );
		}

		/**
		 * Read the saved icons, discarding anything malformed.
		 *
		 * @since 1.1.0
		 * @return array
		 */
		public static function get_saved_icons() {
			$icons = get_option( self::OPTION, array() );

			if ( ! is_array( $icons ) ) {
				return array();
			}

			$valid = array();

			foreach ( $icons as $icon ) {
				if ( ! is_array( $icon ) || empty( $icon['id'] ) || empty( $icon['svg'] ) ) {
					continue;
				}

				$valid[] = array(
					'id'      => (string) $icon['id'],
					'label'   => isset( $icon['label'] ) ? (string) $icon['label'] : '',
					'svg'     => (string) $icon['svg'],
					'created' => isset( $icon['created'] ) ? (int) $icon['created'] : 0,
				);
			}

			return $valid;
		}

		/**
		 * Persist the icon collection.
		 *
		 * Stored without autoload: the markup is large and is only ever needed
		 * inside the editor.
		 *
		 * @since 1.1.0
		 * @param array $icons Icons to store.
		 * @return void
		 */
		private static function save_icons( $icons ) {
			update_option( self::OPTION, array_values( $icons ), false );
		}
	}

	new Svgib_My_Icons();
}
