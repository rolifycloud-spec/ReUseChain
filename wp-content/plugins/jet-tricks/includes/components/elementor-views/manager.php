<?php
/**
 * JetTricks Elementor views manager.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Elementor_Views' ) ) {

	/**
	 * Define Jet_Tricks_Elementor_Views class.
	 */
	class Jet_Tricks_Elementor_Views {

		/**
		 * Check if processing elementor widget.
		 *
		 * @var boolean
		 */
		private $is_elementor_ajax = false;

		/**
		 * Constructor.
		 */
		public function __construct() {

			add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );

			if ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, '3.5.0', '>=' ) ) {
				add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ], 10 );
			} else {
				add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widgets' ], 10 );
			}

			add_action( 'wp_ajax_elementor_render_widget', [ $this, 'set_elementor_ajax' ], 10, -1 );
		}

		/**
		 * Set $this->is_elementor_ajax to true on Elementor AJAX processing.
		 *
		 * @return void
		 */
		public function set_elementor_ajax() {
			$this->is_elementor_ajax = true;
		}

		/**
		 * Check if we currently in Elementor mode.
		 *
		 * @return boolean
		 */
		public function in_elementor() {

			$result = false;

			if ( wp_doing_ajax() ) {
				$result = $this->is_elementor_ajax;
			} elseif ( \Elementor\Plugin::instance()->editor->is_edit_mode()
				|| \Elementor\Plugin::instance()->preview->is_preview_mode() ) {
				$result = true;
			}

			return apply_filters( 'jet-tricks/in-elementor', $result );
		}

		/**
		 * Register widgets.
		 *
		 * @param object $widgets_manager Elementor widgets manager instance.
		 *
		 * @return void
		 */
		public function register_widgets( $widgets_manager ) {

			$avaliable_widgets = jet_tricks_settings()->get( 'avaliable_widgets' );

			require jet_tricks()->plugin_path( 'includes/base/class-jet-tricks-base.php' );

			foreach ( glob( $this->component_path( 'widgets/' ) . '*.php' ) as $file ) {
				$slug    = basename( $file, '.php' );
				$enabled = isset( $avaliable_widgets[ $slug ] ) ? $avaliable_widgets[ $slug ] : '';

				if ( filter_var( $enabled, FILTER_VALIDATE_BOOLEAN ) || ! $avaliable_widgets ) {
					$this->register_widget( $file, $widgets_manager );
				}
			}
		}

		/**
		 * Register widget by file name.
		 *
		 * @param string $file            File name.
		 * @param object $widgets_manager Widgets manager instance.
		 *
		 * @return void
		 */
		public function register_widget( $file, $widgets_manager ) {

			$base  = basename( str_replace( '.php', '', $file ) );
			$class = ucwords( str_replace( '-', ' ', $base ) );
			$class = str_replace( ' ', '_', $class );
			$class = sprintf( 'Elementor\%s', $class );

			require $file;

			if ( class_exists( $class ) ) {
				if ( method_exists( $widgets_manager, 'register' ) ) {
					$widgets_manager->register( new $class );
				} else {
					$widgets_manager->register_widget_type( new $class );
				}
			}
		}

		/**
		 * Register category for elementor if not exists.
		 *
		 * @return void
		 */
		public function register_category() {

			\Elementor\Plugin::instance()->elements_manager->add_category(
				'jet-tricks',
				array(
					'title' => esc_html__( 'JetTricks', 'jet-tricks' ),
					'icon'  => 'font',
				),
				1
			);
		}

		/**
		 * Component path.
		 *
		 * @param string $path Path name.
		 *
		 * @return string
		 */
		public function component_path( $path ) {
			return jet_tricks()->plugin_path( 'includes/components/elementor-views/' . $path );
		}
	}
}
