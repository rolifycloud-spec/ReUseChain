<?php
namespace Jet_Theme_Core\Theme_Builder;
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

class Page_Templates_Export_Import {

	/**
	 * A reference to an instance of this class.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    Jet_Theme_Core
	 */
	private static $instance = null;

	/**
	 * Returns the instance.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return Jet_Theme_Core
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * @param $page_template_id
	 *
	 * @return string
	 */
	public function get_page_template_export_link( $page_template_id ) {
		return add_query_arg(
			[
				'action'           => 'jet_theme_core_export_page_template',
				'page_template_id' => $page_template_id,
				'nonce'            => wp_create_nonce( 'jet-theme-core-builder-nonce' ),
			],
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 *
	 */
	public function export_page_template_action() {

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$page_template_id = isset( $_GET['page_template_id'] ) ? absint( $_GET['page_template_id'] ) : 0;

		if ( 'jet_theme_core_export_page_template' !== $action || ! $page_template_id ) {
			return;
		}

		if ( jet_theme_core()->theme_builder->page_templates_manager->get_slug() !== get_post_type( $page_template_id ) || ! current_user_can( 'edit_post', $page_template_id ) ) {
			wp_send_json_error( __( 'You don\'t have permissions to do this', 'jet-theme-core' ) );
		}

		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'jet-theme-core-builder-nonce' ) ) {
			wp_send_json_error( __( 'Page has expired. Please reload this page.', 'jet-theme-core' ) );
		}

		$this->export_page_template( $page_template_id );
	}

