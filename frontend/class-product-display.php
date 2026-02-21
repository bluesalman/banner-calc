<?php
/**
 * Product Display — renders the configurator on single product pages.
 *
 * @package BannerCalc
 */

namespace BannerCalc\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ProductDisplay {

    public function __construct() {
        // Hook into single product page to render configurator.
        add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'render_configurator' ], 10 );

        // Hide default WooCommerce variation dropdowns on BannerCalc products.
        add_filter( 'woocommerce_product_data_tabs', [ $this, 'maybe_hide_variations_tab' ] );
    }

    /**
     * Render the BannerCalc configurator on the product page.
     */
    public function render_configurator(): void {
        global $product;

        if ( ! $product ) {
            return;
        }

        $plugin = \BannerCalc\Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return;
        }

        $config   = $plugin->get_product_config( $product->get_id() );
        $settings = \BannerCalc\Plugin::get_settings();
        $attr_mgr = $plugin->attributes;

        // Prepare data for templates.
        $sizing_mode      = $config['sizing_mode'] ?? 'preset_and_custom';
        $default_unit     = $config['default_unit'] ?? $settings['default_unit'];
        $available_units  = $config['available_units'] ?? $settings['available_units'];
        $preset_sizes     = $config['preset_sizes'] ?? [];
        $enabled_attrs    = $config['enabled_attributes'] ?? [];
        $attribute_pricing = $config['attribute_pricing'] ?? [];
        $currency         = $settings['currency_symbol'];

        // Output the config as a data attribute for JavaScript.
        $js_config = wp_json_encode( [
            'productId'       => $product->get_id(),
            'sizingMode'      => $sizing_mode,
            'defaultUnit'     => $default_unit,
            'availableUnits'  => $available_units,
            'areaRateSqft'    => (float) ( $config['area_rate_sqft'] ?? 0 ),
            'minimumCharge'   => (float) ( $config['minimum_charge'] ?? 0 ),
            'minWidthM'       => (float) ( $config['min_width_m'] ?? 0 ),
            'minHeightM'      => (float) ( $config['min_height_m'] ?? 0 ),
            'maxWidthM'       => (float) ( $config['max_width_m'] ?? 100 ),
            'maxHeightM'      => (float) ( $config['max_height_m'] ?? 100 ),
            'presetSizes'     => $preset_sizes,
            'enabledAttrs'    => $enabled_attrs,
            'attributePricing' => $attribute_pricing,
            'currency'        => $currency,
            'decimals'        => (int) $settings['price_display_decimals'],
        ] );

        echo '<div id="bannercalc-configurator" class="bannercalc-configurator" data-config="' . esc_attr( $js_config ) . '">';

        // Load sub-templates.
        include BANNERCALC_PLUGIN_DIR . 'frontend/views/configurator.php';

        echo '</div>';
    }

    /**
     * Optionally hide the Variations tab in admin for BannerCalc products.
     *
     * @param array $tabs
     * @return array
     */
    public function maybe_hide_variations_tab( array $tabs ): array {
        // This is handled at the admin level — stub for now.
        return $tabs;
    }
}
