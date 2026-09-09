<?php
if (!defined('ABSPATH')) exit;

/**
 * =====================================================
 * ReuseChain – Frontend delete own products (Woo)
 * =====================================================
 */

/**
 * Feature flag
 */
function rc_is_frontend_delete_enabled() {
    return (bool) get_option('reusechain_enable_frontend_delete_products', 0);
}


/**
 * Detect current product ID (JetEngine compatible)
 */
function rc_get_current_object_id_any_context() {

    if (function_exists('jet_engine') && isset(jet_engine()->listings->data)) {
        $id = jet_engine()->listings->data->get_current_object_id();
        if ($id) return absint($id);
    }

    global $post;
    if ($post instanceof WP_Post && $post->ID) {
        return absint($post->ID);
    }

    $id = get_the_ID();
    if ($id) return absint($id);

    $id = get_queried_object_id();
    if ($id) return absint($id);

    return 0;
}

/**
 * Safe redirect
 */
function rc_safe_redirect_target($maybe_url, $fallback) {

    $fallback = $fallback ?: home_url('/');

    if (!$maybe_url) return $fallback;

    if (strpos($maybe_url, '/') === 0) {
        return $maybe_url;
    }

    $site = parse_url(home_url('/'), PHP_URL_HOST);
    $host = parse_url($maybe_url, PHP_URL_HOST);

    if ($site && $host && strtolower($site) === strtolower($host)) {
        return $maybe_url;
    }

    return $fallback;
}

/**
 * Handle delete action
 */
add_action('template_redirect', function () {

    if (!rc_is_frontend_delete_enabled()) return;
    if (empty($_GET['rc_delete_product'])) return;

    if (!is_user_logged_in()) wp_die('Morate biti prijavljeni.', 403);

    $product_id = absint($_GET['rc_delete_product']);
    if (!$product_id) wp_die('Neispravan ID proizvoda.', 400);

    $nonce = $_GET['_wpnonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'rc_delete_product_' . $product_id)) {
        wp_die('Sigurnosna provjera nije prošla.', 403);
    }

    if (get_post_type($product_id) !== 'product') {
        wp_die('Ovo nije proizvod.', 400);
    }

    $post = get_post($product_id);
    if (!$post) wp_die('Proizvod ne postoji.', 404);

    if ((int) $post->post_author !== get_current_user_id()) {
        wp_die('Nemate pravo brisati tuđi proizvod.', 403);
    }

    wp_trash_post($product_id);

    $fallback = home_url('/korisnik/');
    $redirect = rc_safe_redirect_target($_GET['redirect_to'] ?? '', $fallback);
    $redirect = add_query_arg('rc_deleted', $product_id, $redirect);

    wp_safe_redirect($redirect);
    exit;
});

/**
 * Shortcode button
 */
add_shortcode('rc_delete_product_button', function ($atts) {

    if (!is_user_logged_in()) return '';
    if (!rc_is_frontend_delete_enabled()) return '';

    $atts = shortcode_atts([
        'id'          => 0,
        'label'       => 'Obriši oglas',
        'class'       => 'rc-delete-product-btn',
        'redirect_to' => '/korisnik/',
        'confirm'     => 'Da li ste sigurni?',
    ], $atts);

    $product_id = absint($atts['id']) ?: rc_get_current_object_id_any_context();
    if (!$product_id || get_post_type($product_id) !== 'product') return '';

    $post = get_post($product_id);
    if (!$post || (int) $post->post_author !== get_current_user_id()) return '';

    $url = add_query_arg([
        'rc_delete_product' => $product_id,
        'redirect_to'       => $atts['redirect_to'],
    ], home_url('/'));

    $url = wp_nonce_url($url, 'rc_delete_product_' . $product_id);

    return sprintf(
        '<a class="%s" href="%s" onclick="return confirm(\'%s\');">%s</a>',
        esc_attr($atts['class']),
        esc_url($url),
        esc_js($atts['confirm']),
        esc_html($atts['label'])
    );
});
