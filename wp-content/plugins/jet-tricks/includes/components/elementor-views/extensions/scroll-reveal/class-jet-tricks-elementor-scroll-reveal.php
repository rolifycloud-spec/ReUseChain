<?php
/**
 * Scroll Reveal controls and render helpers for Elementor (widgets, containers, sections, columns).
 *
 * @package jet-tricks
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Elementor_Scroll_Reveal' ) ) {

	/**
	 * Jet_Tricks_Elementor_Scroll_Reveal class
	 */
	class Jet_Tricks_Elementor_Scroll_Reveal {

		/**
		 * Default settings keys (same control IDs as legacy widget extension).
		 *
		 * @return array
		 */
		public static function get_default_settings() {
			return array(
				'jet_tricks_widget_scroll_reveal'                => 'false',
				'jet_tricks_widget_scroll_reveal_effect'         => 'fade-up',
				'jet_tricks_widget_scroll_reveal_duration'       => array(
					'unit' => 's',
					'size' => 0.6,
				),
				'jet_tricks_widget_scroll_reveal_delay'          => array(
					'unit' => 's',
					'size' => 0,
				),
				'jet_tricks_widget_scroll_reveal_once'           => 'true',
				'jet_tricks_widget_scroll_reveal_root_margin'    => 0,
				'jet_tricks_widget_scroll_reveal_on'             => array(
					'desktop',
					'tablet',
					'mobile',
				),
				'jet_tricks_widget_scroll_reveal_mask_direction' => 'up',
				'jet_tricks_widget_scroll_reveal_mask_bg_background' => 'classic',
				'jet_tricks_widget_scroll_reveal_mask_bg_color'    => '#ffffff',
			);
		}

		/**
		 * @return bool
		 */
		public static function is_extension_enabled() {
			$ext = jet_tricks_settings()->get_avaliable_extensions();

			return filter_var( $ext['widget_scroll_reveal'] ?? 'true', FILTER_VALIDATE_BOOLEAN );
		}

		/**
		 * @param \Elementor\Controls_Stack $element Control stack.
		 */
		public static function register_controls( $element ) {

			if ( ! self::is_extension_enabled() ) {
				return;
			}

			if ( \Elementor\Plugin::$instance->breakpoints && method_exists( \Elementor\Plugin::$instance->breakpoints, 'get_active_breakpoints' ) ) {
				$active_breakpoints = \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints();
				$breakpoints_list   = array();

				foreach ( $active_breakpoints as $key => $value ) {
					$breakpoints_list[ $key ] = $value->get_label();
				}

				$breakpoints_list['desktop'] = 'Desktop';
				$breakpoints_list          = array_reverse( $breakpoints_list );
			} else {
				$breakpoints_list = array(
					'desktop' => 'Desktop',
					'tablet'  => 'Tablet',
					'mobile'  => 'Mobile',
				);
			}

			$element->add_control(
				'scroll_reveal_heading',
				array(
					'label'     => esc_html__( 'Scroll Reveal', 'jet-tricks' ),
					'type'      => Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);

			$element->add_control(
				'jet_tricks_widget_scroll_reveal',
				array(
					'label'        => esc_html__( 'Scroll Reveal', 'jet-tricks' ),
					'type'         => Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'Yes', 'jet-tricks' ),
					'label_off'    => esc_html__( 'No', 'jet-tricks' ),
					'return_value' => 'true',
					'default'      => 'false',
				)
			);

			$element->add_control(
				'jet_tricks_widget_scroll_reveal_effect',
				array(
					'label'   => esc_html__( 'Effect', 'jet-tricks' ),
					'type'    => Elementor\Controls_Manager::SELECT,
					'default' => 'fade-up',
					'options' => array(
						'fade'       => esc_html__( 'Fade', 'jet-tricks' ),
						'fade-up'    => esc_html__( 'Fade Up', 'jet-tricks' ),
						'fade-down'  => esc_html__( 'Fade Down', 'jet-tricks' ),
						'fade-left'  => esc_html__( 'Fade Left', 'jet-tricks' ),
						'fade-right' => esc_html__( 'Fade Right', 'jet-tricks' ),
						'zoom-in'    => esc_html__( 'Zoom In', 'jet-tricks' ),
						'zoom-out'   => esc_html__( 'Zoom Out', 'jet-tricks' ),
						'flip-up'    => esc_html__( 'Flip Up', 'jet-tricks' ),
						'flip-down'  => esc_html__( 'Flip Down', 'jet-tricks' ),
						'mask'       => esc_html__( 'Mask', 'jet-tricks' ),
					),
					'condition' => array(
						'jet_tricks_widget_scroll_reveal' => 'true',
					),
				)
			);

			$element->add_control(
				'jet_tricks_widget_scroll_reveal_mask_direction',
				array(
					'label'   => esc_html__( 'Mask direction', 'jet-tricks' ),
					'type'    => Elementor\Controls_Manager::SELECT,
					'default' => 'up',
					'options' => array(
						'up'                => esc_html__( 'Up', 'jet-tricks' ),
						'down'              => esc_html__( 'Down', 'jet-tricks' ),
						'left'              => esc_html__( 'Left', 'jet-tricks' ),
						'right'             => esc_html__( 'Right', 'jet-tricks' ),
						'center-vertical'   => esc_html__( 'Center - Vertical', 'jet-tricks' ),
						'center-horizontal' => esc_html__( 'Center - Horizontal', 'jet-tricks' ),
						'center-all'        => esc_html__( 'Center - All sides', 'jet-tricks' ),
					),
					'condition' => array(
						'jet_tricks_widget_scroll_reveal'        => 'true',
						'jet_tricks_widget_scroll_reveal_effect' => 'mask',
					),
				)
			);

			$element->add_group_control(
				Elementor\Group_Control_Background::get_type(),
				array(
					'name'      => 'jet_tricks_widget_scroll_reveal_mask_bg',
					'label'     => esc_html__( 'Mask', 'jet-tricks' ),
					'types'     => array( 'classic', 'gradient' ),
					'selector'  => '{{WRAPPER}}.jet-scroll-reveal--effect-mask::before',
					'condition' => array(
						'jet_tricks_widget_scroll_reveal'        => 'true',
						'jet_tricks_widget_scroll_reveal_effect' => 'mask',
					),
				)
			);

			$element->add_responsive_control(
				'jet_tricks_widget_scroll_reveal_mask_border_radius',
				array(
					'label'      => esc_html__( 'Mask Border Radius', 'jet-tricks' ),
					'type'       => Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
					'selectors'  => array(
						'{{WRAPPER}}.jet-scroll-reveal--effect-mask::before' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'condition'  => array(
						'jet_tricks_widget_scroll_reveal'        => 'true',
						'jet_tricks_widget_scroll_reveal_effect' => 'mask',
					),
				)
			);

			$element->add_control(
				'jet_tricks_widget_scroll_reveal_duration',
				array(
					'label'      => esc_html__( 'Duration', 'jet-tricks' ),
					'type'       => Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 's' ),
					'range'      => array(
						's' => array(
							'min'  => 0.1,
							'max'  => 5,
							'step' => 0.05,
						),
					),
					'default'   => array(
						'unit' => 's',
						'size' => 0.6,
					),
					'condition' => array(
						'jet_tricks_widget_scroll_reveal' => 'true',
					),
				)
			);

			$element->add_control(
				'jet_tricks_widget_scroll_reveal_delay',
				array(
					'label'      => esc_html__( 'Delay', 'jet-tricks' ),
					'type'       => Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 's' ),
					'range'      => array(
						's' => array(
							'min'  => 0,
							'max'  => 10,
							'step' => 0.05,
						),
					),
					'default'   => array(
						'unit' => 's',
						'size' => 1,
					),
					'condition' => array(
						'jet_tricks_widget_scroll_reveal' => 'true',
					),
				)
			);

			$element->add_control(
				'jet_tricks_widget_scroll_reveal_once',
				array(
					'label'         => esc_html__( 'Animate Once', 'jet-tricks' ),
					'type'          => Elementor\Controls_Manager::SWITCHER,
					'label_on'      => esc_html__( 'Yes', 'jet-tricks' ),
					'label_off'     => esc_html__( 'No', 'jet-tricks' ),
					'description'   => esc_html__( 'When off, the animation repeats each time the element enters the viewport.', 'jet-tricks' ),
					'return_value'  => 'true',
					'default'       => 'true',
					'condition'     => array(
						'jet_tricks_widget_scroll_reveal' => 'true',
					),
				)
			);

			$element->add_control(
				'jet_tricks_widget_scroll_reveal_root_margin',
				array(
					'label'       => esc_html__( 'Viewport offset (px)', 'jet-tricks' ),
					'description' => esc_html__( 'Adjusts when the reveal triggers relative to the viewport edge. Negative values start the animation earlier.', 'jet-tricks' ),
					'type'        => Elementor\Controls_Manager::NUMBER,
					'min'         => -200,
					'max'         => 200,
					'step'        => 1,
					'default'     => 0,
					'condition'   => array(
						'jet_tricks_widget_scroll_reveal' => 'true',
					),
				)
			);

			$element->add_control(
				'jet_tricks_widget_scroll_reveal_on',
				array(
					'label'       => __( 'Active On', 'jet-tricks' ),
					'type'        => Elementor\Controls_Manager::SELECT2,
					'multiple'    => true,
					'label_block' => true,
					'default'     => array(
						'desktop',
						'tablet',
						'mobile',
					),
					'options'     => $breakpoints_list,
					'condition'   => array(
						'jet_tricks_widget_scroll_reveal' => 'true',
					),
				)
			);
		}

		/**
		 * @param \Elementor\Element_Base $element        Element.
		 * @param array                   $settings       Settings for display (merged with defaults).
		 * @param array                   $widget_settings Output JSON settings (by ref).
		 * @return bool True if scroll reveal was applied.
		 */
		public static function apply_render( $element, array $settings, array &$widget_settings ) {

			if ( ! self::is_extension_enabled() ) {
				return false;
			}

			if ( ! filter_var( $settings['jet_tricks_widget_scroll_reveal'], FILTER_VALIDATE_BOOLEAN ) ) {
				return false;
			}

			$effect = isset( $settings['jet_tricks_widget_scroll_reveal_effect'] ) ? sanitize_key( $settings['jet_tricks_widget_scroll_reveal_effect'] ) : 'fade-up';

			$mask_direction = isset( $settings['jet_tricks_widget_scroll_reveal_mask_direction'] ) ? sanitize_key( $settings['jet_tricks_widget_scroll_reveal_mask_direction'] ) : 'up';

			$allowed_mask_dirs = array( 'up', 'down', 'left', 'right', 'center-vertical', 'center-horizontal', 'center-all' );

			if ( 'mask' === $effect && ! in_array( $mask_direction, $allowed_mask_dirs, true ) ) {
				$mask_direction = 'up';
			}

			$widget_settings['scrollReveal']           = 'true';
			$widget_settings['scrollRevealEffect']     = $effect;
			$widget_settings['scrollRevealDuration']   = $settings['jet_tricks_widget_scroll_reveal_duration'];
			$widget_settings['scrollRevealDelay']      = $settings['jet_tricks_widget_scroll_reveal_delay'];
			$widget_settings['scrollRevealOnce']       = filter_var( $settings['jet_tricks_widget_scroll_reveal_once'], FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';
			$widget_settings['scrollRevealRootMargin'] = isset( $settings['jet_tricks_widget_scroll_reveal_root_margin'] ) ? (int) $settings['jet_tricks_widget_scroll_reveal_root_margin'] : 0;
			$widget_settings['scrollRevealOn']         = $settings['jet_tricks_widget_scroll_reveal_on'];

			$reveal_classes = 'jet-scroll-reveal-widget jet-scroll-reveal--pending jet-scroll-reveal--effect-' . $effect;

			if ( 'mask' === $effect ) {
				$widget_settings['scrollRevealMaskDirection'] = $mask_direction;
				$reveal_classes .= ' jet-scroll-reveal--mask-dir-' . $mask_direction;
			}

			$element->add_render_attribute(
				'_wrapper',
				array(
					'class' => $reveal_classes,
				)
			);

			return true;
		}
	}
}
