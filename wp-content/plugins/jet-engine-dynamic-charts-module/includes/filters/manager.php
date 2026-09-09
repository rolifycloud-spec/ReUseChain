<?php
namespace Jet_Engine_Dynamic_Charts\Filters;

class Manager {

	public function __construct() {
		add_action( 'jet-smart-filters/providers/register', array( $this, 'register_provider_for_filters' ) );
		add_filter( 'jet-engine/query-builder/filters/allowed-providers', array( $this, 'register_provider_for_query_builder' ) );
	}

	/**
	 * Register new provider for the filters
	 *
	 * @return [type] [description]
	 */
	public function register_provider_for_filters( $providers_manager ) {
		$providers_manager->register_provider(
			'\Jet_Engine_Dynamic_Charts\Filters\Provider',
			JET_ENGINE_DYNAMIC_CHARTS_PATH . 'includes/filters/provider.php'
		);
	}

	public function register_provider_for_query_builder( $providers ) {
		$providers[] = 'jet-data-chart';
		return $providers;
	}

}
