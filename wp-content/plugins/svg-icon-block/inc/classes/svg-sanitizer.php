<?php
/**
 * Shared SVG Sanitiser
 *
 * Allow-list based sanitisation for SVG markup. Elements that are not known
 * safe SVG are dropped, event handlers are stripped, and every URL reference is
 * validated against a safe scheme list.
 *
 * Used by both the media uploader and the "My Icons" REST endpoints so every
 * route into the site is held to the same rules.
 *
 * @since 1.1.0
 * @package SvgibBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Svgib_SVG_Sanitizer' ) ) {

	/**
	 * Svgib SVG Sanitiser Class
	 *
	 * @since 1.1.0
	 * @package SvgibBlock
	 */
	class Svgib_SVG_Sanitizer {

		/**
		 * Namespaces that are allowed to appear in a sanitised document.
		 */
		const SVG_NS   = 'http://www.w3.org/2000/svg';
		const XLINK_NS = 'http://www.w3.org/1999/xlink';
		const XML_NS   = 'http://www.w3.org/XML/1998/namespace';
		const XMLNS_NS = 'http://www.w3.org/2000/xmlns/';

		/**
		 * Sanitise SVG markup.
		 *
		 * @since 1.0.0
		 * @param string $content Raw SVG markup.
		 * @return string|false Sanitised markup, or false when the document is not a usable SVG.
		 */
		public static function sanitize( $content ) {
			// Strip a UTF-8 byte order mark, it prevents the document from parsing.
			$content = preg_replace( '/^\xEF\xBB\xBF/', '', $content );

			// Entity declarations enable XXE and billion laughs attacks.
			if ( preg_match( '/<!\s*ENTITY/i', $content ) ) {
				return false;
			}

			// A file shipping PHP is hostile, there is nothing worth salvaging.
			if ( false !== stripos( $content, '<?php' ) || false !== stripos( $content, '<?=' ) ) {
				return false;
			}

			if ( ! preg_match( '/<svg[\s\/>]/i', $content ) ) {
				return false;
			}

			$previous_state = libxml_use_internal_errors( true );

			$doc                        = new DOMDocument();
			$doc->strictErrorChecking   = false;
			$doc->substituteEntities    = false;
			$doc->resolveExternals      = false;
			$doc->formatOutput          = false;

			$loaded = $doc->loadXML( $content, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );

			libxml_clear_errors();
			libxml_use_internal_errors( $previous_state );

			if ( ! $loaded || ! $doc->documentElement ) {
				return false;
			}

			if ( 'svg' !== strtolower( $doc->documentElement->localName ) ) {
				return false;
			}

			// Drop the doctype together with any top level comment or processing
			// instruction, such as an xml-stylesheet pointing at a remote XSL file.
			foreach ( iterator_to_array( $doc->childNodes, false ) as $node ) {
				if ( XML_DOCUMENT_TYPE_NODE === $node->nodeType || XML_PI_NODE === $node->nodeType || XML_COMMENT_NODE === $node->nodeType ) {
					$doc->removeChild( $node );
				}
			}

			self::sanitize_element( $doc->documentElement );

			// A root element that survived sanitisation must still be an <svg>.
			if ( ! $doc->documentElement || 'svg' !== strtolower( $doc->documentElement->localName ) ) {
				return false;
			}

			// Media library previews render the file as an image, which requires
			// the namespace to be declared.
			if ( ! $doc->documentElement->hasAttribute( 'xmlns' ) ) {
				$doc->documentElement->setAttribute( 'xmlns', self::SVG_NS );
			}

			$output = $doc->saveXML();

			return ( false === $output ) ? false : $output;
		}

		/**
		 * Recursively sanitise an element and its children.
		 *
		 * @since 1.0.0
		 * @param DOMElement $element Element to sanitise.
		 * @return void
		 */
		private static function sanitize_element( DOMElement $element ) {
			$parent    = $element->parentNode;
			$namespace = $element->namespaceURI;

			// Foreign markup, e.g. embedded XHTML or RDF, is never rendered as SVG.
			if ( null !== $namespace && ! in_array( $namespace, array( self::SVG_NS, self::XLINK_NS ), true ) ) {
				if ( $parent ) {
					$parent->removeChild( $element );
				}
				return;
			}

			$tag = strtolower( $element->localName );

			if ( ! in_array( $tag, self::allowed_elements(), true ) ) {
				if ( $parent ) {
					$parent->removeChild( $element );
				}
				return;
			}

			foreach ( iterator_to_array( $element->attributes, false ) as $attribute ) {
				self::sanitize_attribute( $element, $attribute );
			}

			// An animation element must not be able to retarget a URL bearing
			// attribute, e.g. <set attributeName="href" to="javascript:..." />.
			if ( in_array( $tag, array( 'animate', 'animatemotion', 'animatetransform', 'set' ), true ) ) {
				if ( self::targets_unsafe_attribute( $element ) ) {
					if ( $parent ) {
						$parent->removeChild( $element );
					}
					return;
				}
			}

			// Style rules can reach outside the document via @import or url().
			if ( 'style' === $tag && ! self::css_is_safe( $element->textContent ) ) {
				if ( $parent ) {
					$parent->removeChild( $element );
				}
				return;
			}

			foreach ( iterator_to_array( $element->childNodes, false ) as $child ) {
				if ( $child instanceof DOMElement ) {
					self::sanitize_element( $child );
				} elseif ( XML_COMMENT_NODE === $child->nodeType || XML_PI_NODE === $child->nodeType ) {
					$element->removeChild( $child );
				}
			}
		}

		/**
		 * Sanitise a single attribute, removing it when it is unsafe.
		 *
		 * @since 1.0.0
		 * @param DOMElement $element   Owner element.
		 * @param DOMAttr    $attribute Attribute to inspect.
		 * @return void
		 */
		private static function sanitize_attribute( DOMElement $element, DOMAttr $attribute ) {
			$name      = strtolower( $attribute->nodeName );
			$local     = strtolower( $attribute->localName );
			$namespace = $attribute->namespaceURI;
			$value     = $attribute->value;

			// Event handlers: onload, onclick, onerror and friends.
			if ( 0 === strpos( $local, 'on' ) || 0 === strpos( $name, 'on' ) ) {
				$element->removeAttributeNode( $attribute );
				return;
			}

			// Attributes belonging to a namespace we do not render.
			if ( null !== $namespace && ! in_array( $namespace, array( self::SVG_NS, self::XLINK_NS, self::XML_NS, self::XMLNS_NS ), true ) ) {
				$element->removeAttributeNode( $attribute );
				return;
			}

			// Attributes that are dangerous regardless of their value.
			if ( in_array( $local, array( 'formaction', 'action', 'srcdoc', 'data', 'ping' ), true ) ) {
				$element->removeAttributeNode( $attribute );
				return;
			}

			$normalized = self::normalize_value( $value );

			// Direct URL references.
			if ( in_array( $local, array( 'href', 'src' ), true ) && ! self::is_safe_url( $value ) ) {
				$element->removeAttributeNode( $attribute );
				return;
			}

			// Indirect references, e.g. fill="url(#gradient)" or a `style` value.
			if ( preg_match_all( '/url\s*\(\s*[\'"]?([^\'")]*)/i', $normalized, $matches ) ) {
				foreach ( $matches[1] as $url ) {
					if ( ! self::is_safe_url( $url ) ) {
						$element->removeAttributeNode( $attribute );
						return;
					}
				}
			}

			// Catch-all for executable schemes hidden anywhere in the value.
			if ( preg_match( '/(?:javascript|vbscript|livescript|mocha|jar|view-source)\s*:/i', $normalized ) ) {
				$element->removeAttributeNode( $attribute );
				return;
			}

			if ( 'style' === $local && ! self::css_is_safe( $value ) ) {
				$element->removeAttributeNode( $attribute );
			}
		}

		/**
		 * Whether an animation element retargets a URL bearing attribute.
		 *
		 * @since 1.1.0
		 * @param DOMElement $element Animation element.
		 * @return bool
		 */
		private static function targets_unsafe_attribute( DOMElement $element ) {
			$unsafe = array( 'href', 'xlink:href', 'src', 'style', 'attributename', 'begin', 'from', 'to', 'values' );

			foreach ( iterator_to_array( $element->attributes, false ) as $attribute ) {
				if ( 'attributename' !== strtolower( $attribute->localName ) ) {
					continue;
				}

				return in_array( strtolower( trim( $attribute->value ) ), $unsafe, true );
			}

			return false;
		}

		/**
		 * Whether a URL uses a scheme that cannot execute script.
		 *
		 * @since 1.1.0
		 * @param string $url URL to check.
		 * @return bool
		 */
		private static function is_safe_url( $url ) {
			$url = strtolower( self::normalize_value( $url ) );

			if ( '' === $url ) {
				return true;
			}

			// Same document reference, e.g. url(#gradient).
			if ( 0 === strpos( $url, '#' ) ) {
				return true;
			}

			// Raster data URIs only. `data:image/svg+xml` and `data:text/html`
			// would both re-introduce arbitrary markup.
			if ( preg_match( '#^data:image/(?:png|jpe?g|gif|webp);base64,[a-z0-9+/=]+$#', $url ) ) {
				return true;
			}

			if ( preg_match( '#^(?:https?://|mailto:)#', $url ) ) {
				return true;
			}

			// Any other explicit scheme is rejected.
			if ( preg_match( '#^[a-z][a-z0-9+.\-]*:#', $url ) ) {
				return false;
			}

			// Relative reference.
			return true;
		}

		/**
		 * Whether a CSS fragment is free of script and external references.
		 *
		 * @since 1.1.0
		 * @param string $css CSS to check.
		 * @return bool
		 */
		private static function css_is_safe( $css ) {
			$normalized = strtolower( self::normalize_value( $css ) );

			if ( '' === $normalized ) {
				return true;
			}

			if ( preg_match( '/@import|expression\(|behaviou?r:|-moz-binding|javascript:|vbscript:|livescript:/', $normalized ) ) {
				return false;
			}

			if ( preg_match_all( '/url\(([^)]*)/', $normalized, $matches ) ) {
				foreach ( $matches[1] as $url ) {
					if ( ! self::is_safe_url( trim( $url, "'\"" ) ) ) {
						return false;
					}
				}
			}

			return true;
		}

		/**
		 * Decode entities and strip control characters used to obfuscate values.
		 *
		 * @since 1.1.0
		 * @param string $value Attribute or CSS value.
		 * @return string
		 */
		private static function normalize_value( $value ) {
			$value = (string) $value;

			// Decode twice: `&amp;#106;avascript:` decodes to `javascript:`.
			for ( $i = 0; $i < 2; $i++ ) {
				$decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				if ( $decoded === $value ) {
					break;
				}
				$value = $decoded;
			}

			return preg_replace( '/[\x00-\x20\x7F]/', '', $value );
		}

		/**
		 * Elements that are kept during sanitisation.
		 *
		 * @since 1.1.0
		 * @return string[] Lowercase local names.
		 */
		private static function allowed_elements() {
			static $elements = null;

			if ( null !== $elements ) {
				return $elements;
			}

			$elements = array(
				'a', 'animate', 'animatemotion', 'animatetransform', 'circle', 'clippath',
				'defs', 'desc', 'ellipse', 'filter', 'g', 'image', 'line', 'lineargradient',
				'marker', 'mask', 'metadata', 'mpath', 'path', 'pattern', 'polygon',
				'polyline', 'radialgradient', 'rect', 'set', 'stop', 'style', 'svg',
				'switch', 'symbol', 'text', 'textpath', 'title', 'tspan', 'use', 'view',
				// Filter primitives.
				'feblend', 'fecolormatrix', 'fecomponenttransfer', 'fecomposite',
				'feconvolvematrix', 'fediffuselighting', 'fedisplacementmap',
				'fedistantlight', 'fedropshadow', 'feflood', 'fefunca', 'fefuncb',
				'fefuncg', 'fefuncr', 'fegaussianblur', 'feimage', 'femerge',
				'femergenode', 'femorphology', 'feoffset', 'fepointlight',
				'fespecularlighting', 'fespotlight', 'fetile', 'feturbulence',
			);

			/**
			 * Filters the SVG elements that survive sanitisation.
			 *
			 * @since 1.1.0
			 * @param string[] $elements Lowercase element local names.
			 */
			$elements = array_map( 'strtolower', (array) apply_filters( 'svgib_allowed_svg_elements', $elements ) );

			return $elements;
		}
	}
}
