<?php
/**
 * JetTricks Block Type: Hotspots.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Views_Type_Hotspots' ) ) {

	class Jet_Tricks_Blocks_Views_Type_Hotspots extends Jet_Tricks_Blocks_Views_Type_Base {

		public function get_name() {
			return 'hotspots';
		}

		public function get_css_scheme() {
			return [
				'instance'   => '.jet-hotspots',
				'inner'      => '.jet-hotspots__inner',
				'item'       => '.jet-hotspots__item',
				'item_inner' => '.jet-hotspots__item-inner',
				'tooltip'    => '.tippy-box',
				'image'      => '.jet-hotspots__inner > img',
			];
		}

		public function enqueue_frontend_assets() {
			parent::enqueue_frontend_assets();

			if ( ! wp_script_is( 'imagesloaded', 'enqueued' ) ) {
				wp_enqueue_script( 'imagesloaded' );
			}
			Jet_Tricks_Assets::ensure_tippy_registered();
			wp_enqueue_script( 'jet-tricks-tippy-bundle' );
		}

		public function add_style_manager_options() {
			$c = $this->css_scheme;

			// Hotspot section
			$this->controls_manager->start_section(
				'style_controls',
				[
					'id'           => 'section_hotspot_style',
					'title'        => __( 'Hotspot', 'jet-tricks' ),
					'initial_open' => true,
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'hotspot_icon_size',
					'type'         => 'range',
					'label'        => __( 'Icon Size', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['item'] . ' ' . $c['item_inner'] . ' .jet-hotspots__item-icon' ) => 'font-size: {{VALUE}}{{UNIT}};',
					],
					'units'        => [
						[ 'value' => 'px', 'intervals' => [ 'step' => 1, 'min' => 8, 'max' => 50 ] ],
						[ 'value' => 'em', 'intervals' => [ 'step' => 0.1, 'min' => 0.5, 'max' => 3 ] ],
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'hotspot_typography',
					'type'         => 'typography',
					'label'        => __( 'Typography', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['item'] . ' ' . $c['item_inner'] . ' .jet-hotspots__item-text' ) => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
					],
				]
			);

			$this->controls_manager->start_tabs( 'style_controls', [ 'id' => 'tabs_hotspot_style' ] );

			$this->controls_manager->start_tab( 'style_controls', [ 'id' => 'tab_hotspot_normal', 'title' => __( 'Normal', 'jet-tricks' ) ] );

			$this->controls_manager->add_control(
				[
					'id'           => 'hotspot_icon_color',
					'type'         => 'color-picker',
					'label'        => __( 'Icon Color', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $c['item'] . ' ' . $c['item_inner'] . ' .jet-hotspots__item-icon' ) => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'hotspot_text_color',
					'type'         => 'color-picker',
					'label'        => __( 'Text Color', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $c['item'] . ' ' . $c['item_inner'] . ' .jet-hotspots__item-text' ) => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'hotspot_background',
					'type'         => 'color-picker',
					'label'        => __( 'Background Color', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $c['item_inner'] ) => 'background-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->end_tab();

			$this->controls_manager->start_tab( 'style_controls', [ 'id' => 'tab_hotspot_hover', 'title' => __( 'Hover', 'jet-tricks' ) ] );

			$this->controls_manager->add_control(
				[
					'id'           => 'hotspot_icon_color_hover',
					'type'         => 'color-picker',
					'label'        => __( 'Icon Color', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $c['item'] . ':hover ' . $c['item_inner'] . ' .jet-hotspots__item-icon' ) => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'hotspot_text_color_hover',
					'type'         => 'color-picker',
					'label'        => __( 'Text Color', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $c['item'] . ':hover ' . $c['item_inner'] . ' .jet-hotspots__item-text' ) => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'hotspot_background_hover',
					'type'         => 'color-picker',
					'label'        => __( 'Background Color', 'jet-tricks' ),
					'css_selector' => [
						$this->css_selector( $c['item'] . ':hover ' . $c['item_inner'] ) => 'background-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->end_tab();

			$this->controls_manager->end_tabs();

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'hotspot_padding',
					'type'         => 'dimensions',
					'label'        => __( 'Padding', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [ 'px', '%' ],
					'css_selector' => [
						$this->css_selector( $c['item_inner'] ) => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'hotspot_border',
					'type'         => 'border',
					'label'        => __( 'Border', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['item_inner'] ) => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}};',
					],
				]
			);

			$this->controls_manager->end_section();

			// Tooltip section
			$this->controls_manager->start_section(
				'style_controls',
				[
					'id'    => 'section_tooltip_style',
					'title' => __( 'Tooltip', 'jet-tricks' ),
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'tooltip_color',
					'type'         => 'color-picker',
					'label'        => __( 'Text Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['instance'] . ' ' . $c['tooltip'] . ' .tippy-content' ) => 'color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'tooltip_typography',
					'type'         => 'typography',
					'label'        => __( 'Typography', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['instance'] . ' ' . $c['tooltip'] . ' .tippy-content' ) => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
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
						$this->css_selector( $c['instance'] . ' ' . $c['tooltip'] . ' .tippy-content' ) => 'text-align: {{VALUE}};',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'tooltip_arrow_color',
					'type'         => 'color-picker',
					'label'        => __( 'Arrow Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['instance'] . ' .tippy-box[data-placement*=left] .tippy-arrow:before' )   => 'border-left-color: {{VALUE}}',
						$this->css_selector( $c['instance'] . ' .tippy-box[data-placement*=right] .tippy-arrow:before' )  => 'border-right-color: {{VALUE}}',
						$this->css_selector( $c['instance'] . ' .tippy-box[data-placement*=top] .tippy-arrow:before' )    => 'border-top-color: {{VALUE}}',
						$this->css_selector( $c['instance'] . ' .tippy-box[data-placement*=bottom] .tippy-arrow:before' ) => 'border-bottom-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'tooltip_width',
					'type'         => 'range',
					'label'        => __( 'Width', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [
						[ 'value' => 'px', 'intervals' => [ 'step' => 1, 'min' => 50, 'max' => 1000 ] ],
						[ 'value' => 'em', 'intervals' => [ 'step' => 0.1, 'min' => 1, 'max' => 50 ] ],
					],
					'css_selector' => [
						$this->css_selector( $c['instance'] . ' ' . $c['tooltip'] ) => 'width: {{VALUE}}{{UNIT}};',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'tooltip_background',
					'type'         => 'color-picker',
					'label'        => __( 'Background Color', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['instance'] . ' ' . $c['tooltip'] ) => 'background-color: {{VALUE}}',
					],
				]
			);

			$this->controls_manager->add_responsive_control(
				[
					'id'           => 'tooltip_padding',
					'type'         => 'dimensions',
					'label'        => __( 'Padding', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [ 'px', '%' ],
					'css_selector' => [
						$this->css_selector( $c['instance'] . ' ' . $c['tooltip'] . ' .tippy-content' ) => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
					],
				]
			);

			$this->controls_manager->add_control(
				[
					'id'           => 'tooltip_border',
					'type'         => 'border',
					'label'        => __( 'Border', 'jet-tricks' ),
					'separator'    => 'after',
					'css_selector' => [
						$this->css_selector( $c['instance'] . ' ' . $c['tooltip'] ) => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}};',
					],
				]
			);

			$this->controls_manager->end_section();
		}

		public function render_callback( $attributes = [] ) {
			$this->enqueue_frontend_assets();

			$image     = ! empty( $attributes['image'] ) ? $attributes['image'] : [];
			$hotspots  = ! empty( $attributes['hotspots'] ) ? $attributes['hotspots'] : [];
			$image_size = ! empty( $attributes['imageSize'] ) ? $attributes['imageSize'] : 'full';

			if ( empty( $image['id'] ) && empty( $image['url'] ) ) {
				return sprintf(
					'<div class="jet-tricks-hotspots" data-is-block="jet-tricks/hotspots"><h3>%s</h3></div>',
					esc_html__( 'Image not defined', 'jet-tricks' )
				);
			}

			$image_html = '';
			$image_id   = ! empty( $image['id'] ) ? (int) $image['id'] : 0;
			if ( $image_id ) {
				$image_html = wp_get_attachment_image( $image_id, $image_size, false, [ 'alt' => '' ] );
			}
			if ( empty( $image_html ) && ! empty( $image['url'] ) ) {
				$image_html = sprintf( '<img src="%s" alt="" />', esc_url( $image['url'] ) );
			}
			if ( empty( $image_html ) ) {
				return sprintf(
					'<div class="jet-tricks-hotspots" data-is-block="jet-tricks/hotspots"><h3>%s</h3></div>',
					esc_html__( 'Image not defined', 'jet-tricks' )
				);
			}

			$json_settings = [
				'tooltipPlacement'    => ! empty( $attributes['tooltipPlacement'] ) ? $attributes['tooltipPlacement'] : 'top',
				'tooltipArrow'        => ! empty( $attributes['tooltipArrow'] ),
				'tooltipTrigger'      => ! empty( $attributes['tooltipTrigger'] ) ? $attributes['tooltipTrigger'] : 'mouseenter',
				'tooltipShowOnInit'   => ! empty( $attributes['tooltipShowOnInit'] ),
				'tooltipShowDuration' => ! empty( $attributes['tooltipShowDuration'] ) && isset( $attributes['tooltipShowDuration']['size'] )
					? $attributes['tooltipShowDuration']
					: [ 'size' => 500, 'unit' => 'ms' ],
				'tooltipHideDuration' => ! empty( $attributes['tooltipHideDuration'] ) && isset( $attributes['tooltipHideDuration']['size'] )
					? $attributes['tooltipHideDuration']
					: [ 'size' => 300, 'unit' => 'ms' ],
				'tooltipDelay'        => ! empty( $attributes['tooltipDelay'] ) ? $attributes['tooltipDelay'] : [ 'size' => 0, 'unit' => 'ms' ],
				'tooltipDistance'     => ! empty( $attributes['tooltipDistance'] ) && isset( $attributes['tooltipDistance']['size'] )
					? $attributes['tooltipDistance']
					: [ 'size' => 15, 'unit' => 'px' ],
				'tooltipAnimation'    => ! empty( $attributes['tooltipAnimation'] ) ? $attributes['tooltipAnimation'] : 'fade',
				'tooltipInteractive'  => ! empty( $attributes['tooltipInteractive'] ),
			];

			$hotspots_animation_raw = ! empty( $attributes['hotspotsAnimation'] ) ? $attributes['hotspotsAnimation'] : 'pulse';
			$hotspots_animation_allowed = [ 'none', 'flash', 'pulse', 'shake', 'tada', 'rubber', 'swing' ];
			$hotspots_animation         = in_array( $hotspots_animation_raw, $hotspots_animation_allowed, true ) ? $hotspots_animation_raw : 'pulse';

			$instance_attrs = [
				'class'         => [
					'jet-hotspots',
					'jet-hotspots__hotspots-' . $hotspots_animation . '-animation',
				],
				'data-settings' => wp_json_encode( $json_settings ),
			];

			$id_prefix = 'jet-hotspot-' . substr( wp_rand(), 0, 3 );

			ob_start();
			?>
			<div>
				<div class="jet-tricks-hotspots" data-is-block="jet-tricks/hotspots">
				<div <?php echo $this->render_attributes( $instance_attrs ); // phpcs:ignore ?>>
					<div class="jet-hotspots__inner">
						<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<div class="jet-hotspots__container">
							<?php
							foreach ( $hotspots as $index => $hotspot ) {
								$h_pos = isset( $hotspot['horizontalPosition'] ) ? $hotspot['horizontalPosition'] : 50;
								$v_pos = isset( $hotspot['verticalPosition'] ) ? $hotspot['verticalPosition'] : 50;
								if ( is_array( $h_pos ) && isset( $h_pos['size'] ) ) {
									$h_pos = $h_pos['size'];
								}
								if ( is_array( $v_pos ) && isset( $v_pos['size'] ) ) {
									$v_pos = $v_pos['size'];
								}

								$tooltip_content = wp_kses_post( ! empty( $hotspot['hotspotDescription'] ) ? $hotspot['hotspotDescription'] : '' );
								$show_on_init    = ! empty( $hotspot['hotspotShowOnInit'] ) ? 'yes' : 'no';

								$hotspot_url = ! empty( $hotspot['hotspotUrl'] ) ? $hotspot['hotspotUrl'] : [];
								$url_val     = isset( $hotspot_url['url'] ) ? $hotspot_url['url'] : '';
								$is_link     = ! empty( $url_val );

								$icon_html = $this->get_hotspot_icon_html( $hotspot );

								$text_html = '';
								if ( ! empty( $hotspot['hotspotText'] ) ) {
									$text_html = sprintf( '<span class="jet-hotspots__item-text">%1$s</span>', esc_html( $hotspot['hotspotText'] ) );
								}

								$item_attrs = [
									'id'                       => $id_prefix . ( $index + 1 ),
									'class'                    => [ 'jet-hotspots__item' ],
									'data-tippy-content'       => $tooltip_content,
									'data-horizontal-position' => $h_pos,
									'data-vertical-position'   => $v_pos,
									'data-show-on-init'        => $show_on_init,
									'style'                    => 'left:' . esc_attr( $h_pos ) . '%; top:' . esc_attr( $v_pos ) . '%;',
								];

								if ( $is_link ) {
									$item_attrs['href'] = esc_url( $url_val );
									if ( ! empty( $hotspot_url['opensInNewTab'] ) ) {
										$item_attrs['target'] = '_blank';
									}
								}

								$tag = $is_link ? 'a' : 'div';
								?>
								<<?php echo esc_html( $tag ); ?> <?php echo $this->render_attributes( $item_attrs ); // phpcs:ignore ?>>
									<div class="jet-hotspots__item-inner"><?php echo $icon_html . wp_kses_post( $text_html ); // phpcs:ignore ?></div>
								</<?php echo esc_html( $tag ); ?>>
								<?php
							}
							?>
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

		private function get_hotspot_icon_html( $hotspot ) {
			$icon = ! empty( $hotspot['hotspotIcon'] ) ? $hotspot['hotspotIcon'] : [];
			if ( empty( $icon ) ) {
				return '';
			}
			// Gutenberg format: { id, url } or { value: 'fa fa-class' }
			if ( is_array( $icon ) && ! empty( $icon['url'] ) ) {
				$url = $icon['url'];
				if ( preg_match( '/\.svg$/i', $url ) ) {
					$attachment_id = ! empty( $icon['id'] ) ? (int) $icon['id'] : attachment_url_to_postid( $url );
					$svg           = $this->get_inline_svg( $attachment_id );
					if ( $svg ) {
						return '<span class="jet-hotspots__item-icon jet-tricks-icon">' . $svg . '</span>';
					}
				}
				return sprintf(
					'<span class="jet-hotspots__item-icon jet-tricks-icon"><img src="%s" alt="" style="width:1em;height:1em" /></span>',
					esc_url( $url )
				);
			}
			if ( is_array( $icon ) && ! empty( $icon['value'] ) ) {
				return sprintf(
					'<span class="jet-hotspots__item-icon jet-tricks-icon"><i class="%s"></i></span>',
					esc_attr( $icon['value'] )
				);
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
