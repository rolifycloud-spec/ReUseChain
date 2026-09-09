<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Učitava WordPress Dashicons na frontendu.
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('dashicons');
});


add_shortcode('rc_co2_saved_products_total_v2', 'rc_sc_co2_saved_products_total_v2');

function rc_sc_co2_saved_products_total_v2() {

    // Feature flag
    if (!get_option('reusechain_enable_products_total_v2', 1)) {
        return '';
    }

    // Product IDs iz admin podešavanja
    $product_ids = get_option('reusechain_co2_products', []);

    if (empty($product_ids) || !is_array($product_ids)) {
        return '<div class="rc-co2-panel rc-co2-panel-empty">Nema definisanih proizvoda za CO₂ obračun.</div>';
    }

    // Nazivi i poslovne Dashicons ikonice kategorija
    $category_meta = [
        'plastika' => ['label' => 'Plastika', 'icon' => 'dashicons-update'],
        'metalni-otpad'        => ['label' => 'Metalni otpad', 'icon' => 'dashicons-admin-tools'],
        'stakleni-otpad'       => ['label' => 'Stakleni otpad', 'icon' => 'dashicons-admin-site-alt3'],
        'papirni-otpad'        => ['label' => 'Papirni otpad', 'icon' => 'dashicons-media-document'],
        'tekstilni-otpad'      => ['label' => 'Tekstilni otpad', 'icon' => 'dashicons-art'],
        'elektronski-otpad'    => ['label' => 'Elektronski otpad', 'icon' => 'dashicons-laptop'],
        'drvni-otpad'          => ['label' => 'Drvni otpad', 'icon' => 'dashicons-admin-home'],
        'poljoprivredni-otpad' => ['label' => 'Poljoprivredni otpad', 'icon' => 'dashicons-palmtree'],
        'prehrambeni-otpad'    => ['label' => 'Prehrambeni otpad', 'icon' => 'dashicons-carrot'],
        'guma'                 => ['label' => 'Guma', 'icon' => 'dashicons-controls-repeat'],
    ];

    $total_kg   = 0;
    $cat_totals = [];

    foreach ($product_ids as $pid) {

        $product = wc_get_product($pid);

        if (!$product) {
            continue;
        }

        $stock = $product->get_stock_quantity();

        if ($stock === null || $stock <= 0) {
            continue;
        }

        // Kategorije proizvoda, uključujući parent fallback
        $terms = wp_get_post_terms($pid, 'product_cat');

        if (empty($terms) || is_wp_error($terms)) {
            continue;
        }

        foreach ($terms as $term) {

            // Direktna kategorija
            $saved = rc_calculate_co2_by_category($term->slug, $stock);

            if ($saved > 0) {
                $total_kg += $saved;
                $cat_totals[$term->slug] = ($cat_totals[$term->slug] ?? 0) + $saved;
                break;
            }

            // Parent kategorije
            $ancestors = get_ancestors($term->term_id, 'product_cat');

            foreach ($ancestors as $ancestor_id) {

                $ancestor = get_term($ancestor_id, 'product_cat');

                if (!$ancestor || is_wp_error($ancestor)) {
                    continue;
                }

                $saved = rc_calculate_co2_by_category($ancestor->slug, $stock);

                if ($saved > 0) {
                    $total_kg += $saved;
                    $cat_totals[$ancestor->slug] = ($cat_totals[$ancestor->slug] ?? 0) + $saved;
                    break 2;
                }
            }
        }
    }

    if ($total_kg <= 0) {
        return '<div class="rc-co2-panel rc-co2-panel-empty">Trenutno nema aktivnih oglasa za izračun CO₂ uštede.</div>';
    }

    // Najveća CO₂ ušteda prva
    arsort($cat_totals);

    $format = function($kg) {
        return ($kg >= 1000)
            ? number_format($kg / 1000, 2, ',', '.') . ' t CO₂'
            : number_format($kg, 2, ',', '.') . ' kg CO₂';
    };

    ob_start();
    ?>

    <div class="rc-co2-panel">

        <div class="rc-co2-panel-header">
            <div class="rc-co2-panel-header-icon">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>

            <div class="rc-co2-panel-header-content">
                <span>Očekivana ušteda CO₂</span>
                <strong><?php echo esc_html($format($total_kg)); ?></strong>
                <p>Pri realizaciji trenutno aktivnih oglasa.</p>
            </div>
        </div>

        <div class="rc-co2-panel-list">
            <div class="rc-co2-panel-grid">

                <?php foreach ($cat_totals as $cat => $val) : ?>
                    <?php
                    $label = $category_meta[$cat]['label'] ?? ucfirst(str_replace('-', ' ', $cat));
                    $icon  = $category_meta[$cat]['icon'] ?? 'dashicons-recycle';
                    ?>

                    <div class="rc-co2-category-item">
                        <div class="rc-co2-category-icon">
                            <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                        </div>

                        <div class="rc-co2-category-name">
                            <?php echo esc_html($label); ?>
                        </div>

                        <strong><?php echo esc_html($format($val)); ?></strong>
                    </div>

                <?php endforeach; ?>

            </div>
        </div>

    </div>

    <style>
        /* CO₂ panel, compact */

        .rc-co2-panel {
            width: min(1420px, calc(100vw - 40px)) !important;
            max-width: none !important;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            margin: 18px 0;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #e1ece3;
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(20, 92, 43, 0.08);
            font-family: inherit;
        }

        .rc-co2-panel-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 20px 28px;
            background: linear-gradient(135deg, #176b2c 0%, #2e9b4c 100%);
        }

        .rc-co2-panel-header-icon {
            width: 54px;
            height: 54px;
            flex: 0 0 54px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 14px;
        }

        .rc-co2-panel-header-icon .dashicons {
            width: auto;
            height: auto;
            color: #ffffff;
            font-size: 27px;
            line-height: 1;
        }

        .rc-co2-panel-header-content span {
            display: block;
            margin-bottom: 2px;
            color: rgba(255, 255, 255, 0.82);
            font-size: 13px;
            font-weight: 600;
        }

        .rc-co2-panel-header-content strong {
            display: block;
            color: #ffffff;
            font-size: 31px;
            font-weight: 800;
            line-height: 1.05;
        }

        .rc-co2-panel-header-content p {
            margin: 4px 0 0;
            color: rgba(255, 255, 255, 0.82);
            font-size: 12px;
            font-weight: 500;
        }

        .rc-co2-panel-list {
            padding: 16px 28px 18px;
        }

        .rc-co2-panel-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 20px;
        }

        .rc-co2-category-item {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 0;
            padding: 12px 14px;
            background: #f8fbf8;
            border: 1px solid #e3eee5;
            border-radius: 12px;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .rc-co2-category-item:hover {
            transform: translateY(-1px);
            border-color: #b9dfbf;
            box-shadow: 0 5px 12px rgba(23, 107, 44, 0.08);
        }

        .rc-co2-category-icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #edf8ef;
            border: 1px solid #d3ead7;
            border-radius: 10px;
        }

        .rc-co2-category-icon .dashicons {
            width: auto;
            height: auto;
            color: #176b2c;
            font-size: 18px;
            line-height: 1;
        }

        .rc-co2-category-name {
            flex: 1;
            min-width: 0;
            color: #344054;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rc-co2-category-item strong {
            color: #176b2c;
            font-size: 15px;
            font-weight: 800;
            line-height: 1.2;
            white-space: nowrap;
        }

        .rc-co2-panel-empty {
            width: min(1420px, calc(100vw - 40px)) !important;
            max-width: none !important;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            margin: 18px 0;
            padding: 16px 20px;
            color: #475467;
            background: #ffffff;
            border: 1px solid #e1ece3;
            border-radius: 14px;
            font-weight: 600;
        }

        @media (max-width: 900px) {
            .rc-co2-panel-header {
                padding: 18px 22px;
            }

            .rc-co2-panel-list {
                padding: 14px 22px 16px;
            }

            .rc-co2-panel-grid {
                gap: 10px 14px;
            }

            .rc-co2-category-item {
                padding: 11px 12px;
            }
        }

        @media (max-width: 700px) {
            .rc-co2-panel-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 520px) {
            .rc-co2-panel {
                width: calc(100vw - 28px) !important;
                margin: 14px 0;
                border-radius: 14px;
            }

            .rc-co2-panel-header {
                align-items: flex-start;
                gap: 12px;
                padding: 16px;
            }

            .rc-co2-panel-header-icon {
                width: 46px;
                height: 46px;
                flex-basis: 46px;
                border-radius: 12px;
            }

            .rc-co2-panel-header-icon .dashicons {
                font-size: 23px;
            }

            .rc-co2-panel-header-content strong {
                font-size: 26px;
            }

            .rc-co2-panel-list {
                padding: 12px;
            }

            .rc-co2-category-item {
                gap: 10px;
                padding: 11px;
            }

            .rc-co2-category-icon {
                width: 36px;
                height: 36px;
                flex-basis: 36px;
            }

            .rc-co2-category-icon .dashicons {
                font-size: 17px;
            }

            .rc-co2-category-name,
            .rc-co2-category-item strong {
                font-size: 13px;
            }
        }
    </style>

    <?php
    return ob_get_clean();
}