<?php
/**
 * Main configurator wrapper template.
 *
 * @package BannerCalc
 * @var string $sizing_mode
 * @var string $default_unit
 * @var array  $available_units
 * @var array  $preset_sizes
 * @var array  $enabled_attrs
 * @var array  $attribute_pricing
 * @var string $currency
 * @var \BannerCalc\AttributeManager $attr_mgr
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<!-- CMYK Brand Bar -->
<div class="bannercalc-cmyk-bar"><span></span><span></span><span></span><span></span></div>

<!-- 1. SIZE SECTION -->
<?php if ( $sizing_mode !== 'none' ) : ?>
<div class="bannercalc-section bannercalc-size-section">
    <?php include BANNERCALC_PLUGIN_DIR . 'frontend/views/size-selector.php'; ?>
</div>
<?php endif; ?>

<!-- 2. ATTRIBUTE SELECTORS -->
<?php if ( ! empty( $enabled_attrs ) ) : ?>
<div class="bannercalc-section bannercalc-attributes-section">
    <div class="bannercalc-section-overline"><?php esc_html_e( 'OPTIONS', 'bannercalc' ); ?></div>
    <?php
    foreach ( $enabled_attrs as $taxonomy ) {
        if ( $taxonomy === 'pa_size' ) {
            continue; // Size is handled in the size section.
        }

        $attr_config = $attribute_pricing[ $taxonomy ] ?? [];
        $terms       = $attr_mgr->get_attribute_terms( $taxonomy );

        if ( empty( $terms ) ) {
            continue;
        }

        include BANNERCALC_PLUGIN_DIR . 'frontend/views/attribute-selector.php';
    }
    ?>
</div>
<?php endif; ?>

<!-- 3. PRICE DISPLAY -->
<div class="bannercalc-section bannercalc-price-section">
    <div class="bannercalc-section-overline"><?php esc_html_e( 'PRICING', 'bannercalc' ); ?></div>
    <?php include BANNERCALC_PLUGIN_DIR . 'frontend/views/price-display.php'; ?>
</div>

<!-- Hidden fields for form submission -->
<input type="hidden" name="bannercalc[product_id]" value="<?php echo esc_attr( $product->get_id() ); ?>" />
<input type="hidden" name="bannercalc[sizing_mode]" value="" id="bannercalc-input-sizing-mode" />
<input type="hidden" name="bannercalc[preset_slug]" value="" id="bannercalc-input-preset-slug" />
<input type="hidden" name="bannercalc[unit]" value="" id="bannercalc-input-unit" />
<input type="hidden" name="bannercalc[width_raw]" value="" id="bannercalc-input-width" />
<input type="hidden" name="bannercalc[height_raw]" value="" id="bannercalc-input-height" />
<input type="hidden" name="bannercalc[calculated_price]" value="" id="bannercalc-input-price" />
