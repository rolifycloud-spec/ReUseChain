<?php
/**
 * JetTricks blocks views parallax extension.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Tricks_Blocks_Parallax_Extension' ) ) {

	/**
	 * Define Jet_Tricks_Blocks_Parallax_Extension class.
	 */
	class Jet_Tricks_Blocks_Parallax_Extension {

		private $supported_blocks;

		/**
		 * Returns supported block names.
		 *
		 * @return string[]
		 */
		public static function get_supported_blocks() {
			$blocks = [
				'core/paragraph',
				'core/heading',
				'core/button',
				'core/image',
				'core/list',
				'core/quote',
				'core/group',
				'core/columns',
				'core/column',
				'core/cover',
			];

			return apply_filters( 'jet-tricks/blocks-parallax/supported-blocks', $blocks );
		}

		/**
		 * Initialize extension.		 
		 *
		 * @return void
		 */
		public function init() {
			$this->supported_blocks = self::get_supported_blocks();

			if ( ! function_exists( 'jet_tricks_settings' ) ) {
				return;
			}

			$avaliable_extensions = jet_tricks_settings()->get_avaliable_extensions();

			if ( ! filter_var( $avaliable_extensions['widget_parallax'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
				return;
			}
		}

	}
}
