<?php
namespace Jet_Engine_Dynamic_Charts\Render;

use Jet_Engine_Dynamic_Charts\Chart;
use Jet_Engine_Dynamic_Charts\Plugin;

class Chart_Renderer extends \Jet_Engine_Render_Base {

	private $chart = null;

	public function get_name() {
		return 'jet-dynamic-chart';
	}

	public function default_settings() {
		return array(
			'chart_id' => null,
		);
	}

	public function setup_chart( $chart_id = null, $columns = array(), $settings = array() ) {

		if ( null !== $this->chart ) {
			return;
		}

		if ( $chart_id ) {

			$this->chart = new Chart();
			$this->chart->setup_chart_by_id( $chart_id );

		} else {
			$this->chart = new Chart( $columns, $settings );
		}

	}

	public function render() {

		do_action( 'jet-engine/data-charts/before-render', $this );

		$chart_id = $this->get( 'chart_id' );

		if ( ! $chart_id ) {
			return;
		}

		$this->setup_chart( $chart_id );
		$this->chart_body();

	}

	/**
	 * @return Chart
	 */
	public function get_chart_object() {
		return $this->chart;
	}

	public function encode_data( $data ) {
		return htmlentities( json_encode( $data ) );
	}

	public function chart_body() {

		$config = $this->encode_data( $this->chart->get_prepared_options() );
		$data   = $this->encode_data( $this->chart->get_data() );
		echo '<div class="jet-engine-chart" style="' . $this->chart->get_inline_styles() . '" data-config="' . $config . '" data-data="' . $data . '"></div>';
	}

}
