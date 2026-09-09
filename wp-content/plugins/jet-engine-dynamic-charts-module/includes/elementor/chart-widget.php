<?php
namespace Jet_Engine_Dynamic_Charts\Elementor;

use Jet_Engine_Dynamic_Charts\Plugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Chart_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'jet-dynamic-chart';
	}

	public function get_title() {
		return __( 'Dynamic Chart', 'jet-engine' );
	}

	public function get_icon() {
		return 'jet-engine-icon-dynamic-chart';
	}

	public function get_categories() {
		return array( 'jet-listing-elements' );
	}

	public function get_help_url() {
		return 'https://crocoblock.com/knowledge-base/article-category/jetbooking/';
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_general',
			array(
				'label' => __( 'Content', 'jet-engine' ),
			)
		);

		$this->add_control(
			'chart_id',
			array(
				'label'       => esc_html__( 'Chart', 'jet-engine' ),
				'description' => esc_html__( 'Select chart to show', 'jet-engine' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => Plugin::instance()->data->get_charts_for_options( 'elementor' ),
			)
		);

		$this->end_controls_section();

	}

	protected function render() {
		jet_engine()->listings->render_item( 'dynamic-chart', $this->get_settings() );
	}

}
