<?php
if (!defined('ABSPATH')) exit;

add_shortcode('rc_product_co2', 'rc_sc_product_co2');

function rc_sc_product_co2($atts) {

    $atts = shortcode_atts([
        'id'  => get_the_ID(),
        'qty' => null
    ], $atts);

    $product_id = (int) $atts['id'];

    if (!$product_id) {
        return '';
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        return '';
    }

    // Ako qty nije zadan → koristi stock
    $quantity = $atts['qty'] !== null
        ? (float) $atts['qty']
        : (float) $product->get_stock_quantity();

    if ($quantity <= 0) {
        return '<span>0 kg CO₂</span>';
    }

    $terms = wp_get_post_terms($product_id, 'product_cat');

    if (empty($terms) || is_wp_error($terms)) {
        return '<span>0 kg CO₂</span>';
    }

    $total_co2 = 0;

    foreach ($terms as $term) {

        // 1) direktni slug
        $calculated = rc_calculate_co2_by_category($term->slug, $quantity);

        if ($calculated > 0) {
            $total_co2 += $calculated;
            continue;
        }

        // 2) parent kategorije
        $ancestors = get_ancestors($term->term_id, 'product_cat');

        foreach ($ancestors as $ancestor_id) {

            $ancestor = get_term($ancestor_id, 'product_cat');
            if (!$ancestor || is_wp_error($ancestor)) continue;

            $calculated = rc_calculate_co2_by_category($ancestor->slug, $quantity);

            if ($calculated > 0) {
                $total_co2 += $calculated;
                break;
            }
        }
    }

    if ($total_co2 <= 0) {
        return '<span>0 kg CO₂</span>';
    }

    // =========================
    // PROŠIRENI CO2 PRIKAZ
    // =========================

    $co2_per_kg = $total_co2 / $quantity;

    // približne vrijednosti
    $km_equivalent = $total_co2 / 0.2;   // 0.2 kg CO2 po km
    $trees_equivalent = $total_co2 / 22; // 1 drvo ≈ 22 kg godišnje

    $output  = '<div class="rc-co2-product" style="line-height:1.5;">';

    $output .= '<div style="font-weight:600;">
    🌱 ' . number_format($total_co2, 2, ',', '.') . ' kg CO₂ uštede
    </div>';

    $output .= '<div style="font-size:13px; opacity:0.8;">
    ≈ ' . number_format($co2_per_kg, 2, ',', '.') . ' kg CO₂ / kg
    </div>';

    $output .= '<div style="font-size:13px; margin-top:4px;">
    🚗 ≈ ' . number_format($km_equivalent, 0, ',', '.') . ' km vožnje
    </div>';

    $output .= '<div style="font-size:13px;">
    🌳 ≈ ' . number_format($trees_equivalent, 0, ',', '.') . ' stabala/god
    </div>';

    $output .= '</div>';

    return $output;
}