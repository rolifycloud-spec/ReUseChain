<?php
if (!defined('ABSPATH')) exit;

add_shortcode('rc_co2_saved_vendor_total_v2', 'rc_sc_co2_saved_vendor_total_v2');

function rc_sc_co2_saved_vendor_total_v2() {

    if (!is_user_logged_in()) {
        return '<p>Morate biti ulogovani da biste vidjeli svoju CO₂ statistiku.</p>';
    }

    // Toggle iz CO₂ taba
    if (!get_option('reusechain_enable_vendor_total_v2', 1)) {
        return '<p><em>CO₂ vendor total (v2) je trenutno isključen.</em></p>';
    }

    $vendor_id = get_current_user_id();
    $total_co2 = 0;

    $orders = wc_get_orders([
        'limit'  => -1,
        'status' => ['wc-completed', 'wc-processing', 'wc-on-hold'],
        'return' => 'ids',
    ]);

    if (empty($orders)) {
        return '<p>Nema prodaja.</p>';
    }

    foreach ($orders as $order_id) {

        $order = wc_get_order($order_id);
        if (!$order) continue;

        foreach ($order->get_items() as $item) {

            $product = $item->get_product();
            if (!$product) continue;

            // Vendor = autor proizvoda
            $product_id = $product->get_parent_id() ?: $product->get_id();
            $author_id  = (int) get_post_field('post_author', $product_id);

            if ($author_id !== (int) $vendor_id) {
                continue;
            }

            $quantity = (float) $item->get_quantity();

            // KATEGORIJA (slug)
           $terms = wp_get_post_terms($product_id, 'product_cat');
if (empty($terms) || is_wp_error($terms)) continue;

$co2_added = false;

foreach ($terms as $term) {

    // 1) provjeri direktni slug
    $calculated = rc_calculate_co2_by_category($term->slug, $quantity);

    if ($calculated > 0) {
        $total_co2 += $calculated;
        $co2_added = true;
        break;
    }

    // 2) provjeri parent kategorije
    $ancestors = get_ancestors($term->term_id, 'product_cat');

    foreach ($ancestors as $ancestor_id) {
        $ancestor = get_term($ancestor_id, 'product_cat');
        if (!$ancestor || is_wp_error($ancestor)) continue;

        $calculated = rc_calculate_co2_by_category($ancestor->slug, $quantity);

        if ($calculated > 0) {
            $total_co2 += $calculated;
            $co2_added = true;
            break 2;
        }
    }
}

        }
    }

    if ($total_co2 <= 0) {
        return '<p style="background:#ffe8e8;padding:10px;border-left:4px solid #e02b2b;">
            Nema prodaja u CO₂ kategorijama za ovog korisnika.
        </p>';
    }

    return '
        <div class="co2-saved-summary">
            <h3>🌱 Ukupna CO₂ ušteda prodavača</h3>
            <p style="text-align:center;font-size:20px;margin-top:10px;">
                <strong>' . number_format($total_co2, 2, ',', '.') . ' kg</strong> CO₂
            </p>
        </div>
    ';
}
