<?php
namespace Jet_Engine_Dynamic_Tables\Render;

use Jet_Engine_Dynamic_Tables\Table;

class Table_Renderer extends \Jet_Engine_Render_Base {

	/**
	 * @var \Jet_Engine_Dynamic_Tables\Table
	 */
	public $table = null;

	/**
	 * @var bool
	 */
	private $export_allowed;

	private static $export_scripts_loaded = false;

	public function is_export_allowed() {
		if ( isset( $this->export_allowed ) ) {
			return $this->export_allowed;
		}

		$enable_csv_export = $this->get( 'enable_csv_export' );
		$enable_csv_export = filter_var( $enable_csv_export, FILTER_VALIDATE_BOOLEAN );

		$this->export_allowed = $enable_csv_export && $this->table->get_settings( 'allow_csv_export', false );

		return $this->export_allowed;
	}

	public function get_name() {
		return 'jet-dynamic-table';
	}

	public function default_settings() {
		return apply_filters( 'jet-engine/table-builder/render/default-settings', array(
			'table_id'          => null,
			'thead'             => true,
			'tfoot'             => false,
			'inline_css'        => false,
			'scrollable'        => false,
			'rewrite_query'     => '',
			'rewrite_query_id'  => '',
			'enable_csv_export' => false,
		));
	}

	public function setup_table( $table_id = null, $columns = array(), $settings = array() ) {

		if ( null !== $this->table ) {
			return;
		}

		if ( $table_id ) {
			$this->table = new Table( $table_id, $settings );
		} else {
			$this->table = new Table();
			$this->table->setup_table( $columns, $settings );
		}

	}

	public function render() {

		$table_id = $this->get( 'table_id' );

		if ( ! $table_id ) {
			return;
		}

		$this->setup_table( $table_id, array(), $this->get_settings() );

		do_action( 'jet-engine/data-tables/before-render', $this );

		$initial_object = jet_engine()->listings->data->get_current_object();

		$this->table_header();
		$this->table_body();
		$this->table_footer();

		jet_engine()->frontend->reset_data();
		jet_engine()->listings->data->set_current_object( $initial_object );

		do_action( 'jet-engine/data-tables/after-render', $this );

	}

	public function css_class( $suffix = '' ) {
		if ( ! is_array( $suffix ) ) {
			return $this->get_name() . $suffix;
		} else {
			$res = array();

			foreach ( $suffix as $suffix_item ) {
				$res[] = $this->get_name() . $suffix_item;
			}

			return implode( ' ', $res );
		}
	}

	public function get_table_object() {
		return $this->table;
	}

	/**
	 * [table_header description]
	 * @return [type] [description]
	 */
	public function table_header() {

		$header = $this->get( 'thead' );
		$inline_css = $this->get( 'inline_css' );
		$scrollable = $this->get( 'scrollable' );
		$scrollable = filter_var( $scrollable, FILTER_VALIDATE_BOOLEAN );

		if ( $inline_css ) {
			$inline_css = str_replace( '{{WRAPPER}}', '.' . $this->css_class() . '[data-table-id="' . $this->table->get_id() . '"]', $inline_css );
		}

		$scrollable_css = '';

		if ( $scrollable ) {
			$scrollable_css = ' style="max-width: 100%; overflow: auto;"';
		}

		if ( $this->is_export_allowed() ) {
			echo '<div class="' . $this->css_class( '-export-wrapper' ) . '">';
		}

		echo '<style>' . $inline_css . '</style>';
		echo '<div class="' . $this->css_class( '-wrapper' ) . '"' . $scrollable_css . '>';

		echo '<table class="' . $this->css_class() . '" data-table-id="' . $this->table->get_id() . '" data-query-id="' . $this->table->get_query_id() . '">';

		do_action( 'jet-engine/data-tables/before-header', $this );

		if ( $header ) {
			echo '<thead class="' . $this->css_class( '__header' ) . '">';

			do_action( 'jet-engine/data-tables/before-header-row', $this );

			$this->render_row( $this->table->get_columns_headers(), 'th', 'header' );

			do_action( 'jet-engine/data-tables/after-header-row', $this );

			echo '</thead>';
		}
	}

	public function render_row( $columns = array(), $html_tag = 'td', $class = 'regular', $data_attr = '' ) {
		echo '<tr class="' . $this->css_class( array( '__row', '__row--header' ) ) . '" ' . $data_attr . '>';
			foreach ( $columns as $column ) {

				$column_css = '';
				$css = $this->table->get_column_css( $column['id'], $html_tag );

				if ( $css ) {
					$column_css = 'style="' . $css . '"';
				}

				printf(
					'<%1$s class="%2$s" %4$s>%3$s</%1$s>',
					$html_tag,
					$this->css_class( array( '__col', '__col--' . $column['css_class'] ) ),
					$column['content'],
					$column_css
				);
			}
		echo '</tr>';
	}

