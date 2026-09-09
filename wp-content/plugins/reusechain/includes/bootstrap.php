<?php
if (!defined('ABSPATH')) exit;

/**
 * CONFIG (prvo)
 */
require_once __DIR__ . '/config/co2-categories.php';

/**
 * HELPERS CO2
 */
require_once __DIR__ . '/co2/helpers.php';
require_once __DIR__ . '/helpers/options.php';

/**
 * CORE LOGIC
 */
require_once __DIR__ . '/co2/calculator.php';

/** * PRODUCTS (frontend actions)
 */
require_once __DIR__ . '/products/frontend-delete.php';
require_once __DIR__ . '/products/frontend-publish-toggle.php';

/**
 * SHORTCODES
 */
require_once __DIR__ . '/co2/shortcodes/vendor-total-summary-v2.php';
require_once __DIR__ . '/co2/shortcodes/products-total-v2.php';
require_once __DIR__ . '/co2/shortcodes/categories-overview-v2.php';
require_once __DIR__ . '/co2/shortcodes/product-co2.php';
require_once __DIR__ . '/co2/shortcodes/gauge.php';

/**
 * ADMIN
 */
require_once __DIR__ . '/admin/admin-tabs.php';


add_action('wp_enqueue_scripts', 'rc_enqueue_gauge_script');

function rc_enqueue_gauge_script() {

    $file = plugin_dir_path(__FILE__) . '../assets/js/rc-gauge-v3.js';

    if (!file_exists($file)) {
        return;
    }

    wp_enqueue_script(
        'rc-gauge-v3',
        plugin_dir_url(__FILE__) . '../assets/js/rc-gauge-v3.js',
        [],
        '1.0.7',
        true
    );
}