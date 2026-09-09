<?php
namespace Jet_Engine_Dynamic_Charts\Blocks;

use Jet_Engine_Dynamic_Charts\Plugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Manager {

	public function __construct() {
		add_action( 'jet-engine/blocks-views/register-block-types', array( $this, 'register_blocks' ), 10 );
		add_action( 'jet-engine/blocks-views/editor-script/after', array( $this, 'register_blocks_script' ), 10 );
		add_filter( 'jet-engine/blocks-views/editor/config', array( $this, 'localize_charts_list' ) );
	}

	public function register_blocks( $blocks_manager ) {
		$blocks_manager->register_block_type( new Chart_Block() );
	}

	public function register_blocks_script() {
		wp_enqueue_script(
			'jet-engine-dynamic-chart',
			JET_ENGINE_DYNAMIC_CHARTS_URL . 'assets/js/blocks/blocks.js',
			array(),
			JET_ENGINE_DYNAMIC_CHARTS_VERSION,
			true
		);
	}

	public function localize_charts_list( $config ) {

		$config['chartsList']           = Plugin::instance()->data->get_charts_for_options( 'blocks' );
		$config['atts']['dynamicChart'] = jet_engine()->blocks_views->block_types->get_block_atts( 'dynamic-chart' );

		return $config;
	}

}
