<?php
if (!defined('ABSPATH')) exit;

function rc_get_effective_co2_categories() {

    $default = rc_get_co2_categories_config();
    $custom  = get_option('reusechain_co2_factors', []);

    if (empty($custom) || !is_array($custom)) {
        return $default;
    }

    foreach ($custom as $slug => $values) {

        if (!isset($default[$slug])) {
            continue;
        }

        if (isset($values['co2_factor'])) {
            $default[$slug]['co2_factor'] = (float) $values['co2_factor'];
        }

        if (isset($values['avg_weight'])) {
            $default[$slug]['avg_weight'] = (float) $values['avg_weight'];
        }
    }

    return $default;
}
