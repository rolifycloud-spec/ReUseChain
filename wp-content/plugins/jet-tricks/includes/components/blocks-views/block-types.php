<?php
/**
 * JetTricks Block Views Types.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Views_Types' ) ) {

	class Jet_Tricks_Blocks_Views_Types {

		private $_types = [];

		public function __construct() {
			add_action( 'init', [ $this, 'register_block_types' ], 99 );
		}

		public function register_block_types() {

			$types_dir = jet_tricks()->plugin_path( 'includes/components/blocks-views/block-types/' );

			require $types_dir . 'base.php';
			require $types_dir . 'view-more.php';
			require $types_dir . 'unfold.php';
			require $types_dir . 'hotspots.php';

			$types = [
				new Jet_Tricks_Blocks_Views_Type_View_More(),
				new Jet_Tricks_Blocks_Views_Type_Unfold(),
				new Jet_Tricks_Blocks_Views_Type_Hotspots(),
			];

			foreach ( $types as $type ) {
				$this->_types[ $type->get_name() ] = $type;
			}
		}
	}
}
