<?php
/**
 * JetTricks blocks views extensions render.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Extensions_Render' ) ) {

	/**
	 * Define Jet_Tricks_Blocks_Extensions_Render class.
	 */
	class Jet_Tricks_Blocks_Extensions_Render {
		private $parallax_enabled       = false;
		private $tooltip_enabled        = false;
		private $satellite_enabled      = false;
		private $scroll_reveal_enabled  = false;

		/**
		 * Inject JetTricks classes, data attribute and optional extra inline style
		 * directly into the block's root HTML element using WP_HTML_Tag_Processor.
		 * Also prepends satellite/tooltip HTML right after the opening root tag.		
		 */
		private function inject_attributes_into_block( $block_content, $classes, $settings, $satellite_html, $tooltip_html, $extra_inline_style ) {
			$processor = new WP_HTML_Tag_Processor( $block_content );
			if ( ! $processor->next_tag() ) {
				return null;
			}

			foreach ( $classes as $class ) {
				$processor->add_class( $class );
			}

			$processor->set_attribute( 'data-jet-tricks-settings', wp_json_encode( $settings ) );

			if ( '' !== $extra_inline_style ) {
				$existing = (string) $processor->get_attribute( 'style' );
				if ( '' !== $existing ) {
					$existing = rtrim( $existing, ';' ) . ';';
				}
				$processor->set_attribute( 'style', $existing . $extra_inline_style );
			}

			$modified = $processor->get_updated_html();

			return $this->replace_extension_children( $modified, $satellite_html, $tooltip_html );
		}

		/**
		 * Remove current extension children from the root element and prepend fresh markup.
		 *
		 * @param string $markup         Root block markup.
		 * @param string $satellite_html Satellite HTML.
		 * @param string $tooltip_html   Tooltip HTML.
		 * @return string
		 */
		private function replace_extension_children( $markup, $satellite_html, $tooltip_html ) {
			$prepend_html = $satellite_html . $tooltip_html;

			if ( '' === $prepend_html
				&& false === strpos( $markup, 'jet-tricks-satellite' )
				&& false === strpos( $markup, 'jet-tooltip-widget__content' )
			) {
				return $markup;
			}

			$previous = libxml_use_internal_errors( true );
			$document = new DOMDocument( '1.0', 'UTF-8' );
			$loaded   = $document->loadHTML(
				'<?xml encoding="utf-8" ?><div id="jet-tricks-root">' . $markup . '</div>',
				LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
			);
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );

			if ( ! $loaded ) {
				return $markup;
			}

			$container = $document->getElementById( 'jet-tricks-root' );
			$root      = $container ? $this->get_first_element_child( $container ) : null;

			if ( ! $container || ! $root ) {
				return $markup;
			}

			$to_remove = $this->get_extension_nodes_to_remove( $root );

			foreach ( $container->childNodes as $child ) {
				if ( $child === $root || XML_ELEMENT_NODE !== $child->nodeType ) {
					continue;
				}

				if ( $this->dom_element_has_class( $child, 'jet-tricks-satellite' ) || $this->dom_element_has_class( $child, 'jet-tooltip-widget__content' ) ) {
					$to_remove[] = $child;
				}
			}

			foreach ( $to_remove as $child ) {
				$root->removeChild( $child );
			}

			if ( '' !== $prepend_html ) {
				$fragment_document = new DOMDocument( '1.0', 'UTF-8' );
				$previous          = libxml_use_internal_errors( true );
				$loaded_fragment   = $fragment_document->loadHTML(
					'<?xml encoding="utf-8" ?><div id="jet-tricks-fragment">' . $prepend_html . '</div>',
					LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
				);
				libxml_clear_errors();
				libxml_use_internal_errors( $previous );

				if ( ! $loaded_fragment ) {
					return $markup;
				}

				$fragment_root = $fragment_document->getElementById( 'jet-tricks-fragment' );

				if ( ! $fragment_root ) {
					return $markup;
				}

				$nodes = array();

				foreach ( $fragment_root->childNodes as $child ) {
					$nodes[] = $child;
				}

				foreach ( array_reverse( $nodes ) as $child ) {
					$root->insertBefore( $document->importNode( $child, true ), $root->firstChild );
				}
			}

			$result = '';

			foreach ( $container->childNodes as $child ) {
				$result .= $document->saveHTML( $child );
			}

			return $result;
		}

		/**
		 * Return extension nodes that should not be persisted in block content.
		 *
		 * @param DOMElement $root Root block element.
		 * @return DOMNode[]
		 */
		private function get_extension_nodes_to_remove( $root ) {
			$to_remove = array();

			foreach ( $root->childNodes as $child ) {
				if ( XML_ELEMENT_NODE !== $child->nodeType ) {
					continue;
				}

				if ( $this->dom_element_has_class( $child, 'jet-tricks-satellite' ) || $this->dom_element_has_class( $child, 'jet-tooltip-widget__content' ) ) {
					$to_remove[] = $child;
				}
			}

			return $to_remove;
		}

		/**
		 * Return the first element child for a DOM node.
		 *
		 * @param DOMNode $node DOM node.
		 * @return DOMElement|null
		 */
		private function get_first_element_child( $node ) {
			foreach ( $node->childNodes as $child ) {
				if ( XML_ELEMENT_NODE === $child->nodeType ) {
					return $child;
				}
			}

			return null;
		}

		/**
		 * Check whether a DOM element contains a CSS class.
		 *
		 * @param DOMElement $element    DOM element.
		 * @param string     $class_name CSS class name.
		 * @return bool
		 */
		private function dom_element_has_class( $element, $class_name ) {
			if ( ! $element->hasAttribute( 'class' ) ) {
				return false;
			}

			$class_attr = trim( (string) $element->getAttribute( 'class' ) );

			if ( '' === $class_attr ) {
				return false;
			}

			$classes = preg_split( '/\s+/', $class_attr );

			return in_array( $class_name, $classes, true );
		}

		/**
		 * Extract the root tag name from block HTML.
		 *
		 * @param string $block_content Block HTML.
		 * @return string
		 */
		private function get_root_tag_name( $block_content ) {
			if ( preg_match( '/<\s*([a-z0-9:-]+)/i', $block_content, $matches ) ) {
				return strtolower( $matches[1] );
			}

			return '';
		}

		/**
		 * Whether the root tag only accepts phrasing content.
		 *
		 * @param string $tag_name Root tag name.
		 * @return bool
		 */
		private function root_requires_phrasing_children( $tag_name ) {
			return in_array( $tag_name, array( 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true );
		}

		/**
		 * Whether the block's saved root only accepts phrasing content.
		 *
		 * @param string $block_name Block name.
		 * @return bool
		 */
		private function block_requires_phrasing_children( $block_name ) {
			return in_array( $block_name, array( 'core/paragraph', 'core/heading' ), true );
		}

		private $parallax_blocks        = [];
		private $tooltip_blocks         = [];
		private $satellite_blocks       = [];
		private $scroll_reveal_blocks   = [];
		private $all_blocks             = [];

		private $page_flags = null;

		/**
		 * Initialize render filter and conditional assets.
		 *
		 * @return void
		 */
		public function init() {
			if ( ! function_exists( 'jet_tricks_settings' ) ) {
				return;
			}

			$avaliable = jet_tricks_settings()->get_avaliable_extensions();

			$this->parallax_enabled      = filter_var( $avaliable['widget_parallax'] ?? false, FILTER_VALIDATE_BOOLEAN );
			$this->tooltip_enabled       = filter_var( $avaliable['widget_tooltip'] ?? false, FILTER_VALIDATE_BOOLEAN );
			$this->satellite_enabled     = filter_var( $avaliable['widget_satellite'] ?? false, FILTER_VALIDATE_BOOLEAN );
			$this->scroll_reveal_enabled = filter_var( $avaliable['widget_scroll_reveal'] ?? false, FILTER_VALIDATE_BOOLEAN );

			if ( ! $this->parallax_enabled && ! $this->tooltip_enabled && ! $this->satellite_enabled && ! $this->scroll_reveal_enabled ) {
				return;
			}

			if ( $this->parallax_enabled && class_exists( 'Jet_Tricks_Blocks_Parallax_Extension' ) ) {
				$this->parallax_blocks = Jet_Tricks_Blocks_Parallax_Extension::get_supported_blocks();
			}
			if ( $this->tooltip_enabled && class_exists( 'Jet_Tricks_Blocks_Tooltip_Extension' ) ) {
				$this->tooltip_blocks = Jet_Tricks_Blocks_Tooltip_Extension::get_supported_blocks();
			}
			if ( $this->satellite_enabled && class_exists( 'Jet_Tricks_Blocks_Satellite_Extension' ) ) {
				$this->satellite_blocks = Jet_Tricks_Blocks_Satellite_Extension::get_supported_blocks();
			}
			if ( $this->scroll_reveal_enabled && class_exists( 'Jet_Tricks_Blocks_Scroll_Reveal_Extension' ) ) {
				$this->scroll_reveal_blocks = Jet_Tricks_Blocks_Scroll_Reveal_Extension::get_supported_blocks();
			}

			$this->all_blocks = array_unique( array_merge(
				$this->parallax_blocks,
				$this->tooltip_blocks,
				$this->satellite_blocks,
				$this->scroll_reveal_blocks
			) );

			add_filter( 'render_block', [ $this, 'render_block' ], 10, 2 );
			add_action( 'enqueue_block_assets', [ $this, 'maybe_enqueue_frontend_assets' ], 10 );
		}

		/**
		 * Sanitize mask color for blocks (hex fallback).
		 *
		 * @param mixed $value Attribute value.
		 * @return string
		 */
		private function sanitize_scroll_reveal_mask_color( $value ) {
			if ( ! is_string( $value ) ) {
				return '#ffffff';
			}
			$value = trim( $value );
			if ( '' === $value ) {
				return '#ffffff';
			}
			$hex = sanitize_hex_color( $value );
			return $hex ? $hex : '#ffffff';
		}

		/**
		 * Filter block output — wrapper, data-jet-tricks-settings, satellite and tooltip inner HTML.
		 *
		 * @param string $block_content Block HTML.
		 * @param array  $block         Block data.
		 * @return string
		 */
		public function render_block( $block_content, $block ) {
			if ( empty( $block['blockName'] ) || ! in_array( $block['blockName'], $this->all_blocks, true ) ) {
				return $block_content;
			}

			$attrs              = $block['attrs'] ?? [];
			$crocoblock_styles  = $attrs['crocoblock_styles'] ?? [];
			$widget_settings    = [];
			$classes            = [];
			$satellite_html     = '';
			$tooltip_html       = '';
			$extra_inline_style = '';
			$needs_child_cleanup = false;

			$wrapper_class = trim( $crocoblock_styles['_uniqueClassName'] ?? '' );

			if ( '' !== $wrapper_class ) {
				$classes[] = $wrapper_class;
			}

			if ( $this->parallax_enabled
				&& in_array( $block['blockName'], $this->parallax_blocks, true )
				&& filter_var( $attrs['jetTricksParallax'] ?? false, FILTER_VALIDATE_BOOLEAN )
			) {
				$widget_settings['parallax'] = 'true';
				$widget_settings['invert']   = filter_var( $attrs['jetTricksParallaxInvert'] ?? false, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';
				$widget_settings['speed']    = [
					'size' => isset( $attrs['jetTricksParallaxSpeed'] ) ? (int) $attrs['jetTricksParallaxSpeed'] : 50,
					'unit' => '%',
				];
				if ( isset( $attrs['jetTricksParallaxOn'] ) && is_array( $attrs['jetTricksParallaxOn'] ) ) {
					$allowed = array( 'desktop', 'tablet', 'mobile' );
					$widget_settings['stickyOn'] = array_values(
						array_intersect( $allowed, array_map( 'sanitize_text_field', wp_unslash( $attrs['jetTricksParallaxOn'] ) ) )
					);
				} else {
					$widget_settings['stickyOn'] = array();
				}
				$classes[] = 'jet-parallax-widget';
			}

			if ( $this->satellite_enabled
				&& in_array( $block['blockName'], $this->satellite_blocks, true )
			) {
				$needs_child_cleanup = true;

				if ( filter_var( $attrs['jetTricksSatellite'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
					$helper        = new Jet_Tricks_Blocks_Satellite_Extension();
					$root_tag_name = $this->get_root_tag_name( $block_content );
					$wrapper_tag   = ( $this->block_requires_phrasing_children( $block['blockName'] ) || $this->root_requires_phrasing_children( $root_tag_name ) ) ? 'span' : 'div';
					$satellite_html = $helper->build_satellite_inner_html( $attrs, $wrapper_tag );

					if ( $satellite_html !== '' ) {
						$widget_settings['satellite']         = 'true';
						$widget_settings['satelliteType']     = ! empty( $attrs['jetTricksSatelliteType'] ) ? $attrs['jetTricksSatelliteType'] : 'text';
						$widget_settings['satellitePosition'] = ! empty( $attrs['jetTricksSatellitePosition'] ) ? $attrs['jetTricksSatellitePosition'] : 'top-center';
						$widget_settings['satelliteOffsetX']  = isset( $attrs['jetTricksSatelliteOffsetX'] ) ? (int) $attrs['jetTricksSatelliteOffsetX'] : 0;
						$widget_settings['satelliteOffsetY']  = isset( $attrs['jetTricksSatelliteOffsetY'] ) ? (int) $attrs['jetTricksSatelliteOffsetY'] : 0;
						$widget_settings['satelliteRotate']   = isset( $attrs['jetTricksSatelliteRotate'] ) ? (float) $attrs['jetTricksSatelliteRotate'] : 0;
						$widget_settings['satelliteZIndex']   = isset( $attrs['jetTricksSatelliteZIndex'] ) && '' !== $attrs['jetTricksSatelliteZIndex'] ? (int) $attrs['jetTricksSatelliteZIndex'] : 2;
						$classes[] = 'jet-satellite-widget';
						$classes[] = 'jet-tricks-block-satellite';
					}
				}
			}

			if ( $this->tooltip_enabled
				&& in_array( $block['blockName'], $this->tooltip_blocks, true )
			) {
				$needs_child_cleanup = true;

				if ( filter_var( $attrs['jetTricksTooltip'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
					$content = trim( $attrs['jetTricksTooltipContent'] ?? '' );

					if ( $content !== '' ) {
						$root_tag_name = $this->get_root_tag_name( $block_content );
						$tooltip_tag   = ( $this->block_requires_phrasing_children( $block['blockName'] ) || $this->root_requires_phrasing_children( $root_tag_name ) ) ? 'span' : 'div';
						$append_to = ! empty( $attrs['jetTricksTooltipAppendTo'] ) ? $attrs['jetTricksTooltipAppendTo'] : 'widget';
						$tooltip_wrapper_class = ( 'body' === $append_to ) ? $wrapper_class : '';

						$widget_settings['tooltip']            = 'true';
						$widget_settings['tooltipDescription'] = wp_kses_post( $content );
						$widget_settings['tooltipPlacement']   = ! empty( $attrs['jetTricksTooltipPlacement'] ) ? $attrs['jetTricksTooltipPlacement'] : 'top';
						$widget_settings['tooltipArrow']       = isset( $attrs['jetTricksTooltipArrow'] ) ? filter_var( $attrs['jetTricksTooltipArrow'], FILTER_VALIDATE_BOOLEAN ) : true;
						$widget_settings['xOffset']            = isset( $attrs['jetTricksTooltipOffset']['x'] ) ? (int) $attrs['jetTricksTooltipOffset']['x'] : 0;
						$widget_settings['yOffset']            = isset( $attrs['jetTricksTooltipOffset']['y'] ) ? (int) $attrs['jetTricksTooltipOffset']['y'] : 0;
						$widget_settings['tooltipAnimation']   = ! empty( $attrs['jetTricksTooltipAnimation'] ) ? $attrs['jetTricksTooltipAnimation'] : 'fade';
						$widget_settings['tooltipTrigger']     = ! empty( $attrs['jetTricksTooltipTrigger'] ) ? $attrs['jetTricksTooltipTrigger'] : 'mouseenter';
						$widget_settings['zIndex']             = ( isset( $attrs['jetTricksTooltipZIndex'] ) && $attrs['jetTricksTooltipZIndex'] !== '' ) ? (int) $attrs['jetTricksTooltipZIndex'] : 999;
						$widget_settings['appendTo']           = $append_to;
						$custom_sel = ltrim( trim( $attrs['jetTricksTooltipCustomSelector'] ?? '' ), '.' );
						$widget_settings['customSelector']     = $custom_sel;
						$widget_settings['delay']              = [ 'size' => isset( $attrs['jetTricksTooltipDelay'] ) ? (int) $attrs['jetTricksTooltipDelay'] : 0 ];
						$widget_settings['followCursor']       = ! empty( $attrs['jetTricksTooltipFollowCursor'] ) ? $attrs['jetTricksTooltipFollowCursor'] : 'false';
						$widget_settings['wrapperClass']       = $tooltip_wrapper_class;
						if ( isset( $attrs['jetTricksTooltipDevices'] ) && is_array( $attrs['jetTricksTooltipDevices'] ) ) {
							$widget_settings['tooltipDevices'] = array_values( array_unique( $attrs['jetTricksTooltipDevices'] ) );
						} else {
							$widget_settings['tooltipDevices'] = array();
						}

						$classes[] = 'jet-tooltip-widget';

						$tooltip_html = sprintf(
							'<%1$s class="jet-tooltip-widget__content">%2$s</%1$s>',
							tag_escape( $tooltip_tag ),
							do_shortcode( wp_kses_post( $content ) )
						);
					}
				}
			}

			if ( $this->scroll_reveal_enabled
				&& in_array( $block['blockName'], $this->scroll_reveal_blocks, true )
				&& filter_var( $attrs['jetTricksScrollReveal'] ?? false, FILTER_VALIDATE_BOOLEAN )
			) {
				$effect = isset( $attrs['jetTricksScrollRevealEffect'] ) ? sanitize_key( $attrs['jetTricksScrollRevealEffect'] ) : 'fade-up';
				$allowed_effects = array(
					'fade',
					'fade-up',
					'fade-down',
					'fade-left',
					'fade-right',
					'zoom-in',
					'zoom-out',
					'flip-up',
					'flip-down',
					'mask',
				);
				if ( ! in_array( $effect, $allowed_effects, true ) ) {
					$effect = 'fade-up';
				}

				$widget_settings['scrollReveal']       = 'true';
				$widget_settings['scrollRevealEffect'] = $effect;

				$dur = isset( $attrs['jetTricksScrollRevealDuration'] ) ? (float) $attrs['jetTricksScrollRevealDuration'] : 0.6;
				$dur = min( 3, max( 0.1, $dur ) );
				$widget_settings['scrollRevealDuration'] = array(
					'size' => $dur,
					'unit' => 's',
				);

				$del = isset( $attrs['jetTricksScrollRevealDelay'] ) ? (float) $attrs['jetTricksScrollRevealDelay'] : 0;
				$del = min( 15, max( 0, $del ) );
				$widget_settings['scrollRevealDelay'] = array(
					'size' => $del,
					'unit' => 's',
				);

				$widget_settings['scrollRevealOnce'] = filter_var( $attrs['jetTricksScrollRevealOnce'] ?? true, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';

				$rm = isset( $attrs['jetTricksScrollRevealRootMargin'] ) ? (int) $attrs['jetTricksScrollRevealRootMargin'] : 0;
				$rm = min( 200, max( -200, $rm ) );
				$widget_settings['scrollRevealRootMargin'] = $rm;

				if ( isset( $attrs['jetTricksScrollRevealOn'] ) && is_array( $attrs['jetTricksScrollRevealOn'] ) ) {
					$allowed_dev = array( 'desktop', 'tablet', 'mobile' );
					$widget_settings['scrollRevealOn'] = array_values(
						array_intersect( $allowed_dev, array_map( 'sanitize_text_field', wp_unslash( $attrs['jetTricksScrollRevealOn'] ) ) )
					);
				} else {
					$widget_settings['scrollRevealOn'] = array( 'desktop', 'tablet', 'mobile' );
				}

				$classes[] = 'jet-scroll-reveal-widget';
				$classes[] = 'jet-scroll-reveal--pending';
				$classes[] = 'jet-scroll-reveal--effect-' . $effect;

				if ( 'mask' === $effect ) {
					$mask_dir = isset( $attrs['jetTricksScrollRevealMaskDirection'] ) ? sanitize_key( $attrs['jetTricksScrollRevealMaskDirection'] ) : 'up';
					$allowed_mask = array( 'up', 'down', 'left', 'right', 'center-vertical', 'center-horizontal', 'center-all' );
					if ( ! in_array( $mask_dir, $allowed_mask, true ) ) {
						$mask_dir = 'up';
					}
					$widget_settings['scrollRevealMaskDirection'] = $mask_dir;
					$classes[] = 'jet-scroll-reveal--mask-dir-' . $mask_dir;

					$mask_color = $this->sanitize_scroll_reveal_mask_color( $attrs['jetTricksScrollRevealMaskColor'] ?? '#ffffff' );
					$mask_br    = isset( $attrs['jetTricksScrollRevealMaskBorderRadius'] ) ? (float) $attrs['jetTricksScrollRevealMaskBorderRadius'] : 0;
					$mask_unit  = isset( $attrs['jetTricksScrollRevealMaskBorderRadiusUnit'] ) ? sanitize_text_field( $attrs['jetTricksScrollRevealMaskBorderRadiusUnit'] ) : 'px';
					if ( ! in_array( $mask_unit, array( 'px', '%', 'em', 'rem' ), true ) ) {
						$mask_unit = 'px';
					}
					$max_r = ( '%' === $mask_unit ) ? 100 : 500;
					$mask_br = min( $max_r, max( 0, $mask_br ) );

				$style_parts = array( '--jet-sr-mask-color:' . $mask_color );
				if ( $mask_br > 0 ) {
					$radius_css = ( 'px' === $mask_unit )
						? (string) (int) round( $mask_br )
						: (string) round( $mask_br, 4 );
					$style_parts[] = '--jet-sr-mask-radius:' . $radius_css . $mask_unit;
				}
				$extra_inline_style = implode( ';', $style_parts );
				}
			}

			if ( empty( $widget_settings ) ) {
				if ( $needs_child_cleanup ) {
					return $this->replace_extension_children( $block_content, $satellite_html, $tooltip_html );
				}

				return $block_content;
			}

			return $this->inject_attributes_into_block(
				$block_content,
				$classes,
				$widget_settings,
				$satellite_html,
				$tooltip_html,
				$extra_inline_style
			) ?? $block_content;
		}

		/**
		 * Enqueue frontend assets when the post uses parallax, tooltip, satellite or scroll reveal on blocks.
		 *
		 * @return void
		 */
		public function maybe_enqueue_frontend_assets() {
			if ( is_admin() ) {
				return;
			}

			$flags = $this->get_page_flags();

			if ( ! $flags['any'] ) {
				return;
			}

			wp_enqueue_style(
				'jet-tricks-frontend',
				jet_tricks()->plugin_url( 'assets/css/jet-tricks-frontend.css' ),
				[],
				jet_tricks()->get_version()
			);

			if ( $flags['tooltip'] ) {
				Jet_Tricks_Assets::ensure_tippy_registered();
				wp_enqueue_script( 'jet-tricks-tippy-bundle' );
			}

			Jet_Tricks_Assets::ensure_jet_tricks_frontend_registered();
			wp_enqueue_script( 'jet-tricks-frontend' );
		}

		/**
		 * Scan post content for enabled block extensions.
		 *
		 * @return array
		 */
		private function get_page_flags() {
			if ( null !== $this->page_flags ) {
				return $this->page_flags;
			}

			$this->page_flags = [
				'any'            => false,
				'parallax'       => false,
				'tooltip'        => false,
				'satellite'      => false,
				'scroll_reveal'  => false,
			];

			$post = get_post();
			if ( ! $post || empty( $post->post_content ) ) {
				return $this->page_flags;
			}

			$blocks = parse_blocks( $post->post_content );
			$this->scan_blocks( $blocks );

			$this->page_flags['any'] = $this->page_flags['parallax'] || $this->page_flags['tooltip'] || $this->page_flags['satellite'] || $this->page_flags['scroll_reveal'];

			return $this->page_flags;
		}

		/**
		 * Recursively scan parsed blocks for extension flags.
		 *
		 * @param array[] $blocks Parsed blocks.
		 * @return void
		 */
		private function scan_blocks( $blocks ) {
			foreach ( $blocks as $block ) {
				$name = $block['blockName'] ?? '';

				if ( $name && in_array( $name, $this->all_blocks, true ) ) {
					$attrs = $block['attrs'] ?? [];

					if ( ! $this->page_flags['parallax'] && $this->parallax_enabled
						&& in_array( $name, $this->parallax_blocks, true )
						&& filter_var( $attrs['jetTricksParallax'] ?? false, FILTER_VALIDATE_BOOLEAN )
					) {
						$this->page_flags['parallax'] = true;
					}

					if ( ! $this->page_flags['tooltip'] && $this->tooltip_enabled
						&& in_array( $name, $this->tooltip_blocks, true )
						&& filter_var( $attrs['jetTricksTooltip'] ?? false, FILTER_VALIDATE_BOOLEAN )
						&& ! empty( trim( $attrs['jetTricksTooltipContent'] ?? '' ) )
					) {
						$this->page_flags['tooltip'] = true;
					}

					if ( ! $this->page_flags['satellite'] && $this->satellite_enabled
						&& in_array( $name, $this->satellite_blocks, true )
						&& filter_var( $attrs['jetTricksSatellite'] ?? false, FILTER_VALIDATE_BOOLEAN )
					) {
						$this->page_flags['satellite'] = true;
					}

					if ( ! $this->page_flags['scroll_reveal'] && $this->scroll_reveal_enabled
						&& in_array( $name, $this->scroll_reveal_blocks, true )
						&& filter_var( $attrs['jetTricksScrollReveal'] ?? false, FILTER_VALIDATE_BOOLEAN )
					) {
						$this->page_flags['scroll_reveal'] = true;
					}
				}

				if ( ! empty( $block['innerBlocks'] ) ) {
					$this->scan_blocks( $block['innerBlocks'] );
				}

				if ( $this->page_flags['parallax'] && $this->page_flags['tooltip'] && $this->page_flags['satellite'] && $this->page_flags['scroll_reveal'] ) {
					return;
				}
			}
		}
	}
}
