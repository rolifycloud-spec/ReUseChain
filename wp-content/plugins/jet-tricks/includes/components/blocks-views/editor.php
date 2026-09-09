<?php
/**
 * JetTricks Blocks Views Editor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Views_Editor' ) ) {

	/**
	 * Define Jet_Tricks_Blocks_Views_Editor class
	 */
	class Jet_Tricks_Blocks_Views_Editor {

		/**
		 * Constructor for the class
		 */
		public function __construct() {
			add_action( 'enqueue_block_editor_assets', [ $this, 'blocks_assets' ] );
		}

		/**
		 * Register blocks assets
		 */
		public function blocks_assets() {

			$this->enqueue_style_manager_assets();
			$this->enqueue_hotspots_tooltips_assets();

			$script_deps = [ 'wp-blocks', 'wp-components', 'wp-element', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render', 'lodash', 'jet-tricks-tippy-bundle' ];
			if ( wp_script_is( 'crocoblock-blocks-style-editor', 'registered' ) ) {
				$script_deps[] = 'crocoblock-blocks-style-editor';
			}

			wp_enqueue_script(
				'jet-tricks-blocks-views',
				jet_tricks()->plugin_url( 'assets/js/admin/blocks-views/blocks.js' ),
				$script_deps,
				jet_tricks()->get_version(),
				true
			);

			$config = [
				'imageSizes'              => $this->get_image_sizes(),
				'particlesGeneratorUrl'   => $this->get_particles_generator_url(),
				'particlesVersion'        => $this->get_particles_version(),
				'particlesSettingsUrl'    => admin_url( 'admin.php?page=jet-dashboard-settings-page&subpage=jet-tricks-general-settings' ),
				'particlesBlocks'         => $this->get_extension_blocks( 'section_particles', 'Jet_Tricks_Blocks_Particles_Extension' ),
				'tooltipBlocks'           => $this->get_extension_blocks( 'widget_tooltip', 'Jet_Tricks_Blocks_Tooltip_Extension' ),
				'satelliteBlocks'         => $this->get_extension_blocks( 'widget_satellite', 'Jet_Tricks_Blocks_Satellite_Extension' ),
				'parallaxBlocks'          => $this->get_extension_blocks( 'widget_parallax', 'Jet_Tricks_Blocks_Parallax_Extension' ),
				'scrollRevealBlocks'      => $this->get_extension_blocks( 'widget_scroll_reveal', 'Jet_Tricks_Blocks_Scroll_Reveal_Extension' ),
				'stickyColumnBlocks'      => $this->get_extension_blocks( 'column_sticky', 'Jet_Tricks_Blocks_Sticky_Column_Extension' ),
			];

			wp_localize_script(
				'jet-tricks-blocks-views',
				'JetTricksBlocksData',
				apply_filters( 'jet-tricks/blocks-views/editor/config', $config )
			);

			if ( ! empty( $config['tooltipBlocks'] ) || ! empty( $config['satelliteBlocks'] ) || ! empty( $config['stickyColumnBlocks'] ) || ! empty( $config['scrollRevealBlocks'] ) ) {
				wp_enqueue_style(
					'jet-tricks-frontend',
					jet_tricks()->plugin_url( 'assets/css/jet-tricks-frontend.css' ),
					[],
					jet_tricks()->get_version()
				);
				wp_enqueue_style(
					'jet-tricks-icons-font',
					jet_tricks()->plugin_url( 'assets/css/jet-tricks-icons.css' ),
					[],
					jet_tricks()->get_version()
				);
			}

			wp_enqueue_style(
				'jet-tricks-blocks-views-css',
				jet_tricks()->plugin_url( 'assets/css/admin/blocks-views.css' ),
				false,
				jet_tricks()->get_version()
			);

			jet_tricks_assets()->enqueue_styles();
		}

		/**
		 * Enqueue style manager editor assets
		 *
		 * @return void
		 */
		private function enqueue_style_manager_assets() {
			if ( isset( jet_tricks()->blocks_views->style_manager ) && jet_tricks()->blocks_views->style_manager ) {
				jet_tricks()->blocks_views->style_manager->enqueue_editor_assets();
			}
		}

		/**
		 * Enqueue assets for hotspots tooltips in block editor
		 *
		 * @return void
		 */
		private function enqueue_hotspots_tooltips_assets() {
			Jet_Tricks_Assets::ensure_tippy_registered();
			wp_enqueue_script( 'jet-tricks-tippy-bundle' );
		}

		/**
		 * Get supported blocks for extension (passed to JS when extension is enabled).
		 *
		 * @param string $setting_key   Key in avaliable_extensions (e.g. 'section_particles', 'widget_tooltip').
		 * @param string $extension_class Class name with get_supported_blocks() method.
		 * @return string[]
		 */
		private function get_extension_blocks( $setting_key, $extension_class ) {
			if ( ! function_exists( 'jet_tricks_settings' ) || ! class_exists( $extension_class ) ) {
				return [];
			}
			$extensions = jet_tricks_settings()->get_avaliable_extensions();
			if ( ! filter_var( $extensions[ $setting_key ] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
				return [];
			}
			return $extension_class::get_supported_blocks();
		}

		/**
		 * Get Particles generator URL
		 *
		 * @return string
		 */
		private function get_particles_generator_url() {
			if ( ! function_exists( 'jet_tricks_settings' ) ) {
				return 'https://vincentgarreau.com/particles.js/';
			}
			$version = $this->get_particles_version();
			return version_compare( $version, '3.0.2', '>=' )
				? 'https://particles.js.org/'
				: 'https://vincentgarreau.com/particles.js/';
		}

		/**
		 * Get particles version used by plugin settings.
		 *
		 * @return string
		 */
		private function get_particles_version() {
			if ( ! function_exists( 'jet_tricks_settings' ) ) {
				return '1.18.11';
			}

			return jet_tricks_settings()->get( 'particles_version', '1.18.11' );
		}

		/**
		 * Get available image sizes for block editor
		 *
		 * @return array
		 */
		private function get_image_sizes() {
			global $_wp_additional_image_sizes;

			$sizes = get_intermediate_image_sizes();
			$result = [
				[ 'value' => 'full', 'label' => __( 'Full', 'jet-tricks' ) ],
			];

			foreach ( $sizes as $size ) {
				if ( in_array( $size, [ 'thumbnail', 'medium', 'medium_large', 'large' ], true ) ) {
					$label = ucwords( trim( str_replace( [ '-', '_' ], [ ' ', ' ' ], $size ) ) );
					$result[] = [ 'value' => $size, 'label' => $label ];
				} elseif ( ! empty( $_wp_additional_image_sizes[ $size ] ) ) {
					$label = sprintf(
						'%1$s (%2$s×%3$s)',
						ucwords( trim( str_replace( [ '-', '_' ], [ ' ', ' ' ], $size ) ) ),
						$_wp_additional_image_sizes[ $size ]['width'],
						$_wp_additional_image_sizes[ $size ]['height']
					);
					$result[] = [ 'value' => $size, 'label' => $label ];
				}
			}

			return $result;
		}
	}
}
