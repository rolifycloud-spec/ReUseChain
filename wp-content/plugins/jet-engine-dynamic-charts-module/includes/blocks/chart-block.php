<?php
namespace Jet_Engine_Dynamic_Charts\Blocks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Chart_Block extends \Jet_Engine_Blocks_Views_Type_Base {

	/**
	 * Returns block name
	 *
	 * @return [type] [description]
	 */
	public function get_name() {
		return 'dynamic-chart';
	}

	/**
	 * Return attributes array
	 *
	 * @return array
	 */
	public function get_attributes() {
		return array(
			'chart_id' => array(
				'type'    => 'string',
				'default' => '',
			),
			// Element ID
			'_element_id' => array(
				'type'    => 'string',
				'default' => '',
			),
		);
	}

	public function css_selector( $el = '' ) {
		return sprintf( '{{WRAPPER}} .jet-%1$s%2$s', $this->get_name(), $el );
	}

}
