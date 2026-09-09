<?php
namespace Jet_Engine_Dynamic_Tables\Admin\Rest_API;

use Jet_Engine_Dynamic_Tables\Table;

class Table_Get_CSV extends \Jet_Engine_Base_API_Endpoint {

	private $query_id = 0;
	private $filters_request = array();

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'table-get-csv';
	}

	/**
	 * API callback
	 * @param \WP_REST_Request $request
	 * @return void
	 */
	public function callback( $request ) {
		$params = $request->get_params();

		$url = strval( $params['jetDynTablesExportUrl'] ?? '' );
		$_POST['jetDynTablesExportUrl'] = $url;

		$url_params_string = parse_url( $url, PHP_URL_QUERY );

		if ( ! empty( $url_params_string ) ) {
			parse_str( $url_params_string, $url_params );

			foreach ( $url_params as $key => $value ) {
				if ( ! isset( $_REQUEST[ $key ] ) ) {
					$_REQUEST[ $key ] = $value;
				}
			}
		}

		$table_id = ! empty( $params['table_id'] ) ? $params['table_id'] : false;

		if ( ! $table_id ) {
			return new \WP_HTTP_Response(
				array(
					'success' => false,
					'error'   => 'No table ID provided.',
				),
				400
			);
		}

		$settings = array();

		if ( ! empty( $params['query_id'] ) ) {
			$settings['rewrite_query']    = true;
			$settings['rewrite_query_id'] = $params['query_id'];
		}

		$table = new Table( $table_id, $settings );

		if ( ! $table->get_query_id() ) {
			return new \WP_HTTP_Response(
				array(
					'success' => false,
					'error'   => 'The table does not exist or is configured incorrectly. Please contact the site administrator.',
					'timeout' => -1,
				),
				500
			);
		}

		if ( ! $table->get_settings( 'allow_csv_export', false ) ) {
			return new \WP_HTTP_Response(
				array(
					'success' => false,
					'error'   => 'This table cannot be exported. Please contact site administrator.',
					'timeout' => -1,
				),
				403
			);
		}

		$sent_signature = ! empty( $params['signature'] ) ? $params['signature'] : false;

		if ( ! $sent_signature || $sent_signature !== $table->create_signature( $params['filter_params'] ?? [] ) ) {
			return new \WP_HTTP_Response(
				array(
					'success' => false,
					'error'   => 'Invalid export signature.',
					'timeout' => 5,
				),
				401
			);
		}

		$request_uri = $_SERVER['REQUEST_URI'] ?? '';

		if ( $url ) {
			$_SERVER['REQUEST_URI'] = $url;
			global $wp;

			$wp->parse_request();
			$wp->query_posts();
			wp_reset_postdata();
		}

		$this->query_id = absint( $table->get_query_id() );

		$query = \Jet_Engine\Query_Builder\Manager::instance()->get_query_by_id( $this->query_id );

		add_filter( 'jet-engine/query-builder/filters/is-filters-request', array( $this, 'force_filters' ), 100, 2 );
		$this->filters_request = $params['filter_params'] ?? [];
		add_filter( 'jet-smart-filters/query/request', array( $this, 'replace_filters_request' ), 100 );
		$query->setup_query();
		remove_filter( 'jet-engine/query-builder/filters/is-filters-request', array( $this, 'force_filters' ), 100 );
		remove_filter( 'jet-smart-filters/query/request', array( $this, 'replace_filters_request' ), 100 );

		$source = $params['source'] ?? 'current_page';

		switch ( $source ) {
			case 'all_pages':
				$query->set_filtered_prop( '_page', 1 );
				$query->set_filtered_prop( '_items_per_page', apply_filters( 'jet-engine/table-builder/export/row-limit', 1000000 ) );
				break;
			default:
				break;
		}

		$_SERVER['REQUEST_URI'] = $request_uri;

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="export.csv"');

		$headers = $table->get_columns_headers();

		if ( ! is_array( $headers ) ) {
			$headers = array();
		}

		$headers = array_column( $headers, 'content' );

		if ( ! empty( $params['included_columns'] ) ) {
			$headers = array_filter(
				$headers,
				function( $key ) use ( $params ) {
					return array_search( $key, $params['included_columns'] ) !== false;
				},
				ARRAY_FILTER_USE_KEY
			);
		}

		$output    = fopen( 'php://output', 'w' );
		$separator = $table->get_settings( 'csv_separator', ',' );
		$separator = trim( $separator );

		if ( empty( $separator || strlen( $separator ) > 1 ) ) {
			$separator = ',';
		}

		ob_start();

		fputcsv( $output, $headers, $separator, "\"", "\\" );

		foreach ( $table->get_rows() as $row_object ) {
			$columns = $table->get_row_contents( $row_object );
			$columns = array_column( $columns, 'content' );
			$columns = array_map( function( $item ) {
				return is_scalar( $item ) ? wp_strip_all_tags( $item ) : '';
			}, $columns );
			$columns = array_filter(
				$columns,
				function( $key ) use ( $params ) {
					return array_search( $key, $params['included_columns'] ) !== false;
				},
				ARRAY_FILTER_USE_KEY
			);
			fputcsv( $output, $columns, $separator, "\"", "\\" );	
		}

		fclose( $output );

		return new \WP_HTTP_Response(
			array(
				'success' => true,
				'content' => wp_strip_all_tags( ob_get_clean() ),
			),
			200
		);
	}

	public function force_filters( $is_filters_query, $query ) {
		if ( $this->query_id === $query->id ) {
			return true;
		}

		return $is_filters_query;
	}

	public function replace_filters_request( $request ) {
		return $this->filters_request;
	}

	/**
	 * Returns endpoint request method - GET/POST/PUT/DELTE
	 *
	 * @return string
	 */
	public function get_method() {
		return 'POST';
	}

	/**
	 * Check user access to current end-popint
	 *
	 * @return bool
	 */
	public function permission_callback( $request ) {
		return true;
	}

}
