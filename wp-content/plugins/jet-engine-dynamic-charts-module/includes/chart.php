<?php
namespace Jet_Engine_Dynamic_Charts;

use Jet_Engine\Query_Builder\Manager as Query_Builder;

class Chart {

	private $columns    = array();
	private $settings   = array();
	/**
	 * @var \Jet_Engine\Query_Builder\Queries\Base_Query
	 */
	private $query      = null;

	private static $is_js_enqueued;

	public function __construct( $columns = array(), $settings = array() ) {

		if ( ! empty( $columns ) || ! empty( $settings ) ) {
			$this->setup_chart( $columns, $settings );
		}

		self::enqueue_charts_js();
	}

	public function get_settings( $key = '' ) {
		if ( ! empty( $key ) ) {
			return $this->settings[ $key ] ?? null;
		}

		return $this->settings;
	}

	public function setup_chart_by_id( $id ) {

		$chart_data = Plugin::instance()->data->get_item_for_edit( $id );
		$columns    = ! empty( $chart_data['meta_fields'] ) ? $chart_data['meta_fields'] : array();

		if ( isset( $chart_data['meta_fields'] ) ) {
			unset( $chart_data['meta_fields'] );
		}

		$settings = $chart_data;

		$this->setup_chart( $columns, $settings );

		jet_engine()->admin_bar->register_item( 'dynamic-chart-' . $id, array(
			'title'     => $settings['name'],
			'sub_title' => esc_html__( 'Chart', 'jet-engine' ),
			'href'      => admin_url( 'admin.php?page=jet-engine-charts&chart_action=edit&id=' . $id ),
		) );
	}

	public function setup_chart( $columns = array(), $settings = array() ) {

		if ( ! empty( $columns ) ) {
			foreach( $columns as $column ) {
				$this->columns[ $column['_id'] ] = $column;
			}
		}

		$this->settings = $settings;

		$query_id = ! empty( $this->settings['query_id'] ) ? $this->settings['query_id'] : false;
		$query_id = apply_filters( 'jet-engine/query-builder/listings/query-id', $query_id, null, $settings );

		if ( $query_id ) {
			$this->query = Query_Builder::instance()->get_query_by_id( $query_id );
		}

	}

	public static function enqueue_charts_js() {

		if ( self::$is_js_enqueued ) {
			return;
		}

		self::$is_js_enqueued = true;

		wp_enqueue_script(
			'google-charts',
			'https://www.gstatic.com/charts/loader.js',
			array(),
			JET_ENGINE_DYNAMIC_CHARTS_VERSION,
			true
		);

		wp_enqueue_script(
			'jet-engine-chart',
			JET_ENGINE_DYNAMIC_CHARTS_URL . 'assets/js/common/chart-renderer.js',
			array( 'jquery', 'google-charts' ),
			JET_ENGINE_DYNAMIC_CHARTS_VERSION,
			true
		);

		wp_add_inline_script(
			'jet-engine-chart',
			'window.JetChartLocale = "' . Plugin::instance()->get_locale() . '"',
			'before'
		);

	}

	public static function print_chart_js() {
		self::enqueue_charts_js();
		wp_print_scripts( 'jet-engine-chart' );
	}

	/**
	 * Retuns chart config
	 * @return [type] [description]
	 */
	public function get_prepared_options() {

		$result = array();

		$plain_options = array(
			'width'  => 'absint',
			'height' => 'absint',
			'type'   => false,
		);

		foreach ( $plain_options as $key => $sanitize ) {
			if ( ! empty( $this->settings[ $key ] ) ) {
				if ( is_callable( $sanitize ) ) {
					$result[ $key ] = call_user_func( $sanitize, $this->settings[ $key ] );
				} else {
					$result[ $key ] = $this->settings[ $key ];
				}
			}
		}

		if ( ! empty( $this->settings['is_stacked'] ) ) {
			$result['is_stacked'] = true;
		}

		if ( ! empty( $this->settings['allow_advanced'] ) && ! empty( $this->settings['advanced_options'] ) ) {

			$advanced_options = wp_unslash( $this->settings['advanced_options'] );
			$advanced_options = jet_engine()->listings->macros->do_macros( $advanced_options );
			$advanced_options = json_decode( $advanced_options, true );

			if ( $advanced_options ) {
				foreach ( $advanced_options as $key => $value ) {
					$result[ $key ] = $value;
				}
			}

		}

		switch ( $this->settings['type'] ) {
			case 'bar':
				$result['bars'] = 'horizontal';
				break;

			case 'geo':
				$result['maps_api_key'] = ! empty( $this->settings['maps_api_key'] ) ? $this->settings['maps_api_key'] : '';
				break;

			default:
				// code...
				break;
		}

		$legend = array(
			'position' => ! empty( $this->settings['legend'] ) ? $this->settings['legend'] : 'right',
		);

		if ( ! isset( $result['legend'] ) ) {
			$result['legend'] = $legend;
		} elseif ( is_array( $result['legend'] ) ) {
			$result['legend'] = array_merge( $result['legend'], $legend );
		}

		return apply_filters( 'jet-engine/charts-builder/chart-config', $result, $this );
	}

