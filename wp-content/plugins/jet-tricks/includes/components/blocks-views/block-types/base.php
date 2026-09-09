<?php
/**
 * JetTricks Blocks Views Type Base.
 */

use JET_SM\Gutenberg\Controls_Manager;
use JET_SM\Gutenberg\Block_Manager;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Views_Type_Base' ) ) {

	/**
	 * Define Jet_Tricks_Blocks_Views_Type_Base class
	 */
	abstract class Jet_Tricks_Blocks_Views_Type_Base {

		protected $namespace = 'jet-tricks/';

		public $block_manager    = null;
		public $controls_manager = null;
		public $css_scheme       = [];

		/**
		 * Constructor for the class
		 */
		public function __construct() {

			if ( $this->has_style_manager() ) {
				$this->css_scheme = $this->get_css_scheme();
				$this->set_style_manager_instance();
				$this->add_style_manager_options();
			}

			$this->register_block_type();
		}

		/**
		 * Check is has style manager instance
		 *
		 * @return bool
		 */
		public function has_style_manager() {
			return $this->has_crocoblock_style_manager() || $this->has_legacy_style_manager();
		}

		/**
		 * Check if Crocoblock Blocks_Style is available
		 *
		 * @return bool
		 */
		public function has_crocoblock_style_manager() {
			return isset( jet_tricks()->blocks_views )
				&& isset( jet_tricks()->blocks_views->style_manager )
				&& jet_tricks()->blocks_views->style_manager instanceof \Crocoblock\Blocks_Style\Manager;
		}

		/**
		 * Check if legacy Jet Style Manager plugin is available
		 *
		 * @return bool
		 */
		public function has_legacy_style_manager() {
			return class_exists( 'JET_SM\Gutenberg\Controls_Manager' )
				&& class_exists( 'JET_SM\Gutenberg\Block_Manager' );
		}

		/**
		 * Enqueue frontend assets
		 */
		public function enqueue_frontend_assets() {

			if ( ! wp_style_is( 'jet-tricks-frontend', 'enqueued' ) ) {
				wp_enqueue_style(
					'jet-tricks-frontend',
					jet_tricks()->plugin_url( 'assets/css/jet-tricks-frontend.css' ),
					[],
					jet_tricks()->get_version()
				);
			}

			if ( ! wp_script_is( 'jet-tricks-frontend', 'enqueued' ) ) {
				Jet_Tricks_Assets::ensure_jet_tricks_frontend_registered();
				wp_enqueue_script( 'jet-tricks-frontend' );
			}
		}

		/**
		 * Returns full name of the block
		 *
		 * @return string
		 */
		public function get_block_name() {
			return $this->namespace . $this->get_name();
		}

		/**
		 * Set style manager class instance
		 */
		public function set_style_manager_instance() {
			if ( $this->has_crocoblock_style_manager() ) {
				$sm = jet_tricks()->blocks_views->style_manager;
				$sm->register_block_support( $this->get_block_name() );
				$proxy = $sm->get_proxy( $this->get_block_name() );
				$this->block_manager    = $proxy;
				$this->controls_manager = $proxy;
			} elseif ( $this->has_legacy_style_manager() ) {
				$this->block_manager    = Block_Manager::get_instance();
				$this->controls_manager = new Controls_Manager( $this->get_block_name() );
			}
		}

		/**
		 * Returns blocks CSS selector
		 *
		 * @param string $el
		 *
		 * @return string
		 */
		public function css_selector( $el = '' ) {
			return sprintf( '{{WRAPPER}} .jet-tricks-%s %s', $this->get_name(), $el );
		}

		/**
		 * Returns blocks CSS scheme
		 *
		 * @return array
		 */
		public function get_css_scheme() {
			return [];
		}

		/**
		 * Add style block options
		 */
		public function add_style_manager_options() {}

		/**
		 * Register block type
		 */
		public function register_block_type() {

			$block_dir = jet_tricks()->plugin_path(
				'assets/js/admin/blocks-views/src/blocks/' . $this->get_name()
			);

			if ( ! file_exists( $block_dir . '/block.json' ) ) {
				return;
			}

			register_block_type(
				$block_dir,
				[ 'render_callback' => [ $this, 'render_callback' ] ]
			);
		}

		/**
		 * Returns block name
		 *
		 * @return string
		 */
		abstract public function get_name();

		/**
		 * Render block callback
		 *
		 * @param array $attributes
		 *
		 * @return string
		 */
		abstract public function render_callback( $attributes = [] );

		/**
		 * Check if is blocks edit mode
		 *
		 * @return bool
		 */
		public function is_edit_mode() {
			return defined( 'REST_REQUEST' ) && REST_REQUEST;
		}
	}
}
