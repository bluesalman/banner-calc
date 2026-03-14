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
    'parent'     => 0,
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
        $config = [];

        $config['enabled']      = ! empty( $raw['enabled'] );
        $config['sizing_mode']     = sanitize_text_field( $raw['sizing_mode'] ?? 'preset_and_custom' );
        $config['default_unit']    = sanitize_text_field( $raw['default_unit'] ?? 'ft' );
        $config['preset_columns']  = max( 1, min( 6, (int) ( $raw['preset_columns'] ?? 4 ) ) );

        $config['area_rate_sqft'] = (float) ( $raw['area_rate_sqft'] ?? 0 );
        $config['area_rate_sqm']  = (float) ( $raw['area_rate_sqm'] ?? 0 );
        $config['minimum_charge'] = (float) ( $raw['minimum_charge'] ?? 0 );

        // Dimension constraints — stored in metres.
        // Admin enters in their chosen constraint_unit, we convert to metres.
        $constraint_unit = sanitize_text_field( $raw['constraint_unit'] ?? 'm' );
        $factor = \BannerCalc\UnitConverter::TO_METRES[ $constraint_unit ] ?? 1.0;

        $min_w = (float) ( $raw['min_width'] ?? 0 );
        $min_h = (float) ( $raw['min_height'] ?? 0 );
        $max_w = (float) ( $raw['max_width'] ?? 0 );
        $max_h = (float) ( $raw['max_height'] ?? 0 );

        $config['min_width_m']    = $min_w > 0 ? $min_w * $factor : 0;
        $config['min_height_m']   = $min_h > 0 ? $min_h * $factor : 0;
        $config['max_width_m']    = $max_w > 0 ? $max_w * $factor : 0;
        $config['max_height_m']   = $max_h > 0 ? $max_h * $factor : 0;
        $config['constraint_unit'] = $constraint_unit;

        $allowed_units = [ 'mm', 'cm', 'inch', 'ft', 'm' ];
        $config['available_units'] = array_values(
            array_intersect( (array) ( $raw['available_units'] ?? [] ), $allowed_units )
        );

        $config['enabled_attributes'] = array_map( 'sanitize_text_field', (array) ( $raw['enabled_attributes'] ?? [] ) );

        // Sanitize attribute pricing.
        $raw_attr_pricing = (array) ( $raw['attribute_pricing'] ?? [] );
        $sanitized_attr_pricing = [];
        foreach ( $raw_attr_pricing as $ap_tax => $ap_data ) {
            $ap_tax = sanitize_text_field( $ap_tax );
            $sanitized_attr_pricing[ $ap_tax ] = [
                'pricing_type'  => sanitize_text_field( $ap_data['pricing_type'] ?? 'fixed' ),
                'default_value' => sanitize_text_field( $ap_data['default_value'] ?? '' ),
                'required'      => ! empty( $ap_data['required'] ),
                'values'        => [],
            ];
            if ( ! empty( $ap_data['values'] ) && is_array( $ap_data['values'] ) ) {
                foreach ( $ap_data['values'] as $term_slug => $modifier ) {
                    $sanitized_attr_pricing[ $ap_tax ]['values'][ sanitize_text_field( $term_slug ) ] = (float) $modifier;
                }
            }
        }
        $config['attribute_pricing'] = $sanitized_attr_pricing;

        // Preset sizes — parse repeater rows.
        $preset_sizes = [];
        if ( ! empty( $raw['preset_label'] ) && is_array( $raw['preset_label'] ) ) {
            foreach ( $raw['preset_label'] as $i => $label ) {
                $label = sanitize_text_field( $label );
                if ( empty( $label ) ) {
                    continue;
                }
                $p_width  = (float) ( $raw['preset_width'][ $i ] ?? 0 );
                $p_height = (float) ( $raw['preset_height'][ $i ] ?? 0 );
                $p_unit   = sanitize_text_field( $raw['preset_unit'][ $i ] ?? 'ft' );
                $p_price  = $raw['preset_price'][ $i ] ?? '';
                $p_factor = \BannerCalc\UnitConverter::TO_METRES[ $p_unit ] ?? 1.0;

                if ( $p_width <= 0 || $p_height <= 0 ) {
                    continue;
                }

                $p_desc       = sanitize_text_field( $raw['preset_desc'][ $i ] ?? '' );
                $p_popularity = absint( $raw['preset_popularity'][ $i ] ?? 3 );
                $p_popularity = max( 1, min( 5, $p_popularity ) );

                $preset_sizes[] = [
                    'label'        => $label,
                    'slug'         => sanitize_title( $label ),
                    'width_m'      => $p_width * $p_factor,
                    'height_m'     => $p_height * $p_factor,
                    'display_w'    => $p_width,
                    'display_h'    => $p_height,
                    'display_unit' => $p_unit,
                    'price'        => $p_price !== '' ? (float) $p_price : null,
                    'description'  => $p_desc,
                    'popularity'   => $p_popularity,
                ];
            }
        }

        $config['preset_sizes'] = $preset_sizes;

        // Quantity settings.
        $config['quantity_mode']    = sanitize_text_field( $raw['quantity_mode'] ?? 'standard' );
        $config['min_quantity']     = absint( $raw['min_quantity'] ?? 0 );
        $config['default_quantity'] = max( 1, absint( $raw['default_quantity'] ?? 1 ) );

        // Bundle quantities — comma-separated string → array.
        $bundles = [];
        $raw_bundles = sanitize_text_field( $raw['quantity_bundles_csv'] ?? '' );
        if ( ! empty( $raw_bundles ) ) {
            $parts = array_map( 'trim', explode( ',', $raw_bundles ) );
            foreach ( $parts as $part ) {
                $qty = absint( $part );
                if ( $qty > 0 ) {
                    $bundles[] = [ 'qty' => $qty, 'label' => $qty . ' pcs' ];
                }
            }
        }
        $config['quantity_bundles'] = $bundles;

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

                        <!-- Size grid columns -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Size Grid Columns', 'bannercalc' ); ?></th>
                            <td>
                                <input type="number"
                                       name="bannercalc_category[preset_columns]"
                                       value="<?php echo esc_attr( $editing_config['preset_columns'] ?? 4 ); ?>"
                                       min="1" max="6" step="1" class="small-text">
                                <p class="description"><?php esc_html_e( 'Number of columns in the preset-size grid (1–6).', 'bannercalc' ); ?></p>
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
                            <th scope="row"><?php esc_html_e( 'Dimension Constraints', 'bannercalc' ); ?></th>
                            <td>
                                <?php
                                $constraint_unit = $editing_config['constraint_unit'] ?? 'ft';
                                $c_factor = \BannerCalc\UnitConverter::TO_METRES[ $constraint_unit ] ?? 1.0;
                                // Convert stored metres back to display unit.
                                $disp_min_w = ! empty( $editing_config['min_width_m'] ) ? round( (float) $editing_config['min_width_m'] / $c_factor, 4 ) : '';
                                $disp_min_h = ! empty( $editing_config['min_height_m'] ) ? round( (float) $editing_config['min_height_m'] / $c_factor, 4 ) : '';
                                $disp_max_w = ! empty( $editing_config['max_width_m'] ) ? round( (float) $editing_config['max_width_m'] / $c_factor, 4 ) : '';
                                $disp_max_h = ! empty( $editing_config['max_height_m'] ) ? round( (float) $editing_config['max_height_m'] / $c_factor, 4 ) : '';
                                ?>
                                <div style="margin-bottom:10px;">
                                    <label style="font-size:12px;color:#555;"><?php esc_html_e( 'Unit for entry:', 'bannercalc' ); ?></label>
                                    <select name="bannercalc_category[constraint_unit]" class="bannercalc-select" style="width:auto;margin-left:6px;">
                                        <?php foreach ( $all_units as $u_key => $u_label ) : ?>
                                            <option value="<?php echo esc_attr( $u_key ); ?>"
                                                    <?php selected( $constraint_unit, $u_key ); ?>>
                                                <?php echo esc_html( $u_label ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="bannercalc-dimension-grid">
                                    <label>
                                        <span class="bannercalc-dim-label"><?php esc_html_e( 'Min Width', 'bannercalc' ); ?></span>
                                        <input type="number" name="bannercalc_category[min_width]"
                                               value="<?php echo esc_attr( $disp_min_w ); ?>"
                                               step="any" min="0" class="small-text"
                                               placeholder="<?php esc_attr_e( 'No limit', 'bannercalc' ); ?>" />
                                    </label>
                                    <label>
                                        <span class="bannercalc-dim-label"><?php esc_html_e( 'Min Height', 'bannercalc' ); ?></span>
                                        <input type="number" name="bannercalc_category[min_height]"
                                               value="<?php echo esc_attr( $disp_min_h ); ?>"
                                               step="any" min="0" class="small-text"
                                               placeholder="<?php esc_attr_e( 'No limit', 'bannercalc' ); ?>" />
                                    </label>
                                    <label>
                                        <span class="bannercalc-dim-label"><?php esc_html_e( 'Max Width', 'bannercalc' ); ?></span>
                                        <input type="number" name="bannercalc_category[max_width]"
                                               value="<?php echo esc_attr( $disp_max_w ); ?>"
                                               step="any" min="0" class="small-text"
                                               placeholder="<?php esc_attr_e( 'Unlimited', 'bannercalc' ); ?>" />
                                    </label>
                                    <label>
                                        <span class="bannercalc-dim-label"><?php esc_html_e( 'Max Height', 'bannercalc' ); ?></span>
                                        <input type="number" name="bannercalc_category[max_height]"
                                               value="<?php echo esc_attr( $disp_max_h ); ?>"
                                               step="any" min="0" class="small-text"
                                               placeholder="<?php esc_attr_e( 'Unlimited', 'bannercalc' ); ?>" />
                                    </label>
                                </div>
                                <p class="bannercalc-field-hint" style="margin-top:6px;"><?php esc_html_e( 'Leave blank for no limit. Values stored internally in metres.', 'bannercalc' ); ?></p>
                            </td>
                        </tr>

                        <!-- Quantity Mode -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Quantity Mode', 'bannercalc' ); ?></th>
                            <td>
                                <select name="bannercalc_category[quantity_mode]" class="bannercalc-select" id="bannercalc-quantity-mode">
                                    <option value="standard" <?php selected( $editing_config['quantity_mode'] ?? 'standard', 'standard' ); ?>>
                                        <?php esc_html_e( 'Standard (free quantity input)', 'bannercalc' ); ?>
                                    </option>
                                    <option value="bundles" <?php selected( $editing_config['quantity_mode'] ?? 'standard', 'bundles' ); ?>>
                                        <?php esc_html_e( 'Bundle Quantities (predefined tiers)', 'bannercalc' ); ?>
                                    </option>
                                </select>
                                <p class="bannercalc-field-hint">
                                    <?php esc_html_e( 'Bundle mode replaces the standard qty input with selectable quantity pills.', 'bannercalc' ); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- Minimum Order Quantity -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Min Order Quantity', 'bannercalc' ); ?></th>
                            <td>
                                <input type="number"
                                       name="bannercalc_category[min_quantity]"
                                       value="<?php echo esc_attr( $editing_config['min_quantity'] ?? '' ); ?>"
                                       step="1" min="0" class="small-text" />
                                <span class="bannercalc-field-hint"><?php esc_html_e( '0 = no minimum', 'bannercalc' ); ?></span>
                            </td>
                        </tr>

                        <!-- Default Quantity -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Default Quantity', 'bannercalc' ); ?></th>
                            <td>
                                <input type="number"
                                       name="bannercalc_category[default_quantity]"
                                       value="<?php echo esc_attr( $editing_config['default_quantity'] ?? 1 ); ?>"
                                       step="1" min="1" class="small-text" />
                                <span class="bannercalc-field-hint"><?php esc_html_e( 'Pre-filled quantity on product page', 'bannercalc' ); ?></span>
                            </td>
                        </tr>

                        <!-- Bundle Quantities -->
                        <tr id="bannercalc-bundles-row" style="<?php echo ( $editing_config['quantity_mode'] ?? 'standard' ) !== 'bundles' ? 'display:none;' : ''; ?>">
                            <th scope="row"><?php esc_html_e( 'Bundle Quantities', 'bannercalc' ); ?></th>
                            <td>
                                <?php
                                $bundle_csv = '';
                                $existing_bundles = $editing_config['quantity_bundles'] ?? [];
                                if ( ! empty( $existing_bundles ) ) {
                                    $bundle_csv = implode( ', ', array_column( $existing_bundles, 'qty' ) );
                                }
                                ?>
                                <input type="text"
                                       name="bannercalc_category[quantity_bundles_csv]"
                                       value="<?php echo esc_attr( $bundle_csv ); ?>"
                                       class="regular-text"
                                       placeholder="<?php esc_attr_e( 'e.g. 25, 50, 100, 250, 500', 'bannercalc' ); ?>" />
                                <p class="bannercalc-field-hint">
                                    <?php esc_html_e( 'Comma-separated quantity tiers. Customers pick from these as clickable pills.', 'bannercalc' ); ?>
                                </p>
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

                        <!-- Attribute Pricing -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Attribute Pricing', 'bannercalc' ); ?></th>
                            <td>
                                <?php
                                $enabled_attr_list = (array) ( $editing_config['enabled_attributes'] ?? [] );
                                $saved_attr_pricing = (array) ( $editing_config['attribute_pricing'] ?? [] );

                                if ( ! empty( $enabled_attr_list ) ) :
                                    foreach ( $enabled_attr_list as $ea_tax ) :
                                        $ea_label = wc_attribute_label( $ea_tax );
                                        $ea_terms = $attr_mgr->get_attribute_terms( $ea_tax );
                                        $ea_conf  = $saved_attr_pricing[ $ea_tax ] ?? [];
                                        $ea_pricing_type = $ea_conf['pricing_type'] ?? 'fixed';
                                        $ea_values       = $ea_conf['values'] ?? [];
                                        $ea_default      = $ea_conf['default_value'] ?? '';
                                        $ea_required     = ! empty( $ea_conf['required'] );

                                        if ( empty( $ea_terms ) ) continue;
                                ?>
                                    <div class="bannercalc-attr-pricing-block" style="margin-bottom:20px;padding:14px 16px;border:1px solid #e2e4e7;border-radius:8px;background:#fafbfc;">
                                        <h4 style="margin:0 0 10px;font-size:13px;font-weight:700;color:#1e1e1e;">
                                            <?php echo esc_html( $ea_label ); ?>
                                            <code style="font-size:11px;color:#8892A0;font-weight:400;margin-left:6px;"><?php echo esc_html( $ea_tax ); ?></code>
                                        </h4>
                                        <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:10px;">
                                            <label style="font-size:12px;">
                                                <?php esc_html_e( 'Pricing Type:', 'bannercalc' ); ?>
                                                <select name="bannercalc_category[attribute_pricing][<?php echo esc_attr( $ea_tax ); ?>][pricing_type]" style="margin-left:4px;">
                                                    <option value="fixed" <?php selected( $ea_pricing_type, 'fixed' ); ?>><?php esc_html_e( 'Fixed (£)', 'bannercalc' ); ?></option>
                                                    <option value="per_sqft" <?php selected( $ea_pricing_type, 'per_sqft' ); ?>><?php esc_html_e( 'Per sqft (£)', 'bannercalc' ); ?></option>
                                                    <option value="percentage" <?php selected( $ea_pricing_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage (%)', 'bannercalc' ); ?></option>
                                                </select>
                                            </label>
                                            <label style="font-size:12px;">
                                                <?php esc_html_e( 'Default Value:', 'bannercalc' ); ?>
                                                <select name="bannercalc_category[attribute_pricing][<?php echo esc_attr( $ea_tax ); ?>][default_value]" style="margin-left:4px;">
                                                    <option value=""><?php esc_html_e( '— None —', 'bannercalc' ); ?></option>
                                                    <?php foreach ( $ea_terms as $eat ) : ?>
                                                        <option value="<?php echo esc_attr( $eat['slug'] ); ?>" <?php selected( $ea_default, $eat['slug'] ); ?>>
                                                            <?php echo esc_html( $eat['name'] ); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <label style="font-size:12px;">
                                                <input type="checkbox"
                                                       name="bannercalc_category[attribute_pricing][<?php echo esc_attr( $ea_tax ); ?>][required]"
                                                       value="1"
                                                       <?php checked( $ea_required ); ?> />
                                                <?php esc_html_e( 'Required', 'bannercalc' ); ?>
                                            </label>
                                        </div>
                                        <table style="width:100%;border-collapse:collapse;">
                                            <thead>
                                                <tr>
                                                    <th style="text-align:left;padding:4px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#8892A0;"><?php esc_html_e( 'Term', 'bannercalc' ); ?></th>
                                                    <th style="text-align:left;padding:4px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#8892A0;"><?php esc_html_e( 'Slug', 'bannercalc' ); ?></th>
                                                    <th style="text-align:left;padding:4px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#8892A0;"><?php esc_html_e( 'Modifier', 'bannercalc' ); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ( $ea_terms as $eat ) : ?>
                                                    <tr>
                                                        <td style="padding:3px 8px;font-size:13px;"><?php echo esc_html( $eat['name'] ); ?></td>
                                                        <td style="padding:3px 8px;font-size:12px;color:#8892A0;"><?php echo esc_html( $eat['slug'] ); ?></td>
                                                        <td style="padding:3px 8px;">
                                                            <input type="number"
                                                                   name="bannercalc_category[attribute_pricing][<?php echo esc_attr( $ea_tax ); ?>][values][<?php echo esc_attr( $eat['slug'] ); ?>]"
                                                                   value="<?php echo esc_attr( $ea_values[ $eat['slug'] ] ?? '' ); ?>"
                                                                   step="0.01" min="0" style="width:90px;"
                                                                   placeholder="0.00" />
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php
                                    endforeach;
                                else : ?>
                                    <p class="bannercalc-field-hint"><?php esc_html_e( 'Enable attributes above, then save to configure pricing for each.', 'bannercalc' ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Preset Sizes -->
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Preset Sizes', 'bannercalc' ); ?></th>
                            <td>
                                <p class="bannercalc-field-hint" style="margin-bottom:10px;">
                                    <?php esc_html_e( 'Define popular sizes for the "Popular Sizes" tab on the product page. Leave price blank to auto-calculate from area rate.', 'bannercalc' ); ?>
                                </p>
                                <div id="bannercalc-presets-repeater">
                                    <table class="bannercalc-presets-table" style="width:100%;border-collapse:collapse;">
                                        <thead>
                                            <tr>
                                                <th style="text-align:left;padding:6px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#8892A0;"><?php esc_html_e( 'Label', 'bannercalc' ); ?></th>
                                                <th style="text-align:left;padding:6px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#8892A0;"><?php esc_html_e( 'W', 'bannercalc' ); ?></th>
                                                <th style="text-align:left;padding:6px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#8892A0;"><?php esc_html_e( 'H', 'bannercalc' ); ?></th>
                                                <th style="text-align:left;padding:6px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#8892A0;"><?php esc_html_e( 'Unit', 'bannercalc' ); ?></th>
                                                <th style="text-align:left;padding:6px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#8892A0;"><?php esc_html_e( 'Common Use / Description', 'bannercalc' ); ?></th>
                                                <th style="text-align:center;padding:6px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#8892A0;width:50px;"><?php esc_html_e( '★', 'bannercalc' ); ?></th>
                                                <th style="text-align:left;padding:6px 8px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#8892A0;"><?php esc_html_e( 'Price (£)', 'bannercalc' ); ?></th>
                                                <th style="width:40px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="bannercalc-presets-body">
                                            <?php
                                            $existing_presets = $editing_config['preset_sizes'] ?? [];
                                            if ( ! empty( $existing_presets ) ) :
                                                foreach ( $existing_presets as $pi => $ps ) :
                                            ?>
                                                <tr class="bannercalc-preset-row">
                                                    <td style="padding:4px 4px;">
                                                        <input type="text" name="bannercalc_category[preset_label][]"
                                                               value="<?php echo esc_attr( $ps['label'] ?? '' ); ?>"
                                                               placeholder="<?php esc_attr_e( 'e.g. 6ft × 3ft', 'bannercalc' ); ?>"
                                                               style="width:100%;" />
                                                    </td>
                                                    <td style="padding:4px 4px;">
                                                        <input type="number" name="bannercalc_category[preset_width][]"
                                                               value="<?php echo esc_attr( $ps['display_w'] ?? '' ); ?>"
                                                               step="any" min="0" style="width:70px;" />
                                                    </td>
                                                    <td style="padding:4px 4px;">
                                                        <input type="number" name="bannercalc_category[preset_height][]"
                                                               value="<?php echo esc_attr( $ps['display_h'] ?? '' ); ?>"
                                                               step="any" min="0" style="width:70px;" />
                                                    </td>
                                                    <td style="padding:4px 4px;">
                                                        <select name="bannercalc_category[preset_unit][]" style="width:80px;">
                                                            <?php foreach ( $all_units as $pu_key => $pu_label ) : ?>
                                                                <option value="<?php echo esc_attr( $pu_key ); ?>"
                                                                        <?php selected( $ps['display_unit'] ?? 'ft', $pu_key ); ?>>
                                                                    <?php echo esc_html( $pu_label ); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td style="padding:4px 4px;">
                                                        <input type="text" name="bannercalc_category[preset_desc][]"
                                                               value="<?php echo esc_attr( $ps['description'] ?? '' ); ?>"
                                                               placeholder="<?php esc_attr_e( 'e.g. Trade shows, retail', 'bannercalc' ); ?>"
                                                               style="width:100%;" />
                                                    </td>
                                                    <td style="padding:4px 4px;text-align:center;">
                                                        <select name="bannercalc_category[preset_popularity][]" style="width:50px;">
                                                            <?php for ( $star = 5; $star >= 1; $star-- ) : ?>
                                                                <option value="<?php echo $star; ?>" <?php selected( (int) ( $ps['popularity'] ?? 3 ), $star ); ?>><?php echo $star; ?>★</option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </td>
                                                    <td style="padding:4px 4px;">
                                                        <input type="number" name="bannercalc_category[preset_price][]"
                                                               value="<?php echo esc_attr( $ps['price'] ?? '' ); ?>"
                                                               step="0.01" min="0" style="width:80px;"
                                                               placeholder="<?php esc_attr_e( 'Auto', 'bannercalc' ); ?>" />
                                                    </td>
                                                    <td style="padding:4px 4px;text-align:center;">
                                                        <button type="button" class="bannercalc-remove-preset" title="<?php esc_attr_e( 'Remove', 'bannercalc' ); ?>"
                                                                style="background:none;border:none;color:#ED1C24;cursor:pointer;font-size:18px;">&times;</button>
                                                    </td>
                                                </tr>
                                            <?php
                                                endforeach;
                                            endif;
                                            ?>
                                        </tbody>
                                    </table>
                                    <div style="display:flex;gap:10px;align-items:center;margin-top:10px;">
                                        <button type="button" id="bannercalc-add-preset" class="button bannercalc-btn-secondary">
                                            + <?php esc_html_e( 'Add Preset Size', 'bannercalc' ); ?>
                                        </button>
                                        <button type="button" id="bannercalc-import-presets" class="button bannercalc-btn-secondary"
                                                data-cat-id="<?php echo esc_attr( $editing_cat_id ); ?>"
                                                title="<?php esc_attr_e( 'Import popular UK sizes for this category from reference data', 'bannercalc' ); ?>">
                                            ↓ <?php esc_html_e( 'Import Popular Sizes', 'bannercalc' ); ?>
                                        </button>
                                        <span id="bannercalc-import-status" style="font-size:12px;color:#8892A0;"></span>
                                    </div>
                                </div>
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
