<?php
if (!defined('ABSPATH')) exit;

function rc_calculate_co2_by_category($category_slug, $stock_qty) {

   $config = rc_get_effective_co2_categories();


    if (!isset($config[$category_slug]) || $stock_qty <= 0) {
        return 0;
    }

    $cat = $config[$category_slug];

    // Special case: guma
    if ($category_slug === 'guma' && isset($cat['special'])) {

        $auto_qty  = $stock_qty / 2;
        $truck_qty = $stock_qty / 2;

        return
            ($auto_qty  * $cat['special']['auto_weight']  * $cat['co2_factor']) +
            ($truck_qty * $cat['special']['truck_weight'] * $cat['co2_factor']);
    }

    // Standard case
    return
        $stock_qty *
        ($cat['avg_weight'] ?? 0) *
        ($cat['co2_factor'] ?? 0);
}
