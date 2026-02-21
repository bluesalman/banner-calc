<?php
/**
 * Category Defaults admin page — manage BannerCalc config per product category.
 *
 * Standalone admin page (not the WP term-edit screen).
 *
 * @package BannerCalc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$attr_mgr   = new \BannerCalc\AttributeManager();
$attributes  = $attr_mgr->get_all_attributes();
$all_units   = \BannerCalc\UnitConverter::get_unit_labels();

$sizing_modes = [
    'preset_only'       => __( 'Preset Sizes Only', 'bannercalc' ),
    'custom_only'       => __( 'Custom Size Only', 'bannercalc' ),
    'preset_and_custom' => __( 'Preset + Custom Size', 'bannercalc' ),
    'none'              => __( 'None (fixed price)', 'bannercalc' ),
];

$categories = get_terms( [
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
] );

if ( is_wp_error( $categories ) ) {
    $categories = [];
}

// Handle save.
$saved_message = '';
if ( isset( $_POST['bannercalc_category_save'] ) && wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'bannercalc_category_page' ) ) {
    $cat_id    = absint( $_POST['bannercalc_cat_id'] ?? 0 );
    $raw       = $_POST['bannercalc_category'] ?? []; // phpcs:ignore

    if ( $cat_id && current_user_can( 'manage_woocommerce' ) ) {
        $category_defaults = new \BannerCalc\Admin\CategoryDefaults();
        // Use the public save method — trigger the same sanitize path.
        $config = [];

        $config['enabled']      = ! empty( $raw['enabled'] );
        $config['sizing_mode']  = sanitize_text_field( $raw['sizing_mode'] ?? 'preset_and_custom' );
        $config['default_unit'] = sanitize_text_field( $raw['default_unit'] ?? 'ft' );

        $config['area_rate_sqft'] = (float) ( $raw['area_rate_sqft'] ?? 0 );
        $config['area_rate_sqm']  = (float) ( $raw['area_rate_sqm'] ?? 0 );
        $config['min_width_m']    = (float) ( $raw['min_width_m'] ?? 0 );
        $config['min_height_m']   = (float) ( $raw['min_height_m'] ?? 0 );
        $config['max_width_m']    = (float) ( $raw['max_width_m'] ?? 0 );
        $config['max_height_m']   = (float) ( $raw['max_height_m'] ?? 0 );
        $config['minimum_charge'] = (float) ( $raw['minimum_charge'] ?? 0 );

        $allowed_units = [ 'mm', 'cm', 'inch', 'ft', 'm' ];
        $config['available_units'] = array_values(
            array_intersect( (array) ( $raw['available_units'] ?? [] ), $allowed_units )
        );

        $config['enabled_attributes'] = array_map( 'sanitize_text_field', (array) ( $raw['enabled_attributes'] ?? [] ) );
        $config['attribute_pricing']  = $raw['attribute_pricing'] ?? [];
        $config['preset_sizes']       = $raw['preset_sizes'] ?? [];

        update_term_meta( $cat_id, '_bannercalc_config', $config );
        $saved_message = __( 'Category configuration saved.', 'bannercalc' );
    }
}

// Determine which category is currently being edited.
$editing_cat_id = absint( $_GET['category'] ?? ( $_POST['bannercalc_cat_id'] ?? 0 ) );
$editing_config = [];
if ( $editing_cat_id ) {
    $editing_config = get_term_meta( $editing_cat_id, '_bannercalc_config', true );
    if ( ! is_array( $editing_config ) ) {
        $editing_config = [];
    }
}
?>
<div class="wrap bannercalc-admin-wrap bannercalc-categories-page">

    <!-- CMYK Brand Bar -->
    <div class="bannercalc-cmyk-bar"><span></span><span></span><span></span><span></span></div>

    <!-- Page Header -->
    <div class="bannercalc-page-header">
        <div class="bannercalc-admin-overline"><?php esc_html_e( 'CONFIGURATION', 'bannercalc' ); ?></div>
        <h1 class="bannercalc-page-title"><?php esc_html_e( 'Category Defaults', 'bannercalc' ); ?></h1>
        <p class="bannercalc-page-desc"><?php esc_html_e( 'Configure pricing, sizing, and attribute defaults for each product category.', 'bannercalc' ); ?></p>
    </div>

    <?php if ( $saved_message ) : ?>
        <div class="bannercalc-notice bannercalc-notice-success">
            <span class="dashicons dashicons-yes-alt"></span>
            <?php echo esc_html( $saved_message ); ?>
        </div>
    <?php endif; ?>

    <div class="bannercalc-split-layout">

        <!-- Left: Category List -->
        <div class="bannercalc-category-list-panel">
            <h2 class="bannercalc-section-heading"><?php esc_html_e( 'Product Categories', 'bannercalc' ); ?></h2>
            <ul class="bannercalc-cat-list">
                <?php foreach ( $categories as $cat ) :
                    $cat_config = get_term_meta( $cat->term_id, '_bannercalc_config', true );
                    $cat_enabled = ! empty( $cat_config['enabled'] );
                    $is_active = ( $cat->term_id === $editing_cat_id );
                    $url = admin_url( 'admin.php?page=bannercalc-categories&category=' . $cat->term_id );
                ?>
                    <li class="bannercalc-cat-item <?php echo $is_active ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( $url ); ?>">
                            <span class="bannercalc-cat-name"><?php echo esc_html( $cat->name ); ?></span>
                            <?php if ( $cat_enabled ) : ?>
                                <span class="bannercalc-cat-badge bannercalc-badge-active"><?php esc_html_e( 'Active', 'bannercalc' ); ?></span>
                            <?php else : ?>
                                <span class="bannercalc-cat-badge bannercalc-badge-inactive">&mdash;</span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Right: Edit Form -->
        <div class="bannercalc-category-edit-panel">
            <?php if ( $editing_cat_id ) :
                $editing_term = get_term( $editing_cat_id, 'product_cat' );
            ?>
                <h2 class="bannercalc-section-heading">
                    <?php
                    printf(
                        /* translators: %s: category name */
                        esc_html__( 'Configure: %s', 'bannercalc' ),
                        '<strong>' . esc_html( $editing_term->name ) . '</strong>'
                    );
                    ?>
                </h2>

                <form method="post" action="">
                    <?php wp_nonce_field( 'bannercalc_category_page' ); ?>
                    <input type="hidden" name="bannercalc_cat_id" value="<?php echo esc_attr( $editing_cat_id ); ?>" />

                    <table class="form-table bannercalc-form-table">

                        <!-- Enable toggle -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable BannerCalc', 'bannercalc' ); ?></th>
                            <td>
                                <label class="bannercalc-toggle-label">
                                    <input type="checkbox"
                                           name="bannercalc_category[enabled]"
                                           value="1"
                                           <?php checked( ! empty( $editing_config['enabled'] ) ); ?> />
                                    <?php esc_html_e( 'Enable for products in this category', 'bannercalc' ); ?>
                                </label>
                            </td>
                        </tr>

                        <!-- Sizing mode -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Sizing Mode', 'bannercalc' ); ?></th>
                            <td>
                                <select name="bannercalc_category[sizing_mode]" class="bannercalc-select">
                                    <?php foreach ( $sizing_modes as $mode_key => $mode_label ) : ?>
                                        <option value="<?php echo esc_attr( $mode_key ); ?>"
                                                <?php selected( $editing_config['sizing_mode'] ?? 'preset_and_custom', $mode_key ); ?>>
                                            <?php echo esc_html( $mode_label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>

                        <!-- Default unit -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Default Unit', 'bannercalc' ); ?></th>
                            <td>
                                <select name="bannercalc_category[default_unit]" class="bannercalc-select">
                                    <?php foreach ( $all_units as $unit_key => $unit_label ) : ?>
                                        <option value="<?php echo esc_attr( $unit_key ); ?>"
                                                <?php selected( $editing_config['default_unit'] ?? 'ft', $unit_key ); ?>>
                                            <?php echo esc_html( $unit_label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>

                        <!-- Available units -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Available Units', 'bannercalc' ); ?></th>
                            <td>
                                <?php
                                $cat_units = (array) ( $editing_config['available_units'] ?? [] );
                                foreach ( $all_units as $unit_key => $unit_label ) : ?>
                                    <label class="bannercalc-checkbox-label">
                                        <input type="checkbox"
                                               name="bannercalc_category[available_units][]"
                                               value="<?php echo esc_attr( $unit_key ); ?>"
                                               <?php checked( in_array( $unit_key, $cat_units, true ) ); ?> />
                                        <?php echo esc_html( $unit_label ); ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>

                        <!-- Area rate per sqft -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Area Rate (per sqft)', 'bannercalc' ); ?></th>
                            <td>
                                <input type="number"
                                       name="bannercalc_category[area_rate_sqft]"
                                       value="<?php echo esc_attr( $editing_config['area_rate_sqft'] ?? '' ); ?>"
                                       step="0.01" min="0" class="small-text" />
                                <span class="bannercalc-field-hint"><?php esc_html_e( 'e.g. 1.60', 'bannercalc' ); ?></span>
                            </td>
                        </tr>

                        <!-- Area rate per sqm -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Area Rate (per sqm)', 'bannercalc' ); ?></th>
                            <td>
                                <input type="number"
                                       name="bannercalc_category[area_rate_sqm]"
                                       value="<?php echo esc_attr( $editing_config['area_rate_sqm'] ?? '' ); ?>"
                                       step="0.01" min="0" class="small-text" />
                                <span class="bannercalc-field-hint"><?php esc_html_e( 'e.g. 17.00', 'bannercalc' ); ?></span>
                            </td>
                        </tr>

                        <!-- Minimum charge -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Minimum Charge', 'bannercalc' ); ?></th>
                            <td>
                                <input type="number"
                                       name="bannercalc_category[minimum_charge]"
                                       value="<?php echo esc_attr( $editing_config['minimum_charge'] ?? '' ); ?>"
                                       step="0.01" min="0" class="small-text" />
                            </td>
                        </tr>

                        <!-- Dimension constraints -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Dimension Constraints (m)', 'bannercalc' ); ?></th>
                            <td>
                                <div class="bannercalc-dimension-grid">
                                    <label>
                                        <span class="bannercalc-dim-label"><?php esc_html_e( 'Min Width', 'bannercalc' ); ?></span>
                                        <input type="number" name="bannercalc_category[min_width_m]"
                                               value="<?php echo esc_attr( $editing_config['min_width_m'] ?? '' ); ?>"
                                               step="0.0001" min="0" class="small-text" />
                                    </label>
                                    <label>
                                        <span class="bannercalc-dim-label"><?php esc_html_e( 'Min Height', 'bannercalc' ); ?></span>
                                        <input type="number" name="bannercalc_category[min_height_m]"
                                               value="<?php echo esc_attr( $editing_config['min_height_m'] ?? '' ); ?>"
                                               step="0.0001" min="0" class="small-text" />
                                    </label>
                                    <label>
                                        <span class="bannercalc-dim-label"><?php esc_html_e( 'Max Width', 'bannercalc' ); ?></span>
                                        <input type="number" name="bannercalc_category[max_width_m]"
                                               value="<?php echo esc_attr( $editing_config['max_width_m'] ?? '' ); ?>"
                                               step="0.0001" min="0" class="small-text" />
                                    </label>
                                    <label>
                                        <span class="bannercalc-dim-label"><?php esc_html_e( 'Max Height', 'bannercalc' ); ?></span>
                                        <input type="number" name="bannercalc_category[max_height_m]"
                                               value="<?php echo esc_attr( $editing_config['max_height_m'] ?? '' ); ?>"
                                               step="0.0001" min="0" class="small-text" />
                                    </label>
                                </div>
                            </td>
                        </tr>

                        <!-- Enabled attributes -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enabled Attributes', 'bannercalc' ); ?></th>
                            <td>
                                <?php
                                $enabled_attrs = (array) ( $editing_config['enabled_attributes'] ?? [] );
                                if ( ! empty( $attributes ) ) :
                                    foreach ( $attributes as $tax => $attr ) : ?>
                                        <label class="bannercalc-checkbox-label">
                                            <input type="checkbox"
                                                   name="bannercalc_category[enabled_attributes][]"
                                                   value="<?php echo esc_attr( $tax ); ?>"
                                                   <?php checked( in_array( $tax, $enabled_attrs, true ) ); ?> />
                                            <?php echo esc_html( $attr['name'] ); ?>
                                            <code class="bannercalc-tax-slug"><?php echo esc_html( $tax ); ?></code>
                                        </label>
                                    <?php endforeach;
                                else : ?>
                                    <p class="bannercalc-field-hint"><?php esc_html_e( 'No WooCommerce attributes found.', 'bannercalc' ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>

                    </table>

                    <div class="bannercalc-form-actions">
                        <button type="submit" name="bannercalc_category_save" class="button bannercalc-btn-primary">
                            <?php esc_html_e( 'Save Category Config', 'bannercalc' ); ?>
                        </button>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bannercalc-categories' ) ); ?>" class="button bannercalc-btn-secondary">
                            <?php esc_html_e( 'Back to List', 'bannercalc' ); ?>
                        </a>
                    </div>
                </form>

            <?php else : ?>
                <div class="bannercalc-empty-state">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <p><?php esc_html_e( 'Select a product category from the list to configure its BannerCalc defaults.', 'bannercalc' ); ?></p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Version Footer -->
    <div class="bannercalc-admin-footer">
        <span class="bannercalc-version-badge">v<?php echo esc_html( BANNERCALC_VERSION ); ?></span>
        <span class="bannercalc-footer-text"><?php esc_html_e( 'BannerCalc — Product Configurator & Pricing Engine', 'bannercalc' ); ?></span>
    </div>
</div>
