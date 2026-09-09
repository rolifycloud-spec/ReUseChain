<?php
 /**
 * Plugin Name: Escrow Payments on WooCoomerce
 * Plugin URI: https://www.escrow.com/plugins/woocommerce
 * Description: Take secure escrow payments on your store using Escrow.com.
 * Version: 3.0.1
 * Author: Escrow.com
 * Author URI: https://www.escrow.com/
 * Developer: Michael Liedtke
 * Text Domain: woo-escrow-gateway
 * Domain Path: /languages
 *
 * WC requires at least: 8.5.0
 * WC tested up to: 9.9.5
 *
 * Copyright: @ 2021 Escrow.com
 * License: GNU General Public License v2.0 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Ensure plugin cannot be accessed outside of WordPress.
defined('ABSPATH') or exit;

// Plugin version
define('WC_ESCROW_PLUGIN_VERSION', '3.0.0');

// Make sure WooCommerce is active.
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

// Declaring HPOS compatibility and Checkout Blocks incompatibility
add_action('before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, false);
	}
} );

// Manage active and inactive multi-vendor plugins
add_action('admin_footer', 'disable_vs_option_js');
function disable_vs_option_js() {
    if (isset($_GET['page']) && $_GET['page'] == 'wc-settings') {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('#woocommerce_escrow_gateway_vs_option option:contains("(Inactive)")').prop('disabled', true);
            });
        </script>
        <?php
    }
}

/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways - All available WC gateways.
 * @return array $gateways - All WC gateways plus the Escrow.com gateway.
 */
add_filter('woocommerce_payment_gateways', 'wc_escrow_add_to_gateways');
function wc_escrow_add_to_gateways($gateways) {
    $gateways[] = 'WC_Gateway_Escrow';
    return $gateways;
}

/**
 * Filter the WC Available Gateways
 *
 * @since 1.0.3
 * @param array $gateways - All WC gateways.
 * @return array $gateways - All WC gateways minus the Escrow.com gateway under certain conditions.
 */
add_filter('woocommerce_available_payment_gateways', 'wc_escrow_filter_gateways', 1);
function wc_escrow_filter_gateways($gateways) {

    if (!is_admin()) {
        // Get the setting that will tell us how to handle the Escrow.com payment option visibility.
        $settings = new WC_Gateway_Escrow();
        $enable_when = $settings->get_enable_when();

        // Conditionally remove the Escrow.com payment option according to settings and contents of cart.
        if ('all_items' === $enable_when) {
            foreach(WC()->cart->get_cart() as $cart_item) {
                $product_id = $cart_item['product_id'];
                $product = new WC_Product($product_id);
                $escrowable = $product->get_attribute('escrowable');
                if ('true' !== strtolower($escrowable)) {
                    unset($gateways['escrow_gateway']);
                    break;
                }
            }
        }
    }

    // Return list of payment options that should be rendered on the checkout page.
    return $gateways;
}

/*
 * Update the order receipt to render Escrow.com specific instructions and links so the user may easily
 * continue the payment process on the Escrow.com platform.
 *
 * @since 1.0.0
 * @param string $str - Default thank you message.
 * @param WC_Order $order - WooCommerce order object.
 * @return string $new_str - Complete thank you message including links to Escrow.com.
 */
add_filter('woocommerce_thankyou_order_received_text', 'wc_escrow_gateway_change_order_received_text', 10, 2);
function wc_escrow_gateway_change_order_received_text($str, $order) {
    $order = wc_get_order( $order->get_id() );
    $escrow_api_url = $order->get_meta('EscrowApiUrl');
    if (!isset($escrow_api_url) or '' === $escrow_api_url) {
        return $str;
    }
    $buyer_email             = $order->get_billing_email();
    $order_name              = get_bloginfo('title') . ' order ' . $order->get_order_number();
    $escrow_trx_ids          = $order->get_meta('EscrowTrxIds');
    $escrow_buyer_next_steps = $order->get_meta('EscrowBuyerNextSteps');
    $escrow_trx_links        = '<div id="escrow-trx-links">';
    $index = 0;
    $escrow_trx_ids_arr = explode(',', $escrow_trx_ids);
    foreach(explode(',', $escrow_buyer_next_steps) as $escrow_buyer_next_step) {
        $escrow_trx_id = $escrow_trx_ids_arr[$index];
        $escrow_cta_text = 'Make Payment';
        if (sizeof($escrow_trx_ids_arr) > 1) {
            $escrow_cta_text .= ' for '
            . $order_name
            . ' (Escrow.com trx '
            . $escrow_trx_id
            . ')';
        }
        $escrow_cta = '<a href="'
            . $escrow_buyer_next_step
            . '" class="button alt" rel="nofollow" target="_blank">'
            . $escrow_cta_text
            . '</a>';
        $escrow_trx_links .= '<div>' . $escrow_cta . '<br /><br /></div>';
        $index++;
    }
    $escrow_trx_links .= '</div>';
    $trx_text = $index > 1 ? 'buttons for each of the transactions' : '<b>Make Payment</b> button';
    $new_str = $str . ' Your next step in this process is to submit payment to <b>Escrow.com</b>.'
        . $escrow_trx_links
        . 'Please click on the '
        . $trx_text
        . ' above. You will be guided through the payment process.';
    return $new_str;
}

/**
 * Handle plugin activation.
 *
 * @since 1.3.0
 */
add_action('activated_plugin', 'detect_plugin_activation', 10, 2);
function detect_plugin_activation($plugin, $network_activation) {
    if (strpos($plugin, 'woo-escrow-gateway') !== false) {
        customer_service_notification('activate');
    }
}

/**
 * Handle plugin deactivation.
 *
 * @since 1.3.0
 */
