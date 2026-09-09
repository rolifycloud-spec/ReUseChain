<?php
if (!defined('ABSPATH')) exit;

/**
 * =====================================================
 * ReuseChain – Admin Menu + Tabs
 * Tabs: General | CO₂ | Proizvodi | Podešavanja
 * =====================================================
 */

/* =====================
 * ADMIN MENU
 * ===================== */
add_action('admin_menu', function () {
    add_menu_page(
        'ReuseChain',
        'ReuseChain',
        'manage_options',
        'reusechain',
        'rc_render_admin_page',
        'dashicons-update',
        30
    );
});

/* =====================
 * HANDLE ADMIN SUBMITS
 * ===================== */
add_action('admin_init', function () {

    if (!current_user_can('manage_options')) return;

    /* -------- GENERAL / PERFORMANCE -------- */
    if (
        isset($_POST['reusechain_general_nonce']) &&
        wp_verify_nonce($_POST['reusechain_general_nonce'], 'reusechain_save_general')
    ) {

        $flags = [
            'perf_defer_scripts',
            'perf_optimize_css',
            'perf_strip_home_scripts',
            'perf_delay_fb',
            'perf_disable_dashicons',
            'perf_preload_hero',
            'perf_move_jquery_footer',
            'perf_block_jet_reviews',
        ];

        foreach ($flags as $flag) {
            update_option(
                'reusechain_' . $flag,
                !empty($_POST[$flag]) ? 1 : 0
            );
        }
    }

    /* -------- CO₂ TOGGLES -------- */
    if (
        isset($_POST['reusechain_co2_settings_nonce']) &&
        wp_verify_nonce($_POST['reusechain_co2_settings_nonce'], 'reusechain_save_co2_settings')
    ) {
        update_option('reusechain_enable_vendor_total_v2', !empty($_POST['enable_vendor_total_v2']) ? 1 : 0);
        update_option('reusechain_enable_products_total_v2', !empty($_POST['enable_products_total_v2']) ? 1 : 0);
        update_option('reusechain_enable_categories_overview_v2', !empty($_POST['enable_categories_overview_v2']) ? 1 : 0);
    }

    /* -------- PRODUCTS / FRONTEND ACTIONS -------- */
    if (
        isset($_POST['reusechain_products_nonce']) &&
        wp_verify_nonce($_POST['reusechain_products_nonce'], 'reusechain_save_products')
    ) {
        update_option('reusechain_enable_frontend_delete_products', !empty($_POST['enable_frontend_delete_products']) ? 1 : 0);
        update_option('reusechain_enable_frontend_unpublish_products', !empty($_POST['enable_frontend_unpublish_products']) ? 1 : 0);
        update_option('reusechain_enable_frontend_publish_products', !empty($_POST['enable_frontend_publish_products']) ? 1 : 0);
    }

    /* -------- CO₂ FACTORS -------- */
    if (
        isset($_POST['reusechain_co2_factors_nonce']) &&
        wp_verify_nonce($_POST['reusechain_co2_factors_nonce'], 'reusechain_save_co2_factors')
    ) {

        $clean = [];

        if (!empty($_POST['co2']) && is_array($_POST['co2'])) {
            foreach ($_POST['co2'] as $slug => $vals) {
                $clean[$slug] = [
                    'co2_factor' => (float) ($vals['co2_factor'] ?? 0),
                    'avg_weight' => (float) ($vals['avg_weight'] ?? 0),
                ];
            }
        }

        update_option('reusechain_co2_factors', $clean);
    }

    /* -------- CO₂ PRODUCTS LIST -------- */
    if (
        isset($_POST['reusechain_co2_products_nonce']) &&
        wp_verify_nonce($_POST['reusechain_co2_products_nonce'], 'reusechain_save_co2_products')
    ) {

        $ids = [];

        if (!empty($_POST['co2_products'])) {
            foreach (explode(',', $_POST['co2_products']) as $id) {
                $id = (int) trim($id);
                if ($id > 0) $ids[] = $id;
            }
        }

        update_option('reusechain_co2_products', $ids);
    }
});