	/**
	 * [export_template description]
	 * @param  [type] $popup_id [description]
	 * @return [type]           [description]
	 */
	public function export_page_template( $page_template_id ) {
		$file_data = $this->prepare_page_template( $page_template_id );

		header( 'Pragma: public' );
		header( 'Expires: 0' );
		header( 'Cache-Control: public' );
		header( 'Content-Description: File Transfer' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="'. $file_data['name'] . '"' );
		header( 'Content-Transfer-Encoding: binary' );

		session_write_close();

		// Output file data.
		// The response is a JSON download encoded with wp_json_encode(), not HTML.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $file_data['data'];

		die();
	}

	/**
	 * [prepare_popup_export description]
	 * @param  [type] $popup_id [description]
	 * @return [type]           [description]
	 */
	public function prepare_page_template( $page_template_id ) {

		$layout = get_post_meta( $page_template_id, '_layout', true );
		$conditions = get_post_meta( $page_template_id, '_conditions', true );
		$relation_type = get_post_meta( $page_template_id, '_relation_type', true );
		$type = get_post_meta( $page_template_id, '_type', true );
		$template_ids = [];
		$template_data_to_export = [ 'templateList' => [] ];

		if ( ! empty( $layout ) ) {
			foreach ( $layout as $layout_name => $layout_data ) {
				if ( false !== $layout_data['id'] ) {
					$template_ids[] = $layout_data['id'];
				}
			}
		}

		if ( ! empty( $template_ids ) ) {
			$template_data_to_export = jet_theme_core()->templates->export_import_manager->prepare_template_data_to_export( $template_ids );
		}

		$export_data = [
			'version'          => JET_THEME_CORE_VERSION,
			'pageTemplateName' => get_the_title( $page_template_id ),
			'conditions'       => $conditions,
			'relationType'     => $relation_type,
			'layout'           => $layout,
			'type'             => $type,
			'templateList'     => $template_data_to_export['templateList'],
		];

		return [
			'name' => 'jet-page-template-' . $page_template_id . '-' . date( 'Y-m-d' ) . '.json',
			'data' => wp_json_encode( $export_data ),
		];
	}

	/**
	 * Process page template import
	 */
	public function process_import() {

		if ( ! current_user_can( 'import' ) ) {
			wp_send_json_error( __( 'You don\'t have permissions to do this', 'jet-theme-core' ) );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'jet-theme-core-builder-nonce' ) ) {
			wp_send_json_error( __( 'Page has expired. Please reload this page.', 'jet-theme-core' ) );
		}

		if ( empty( $_FILES['_file'] ) ) {
			wp_send_json_error( __( 'File not passed', 'jet-theme-core' ) );
		}

		// File metadata and contents are validated by read_uploaded_json().
		$file    = $_FILES['_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$content = \Jet_Theme_Core\Utils::read_uploaded_json( $file );

		if ( is_wp_error( $content ) ) {
			wp_send_json_error( $content->get_error_message() );
		}

		if (
			! isset( $content['pageTemplateName'], $content['conditions'], $content['layout'], $content['type'] )
			|| ! is_string( $content['pageTemplateName'] )
			|| ! is_array( $content['conditions'] )
			|| ! is_array( $content['layout'] )
			|| ! is_string( $content['type'] )
			|| ( isset( $content['relationType'] ) && ! is_string( $content['relationType'] ) )
			|| ( isset( $content['templateList'] ) && null !== $content['templateList'] && ! is_array( $content['templateList'] ) )
		) {
			wp_send_json_error( __( 'Invalid page template data.', 'jet-theme-core' ) );
		}

		$template_name          = sanitize_text_field( $content['pageTemplateName'] );
		$template_conditions    = $content['conditions'];
		$template_relation_type = isset( $content['relationType'] ) ? sanitize_key( $content['relationType'] ) : 'or';
		$template_layout        = $content['layout'];
		$template_type          = sanitize_key( $content['type'] );
		$template_list          = isset( $content['templateList'] ) && is_array( $content['templateList'] ) ? $content['templateList'] : [];
		$allowed_template_types = jet_theme_core()->structures->get_structure_types();

		if ( ! in_array( $template_type, $allowed_template_types, true ) || ! in_array( $template_relation_type, [ 'and', 'or' ], true ) ) {
			wp_send_json_error( __( 'Invalid page template type.', 'jet-theme-core' ) );
		}

		$sanitized_layout = [];

		foreach ( [ 'header', 'body', 'footer' ] as $location ) {
			$location_data = isset( $template_layout[ $location ] ) && is_array( $template_layout[ $location ] ) ? $template_layout[ $location ] : [];

			$sanitized_layout[ $location ] = [
				'id'       => ! empty( $location_data['id'] ) ? absint( $location_data['id'] ) : false,
				'enabled'  => isset( $location_data['enabled'] ) ? rest_sanitize_boolean( $location_data['enabled'] ) : true,
				'override' => isset( $location_data['override'] ) ? rest_sanitize_boolean( $location_data['override'] ) : true,
			];
		}

		$template_layout = $sanitized_layout;

		if ( ! empty( $template_list ) ) {
			foreach ( $template_list as $templateData ) {
				$create_template_handler = jet_theme_core()->templates->export_import_manager->create_imported_template( $templateData );

				if ( 'success' !== $create_template_handler['type'] ) {
					wp_send_json_error( $create_template_handler['message'] );
				}

				$body_type_map = apply_filters( 'jet-theme-core/templates-import/body-type-map', [ 'jet_page', 'jet_archive', 'jet_single' ] );

				if ( 'jet_header' === $templateData['type'] ) {
					$template_layout['header']['id'] = $create_template_handler['template_id'];
				}

				if ( in_array( $templateData['type'], $body_type_map, true ) ) {
					$template_layout['body']['id'] = $create_template_handler['template_id'];
				}

				if ( 'jet_footer' === $templateData['type'] ) {
					$template_layout['footer']['id'] = $create_template_handler['template_id'];
				}
			}
		}

		$create_template_data = jet_theme_core()->theme_builder->page_templates_manager->create_page_template( $template_name, $template_conditions, $template_layout, $template_type, $template_relation_type );

		if ( 'success' !== $create_template_data['type'] || empty( $create_template_data['data'] ) ) {
			wp_send_json_error( $create_template_data['message'] );
		}

		wp_send_json_success( [
			'newTemplateId'    => $create_template_data[ 'data' ][ 'newTemplateId' ],
			'pageTemplatesList'    => $create_template_data[ 'data' ][ 'list' ],
			'templatesList' => jet_theme_core()->templates->get_template_list(),
			'message'          => $create_template_data[ 'message' ]
		] );
	}

	/**
	 * Constructor for the class
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'export_page_template_action' ] );
		add_action( 'wp_ajax_jet_theme_core_import_page_template', array( $this, 'process_import' ) );
	}

}