add_action('deactivated_plugin', 'detect_plugin_deactivation', 10, 2);
function detect_plugin_deactivation($plugin, $network_activation) {
    if (strpos($plugin, 'woo-escrow-gateway') !== false) {
        customer_service_notification('deactivate');
    }
}

/**
 * Notify Escrow.com of plugin status to facilitate customer support.
 *
 * @param string $event - The relevant event that was triggered.
 *
 * @since 1.3.0
 */
function customer_service_notification($event) {

    // Attempt notification.
    try {

        // Get logged in user.
        $current_user = wp_get_current_user();

        // Get the user agent for this plugin.
        $user_agent = "EscrowPlugin/WooCommerce/" . WC_ESCROW_PLUGIN_VERSION . " WooCommerce/" . WC()->version . " WordPress/" . get_bloginfo('version') . " PHP/" . PHP_VERSION;

        // Build request.
        $request = array(
            'url'            => get_home_url(),
            'email'          => $current_user->user_email,
            'event'          => $event,
            'plugin_name'    => 'WooCommerce',
            'plugin_details' => $user_agent
        );

        // Send the notification to production.
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.escrow.com/internal/plugin_event',
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_SSL_VERIFYPEER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode($request)
        ));

        // Make the call to the Escrow.com API.
        curl_exec($curl);

        // Close the curl command as we are done using it.
        curl_close($curl);

    } catch (Exception $e) {
        // If this failed, continue without interruption.
    }
}

/*
 * Initializes a new plugin class for the Escrow.com gateway.
 *
 * @since 1.0.0
 */
