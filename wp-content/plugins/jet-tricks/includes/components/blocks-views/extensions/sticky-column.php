<?php
/**
 * JetTricks blocks views sticky column extension (core/column).
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Sticky_Column_Extension' ) ) {

	/**
	 * Jet_Tricks_Blocks_Sticky_Column_Extension class.
	 */
	class Jet_Tricks_Blocks_Sticky_Column_Extension {

		/**
		 * Supported block names.
		 *
		 * @return string[]
		 */
		public static function get_supported_blocks() {
			return [ 'core/column' ];
		}

		/**
		 * Initialize extension.
		 *
		 * @return void
		 */
		public function init() {
			if ( ! function_exists( 'jet_tricks_settings' ) ) {
				return;
			}

			$extensions = jet_tricks_settings()->get_avaliable_extensions();

			if ( ! filter_var( $extensions['column_sticky'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
				return;
			}

			// Before Jet_Tricks_Blocks_Extensions_Render (priority 10) so markup stays columns > column.
			add_filter( 'render_block', [ $this, 'render_block' ], 9, 2 );
			add_action( 'enqueue_block_assets', [ $this, 'maybe_enqueue_frontend_assets' ], 10 );
		}

		/**
		 * Filter block output — inner wrapper, jet-sticky-column, data-jet-settings, inline z-index.
		 *
		 * @param string $block_content Block HTML.
		 * @param array  $block         Block data.
		 * @return string
		 */
		public function render_block( $block_content, $block ) {
			if ( empty( $block['blockName'] ) || 'core/column' !== $block['blockName'] ) {
				return $block_content;
			}

			$attrs = $block['attrs'] ?? [];

			if ( empty( $attrs['jetTricksStickyColumn'] ) || ! filter_var( $attrs['jetTricksStickyColumn'], FILTER_VALIDATE_BOOLEAN ) ) {
				return $block_content;
			}

			$sticky_on = isset( $attrs['jetTricksStickyOn'] ) && is_array( $attrs['jetTricksStickyOn'] ) ? array_values( array_filter( $attrs['jetTricksStickyOn'] ) ) : [ 'desktop', 'tablet' ];
			if ( empty( $sticky_on ) ) {
				$sticky_on = [ 'desktop', 'tablet', 'mobile' ];
			}

			$column_settings = [
				'topSpacing'    => isset( $attrs['jetTricksStickyTop'] ) ? (int) $attrs['jetTricksStickyTop'] : 50,
				'bottomSpacing' => isset( $attrs['jetTricksStickyBottom'] ) ? (int) $attrs['jetTricksStickyBottom'] : 50,
				'stickyAlign'   => $this->sanitize_sticky_align( $attrs['jetTricksStickyAlign'] ?? 'top' ),
				'stickyOn'      => $sticky_on,
				'zIndex'        => isset( $attrs['jetTricksStickyZIndex'] ) && $attrs['jetTricksStickyZIndex'] !== '' ? (int) $attrs['jetTricksStickyZIndex'] : 1100,
			];

			$html = $this->add_sticky_attributes_to_column_root( $block_content, $column_settings, $attrs );

			return $html;
		}

		/**
		 * Add jet-sticky-column, data-jet-settings, merged inline style (z-index) to the column root.
		 *
		 * @param string $html             HTML.
		 * @param array  $column_settings  Settings for JSON.
		 * @param array  $attrs            Block attrs.
		 * @return string
		 */
		private function add_sticky_attributes_to_column_root( $html, $column_settings, $attrs ) {
			$processor = new WP_HTML_Tag_Processor( $html );
			if ( ! $processor->next_tag( [ 'tag_name' => 'div' ] ) ) {
				return $html;
			}

			$processor->add_class( 'jet-sticky-column' );
			$processor->set_attribute(
				'data-jet-settings',
				wp_json_encode( $column_settings, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE )
			);

			$style_bits = $this->build_sticky_style_declarations( $attrs );
			if ( $style_bits !== '' ) {
				$existing = (string) $processor->get_attribute( 'style' );
				if ( '' !== $existing ) {
					$existing = trim( rtrim( $existing, ';' ) ) . '; ';
				}
				$processor->set_attribute( 'style', $existing . $style_bits );
			}

			return $processor->get_updated_html();
		}

		/**
		 * Inline style fragment for z-index.
		 *
		 * @param array $attrs Block attrs.
		 * @return string
		 */
		private function build_sticky_style_declarations( $attrs ) {
			$parts = [];

			$z = isset( $attrs['jetTricksStickyZIndex'] ) && $attrs['jetTricksStickyZIndex'] !== '' ? (int) $attrs['jetTricksStickyZIndex'] : 1100;
			if ( $z > 0 ) {
				$parts[] = 'z-index:' . $z;
			}

			return implode( ';', $parts );
		}

		/**
		 * Normalize sticky align setting for block sticky.
		 *
		 * @param string $align Raw align.
		 * @return string
		 */
		private function sanitize_sticky_align( $align ) {
			$align = sanitize_key( $align );

			if ( ! in_array( $align, [ 'top', 'center', 'bottom' ], true ) ) {
				$align = 'top';
			}

			return $align;
		}

		/**
		 * Enqueue frontend assets when post contains sticky columns.
		 *
		 * @return void
		 */
		public function maybe_enqueue_frontend_assets() {
			if ( is_admin() || ! $this->page_has_sticky_column() ) {
				return;
			}

			wp_enqueue_style(
				'jet-tricks-frontend',
				jet_tricks()->plugin_url( 'assets/css/jet-tricks-frontend.css' ),
				[],
				jet_tricks()->get_version()
			);

			Jet_Tricks_Assets::ensure_jet_tricks_frontend_registered();
			wp_enqueue_script( 'jet-tricks-frontend' );
		}

		/**
		 * Whether current singular post content includes a sticky column block.
		 *
		 * @return bool
		 */
		private function page_has_sticky_column() {
			$post = get_post();
			if ( ! $post || empty( $post->post_content ) ) {
				return false;
			}

			return $this->blocks_contain_sticky_column( parse_blocks( $post->post_content ) );
		}

		/**
		 * Recursive scan for sticky core/column.
		 *
		 * @param array[] $blocks Parsed blocks.
		 * @return bool
		 */
		private function blocks_contain_sticky_column( $blocks ) {
			foreach ( $blocks as $block ) {
				if ( ! empty( $block['blockName'] ) && 'core/column' === $block['blockName'] ) {
					$attrs = $block['attrs'] ?? [];
					if ( ! empty( $attrs['jetTricksStickyColumn'] ) && filter_var( $attrs['jetTricksStickyColumn'], FILTER_VALIDATE_BOOLEAN ) ) {
						return true;
					}
				}
				if ( ! empty( $block['innerBlocks'] ) && $this->blocks_contain_sticky_column( $block['innerBlocks'] ) ) {
					return true;
				}
			}

			return false;
		}
	}
}
