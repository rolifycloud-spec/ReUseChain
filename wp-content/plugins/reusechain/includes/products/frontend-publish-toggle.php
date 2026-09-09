<?php
if (!defined('ABSPATH')) exit;

/**
 * =====================================================
 * ReuseChain – Frontend publish / unpublish own products (v2)
 * =====================================================
 */

/**
 * Feature flag (ODVOJEN OD DELETE)
 */
function rc_is_frontend_publish_enabled() {
    return (bool) get_option('reusechain_enable_frontend_publish_products', 1);
}

/**
 * Detect product ID (JetEngine compatible)
 */
function rc_get_product_id_any_context() {

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
 * ==========================
 * UNPUBLISH (publish → draft)
 * ==========================
 */
add_action('template_redirect', function () {

    if (!rc_is_frontend_publish_enabled()) return;
    if (empty($_GET['rc_unpublish_product'])) return;

    if (!is_user_logged_in()) wp_die('Morate biti prijavljeni.', 403);

    $product_id = absint($_GET['rc_unpublish_product']);
    if (!$product_id) wp_die('Neispravan ID proizvoda.', 400);

    $nonce = $_GET['_wpnonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'rc_unpublish_product_' . $product_id)) {
        wp_die('Sigurnosna provjera nije prošla.', 403);
    }

    if (get_post_type($product_id) !== 'product') {
        wp_die('Ovo nije proizvod.', 400);
    }

    $post = get_post($product_id);
    if (!$post || (int) $post->post_author !== get_current_user_id()) {
        wp_die('Nemate pravo.', 403);
    }

    wp_update_post([
        'ID'          => $product_id,
        'post_status' => 'draft',
    ]);

    wp_safe_redirect(
        add_query_arg('rc_unpublished', $product_id, home_url('/korisnik/'))
    );
    exit;
});

/**
 * UNPUBLISH BUTTON
 */
add_shortcode('rc_unpublish_product_button_v2', function ($atts) {

    if (!is_user_logged_in()) return '';
    if (!rc_is_frontend_publish_enabled()) return '';

    $atts = shortcode_atts([
        'id'      => 0,
        'label'   => 'Sakrij oglas',
        'class'   => 'rc-unpublish-product-btn',
        'confirm' => 'Oglas će biti sakriven (draft). Nastaviti?',
    ], $atts);

    $product_id = absint($atts['id']) ?: rc_get_product_id_any_context();
    if (!$product_id || get_post_type($product_id) !== 'product') return '';

    $post = get_post($product_id);
    if (!$post || (int) $post->post_author !== get_current_user_id()) return '';

    if ($post->post_status !== 'publish') return '';

    $url = wp_nonce_url(
        add_query_arg('rc_unpublish_product', $product_id, home_url('/')),
        'rc_unpublish_product_' . $product_id
    );

    return sprintf(
        '<a class="%s" href="%s" onclick="return confirm(\'%s\');">%s</a>',
        esc_attr($atts['class']),
        esc_url($url),
        esc_js($atts['confirm']),
        esc_html($atts['label'])
    );
});

/**
 * ==========================
 * PUBLISH (draft → publish)
 * ==========================
 */
add_action('template_redirect', function () {

    if (!rc_is_frontend_publish_enabled()) return;
    if (empty($_GET['rc_publish_product'])) return;

    if (!is_user_logged_in()) wp_die('Morate biti prijavljeni.', 403);

    $product_id = absint($_GET['rc_publish_product']);
    if (!$product_id) wp_die('Neispravan ID proizvoda.', 400);

    $nonce = $_GET['_wpnonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'rc_publish_product_' . $product_id)) {
        wp_die('Sigurnosna provjera nije prošla.', 403);
    }

    if (get_post_type($product_id) !== 'product') {
        wp_die('Ovo nije proizvod.', 400);
    }

    $post = get_post($product_id);
    if (!$post || (int) $post->post_author !== get_current_user_id()) {
        wp_die('Nemate pravo.', 403);
    }

    wp_update_post([
        'ID'          => $product_id,
        'post_status' => 'publish',
    ]);

    wp_safe_redirect(
        add_query_arg('rc_published', $product_id, home_url('/korisnik/'))
    );
    exit;
});

/**
 * PUBLISH BUTTON
 */
add_shortcode('rc_publish_product_button_v2', function ($atts) {

    if (!is_user_logged_in()) return '';
    if (!rc_is_frontend_publish_enabled()) return '';

    $atts = shortcode_atts([
        'id'      => 0,
        'label'   => 'Aktiviraj oglas',
        'class'   => 'rc-publish-product-btn',
        'confirm' => 'Oglas će ponovo biti vidljiv. Nastaviti?',
    ], $atts);

    $product_id = absint($atts['id']) ?: rc_get_product_id_any_context();
    if (!$product_id || get_post_type($product_id) !== 'product') return '';

    $post = get_post($product_id);
    if (!$post || (int) $post->post_author !== get_current_user_id()) return '';

    if ($post->post_status !== 'draft') return '';

    $url = wp_nonce_url(
        add_query_arg('rc_publish_product', $product_id, home_url('/')),
        'rc_publish_product_' . $product_id
    );

    return sprintf(
        '<a class="%s" href="%s" onclick="return confirm(\'%s\');">%s</a>',
        esc_attr($atts['class']),
        esc_url($url),
        esc_js($atts['confirm']),
        esc_html($atts['label'])
    );
});
