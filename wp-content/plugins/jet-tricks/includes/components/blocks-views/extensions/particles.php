<?php
/**
 * JetTricks blocks views particles extension.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Particles_Extension' ) ) {

	/**
	 * Define Jet_Tricks_Blocks_Particles_Extension class.
	 */
	class Jet_Tricks_Blocks_Particles_Extension {

		private $supported_blocks;

		/**
		 * Whether frontend scripts have already been enqueued this request.
		 *
		 * @var bool
		 */
		private $scripts_enqueued = false;

		/**
		 * Returns supported block names.
		 *
		 * @return string[]
		 */
		public static function get_supported_blocks() {
			$blocks = [
				'core/group',
				'core/columns',
				'core/column',
			];
			return apply_filters( 'jet-tricks/blocks-particles/supported-blocks', $blocks );
		}

		/**
		 * Initialize extension.
		 *
		 * @return void
		 */
		public function init() {
			$this->supported_blocks = self::get_supported_blocks();

			if ( ! function_exists( 'jet_tricks_settings' ) ) {
				return;
			}

			$avaliable_extensions = jet_tricks_settings()->get_avaliable_extensions();

			if ( ! filter_var( $avaliable_extensions['section_particles'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
				return;
			}

			add_filter( 'render_block', [ $this, 'render_block' ], 10, 2 );
		}

		/**
		 * Filter block output — inject particles class, data attributes and the
		 * particles JSON directly into the block root element.
		 *
		 * @param string $block_content Block HTML.
		 * @param array  $block         Block data.
		 * @return string
		 */
		public function render_block( $block_content, $block ) {
			if ( empty( $block['blockName'] ) || ! in_array( $block['blockName'], $this->supported_blocks, true ) ) {
				return $block_content;
			}

			$attrs   = $block['attrs'] ?? [];
			$enabled = filter_var( $attrs['jetTricksParticles'] ?? false, FILTER_VALIDATE_BOOLEAN );
			$json    = $attrs['jetTricksParticlesJson'] ?? '';

			if ( ! $enabled || empty( trim( $json ) ) ) {
				return $block_content;
			}

			$block_id = ! empty( $attrs['anchor'] ) ? sanitize_html_class( $attrs['anchor'] ) : 'jet-particles-' . wp_unique_id();

			$this->maybe_enqueue_scripts();

			$processor = new WP_HTML_Tag_Processor( $block_content );
			if ( ! $processor->next_tag() ) {
				return $block_content;
			}

			$processor->add_class( 'jet-tricks-particles-section' );
			$processor->set_attribute( 'data-jet-tricks-particles', 'true' );
			$processor->set_attribute( 'data-jet-tricks-particles-id', $block_id );
			$processor->set_attribute( 'data-jet-tricks-particles-json', $json );

			return $processor->get_updated_html();
		}

		/**
		 * Enqueue tsParticles and the JetTricks frontend script on first call.
		 * Called lazily from render_block so it works in any rendering context.
		 *
		 * @return void
		 */
		private function maybe_enqueue_scripts() {
			if ( $this->scripts_enqueued || is_admin() ) {
				return;
			}
			$this->scripts_enqueued = true;

			if ( function_exists( 'jet_tricks_assets' ) ) {
				jet_tricks_assets()->register_scripts();
			}

			wp_enqueue_script( 'jet-tricks-ts-particles' );
			wp_enqueue_style(
				'jet-tricks-frontend',
				jet_tricks()->plugin_url( 'assets/css/jet-tricks-frontend.css' ),
				[],
				jet_tricks()->get_version()
			);

			Jet_Tricks_Assets::ensure_jet_tricks_frontend_registered();
			wp_enqueue_script( 'jet-tricks-frontend' );
		}
	}
}
