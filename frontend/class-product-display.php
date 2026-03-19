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

        // CMYK bar above the product title.
        add_action( 'woocommerce_single_product_summary', [ $this, 'render_cmyk_bar' ], 4 );

        // Price + rating row below the title (replaces default WC price & rating).
        add_action( 'woocommerce_single_product_summary', [ $this, 'render_price_rating_row' ], 6 );
        add_action( 'woocommerce_single_product_summary', [ $this, 'remove_default_price_rating' ], 1 );

        // Make BannerCalc products purchasable even without a WC price.
        add_filter( 'woocommerce_product_get_price', [ $this, 'ensure_purchasable_price' ], 10, 2 );
        add_filter( 'woocommerce_product_get_regular_price', [ $this, 'ensure_purchasable_price' ], 10, 2 );

        // Hide default WC price display — our configurator shows the calculated price.
        add_filter( 'woocommerce_get_price_html', [ $this, 'replace_price_html' ], 10, 2 );

        // Override product structured data with correct price range.
        add_filter( 'woocommerce_structured_data_product', [ $this, 'override_structured_data_price' ], 10, 2 );

        // Suppress WC variation dropdowns on BannerCalc products (safety net).
        add_action( 'woocommerce_before_single_product', [ $this, 'suppress_wc_variation_form' ] );

        // Hide the default WC quantity input for BannerCalc products (optional — keep if needed).
        add_filter( 'woocommerce_product_data_tabs', [ $this, 'maybe_hide_variations_tab' ] );

        // Variation-style URL support: canonical, meta tags, and pre-selection.
        add_action( 'wp_head', [ $this, 'render_variation_meta_tags' ], 1 );

        // Append size to page title when ?attribute_size= is set (for GMC / SEO).
        add_filter( 'document_title_parts', [ $this, 'append_size_to_page_title' ] );
        add_filter( 'wpseo_title', [ $this, 'append_size_to_seo_title' ] );
        add_filter( 'rank_math/frontend/title', [ $this, 'append_size_to_seo_title' ] );

        // Add size variant URLs to WordPress sitemap for discoverability.
        add_filter( 'wp_sitemaps_posts_entry', [ $this, 'add_size_variants_to_sitemap' ], 10, 3 );
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
        $preset_columns   = max( 1, min( 6, (int) ( $config['preset_columns'] ?? 4 ) ) );
        $enabled_attrs    = $config['enabled_attributes'] ?? [];
        $attribute_pricing = $config['attribute_pricing'] ?? [];
        $currency         = $settings['currency_symbol'];

        // Single shipping cost from global settings.
        $shipping_cost = (float) ( $settings['shipping_cost'] ?? 0 );

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
            'collectionEnabled' => ! empty( $settings['collection_enabled'] ),
            'shippingCost'    => $shipping_cost,
            'productPrice'    => (float) $product->get_price(),
            'quantityMode'    => $config['quantity_mode'] ?? 'standard',
            'quantityBundles' => $config['quantity_bundles'] ?? [],
            'minQuantity'     => (int) ( $config['min_quantity'] ?? 0 ),
            'defaultQuantity' => max( 1, (int) ( $config['default_quantity'] ?? 1 ) ),
            'selectedSizeParam' => isset( $_GET['attribute_size'] ) ? sanitize_text_field( wp_unslash( $_GET['attribute_size'] ) ) : '',
        ] );

        echo '<div id="bannercalc-configurator" class="bannercalc-configurator" data-config="' . esc_attr( $js_config ) . '">';

        // Load sub-templates.
        include BANNERCALC_PLUGIN_DIR . 'frontend/views/configurator.php';

        echo '</div>';

        // Output hidden links for each size variant (crawler discoverability).
        if ( ! empty( $preset_sizes ) ) {
            $permalink = get_permalink( $product->get_id() );
            echo '<nav class="bannercalc-size-variants" aria-label="' . esc_attr__( 'Available sizes', 'bannercalc' ) . '" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);">';
            foreach ( $preset_sizes as $preset ) {
                $size_param = self::get_size_url_param( $preset );
                $size_url   = add_query_arg( 'attribute_size', $size_param, $permalink );
                $label      = $preset['label'] ?? $size_param;
                echo '<a href="' . esc_url( $size_url ) . '">' . esc_html( $product->get_name() . ' - ' . $label ) . '</a> ';
            }
            echo '</nav>';
        }
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
            echo '<div class="bannercalc-pro-field"><span>' . esc_html__( 'Reference Files (logos, images, etc.)', 'bannercalc' ) . '</span>';
            echo '<div id="bannercalc-pro-design-slot"></div></div>';
            echo '<label class="bannercalc-pro-field"><span>' . esc_html__( 'Number of Designs', 'bannercalc' ) . '</span>';
            echo '<input type="number" name="bannercalc[design_qty]" value="1" min="1" max="50" class="bannercalc-input bannercalc-pro-design-qty" id="bannercalc-design-qty" style="max-width:80px;" /></label>';
            echo '</div>';
            echo '<input type="hidden" name="bannercalc[design_service]" value="0" id="bannercalc-input-design-service" />';
            echo '</div>';
        }

        echo '</div>'; // .bannercalc-design-mode-section

        echo '</div>'; // .bannercalc-card--extras (design card ends here)

        // Card 4: Delivery Speed + Pricing — separate card.
        echo '<div class="bannercalc-card bannercalc-card--delivery">';

        // Combined fulfilment + delivery speed section.
        $has_fulfilment = ! empty( $settings['collection_enabled'] );
        $has_service    = ! empty( $service_types ) && count( $service_types ) > 1;

        if ( $has_fulfilment || $has_service ) {
            echo '<div class="bannercalc-section bannercalc-delivery-combined">';

            if ( $has_fulfilment ) {
                echo '<div class="bannercalc-attribute bannercalc-attr--fulfilment">';
                echo '<div class="bannercalc-attr-header"><span class="bannercalc-attr-label">' . esc_html__( 'Fulfilment Method', 'bannercalc' ) . '</span></div>';
                echo '<div class="bannercalc-fulfilment-toggle">';
                echo '<button type="button" class="bannercalc-pill bannercalc-fulfilment-pill active" data-fulfilment="delivery">';
                echo '<span class="dashicons dashicons-car"></span><span>' . esc_html__( 'Shipping', 'bannercalc' ) . '</span>';
                echo '</button>';
                echo '<button type="button" class="bannercalc-pill bannercalc-fulfilment-pill" data-fulfilment="collection">';
                echo '<span class="dashicons dashicons-store"></span><span>' . esc_html__( 'Collection', 'bannercalc' ) . '</span> <span class="bannercalc-fulfilment-badge">' . esc_html__( 'Free', 'bannercalc' ) . '</span>';
                echo '</button>';
                echo '</div>';
                echo '<input type="hidden" name="bannercalc[fulfilment_mode]" value="delivery" id="bannercalc-input-fulfilment-mode" />';
                echo '</div>';
            }

            if ( $has_service ) {
                echo '<div class="bannercalc-attribute bannercalc-attr--service-type">';
                echo '<div class="bannercalc-attr-header"><span class="bannercalc-attr-label">' . esc_html__( 'Delivery Speed', 'bannercalc' ) . '</span></div>';
                echo '<div class="bannercalc-attr-pills">';
                foreach ( $service_types as $st ) {
                    $is_default = ! empty( $st['default'] );
                    $markup     = (float) ( $st['markup'] ?? 0 );
                    $slug       = $st['slug'] ?? '';
                    $label      = esc_html( $st['label'] ?? $slug );

                    if ( $markup > 0 ) {
                        $label .= ' <span class="bannercalc-pill-price">(+' . (int) $markup . '%)</span>';
                    }
                    echo '<button type="button" class="bannercalc-pill bannercalc-service-pill' . ( $is_default ? ' active' : '' ) . '" data-service="' . esc_attr( $slug ) . '">' . $label . '</button>';
                }
                echo '</div>';
                echo '<input type="hidden" name="bannercalc[service_type]" value="standard" id="bannercalc-input-service-type" />';
                echo '</div>';
            }

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
        <!-- BannerCalc Preview Tabs (shown by default) -->
        <div class="bannercalc-preview-tabs" id="bannercalc-preview-tabs">
            <button type="button" class="bannercalc-preview-tab active" data-tab="gallery"><?php esc_html_e( 'Product Image', 'bannercalc' ); ?></button>
            <button type="button" class="bannercalc-preview-tab" data-tab="preview"><?php esc_html_e( 'Your Banner', 'bannercalc' ); ?></button>
        </div>
        <div class="bannercalc-preview-panel" id="bannercalc-preview-panel" style="display:none;">
            <div class="bannercalc-preview-header">
                <span class="bannercalc-preview-title"><?php esc_html_e( 'Live Preview', 'bannercalc' ); ?></span>
                <div class="bannercalc-preview-header-actions">
                    <span class="bannercalc-preview-size-label" id="bannercalc-preview-size">&mdash;</span>
                    <button type="button" class="bannercalc-fullscreen-btn" id="bannercalc-fullscreen-btn" title="<?php esc_attr_e( 'Fullscreen preview', 'bannercalc' ); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                    </button>
                </div>
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
        <!-- Fullscreen Preview Overlay -->
        <div class="bannercalc-fullscreen-overlay" id="bannercalc-fullscreen-overlay" style="display:none;">
            <div class="bannercalc-fullscreen-topbar">
                <span class="bannercalc-fullscreen-title"><?php esc_html_e( 'Banner Preview', 'bannercalc' ); ?></span>
                <span class="bannercalc-fullscreen-size" id="bannercalc-fullscreen-size"></span>
                <button type="button" class="bannercalc-fullscreen-close" id="bannercalc-fullscreen-close" title="<?php esc_attr_e( 'Close', 'bannercalc' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="bannercalc-fullscreen-canvas" id="bannercalc-fullscreen-canvas"></div>
        </div>
        <?php
    }

    /**
     * Render the CMYK colour bar above the product title.
     */
    public function render_cmyk_bar(): void {
        global $product;

        if ( ! $product ) {
            return;
        }

        $plugin = \BannerCalc\Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return;
        }

        echo '<span class="bannercalc-cmyk-bar bannercalc-cmyk-bar--price"><span></span><span></span><span></span><span></span></span>';
    }

    /**
     * Remove default WC price and rating from single product summary
     * (we render our own combined row instead).
     */
    public function remove_default_price_rating(): void {
        global $product;

        if ( ! $product ) {
            return;
        }

        $plugin = \BannerCalc\Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return;
        }

        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
    }

    /**
     * Render price range + star rating in a single flex row below the title.
     */
    public function render_price_rating_row(): void {
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
        $currency = $settings['currency_symbol'] ?? '£';

        $sizing_mode = $config['sizing_mode'] ?? 'preset_and_custom';
        $prices      = $this->get_preset_prices( $config );
        $min_charge  = (float) ( $config['minimum_charge'] ?? 0 );

        // If a specific size is requested via URL, show that size's price.
        $requested_size = isset( $_GET['attribute_size'] ) ? sanitize_text_field( wp_unslash( $_GET['attribute_size'] ) ) : '';
        $presets        = $config['preset_sizes'] ?? [];

        // Build price HTML.
        $price_html = '';
        if ( $requested_size !== '' && ! empty( $presets ) ) {
            $matched = $this->find_preset_by_size_param( $requested_size, $presets );
            if ( $matched ) {
                $rate       = (float) ( $config['area_rate_sqft'] ?? 0 );
                $units      = new \BannerCalc\UnitConverter();
                $size_price = $this->calculate_preset_price( $matched, $rate, $min_charge, $units );
                $price_html = esc_html( $currency . number_format( $size_price, 2 ) );
                $price_html .= ' <span class="bannercalc-price-hint">' . esc_html( $matched['label'] ?? '' ) . '</span>';
            }
        }
        if ( empty( $price_html ) && ! empty( $prices ) ) {
            // Has preset / popular sizes.
            $min_price = min( $prices );
            $max_price = max( $prices );
            $price_html = esc_html( $currency . number_format( $min_price, 2 ) );
            if ( $max_price > $min_price ) {
                $price_html .= ' – ' . esc_html( $currency . number_format( $max_price, 2 ) );
            }
            $price_html .= ' <span class="bannercalc-price-hint">' . esc_html__( '(popular sizes)', 'bannercalc' ) . '</span>';
        } elseif ( empty( $price_html ) && 'none' === $sizing_mode ) {
            // Fixed / single size — use the WooCommerce product price.
            $wc_price = (float) $product->get_price();
            if ( $wc_price > 0 ) {
                $price_html = esc_html( $currency . number_format( $wc_price, 2 ) );
            }
        } elseif ( 'custom_only' === $sizing_mode && $min_charge > 0 ) {
            // Custom size only — "Starting from" + minimum rate, same row.
            $price_html = esc_html__( 'Starting from ', 'bannercalc' ) . esc_html( $currency . number_format( $min_charge, 2 ) );
        } elseif ( $min_charge > 0 ) {
            // Other modes without presets — show minimum charge.
            $price_html = esc_html__( 'Starting from ', 'bannercalc' ) . esc_html( $currency . number_format( $min_charge, 2 ) );
        }

        // Build rating HTML.
        $rating_html = '';
        $rating_count = $product->get_rating_count();
        if ( $rating_count > 0 ) {
            $average = $product->get_average_rating();
            $rating_html  = wc_get_rating_html( $average, $rating_count );
            $review_link  = '#reviews';
            $rating_html .= '<a href="' . esc_url( $review_link ) . '" class="bannercalc-review-link">(';
            $rating_html .= sprintf( _n( '%s review', '%s reviews', $rating_count, 'bannercalc' ), esc_html( $rating_count ) );
            $rating_html .= ')</a>';
        }

        echo '<div class="bannercalc-price-rating-row">';
        if ( $price_html ) {
            echo '<span class="bannercalc-product-price">' . $price_html . '</span>';
        }
        if ( $rating_html ) {
            echo '<div class="bannercalc-product-rating">' . $rating_html . '</div>';
        }
        echo '</div>';
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
        echo '</div><!-- .bannercalc-card--delivery -->';
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
     * On archive pages, show a price range based on preset sizes.
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

        $config   = $plugin->get_product_config( $product->get_id() );
        $settings = \BannerCalc\Plugin::get_settings();
        $currency = $settings['currency_symbol'] ?? '£';

        // Single product page: hide default price — shown in our custom price+rating row.
        if ( is_product() ) {
            return '';
        }

        // Archive / shop pages: compute price range from preset sizes.
        $sizing_mode = $config['sizing_mode'] ?? 'preset_and_custom';
        $min_charge  = (float) ( $config['minimum_charge'] ?? 0 );
        $prices      = $this->get_preset_prices( $config );

        if ( ! empty( $prices ) ) {
            $min_price = min( $prices );
            $max_price = max( $prices );

            $range_html = '<span class="bannercalc-archive-price" style="font-weight:600;">';
            $range_html .= esc_html( $currency . number_format( $min_price, 2 ) );
            if ( $max_price > $min_price ) {
                $range_html .= ' – ' . esc_html( $currency . number_format( $max_price, 2 ) );
            }
            $range_html .= '</span>';
            $range_html .= '<span class="bannercalc-archive-popular" style="display:block;font-size:0.8em;color:#8892A0;font-weight:400;">';
            $range_html .= esc_html__( '(popular sizes)', 'bannercalc' );
            $range_html .= '</span>';

            return $range_html;
        }

        // Fixed / single size — use the WooCommerce product price.
        if ( 'none' === $sizing_mode ) {
            $wc_price = (float) $product->get_price();
            if ( $wc_price > 0 ) {
                return '<span class="bannercalc-archive-price" style="font-weight:600;">'
                     . esc_html( $currency . number_format( $wc_price, 2 ) )
                     . '</span>';
            }
        }

        // Custom size only — "Starting from" + minimum rate.
        if ( 'custom_only' === $sizing_mode && $min_charge > 0 ) {
            return '<span class="bannercalc-archive-price" style="font-weight:600;">'
                 . esc_html__( 'Starting from ', 'bannercalc' )
                 . esc_html( $currency . number_format( $min_charge, 2 ) )
                 . '</span>';
        }

        // Other modes without presets — show minimum charge.
        if ( $min_charge > 0 ) {
            return '<span class="bannercalc-archive-price" style="font-weight:600;">'
                 . esc_html__( 'Starting from ', 'bannercalc' )
                 . esc_html( $currency . number_format( $min_charge, 2 ) )
                 . '</span>';
        }

        return '<span class="bannercalc-archive-price" style="font-style:italic;color:#8892A0;">'
             . esc_html__( 'Price on configuration', 'bannercalc' )
             . '</span>';
    }

    /**
     * Compute all preset prices for a product config.
     *
     * @param array $config Product config array.
     * @return float[] Array of prices.
     */
    private function get_preset_prices( array $config ): array {
        $presets    = $config['preset_sizes'] ?? [];
        $rate       = (float) ( $config['area_rate_sqft'] ?? 0 );
        $min_charge = (float) ( $config['minimum_charge'] ?? 0 );
        $prices     = [];

        if ( ! empty( $presets ) ) {
            $units = new \BannerCalc\UnitConverter();
            foreach ( $presets as $preset ) {
                if ( isset( $preset['price'] ) && $preset['price'] !== null && $preset['price'] !== '' ) {
                    $p = (float) $preset['price'];
                } else {
                    $w    = (float) ( $preset['width_m'] ?? 0 );
                    $h    = (float) ( $preset['height_m'] ?? 0 );
                    $sqft = $units->area_sqft( $w, $h );
                    $p    = $sqft * $rate;
                    if ( $p < $min_charge ) {
                        $p = $min_charge;
                    }
                }
                $prices[] = $p;
            }
        }

        return $prices;
    }

    /**
     * Override WooCommerce product structured data with per-size Offers.
     *
     * When ?attribute_size= is present, outputs a single Offer for that size.
     * Otherwise, outputs individual Offer objects for each preset size so that
     * Google Merchant Center can detect each size as a separate product variant.
     *
     * @param array       $markup  Schema.org markup array.
     * @param \WC_Product $product
     * @return array
     */
    public function override_structured_data_price( array $markup, \WC_Product $product ): array {
        $plugin = \BannerCalc\Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return $markup;
        }

        $config  = $plugin->get_product_config( $product->get_id() );
        $presets = $config['preset_sizes'] ?? [];

        if ( empty( $presets ) ) {
            return $markup;
        }

        $currency      = get_woocommerce_currency();
        $permalink     = get_permalink( $product->get_id() );
        $product_name  = $product->get_name();
        $rate          = (float) ( $config['area_rate_sqft'] ?? 0 );
        $min_charge    = (float) ( $config['minimum_charge'] ?? 0 );
        $units         = new \BannerCalc\UnitConverter();

        // Check if a specific size is requested via URL.
        $requested_size = isset( $_GET['attribute_size'] ) ? sanitize_text_field( wp_unslash( $_GET['attribute_size'] ) ) : '';
        $matched_preset = null;

        if ( $requested_size !== '' ) {
            $matched_preset = $this->find_preset_by_size_param( $requested_size, $presets );
        }

        // If a specific size is requested and matched, output a single Offer.
        if ( $matched_preset ) {
            $price      = $this->calculate_preset_price( $matched_preset, $rate, $min_charge, $units );
            $size_param = self::get_size_url_param( $matched_preset );
            $size_url   = add_query_arg( 'attribute_size', $size_param, $permalink );
            $size_label = $matched_preset['label'] ?? $size_param;

            $markup['name']  = $product_name . ' - ' . $size_label;
            $markup['offers'] = [
                '@type'         => 'Offer',
                'priceCurrency' => $currency,
                'price'         => number_format( round( $price, 2 ), 2, '.', '' ),
                'availability'  => 'https://schema.org/InStock',
                'url'           => $size_url,
            ];

            // Add size as product attribute in structured data.
            $markup['size'] = $size_label;

            return $markup;
        }

        // No specific size requested — output individual Offers for each preset.
        $offers = [];
        foreach ( $presets as $preset ) {
            $price      = $this->calculate_preset_price( $preset, $rate, $min_charge, $units );
            $size_param = self::get_size_url_param( $preset );
            $size_url   = add_query_arg( 'attribute_size', $size_param, $permalink );
            $size_label = $preset['label'] ?? $size_param;

            $offers[] = [
                '@type'         => 'Offer',
                'priceCurrency' => $currency,
                'price'         => number_format( round( $price, 2 ), 2, '.', '' ),
                'availability'  => 'https://schema.org/InStock',
                'url'           => $size_url,
                'name'          => $product_name . ' - ' . $size_label,
            ];
        }

        $prices    = array_column( $offers, 'price' );
        $min_price = min( $prices );
        $max_price = max( $prices );

        $markup['offers'] = [
            '@type'         => 'AggregateOffer',
            'priceCurrency' => $currency,
            'lowPrice'      => $min_price,
            'highPrice'     => $max_price,
            'offerCount'    => count( $offers ),
            'availability'  => 'https://schema.org/InStock',
            'url'           => $permalink,
            'offers'        => $offers,
        ];

        return $markup;
    }

    /**
     * Render variation-style meta tags in <head> for SEO and GMC compatibility.
     *
     * When ?attribute_size= is set:
     * - Sets canonical to the variation URL (allows Google to index each size).
     * - Outputs og:price / product:price meta for the specific size.
     *
     * When no specific size is set:
     * - Outputs <link rel="canonical"> to the base product URL.
     */
    public function render_variation_meta_tags(): void {
        if ( ! is_product() ) {
            return;
        }

        global $product;
        if ( ! $product ) {
            return;
        }

        $plugin = \BannerCalc\Plugin::instance();
        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return;
        }

        $config         = $plugin->get_product_config( $product->get_id() );
        $presets        = $config['preset_sizes'] ?? [];
        $permalink      = get_permalink( $product->get_id() );
        $requested_size = isset( $_GET['attribute_size'] ) ? sanitize_text_field( wp_unslash( $_GET['attribute_size'] ) ) : '';
        $currency       = get_woocommerce_currency();

        if ( $requested_size !== '' && ! empty( $presets ) ) {
            $matched = $this->find_preset_by_size_param( $requested_size, $presets );

            if ( $matched ) {
                $rate      = (float) ( $config['area_rate_sqft'] ?? 0 );
                $min_charge = (float) ( $config['minimum_charge'] ?? 0 );
                $units     = new \BannerCalc\UnitConverter();
                $price     = $this->calculate_preset_price( $matched, $rate, $min_charge, $units );
                $size_param = self::get_size_url_param( $matched );
                $var_url   = add_query_arg( 'attribute_size', $size_param, $permalink );

                // Canonical URL for this specific size variation.
                echo '<link rel="canonical" href="' . esc_url( $var_url ) . '" />' . "\n";

                // Open Graph price tags (used by Facebook, some aggregators, GMC).
                echo '<meta property="product:price:amount" content="' . esc_attr( number_format( round( $price, 2 ), 2, '.', '' ) ) . '" />' . "\n";
                echo '<meta property="product:price:currency" content="' . esc_attr( $currency ) . '" />' . "\n";
                echo '<meta property="og:price:amount" content="' . esc_attr( number_format( round( $price, 2 ), 2, '.', '' ) ) . '" />' . "\n";
                echo '<meta property="og:price:currency" content="' . esc_attr( $currency ) . '" />' . "\n";

                // Remove default canonical from WP/Yoast/RankMath to avoid duplicates.
                remove_action( 'wp_head', 'rel_canonical' );
                add_filter( 'wpseo_canonical', function () use ( $var_url ) { return $var_url; } );
                add_filter( 'rank_math/frontend/canonical', function () use ( $var_url ) { return $var_url; } );

                return;
            }
        }

        // No specific size — let WordPress/SEO plugin handle the canonical normally.
    }

    /**
     * Generate the URL parameter value for a preset size.
     *
     * Format: {width}{unit} x {height}{unit}
     * e.g. "1000mm x 2000mm", "3ft x 2ft"
     *
     * @param array $preset Preset size data.
     * @return string URL-safe size parameter (not yet URL-encoded).
     */
    public static function get_size_url_param( array $preset ): string {
        $w    = $preset['display_w'] ?? null;
        $h    = $preset['display_h'] ?? null;
        $unit = $preset['display_unit'] ?? 'mm';

        if ( $w !== null && $h !== null ) {
            $w_clean = self::format_dimension( (float) $w );
            $h_clean = self::format_dimension( (float) $h );
            return $w_clean . $unit . ' x ' . $h_clean . $unit;
        }

        // Fallback: derive from width_m / height_m → mm.
        $width_mm  = round( (float) ( $preset['width_m'] ?? 0 ) * 1000 );
        $height_mm = round( (float) ( $preset['height_m'] ?? 0 ) * 1000 );
        return $width_mm . 'mm x ' . $height_mm . 'mm';
    }

    /**
     * Format a dimension number — strip trailing zeros for clean URLs.
     *
     * @param float $value
     * @return string
     */
    private static function format_dimension( float $value ): string {
        if ( floor( $value ) == $value ) {
            return (string) (int) $value;
        }
        return rtrim( rtrim( number_format( $value, 4, '.', '' ), '0' ), '.' );
    }

    /**
     * Find a preset matching a ?attribute_size= URL parameter.
     *
     * Matches by comparing dimension strings or by parsing the parameter
     * and comparing metres values with tolerance.
     *
     * @param string $size_param The URL parameter value (decoded).
     * @param array  $presets    Array of preset sizes.
     * @return array|null Matched preset or null.
     */
    private function find_preset_by_size_param( string $size_param, array $presets ): ?array {
        // Normalise whitespace and separators.
        $normalised = strtolower( trim( $size_param ) );
        $normalised = preg_replace( '/\s+/', ' ', $normalised );

        foreach ( $presets as $preset ) {
            // Direct match against generated param.
            $preset_param = strtolower( self::get_size_url_param( $preset ) );
            if ( $normalised === $preset_param ) {
                return $preset;
            }
        }

        // Try parsing the parameter: "1000mm x 2000mm" → width, height, unit.
        $parsed = self::parse_size_string( $normalised );
        if ( $parsed ) {
            $target_w = $parsed['width_m'];
            $target_h = $parsed['height_m'];
            $tolerance = 0.005; // 5mm tolerance.

            foreach ( $presets as $preset ) {
                $pw = (float) ( $preset['width_m'] ?? 0 );
                $ph = (float) ( $preset['height_m'] ?? 0 );
                if ( abs( $pw - $target_w ) < $tolerance && abs( $ph - $target_h ) < $tolerance ) {
                    return $preset;
                }
            }
        }

        return null;
    }

    /**
     * Parse a size string like "1000mm x 2000mm" or "3ft x 2ft" into metres.
     *
     * @param string $str Normalised (lowercase, trimmed) size string.
     * @return array|null ['width_m' => float, 'height_m' => float] or null.
     */
    private static function parse_size_string( string $str ): ?array {
        $unit_map = [
            'mm'   => 0.001,
            'cm'   => 0.01,
            'inch' => 0.0254,
            'in'   => 0.0254,
            '"'    => 0.0254,
            'ft'   => 0.3048,
            '\''   => 0.3048,
            'm'    => 1.0,
        ];

        // Pattern: number + optional unit + separator + number + optional unit
        // e.g. "1000mm x 2000mm", "3ft x 2ft", "1000 x 2000"
        $pattern = '/^([\d.]+)\s*(mm|cm|inch|in|ft|m)?\s*[x×]\s*([\d.]+)\s*(mm|cm|inch|in|ft|m)?$/';

        if ( ! preg_match( $pattern, $str, $m ) ) {
            return null;
        }

        $w_val  = (float) $m[1];
        $w_unit = ! empty( $m[2] ) ? $m[2] : 'mm';
        $h_val  = (float) $m[3];
        $h_unit = ! empty( $m[4] ) ? $m[4] : $w_unit;

        $w_factor = $unit_map[ $w_unit ] ?? 0.001;
        $h_factor = $unit_map[ $h_unit ] ?? 0.001;

        return [
            'width_m'  => $w_val * $w_factor,
            'height_m' => $h_val * $h_factor,
        ];
    }

    /**
     * Calculate the price for a single preset size.
     *
     * @param array              $preset
     * @param float              $rate       Area rate per sqft.
     * @param float              $min_charge Minimum charge.
     * @param \BannerCalc\UnitConverter $units
     * @return float
     */
    private function calculate_preset_price( array $preset, float $rate, float $min_charge, \BannerCalc\UnitConverter $units ): float {
        if ( isset( $preset['price'] ) && $preset['price'] !== null && $preset['price'] !== '' ) {
            return (float) $preset['price'];
        }

        $w    = (float) ( $preset['width_m'] ?? 0 );
        $h    = (float) ( $preset['height_m'] ?? 0 );
        $sqft = $units->area_sqft( $w, $h );
        $p    = $sqft * $rate;

        if ( $p < $min_charge ) {
            $p = $min_charge;
        }

        return $p;
    }

    /**
     * Append the selected size to the page <title> for GMC / SEO differentiation.
     *
     * @param array $title_parts Document title parts.
     * @return array
     */
    public function append_size_to_page_title( array $title_parts ): array {
        $size_label = $this->get_requested_size_label();
        if ( $size_label && ! empty( $title_parts['title'] ) ) {
            $title_parts['title'] .= ' - ' . $size_label;
        }
        return $title_parts;
    }

    /**
     * Append the selected size to Yoast/RankMath title string.
     *
     * @param string $title
     * @return string
     */
    public function append_size_to_seo_title( string $title ): string {
        $size_label = $this->get_requested_size_label();
        if ( $size_label ) {
            // Insert before the site name separator if present.
            $separators = [ ' - ', ' | ', ' – ', ' — ' ];
            foreach ( $separators as $sep ) {
                $last_pos = strrpos( $title, $sep );
                if ( $last_pos !== false && $last_pos > 0 ) {
                    return substr( $title, 0, $last_pos ) . ' - ' . $size_label . substr( $title, $last_pos );
                }
            }
            return $title . ' - ' . $size_label;
        }
        return $title;
    }

    /**
     * Get the matched size label from URL parameter, if on a BannerCalc product page.
     *
     * @return string|null Size label or null.
     */
    private function get_requested_size_label(): ?string {
        if ( ! is_product() ) {
            return null;
        }

        $requested_size = isset( $_GET['attribute_size'] ) ? sanitize_text_field( wp_unslash( $_GET['attribute_size'] ) ) : '';
        if ( $requested_size === '' ) {
            return null;
        }

        global $product;
        if ( ! $product ) {
            return null;
        }

        $plugin = \BannerCalc\Plugin::instance();
        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return null;
        }

        $config  = $plugin->get_product_config( $product->get_id() );
        $presets = $config['preset_sizes'] ?? [];
        $matched = $this->find_preset_by_size_param( $requested_size, $presets );

        return $matched ? ( $matched['label'] ?? null ) : null;
    }

    /**
     * Add size variant URLs to WordPress sitemap entries.
     *
     * Adds variant URLs as comments for sitemap plugins to discover.
     *
     * @param array    $sitemap_entry Sitemap entry array.
     * @param \WP_Post $post          Post object.
     * @param string   $post_type     Post type.
     * @return array
     */
    public function add_size_variants_to_sitemap( array $sitemap_entry, \WP_Post $post, string $post_type ): array {
        if ( 'product' !== $post_type ) {
            return $sitemap_entry;
        }

        $plugin = \BannerCalc\Plugin::instance();
        if ( ! $plugin->is_enabled_for_product( $post->ID ) ) {
            return $sitemap_entry;
        }

        return $sitemap_entry;
    }
}
