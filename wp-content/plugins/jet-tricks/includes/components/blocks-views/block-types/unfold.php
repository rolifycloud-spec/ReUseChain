<?php
/**
 * JetTricks Block Type: Unfold.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Views_Type_Unfold' ) ) {

	class Jet_Tricks_Blocks_Views_Type_Unfold extends Jet_Tricks_Blocks_Views_Type_Base {

		public function get_name() {
			return 'unfold';
		}

		public function get_css_scheme() {
			return [
				'instance'  => '.jet-unfold',
				'inner'     => '.jet-unfold__inner',
				'mask'      => '.jet-unfold__mask',
				'separator' => '.jet-unfold__separator',
				'content'   => '.jet-unfold__content',
				'button'    => '.jet-unfold__button',
				'trigger'   => '.jet-unfold__trigger',
			];
		}

		public function enqueue_frontend_assets() {
			parent::enqueue_frontend_assets();

			if ( ! wp_script_is( 'jet-anime-js', 'registered' ) ) {
				wp_register_script(
					'jet-anime-js',
					jet_tricks()->plugin_url( 'assets/js/lib/anime/anime.min.js' ),
					[],
					'2.2.0',
					true
				);
			}
			if ( ! wp_script_is( 'jet-anime-js', 'enqueued' ) ) {
				wp_enqueue_script( 'jet-anime-js' );
			}
		}

		public function add_style_manager_options() {
			$c = $this->css_scheme;

			// Container - Fold state
			$this->controls_manager->start_section(
				'style_controls',
				[
					'id'           => 'section_container_fold',
					'title'        => __( 'Container (Folded)', 'jet-tricks' ),
					'initial_open' => false,
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'container_fold_bg',
					'type'         => 'color-picker',
					'label'        => __( 'Background Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['instance'] ) => 'background-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'container_fold_border',
					'type'         => 'border',
					'label'        => __( 'Border', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['instance'] ) => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}};',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'container_padding',
					'type'         => 'dimensions',
					'label'        => __( 'Padding', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['instance'] ) => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'container_margin',
					'type'         => 'dimensions',
					'label'        => __( 'Margin', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $c['instance'] ) => 'margin: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
					],
				]
			);

			$this->controls_manager->end_section();

			// Container - Unfold state
			$this->controls_manager->start_section(
				'style_controls',
				[
					'id'    => 'section_container_unfold',
					'title' => __( 'Container (Unfolded)', 'jet-tricks' ),
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'container_unfold_bg',
					'type'         => 'color-picker',
					'label'        => __( 'Background Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['instance'] . '.jet-unfold-state' ) => 'background-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'container_unfold_border',
					'type'         => 'border',
					'label'        => __( 'Border', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $c['instance'] . '.jet-unfold-state' ) => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}};',
					],
				]
			);

			$this->controls_manager->end_section();

			// Separator
			$this->controls_manager->start_section(
				'style_controls',
				[
					'id'        => 'section_separator',
					'title'     => __( 'Separator', 'jet-tricks' ),
					'condition' => [
						'separatorType' => 'div',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'separator_bg',
					'type'         => 'color-picker',
					'label'        => __( 'Background Color', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $c['separator'] ) => 'background-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->end_section();

			// Button
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
					'id'           => 'button_spacing',
					'type'         => 'range',
					'label'        => __( 'Spacing', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [
						[ 'value' => 'px', 'intervals' => [ 'step' => 1, 'min' => 0, 'max' => 200 ] ],
						[ 'value' => 'em', 'intervals' => [ 'step' => 0.1, 'min' => 0, 'max' => 20 ] ],
					],
					'attributes'   => [
						'default' => [
							'value' => [
								'value' => 30,
								'unit'  => 'px',
							],
						],
					],
					'css_selector' => [
						$this->css_selector( $c['trigger'] ) => 'margin-top: {{VALUE}}{{UNIT}};',
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
						'flex-start' => [ 'shortcut' => __( 'Start', 'jet-tricks' ), 'icon' => 'dashicons-editor-alignleft' ],
						'center'     => [ 'shortcut' => __( 'Center', 'jet-tricks' ), 'icon' => 'dashicons-editor-aligncenter' ],
						'flex-end'   => [ 'shortcut' => __( 'End', 'jet-tricks' ), 'icon' => 'dashicons-editor-alignright' ],
						'stretch'    => [ 'shortcut' => __( 'Justified', 'jet-tricks' ), 'icon' => 'dashicons-editor-justify' ],
					],
					'css_selector' => [
						$this->css_selector( $c['button'] ) => 'align-self: {{VALUE}};',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'button_icon_position',
					'type'         => 'choose',
					'label'        => __( 'Icon Position', 'jet-tricks' ),
					'separator'    => 'after',
					'options'      => [
						'row'            => [ 'shortcut' => __( 'Start', 'jet-tricks' ), 'icon' => 'dashicons-arrow-left-alt' ],
						'row-reverse'    => [ 'shortcut' => __( 'End', 'jet-tricks' ), 'icon' => 'dashicons-arrow-right-alt' ],
						'column'         => [ 'shortcut' => __( 'Top', 'jet-tricks' ), 'icon' => 'dashicons-arrow-up-alt' ],
						'column-reverse' => [ 'shortcut' => __( 'Bottom', 'jet-tricks' ), 'icon' => 'dashicons-arrow-down-alt' ],
					],
					'css_selector' => [
						$this->css_selector( $c['button'] ) => 'flex-direction: {{VALUE}};',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'button_typography',
					'type'         => 'typography',
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['button'] ) => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'button_icon_size',
					'type'         => 'range',
					'label'        => __( 'Icon Size', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [
						[ 'value' => 'px', 'intervals' => [ 'step' => 1, 'min' => 0, 'max' => 100 ] ],
					],
					'css_selector' => [
						$this->css_selector( $c['button'] . ' .jet-unfold__button-icon' ) => 'font-size: {{VALUE}}{{UNIT}};',
						$this->css_selector( $c['button'] . ' .jet-unfold__button-icon img' ) => 'width: {{VALUE}}{{UNIT}}; height: {{VALUE}}{{UNIT}};',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'button_icon_gap',
					'type'         => 'range',
					'label'        => __( 'Icon Gap', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [
						[ 'value' => 'px', 'intervals' => [ 'step' => 1, 'min' => 0, 'max' => 50 ] ],
					],
					'css_selector' => [
						$this->css_selector( $c['button'] ) => 'gap: {{VALUE}}{{UNIT}};',
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
						$this->css_selector( $c['button'] ) => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
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
						$this->css_selector( $c['button'] ) => 'margin: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
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
						$this->css_selector( $c['button'] ) => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}};',
					],
				]
			);

			$this->controls_manager->start_tabs( 'style_controls', [ 'id' => 'button_style_tabs' ] );

			$this->controls_manager->start_tab( 'style_controls', [ 'id' => 'button_normal_tab', 'title' => __( 'Normal', 'jet-tricks' ) ] );

			$this->controls_manager->add_control(
				[
					'id'           => 'button_color',
					'type'         => 'color-picker',
					'label'        => __( 'Text Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['button'] ) => 'color: {{VALUE}}',
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
						$this->css_selector( $c['button'] ) => 'background-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'button_icon_color',
					'type'         => 'color-picker',
					'label'        => __( 'Icon Color', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $c['button'] . ' .jet-unfold__button-icon' ) => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->end_tab();

			$this->controls_manager->start_tab( 'style_controls', [ 'id' => 'button_hover_tab', 'title' => __( 'Hover', 'jet-tricks' ) ] );

			$this->controls_manager->add_control(
				[
					'id'           => 'button_hover_color',
					'type'         => 'color-picker',
					'label'        => __( 'Text Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['button'] ) . ':hover' => 'color: {{VALUE}}',
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
						$this->css_selector( $c['button'] ) . ':hover' => 'background-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'button_hover_icon_color',
					'type'         => 'color-picker',
					'label'        => __( 'Icon Color', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $c['button'] ) . ':hover .jet-unfold__button-icon' => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->end_tab();

			$this->controls_manager->end_tabs();

			$this->controls_manager->end_section();
		}

		public function render_callback( $attributes = [], $content = '' ) {

			$this->enqueue_frontend_assets();

			$fold               = ! empty( $attributes['fold'] ) ? filter_var( $attributes['fold'], FILTER_VALIDATE_BOOLEAN ) : false;
			$mask_height        = ! empty( $attributes['maskHeight'] ) ? $attributes['maskHeight'] : [ 'size' => 50, 'unit' => 'px' ];
			$height_control     = ! empty( $attributes['heightControlType'] ) ? $attributes['heightControlType'] : 'height';
			$word_count         = ! empty( $attributes['wordCount'] ) ? (int) $attributes['wordCount'] : 20;
			$separator_type     = ! empty( $attributes['separatorType'] ) ? $attributes['separatorType'] : 'div';
			$separator_height   = ! empty( $attributes['separatorHeight'] ) ? $attributes['separatorHeight'] : [ 'size' => 30, 'unit' => 'px' ];
			$separator_z_index  = isset( $attributes['separatorZIndex'] ) ? (int) $attributes['separatorZIndex'] : 0;
			$unfold_text        = isset( $attributes['unfoldText'] ) ? $attributes['unfoldText'] : __( 'Show', 'jet-tricks' );
			$fold_text          = isset( $attributes['foldText'] ) ? $attributes['foldText'] : __( 'Hide', 'jet-tricks' );
			$unfold_icon        = $this->get_icon_html( ! empty( $attributes['unfoldIcon'] ) ? $attributes['unfoldIcon'] : [] );
			$fold_icon          = $this->get_icon_html( ! empty( $attributes['foldIcon'] ) ? $attributes['foldIcon'] : [] );
			$unfold_duration    = ! empty( $attributes['unfoldDuration'] ) ? $attributes['unfoldDuration'] : [ 'size' => 500, 'unit' => 'ms' ];
			$fold_duration      = ! empty( $attributes['foldDuration'] ) ? $attributes['foldDuration'] : [ 'size' => 300, 'unit' => 'ms' ];
			$unfold_easing      = ! empty( $attributes['unfoldEasing'] ) ? $attributes['unfoldEasing'] : 'easeOutBack';
			$fold_easing        = ! empty( $attributes['foldEasing'] ) ? $attributes['foldEasing'] : 'easeOutSine';
			$fold_scroll        = ! empty( $attributes['foldScroll'] ) ? filter_var( $attributes['foldScroll'], FILTER_VALIDATE_BOOLEAN ) : false;
			$fold_scroll_offset  = ! empty( $attributes['foldScrollOffset'] ) ? $attributes['foldScrollOffset'] : [ 'size' => 0, 'unit' => 'px' ];
			$autohide           = ! empty( $attributes['autohide'] ) ? filter_var( $attributes['autohide'], FILTER_VALIDATE_BOOLEAN ) : false;
			$autohide_time      = ! empty( $attributes['autohideTime'] ) ? (int) $attributes['autohideTime'] : 5;
			$hide_outside_click = ! empty( $attributes['hideOutsideClick'] ) ? filter_var( $attributes['hideOutsideClick'], FILTER_VALIDATE_BOOLEAN ) : false;
			$class_name         = ! empty( $attributes['className'] ) ? ' ' . esc_attr( $attributes['className'] ) : '';

			$mask_size = $mask_height['size'] ?? 50;
			$mask_unit = $mask_height['unit'] ?? 'px';

			$json_settings = [
				'height'            => [ 'size' => $mask_size, 'unit' => $mask_unit ],
				'mask_height'       => [ 'size' => $mask_size, 'unit' => $mask_unit ],
				'heightControlType' => $height_control,
				'wordCount'         => $word_count,
				'unfoldDuration'    => $unfold_duration,
				'foldDuration'      => $fold_duration,
				'unfoldEasing'      => $unfold_easing,
				'foldEasing'        => $fold_easing,
				'foldScrolling'     => $fold_scroll ? 'true' : '',
				'foldScrollOffset'  => $fold_scroll_offset,
				'hideOutsideClick'  => $hide_outside_click ? 'true' : '',
				'autoHide'          => $autohide ? 'true' : '',
				'autoHideTime'      => $autohide ? [ 'size' => $autohide_time, 'unit' => 'px' ] : '',
				'separatorType'     => $separator_type,
				'separatorHeight'   => $separator_height,
				'unfoldText'        => $unfold_text,
				'foldText'          => $fold_text,
				'unfoldIcon'        => $unfold_icon,
				'foldIcon'          => $fold_icon,
			];

			$instance_classes = [ 'jet-unfold' ];
			if ( $fold ) {
				$instance_classes[] = 'jet-unfold-state';
			}

			$mask_classes = [ 'jet-unfold__mask' ];
			if ( 'gradient' === $separator_type ) {
				$mask_classes[] = 'jet-unfold__mask-gradient';
			}

			$mask_style = '';
			if ( ! $fold && ! empty( $mask_height['size'] ) && ! empty( $mask_height['unit'] ) && 'height' === $height_control ) {
				$mask_style = sprintf( 'height:%s%s;', esc_attr( $mask_height['size'] ), esc_attr( $mask_height['unit'] ) );
			}
			if ( 'gradient' === $separator_type && ! $fold && ! empty( $separator_height['size'] ) && ! empty( $separator_height['unit'] ) ) {
				$mask_style .= sprintf(
					'--jet-unfold-mask-height:%s%s;',
					esc_attr( $separator_height['size'] ),
					esc_attr( $separator_height['unit'] )
				);
			}

			$separator_style = '';
			if ( 'div' === $separator_type && $separator_z_index > 0 ) {
				$separator_style = sprintf( 'z-index:%d;', $separator_z_index );
			}
			if ( 'div' === $separator_type && ! empty( $separator_height['size'] ) && ! empty( $separator_height['unit'] ) ) {
				$separator_style .= sprintf( 'height:%s%s;', esc_attr( $separator_height['size'] ), esc_attr( $separator_height['unit'] ) );
			}

			$button_icon_html = sprintf( '<span class="jet-unfold__button-icon jet-tricks-icon">%s</span>', $fold ? $fold_icon : $unfold_icon );
			$button_text      = $fold ? $fold_text : $unfold_text;
			$button_text_html = sprintf( '<span class="jet-unfold__button-text">%s</span>', esc_html( $button_text ) );

			$content_inner = $content;
			if ( empty( $content_inner ) && ! empty( $attributes['innerContent'] ) ) {
				$content_inner = do_blocks( $attributes['innerContent'] );
			}
			
			if ( empty( trim( $content_inner ) ) ) {
				$content_inner = '<p></p>';
			}

			$instance_attrs = [
				'class'         => $instance_classes,
				'data-settings' => wp_json_encode( $json_settings ),
			];

			$mask_attrs = [
				'class' => $mask_classes,
			];
			if ( ! empty( $mask_style ) ) {
				$mask_attrs['style'] = $mask_style;
			}

			ob_start();
			?>
			<div>
				<div class="jet-tricks-unfold<?php echo $class_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" data-is-block="jet-tricks/unfold">
				<div <?php echo $this->render_attributes( $instance_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<div class="jet-unfold__inner">
						<div <?php echo $this->render_attributes( $mask_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<div class="jet-unfold__content">
								<div class="jet-unfold__content-inner"><?php echo $content_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							</div>
							<?php if ( 'div' === $separator_type ) : ?>
								<div class="jet-unfold__separator" style="<?php echo esc_attr( $separator_style ); ?>"></div>
							<?php endif; ?>
						</div>
						<div class="jet-unfold__trigger">
							<div class="jet-unfold__button" href="#" tabindex="0" role="button"><?php echo $button_icon_html . $button_text_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						</div>
					</div>
				</div>
			</div>
			</div>
			<?php
			return ob_get_clean();
		}

		private function render_attributes( $attrs ) {
			$parts = [];
			foreach ( $attrs as $key => $value ) {
				if ( $key === 'class' && is_array( $value ) ) {
					$value = implode( ' ', $value );
				}
				if ( is_scalar( $value ) ) {
					$parts[] = esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
				}
			}
			return implode( ' ', $parts );
		}

		private function get_icon_html( $icon ) {
			if ( empty( $icon ) ) {
				return '';
			}
			if ( is_array( $icon ) && ! empty( $icon['url'] ) ) {
				$url = $icon['url'];
				$attachment_id = ! empty( $icon['id'] ) ? (int) $icon['id'] : ( preg_match( '/\.svg$/i', $url ) ? attachment_url_to_postid( $url ) : 0 );
				$svg = $attachment_id ? $this->get_inline_svg( $attachment_id ) : '';
				if ( $svg ) {
					return $svg;
				}
				return sprintf( '<img src="%s" alt="" style="width: 1em; height: 1em;" />', esc_url( $url ) );
			}
			if ( is_array( $icon ) && ! empty( $icon['value'] ) ) {
				return sprintf( '<i class="%s"></i>', esc_attr( $icon['value'] ) );
			}
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
