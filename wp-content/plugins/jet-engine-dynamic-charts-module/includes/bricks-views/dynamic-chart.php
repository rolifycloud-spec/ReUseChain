<?php

namespace Jet_Engine_Dynamic_Charts\Bricks_Views;

use Jet_Engine\Bricks_Views\Elements\Base;
use Jet_Engine_Dynamic_Charts\Plugin;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Dynamic_Chart extends Base {
	// Element properties
	public $category = 'jetengine'; // Use predefined element category 'general'
	public $name = 'jet-dynamic-chart'; // Make sure to prefix your elements
	public $icon = 'jet-engine-icon-dynamic-chart'; // Themify icon font class
	public $css_selector = '.jet-dynamic-chart'; // Default CSS selector
	public $scripts = [ 'jetEngineDynamicChartsBricks' ]; // Script(s) run when element is rendered on frontend or updated in builder

	public $jet_element_render = 'dynamic-chart';

	// Return localised element label
	public function get_label() {
		return esc_html__( 'Dynamic Chart', 'jet-engine' );
	}

	// Set builder control groups
	public function set_control_groups() {

		$this->register_jet_control_group(
			'section_general',
			[
				'title' => esc_html__( 'General', 'jet-engine' ),
				'tab'   => 'content',
			]
		);
	}

	// Set builder controls
	public function set_controls() {

		$this->start_jet_control_group( 'section_general' );

		$this->register_jet_control(
			'chart_id',
			[
				'tab'         => 'content',
				'label'       => esc_html__( 'Chart', 'jet-engine' ),
				'type'        => 'select',
				'options'     => Plugin::instance()->data->get_charts_for_options( 'elementor' ),
				'searchable'  => true,
				'description' => esc_html__( 'Select chart to show', 'jet-engine' ),
			]
		);

		$this->end_jet_control_group();

	}

	// Render element HTML
	public function render() {

		// STEP: Chart field is empty: Show placeholder text
		if ( ! $this->get_jet_settings( 'chart_id' ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Please select chart to show.', 'jet-engine' )
				]
			);
		}

		$render = $this->get_jet_render_instance();

		// STEP: Dynamic Chart renderer class not found: Show placeholder text
		if ( ! $render ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Dynamic Chart renderer class not found', 'jet-engine' )
				]
			);
		}

		echo "<div {$this->render_attributes( '_root' )}>";
		$render->render_content();
		echo "</div>";

	}
}