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
    <!-- CMYK Brand Bar -->
    <div class="bannercalc-cmyk-bar"><span></span><span></span><span></span><span></span></div>

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

        // Group definitions for layout.
        $compact_group   = [ 'pa_hemming', 'pa_packaging' ];
        $inline_group    = [ 'pa_finish', 'pa_cable-ties' ];
        $dropdown_group  = [ 'pa_eyelets', 'pa_pole-pockets' ];

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

        // Render in specific order: compact → inline → dropdown (bottom).
        $rendered = [];

        // 1) Compact pair (hemming + packaging) in 2-column row.
        $compact_ready = array_intersect_key( $renderable, array_flip( $compact_group ) );
        if ( count( $compact_ready ) > 1 ) {
            echo '<div class="bannercalc-attr-row bannercalc-attr-row--compact">';
            foreach ( $compact_group as $taxonomy ) {
                if ( ! isset( $renderable[ $taxonomy ] ) ) continue;
                $attr_config = $renderable[ $taxonomy ]['config'];
                $terms       = $renderable[ $taxonomy ]['terms'];
                include BANNERCALC_PLUGIN_DIR . 'frontend/views/attribute-selector.php';
                $rendered[] = $taxonomy;
            }
            echo '</div>';
        }

        // 2) Finish + Cable Ties in 2-column row.
        $inline_ready = array_intersect_key( $renderable, array_flip( $inline_group ) );
        if ( count( $inline_ready ) > 1 ) {
            echo '<div class="bannercalc-attr-row bannercalc-attr-row--inline">';
            foreach ( $inline_group as $taxonomy ) {
                if ( ! isset( $renderable[ $taxonomy ] ) ) continue;
                $attr_config = $renderable[ $taxonomy ]['config'];
                $terms       = $renderable[ $taxonomy ]['terms'];
                include BANNERCALC_PLUGIN_DIR . 'frontend/views/attribute-selector.php';
                $rendered[] = $taxonomy;
            }
            echo '</div>';
        }

        // 3) Any remaining ungrouped attributes (rendered in order).
        foreach ( $renderable as $taxonomy => $data ) {
            if ( in_array( $taxonomy, $rendered, true ) ) {
                if ( in_array( $taxonomy, $dropdown_group, true ) ) {
                    continue; // will be rendered in step 4
                }
                continue;
            }
            if ( in_array( $taxonomy, $dropdown_group, true ) ) {
                continue; // will be rendered in step 4
            }
            $attr_config = $data['config'];
            $terms       = $data['terms'];
            include BANNERCALC_PLUGIN_DIR . 'frontend/views/attribute-selector.php';
            $rendered[] = $taxonomy;
        }

        // 4) Dropdown pair (eyelets + pole pockets) at bottom of options card.
        $dropdown_ready = array_intersect_key( $renderable, array_flip( $dropdown_group ) );
        if ( ! empty( $dropdown_ready ) ) {
            $count = count( $dropdown_ready );
            if ( $count > 1 ) {
                echo '<div class="bannercalc-attr-row bannercalc-attr-row--2col">';
            }
            foreach ( $dropdown_group as $taxonomy ) {
                if ( ! isset( $renderable[ $taxonomy ] ) ) continue;
                $attr_config = $renderable[ $taxonomy ]['config'];
                $terms       = $renderable[ $taxonomy ]['terms'];
                include BANNERCALC_PLUGIN_DIR . 'frontend/views/attribute-selector.php';
                $rendered[] = $taxonomy;
            }
            if ( $count > 1 ) {
                echo '</div>';
            }
        }
        ?>
    </div>
    <?php endif; ?>

    <!-- PRICE DISPLAY -->
    <div class="bannercalc-section bannercalc-price-section">
        <div class="bannercalc-section-overline"><?php esc_html_e( 'PRICING', 'bannercalc' ); ?></div>
        <?php include BANNERCALC_PLUGIN_DIR . 'frontend/views/price-display.php'; ?>
    </div>
</div>

<!-- Hidden fields for form submission -->
<input type="hidden" name="bannercalc[product_id]" value="<?php echo esc_attr( $product->get_id() ); ?>" />
<input type="hidden" name="bannercalc[sizing_mode]" value="" id="bannercalc-input-sizing-mode" />
<input type="hidden" name="bannercalc[preset_slug]" value="" id="bannercalc-input-preset-slug" />
<input type="hidden" name="bannercalc[unit]" value="" id="bannercalc-input-unit" />
<input type="hidden" name="bannercalc[width_raw]" value="" id="bannercalc-input-width" />
<input type="hidden" name="bannercalc[height_raw]" value="" id="bannercalc-input-height" />
<input type="hidden" name="bannercalc[calculated_price]" value="" id="bannercalc-input-price" />
