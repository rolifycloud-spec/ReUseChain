<?php
namespace Jet_Dashboard;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Jet_Dashboard_License_Manager class
 */
class License_Manager {

	/**
	 * [$slug description]
	 * @var boolean
	 */
	public $license_data_key = 'jet-license-data';

	/**
	 * [$sys_messages description]
	 * @var array
	 */
	public $sys_messages = [];

	/**
	 * Remote license sync transient key.
	 *
	 * @var string
	 */
	public $license_remote_sync_key = 'jet_dashboard_license_remote_sync';

	/**
	 * Init page
	 */
	public function __construct() {

		$this->sys_messages = apply_filters( 'jet_dashboard_license_sys_messages', array(
			'internal'     => 'Internal error. Please, try again later',
			'server_error' => 'Server error. Please, try again later',
		) );

		add_action( 'wp_ajax_jet_license_action', array( $this, 'jet_license_action' ) );

		add_action( 'init', array( $this, 'maybe_modify_tm_license_data' ), -997 );

		$this->license_remote_sync();

		$this->license_expire_check();

		$this->license_to_day_expire_check();

		$this->maybe_theme_core_license_exist();

		$this->maybe_modify_license_data();

		 $this->maybe_site_not_activated();

	}

	/**
	 * Proccesing subscribe form ajax
	 *
	 * @return void
	 */
	public function jet_license_action() {

		$data = ( ! empty( $_POST['data'] ) ) ? wp_unslash( $_POST['data'] ) : false;

		if ( ! $data ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'code'    => 'server_error',
					'message' => $this->sys_messages['server_error'],
					'data'    => [],
				)
			);
		}

		if ( ! isset( $data['nonce'] ) || ! wp_verify_nonce( $data['nonce'], 'jet-dashboard' ) ) {
			wp_send_json( [
				'status'  => 'error',
				'code'    => 'server_error',
				'message' => __( 'Page has expired. Please reload this page.', 'jet-dashboard' ),
				'data'    => [],
			] );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( [
				'status'  => 'error',
				'code'    => 'server_error',
				'message' => __( 'Sorry, you are not allowed to manage license data on this site.', 'jet-dashboard' ),
				'data'    => [],
			] );
		}

		$license_action = isset( $data['action'] ) ? sanitize_key( $data['action'] ) : '';

		if ( ! in_array( $license_action, array( 'activate', 'deactivate' ), true ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'code'    => 'action_not_found',
					'message' => __( 'Action not found.', 'jet-dashboard' ),
					'data'    => [],
				)
			);
		}

		$license_key = isset( $data['license'] ) ? sanitize_text_field( $data['license'] ) : '';

		if ( empty( $license_key ) && isset( $data['plugin'] ) ) {
			$license_key = Utils::get_plugin_license_key( plugin_basename( sanitize_text_field( $data['plugin'] ) ) );
		}

		$responce = $this->license_action_query( $license_action . '_license', $license_key );

		$responce_data = [];

		if ( ! is_array( $responce ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'code'    => 'server_error',
					'message' => $this->sys_messages['server_error'],
					'data'    => [],
				)
			);
		}

		if ( 'error' === $responce['status'] ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'code'    => 'server_error',
					'message' => $responce['message'],
					'data'    => [],
				)
			);
		}

		if ( isset( $responce['data'] ) ) {
			$responce_data = $responce['data'];
		}

		if ( 'activate' === $license_action && empty( $responce_data ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'code'    => 'server_error',
					'message' => 'Server Error. Try again later',
					'data'    => [],
				)
			);
		}

		$responce_data = $this->maybe_modify_responce_data( $responce_data );
		$responce_data = $this->maybe_modify_tm_responce_data( $responce_data );

		switch ( $license_action ) {
			case 'activate':
				$this->update_license_list( $license_key, $responce_data );
			break;

			case 'deactivate':
				$license_list = Utils::get_license_data( 'license-list', [] );
				unset( $license_list[ $license_key ] );
				Utils::set_license_data( 'license-list', $license_list );
			break;
		}

		$responce_data['license_key'] = $license_key;

		delete_site_transient( $this->license_remote_sync_key );
		delete_site_transient( 'jet_dashboard_license_expire_check' );
		set_site_transient( 'update_plugins', null );

		wp_send_json(
			array(
				'status'  => 'success',
				'code'    => $responce['code'],
				'message' => $responce['message'],
				'data'    => $responce_data,
			)
		);
	}

	/**
	 * @param $responce
	 * @return array|mixed
	 */
	public function maybe_modify_responce_data( $responce = array() ) {

		if ( empty( $responce ) ) {
			return $responce;
		}

		if ( isset( $responce['sites'] ) && ! empty( $responce['sites'] ) ) {
			$responce['sites'] = [];
		}

		return $responce;
	}

	/**
	 * [maybe_tm_modify_data description]
	 * @param  array  $responce [description]
	 * @return [type]           [description]
	 */
	public function maybe_modify_tm_responce_data( $responce = array() ) {

		if ( empty( $responce ) ) {
			return $responce;
		}

		if ( ! isset( $responce['type'] ) ) {
			return $responce;
		}

		if ( 'tm' === $responce['type'] ) {

			$responce_plugins = isset( $responce['plugins'] ) && is_array( $responce['plugins'] ) ? $responce['plugins'] : array();

			$user_plugins = Dashboard::get_instance()->plugin_manager->get_user_plugins();

			$tm_plugin_list = array();

			foreach ( $responce_plugins as $plugin_file => $plugin_data ) {

				if ( array_key_exists( $plugin_file, $user_plugins ) ) {
					$tm_plugin_list[ $plugin_file ] = $plugin_data;
				}
			}

			if ( ! empty( $tm_plugin_list ) ) {
				$responce['plugins'] = $tm_plugin_list;

				return $responce;
			}
		}

		return $responce;
	}

	/**
	 * [update_license_list description]
	 * @param  boolean $responce [description]
	 * @return [type]            [description]
	 */
	public function update_license_list( $license_key = '', $responce = false ) {

		if ( empty( $responce ) || ! is_array( $responce ) || empty( $responce['license'] ) ) {
			return false;
		}

		$license_list = Utils::get_license_data( 'license-list', [] );

		$license_list[ $responce['license'] ] = array(
			'licenseStatus'  => 'active',
			'licenseKey'     => $responce['license'],
			'licenseDetails' => $responce,
		);

		Utils::set_license_data( 'license-list', $license_list );
	}

	/**
	 * Remote request to updater API.
	 *
	 * @since  1.0.0
	 * @return array|bool
	 */
	public function license_action_query( $action = '', $license = '' ) {

		$query_url = add_query_arg(
			array(
				'action'   => $action,
				'license'  => $license,
				'site_url' => urlencode( Utils::get_site_url() ),
			),
			Utils::get_api_url()
		);

		$response = wp_remote_get( $query_url, array(
			'timeout' => 60,
		) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != '200' ) {
			return false;
		}

		$response = json_decode( $response['body'], true );

		if ( ! is_array( $response ) ) {
			return false;
		}

		return $response;
	}

	/**
	 * Sync stored license data with remote API.
	 *
	 * @param bool $force Force sync even when transient exists.
	 * @return int Number of synced licenses.
	 */
	public function license_remote_sync( $force = false ) {

		if ( ! $force && get_site_transient( $this->license_remote_sync_key ) ) {
			return 0;
		}

		$license_list = Utils::get_license_data( 'license-list', array() );

		if ( empty( $license_list ) || ! is_array( $license_list ) ) {
			set_site_transient( $this->license_remote_sync_key, 'true', $this->get_license_remote_sync_interval() );

			return 0;
		}

		$synced_count = 0;

		foreach ( $license_list as $license_key => $license_data ) {
			$license_key = ! empty( $license_data['licenseKey'] ) ? $license_data['licenseKey'] : $license_key;
			$license_key = sanitize_text_field( $license_key );
			$license_details = isset( $license_data['licenseDetails'] ) && is_array( $license_data['licenseDetails'] ) ? $license_data['licenseDetails'] : array();

			if ( empty( $license_key ) ) {
				continue;
			}

			if ( isset( $license_details['expire'] ) && Utils::is_lifetime_license_expire( $license_details['expire'] ) ) {
				continue;
			}

			$sync_action = apply_filters( 'jet_dashboard_license_sync_action', 'activate_license', $license_key, $license_data );
			$response    = $this->license_action_query( $sync_action, $license_key );

			if ( ! is_array( $response ) || ( isset( $response['status'] ) && 'error' === $response['status'] ) || empty( $response['data'] ) || ! is_array( $response['data'] ) ) {
				continue;
			}

			$remote_data = $this->prepare_remote_license_data( $response['data'], $license_key, $license_data );

			if ( empty( $remote_data ) ) {
				continue;
			}

			$stored_key = ! empty( $remote_data['license'] ) ? $remote_data['license'] : $license_key;

			if ( $stored_key !== $license_key && isset( $license_list[ $license_key ] ) ) {
				unset( $license_list[ $license_key ] );
			}

			$license_list[ $stored_key ] = array(
				'licenseStatus'  => Utils::license_expired_check( $remote_data['expire'] ) ? 'expired' : 'active',
				'licenseKey'     => $stored_key,
				'licenseDetails' => $remote_data,
			);

			$synced_count++;
		}

		if ( $synced_count ) {
			Utils::set_license_data( 'license-list', $license_list );
			delete_site_transient( 'jet_dashboard_license_expire_check' );
			set_site_transient( 'update_plugins', null );
		}

		set_site_transient( $this->license_remote_sync_key, 'true', $this->get_license_remote_sync_interval() );

		return $synced_count;
	}

	/**
	 * Prepare remote license data before saving it locally.
	 *
	 * @param array  $remote_data Remote license data.
	 * @param string $license_key License key.
	 * @param array  $license_data Existing local license data.
	 * @return array
	 */
	public function prepare_remote_license_data( $remote_data = array(), $license_key = '', $license_data = array() ) {

		if ( empty( $remote_data ) || ! is_array( $remote_data ) ) {
			return array();
		}

		if ( empty( $remote_data['license'] ) ) {
			$remote_data['license'] = $license_key;
		}

		if ( empty( $remote_data['expire'] ) ) {
			return array();
		}

		$remote_data = $this->maybe_modify_responce_data( $remote_data );

		if ( ! isset( $remote_data['site_url'] ) ) {
			$remote_data['site_url'] = isset( $license_data['licenseDetails']['site_url'] ) ? $license_data['licenseDetails']['site_url'] : strtolower( Utils::get_site_url() );
		}

		return $remote_data;
	}

	/**
	 * Get remote license sync interval.
	 *
	 * @return int
	 */
	public function get_license_remote_sync_interval() {
		return absint( apply_filters( 'jet_dashboard_license_remote_sync_interval', DAY_IN_SECONDS ) );
	}

	/**
	 * [license_expire_check description]
	 * @return [type] [description]
	 */
	public function license_expire_check() {

		$jet_dashboard_license_expire_check = get_site_transient( 'jet_dashboard_license_expire_check' );

		if ( $jet_dashboard_license_expire_check ) {
			return false;
		}

		Utils::license_data_expire_sync();

		set_site_transient( 'jet_dashboard_license_expire_check', 'true', HOUR_IN_SECONDS * 12 );
	}

	/**
	 * [license_to_day_expire_check description]
	 * @return [type] [description]
	 */
	public function license_to_day_expire_check() {
		$primary_license_data = $this->get_primary_license_data();

		$expire = $primary_license_data['expire'];

		if ( Utils::license_expired_check( $expire, 30 ) ) {
			Dashboard::get_instance()->notice_manager->register_notice( array(
				'id'      => '30days-to-license-expire',
				'page'    => 'welcome-page',
				'preset'  => 'alert',
				'type'    => 'danger',
				'title'   => esc_html__( 'License expires soon!', 'jet-dashboard' ),
				'message' => esc_html__( 'Your primary license will expire in 30 days. You can renew you current licese in your crocoblock account', 'jet-dashboard' ),
				'buttons' => array(
					array(
						'label' => esc_html__( 'Go to account', 'jet-dashboard' ),
						'url'   => 'https://account.crocoblock.com/',
						'style' => 'accent',
					)
				),
			) );
		}
	}

	/**
	 * @return bool
	 */
	public function is_sublicense() {
		$primary_license_data = $this->get_primary_license_data();

		if ( isset( $primary_license_data['is_sublicense'] ) && $primary_license_data['is_sublicense'] ) {
			return true;
		}

		return false;
	}

	/**
	 * [get_primary_license_data description]
	 * @return [type] [description]
	 */
	public function get_primary_license_data() {
		$license_list = array_values( Utils::get_license_list() );
		$filtered_license_type = array();
		$filtered = array();

		$license_type_map = array(
			'crocoblock',
			'tm',
			'envato',
		);

		foreach ( $license_type_map as $key => $license_type ) {
			$filtered_license_type = array_filter( $license_list, function( $license_data ) use ( $license_type ) {
				$license_details = isset( $license_data['licenseDetails'] ) ? $license_data['licenseDetails'] : array();

				return isset( $license_details['type'] ) && $license_details['type'] === $license_type;
			} );

			if ( ! empty( $filtered_license_type ) ) {
				break;
			}
		}

		$product_category_map = array(
			'lifetime',
			'all-inclusive',
			'plugin-set',
			'theme-plugin-bundle',
			'single-plugin',
		);

		foreach ( $product_category_map as $key => $product_category ) {
			$filtered = array_filter( $filtered_license_type, function( $license_data ) use ( $product_category ) {
				$license_details = isset( $license_data['licenseDetails'] ) ? $license_data['licenseDetails'] : array();

				$license_product = isset( $license_details['product_category'] ) ? $license_details['product_category'] : '';

				return $license_product === $product_category || empty( $license_product );
			} );

			if ( ! empty( $filtered ) ) {
				break;
			}
		}

		if ( empty( $filtered ) ) {
			return array(
				'key'     => '',
				'product' => '',
				'type'    => '',
				'expire'  => '',
				'is_sublicense' => false,
			);
		}

		$filtered_license = array_values( $filtered )[0];

		if ( ! isset( $filtered_license['licenseDetails'] ) ) {
			return array(
				'key'     => '',
				'product' => '',
				'type'    => '',
				'expire'  => '',
				'is_sublicense' => false,
			);
		}

		return array(
			'key'     => isset( $filtered_license['licenseDetails']['license'] ) ? $filtered_license['licenseDetails']['license'] : '',
			'product' => isset( $filtered_license['licenseDetails']['product_category'] ) ? $filtered_license['licenseDetails']['product_category'] : '',
			'type'    => isset( $filtered_license['licenseDetails']['type'] ) ? $filtered_license['licenseDetails']['type'] : '',
			'expire'  => isset( $filtered_license['licenseDetails']['expire'] ) ? $filtered_license['licenseDetails']['expire'] : '',
			'is_sublicense'  => isset( $filtered_license['licenseDetails']['is_sublicense'] ) ? $filtered_license['licenseDetails']['is_sublicense'] : false,
		);
	}

	/**
	 * @return void
	 */
	public function maybe_modify_license_data() {
		$license_list = Utils::get_license_data( 'license-list', array() );

		if ( empty( $license_list ) ) {
			return;
		}

		foreach ( $license_list as $license_key => $license_data ) {

			if ( ! isset( $license_data['licenseDetails'] ) ) {
				continue;
			}

			$license_details = $license_data['licenseDetails'];

			if ( isset( $license_details['sites'] ) ) {
				$license_list[ $license_key ]['licenseDetails']['sites'] = [];
			}

			if ( ! isset( $license_details['site_url'] ) ) {
				$license_list[ $license_key ]['licenseDetails']['site_url'] = strtolower( Utils::get_site_url() );
			}
		}

		Utils::set_license_data( 'license-list', $license_list );

	}

	/**
	 * [maybe_theme_core_license_exist description]
	 * @return [type] [description]
	 */
	public function maybe_theme_core_license_exist() {

		$jet_theme_core_key = get_option( 'jet_theme_core_license', false );

		if ( ! $jet_theme_core_key ) {
			return false;
		}

		$jet_theme_core_license_sync = get_option( 'jet_theme_core_sync', 'false' );

		if ( filter_var( $jet_theme_core_license_sync, FILTER_VALIDATE_BOOLEAN ) ) {
			return false;
		}

		$license_list = Utils::get_license_data( 'license-list', [] );

		if ( array_key_exists( $jet_theme_core_key, $license_list ) ) {
			return false;
		}

		$responce = $this->license_action_query( 'activate_license', $jet_theme_core_key );

		if ( ! is_array( $responce ) ) {
			return false;
		}

		$responce_data = isset( $responce['data'] ) ? $responce['data'] : [];

		$license_list[ $jet_theme_core_key ] = array(
			'licenseStatus'  => 'active',
			'licenseKey'     => $jet_theme_core_key,
			'licenseDetails' => $responce_data,
		);

		update_option( 'jet_theme_core_sync', 'true' );

		if ( 'error' === $responce['status'] ) {

			Utils::set_license_data( 'license-list', $license_list );

			return false;
		}

		Utils::set_license_data( 'license-list', $license_list );
	}

	/**
	 * [maybe_site_not_activated description]
	 * @return [type] [description]
	 */
	public function maybe_site_not_activated() {
		$license_sites = Utils::get_license_site_url();

		if ( empty( $license_sites ) ) {
			Dashboard::get_instance()->notice_manager->register_notice( array(
				'id'      => 'license-not-activated',
				'page'    => [ 'license-page', 'welcome-page' ],
				'preset'  => 'alert',
				'type'    => 'danger',
				'title'   => esc_html__( 'The site is not activated', 'jet-dashboard' ),
				'message' => esc_html__( 'Your license key is not activated for this site. Go to the license manager and activate your key', 'jet-dashboard' ),
				'buttons' => array(
					array(
						'label' => esc_html__( 'Activate', 'jet-dashboard' ),
						'url'   => add_query_arg(
							array(
								'page'    => 'jet-dashboard-license-page',
								'subpage' => 'license-manager'
							),
							admin_url( 'admin.php' )
						),
						'style' => 'accent',
					)
				),
			) );

			return;
		}

		if ( ! Utils::is_site_activated() ) {

			Dashboard::get_instance()->notice_manager->register_notice( array(
				'id'      => 'site-not-activated',
				'page'    => [ 'license-page', 'welcome-page' ],
				'preset'  => 'alert',
				'type'    => 'danger',
				'title'   => esc_html__( 'This site is not activated', 'jet-dashboard' ),
				'message' => esc_html__( 'This site is not activated for this license. Go to the license manager deactivate your license and activate your key again', 'jet-dashboard' ),
				'buttons' => array(
					array(
						'label' => esc_html__( 'Activate', 'jet-dashboard' ),
						'url'   => add_query_arg(
							array(
								'page'    => 'jet-dashboard-license-page',
								'subpage' => 'license-manager'
							),
							admin_url( 'admin.php' )
						),
						'style' => 'accent',
					)
				),
			) );
		}
	}

	/**
	 * [maybe_tm_license_pluging_sync description]
	 * @return [type] [description]
	 */
	public function maybe_modify_tm_license_data() {

		$is_modify_tm_license_data = get_option( 'jet_is_modify_tm_license_data', 'false' );

		if ( filter_var( $is_modify_tm_license_data, FILTER_VALIDATE_BOOLEAN ) ) {
			return false;
		}

		$license_list = Utils::get_license_data( 'license-list', [] );

		if ( $license_list && ! empty( $license_list ) ) {

			$user_plugins = Dashboard::get_instance()->plugin_manager->get_user_plugins();

			foreach ( $license_list as $license_key => $license_data ) {

			$license_details = isset( $license_data['licenseDetails'] ) ? $license_data['licenseDetails'] : array();

			if ( ! empty( $license_details ) && isset( $license_details['type'] ) && 'tm' === $license_details['type'] ) {
				$license_plugins = isset( $license_details['plugins'] ) && is_array( $license_details['plugins'] ) ? $license_details['plugins'] : array();

					$tm_plugin_list = array();

					foreach ( $license_plugins as $plugin_file => $plugin_data ) {

						if ( array_key_exists( $plugin_file, $user_plugins ) ) {
							$tm_plugin_list[ $plugin_file ] = $plugin_data;
						}
					}

					$license_list[ $license_key ]['licenseDetails']['plugins'] = $tm_plugin_list;
				}
			}

			Utils::set_license_data( 'license-list', $license_list );

			update_option( 'jet_is_modify_tm_license_data', 'true' );
		}
	}

}