	public function table_body() {
		$attrs  = array(
			'class' => $this->css_class( '__body' ),
		);

		$enable_csv_export = $this->get( 'enable_csv_export' );
		$enable_csv_export = filter_var( $enable_csv_export, FILTER_VALIDATE_BOOLEAN );

		if ( $this->is_export_allowed() ) {
			$separator = $enable_csv_export ? $this->table->get_settings( 'csv_separator', ',' ) : '';
			$separator = trim( $separator );

			if ( empty( $separator || strlen( $separator ) > 1 ) ) {
				$separator = ',';
			}

			$attrs['data-export-signature'] = $this->table->create_signature();
			$attrs['data-export-separator'] = $separator;
		}

		printf(
			'<tbody %1$s>',
			\Jet_Engine_Tools::get_attr_string( $attrs ),
		);

		add_action( 'jet-engine/query-builder/filter-provider', array( $this, 'set_props_provider_name' ), 10, 2 );

		do_action(
			'jet-engine/query-builder/listings/on-query',
			$this->table->get_query(),
			$this->get_settings(),
			$this,
			$this->table->get_query()
		);

		foreach ( $this->table->get_rows() as $row_object ) {

			$columns = $this->table->get_row_contents( $row_object );
			// must be called after table->get_row_contents()
			$data_attr = 'data-item-object="' . jet_engine()->listings->data->get_current_object_id() . '"';

			$this->render_row( $columns, 'td', 'regular', $data_attr );

		}

		echo '</tbody>';
	}

	/**
	 * Set provider name for table renderer

	 * @return [type] [description]
	 */
	public function set_props_provider_name( $provider, $widget ) {
			
		if ( $widget->get_name() === $this->get_name() ) {
			return 'jet-data-table';
		}

		return $provider;

	}

	/**
	 * [table_header description]
	 * @return [type] [description]
	 */
	public function table_footer() {

		$footer = $this->get( 'tfoot' );

		do_action( 'jet-engine/data-tables/before-footer', $this );

		if ( $footer ) {
			echo '<tfoot class="' . $this->css_class( '__header' ) . '">';

			do_action( 'jet-engine/data-tables/before-footer-row', $this );

			$this->render_row( $this->table->get_columns_headers(), 'th', 'footer' );

			do_action( 'jet-engine/data-tables/after-footer-row', $this );

			echo '</tfoot>';
		}

		echo '</table>';

		echo '</div>';

		$this->mayber_render_export_controls();
	}

	private function mayber_render_export_controls() {
		$enable_csv_export = $this->get( 'enable_csv_export' );
		$enable_csv_export = filter_var( $enable_csv_export, FILTER_VALIDATE_BOOLEAN );

		if ( ! $this->is_export_allowed() ) {
			return;
		}

		if ( ! self::$export_scripts_loaded ) {
			wp_enqueue_script(
				'jquery-chosen',
				JET_ENGINE_DYNAMIC_TABLES_URL . 'assets/js/lib/chosen.jquery.min.js',
				array( 'jquery' ),
				JET_ENGINE_DYNAMIC_TABLES_VERSION,
				true
			);

			wp_enqueue_script(
				'jet-dynamic-table-csv-export',
				JET_ENGINE_DYNAMIC_TABLES_URL . 'assets/js/frontend/csv-export.js',
				array( 'jquery', 'jquery-chosen', 'jet-plugins', 'wp-api-fetch' ),
				JET_ENGINE_DYNAMIC_TABLES_VERSION,
				true
			);

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

			self::$export_scripts_loaded = true;

			// wp_enqueue_script(
			// 	'jet-dynamic-table-select2',
			// 	JET_ENGINE_DYNAMIC_TABLES_URL . 'assets/js/lib/select2.full.min.js',
			// 	array( 'jquery' ),
			// 	JET_ENGINE_DYNAMIC_TABLES_VERSION,
			// 	true
			// );

			// wp_enqueue_style(
			// 	'jet-dynamic-table-select2',
			// 	JET_ENGINE_DYNAMIC_TABLES_URL . 'assets/css/lib/select2.min.css',
			// 	array(),
			// 	JET_ENGINE_DYNAMIC_TABLES_VERSION,
			// );

		}

		?> 

			<div class="jet-dynamic-table__export-controls-box">
				<div class="jet-dynamic-table__export-source-wrapper control-wrapper">
					<label for="export_source"><?php _e( 'Page Range', 'jet-engine' ) ?></label>
					<select name="export_source" class="jet-dynamic-table__export-source control">
						<option value="current_page"><?php _e( 'Current Page', 'jet-engine' ) ?></option>
						<option value="all_pages"><?php _e( 'All Pages', 'jet-engine' ) ?></option>
					</select>
				</div>
				<div class="jet-dynamic-table__included-columns-wrapper control-wrapper">
					<label for="included_columns"><?php _e( 'Included Columns', 'jet-engine' ) ?></label>
					<select name="included_columns" class="jet-dynamic-table__included-columns control" multiple></select>
				</div>
				<div class="jet-dynamic-table__export-button-wrapper control-wrapper">
					<label for="export"><?php _e( 'Export in CSV', 'jet-engine' ) ?></label>
					<button name="export" class="jet-dynamic-table__export-button control"><?php _e( 'Export in CSV', 'jet-engine' ) ?></button>
				</div>
			</div>
			<div class="jet-dynamic-table__export-error jet-dynamic-table__export-hidden"></div>

			</div>
		
		<?php
	}

}
