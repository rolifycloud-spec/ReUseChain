<?php
/**
 * JetTricks blocks views scroll reveal extension.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Scroll_Reveal_Extension' ) ) {

	/**
	 * Define Jet_Tricks_Blocks_Scroll_Reveal_Extension class.
	 */
	class Jet_Tricks_Blocks_Scroll_Reveal_Extension {

		private $supported_blocks;

		/**
		 * Returns supported block names.
		 *
		 * @return string[]
		 */
		public static function get_supported_blocks() {
			$blocks = [
				'core/paragraph',
				'core/heading',
				'core/button',
				'core/image',
				'core/list',
				'core/quote',
				'core/group',
				'core/columns',
				'core/column',
				'core/cover',
			];

			return apply_filters( 'jet-tricks/blocks-scroll-reveal/supported-blocks', $blocks );
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

			if ( ! filter_var( $avaliable_extensions['widget_scroll_reveal'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
				return;
			}

			add_action( 'init', [ $this, 'register_scroll_reveal_styles' ], 20 );
		}

		/**
		 * Register scroll reveal style controls in Blocks Style Manager.
		 *
		 * @return void
		 */
		public function register_scroll_reveal_styles() {
			if ( ! isset( jet_tricks()->blocks_views->style_manager )
				|| ! ( jet_tricks()->blocks_views->style_manager instanceof \Crocoblock\Blocks_Style\Manager ) ) {
				return;
			}

			$sm = jet_tricks()->blocks_views->style_manager;

			foreach ( $this->supported_blocks as $block_name ) {
				$sm->register_block_support( $block_name );
				$proxy = $sm->get_proxy( $block_name );

				if ( ! $proxy ) {
					continue;
				}

				$proxy->start_section( 'style_controls', [
					'id'        => 'section_scroll_reveal_style',
					'title'     => __( 'Scroll Reveal', 'jet-tricks' ),
					'condition' => [
						'jetTricksScrollReveal'       => true,
						'jetTricksScrollRevealEffect' => 'mask',
					],
				] );

				$mask_selector = '{{WRAPPER}}.jet-scroll-reveal--effect-mask::before';

				$proxy->add_control( [
					'id'           => 'scroll_reveal_mask_color',
					'type'         => 'color-picker',
					'label'        => __( 'Mask', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$mask_selector => 'background-color: {{VALUE}};',
					],
				] );

				$proxy->add_responsive_control( [
					'id'           => 'scroll_reveal_mask_border_radius',
					'type'         => 'dimensions',
					'label'        => __( 'Mask Border Radius', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [ 'px', '%', 'em', 'rem' ],
					'css_selector' => [
						$mask_selector => 'border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
					],
				] );

				$proxy->end_section();
			}
		}
	}
}
