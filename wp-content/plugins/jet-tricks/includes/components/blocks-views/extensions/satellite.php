<?php
/**
 * JetTricks blocks views satellite extension.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Satellite_Extension' ) ) {

	/**
	 * Define Jet_Tricks_Blocks_Satellite_Extension class.
	 */
	class Jet_Tricks_Blocks_Satellite_Extension {

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

			return apply_filters( 'jet-tricks/blocks-satellite/supported-blocks', $blocks );
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

			if ( ! filter_var( $avaliable_extensions['widget_satellite'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
				return;
			}

			add_action( 'init', [ $this, 'register_satellite_styles' ], 20 );
		}

		/**
		 * Register satellite style controls in Blocks Style Manager.
		 *
		 * @return void
		 */
		public function register_satellite_styles() {
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
					'id'        => 'section_satellite_style',
					'title'     => __( 'Satellite', 'jet-tricks' ),
					'condition' => [
						'jetTricksSatellite' => true,
					],
				] );

				$text_span = '{{WRAPPER}} .jet-tricks-satellite .jet-tricks-satellite__text span';
				$text_wrap = '{{WRAPPER}} .jet-tricks-satellite .jet-tricks-satellite__text';
				$icon_inst = '{{WRAPPER}} .jet-tricks-satellite .jet-tricks-satellite__icon-instance';
				$icon_hov  = '{{WRAPPER}} .jet-tricks-satellite:hover .jet-tricks-satellite__icon-instance';
				$img_wrap  = '{{WRAPPER}} .jet-tricks-satellite .jet-tricks-satellite__image';

				$proxy->add_control( [
					'id'           => 'satellite_text_typography',
					'type'         => 'typography',
					'label'        => __( 'Typography', 'jet-tricks' ),
					'separator'    => 'after',
					'condition'    => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'text',
					],
					'css_selector' => [
						$text_wrap => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
					],
				] );

				$proxy->start_tabs( 'style_controls', [
					'id'        => 'satellite_text_color_tabs',
					'condition' => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'text',
					],
				] );

				$proxy->start_tab( 'style_controls', [
					'id'    => 'satellite_text_tab_normal',
					'title' => __( 'Normal', 'jet-tricks' ),
				] );

				$proxy->add_control( [
					'id'           => 'satellite_text_color',
					'type'         => 'color-picker',
					'label'        => __( 'Text Color', 'jet-tricks' ),
					'separator'    => 'after',
					'condition'    => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'text',
					],
					'css_selector' => [
						$text_span => 'color: {{VALUE}};',
					],
				] );

				$proxy->end_tab();

				$proxy->start_tab( 'style_controls', [
					'id'    => 'satellite_text_tab_hover',
					'title' => __( 'Hover', 'jet-tricks' ),
				] );

				$proxy->add_control( [
					'id'           => 'satellite_text_color_hover',
					'type'         => 'color-picker',
					'label'        => __( 'Text Color', 'jet-tricks' ),
					'separator'    => 'after',
					'condition'    => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'text',
					],
					'css_selector' => [
						'{{WRAPPER}} .jet-tricks-satellite:hover .jet-tricks-satellite__text span' => 'color: {{VALUE}};',
					],
				] );

				$proxy->end_tab();

				$proxy->end_tabs();

				$proxy->add_responsive_control( [
					'id'           => 'satellite_icon_inner_size',
					'type'         => 'range',
					'label'        => __( 'Icon Size', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [
						[ 'value' => 'px', 'intervals' => [ 'step' => 1, 'min' => 5, 'max' => 500 ] ],
						[ 'value' => 'em', 'intervals' => [ 'step' => 0.1, 'min' => 0.5, 'max' => 20 ] ],
					],
					'condition'    => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'icon',
					],
					'css_selector' => [
						$icon_inst             => 'font-size: {{VALUE}}{{UNIT}};',
						$icon_inst . ' svg'   => 'width: {{VALUE}}{{UNIT}}; height: {{VALUE}}{{UNIT}};',
						$icon_inst . ' img'  => 'width: {{VALUE}}{{UNIT}}; height: {{VALUE}}{{UNIT}};',
					],
				] );

				$proxy->add_responsive_control( [
					'id'           => 'satellite_icon_box_size',
					'type'         => 'range',
					'label'        => __( 'Box Size', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [
						[ 'value' => 'px', 'intervals' => [ 'step' => 1, 'min' => 5, 'max' => 500 ] ],
						[ 'value' => 'em', 'intervals' => [ 'step' => 0.1, 'min' => 0.5, 'max' => 20 ] ],
						[ 'value' => '%', 'intervals' => [ 'step' => 1, 'min' => 5, 'max' => 100 ] ],
					],
					'condition'    => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'icon',
					],
					'css_selector' => [
						$icon_inst => 'width: {{VALUE}}{{UNIT}}; min-width: {{VALUE}}{{UNIT}}; height: {{VALUE}}{{UNIT}}; min-height: {{VALUE}}{{UNIT}}; box-sizing: border-box;',
					],
				] );

				$proxy->add_control( [
					'id'           => 'satellite_icon_border',
					'type'         => 'border',
					'label'        => __( 'Border', 'jet-tricks' ),
					'separator'    => 'after',
					'condition'    => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'icon',
					],
					'css_selector' => [
						$icon_inst => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}};',
					],
				] );

				$proxy->start_tabs( 'style_controls', [
					'id'        => 'satellite_icon_style_tabs',
					'condition' => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'icon',
					],
				] );

				$proxy->start_tab( 'style_controls', [
					'id'    => 'satellite_icon_tab_normal',
					'title' => __( 'Normal', 'jet-tricks' ),
				] );

				$proxy->add_control( [
					'id'           => 'satellite_icon_color',
					'type'         => 'color-picker',
					'label'        => __( 'Icon Color', 'jet-tricks' ),
					'separator'    => 'after',
					'condition'    => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'icon',
					],
					'css_selector' => [
						$icon_inst             => 'color: {{VALUE}};',
						$icon_inst . ' svg'   => 'fill: {{VALUE}};',
						$icon_inst . ' svg *' => 'fill: {{VALUE}};',
					],
				] );

				$proxy->add_control( [
					'id'           => 'satellite_icon_background',
					'type'         => 'color-picker',
					'label'        => __( 'Background Color', 'jet-tricks' ),
					'separator'    => 'after',
					'condition'    => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'icon',
					],
					'css_selector' => [
						$icon_inst => 'background-color: {{VALUE}};',
					],
				] );

				$proxy->end_tab();

				$proxy->start_tab( 'style_controls', [
					'id'    => 'satellite_icon_tab_hover',
					'title' => __( 'Hover', 'jet-tricks' ),
				] );

				$proxy->add_control( [
					'id'           => 'satellite_icon_hover_color',
					'type'         => 'color-picker',
					'label'        => __( 'Icon Color', 'jet-tricks' ),
					'separator'    => 'after',
					'condition'    => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'icon',
					],
					'css_selector' => [
						$icon_hov             => 'color: {{VALUE}};',
						$icon_hov . ' svg'   => 'fill: {{VALUE}};',
						$icon_hov . ' svg *' => 'fill: {{VALUE}};',
					],
				] );

				$proxy->add_control( [
					'id'           => 'satellite_icon_hover_bg',
					'type'         => 'color-picker',
					'label'        => __( 'Background Color', 'jet-tricks' ),
					'separator'    => 'after',
					'condition'    => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'icon',
					],
					'css_selector' => [
						$icon_hov => 'background-color: {{VALUE}};',
					],
				] );

				$proxy->end_tab();

				$proxy->end_tabs();

				$proxy->add_responsive_control( [
					'id'           => 'satellite_image_width',
					'type'         => 'range',
					'label'        => __( 'Image Width', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [
						[ 'value' => 'px', 'intervals' => [ 'step' => 1, 'min' => 10, 'max' => 1000 ] ],
					],
					'condition'    => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'image',
					],
					'css_selector' => [
						$img_wrap => 'width: {{VALUE}}{{UNIT}};',
					],
				] );

				$proxy->add_responsive_control( [
					'id'           => 'satellite_image_height',
					'type'         => 'range',
					'label'        => __( 'Image Height', 'jet-tricks' ),
					'separator'    => 'after',
					'units'        => [
						[ 'value' => 'px', 'intervals' => [ 'step' => 1, 'min' => 10, 'max' => 1000 ] ],
					],
					'condition'    => [
						'jetTricksSatellite'     => true,
						'jetTricksSatelliteType' => 'image',
					],
					'css_selector' => [
						$img_wrap => 'height: {{VALUE}}{{UNIT}};',
					],
				] );

				$proxy->end_section();
			}
		}

		/**
		 * Build inner satellite markup (inside block wrapper).
		 *
		 * @param array $attrs Block attributes.
		 * @return string
		 */
		public function build_satellite_inner_html( $attrs, $wrapper_tag = 'div' ) {
			$allowed_positions = [
				'top-left', 'top-center', 'top-right',
				'middle-left', 'middle-center', 'middle-right',
				'bottom-left', 'bottom-center', 'bottom-right',
			];
			$type     = ! empty( $attrs['jetTricksSatelliteType'] ) ? $attrs['jetTricksSatelliteType'] : 'text';
			$position = ! empty( $attrs['jetTricksSatellitePosition'] ) ? $attrs['jetTricksSatellitePosition'] : 'top-center';
			if ( ! in_array( $position, $allowed_positions, true ) ) {
				$position = 'top-center';
			}
			$link     = isset( $attrs['jetTricksSatelliteLink'] ) && is_array( $attrs['jetTricksSatelliteLink'] ) ? $attrs['jetTricksSatelliteLink'] : [];

			$wrapper_tag  = ( 'span' === strtolower( $wrapper_tag ) ) ? 'span' : 'div';
			$instance_tag = ( 'span' === $wrapper_tag ) ? 'span' : 'div';
			$link_attrs   = $this->get_link_html_attributes( $link );
			$link_start   = $link_attrs ? '<a class="jet-tricks-satellite__link" ' . $link_attrs . '>' : '';
			$link_end     = $link_attrs ? '</a>' : '';
			$pos_classes  = 'jet-tricks-satellite jet-tricks-satellite--blocks jet-tricks-satellite--' . $position;

			$layout_style = $this->get_satellite_layout_style_attr( $attrs );

			switch ( $type ) {
				case 'text':
					$text = isset( $attrs['jetTricksSatelliteText'] ) ? trim( (string) $attrs['jetTricksSatelliteText'] ) : '';
					if ( $text === '' ) {
						return '';
					}
					return sprintf(
						'<%6$s class="%1$s"%5$s><%6$s class="jet-tricks-satellite__inner"><%6$s class="jet-tricks-satellite__text">%2$s<span>%3$s</span>%4$s</%6$s></%6$s></%6$s>',
						esc_attr( $pos_classes ),
						wp_kses_post( $link_start ),
						wp_kses_post( $text ),
						wp_kses_post( $link_end ),
						$layout_style,
						tag_escape( $wrapper_tag )
					);

				case 'icon':
					$icon = isset( $attrs['jetTricksSatelliteIcon'] ) && is_array( $attrs['jetTricksSatelliteIcon'] ) ? $attrs['jetTricksSatelliteIcon'] : [];
					$icon_html = $this->get_satellite_svg_icon_inner_html( $icon );
					if ( empty( $icon_html ) ) {
						return '';
					}
					return sprintf(
						'<%6$s class="%1$s"%5$s><%6$s class="jet-tricks-satellite__inner"><%6$s class="jet-tricks-satellite__icon">%2$s<%7$s class="jet-tricks-satellite__icon-instance jet-tricks-icon">%3$s</%7$s>%4$s</%6$s></%6$s></%6$s>',
						esc_attr( $pos_classes ),
						wp_kses_post( $link_start ),
						$icon_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						wp_kses_post( $link_end ),
						$layout_style,
						tag_escape( $wrapper_tag ),
						tag_escape( $instance_tag )
					);

				case 'image':
					$image = isset( $attrs['jetTricksSatelliteImage'] ) && is_array( $attrs['jetTricksSatelliteImage'] ) ? $attrs['jetTricksSatelliteImage'] : [];
					$url   = ! empty( $image['url'] ) ? $image['url'] : '';
					if ( $url === '' ) {
						return '';
					}
					$img_id = ! empty( $image['id'] ) ? (int) $image['id'] : 0;
					$alt    = $img_id ? (string) get_post_meta( $img_id, '_wp_attachment_image_alt', true ) : '';

					return sprintf(
						'<%7$s class="%1$s"%2$s><%7$s class="jet-tricks-satellite__inner"><%7$s class="jet-tricks-satellite__image">%3$s<img class="jet-tricks-satellite__image-instance" src="%4$s" alt="%5$s">%6$s</%7$s></%7$s></%7$s>',
						esc_attr( $pos_classes ),
						$layout_style,
						wp_kses_post( $link_start ),
						esc_url( $url ),
						esc_attr( $alt ),
						wp_kses_post( $link_end ),
						tag_escape( $wrapper_tag )
					);

				default:
					return '';
			}
		}

		/**
		 * Inline CSS variables for satellite position, offset, rotation and z-index.
		 *
		 * @param array $attrs Block attributes.
		 * @return string
		 */
		public function get_satellite_layout_style_attr( $attrs ) {
			$x = isset( $attrs['jetTricksSatelliteOffsetX'] ) ? (int) $attrs['jetTricksSatelliteOffsetX'] : 0;
			$x = max( -500, min( 500, $x ) );

			$y = isset( $attrs['jetTricksSatelliteOffsetY'] ) ? (int) $attrs['jetTricksSatelliteOffsetY'] : 0;
			$y = max( -500, min( 500, $y ) );

			$rot = isset( $attrs['jetTricksSatelliteRotate'] ) ? (float) $attrs['jetTricksSatelliteRotate'] : 0.0;
			$rot = max( -180, min( 180, $rot ) );

			$z_raw = $attrs['jetTricksSatelliteZIndex'] ?? null;
			if ( $z_raw === '' || null === $z_raw ) {
				$z = 2;
			} elseif ( is_numeric( $z_raw ) ) {
				$z = (int) $z_raw;
			} else {
				$z = 2;
			}
			$z = max( -10, min( 999, $z ) );

			$style_parts = array(
				sprintf( '--jet-satellite-offset-x:%dpx', $x ),
				sprintf( '--jet-satellite-offset-y:%dpx', $y ),
				sprintf( '--jet-satellite-rotate:%sdeg', (string) round( $rot, 2 ) ),
				sprintf( '--jet-satellite-z:%d', $z ),
			);

			return sprintf( ' style="%s"', esc_attr( implode( ';', $style_parts ) ) );
		}

		/**
		 * Icon markup for satellite (inline SVG or img).
		 *
		 * @param array $icon Icon data (id, url).
		 * @return string
		 */
		public function get_satellite_svg_icon_inner_html( $icon ) {
			if ( empty( $icon ) || ! is_array( $icon ) || empty( $icon['url'] ) ) {
				return '';
			}

			$url           = $icon['url'];
			$attachment_id = ! empty( $icon['id'] ) ? (int) $icon['id'] : ( preg_match( '/\.svg$/i', $url ) ? (int) attachment_url_to_postid( $url ) : 0 );
			$svg           = $attachment_id ? $this->get_satellite_inline_svg( $attachment_id ) : '';

			if ( $svg ) {
				return $svg;
			}

			return sprintf( '<img src="%s" alt="" />', esc_url( $url ) );
		}

		/**
		 * Read inline SVG from attachment file.
		 *
		 * @param int $attachment_id Attachment ID.
		 * @return string
		 */
		public function get_satellite_inline_svg( $attachment_id ) {
			if ( ! $attachment_id ) {
				return '';
			}

			$path = get_attached_file( $attachment_id );

			if ( ! $path || ! file_exists( $path ) ) {
				return '';
			}

			$svg = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			return ( ! empty( $svg ) && strpos( $svg, '<svg' ) !== false ) ? $svg : '';
		}

		/**
		 * Build anchor attributes from link control data.
		 *
		 * @param array $link Link data.
		 * @return string
		 */
		public function get_link_html_attributes( $link ) {
			$url = ! empty( $link['url'] ) ? $link['url'] : '';

			if ( $url === '' ) {
				return '';
			}

			$external = ! empty( $link['is_external'] ) || ! empty( $link['opensInNewTab'] );

			$rel_parts = [];
			if ( $external ) {
				$rel_parts[] = 'noopener';
				$rel_parts[] = 'noreferrer';
			}

			return sprintf(
				'href="%1$s"%2$s%3$s',
				esc_url( $url ),
				$external ? ' target="_blank"' : '',
				$rel_parts ? ' rel="' . esc_attr( implode( ' ', $rel_parts ) ) . '"' : ''
			);
		}

	}
}
