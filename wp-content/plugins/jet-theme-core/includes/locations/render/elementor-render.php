<?php
namespace Jet_Theme_Core\Locations\Render;

use Jet_Theme_Core\Locations\Render\Base_Render as Base_Render;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Elementor_Location_Render extends Base_Render {

	/**
	 * [$name description]
	 * @var string
	 */
	protected $name = 'elementor-location-render';

	/**
	 * [init description]
	 * @return [type] [description]
	 */
	public function init() {}

	/**
	 * [get_name description]
	 * @return [type] [description]
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * [render description]
	 * @return [type] [description]
	 */
	public function render() {

		if ( ! \Jet_Theme_Core\Utils::has_elementor() ) {
			return false;
		}

		$location = $this->get('location');

		$is_prevent_pro_locations = jet_theme_core()->settings->get( 'prevent_pro_locations', 'false' );

		if ( filter_var( $is_prevent_pro_locations, FILTER_VALIDATE_BOOLEAN ) ) {
			return $this->render_jet_template( $location );
		}

		$relations = jet_theme_core()->settings->get( 'pro_relations', 'show_both' );

		$jet_render_status = false;
		$pro_render_status = false;

		switch ( $relations ) {
			case 'jet_override':
				$jet_render_status = $this->render_jet_template( $location );

				break;
			case 'pro_override':
				$pro_render_status = $this->render_elementor_pro_template( $location );

				break;
			case 'show_both':
				$jet_render_status = $this->render_jet_template( $location );
				$pro_render_status = $this->render_elementor_pro_template( $location );

				break;
			case 'show_both_reverse':
				$jet_render_status = $this->render_elementor_pro_template( $location );
				$pro_render_status = $this->render_jet_template( $location );

				break;
		}

		return ( $jet_render_status || $pro_render_status ) ? true : false;
    }

	/**
	 * @param $location
	 * @return bool
	 */
	public function render_jet_template( $location = false ) {

		if ( ! $location ) {
			return false;
		}

		$template_id = $this->get( 'template_id' );

		$template_id = apply_filters( 'jet-theme-core/location/render/template-id', $template_id );

		if ( ! $template_id ) {
			return false;
		}

		$structure  = jet_theme_core()->locations->get_structure_for_location( $location );
		$content    =  \Elementor\Plugin::instance()->frontend->get_builder_content( $template_id, false );

		if ( $structure ) {
			jet_theme_core()->admin_bar->register_post_item( $template_id, array(
				'sub_title' => $structure->get_single_label(),
				'priority'  => $structure->get_admin_bar_priority(),
			) );
		}

		// Elementor preview is a read-only frontend rendering mode.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$elementor_preview = isset( $_GET['elementor-preview'] ) ? absint( $_GET['elementor-preview'] ) : 0;

		if ( ! $elementor_preview ) {
			// Elementor returns trusted builder markup that must remain unescaped.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $content;
		} else {
			$back_button = '<div class="jet-template-edit-container__back"><span class="dashicons dashicons-arrow-left-alt"></span><span>' . esc_html__( 'Back', 'jet-theme-core' ) . '</span></div>';

			// Elementor content and the static back button are trusted builder markup.
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			printf(
				'<div class="jet-template-edit-container" data-template-id="%1$s" data-location="%2$s">%3$s%4$s</div>',
				esc_attr( absint( $template_id ) ),
				esc_attr( sanitize_html_class( $location ) ),
				$content,
				$back_button
			);
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return true;
	}

	/**
	 * @param false $location
	 *
	 * @return bool
	 */
	public function render_elementor_pro_template( $location = false ) {

		if ( ! \Jet_Theme_Core\Utils::has_elementor_pro() || ! function_exists( 'elementor_theme_do_location' ) || ! $location ) {
			return false;
		}

		return elementor_theme_do_location( $location );
	}
}
