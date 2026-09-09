<?php
/**
 * Gutenberg blocks manager
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Views' ) ) {

	/**
	 * Define Jet_Tricks_Blocks_Views class
	 */
	class Jet_Tricks_Blocks_Views {

		public $editor;
		public $block_types;

		/**
		 * @var \Crocoblock\Blocks_Style\Manager|null
		 */
		public $style_manager = null;

		/**
		 * Constructor for the class
		 */
		public function __construct() {

			add_filter( 'block_categories_all', [ $this, 'add_block_category' ] );

			$this->init_style_manager();

			if ( is_admin() ) {
				require $this->component_path( 'editor.php' );
				$this->editor = new Jet_Tricks_Blocks_Views_Editor();
			}

			require $this->component_path( 'block-types.php' );
			$this->block_types = new Jet_Tricks_Blocks_Views_Types();
		}

		/**
		 * Initialize style manager
		 *
		 * @return void
		 */
		protected function init_style_manager() {
			$module_data = jet_tricks()->module_loader->get_included_module_data( 'style-manager.php' );

			if ( empty( $module_data['path'] ) || empty( $module_data['url'] ) ) {
				return;
			}

			$this->style_manager = new \Crocoblock\Blocks_Style\Manager( array(
				'path' => $module_data['path'],
				'url'  => $module_data['url'],
			) );
		}

		/**
		 * Add block category
		 *
		 * @param array $block_categories
		 *
		 * @return array
		 */
		public function add_block_category( $block_categories ) {
			return array_merge(
				$block_categories,
				[
					[
						'slug'  => 'jet-tricks',
						'title' => __( 'JetTricks', 'jet-tricks' ),
					],
				]
			);
		}

		/**
		 * Return path to component file
		 *
		 * @param string $path
		 *
		 * @return string
		 */
		public function component_path( $path ) {
			return jet_tricks()->plugin_path( 'includes/components/blocks-views/' . $path );
		}
	}
}
