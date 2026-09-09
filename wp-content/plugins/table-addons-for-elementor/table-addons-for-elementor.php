<?php
/**
 * Plugin Name:	Table Addons for Elementor
 * Description: Table Widget for elementor page builder. Effortlessly create stunning and functional tables on Elementor.
 * Plugin URI:  https://fusionplugin.com/plugins/table-addons-for-elementor/
 * Version:     2.1.6
 * Elementor tested up to: 3.35.7
 * Elementor Pro tested up to: 3.35.1
 * Author:      FusionPlugin
 * Author URI:  https://fusionplugin.com/
 * License:		GPL-2.0+
 * License URI:	http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:	table-addons-for-elementor
 * Domain Path:	/languages
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'TABLE_ADDONS_FOR_ELEMENTOR_VERSION', '2.1.6' );
define( 'TABLE_ADDONS_FOR_ELEMENTOR__FILE', __FILE__ );
define( 'TABLE_ADDONS_FOR_ELEMENTOR__BASE', plugin_basename( __FILE__ ) );
define( 'TABLE_ADDONS_FOR_ELEMENTOR__PATH', plugin_dir_path( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-table-addons-for-elementor-activator.php
 */
function activate_table_addons_for_elementor() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-table-addons-for-elementor-activator.php';
	Table_Addons_For_Elementor_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-table-addons-for-elementor-deactivator.php
 */
function deactivate_table_addons_for_elementor() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-table-addons-for-elementor-deactivator.php';
	Table_Addons_For_Elementor_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_table_addons_for_elementor' );
register_deactivation_hook( __FILE__, 'deactivate_table_addons_for_elementor' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-table-addons-for-elementor.php';

/**
 * Begins execution of the plugin.
 * @since    1.0.0
 */
function run_table_addons_for_elementor() {

	$plugin = new Table_Addons_For_Elementor();
	$plugin->run();

}
run_table_addons_for_elementor();


/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function appsero_init_tracker_table_addons_for_elementor() {

    if ( ! class_exists( 'Appsero\Client' ) ) {
      require_once __DIR__ . '/includes/appsero/src/Client.php';
    }

    $client = new Appsero\Client( '97381f4c-2239-43f7-b79a-3737f4be7950', 'Table Addons for Elementor', __FILE__ );

    // Active insights
    $client->insights()->init();

}

//appsero_init_tracker_table_addons_for_elementor();