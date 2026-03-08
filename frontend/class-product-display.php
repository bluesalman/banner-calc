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

        // Banner preview tab switcher + SVG panel (injected above gallery).
        add_action( 'woocommerce_before_single_product', [ $this, 'render_preview_container' ], 30 );

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
            'serviceTypes'    => $settings['service_types'] ?? [],
            'designService'   => $settings['design_service'] ?? [],
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
        $design_service_config = $settings['design_service'] ?? [];
        $service_types         = $settings['service_types'] ?? [];

        echo '<div class="bannercalc-card bannercalc-card--extras">';

        // 3-way design mode selector (replaces the old accordion).
        echo '<div class="bannercalc-section bannercalc-design-mode-section">';
        echo '<div class="bannercalc-section-overline">' . esc_html__( 'DESIGN', 'bannercalc' ) . '</div>';
        echo '<div class="bannercalc-attribute bannercalc-attr--design-mode">';
        echo '<div class="bannercalc-attr-header"><span class="bannercalc-attr-label">' . esc_html__( 'How would you like your design?', 'bannercalc' ) . '</span></div>';
        echo '<div class="bannercalc-attr-pills bannercalc-design-mode-pills">';
        echo '<button type="button" class="bannercalc-pill bannercalc-design-pill active" data-design-mode="upload">' . esc_html__( 'Upload Files', 'bannercalc' ) . '</button>';
        echo '<button type="button" class="bannercalc-pill bannercalc-design-pill" data-design-mode="online">' . esc_html__( 'Design Online', 'bannercalc' ) . '</button>';

        if ( ! empty( $design_service_config['enabled'] ) ) {
            $ds_price = (float) ( $design_service_config['price'] ?? 0 );
            echo '<button type="button" class="bannercalc-pill bannercalc-design-pill" data-design-mode="pro">';
            echo esc_html__( 'Pro Design', 'bannercalc' );
            if ( $ds_price > 0 ) {
                echo ' <span class="bannercalc-pill-price">+' . esc_html( $currency . number_format( $ds_price, 2 ) ) . '</span>';
            }
            echo '</button>';
        }

        echo '</div>';
        echo '<input type="hidden" name="bannercalc[design_mode]" value="upload" id="bannercalc-input-design-mode" />';
        echo '</div>';

        // Conditional content panels.
        // Panel 1: Upload files.
        echo '<div class="bannercalc-design-panel bannercalc-design-panel--upload" id="bannercalc-design-upload" style="margin-top:12px;">';
        echo '<div id="bannercalc-design-slot">';
        // JS will move .wc-dnd-file-upload here.
        echo '</div>';
        echo '</div>';

        // Panel 2: Design online.
        echo '<div class="bannercalc-design-panel bannercalc-design-panel--online" id="bannercalc-design-online" style="display:none;margin-top:12px;">';
        echo '<div id="bannercalc-personalize-slot">';
        // JS will move .product_type_customizable here.
        echo '</div>';
        echo '</div>';

        // Panel 3: Professional design service.
        if ( ! empty( $design_service_config['enabled'] ) ) {
            echo '<div class="bannercalc-design-panel bannercalc-design-panel--pro" id="bannercalc-design-pro" style="display:none;margin-top:12px;">';
            echo '<div class="bannercalc-pro-design-form">';
            echo '<p class="bannercalc-design-service-desc">' . esc_html( $design_service_config['description'] ?? '' ) . ' — <strong>' . esc_html( $currency . number_format( (float) ( $design_service_config['price'] ?? 0 ), 2 ) ) . '</strong></p>';
            echo '<label class="bannercalc-pro-field"><span>' . esc_html__( 'Design Brief', 'bannercalc' ) . '</span>';
            echo '<textarea name="bannercalc[design_brief]" rows="3" placeholder="' . esc_attr__( 'Describe what you would like on your banner…', 'bannercalc' ) . '" class="bannercalc-textarea"></textarea></label>';
            echo '<label class="bannercalc-pro-field"><span>' . esc_html__( 'Brand Colours / Text', 'bannercalc' ) . '</span>';
            echo '<input type="text" name="bannercalc[design_colours]" placeholder="' . esc_attr__( 'e.g. Blue, White, Company Name…', 'bannercalc' ) . '" class="bannercalc-input" /></label>';
            echo '</div>';
            echo '<input type="hidden" name="bannercalc[design_service]" value="0" id="bannercalc-input-design-service" />';
            echo '</div>';
        }

        echo '</div>'; // .bannercalc-design-mode-section

        // Service type selector.
        if ( ! empty( $service_types ) && count( $service_types ) > 1 ) {
            echo '<div class="bannercalc-section bannercalc-service-section">';
            echo '<div class="bannercalc-attribute bannercalc-attr--service-type">';
            echo '<div class="bannercalc-attr-header"><span class="bannercalc-attr-label">' . esc_html__( 'Delivery Speed', 'bannercalc' ) . '</span></div>';
            echo '<div class="bannercalc-attr-pills">';
            foreach ( $service_types as $st ) {
                $is_default = ! empty( $st['default'] );
                $markup     = (float) ( $st['markup'] ?? 0 );
                $label      = esc_html( $st['label'] ?? $st['slug'] );
                if ( $markup > 0 ) {
                    $label .= ' <span class="bannercalc-pill-price">(+' . (int) $markup . '%)</span>';
                }
                echo '<button type="button" class="bannercalc-pill bannercalc-service-pill' . ( $is_default ? ' active' : '' ) . '" data-service="' . esc_attr( $st['slug'] ) . '">' . $label . '</button>';
            }
            echo '</div>';
            echo '<input type="hidden" name="bannercalc[service_type]" value="standard" id="bannercalc-input-service-type" />';
            echo '</div>';
            echo '</div>';
        }

        // Pricing section.
        echo '<div class="bannercalc-section bannercalc-price-section">';
        echo '<div class="bannercalc-section-overline">' . esc_html__( 'PRICING', 'bannercalc' ) . '</div>';
        include BANNERCALC_PLUGIN_DIR . 'frontend/views/price-display.php';
        echo '</div>';

        // Card stays open — quantity row will be rendered inside, then close_quantity_row() closes the card.
    }

    /**
     * Render the banner preview tab switcher and SVG container.
     * Injected before the product — JS will relocate into the gallery column.
     */
    public function render_preview_container(): void {
        global $product;

        if ( ! $product ) {
            return;
        }

        $plugin = \BannerCalc\Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return;
        }
        ?>
        <!-- BannerCalc Preview Tabs (hidden until visual attribute is selected) -->
        <div class="bannercalc-preview-tabs" id="bannercalc-preview-tabs" style="display:none;">
            <button type="button" class="bannercalc-preview-tab active" data-tab="gallery"><?php esc_html_e( 'Product Image', 'bannercalc' ); ?></button>
            <button type="button" class="bannercalc-preview-tab" data-tab="preview"><?php esc_html_e( 'Your Banner', 'bannercalc' ); ?></button>
        </div>
        <div class="bannercalc-preview-panel" id="bannercalc-preview-panel" style="display:none;">
            <div class="bannercalc-preview-header">
                <span class="bannercalc-preview-title"><?php esc_html_e( 'Live Preview', 'bannercalc' ); ?></span>
                <span class="bannercalc-preview-size-label" id="bannercalc-preview-size">—</span>
            </div>
            <div class="bannercalc-preview-canvas" id="bannercalc-preview-canvas">
                <!-- SVG rendered by JavaScript -->
            </div>
            <div class="bannercalc-preview-legend" id="bannercalc-preview-legend">
                <div class="bannercalc-legend-item" data-legend="eyelets">
                    <span class="bannercalc-legend-dot bannercalc-legend-dot--eyelets"></span> <?php esc_html_e( 'Eyelets', 'bannercalc' ); ?>
                </div>
                <div class="bannercalc-legend-item" data-legend="pole-pockets">
                    <span class="bannercalc-legend-dot bannercalc-legend-dot--pole-pockets"></span> <?php esc_html_e( 'Pole Pockets', 'bannercalc' ); ?>
                </div>
                <div class="bannercalc-legend-item" data-legend="hemming">
                    <span class="bannercalc-legend-dot bannercalc-legend-dot--hemming"></span> <?php esc_html_e( 'Hemming', 'bannercalc' ); ?>
                </div>
                <div class="bannercalc-legend-item" data-legend="cable-ties">
                    <span class="bannercalc-legend-dot bannercalc-legend-dot--cable-ties"></span> <?php esc_html_e( 'Cable Ties', 'bannercalc' ); ?>
                </div>
            </div>
        </div>
        <?php
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
