<?php
/**
 * JetTricks blocks views tooltip extension.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Tooltip_Extension' ) ) {

	/**
	 * Define Jet_Tricks_Blocks_Tooltip_Extension class.
	 */
	class Jet_Tricks_Blocks_Tooltip_Extension {

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
				'core/html',
			];

			return apply_filters( 'jet-tricks/blocks-tooltip/supported-blocks', $blocks );
		}

		/**
		 * Initialize extension.
		 *
		 * Frontend output is handled by Jet_Tricks_Blocks_Extensions_Render.
		 *
		 * @return void
		 */
		public function init() {
			$this->supported_blocks = self::get_supported_blocks();

			if ( ! function_exists( 'jet_tricks_settings' ) ) {
				return;
			}

			$avaliable_extensions = jet_tricks_settings()->get_avaliable_extensions();

			if ( ! filter_var( $avaliable_extensions['widget_tooltip'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
				return;
			}

			add_action( 'init', [ $this, 'register_tooltip_styles' ], 20 );
		}

		/**
		 * Register tooltip style controls in Blocks Style Manager.
		 *
		 * @return void
		 */
		public function register_tooltip_styles() {
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
					'id'        => 'section_tooltip_style',
					'title'     => __( 'Tooltip', 'jet-tricks' ),
					'condition' => [
						'jetTricksTooltip' => true,
					],
				] );

				$tooltip_sel          = '{{WRAPPER}} .tippy-box';
				$tooltip_content_sel  = '{{WRAPPER}} .tippy-box .tippy-content';

				$proxy->add_control( [
					'id'           => 'tooltip_color',
					'type'         => 'color-picker',
					'label'        => __( 'Text Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$tooltip_content_sel => 'color: {{VALUE}}',
					],
				] );

				$proxy->add_control( [
					'id'           => 'tooltip_typography',
					'type'         => 'typography',
					'label'        => __( 'Typography', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$tooltip_content_sel => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
					],
				] );

				$proxy->add_responsive_control( [
					'id'           => 'tooltip_text_align',
					'type'         => 'choose',
					'label'        => __( 'Text Alignment', 'jet-tricks' ),
					'separator'    => 'after',
					'options'      => [
						'left'   => [ 'shortcut' => __( 'Left', 'jet-tricks' ), 'icon' => 'dashicons-editor-alignleft' ],
						'center' => [ 'shortcut' => __( 'Center', 'jet-tricks' ), 'icon' => 'dashicons-editor-aligncenter' ],
						'right'  => [ 'shortcut' => __( 'Right', 'jet-tricks' ), 'icon' => 'dashicons-editor-alignright' ],
					],
					'css_selector' => [
						$tooltip_content_sel => 'text-align: {{VALUE}};',
					],
				] );

				$proxy->add_control( [
					'id'           => 'tooltip_arrow_color',
					'type'         => 'color-picker',
					'label'        => __( 'Arrow Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						'{{WRAPPER}} .tippy-box[data-placement*=left] .tippy-arrow:before'   => 'border-left-color: {{VALUE}}',
						'{{WRAPPER}} .tippy-box[data-placement*=right] .tippy-arrow:before'  => 'border-right-color: {{VALUE}}',
						'{{WRAPPER}} .tippy-box[data-placement*=top] .tippy-arrow:before'    => 'border-top-color: {{VALUE}}',
						'{{WRAPPER}} .tippy-box[data-placement*=bottom] .tippy-arrow:before' => 'border-bottom-color: {{VALUE}}',
					],
				] );

				$proxy->add_responsive_control( [
					'id'           => 'tooltip_width',
					'type'         => 'range',
					'label'        => __( 'Width', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [
						[ 'value' => 'px', 'intervals' => [ 'step' => 1, 'min' => 50, 'max' => 1000 ] ],
						[ 'value' => 'em', 'intervals' => [ 'step' => 0.1, 'min' => 1, 'max' => 50 ] ],
					],
					'css_selector' => [
						$tooltip_sel => 'width: {{VALUE}}{{UNIT}};',
					],
				] );

				$proxy->add_control( [
					'id'           => 'tooltip_background',
					'type'         => 'color-picker',
					'label'        => __( 'Background Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$tooltip_sel => 'background-color: {{VALUE}}',
					],
				] );

				$proxy->add_responsive_control( [
					'id'           => 'tooltip_padding',
					'type'         => 'dimensions',
					'label'        => __( 'Padding', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [ 'px', '%' ],
					'css_selector' => [
						$tooltip_content_sel => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
					],
				] );

				$proxy->add_control( [
					'id'           => 'tooltip_border',
					'type'         => 'border',
					'label'        => __( 'Border', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$tooltip_sel => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}};',
					],
				] );

				$proxy->end_section();
			}
		}

	}
}
