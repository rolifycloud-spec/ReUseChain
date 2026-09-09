<?php

namespace Jet_Engine_Dynamic_Charts\Bricks_Views;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Manager {
	/**
	 * Elementor Frontend instance
	 *
	 * @var null
	 */
	public $frontend = null;

	/**
	 * Constructor for the class
	 */
	function __construct() {
		if ( ! $this->has_bricks() ) {
			return;
		}

		add_action( 'init', array( $this, 'register_elements' ), 13 );
	}

	public function register_elements() {

		if ( ! class_exists('\Jet_Engine\Bricks_Views\Elements\Base') ) {
			return;
		}

		\Bricks\Elements::register_element( JET_ENGINE_DYNAMIC_CHARTS_PATH . 'includes/bricks-views/dynamic-chart.php' );

		do_action( 'jet-engine/bricks-views/register-elements' );

	}

	public function has_bricks() {
		return defined( 'BRICKS_VERSION' );
	}
}