<?php
/**
 * JetTricks Block Type: Read More (view-more).
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Views_Type_View_More' ) ) {

	class Jet_Tricks_Blocks_Views_Type_View_More extends Jet_Tricks_Blocks_Views_Type_Base {

		public function get_name() {
			return 'view-more';
		}

		public function get_css_scheme() {
			return [
				'wrapper' => '.jet-view-more',
				'button'  => '.jet-view-more__button',
				'label'   => '.jet-view-more__label',
				'icon'    => '.jet-view-more__icon',
			];
		}

		public function add_style_manager_options() {

			$this->controls_manager->start_section(
				'style_controls',
				[
					'id'           => 'section_button_style',
					'title'        => __( 'Button', 'jet-tricks' ),
					'initial_open' => true,
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'icon_size',
					'type'         => 'range',
					'label'        => __( 'Icon Size', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [
						[
							'value'     => 'px',
							'intervals' => [
								'step' => 1,
								'min'  => 0,
								'max'  => 100,
							],
						],
					],
					'css_selector' => [
						$this->css_selector( $this->css_scheme['icon'] ) => 'font-size: {{VALUE}}{{UNIT}};',
						$this->css_selector( $this->css_scheme['icon'] ) . ' img' => 'width: {{VALUE}}{{UNIT}}; height: {{VALUE}}{{UNIT}};',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'icon_gap',
					'type'         => 'range',
					'label'        => __( 'Icon Gap', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [
						[
							'value'     => 'px',
							'intervals' => [
								'step' => 1,
								'min'  => 0,
								'max'  => 50,
							],
						],
					],
					'css_selector' => [
						$this->css_selector( $this->css_scheme['button'] ) => 'gap: {{VALUE}}{{UNIT}};',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'icon_position',
					'type'         => 'choose',
					'label'        => __( 'Icon Position', 'jet-tricks' ),
					'separator'    => 'after',
					'options'      => [
						'1' => [
							'shortcut' => __( 'Before', 'jet-tricks' ),
							'icon'     => 'dashicons-arrow-left-alt',
						],
						'3' => [
							'shortcut' => __( 'After', 'jet-tricks' ),
							'icon'     => 'dashicons-arrow-right-alt',
						],
					],
					'css_selector' => [
						$this->css_selector( $this->css_scheme['icon'] ) => 'order: {{VALUE}};',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'icon_orientation',
					'type'         => 'choose',
					'label'        => __( 'Icon Orientation', 'jet-tricks' ),
					'separator'    => 'after',
					'options'      => [
						'row'    => [
							'shortcut' => __( 'Horizontal', 'jet-tricks' ),
							'icon'     => 'dashicons-arrow-right-alt',
						],
						'column' => [
							'shortcut' => __( 'Vertical', 'jet-tricks' ),
							'icon'     => 'dashicons-arrow-down-alt',
						],
					],
					'css_selector' => [
						$this->css_selector( $this->css_scheme['button'] ) => 'flex-direction: {{VALUE}};',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'button_typography',
					'type'         => 'typography',
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $this->css_scheme['button'] ) => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'button_padding',
					'type'         => 'dimensions',
					'label'        => __( 'Padding', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $this->css_scheme['button'] ) => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'button_margin',
					'type'         => 'dimensions',
					'label'        => __( 'Margin', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $this->css_scheme['button'] ) => 'margin: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'button_alignment',
					'type'         => 'choose',
					'label'        => __( 'Alignment', 'jet-tricks' ),
					'separator'    => 'after',
					'options'      => [
						'flex-start' => [
							'shortcut' => __( 'Left', 'jet-tricks' ),
							'icon'     => 'dashicons-editor-alignleft',
						],
						'center'     => [
							'shortcut' => __( 'Center', 'jet-tricks' ),
							'icon'     => 'dashicons-editor-aligncenter',
						],
						'flex-end'   => [
							'shortcut' => __( 'Right', 'jet-tricks' ),
							'icon'     => 'dashicons-editor-alignright',
						],
					],
					'css_selector' => [
						$this->css_selector( $this->css_scheme['wrapper'] ) => 'display: flex; justify-content: {{VALUE}};',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'button_border',
					'type'         => 'border',
					'label'        => __( 'Border', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $this->css_scheme['button'] ) => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}};',
					],
				]
			);

			$this->controls_manager->start_tabs(
				'style_controls',
				[
					'id' => 'button_style_tabs',
				]
			);

			$this->controls_manager->start_tab(
				'style_controls',
				[
					'id'    => 'button_normal_tab',
					'title' => __( 'Normal', 'jet-tricks' ),
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'button_color',
					'type'         => 'color-picker',
					'label'        => __( 'Text Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $this->css_scheme['button'] ) => 'color: {{VALUE}}',
						$this->css_selector( $this->css_scheme['label'] ) => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'button_background_color',
					'type'         => 'color-picker',
					'label'        => __( 'Background Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $this->css_scheme['button'] ) => 'background-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'button_icon_color',
					'type'         => 'color-picker',
					'label'        => __( 'Icon Color', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $this->css_scheme['button'] . ' ' . $this->css_scheme['icon'] ) => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->end_tab();

			$this->controls_manager->start_tab(
				'style_controls',
				[
					'id'    => 'button_hover_tab',
					'title' => __( 'Hover', 'jet-tricks' ),
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'button_hover_color',
					'type'         => 'color-picker',
					'label'        => __( 'Text Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $this->css_scheme['button'] ) . ':hover' => 'color: {{VALUE}}',
						$this->css_selector( $this->css_scheme['button'] ) . ':hover ' . $this->css_scheme['label'] => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'button_hover_background_color',
					'type'         => 'color-picker',
					'label'        => __( 'Background Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $this->css_scheme['button'] ) . ':hover' => 'background-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'button_hover_icon_color',
					'type'         => 'color-picker',
					'label'        => __( 'Icon Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $this->css_scheme['button'] ) . ':hover ' . $this->css_scheme['icon'] => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'button_hover_border_color',
					'type'         => 'color-picker',
					'label'        => __( 'Border Color', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $this->css_scheme['button'] ) . ':hover' => 'border-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->end_tab();

			$this->controls_manager->end_tabs();

			$this->controls_manager->end_section();

			// Read Less Button
			$read_less_btn = $this->css_scheme['button'] . '.jet-view-more__button--read-less';

			$this->controls_manager->start_section(
				'style_controls',
				[
					'id'        => 'section_read_less_style',
					'title'     => __( 'Read Less Button', 'jet-tricks' ),
					'condition' => [
						'readLess' => true,
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'read_less_typography',
					'type'         => 'typography',
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $read_less_btn . ' ' . $this->css_scheme['label'] ) => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'read_less_border',
					'type'         => 'border',
					'label'        => __( 'Border', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $read_less_btn ) => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}};',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'read_less_icon_size',
					'type'         => 'range',
					'label'        => __( 'Icon Size', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [
						[
							'value'     => 'px',
							'intervals' => [
								'step' => 1,
								'min'  => 0,
								'max'  => 100,
							],
						],
					],
					'css_selector' => [
						$this->css_selector( $read_less_btn . ' ' . $this->css_scheme['icon'] ) => 'font-size: {{VALUE}}{{UNIT}};',
						$this->css_selector( $read_less_btn . ' ' . $this->css_scheme['icon'] ) . ' img' => 'width: {{VALUE}}{{UNIT}}; height: {{VALUE}}{{UNIT}};',
					],
				]
			);

			$this->controls_manager->start_tabs(
				'style_controls',
				[
					'id' => 'read_less_style_tabs',
				]
			);

			$this->controls_manager->start_tab(
				'style_controls',
				[
					'id'    => 'read_less_normal_tab',
					'title' => __( 'Normal', 'jet-tricks' ),
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'read_less_text_color',
					'type'         => 'color-picker',
					'label'        => __( 'Text Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $read_less_btn ) => 'color: {{VALUE}}',
						$this->css_selector( $read_less_btn . ' ' . $this->css_scheme['label'] ) => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'read_less_background_color',
					'type'         => 'color-picker',
					'label'        => __( 'Background Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $read_less_btn ) => 'background-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'read_less_icon_color',
					'type'         => 'color-picker',
					'label'        => __( 'Icon Color', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $read_less_btn . ' ' . $this->css_scheme['icon'] ) => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->end_tab();

			$this->controls_manager->start_tab(
				'style_controls',
				[
					'id'    => 'read_less_hover_tab',
					'title' => __( 'Hover', 'jet-tricks' ),
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'read_less_hover_text_color',
					'type'         => 'color-picker',
					'label'        => __( 'Text Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $read_less_btn ) . ':hover' => 'color: {{VALUE}}',
						$this->css_selector( $read_less_btn ) . ':hover ' . $this->css_scheme['label'] => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'read_less_hover_background_color',
					'type'         => 'color-picker',
					'label'        => __( 'Background Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $read_less_btn ) . ':hover' => 'background-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'read_less_hover_icon_color',
					'type'         => 'color-picker',
					'label'        => __( 'Icon Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $read_less_btn ) . ':hover ' . $this->css_scheme['icon'] => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'read_less_hover_border_color',
					'type'         => 'color-picker',
					'label'        => __( 'Border Color', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $read_less_btn ) . ':hover' => 'border-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->end_tab();

			$this->controls_manager->end_tabs();

			$this->controls_manager->end_section();
		}

		public function render_callback( $attributes = [] ) {

			$this->enqueue_frontend_assets();

			$sections_raw  = ! empty( $attributes['sections'] ) ? $attributes['sections'] : [];
			$sections_list = [];

			foreach ( $sections_raw as $index => $section ) {
				$id                    = ! empty( $section['id'] ) ? $section['id'] : 'section_' . $index;
				$sections_list[ $id ]  = ! empty( $section['sectionId'] ) ? $section['sectionId'] : '';
			}

			$show_all  = ! empty( $attributes['showAll'] ) ? filter_var( $attributes['showAll'], FILTER_VALIDATE_BOOLEAN ) : false;
			$read_less = ! empty( $attributes['readLess'] ) ? filter_var( $attributes['readLess'], FILTER_VALIDATE_BOOLEAN ) : false;
			$hide_all  = ! empty( $attributes['hideAll'] ) ? filter_var( $attributes['hideAll'], FILTER_VALIDATE_BOOLEAN ) : false;

			$read_more_icon = ! empty( $attributes['buttonIcon'] ) ? $attributes['buttonIcon'] : [];
			$read_less_icon = ! empty( $attributes['readLessIcon'] ) ? $attributes['readLessIcon'] : [];

			$settings = [
				'effect'               => ! empty( $attributes['showEffect'] ) ? $attributes['showEffect'] : 'move-up',
				'hide_effect'          => ! empty( $attributes['hideEffect'] ) ? $attributes['hideEffect'] : 'move-down',
				'sections'             => $sections_list,
				'showall'              => $show_all,
				'read_less'            => $read_less,
				'read_more_label'      => ! empty( $attributes['buttonLabel'] ) ? $attributes['buttonLabel'] : __( 'Read More', 'jet-tricks' ),
				'read_more_icon_html'  => $this->get_icon_html( $read_more_icon ),
				'read_less_label'      => ! empty( $attributes['readLessLabel'] ) ? $attributes['readLessLabel'] : __( 'Read Less', 'jet-tricks' ),
				'read_less_icon_html'  => $this->get_icon_html( $read_less_icon ),
				'hide_all'             => $hide_all,
			];

			$button_label = esc_html( $settings['read_more_label'] );
			$icon_html    = $settings['read_more_icon_html'];
			$class_name   = ! empty( $attributes['className'] ) ? ' ' . esc_attr( $attributes['className'] ) : '';

			$label_html = '';
			if ( ! empty( $button_label ) ) {
				$label_html = sprintf( '<div class="jet-view-more__label">%s</div>', $button_label );
			}

			$icon_wrapper = '';
			if ( ! empty( $icon_html ) ) {
				$icon_wrapper = sprintf( '<div class="jet-view-more__icon jet-tricks-icon">%s</div>', $icon_html );
			}

			return sprintf(
				'<div><div class="jet-tricks-view-more" data-is-block="jet-tricks/view-more"><div class="jet-view-more%1$s" data-settings="%2$s"><div class="jet-view-more__button" role="button" tabindex="0">%3$s%4$s</div></div></div></div>',
				$class_name,
				esc_attr( wp_json_encode( $settings ) ),
				$icon_wrapper,
				$label_html
			);
		}

		private function get_icon_html( $icon ) {

			if ( empty( $icon ) ) {
				return '';
			}

			// Gutenberg format: { id, url } — SVG media upload (inline for color inheritance)
			if ( is_array( $icon ) && ! empty( $icon['url'] ) ) {
				$url = $icon['url'];
				$attachment_id = ! empty( $icon['id'] ) ? (int) $icon['id'] : ( preg_match( '/\.svg$/i', $url ) ? attachment_url_to_postid( $url ) : 0 );
				$svg = $attachment_id ? $this->get_inline_svg( $attachment_id ) : '';
				if ( $svg ) {
					return $svg;
				}
				return sprintf(
					'<img src="%s" alt="" class="jet-view-more-icon-svg" style="width: 1em; height: 1em;" />',
					esc_url( $url )
				);
			}

			// Icon object format: { value: 'fa fa-class', library: '...' }
			if ( is_array( $icon ) && ! empty( $icon['value'] ) ) {
				return sprintf( '<i class="%s"></i>', esc_attr( $icon['value'] ) );
			}

			// String fallback
			if ( is_string( $icon ) && ! empty( $icon ) ) {
				return sprintf( '<i class="%s"></i>', esc_attr( $icon ) );
			}

			return '';
		}

		private function get_inline_svg( $attachment_id ) {
			if ( ! $attachment_id ) {
				return '';
			}
			$path = get_attached_file( $attachment_id );
			if ( ! $path || ! file_exists( $path ) ) {
				return '';
			}
			$svg = file_get_contents( $path );
			return ( ! empty( $svg ) && strpos( $svg, '<svg' ) !== false ) ? $svg : '';
		}
	}
}
