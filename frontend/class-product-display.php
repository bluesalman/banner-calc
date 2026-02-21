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

        // Card 3: design accordion + pricing (fully self-contained).
        add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'render_extras_card' ], 11 );

        // Wrap quantity + add-to-cart button in a flex row.
        add_action( 'woocommerce_before_add_to_cart_quantity', [ $this, 'open_quantity_row' ], 1 );
        add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'close_quantity_row' ], 99 );

        // Make BannerCalc products purchasable even without a WC price.
        add_filter( 'woocommerce_product_get_price', [ $this, 'ensure_purchasable_price' ], 10, 2 );
        add_filter( 'woocommerce_product_get_regular_price', [ $this, 'ensure_purchasable_price' ], 10, 2 );

        // Hide default WC price display — our configurator shows the calculated price.
        add_filter( 'woocommerce_get_price_html', [ $this, 'replace_price_html' ], 10, 2 );

        // Suppress WC variation dropdowns on BannerCalc products (safety net).
        add_action( 'woocommerce_before_single_product', [ $this, 'suppress_wc_variation_form' ] );

        // Hide the default WC quantity input for BannerCalc products (optional — keep if needed).
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
     * Render Card 3: design accordion (Personalize + Upload) + pricing.
     * JS will relocate the Personalize link & file uploader into the accordion.
     */
    public function render_extras_card(): void {
        global $product;

        if ( ! $product ) {
            return;
        }

        $plugin = \BannerCalc\Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return;
        }

        $settings = \BannerCalc\Plugin::get_settings();
        $currency = $settings['currency_symbol'];

        echo '<div class="bannercalc-card bannercalc-card--extras">';

        // Design accordion (collapsed by default — JS moves Personalize + Upload here).
        echo '<details class="bannercalc-design-accordion" id="bannercalc-design-accordion">';
        echo '<summary class="bannercalc-accordion-trigger">';
        echo '<span class="bannercalc-accordion-icon"><span class="dashicons dashicons-cloud-upload"></span></span>';
        echo '<span class="bannercalc-accordion-label">' . esc_html__( 'Upload or Design with Online Designer', 'bannercalc' ) . '</span>';
        echo '<span class="bannercalc-accordion-arrow dashicons dashicons-arrow-down-alt2"></span>';
        echo '</summary>';
        echo '<div class="bannercalc-accordion-content" id="bannercalc-design-slot">';
        // JS will move .product_type_customizable and .wc-dnd-file-upload here.
        echo '</div>';
        echo '</details>';

        // Pricing section.
        echo '<div class="bannercalc-section bannercalc-price-section">';
        echo '<div class="bannercalc-section-overline">' . esc_html__( 'PRICING', 'bannercalc' ) . '</div>';
        include BANNERCALC_PLUGIN_DIR . 'frontend/views/price-display.php';
        echo '</div>';

        // Card stays open — quantity row will be rendered inside, then close_quantity_row() closes the card.
    }

    /**
     * Open the quantity + button flex wrapper for BannerCalc products.
     */
    public function open_quantity_row(): void {
        global $product;

        if ( ! $product ) {
            return;
        }

        $plugin = \BannerCalc\Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return;
        }

        echo '<div class="bannercalc-quantity-row">';
    }

    /**
     * Close the quantity + button flex wrapper for BannerCalc products.
     */
    public function close_quantity_row(): void {
        global $product;

        if ( ! $product ) {
            return;
        }

        $plugin = \BannerCalc\Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return;
        }

        echo '</div><!-- .bannercalc-quantity-row -->';
        echo '</div><!-- .bannercalc-card--extras -->';
    }

    /**
     * Suppress WooCommerce variation dropdowns for BannerCalc products.
     *
     * If a BannerCalc-enabled product is still set to Variable, remove the
     * default WC variation add-to-cart template so only our configurator shows.
     */
    public function suppress_wc_variation_form(): void {
        global $product;

        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            return;
        }

        $plugin = \BannerCalc\Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return;
        }

        // Remove the default variable product add-to-cart template.
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );

        // Re-add the simple product add-to-cart template instead (which has our hook).
        add_action( 'woocommerce_single_product_summary', function() {
            wc_get_template( 'single-product/add-to-cart/simple.php' );
        }, 30 );
    }

    /**
     * Return a meaningful price for BannerCalc products that have no WC price.
     *
     * Uses the category's minimum charge so WooCommerce considers the product
     * purchasable AND payment gateways (PayPal, Apple Pay, etc.) see a non-zero
     * amount and display their buttons on the product page.
     *
     * The real calculated price is set by CartHandler at add-to-cart time.
     *
     * @param string      $price
     * @param \WC_Product $product
     * @return string
     */
    public function ensure_purchasable_price( $price, $product ): string {
        if ( '' !== $price ) {
            return $price;
        }

        $plugin = \BannerCalc\Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return $price;
        }

        // Use minimum charge so payment gateways recognise a purchasable product.
        $config     = $plugin->get_product_config( $product->get_id() );
        $min_charge = (float) ( $config['minimum_charge'] ?? 0 );

        return $min_charge > 0 ? (string) $min_charge : '0.01';
    }

    /**
     * Replace the default WC price HTML with a "Calculated at checkout" hint
     * for BannerCalc-enabled products on the single product page.
     *
     * @param string      $price_html
     * @param \WC_Product $product
     * @return string
     */
    public function replace_price_html( $price_html, $product ): string {
        $plugin = \BannerCalc\Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return $price_html;
        }

        $config = $plugin->get_product_config( $product->get_id() );
        $settings = \BannerCalc\Plugin::get_settings();
        $currency = $settings['currency_symbol'] ?? '£';
        $min_charge = (float) ( $config['minimum_charge'] ?? 0 );

        if ( is_product() ) {
            // Single product page: CMYK stripe instead of price.
            return '<span class="bannercalc-cmyk-bar bannercalc-cmyk-bar--price"><span></span><span></span><span></span><span></span></span>';
        }

        // Archive / shop pages: show "From £X.XX".
        if ( $min_charge > 0 ) {
            return '<span class="bannercalc-archive-price" style="font-weight:600;">'
                 . esc_html__( 'From ', 'bannercalc' )
                 . esc_html( $currency . number_format( $min_charge, 2 ) )
                 . '</span>';
        }

        return '<span class="bannercalc-archive-price" style="font-style:italic;color:#8892A0;">'
             . esc_html__( 'Price on configuration', 'bannercalc' )
             . '</span>';
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
