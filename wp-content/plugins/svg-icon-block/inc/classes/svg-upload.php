<?php
/**
 * Secure SVG Uploader
 *
 * Allows SVG uploads and sanitises every uploaded file before it is stored in
 * the media library. Sanitisation is allow-list based: anything that is not a
 * known-safe SVG element is dropped, event handlers are stripped and every URL
 * reference is validated against a safe scheme list.
 *
 * @since 1.0.0
 * @package SvgibBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Svgib_SecureSVGUploader' ) ) {

	/**
	 * Svgib Secure SVG Uploader Class
	 *
	 * @since 1.0.0
	 * @package SvgibBlock
	 */
	class Svgib_SecureSVGUploader {

		/**
		 * SVG mime type.
		 */
		const MIME_TYPE = 'image/svg+xml';

		/**
		 * Default maximum size, in bytes, for an uploaded SVG.
		 */
		const MAX_FILE_SIZE = 2097152; // 2 MB.

		/**
		 * WP_Filesystem instance, or false when it is not available.
		 *
		 * @var WP_Filesystem_Base|false|null
		 */
		private $wp_filesystem = null;

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function __construct() {
			add_filter( 'upload_mimes', array( $this, 'add_svg_mime_type' ) );

			// Registered unconditionally: media uploads happen over both admin-ajax
			// and the REST API, and `admin_init` never fires for REST requests.
			add_filter( 'wp_check_filetype_and_ext', array( $this, 'check_svg_filetype' ), 10, 5 );

			add_filter( 'wp_handle_upload_prefilter', array( $this, 'validate_svg' ) );
			add_filter( 'wp_handle_sideload_prefilter', array( $this, 'validate_svg' ) );

			add_filter( 'wp_generate_attachment_metadata', array( $this, 'add_svg_metadata' ), 10, 2 );
			add_filter( 'wp_prepare_attachment_for_js', array( $this, 'fix_svg_thumbnail_display' ), 10, 2 );

			add_action( 'admin_print_styles', array( $this, 'print_media_library_styles' ) );
		}

		/*
		 * -----------------------------------------------------------------
		 * Upload pipeline
		 * -----------------------------------------------------------------
		 */

		/**
		 * Add the SVG mime type to the allowed upload types.
		 *
		 * `.svgz` is intentionally not supported: gzipped markup cannot be
		 * sanitised in place, so allowing it would create a bypass.
		 *
		 * @since 1.0.0
		 * @param array $mimes Allowed mime types.
		 * @return array
		 */
		public function add_svg_mime_type( $mimes ) {
			/**
			 * Filters whether SVG uploads are allowed.
			 *
			 * @since 1.1.0
			 * @param bool $allowed Whether SVG uploads are allowed.
			 */
			if ( ! apply_filters( 'svgib_allow_svg_upload', true ) ) {
				return $mimes;
			}

			$mimes['svg'] = self::MIME_TYPE;

			return $mimes;
		}

		/**
		 * Keep WordPress from rejecting SVG files during its real mime check.
		 *
		 * `finfo` reports SVG documents as `text/html`, `text/xml` or
		 * `text/plain` depending on the libmagic build, which makes core drop the
		 * extension and refuse the upload. Only files that really do contain an
		 * `<svg>` root are corrected here.
		 *
		 * @since 1.0.0
		 * @param array        $data      File data with `ext`, `type` and `proper_filename`.
		 * @param string       $file      Full path to the file.
		 * @param string       $filename  The name of the file.
		 * @param string[]|null $mimes    Allowed mime types, if any.
		 * @param string|false $real_mime The actual mime type, when detected.
		 * @return array
		 */
		public function check_svg_filetype( $data, $file, $filename, $mimes = null, $real_mime = false ) {
			if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
				return $data;
			}

			if ( 'svg' !== strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
				return $data;
			}

			$allowed = is_array( $mimes ) ? $mimes : get_allowed_mime_types();
			if ( ! in_array( self::MIME_TYPE, (array) $allowed, true ) ) {
				return $data;
			}

			if ( ! $this->file_looks_like_svg( $file ) ) {
				return $data;
			}

			$data['ext']  = 'svg';
			$data['type'] = self::MIME_TYPE;

			return $data;
		}

		/**
		 * Validate and sanitise an uploaded SVG file.
		 *
		 * @since 1.0.0
		 * @param array $file Uploaded file data.
		 * @return array
		 */
		public function validate_svg( $file ) {
			// A PHP level upload error is already present, nothing to do.
			if ( ! empty( $file['error'] ) ) {
				return $file;
			}

			$name = isset( $file['name'] ) ? $file['name'] : '';
			$ext  = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );
			$type = isset( $file['type'] ) ? strtolower( $file['type'] ) : '';

			if ( 'svg' !== $ext && 'svgz' !== $ext && self::MIME_TYPE !== $type ) {
				return $file;
			}

			if ( 'svg' !== $ext ) {
				$file['error'] = __( 'Only .svg files can be uploaded. Compressed .svgz files are not supported.', 'svg-icon-block' );
				return $file;
			}

			if ( ! apply_filters( 'svgib_allow_svg_upload', true ) ) {
				$file['error'] = __( 'SVG uploads are disabled on this site.', 'svg-icon-block' );
				return $file;
			}

			if ( ! current_user_can( 'upload_files' ) ) {
				$file['error'] = __( 'You are not allowed to upload SVG files.', 'svg-icon-block' );
				return $file;
			}

			$tmp_name = isset( $file['tmp_name'] ) ? $file['tmp_name'] : '';
			if ( '' === $tmp_name || ! file_exists( $tmp_name ) ) {
				$file['error'] = __( 'Unable to locate the uploaded SVG file.', 'svg-icon-block' );
				return $file;
			}

			/**
			 * Filters the maximum allowed size, in bytes, of an uploaded SVG.
			 *
			 * @since 1.1.0
			 * @param int $max_size Maximum size in bytes. Use 0 to disable the check.
			 */
			$max_size = (int) apply_filters( 'svgib_max_svg_upload_size', self::MAX_FILE_SIZE );
			if ( $max_size > 0 && filesize( $tmp_name ) > $max_size ) {
				$file['error'] = sprintf(
					/* translators: %s: maximum file size, e.g. "2 MB". */
					__( 'The SVG file is too large. Maximum allowed size is %s.', 'svg-icon-block' ),
					size_format( $max_size )
				);
				return $file;
			}

			$content = $this->read_file( $tmp_name );
			if ( false === $content || '' === trim( $content ) ) {
				$file['error'] = __( 'Unable to read the uploaded SVG file.', 'svg-icon-block' );
				return $file;
			}

			$sanitized = $this->sanitize_svg( $content );
			if ( false === $sanitized ) {
				$file['error'] = __( 'The file could not be processed as a valid SVG image.', 'svg-icon-block' );
				return $file;
			}

			if ( ! $this->write_file( $tmp_name, $sanitized ) ) {
				$file['error'] = __( 'Unable to save the sanitized SVG file.', 'svg-icon-block' );
				return $file;
			}

			$file['type'] = self::MIME_TYPE;
			$file['size'] = strlen( $sanitized );

			return $file;
		}

		/**
		 * Sanitise SVG markup.
		 *
		 * Delegates to the shared sanitiser so uploads and icons saved through the
		 * REST API are held to exactly the same rules.
		 *
		 * @since 1.0.0
		 * @param string $content Raw SVG markup.
		 * @return string|false Sanitised markup, or false when the document is not a usable SVG.
		 */
		private function sanitize_svg( $content ) {
			return Svgib_SVG_Sanitizer::sanitize( $content );
		}

		/*
		 * -----------------------------------------------------------------
		 * Media library display
		 * -----------------------------------------------------------------
		 */

		/**
		 * Record dimensions for uploaded SVG files.
		 *
		 * Without width and height the media modal and `wp_get_attachment_image()`
		 * have nothing to work with.
		 *
		 * @since 1.1.0
		 * @param array $metadata      Attachment metadata.
		 * @param int   $attachment_id Attachment ID.
		 * @return array
		 */
		public function add_svg_metadata( $metadata, $attachment_id ) {
			if ( self::MIME_TYPE !== get_post_mime_type( $attachment_id ) ) {
				return $metadata;
			}

			$file = get_attached_file( $attachment_id );
			if ( ! $file ) {
				return $metadata;
			}

			$dimensions = $this->get_svg_dimensions( $file );
			$metadata   = is_array( $metadata ) ? $metadata : array();

			$metadata['width']  = $dimensions['width'];
			$metadata['height'] = $dimensions['height'];
			$metadata['file']   = _wp_relative_upload_path( $file );

			if ( empty( $metadata['sizes'] ) ) {
				$metadata['sizes'] = array();
			}

			return $metadata;
		}

		/**
		 * Give SVG attachments a usable preview in the media modal.
		 *
		 * @since 1.0.0
		 * @param array       $response   Attachment data prepared for JavaScript.
		 * @param WP_Post|null $attachment Attachment post object.
		 * @return array
		 */
		public function fix_svg_thumbnail_display( $response, $attachment = null ) {
			if ( empty( $response['mime'] ) || self::MIME_TYPE !== $response['mime'] || empty( $response['url'] ) ) {
				return $response;
			}

			$width  = ! empty( $response['width'] ) ? (int) $response['width'] : 0;
			$height = ! empty( $response['height'] ) ? (int) $response['height'] : 0;

			if ( ! $width || ! $height ) {
				$file = $attachment instanceof WP_Post ? get_attached_file( $attachment->ID ) : false;
				$dimensions = $file ? $this->get_svg_dimensions( $file ) : array(
					'width'  => 100,
					'height' => 100,
				);

				$width  = $dimensions['width'];
				$height = $dimensions['height'];
			}

			$response['width']       = $width;
			$response['height']      = $height;
			$response['orientation'] = $height > $width ? 'portrait' : 'landscape';
			$response['icon']        = $response['url'];

			$size = array(
				'url'         => $response['url'],
				'width'       => $width,
				'height'      => $height,
				'orientation' => $response['orientation'],
			);

			$response['sizes'] = array(
				'full'      => $size,
				'large'     => $size,
				'medium'    => $size,
				'thumbnail' => $size,
			);

			return $response;
		}

		/**
		 * Make SVG previews fill their thumbnail box in the media library.
		 *
		 * @since 1.1.0
		 * @return void
		 */
		public function print_media_library_styles() {
			echo '<style>.media-icon img[src$=".svg"],.attachment .thumbnail img[src$=".svg"],.attachment-preview img[src$=".svg"]{width:100%;height:100%;object-fit:contain;}</style>';
		}

		/**
		 * Read the intrinsic dimensions of an SVG file.
		 *
		 * @since 1.1.0
		 * @param string $file Absolute path to the SVG file.
		 * @return array{width:int,height:int}
		 */
		private function get_svg_dimensions( $file ) {
			$fallback = array(
				'width'  => 100,
				'height' => 100,
			);

			$content = $this->read_file( $file );
			if ( false === $content || '' === trim( $content ) ) {
				return $fallback;
			}

			$previous_state = libxml_use_internal_errors( true );

			$doc = new DOMDocument();
			$loaded = $doc->loadXML( $content, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );

			libxml_clear_errors();
			libxml_use_internal_errors( $previous_state );

			if ( ! $loaded || ! $doc->documentElement ) {
				return $fallback;
			}

			$svg    = $doc->documentElement;
			$width  = $this->to_pixels( $svg->getAttribute( 'width' ) );
			$height = $this->to_pixels( $svg->getAttribute( 'height' ) );

			if ( ! $width || ! $height ) {
				$view_box = preg_split( '/[\s,]+/', trim( $svg->getAttribute( 'viewBox' ) ) );

				if ( is_array( $view_box ) && 4 === count( $view_box ) ) {
					$width  = $width ? $width : (int) round( (float) $view_box[2] );
					$height = $height ? $height : (int) round( (float) $view_box[3] );
				}
			}

			if ( ! $width || ! $height ) {
				return $fallback;
			}

			return array(
				'width'  => $width,
				'height' => $height,
			);
		}

		/**
		 * Convert an SVG length to a pixel value.
		 *
		 * @since 1.1.0
		 * @param string $value Raw attribute value, e.g. "24px" or "1.5em".
		 * @return int Pixel value, or 0 when it cannot be resolved.
		 */
		private function to_pixels( $value ) {
			$value = trim( (string) $value );

			// Percentages and relative units have no intrinsic pixel size.
			if ( '' === $value || ! preg_match( '/^([0-9.]+)\s*(px)?$/i', $value, $matches ) ) {
				return 0;
			}

			return (int) round( (float) $matches[1] );
		}

		/*
		 * -----------------------------------------------------------------
		 * Filesystem helpers
		 * -----------------------------------------------------------------
		 */

		/**
		 * Whether a file starts with SVG markup.
		 *
		 * @since 1.1.0
		 * @param string $file Absolute path to the file.
		 * @return bool
		 */
		private function file_looks_like_svg( $file ) {
			if ( ! is_string( $file ) || ! file_exists( $file ) ) {
				return false;
			}

			$handle = fopen( $file, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			if ( ! $handle ) {
				return false;
			}

			$chunk = fread( $handle, 4096 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

			if ( false === $chunk ) {
				return false;
			}

			if ( false !== stripos( $chunk, '<?php' ) || false !== stripos( $chunk, '<?=' ) ) {
				return false;
			}

			return (bool) preg_match( '/<svg[\s\/>]/i', $chunk );
		}

		/**
		 * Lazily resolve the WP_Filesystem instance.
		 *
		 * @since 1.1.0
		 * @return WP_Filesystem_Base|false
		 */
		private function filesystem() {
			if ( null !== $this->wp_filesystem ) {
				return $this->wp_filesystem;
			}

			global $wp_filesystem;

			if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
				if ( ! function_exists( 'WP_Filesystem' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}

				WP_Filesystem();
			}

			$this->wp_filesystem = ( $wp_filesystem instanceof WP_Filesystem_Base ) ? $wp_filesystem : false;

			return $this->wp_filesystem;
		}

		/**
		 * Read a file, falling back to native calls when WP_Filesystem is unusable.
		 *
		 * Uploads live in the system temp directory, which a remote filesystem
		 * transport such as FTP cannot reach.
		 *
		 * @since 1.1.0
		 * @param string $path Absolute path.
		 * @return string|false
		 */
		private function read_file( $path ) {
			$filesystem = $this->filesystem();

			if ( $filesystem ) {
				$content = $filesystem->get_contents( $path );
				if ( false !== $content ) {
					return $content;
				}
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			return file_get_contents( $path );
		}

		/**
		 * Write a file, falling back to native calls when WP_Filesystem is unusable.
		 *
		 * @since 1.1.0
		 * @param string $path    Absolute path.
		 * @param string $content Content to write.
		 * @return bool
		 */
		private function write_file( $path, $content ) {
			$filesystem = $this->filesystem();

			if ( $filesystem && $filesystem->put_contents( $path, $content ) ) {
				return true;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return false !== file_put_contents( $path, $content );
		}
	}

	new Svgib_SecureSVGUploader();
}
