<?php
namespace Jet_Engine_Dynamic_Charts;
/**
 * Glossaries data controller class
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Jet_Engine_Options_Data class
 */
class Data extends \Jet_Engine_Base_Data {

	/**
	 * Chart name
	 *
	 * @var string
	 */
	public $table = 'post_types';

	/**
	 * Query arguments
	 *
	 * @var array
	 */
	public $query_args = array(
		'status' => 'chart',
	);

	/**
	 * Chart format
	 *
	 * @var string
	 */
	public $chart_format = array( '%s', '%s', '%s', '%s', '%s' );

	/**
	 * Returns blacklisted post types slugs
	 *
	 * @return array
	 */
	public function items_blacklist() {
		return array();
	}

	/**
	 * Returns blacklisted post types slugs
	 *
	 * @return array
	 */
	public function meta_blacklist() {
		return array();
	}

	/**
	 * Sanitizr post type request
	 *
	 * @return void
	 */
	public function sanitize_item_request() {
		return true;
	}

	public function get_charts_for_options( $context = 'blocks' ) {

		$items = $this->get_items();

		$placeholder = __( 'Select chart...', 'jet-engine' );
		$result      = array();

		if ( 'elementor' === $context ) {
			$result[''] = $placeholder;
		} else {
			$result[] = array(
				'value' => '',
				'label' => $placeholder,
			);
		}

		if ( ! empty( $items ) ) {
			foreach ( $items as $index => $item ) {

				$id     = absint( $item['id'] );
				$labels = maybe_unserialize( $item['labels'] );
				$name   = ! empty( $labels['name'] ) ? $labels['name'] : 'chart #' . $id;

				if ( 'elementor' === $context ) {
					$result[ $id ] = $name;
				} else {
					$result[] = array(
						'value' => $id,
						'label' => $name,
					);
				}

			}
		}

		return $result;

	}

	/**
	 * Prepare post data from request to write into database
	 *
	 * @return array
	 */
	public function sanitize_item_from_request() {

		$request = $this->request;

		$result = array(
			'slug'        => '',
			'status'      => 'chart',
			'labels'      => array(),
			'args'        => array(),
			'meta_fields' => array(),
		);

		$name = ! empty( $request['name'] ) ? sanitize_text_field( $request['name'] ) : 'Untitled table';

		$labels = array(
			'name' => $name,
		);

		// Sanitize arguments
		$args          = array();
		$request_args  = ! empty( $request['args'] ) ? $request['args'] : array();

		$bool_args = array(
			'is_stacked',
			'allow_advanced',
		);

		foreach ( $bool_args as $key ) {
			$value = isset( $request_args[ $key ] ) ? $request_args[ $key ] : false;
			$args[ $key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		}

		$regular_args = array(
			'query_id',
			'type',
			'maps_api_key',
			'allowed_columns',
			'inline_styles',
			'legend',
			'advanced_options',
			'width',
			'height',
		);

		foreach ( $regular_args as $key ) {
			$args[ $key ] = isset( $request_args[ $key ] ) ? $request_args[ $key ] : null;
		}

		$result['slug']        = null;
		$result['labels']      = $labels;
		$result['meta_fields'] = ! empty( $request['meta_fields'] ) ? $request['meta_fields'] : false;

		if ( ! empty( $args['inline_styles'] ) ) {

			$allowed_fields = array();

			foreach ( $result['meta_fields'] as $field ) {
				$allowed_fields[] = $field['_id'];
			}

			foreach ( $args['inline_styles'] as $id => $style ) {
				if ( ! in_array( $id, $allowed_fields ) ) {
					unset( $args['inline_styles'][ $id ] );
				}
			}
		}

		$result['args'] = $args;

		return $result;

	}

	/**
	 * Sanitize meta fields
	 *
	 * @param  [type] $meta_fields [description]
	 * @return [type]              [description]
	 */
	public function sanitize_meta_fields( $meta_fields ) {
		return $meta_fields;
	}

	/**
	 * Filter post type for register
	 *
	 * @return array
	 */
	public function filter_item_for_register( $item ) {

		$result       = array();
		$args         = maybe_unserialize( $item['args'] );
		$labels       = maybe_unserialize( $item['labels'] );
		$args['name'] = $labels['name'];
		$result       = array_merge( $item, $args );

		unset( $result['args'] );
		unset( $result['labels'] );
		unset( $result['status'] );
		unset( $result['slug'] );

		if ( empty( $item['meta_fields'] ) ) {
			$result['meta_fields'] = false;
		} else {

			$fields = maybe_unserialize( $item['meta_fields'] );

			array_walk( $fields, function( &$item ) {
				$item['collapsed'] = true;
			} );

			$result['meta_fields'] = $fields;

		}

		return $result;

	}

	/**
	 * Filter post type for edit
	 *
	 * @return array
	 */
	public function filter_item_for_edit( $item ) {
		return $this->filter_item_for_register( $item );
	}

}