/* =====================
 * RENDER ADMIN PAGE
 * ===================== */
function rc_render_admin_page() {

    $tab = $_GET['tab'] ?? 'general';
    ?>
    <div class="wrap">
		<div style="display:flex;align-items:center;gap:18px;margin-bottom:24px;">
    <?php
    if (function_exists('has_custom_logo') && has_custom_logo()) {
        echo wp_get_attachment_image(
            get_theme_mod('custom_logo'),
            'full',
            false,
            [
                'style' => 'max-height:44px;width:auto;',
                'alt'   => get_bloginfo('name')
            ]
        );
    }
    ?>
    <div>
        <h1 style="margin:0;">ReuseChain plugin V1.1</h1>
        <p style="margin:4px 0 0;color:#666;">
            <?php echo esc_html(get_bloginfo('name')); ?>
        </p>
    </div>
</div>

        <h1>ReuseChain</h1>

        <nav class="nav-tab-wrapper">
            <a href="?page=reusechain&tab=general"  class="nav-tab <?php echo $tab==='general'?'nav-tab-active':'' ?>">General/Performance</a>
            <a href="?page=reusechain&tab=co2"      class="nav-tab <?php echo $tab==='co2'?'nav-tab-active':'' ?>">CO₂</a>
            <a href="?page=reusechain&tab=products" class="nav-tab <?php echo $tab==='products'?'nav-tab-active':'' ?>">Proizvodi</a>
            <a href="?page=reusechain&tab=settings" class="nav-tab <?php echo $tab==='settings'?'nav-tab-active':'' ?>">Podešavanja</a>
        </nav>

        <div style="margin-top:24px;">
        <?php switch ($tab):

        /* ================= GENERAL ================= */
        case 'general':
            $perf = [
                'perf_defer_scripts'      => get_option('reusechain_perf_defer_scripts', 1),
                'perf_optimize_css'       => get_option('reusechain_perf_optimize_css', 1),
                'perf_strip_home_scripts' => get_option('reusechain_perf_strip_home_scripts', 1),
                'perf_delay_fb'           => get_option('reusechain_perf_delay_fb', 1),
                'perf_disable_dashicons'  => get_option('reusechain_perf_disable_dashicons', 1),
                'perf_preload_hero'       => get_option('reusechain_perf_preload_hero', 0),
                'perf_move_jquery_footer' => get_option('reusechain_perf_move_jquery_footer', 1),
                'perf_block_jet_reviews'  => get_option('reusechain_perf_block_jet_reviews', 1),
            ];
            ?>
            <form method="post">
                <?php wp_nonce_field('reusechain_save_general', 'reusechain_general_nonce'); ?>
				<h1>Performance opcije</h1>
                <table class="form-table">
                    <?php foreach ($perf as $key => $val): ?>
                        <tr>
                            <th><?php echo esc_html($key); ?></th>
                            <td>
                                <input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" <?php checked($val,1); ?>>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <button class="button button-primary">Sačuvaj</button>
            </form>
        <?php break;

        /* ================= CO₂ ================= */
        case 'co2':
            ?>
            <form method="post">
                <?php wp_nonce_field('reusechain_save_co2_settings', 'reusechain_co2_settings_nonce'); ?>
				<h1>CO2 opcije</h1>
                <table class="form-table">
                    <tr><th>Vendor total CO2 funkcija</th><td><input type="checkbox" name="enable_vendor_total_v2" value="1" <?php checked(get_option('reusechain_enable_vendor_total_v2',1),1); ?>></td></tr>
                    <tr><th>Products total CO2 funkcija</th><td><input type="checkbox" name="enable_products_total_v2" value="1" <?php checked(get_option('reusechain_enable_products_total_v2',1),1); ?>></td></tr>
                    <tr><th>Categories overview CO2 funkcija</th><td><input type="checkbox" name="enable_categories_overview_v2" value="1" <?php checked(get_option('reusechain_enable_categories_overview_v2',1),1); ?>></td></tr>
                </table>
                <button class="button button-primary">Sačuvaj</button>
            </form>
        <?php break;

        /* ================= PRODUCTS ================= */
        case 'products':
            ?>
            <form method="post">
                <?php wp_nonce_field('reusechain_save_products', 'reusechain_products_nonce'); ?>
				<h1>Proizvodi funkcije</h1>
                <table class="form-table">
                    <tr><th>Funkcija brisanje proizvoda</th><td><input type="checkbox" name="enable_frontend_delete_products" value="1" <?php checked(get_option('reusechain_enable_frontend_delete_products',1),1); ?>></td></tr>
                    <tr><th>Funkcija sakrij proizvod</th><td><input type="checkbox" name="enable_frontend_unpublish_products" value="1" <?php checked(get_option('reusechain_enable_frontend_unpublish_products',1),1); ?>></td></tr>
                    <tr><th>Funkcija prikaži proizvod</th><td><input type="checkbox" name="enable_frontend_publish_products" value="1" <?php checked(get_option('reusechain_enable_frontend_publish_products',1),1); ?>></td></tr>
                </table>
                <button class="button button-primary">Sačuvaj</button>
            </form>
        <?php break;

        /* ================= SETTINGS ================= */
        case 'settings':

            $defaults = rc_get_co2_categories_config();
            $saved    = get_option('reusechain_co2_factors', []);
            $products = get_option('reusechain_co2_products', []);
            ?>

           <form method="post">
    <?php wp_nonce_field('reusechain_save_co2_factors', 'reusechain_co2_factors_nonce'); ?>

    <h1>Podešavanje kategorija i CO₂ faktora</h1>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>Kategorija</th>
                <th>CO₂ faktor</th>
                <th>Prosječna težina (kg)</th>
            </tr>
        </thead>
        <tbody>

        <?php foreach ($defaults as $slug => $cat): ?>
            <tr>
                <td>
                    <?php echo $cat['icon'] ?? '♻️'; ?>
                    <strong><?php echo esc_html($cat['label']); ?></strong>
                </td>

                <td>
                    <input type="number"
                           step="0.01"
                           name="co2[<?php echo esc_attr($slug); ?>][co2_factor]"
                           value="<?php echo esc_attr(
                               $saved[$slug]['co2_factor'] ?? $cat['co2_factor']
                           ); ?>">
                </td>

                <td>
                    <?php if (isset($cat['avg_weight'])): ?>
                        <input type="number"
                               step="0.01"
                               name="co2[<?php echo esc_attr($slug); ?>][avg_weight]"
                               value="<?php echo esc_attr(
                                   $saved[$slug]['avg_weight'] ?? $cat['avg_weight']
                               ); ?>">
                    <?php else: ?>
                        <em>Specijalna logika</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>

    <p style="margin-top:16px;">
        <button class="button button-primary">
            Sačuvaj CO₂ faktore
        </button>
    </p>
</form>

<hr style="margin:32px 0;">

<form method="post">
    <?php wp_nonce_field('reusechain_save_co2_products', 'reusechain_co2_products_nonce'); ?>

    <h2>CO₂ – Proizvodi za projekciju</h2>

    <p class="description">
        Unesite ID-jeve proizvoda (odvojene zarezom) koji ulaze u CO₂ projekciju.
    </p>

    <textarea name="co2_products"
              rows="3"
              style="width:100%;max-width:600px;"><?php
        echo esc_textarea(implode(', ', $products));
    ?></textarea>

    <p class="description">
        Primjer: <code>19418, 18832, 18687</code>
    </p>

    <p>
        <button class="button button-primary">
            Sačuvaj proizvode
        </button>
    </p>
</form>


        <?php break;

        endswitch; ?>
        </div>
    </div>
<?php
}
