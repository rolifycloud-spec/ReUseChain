<?php
namespace Jet_Engine_Dynamic_Tables\Blocks;

use Jet_Engine_Dynamic_Tables\Plugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Manager {

	public function __construct() {
		add_action( 'jet-engine/blocks-views/register-block-types', array( $this, 'register_blocks' ), 10 );
		add_action( 'jet-engine/blocks-views/editor-script/after', array( $this, 'register_blocks_script' ), 10 );
		add_filter( 'jet-engine/blocks-views/editor/config', array( $this, 'localize_tables_list' ) );
		add_action( 'jet-engine/blocks-views/editor-style/after', array( $this, 'enqueue_preview_styles' ) );
	}

	public function register_blocks( $blocks_manager ) {
		$blocks_manager->register_block_type( new Table_Block() );
	}

	public function register_blocks_script() {
		wp_enqueue_script(
			'jet-engine-dynamic-table',
			JET_ENGINE_DYNAMIC_TABLES_URL . 'assets/js/blocks/blocks.js',
			array(),
			JET_ENGINE_DYNAMIC_TABLES_VERSION,
			true
		);
	}

	public function enqueue_preview_styles() {
		if ( ! is_admin() || wp_doing_ajax() ) {
			return;
		}

		wp_enqueue_style(
			'jet-dynamic-table-csv-export',
			JET_ENGINE_DYNAMIC_TABLES_URL . 'assets/css/csv-export.css',
			array(),
			JET_ENGINE_DYNAMIC_TABLES_VERSION,
		);

		wp_enqueue_style(
			'jquery-chosen',
			JET_ENGINE_DYNAMIC_TABLES_URL . 'assets/css/lib/chosen/chosen.min.css',
			array(),
			JET_ENGINE_DYNAMIC_TABLES_VERSION,
		);
	}

	public function localize_tables_list( $config ) {

		$config['tablesList'] = Plugin::instance()->data->get_tables_for_options( 'blocks' );
		$config['atts']['dynamicTable'] = jet_engine()->blocks_views->block_types->get_block_atts( 'dynamic-table' );

		return $config;
	}

}
