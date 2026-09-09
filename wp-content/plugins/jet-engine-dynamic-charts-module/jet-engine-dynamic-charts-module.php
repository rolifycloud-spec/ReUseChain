<?php
/**
 * Plugin Name: JetEngine - dynamic charts builder
 * Plugin URI:
 * Description: Dynamic charts builder module for JetEngine.
 * Version:     1.1.0
 * Author:      Crocoblock
 * Author URI:
 * Text Domain: jet-engine-dynamic-charts-module
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

define( 'JET_ENGINE_DYNAMIC_CHARTS_VERSION', '1.1.0' );

define( 'JET_ENGINE_DYNAMIC_CHARTS__FILE__', __FILE__ );
define( 'JET_ENGINE_DYNAMIC_CHARTS_PLUGIN_BASE', plugin_basename( JET_ENGINE_DYNAMIC_CHARTS__FILE__ ) );
define( 'JET_ENGINE_DYNAMIC_CHARTS_PATH', plugin_dir_path( JET_ENGINE_DYNAMIC_CHARTS__FILE__ ) );
define( 'JET_ENGINE_DYNAMIC_CHARTS_URL', plugins_url( '/', JET_ENGINE_DYNAMIC_CHARTS__FILE__ ) );

add_action( 'plugins_loaded', 'jet_engine_dynamic_charts_init' );

function jet_engine_dynamic_charts_init() {
	require JET_ENGINE_DYNAMIC_CHARTS_PATH . 'includes/plugin.php';
}

function jet_engine_dynamic_charts() {
	return Jet_Engine_Dynamic_Charts\Plugin::instance();
}
