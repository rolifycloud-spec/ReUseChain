<?php
namespace Jet_Engine_Dynamic_Charts\Render;

use Jet_Engine_Dynamic_Charts\Chart;

class Preview {

	public function __construct() {
		add_action( 'wp_ajax_jet_engine_data_chart_preview', array( $this, 'render_preview' ) );
	}

	public function render_preview() {

		$columns  = ! empty( $_POST['meta_fields'] ) ? $_POST['meta_fields'] : array();
		$settings = ! empty( $_POST['general_settings'] ) ? $_POST['general_settings'] : array();

		$chart = new Chart( $columns, $settings );

		wp_send_json_success( array(
			'items'   => $chart->get_data(),
			'options' => $chart->get_prepared_options()
		) );

	}

}