	public function get_inline_styles() {

		$styles = array(
			'display: flex',
			'justify-content: center',
		);

		if ( ! empty( $this->settings['height'] ) ) {
			$styles[] = 'height: ' . absint( $this->settings['height'] ) . 'px';
		}

		return implode( ';', $styles );

	}

	/**
	 * Returns query
	 *
	 * @return \Jet_Engine\Query_Builder\Queries\Base_Query Query Builder Query
	 */
	public function get_query() {
		return $this->query;
	}

	/**
	 * Returns an array of table rows
	 *
	 * @return [type] [description]
	 */
	public function get_data() {

		if ( ! $this->query ) {
			return array();
		}

		$items = $this->query->get_items();

		$result = array();
		$row    = array();

		foreach( $this->columns as $column ) {

			if ( ! empty( $column['data_type'] ) ) {
				$row[] = array(
					'label' => $column['name'],
					'type'  => $column['data_type'],
				);
			} else {
				$row[] = $column['name'];
			}

		}

		$result[] = $row;

		$initial_object = jet_engine()->listings->data->get_current_object();

		foreach ( $items as $item ) {

			$row = array();
			jet_engine()->listings->data->set_current_object( $item );
			$first = true;

			foreach( $this->columns as $column ) {
				$row[] = $this->get_raw_content( $column, $first );
				$first = false;
			}

			$result[] = $row;
		}

		jet_engine()->listings->data->set_current_object( $initial_object );

		return apply_filters( 'jet-engine/charts-builder/data', $result, $this );

	}

	function unhtmlentities($string) {

		$string = preg_replace_callback('~&#x([0-9a-f]+);~i', function ($matches) { return chr(hexdec($matches[0])); }, $string);
		$string = preg_replace_callback('~&#([0-9]+);~', function ($matches) { return chr((int) $matches[0]); }, $string);

		$trans_tbl = get_html_translation_table(HTML_ENTITIES);
		$trans_tbl = array_flip($trans_tbl);

		return strtr( $string, $trans_tbl );
	}

	public function sanitize_num( $maybenum ) {

		$maybenum = wp_kses_decode_entities( strip_tags( $maybenum ) );
		return floatval( filter_var( $maybenum, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ) );
	}

	public function get_raw_content( $column = array(), $first = false ) {

		$res    = null;
		$source = ! empty( $column['data_source'] ) ? $column['data_source'] : '';

		switch ( $source ) {

			case 'object':

				$prop = ! empty( $column['object_field'] ) ? $column['object_field'] : false;

				if ( $prop ) {
					$res = jet_engine()->listings->data->get_prop( $prop );
				}

				break;

			case 'fetched':

				$object = jet_engine()->listings->data->get_current_object();
				$col    = ! empty( $column['fetched_column'] ) ? $column['fetched_column'] : false;
				$res    = isset( $object->$col ) ? $object->$col : false;

				break;

			case 'meta':

				$key        = ! empty( $column['meta_key'] ) ? $column['meta_key'] : false;
				$custom_key = ! empty( $column['custom_meta_key'] ) ? $column['custom_meta_key'] : false;

				if ( $custom_key ) {
					$key = $custom_key;
				}

				if ( $key ) {
					$res = jet_engine()->listings->data->get_meta( $key );
				}

				break;
		}

		/**
		 * Make early values sanitizing with PHP on this hook
		 */
		$res = apply_filters( 'jet-engine/charts-builder/column-value', $res, $column, $first );

		if ( ! empty( $column['apply_callback'] ) && ! empty( $column['filter_callback'] ) ) {
			$res = jet_engine()->listings->apply_callback( $res, $column['filter_callback'], $column );
		}

		if ( ! empty( $column['customize'] ) && ! empty( $column['customize_format'] ) ) {
			$res = sprintf( $column['customize_format'], $res );
		}

		$res       = do_shortcode( wp_unslash( $res ) );
		$data_type = isset( $column['data_type'] ) ? $column['data_type'] : '';

		switch ( $data_type ) {

			case 'number':
				$res = $this->sanitize_num( $res );
				break;

			case 'string':
				$res = (string) $res;
				break;

			case 'date':
				if ( ! \Jet_Engine_Tools::is_valid_timestamp( $res ) ) {
					$res = strtotime( $res );
				}

				$res = date( 'Y-m-d H:i:s', $res );
				break;

			default:
				$res = ( ! $first ) ? floatval( $res ) : $res;
				break;
		}

		return $res;

	}

}
