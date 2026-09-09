<?php
namespace Jet_Engine_Dynamic_Charts\Filters;

use Jet_Engine_Dynamic_Charts\Chart;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define filters provider class
 */
class Provider extends \Jet_Smart_Filters_Provider_Base {

	/**
	 * Watch for default query
	 */
	public function __construct() {

		if ( ! jet_smart_filters()->query->is_ajax_filter() ) {
			add_filter( 'jet-engine/data-charts/before-render', array( $this, 'store_default_query' ) );
		}

	}

	/**
	 * Store default query args
	 *
	 * @param  \Jet_Engine_Dynamic_Charts\Render\Chart_Renderer $renderer [description]
	 */
	public function store_default_query( $renderer ) {

		$settings = $renderer->get_settings();

		if ( empty( $settings['_element_id'] ) ) {
			$query_id = false;
		} else {
			$query_id = $settings['_element_id'];
		}

		$chart_object = $renderer->get_chart_object();

		if ( ! $chart_object ) {
			return;
		}

		$query = $chart_object->get_query();

		if ( ! $query ) {
			return;
		}

		jet_smart_filters()->query->set_props(
			$this->get_id(),
			apply_filters( 'jet-engine/query-builder/set-props', array(
				'found_posts'   => $query->get_items_total_count(),
				'max_num_pages' => $query->get_items_pages_count(),
				'page'          => $query->get_current_items_page(),
				'query_type'    => $query->get_query_type(),
				'query_id'      => $query->id,
				'query_meta'    => $query->get_query_meta(),
			), $this->get_id(), $query_id, $query ),
			$query_id
		);

		$provider_settings = $renderer->get_required_settings();

		jet_smart_filters()->query->store_provider_default_query(
			$this->get_id(),
			$query->get_query_args(),
			$query_id
		);

		jet_smart_filters()->providers->store_provider_settings(
			$this->get_id(),
			$provider_settings,
			$query_id
		);

	}

	/**
	 * Get provider name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'JetEngine Dynamic Chart', 'jet-engine' );
	}

	/**
	 * Get provider ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'jet-data-chart';
	}

	/**
	 * Get filtered provider content
	 *
	 * @return string
	 */
	public function ajax_get_content() {
		add_filter( 'jet-smart-filters/render/ajax/data', array( $this, 'add_queried_data_to_response' ) );
	}

	public function add_queried_data_to_response( $response ) {

		$settings = isset( $_REQUEST['settings'] ) ? $_REQUEST['settings'] : array();

		if ( empty( $settings['chart_id'] ) ) {
			return $response;
		}

		$chart = new Chart();
		$chart->setup_chart_by_id( absint( $settings['chart_id'] ) );

		$response['chartData'] = $chart->get_data();
		$response['content']   = false;

		return $response;

	}

	public function is_data() {
		return true;
	}

	/**
	 * Get provider wrapper selector
	 *
	 * @return string
	 */
	public function get_wrapper_selector() {
		return '.jet-engine-chart';
	}

	/**
	 * If added unique ID this paramter will determine - search selector inside this ID, or is the same element
	 */
	public function in_depth() {
		return true;
	}

	public function apply_filters_in_request() {}

}
