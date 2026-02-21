<?php
/**
 * Category defaults HTML template.
 *
 * Rendered on the WooCommerce product category edit screen.
 *
 * @package BannerCalc
 * @var \WP_Term $term   The category term being edited.
 * @var array    $config The current BannerCalc config for this category.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sizing_modes = [
    'preset_only'       => __( 'Preset Sizes Only', 'bannercalc' ),
    'custom_only'       => __( 'Custom Size Only', 'bannercalc' ),
    'preset_and_custom' => __( 'Preset + Custom Size', 'bannercalc' ),
    'none'              => __( 'None (fixed price)', 'bannercalc' ),
];

$all_units  = \BannerCalc\UnitConverter::get_unit_labels();
$attr_mgr   = new \BannerCalc\AttributeManager();
$attributes = $attr_mgr->get_all_attributes();
?>

<tr class="form-field bannercalc-category-config">
    <th colspan="2">
        <h3><?php esc_html_e( 'BannerCalc Configuration', 'bannercalc' ); ?></h3>
    </th>
</tr>

<!-- Enable toggle -->
<tr class="form-field">
    <th scope="row">
        <label><?php esc_html_e( 'Enable BannerCalc', 'bannercalc' ); ?></label>
    </th>
    <td>
        <label>
            <input type="checkbox"
                   name="bannercalc_category[enabled]"
                   value="1"
                   <?php checked( ! empty( $config['enabled'] ) ); ?> />
            <?php esc_html_e( 'Enable BannerCalc for products in this category', 'bannercalc' ); ?>
        </label>
    </td>
</tr>

<!-- Sizing mode -->
<tr class="form-field">
    <th scope="row">
        <label><?php esc_html_e( 'Sizing Mode', 'bannercalc' ); ?></label>
    </th>
    <td>
        <select name="bannercalc_category[sizing_mode]">
            <?php foreach ( $sizing_modes as $mode_key => $mode_label ) : ?>
                <option value="<?php echo esc_attr( $mode_key ); ?>"
                        <?php selected( $config['sizing_mode'] ?? 'preset_and_custom', $mode_key ); ?>>
                    <?php echo esc_html( $mode_label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>

<!-- Default unit -->
<tr class="form-field">
    <th scope="row">
        <label><?php esc_html_e( 'Default Unit', 'bannercalc' ); ?></label>
    </th>
    <td>
        <select name="bannercalc_category[default_unit]">
            <?php foreach ( $all_units as $unit_key => $unit_label ) : ?>
                <option value="<?php echo esc_attr( $unit_key ); ?>"
                        <?php selected( $config['default_unit'] ?? 'ft', $unit_key ); ?>>
                    <?php echo esc_html( $unit_label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>

<!-- Area rate per sqft -->
<tr class="form-field">
    <th scope="row">
        <label><?php esc_html_e( 'Area Rate (per sqft)', 'bannercalc' ); ?></label>
    </th>
    <td>
        <input type="number"
               name="bannercalc_category[area_rate_sqft]"
               value="<?php echo esc_attr( $config['area_rate_sqft'] ?? '' ); ?>"
               step="0.01"
               min="0"
               class="small-text" />
    </td>
</tr>

<!-- Minimum charge -->
<tr class="form-field">
    <th scope="row">
        <label><?php esc_html_e( 'Minimum Charge', 'bannercalc' ); ?></label>
    </th>
    <td>
        <input type="number"
               name="bannercalc_category[minimum_charge]"
               value="<?php echo esc_attr( $config['minimum_charge'] ?? '' ); ?>"
               step="0.01"
               min="0"
               class="small-text" />
    </td>
</tr>

<!-- Min / Max dimensions -->
<tr class="form-field">
    <th scope="row">
        <label><?php esc_html_e( 'Dimension Constraints (metres)', 'bannercalc' ); ?></label>
    </th>
    <td>
        <p>
            <label><?php esc_html_e( 'Min Width:', 'bannercalc' ); ?>
                <input type="number" name="bannercalc_category[min_width_m]"
                       value="<?php echo esc_attr( $config['min_width_m'] ?? '' ); ?>"
                       step="0.0001" min="0" class="small-text" />
            </label>
            <label style="margin-left: 10px;"><?php esc_html_e( 'Min Height:', 'bannercalc' ); ?>
                <input type="number" name="bannercalc_category[min_height_m]"
                       value="<?php echo esc_attr( $config['min_height_m'] ?? '' ); ?>"
                       step="0.0001" min="0" class="small-text" />
            </label>
        </p>
        <p>
            <label><?php esc_html_e( 'Max Width:', 'bannercalc' ); ?>
                <input type="number" name="bannercalc_category[max_width_m]"
                       value="<?php echo esc_attr( $config['max_width_m'] ?? '' ); ?>"
                       step="0.0001" min="0" class="small-text" />
            </label>
            <label style="margin-left: 10px;"><?php esc_html_e( 'Max Height:', 'bannercalc' ); ?>
                <input type="number" name="bannercalc_category[max_height_m]"
                       value="<?php echo esc_attr( $config['max_height_m'] ?? '' ); ?>"
                       step="0.0001" min="0" class="small-text" />
            </label>
        </p>
    </td>
</tr>

<!-- Enabled attributes -->
<tr class="form-field">
    <th scope="row">
        <label><?php esc_html_e( 'Enabled Attributes', 'bannercalc' ); ?></label>
    </th>
    <td>
        <?php
        $enabled_attrs = (array) ( $config['enabled_attributes'] ?? [] );
        foreach ( $attributes as $tax => $attr ) : ?>
            <label style="display: block; margin-bottom: 4px;">
                <input type="checkbox"
                       name="bannercalc_category[enabled_attributes][]"
                       value="<?php echo esc_attr( $tax ); ?>"
                       <?php checked( in_array( $tax, $enabled_attrs, true ) ); ?> />
                <?php echo esc_html( $attr['name'] ); ?> (<code><?php echo esc_html( $tax ); ?></code>)
            </label>
        <?php endforeach; ?>
        <p class="description">
            <?php esc_html_e( 'Select which attributes are available for products in this category. Pricing per attribute term will be configurable in a future update.', 'bannercalc' ); ?>
        </p>
    </td>
</tr>
