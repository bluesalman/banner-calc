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

<!-- ═══════════ CARD 1: SIZE ═══════════ -->
<div class="bannercalc-card bannercalc-card--size">
    <?php if ( $sizing_mode !== 'none' ) : ?>
    <div class="bannercalc-section bannercalc-size-section">
        <?php include BANNERCALC_PLUGIN_DIR . 'frontend/views/size-selector.php'; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════════ CARD 2: OPTIONS + PRICING ═══════════ -->
<div class="bannercalc-card bannercalc-card--options">
    <?php if ( ! empty( $enabled_attrs ) ) : ?>
    <div class="bannercalc-section bannercalc-attributes-section">
        <div class="bannercalc-section-overline"><?php esc_html_e( 'OPTIONS', 'bannercalc' ); ?></div>
        <?php
        // Attributes that are handled by the size section — skip from OPTIONS.
        $size_taxonomies = [ 'pa_size', 'pa_banner-size-width-height' ];

        // Group definitions for 3-column grid layout.
        // Row 1: Eyelets, Pole Pockets, Packaging
        // Row 2: Finish, Hemming, Cable Ties (hemming + cable ties bottom-aligned)
        $row1_group = [ 'pa_eyelets', 'pa_pole-pockets', 'pa_packaging' ];
        $row2_group = [ 'pa_finish', 'pa_hemming', 'pa_cable-ties' ];

        // Build ordered attribute list (excluding size taxonomies and empty).
        $renderable = [];
        foreach ( $enabled_attrs as $taxonomy ) {
            if ( in_array( $taxonomy, $size_taxonomies, true ) ) {
                continue;
            }
            $attr_config = $attribute_pricing[ $taxonomy ] ?? [];
            $terms       = $attr_mgr->get_attribute_terms( $taxonomy );
            if ( empty( $terms ) ) {
                continue;
            }
            $renderable[ $taxonomy ] = [ 'config' => $attr_config, 'terms' => $terms ];
        }

        $rendered = [];

        // Row 1: Eyelets, Pole Pockets, Packaging — 3-column grid.
        $row1_ready = array_intersect_key( $renderable, array_flip( $row1_group ) );
        if ( ! empty( $row1_ready ) ) {
            echo '<div class="bannercalc-attr-row bannercalc-attr-row--3col">';
            foreach ( $row1_group as $taxonomy ) {
                if ( ! isset( $renderable[ $taxonomy ] ) ) continue;
                $attr_config = $renderable[ $taxonomy ]['config'];
                $terms       = $renderable[ $taxonomy ]['terms'];
                include BANNERCALC_PLUGIN_DIR . 'frontend/views/attribute-selector.php';
                $rendered[] = $taxonomy;
            }
            echo '</div>';
        }

        // Row 2: Finish, Hemming, Cable Ties — 3-column grid, hemming + cable-ties bottom-aligned.
        $row2_ready = array_intersect_key( $renderable, array_flip( $row2_group ) );
        if ( ! empty( $row2_ready ) ) {
            echo '<div class="bannercalc-attr-row bannercalc-attr-row--3col bannercalc-attr-row--bottom-align">';
            foreach ( $row2_group as $taxonomy ) {
                if ( ! isset( $renderable[ $taxonomy ] ) ) continue;
                $attr_config = $renderable[ $taxonomy ]['config'];
                $terms       = $renderable[ $taxonomy ]['terms'];
                include BANNERCALC_PLUGIN_DIR . 'frontend/views/attribute-selector.php';
                $rendered[] = $taxonomy;
            }
            echo '</div>';
        }

        // Any remaining ungrouped attributes (rendered in order).
        foreach ( $renderable as $taxonomy => $data ) {
            if ( in_array( $taxonomy, $rendered, true ) ) {
                continue;
            }
            $attr_config = $data['config'];
            $terms       = $data['terms'];
            include BANNERCALC_PLUGIN_DIR . 'frontend/views/attribute-selector.php';
            $rendered[] = $taxonomy;
        }
        ?>
    </div>
    <?php endif; ?>
</div>

<!-- Hidden fields for form submission -->
<input type="hidden" name="bannercalc[product_id]" value="<?php echo esc_attr( $product->get_id() ); ?>" />
<input type="hidden" name="bannercalc[sizing_mode]" value="" id="bannercalc-input-sizing-mode" />
<input type="hidden" name="bannercalc[preset_slug]" value="" id="bannercalc-input-preset-slug" />
<input type="hidden" name="bannercalc[unit]" value="" id="bannercalc-input-unit" />
<input type="hidden" name="bannercalc[width_raw]" value="" id="bannercalc-input-width" />
<input type="hidden" name="bannercalc[height_raw]" value="" id="bannercalc-input-height" />
<input type="hidden" name="bannercalc[calculated_price]" value="" id="bannercalc-input-price" />
