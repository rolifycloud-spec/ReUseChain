<?php
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/functions.php';

/**
 * PERFORMANCE ENGINE
 */

if (rc_is_enabled('perf_defer_scripts')) {
    add_filter('script_loader_tag', 'reusechain_safe_defer', 10, 3);
}

if (rc_is_enabled('perf_optimize_css')) {
    add_filter('style_loader_tag', 'reusechain_optimize_css', 10, 3);
}

if (rc_is_enabled('perf_strip_home_scripts')) {
    add_action('wp_loaded', function () {
        ob_start('reusechain_strip_heavy_scripts_on_home');
    });
}

if (rc_is_enabled('perf_delay_fb')) {
    add_filter('script_loader_tag', 'reusechain_delay_fb_scripts', 10, 3);
}

if (rc_is_enabled('perf_disable_dashicons')) {
    add_action('wp_enqueue_scripts', 'reusechain_disable_dashicons_for_guests');
}

if (rc_is_enabled('perf_preload_hero')) {
    add_action('wp_head', 'reusechain_preload_hero_bg_image', 2);
}

if (rc_is_enabled('perf_move_jquery_footer')) {
    add_action('wp_enqueue_scripts', 'reusechain_move_jquery_to_footer');
}

if (rc_is_enabled('perf_block_jet_reviews')) {
    add_filter('script_loader_tag', 'rc_block_jet_reviews_scripts', 9999, 3);
    add_filter('style_loader_tag', 'rc_block_jet_reviews_css', 9999, 4);
}
