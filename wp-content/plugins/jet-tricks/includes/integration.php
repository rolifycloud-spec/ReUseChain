<?php
/**
 * Class description
 *
 * @package   package_name
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Integration' ) ) {

	/**
	 * Define Jet_Tricks_Integration class
	 */
	class Jet_Tricks_Integration {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Initalize integration hooks
		 *
		 * @return void
		 */
		public function init() {

			require jet_tricks()->plugin_path( 'includes/components/elementor-views/manager.php' );
			new Jet_Tricks_Elementor_Views();

			add_action( 'elementor/controls/controls_registered', array( $this, 'add_controls' ), 10 );

			// Init Jet Elementor Extension module
			$ext_module_data = jet_tricks()->module_loader->get_included_module_data( 'jet-elementor-extension.php' );

			Jet_Elementor_Extension\Module::get_instance(
				array(
					'path' => $ext_module_data['path'],
					'url'  => $ext_module_data['url'],
				)
			);

		}

		/**
		 * Add new controls.
		 *
		 * @param  object $controls_manager Controls manager instance.
		 * @return void
		 */
		public function add_controls( $controls_manager ) {

			$grouped = array(
				'jet-tricks-box-style' => 'Jet_Tricks_Group_Control_Box_Style',
			);

			foreach ( $grouped as $control_id => $class_name ) {
				if ( $this->include_control( $class_name, true ) ) {
					$controls_manager->add_group_control( $control_id, new $class_name() );
				}
			}

		}

		/**
		 * Include control file by class name.
		 *
		 * @param  [type] $class_name [description]
		 * @return [type]             [description]
		 */
		public function include_control( $class_name, $grouped = false ) {

			$filename = sprintf(
				'includes/controls/%2$sclass-%1$s.php',
				str_replace( '_', '-', strtolower( $class_name ) ),
				( true === $grouped ? 'groups/' : '' )
			);

			if ( ! file_exists( jet_tricks()->plugin_path( $filename ) ) ) {
				return false;
			}

			require jet_tricks()->plugin_path( $filename );

			return true;
		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @return object
		 */
		public static function get_instance( $shortcodes = array() ) {

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self( $shortcodes );
			}
			return self::$instance;
		}
	}

}

/**
 * Returns instance of Jet_Tricks_Integration
 *
 * @return object
 */
function jet_tricks_integration() {
	return Jet_Tricks_Integration::get_instance();
}
