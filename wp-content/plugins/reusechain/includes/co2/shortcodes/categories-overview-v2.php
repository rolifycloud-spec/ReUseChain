<?php
if (!defined('ABSPATH')) exit;

/**
 * CO₂ categories overview – v2
 */

add_shortcode(
    'rc_co2_categories_overview_v2',
    'rc_sc_co2_categories_overview_v2'
);

function rc_sc_co2_categories_overview_v2() {
 // Toggle
    if (!get_option('reusechain_enable_categories_overview_v2', 1)) {
        return '';
    }
    // AIRBAG – ako engine nije učitan, shortcode se ne renderuje
    if (
        !function_exists('rc_get_effective_co2_categories') ||
        !function_exists('rc_calculate_co2_by_category')
    ) {
        return '';
    }

    $categories_config = rc_get_effective_co2_categories();

    $results = [];
    foreach ($categories_config as $slug => $cfg) {
        $results[$slug] = [
            'slug'      => $slug,
            'label'     => $cfg['label'],
            'icon'      => $cfg['icon'] ?? '♻️',
            'ads'       => 0,
            'companies' => [],
            'saved'     => 0,
        ];
    }

    $products = get_posts([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    foreach ($products as $pid) {

        $product = wc_get_product($pid);
        if (!$product) continue;

        $stock = $product->get_stock_quantity();
        if ($stock === null || $stock <= 0) continue;

        $author_id = (int) get_post_field('post_author', $pid);

        $terms = wp_get_post_terms($pid, 'product_cat');
        if (empty($terms) || is_wp_error($terms)) continue;

        foreach ($terms as $term) {

            $saved = rc_calculate_co2_by_category($term->slug, $stock);

            if ($saved > 0 && isset($results[$term->slug])) {

                $results[$term->slug]['ads']++;
                if ($author_id) {
                    $results[$term->slug]['companies'][$author_id] = true;
                }
                $results[$term->slug]['saved'] += $saved;
                break;
            }

            $ancestors = get_ancestors($term->term_id, 'product_cat');
            foreach ($ancestors as $ancestor_id) {

                $ancestor = get_term($ancestor_id, 'product_cat');
                if (!$ancestor || is_wp_error($ancestor)) continue;

                $saved = rc_calculate_co2_by_category($ancestor->slug, $stock);

                if ($saved > 0 && isset($results[$ancestor->slug])) {

                    $results[$ancestor->slug]['ads']++;
                    if ($author_id) {
                        $results[$ancestor->slug]['companies'][$author_id] = true;
                    }
                    $results[$ancestor->slug]['saved'] += $saved;
                    break 2;
                }
            }
        }
    }

    $total_all = 0;
    foreach ($results as $r) {
        $total_all += $r['saved'];
    }

    $format = function ($kg) {
        return ($kg >= 1000)
            ? number_format($kg / 1000, 2, ',', '.') . ' t CO₂'
            : number_format($kg, 2, ',', '.') . ' kg CO₂';
    };

    ob_start();
    ?>

    <style>
        .co2-total-box {
            padding: 15px 20px;
            border-radius: 12px;
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            margin-bottom: 25px;
            font-size: 20px;
            font-weight: 600;
            color: #2d7c30;
            text-align: center;
        }
        .co2-cat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }
        .co2-card {
            background: #ffffff;
            border: 1px solid #eee;
            padding: 18px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .co2-icon {
            font-size: 34px;
            margin-bottom: 10px;
        }
        .co2-card h4 {
            margin: 0 0 10px;
            font-size: 18px;
        }
        .co2-card .line {
            margin-bottom: 6px;
            color: #444;
        }
        .co2-card strong {
            color: #2d7c30;
        }
    </style>

    <div class="co2-total-box">
        🌍 Ukupna potencijalna ušteda CO₂:
        <strong><?php echo $format($total_all); ?></strong>
    </div>

    <div class="co2-cat-grid">
        <?php foreach ($results as $r): ?>
            <?php if ($r['ads'] <= 0) continue; ?>
            <div class="co2-card">
                <div class="co2-icon"><?php echo esc_html($r['icon']); ?></div>
                <h4><?php echo esc_html($r['label']); ?></h4>

                <div class="line">
                    Broj oglasa:
                    <strong><?php echo (int) $r['ads']; ?></strong>
                </div>

                <div class="line">
                    Broj kompanija:
                    <strong><?php echo count($r['companies']); ?></strong>
                </div>

                <div class="line">
                    CO₂ potencijalna ušteda:
                    <strong><?php echo $format($r['saved']); ?></strong>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
    return ob_get_clean();
}