add_action('plugins_loaded', 'wc_escrow_gateway_init', 11);
function wc_escrow_gateway_init() {

    class WC_Gateway_Escrow extends WC_Payment_Gateway {

        // Define API Settings.
        private $as_escrow_email          = "";
        private $as_escrow_api_key        = "";
        private $as_escrow_api_url        = "";

        // Define Checkout Settings.
        private $cs_enable_when           = "";

        // Define Transaction Settings.
        private $ts_currency              = "";
        private $ts_escrow_fee_payer      = "";
        private $ts_inspection_period     = "";
        private $ts_tax_options           = "";
        private $ts_transaction_type      = "";

        // Define Vendor Settings.
        private $vs_option                = "";
        private $vs_commission            = "";
        private $vs_commission_max        = "";
        private $vs_commission_max_target = "";

        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            // Initialize the basics.
            $this->id                 = 'escrow_gateway';
            $this->icon               = apply_filters('woocommerce_offline_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __('Escrow.com', 'wc-gateway-escrow');
            $this->method_description = __('This page allows you to configure your Escrow.com plugin.<br/>To use this plugin, you will need to <a href="https://www.escrow.com/integrations/signup" target="_blank">sign up</a> for an Escrow.com account and <a href="https://www.escrow.com/integrations/portal/api" target="_blank">get an Escrow.com API key</a>.<br />For more information, please go to <a href="https://www.escrow.com/plugins/woocommerce" target="_blank">www.escrow.com/plugins/woocommerce</a>.');

            // We must use these property names to configure the plugin for the WC checkout page.
            $this->title              = $this->get_option('title');
            $this->description        = $this->get_option('description');

            // Initialize the values configured from the Escrow.com settings page.
            $this->as_escrow_email          = $this->get_option('as_escrow_email', $this->as_escrow_email);
            $this->as_escrow_api_key        = $this->get_option('as_escrow_api_key', $this->as_escrow_api_key);
            $this->as_escrow_api_url        = $this->get_option('as_escrow_api_url', $this->as_escrow_api_url);
            $this->cs_enable_when           = $this->get_option('cs_enable_when', $this->cs_enable_when);
            $this->ts_currency              = $this->get_option('ts_currency', $this->ts_currency);
            $this->ts_escrow_fee_payer      = $this->get_option('ts_escrow_fee_payer', $this->ts_escrow_fee_payer);
            $this->ts_inspection_period     = $this->get_option('ts_inspection_period', $this->ts_inspection_period);
            $this->ts_tax_options           = $this->get_option('ts_tax_options', $this->ts_tax_options);
            $this->ts_transaction_type      = $this->get_option('ts_transaction_type', $this->ts_transaction_type);
            $this->vs_option                = $this->get_option('vs_option', $this->vs_option);
            $this->vs_commission            = $this->get_option('vs_commission', $this->vs_commission);
            $this->vs_commission_max        = $this->get_option('vs_commission_max', $this->vs_commission_max);
            $this->vs_commission_max_target = $this->get_option('vs_commission_max_target', $this->vs_commission_max_target);

            // Without this code, changes made to the Escrow.com settings page within the WooCommerce Settings area will not persist.
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ));

            // Load the settings page.
            $this->init_form_fields();
            $this->init_settings();
        }

        /*
         * Returns setting indicating when to display Escrow.com payment option on checkout page.
         *
         * @since 1.0.3
         */
        public function get_enable_when() {
            return $this->cs_enable_when;
        }

        /*
         * Includes the Escrow.com icon next to the Escrow.com checkout option text and
         * anywhere else that the payment icon is displayed.
         *
         * @since 1.1.1
         */
        public function get_icon() {
            $icons_str = '<img src="https://www.escrow.com/build/images/favicons/favicon-32x32.png" alt="Escrow.com Icon" />';
            return apply_filters('woocommerce_gateway_icon', $icons_str, $this->id);
        }

        /**
         * Initialize Escrow.com gateway form fields.
         */
        public function init_form_fields() {

            // Adding support for other major multi-vendor plugins
            $known_multi_vendor_plugins = [
                'WC Vendors' => 'WCV_Vendors',
                'Dokan' => 'WeDevs_Dokan',
                'WCFM Marketplace' => 'WCFMmp',
            ];

            $opt_vendor_plugins = ['none' => '-- None --'];

            foreach ($known_multi_vendor_plugins as $label => $class_name) {
                if (class_exists($class_name) || function_exists($class_name)) {
                    $opt_vendor_plugins[$class_name] = $label;
                }
                else {
                    $opt_vendor_plugins[$class_name] = $label." (Inactive)";
                }
            }

            $this->form_fields = apply_filters('wc_escrow_form_fields', array(

                'as_heading' => array(
                    'title'       => __('API Settings', 'wc-gateway-escrow'),
                    'type'        => 'title',
                    'description' => ''
                ),
                'as_escrow_email' => array(
                    'title' => __('Escrow Email', 'wc-gateway-escrow'),
                    'type' => 'text',
                    'description' => __('Enter the email address you use to login into Escrow.com.', 'wc-gateway-escrow'),
                    'default' => __('', 'wc-gateway-escrow'),
                    'desc_tip' => true
                ),
                'as_escrow_api_key' => array(
                    'title' => __('Escrow API Key', 'wc-gateway-escrow'),
                    'type' => 'password',
                    'description' => __('Enter your Escrow.com API Key for the configured environment. If you have configured the plugin to call production, use a production API Key. If you have configured the plugin to call sandbox, use a sandbox API Key.', 'wc-gateway-escrow'),
                    'default' => __('', 'wc-gateway-escrow'),
                    'desc_tip' => true
                ),
                'as_escrow_api_url' => array(
                    'title' => __('Escrow API URL', 'wc-gateway-escrow'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Select the version of the Escrow.com API that you wish to use. URLs with api.escrow.com are for production use. URLs with api.escrow-sandbox.com are for testing. Make sure you update the Escrow Email and Escrow API Key to match the selected environment.', 'wc-gateway-escrow'),
                    'default' => __('https://api.escrow.com/', 'wc-gateway-escrow'),
                    'desc_tip' => true,
                    'options' => array(
                        'https://api.escrow.com/' => 'https://api.escrow.com/',
                        'https://api.escrow-sandbox.com/' => 'https://api.escrow-sandbox.com/'
                    )
                ),
                'cs_heading' => array(
                    'title'       => __('Checkout Settings', 'wc-gateway-escrow'),
                    'type'        => 'title',
                    'description' => ''
                ),
                'enabled' => array(
                    'title' => __('Enable/Disable', 'wc-gateway-escrow'),
                    'type' => 'checkbox',
                    'label' => __('Enable Escrow.com Payment Method', 'wc-gateway-escrow'),
                    'description' => __('Enable/disable the Escrow.com Payment Method on your checkout page.', 'wc-gateway-escrow'),
                    'default' => 'no',
                    'desc_tip' => true
                ),
                'cs_enable_when' => array(
                    'title' => __('Enable When', 'wc-gateway-escrow'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Determines whether to show the Escrow.com payment option on the checkout page. \'Enable Always\' shows the Escrow.com payment option at all times. \'Enable Only When All Items Escrowable\' shows the Escrow.com payment option when all items in the cart have the \'escrowable\' custom product attribute set to \'true\'.', 'wc-gateway-escrow'),
                    'default' => __('always', 'wc-gateway-escrow'),
                    'desc_tip' => true,
                    'options' => array(
                        'always' => 'Enable Always',
                        'all_items' => 'Enable Only When All Items Escrowable'
                    )
                ),
                'title' => array(
                    'title' => __('Title', 'wc-gateway-escrow'),
                    'type' => 'text',
                    'description' => __('Payment method title that the customer will see on your checkout page.', 'wc-gateway-escrow'),
                    'default' => __('Escrow.com Secure Payment', 'wc-gateway-escrow'),
                    'desc_tip' => true
                ),
                'description' => array(
                    'title' => __('Description', 'wc-gateway-escrow'),
                    'type' => 'textarea',
                    'css' => 'height:100px; width:400px;',
                    'description' => __('Payment method description that the customer will see on your checkout page.', 'wc-gateway-escrow'),
                    'default' => __('Escrow.com is the safest way to pay online. When you place your order, an account will be created on Escrow.com through which you will be able to complete payment. Simply click on the link in the order receipt to view your payment instructions.', 'wc-gateway-escrow'),
                    'desc_tip' => true
                ),
                'ts_heading' => array(
                    'title'       => __('Transaction Settings', 'wc-gateway-escrow'),
                    'type'        => 'title',
                    'description' => ''
                ),
                'ts_currency' => array(
                    'title' => __('Currency', 'wc-gateway-escrow'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Select the currency you wish to use for all transactions created via this plugin.', 'wc-gateway-escrow'),
                    'default' => __('usd', 'wc-gateway-escrow'),
                    'desc_tip' => true,
                    'options' => array(
                        'usd' => 'USD',
                        'euro' => 'EUR',
                        'aud' => 'AUD',
                        'cad' => 'CAD',
                        'gbp' => 'GBP'
                    )
                ),
                'ts_escrow_fee_payer' => array(
                    'title' => __('Escrow Fee Paid By', 'wc-gateway-escrow'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Select whether the escrow fee is to be paid by the buyer, the seller (that is you), or split evenly between the two.', 'wc-gateway-escrow'),
                    'default' => __('seller', 'wc-gateway-escrow'),
                    'desc_tip' => true,
                    'options' => array(
                        'buyer' => 'Buyer',
                        'seller' => 'Seller',
                        'splitbs' => 'Split 50/50'
                    )
                ),
                'ts_inspection_period' => array(
                    'title' => __('Inspection Period', 'wc-gateway-escrow'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Select the inspection period for all transactions created via this plugin.', 'wc-gateway-escrow'),
                    'default' => __('3', 'wc-gateway-escrow'),
                    'desc_tip' => true,
                    'options' => array(
                        '1' => '1 day',
                        '2' => '2 days',
                        '3' => '3 days',
                        '4' => '4 days',
                        '5' => '5 days',
                        '6' => '6 days',
                        '7' => '7 days',
                        '8' => '8 days',
                        '9' => '9 days',
                        '10' => '10 days',
                        '11' => '11 days',
                        '12' => '12 days',
                        '13' => '13 days',
                        '14' => '14 days',
                        '15' => '15 days',
                        '16' => '16 days',
                        '17' => '17 days',
                        '18' => '18 days',
                        '19' => '19 days',
                        '20' => '20 days'
                    )
                ),
                'ts_tax_options' => array(
                    'title' => __('Tax Options', 'wc-gateway-escrow'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Select whether the tax total in the shopping cart is to be included in the Escrow.com transaction. This does not apply to Domain Name transactions.', 'wc-gateway-escrow'),
                    'default' => __('exclude_tax', 'wc-gateway-escrow'),
                    'desc_tip' => true,
                    'options' => array(
                        'exclude_tax' => 'Exclude Tax (Default)',
                        'include_tax' => 'Include Tax as a Line Item',
                    )
                ),
                'ts_transaction_type' => array(
                    'title' => __('Transaction Type', 'wc-gateway-escrow'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Select the transaction type for all transactions created via this plugin.', 'wc-gateway-escrow'),
                    'default' => __('general_merchandise', 'wc-gateway-escrow'),
                    'desc_tip' => true,
                    'options' => array(
                        'domain_name' => 'Domain Name',
                        'general_merchandise' => 'General Merchandise',
                        'milestone' => 'Milestone'
                    )
                ),
                'vs_heading' => array(
                    'title'       => __('Vendor Settings', 'wc-gateway-escrow'),
                    'type'        => 'title',
                    'description' => ''
                ),
                'vs_option' => array(
                    'title' => __('Multi-Vendor Extension', 'wc-gateway-escrow'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Select the desired multi-vendor extension or \'-- None --\' if you do not have one installed.', 'wc-gateway-escrow'),
                    'default' => __('none', 'wc-gateway-escrow'),
                    'desc_tip' => true,
                    'options' => $opt_vendor_plugins
                ),
                'vs_commission' => array(
                    'title' => __('Commission %', 'wc-gateway-escrow'),
                    'type' => 'text',
                    'description' => __('Percent broker commission to be deducted from each vendor\'s proceeds. Only applies when a multi-vendor extension is selected.', 'wc-gateway-escrow'),
                    'default' => __('', 'wc-gateway-escrow'),
                    'desc_tip' => true
                ),
                'vs_commission_max' => array(
                    'title' => __('Commission $ Max', 'wc-gateway-escrow'),
                    'type' => 'text',
                    'description' => __('Maximum amount in dollars of broker commission to be deducted from each vendor\'s proceeds. Only applies when a multi-vendor extension is selected.', 'wc-gateway-escrow'),
                    'default' => __('', 'wc-gateway-escrow'),
                    'desc_tip' => true
                ),
                'vs_commission_max_target' => array(
                    'title' => __('Commission $ Max Target', 'wc-gateway-escrow'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Apply the max commission rule at either the item level or the vendor level.', 'wc-gateway-escrow'),
                    'default' => __('item', 'wc-gateway-escrow'),
                    'desc_tip' => true,
                    'options' => array(
                        'item' => 'Item',
                        'vendor' => 'Vendor'
                    )
                )
            ));
        }

        /**
         * Process the payment and return order receipt success page redirect.
         *
         * @param int $order_id - ID of order represented on checkout page.
         * @return array - Order receipt success array.
         */
        public function process_payment($order_id) {

            // Get the order from the given ID.
            $order = wc_get_order($order_id);

            // Process either the multi-vendor workflow or the single-vendor workflow.
            if ('none' !== $this->vs_option) {

                // Get an array of vendor IDs in the order.
                $vendors = $this->get_vendors($order);

                // Loop through vendors create one Escrow.com transaction per vendor.
                foreach ($vendors as $vendor_id) {

                    // Get the broker configured request for the order.
                    $request = $this->get_order_marketplace_scenario($order, $vendor_id);

                    // Call the Escrow.com pay endpoint to create an Escrow.com draft transaction.
                    $response = $this->call_escrow_api($request, 'integration/pay/2018-03-31');

                    // If we did not successfully call the API and retrieve the resulting Escrow.com transaction,
                    // then return so that the stand "Error processing cart." message is shown to the user.
                    if ($response == null) {
                        return [];
                    }

                    // Post process the order to store Escrow.com specific properties on it and to update the order status.
                    $this->post_process_order($order, $response);
                }

                // Reduce the stock levels and clear the cart.
                $this->post_process_cart($order_id);

                // If only one vendor is represented in the shopping cart, we can redirect the user directly to the
                // Escrow.com pay workflow. Otherwise, we need to show the intermediate order receipt page as the
                // user will need to pay once for each vendor.
                if (count($vendors) > 1) {

                    // Redirect the user to the order receipt page where we will display an Escrow.com pay button
                    // for each vendor.
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order)
                    );

                } else {

                    // Redirect the user to the Escrow.com pay page.
                    return array(
                        'result' => 'success',
                        'redirect' => $response->landing_page
                    );
                }

            } else {

                // Get the single-vendor non-broker request for the order.
                $request = $this->get_order_store_scenario($order);

                // Call the Escrow.com pay endpoint to create an Escrow.com draft transaction.
                $response = $this->call_escrow_api($request, 'integration/pay/2018-03-31');

                // If we did not successfully call the API, return so that the standard
                // "Error processing cart." message is shown to the user.
                if ($response == null) {
                    return [];
                }

                // Post process the order to store Escrow.com specific properties on it and to update the order status.
                $this->post_process_order($order, $response);

                // Reduce the stock levels and clear the cart.
                $this->post_process_cart($order_id);

                // Return redirect to the Escrow.com pay page.
                return array(
                    'result' => 'success',
                    'redirect' => $response->landing_page
                );
            }
        }

        /**
         * Retrieves array of vendor IDs in order.
         *
         * @param WC_Order $order - Order represented on checkout page.
         * @return array $distinct_vendors - Array of vendor IDs.
         */
        private function get_vendors($order) {

            // Get items from order.
            $order_items = $order->get_items();

            // Keep track of distinct vendor IDs with this array.
            $distinct_vendors = [];

            // Loop through items to extract distinct vendors.
            foreach($order_items as $item) {

                // Get vendor id from item.
                $vendor_id = get_post_field('post_author', $item['product_id']);

                // Add vendor ID to distinct vendors if it is not already present.
                if (!in_array($vendor_id, $distinct_vendors)) {
                    array_push($distinct_vendors, $vendor_id);
                }
            }

            // Return distinct array of vendor IDs.
            return $distinct_vendors;
        }

        /**
         * Makes a curl request to the Escrow.com API.
         *
         * @param array $request  - Request data to post to Escrow.com API.
         * @param array $endpoint - The endpoint to which the request is sent.
         * @return object - Escrow.com transaction response. Returns null if call failed.
         */
        private function call_escrow_api($request, $endpoint) {

            // Get properties relevant to the Escrow.com API call.
            $escrow_email   = $this->as_escrow_email;
            $escrow_api_key = $this->as_escrow_api_key;
            $escrow_api_url = $this->as_escrow_api_url;

            // Remove old path if present.
            $escrow_api_url = str_replace("2017-09-01/", "", $escrow_api_url);

            // Build user agent.
            $user_agent = "EscrowPlugin/WooCommerce/". WC_ESCROW_PLUGIN_VERSION . " WooCommerce/" . WC()->version . " WordPress/" . get_bloginfo('version') . " PHP/" . PHP_VERSION;

            // Initialize curl request with relevant properties and JSON representing complete transaction.
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $escrow_api_url . $endpoint,
                CURLOPT_POST => TRUE,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_SSL_VERIFYPEER => TRUE,
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => $escrow_email . ':' . $escrow_api_key,
                CURLOPT_USERAGENT => $user_agent,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
                CURLOPT_POSTFIELDS => json_encode($request)
            ));

            // Make the call to the Escrow.com API.
            $output = curl_exec($curl);

            // Get the HTTP status of the response.
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Close the curl command as we are done using it.
            curl_close($curl);

            // Initialize logger.
            $logger = wc_get_logger();

            // If we are in debug mode, write the response returned by the Escrow.com API to the default error log.
            if (true === WP_DEBUG) {
                $logger->debug("Escrow.com API request url: " . $escrow_api_url . $endpoint);
                $logger->debug("Escrow.com API request: " . PHP_EOL . json_encode($request, JSON_PRETTY_PRINT));
                $logger->debug("Escrow.com API response status: " . $status);
                $logger->debug("Escrow.com API response: " . PHP_EOL . $output);
            }

            // Decode JSON returned by Escrow.com API.
            $response = json_decode($output);

            // Log an error if we did not create a transaction successfully and return -1 to indicate that the API call failed;
            if (is_null($response)) {
                $logger->critical('Escrow.com API call failed to ' . $escrow_api_url . $endpoint . PHP_EOL . 'Status: ' . $status . PHP_EOL . 'Request:' . PHP_EOL . json_encode($request, JSON_PRETTY_PRINT));
                return null;
            }

            // Return Escrow.com transaction response.
            return $response;
        }

        /**
         * Persists relevant results from API call to WC order and updates order status to processing.
         *
         * @param WC_Order $order - Order represented on checkout page.
         * @param object $pay_response - info to allow buyer to pay for transaction.
         */
        private function post_process_order($order, $pay_response) {

            // Extract id of the newly created transaction.
            $escrow_trx_id = $pay_response->transaction_id;

            // Get the next step for the buyer in the transaction.
            $escrow_buyer_next_step = $pay_response->landing_page;

            // Update the order with the results of the API call. This information is useful both to the store administrator via the
            // order detail view in the admin panel and on the order receipt page (so the buyer may click through to the transaction
            // on Escrow.com).
            $existing_escrow_ids = $order->get_meta('EscrowTrxIds', true);
            $existing_escrow_buyer_next_steps = $order->get_meta('EscrowBuyerNextSteps', true);
            $order->update_meta_data('EscrowApiUrl', $this->as_escrow_api_url);
            $order->update_meta_data('EscrowTrxIds', strlen($existing_escrow_ids) > 0 ? $existing_escrow_ids . ',' . $escrow_trx_id : $escrow_trx_id);
            $order->update_meta_data('EscrowBuyerNextSteps', strlen($existing_escrow_buyer_next_steps) > 0 ? $existing_escrow_buyer_next_steps . ',' . $escrow_buyer_next_step : $escrow_buyer_next_step);
            $order->save();

            // Mark as processing as we are awaiting the payment.
            $order->update_status('processing', __('Awaiting confirmation of payment to Escrow.com.', 'wc-gateway-offline'));
        }

        /**
         * Reduces stock levels and clears cart as order has been successfully created at this point.
         *
         * @param int $order_id - ID of order represented on checkout page.
         */
        private function post_process_cart($order_id) {

            // Reduce stock levels.
            wc_reduce_stock_levels($order_id);

            // Empty the cart.
            WC()->cart->empty_cart();
        }

        /**
         * Gets transaction request that will be posted to the Escrow.com API.
         *
         * @param WC_Order $order - Order represented on checkout page.
         * @return array $json_request - JSON encoded data to post to Escrow.com API.
         */
        private function get_order_store_scenario($order) {

            // Get properties from the order.
            $order_email = $order->get_billing_email();
            $order_items = $order->get_items();
            $order_name  = get_bloginfo( 'title' ) . ' order ' . $order->get_order_number();

            // Build items array.
            $item_array = [];
            foreach ($order_items as $item_id => $item_data) {

                // Get the properties of the current item.
                $product       = wc_get_product($item_data['product_id']);
                $item_name     = $product->get_title();
                $item_quantity = wc_get_order_item_meta($item_id, '_qty', true);
                $item_total    = wc_get_order_item_meta($item_id, '_line_total', true);

                // Add the current item to the items array.
                array_push($item_array, array(
                        'description' => get_permalink($item_data['product_id']),
                        'fees' => $this->get_fees($order_email, 'me'),
                        'inspection_period' => $this->ts_inspection_period * 86400,
                        'quantity' => $item_quantity,
                        'schedule' => array(
                            array(
                                'payer_customer' => $order_email,
                                'amount' => $item_total,
                                'beneficiary_customer' => 'me'
                            )
                        ),
                        'title' => $item_name,
                        'type' => $this->ts_transaction_type
                    )
                );
            }

            // Add taxes to the transaction if applicable.
            if ($this->ts_tax_options == 'include_tax' and ($this->ts_transaction_type == 'general_merchandise' or $this->ts_transaction_type == 'milestone')) {
                $tax_items = $this->get_tax_items($order_email, 'me', 1);
                array_push($item_array, $tax_items);
            }

            // Add a shipping fee if applicable.
            $shipping_total = WC()->cart->shipping_total;
            if ($shipping_total > 0) {
                array_push($item_array, array(
                        'type' => 'shipping_fee',
                        'schedule' => array(
                            array(
                                'payer_customer' => $order_email,
                                'amount' => $shipping_total,
                                'beneficiary_customer' => 'me'
                            )
                        )
                    )
                );
            }

            // Configure buyer.
            $buyer = array(
                'agreed' => true,
                'customer' => $order_email,
                'initiator' => false,
                'role' => 'buyer',
                'first_name'    => $order->get_billing_first_name(),
                'last_name'     => $order->get_billing_last_name(),
                'phone_number'  => $order->get_billing_phone()
            );

            // Add address to buyer when appropriate.
            if (!empty($order->get_billing_address_1()) and
                !empty($order->get_billing_city()) and
                !empty($order->get_billing_state()) and
                !empty($order->get_billing_country())) {
                $buyer['address'] = array(
                    'line1'     => $order->get_billing_address_1(),
                    'line2'     => $order->get_billing_address_2(),
                    'city'      => $order->get_billing_city(),
                    'state'     => $order->get_billing_state(),
                    'country'   => $order->get_billing_country(),
                    'post_code' => $order->get_billing_postcode()
                );
            }

            // Configure seller.
            $seller = array(
                'agreed' => 'true',
                'customer' => 'me',
                'initiator' => 'true',
                'role' => 'seller'
            );

            // Build parties array for the transaction.
            $parties = array($buyer, $seller);

            // Build request.
            $request = array(
                'currency' => $this->ts_currency,
                'items' => $item_array,
                'description' => $order_name,
                'parties' => $parties,
                'return_url' => $order->get_view_order_url()
            );

            // Return populated request.
            return $request;
        }

        /**
         * Gets transaction request that will be posted to the Escrow.com API.
         *
         * @param WC_Order $order - Order represented on checkout page.
         * @param int $vendor_id - ID of vendor to which this order is restricted.
         * @return array $json_request - JSON encoded data to post to Escrow.com API.
         * @throws Exception if a valid WC Vendors Pro commission type is not found.
         */
        private function get_order_marketplace_scenario($order, $vendor_id) {

            // Get vendor properties.
            $vendor_commission_percent = $this->vs_commission;
            $vendor_data               = get_userdata($vendor_id);
            $vendor_email              = $vendor_data->user_email;
            $vendor_name               = $vendor_data->display_name;

            // Get order properties.
            $order_email    = $order->get_billing_email();
            $order_items    = $order->get_items();
            $order_name     = get_bloginfo( 'title' ) . ' order ' . $order->get_order_number() . ' (' . $vendor_name . ')';
            $order_subtotal = $order->get_subtotal();

            // Track amount of order attributable to this vendor.
            $vendor_total = 0;

            // Keep track of vendor commission.
            $vendor_commission_total = 0;

            // Adding support to known multi-vendor plugins
            // Get vendor specific commission.
            switch($this->vs_option){
                case 'WCV_Vendors':
                    $wc_vendors_free_commission = get_user_meta($vendor_id, 'pv_custom_commission_rate', true);
                    // Override commission percentage if vendor-specific commission is found.
                    if (!empty($wc_vendors_free_commission) && $wc_vendors_free_commission > 0) {

                        // Set commission percentage to 100 minus the value stored on vendor. Commission
                        // in WC Vendors refers to the percentage paid out to the vendor, which is why
                        // we subtract that value from 100 to arrive at the broker commission for the
                        // Escrow.com transaction.
                        $vendor_commission_percent = 100 - floatval($wc_vendors_free_commission);
                    }
                    break;
                case 'WeDevs_Dokan':
                    // Since this is already the percentage admin keeps; we'll just use this as the broker commission
                    // for the Escrow.com transaction
                    $dokan_free_admin_commission = get_user_meta($vendor_id, 'dokan_admin_percentage', true);
                    $dokan_free_flat_fee = get_user_meta($vendor_id, 'dokan_admin_additional_fee', true);

                    // Check if vendor-specific admin commission is set, else, use the one in the plugin
                    if (!empty($dokan_free_admin_commission) && $dokan_free_admin_commission > 0){
                        $vendor_commission_percent = floatval($dokan_free_admin_commission);
                    }
                    $vendor_commission_total = (isset($dokan_free_flat_fee) && $dokan_free_flat_fee > 0) ? $dokan_free_flat_fee : 0;

                    break;
                case 'WCFMmp':
                    $wcfmmp_free_commission_type = get_user_meta($vendor_id, '_wcfmmp_commission_type', true);
                    $wcfmmp_free_commission = get_user_meta($vendor_id, '_wcfmmp_commission', true);

                    if($wcfmmp_free_commission_type === 'percent'){
                        // Percentage vendor earns (similar to WC Vendors)
                        if (!empty($wcfmmp_free_commission) && $wcfmmp_free_commission > 0) {
                            $vendor_commission_percent = 100 - floatval($wcfmmp_free_commission);
                        }
                    }
                    break;
                default:
                    $logger = wc_get_logger();
                    $logger->critical('Multi vendor plugin "' . $this->vs_option . '" not supported.');
                    break;
                    }

            // If WC Vendors Pro is active, get commission for its settings.
            if (class_exists('WCVendors_Pro')) {

                $pro_commission_type 	= WCVendors_Pro::get_option( 'commission_type' );
                $pro_commission_percent = WCVendors_Pro::get_option( 'commission_percent' );
                $pro_commission_amount 	= WCVendors_Pro::get_option( 'commission_amount' );
                $pro_commission_fee		= WCVendors_Pro::get_option( 'commission_fee' );

                // Override global settings if values set on vendor.
                $pro_vendor_commission_type = get_user_meta($vendor_id, '_wcv_commission_type', true);
                if (isset($pro_vendor_commission_type) and strlen($pro_vendor_commission_type) > 0) {

                    // Set commission properties to vendor specific values.
                    $pro_commission_amount  = get_user_meta($vendor_id, '_wcv_commission_amount', true);
                    $pro_commission_fee     = get_user_meta($vendor_id, '_wcv_commission_fee', true);
                    $pro_commission_percent = get_user_meta($vendor_id, '_wcv_commission_percent', true);
                    $pro_commission_type    = $pro_vendor_commission_type;
                }

                // Switch on commission type.
                switch ($pro_commission_type) {
                    case 'fixed':
                        $vendor_commission_percent = 0;
                        $vendor_commission_total = $pro_commission_amount;
                        break;
                    case 'fixed_fee':
                        $vendor_commission_percent = 0;
                        $vendor_commission_total = $pro_commission_amount - $pro_commission_fee;
                        break;
                    case 'percent':
                        $vendor_commission_percent = 100 - floatval($pro_commission_percent);
                        break;
                    case 'percent_fee':
                        $vendor_commission_percent = 100 - floatval($pro_commission_percent);
                        $vendor_commission_total -= $pro_commission_fee;
                        break;
                    default:
                        throw new Exception('WC Vendor Pro commission type is not valid: ' . $pro_commission_type);
                }
            }

            // Build items array.
            $item_array = [];
            foreach ($order_items as $item_id => $item_data) {

                // Get the product ID from the item.
                $product_id = $item_data['product_id'];

                // Get vendor ID of current item.
                $item_vendor_id = get_post_field('post_author', $product_id);

                // Skip items that do not belong to the given vendor.
                if ($item_vendor_id != $vendor_id) {
                    continue;
                }

                // Get the properties of the current item.
                $product       = wc_get_product($product_id);
                $item_name     = $product->get_title();
                $item_quantity = wc_get_order_item_meta($item_id, '_qty', true);
                $item_total    = wc_get_order_item_meta($item_id, '_line_total', true);

                // Add item total to vendor total.
                $vendor_total += $item_total;

                // Calculate the commission for the current line item.
                if($vendor_commission_percent > 0) $item_commission = round($item_total * $vendor_commission_percent / 100, 2);

                // Cap item commission at maximum amount if max commission is per item.
                if ($this->vs_commission_max_target == 'item' and (isset($this->vs_commission_max) and $this->vs_commission_max > 0)) {
                    if ($item_commission > $this->vs_commission_max) {
                        $item_commission = $this->vs_commission_max;
                    }
                }

                // Add item commission to vendor commission.
                $vendor_commission_total += $item_commission;

                // Add the current item to the items array.
                array_push($item_array, array(
                        'description' => get_permalink($item_data['product_id']),
                        'fees' => $this->get_fees($order_email, $vendor_email),
                        'inspection_period' => $this->ts_inspection_period * 86400,
                        'quantity' => $item_quantity,
                        'schedule' => array(
                            array(
                                'payer_customer' => $order_email,
                                'amount' => $item_total,
                                'beneficiary_customer' => $vendor_email
                            )
                        ),
                        'title' => $item_name,
                        'type' => $this->ts_transaction_type
                    )
                );
            }

            // Calculate percentage of shipping that should be applied to this vendor.
            $vendor_percent = $vendor_total / $order_subtotal;

            // Add taxes to the transaction if applicable.
            if ($this->ts_tax_options == 'include_tax' and ($this->ts_transaction_type == 'general_merchandise' or $this->ts_transaction_type == 'milestone')) {
                $tax_items = $this->get_tax_items($order_email, $vendor_email, $vendor_percent);
                array_push($item_array, $tax_items);
            }

            // Cap vendor commission at maximum amount if max commission is per vendor.
            if ($this->vs_commission_max_target == 'vendor' and (isset($this->vs_commission_max) and $this->vs_commission_max > 0)) {
                if ($vendor_commission_total > $this->vs_commission_max) {
                    $vendor_commission_total = $this->vs_commission_max;
                }
            }

            // Add a broker fee if applicable.
            if ($vendor_commission_total > 0) {
                array_push($item_array, array(
                        'type' => 'broker_fee',
                        'schedule' => array(
                            array(
                                'payer_customer' => $vendor_email,
                                'amount' => $vendor_commission_total,
                                'beneficiary_customer' => 'me'
                            )
                        )
                    )
                );
            }

            // Add a shipping fee if applicable.
            $shipping_total = WC()->cart->shipping_total;
            if ($shipping_total > 0) {

                // Add shipping fee to items.
                array_push($item_array, array(
                        'type' => 'shipping_fee',
                        'schedule' => array(
                            array(
                                'payer_customer' => $order_email,
                                'amount' => round($shipping_total * $vendor_percent, 2),
                                'beneficiary_customer' => $vendor_email
                            )
                        )
                    )
                );
            }

            // Configure buyer.
            $buyer = array(
                'agreed' => true,
                'customer' => $order_email,
                'initiator' => false,
                'role' => 'buyer',
                'first_name'    => $order->get_billing_first_name(),
                'last_name'     => $order->get_billing_last_name(),
                'phone_number'  => $order->get_billing_phone()
            );

            // Add address to buyer when appropriate.
            if (!empty($order->get_billing_address_1()) and
                !empty($order->get_billing_city()) and
                !empty($order->get_billing_state()) and
                !empty($order->get_billing_country())) {
                $buyer['address'] = array(
                    'line1'     => $order->get_billing_address_1(),
                    'line2'     => $order->get_billing_address_2(),
                    'city'      => $order->get_billing_city(),
                    'state'     => $order->get_billing_state(),
                    'country'   => $order->get_billing_country(),
                    'post_code' => $order->get_billing_postcode()
                );
            }

            // Configure seller.
            $seller = array(
                'agreed' => 'true',
                'customer' => $vendor_email,
                'initiator' => 'false',
                'role' => 'seller'
            );

            // Build parties array for the transaction.
            $parties = [];
            if ($vendor_commission_total > 0) {
                array_push($parties,
                    $buyer,
                    $seller,
                    array(
                        'agreed' => true,
                        'customer' => 'me',
                        'role' => 'broker'
                    )
                );
            } else {
                array_push($parties,
                    $buyer,
                    $seller,
                    array(
                        'agreed' => true,
                        'customer' => 'me',
                        'role' => 'partner'
                    )
                );
            }

            // Build request.
            $request = array(
                'currency' => $this->ts_currency,
                'items' => $item_array,
                'description' => $order_name,
                'parties' => $parties,
                'return_url' => $order->get_view_order_url()
            );

            // Return populated request.
            return $request;
        }

        /**
         * Gets the array of escrow fees.
         *
         * @param string $buyer_email - The email address of the buyer in the transaction.
         * @param string $seller_email - The email address of the seller in the transaction.
         * @return array $fees_array - Array representing the escrow fees for the Escrow.com transaction to be created.
         */
        private function get_fees($buyer_email, $seller_email) {

            // Build the fees to be set on the transaction.
            $fees_array = [];
            if ($this->ts_escrow_fee_payer == 'buyer') {
                array_push($fees_array, array(
                        'payer_customer' => $buyer_email,
                        'split' => 1,
                        'type' => 'escrow'
                    )
                );
            } elseif ($this->ts_escrow_fee_payer == 'seller') {
                array_push($fees_array, array(
                        'payer_customer' => $seller_email,
                        'split' => 1,
                        'type' => 'escrow'
                    )
                );
            } else {
                array_push($fees_array, array(
                        'payer_customer' => $buyer_email,
                        'split' => 0.5,
                        'type' => 'escrow'
                    )
                );
                array_push($fees_array, array(
                        'payer_customer' => $seller_email,
                        'split' => 0.5,
                        'type' => 'escrow'
                    )
                );
            }

            // Return the populated fees array.
            return $fees_array;
        }

        /**
         * Gets an item representing the tax to be paid by the buyer for the given transaction.
         * The inspection period for the tax item defaults to a single day.
         *
         * @param string $buyer_email - The email address of the buyer in the transaction.
         * @param string $seller_email - The email address of the seller in the transaction.
         * @return array $vendor_percent - Percent of total order represented by the seller (aka vendor).
         *
         * @since 1.4.0
         */
        private function get_tax_items($buyer_email, $seller_email, $vendor_percent) {

            // Get total tax from shopping cart.
            $total_tax = WC()->cart->get_taxes_total();

            // Calculate amount of total tax applicable to current vendor.
            $total_tax_applicable = round($total_tax * $vendor_percent, 2);

            // Return new item representing applicable tax on given transaction.
            return array(
                'description' => 'Tax amount paid by buyer and remitted by seller',
                'fees' => $this->get_fees($buyer_email, $seller_email),
                'inspection_period' => $this->ts_inspection_period * 86400,
                'quantity' => 1,
                'schedule' => array(
                    array(
                        'payer_customer' => $buyer_email,
                        'amount' => $total_tax_applicable,
                        'beneficiary_customer' => $seller_email
                    )
                ),
                'title' => 'Tax collected by seller',
                'type' => $this->ts_transaction_type
            );
        }
    }
}
