<?php
/**
 * Help & Documentation page HTML template.
 *
 * Comprehensive plugin usage guide with sidebar navigation.
 *
 * @package BannerCalc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap bannercalc-admin-wrap bannercalc-help-page">

    <!-- CMYK Brand Bar -->
    <div class="bannercalc-cmyk-bar"><span></span><span></span><span></span><span></span></div>

    <!-- Page Header -->
    <div class="bannercalc-page-header">
        <div class="bannercalc-admin-overline"><?php esc_html_e( 'DOCUMENTATION', 'bannercalc' ); ?></div>
        <h1 class="bannercalc-page-title"><?php esc_html_e( 'Help & Documentation', 'bannercalc' ); ?></h1>
        <p class="bannercalc-page-desc"><?php esc_html_e( 'Complete guide to configuring and using the BannerCalc pricing engine.', 'bannercalc' ); ?></p>
    </div>

    <!-- Help Layout: Sidebar + Content -->
    <div class="bannercalc-help-layout">

        <!-- Sidebar Navigation -->
        <nav class="bannercalc-help-sidebar" id="bannercalc-help-nav">
            <div class="bannercalc-help-sidebar-inner">
                <h3 class="bannercalc-help-sidebar-title">
                    <span class="dashicons dashicons-book"></span>
                    <?php esc_html_e( 'Contents', 'bannercalc' ); ?>
                </h3>
                <ul class="bannercalc-help-toc">
                    <li><a href="#getting-started" class="bannercalc-toc-link active" data-section="getting-started">
                        <span class="bannercalc-toc-num">1</span><?php esc_html_e( 'Getting Started', 'bannercalc' ); ?>
                    </a></li>
                    <li><a href="#global-settings" class="bannercalc-toc-link" data-section="global-settings">
                        <span class="bannercalc-toc-num">2</span><?php esc_html_e( 'Global Settings', 'bannercalc' ); ?>
                    </a></li>
                    <li><a href="#category-config" class="bannercalc-toc-link" data-section="category-config">
                        <span class="bannercalc-toc-num">3</span><?php esc_html_e( 'Category Configuration', 'bannercalc' ); ?>
                    </a></li>
                    <li><a href="#sizing-modes" class="bannercalc-toc-link" data-section="sizing-modes">
                        <span class="bannercalc-toc-num">4</span><?php esc_html_e( 'Sizing Modes', 'bannercalc' ); ?>
                    </a></li>
                    <li><a href="#preset-sizes" class="bannercalc-toc-link" data-section="preset-sizes">
                        <span class="bannercalc-toc-num">5</span><?php esc_html_e( 'Preset Sizes', 'bannercalc' ); ?>
                    </a></li>
                    <li><a href="#attributes" class="bannercalc-toc-link" data-section="attributes">
                        <span class="bannercalc-toc-num">6</span><?php esc_html_e( 'Attributes & Pricing', 'bannercalc' ); ?>
                    </a></li>
                    <li><a href="#quantity-bundles" class="bannercalc-toc-link" data-section="quantity-bundles">
                        <span class="bannercalc-toc-num">7</span><?php esc_html_e( 'Quantity & Bundles', 'bannercalc' ); ?>
                    </a></li>
                    <li><a href="#delivery-shipping" class="bannercalc-toc-link" data-section="delivery-shipping">
                        <span class="bannercalc-toc-num">8</span><?php esc_html_e( 'Delivery & Shipping', 'bannercalc' ); ?>
                    </a></li>
                    <li><a href="#design-service" class="bannercalc-toc-link" data-section="design-service">
                        <span class="bannercalc-toc-num">9</span><?php esc_html_e( 'Design Service', 'bannercalc' ); ?>
                    </a></li>
                    <li><a href="#product-overrides" class="bannercalc-toc-link" data-section="product-overrides">
                        <span class="bannercalc-toc-num">10</span><?php esc_html_e( 'Product Overrides', 'bannercalc' ); ?>
                    </a></li>
                    <li><a href="#price-calculation" class="bannercalc-toc-link" data-section="price-calculation">
                        <span class="bannercalc-toc-num">11</span><?php esc_html_e( 'Price Calculation', 'bannercalc' ); ?>
                    </a></li>
                    <li><a href="#cart-checkout" class="bannercalc-toc-link" data-section="cart-checkout">
                        <span class="bannercalc-toc-num">12</span><?php esc_html_e( 'Cart & Checkout', 'bannercalc' ); ?>
                    </a></li>
                    <li><a href="#troubleshooting" class="bannercalc-toc-link" data-section="troubleshooting">
                        <span class="bannercalc-toc-num">13</span><?php esc_html_e( 'Troubleshooting', 'bannercalc' ); ?>
                    </a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="bannercalc-help-content">

            <!-- ============================================================
                 SECTION 1: Getting Started
            ============================================================ -->
            <section id="getting-started" class="bannercalc-help-section">
                <div class="bannercalc-content-card">
                    <h2 class="bannercalc-section-heading">
                        <span class="bannercalc-section-num">1</span>
                        <?php esc_html_e( 'Getting Started', 'bannercalc' ); ?>
                    </h2>

                    <div class="bannercalc-help-intro">
                        <p><?php esc_html_e( 'BannerCalc is a WooCommerce plugin that replaces the default variation-based product configuration with a unified, area-based pricing engine. Products remain as Simple type — no WooCommerce variations are needed.', 'bannercalc' ); ?></p>
                    </div>

                    <div class="bannercalc-help-callout bannercalc-help-callout-info">
                        <span class="dashicons dashicons-info"></span>
                        <div>
                            <strong><?php esc_html_e( 'Requirements', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'WordPress 6.4+, WooCommerce 8.0+, PHP 8.0+', 'bannercalc' ); ?></p>
                        </div>
                    </div>

                    <h3><?php esc_html_e( 'Quick Setup Checklist', 'bannercalc' ); ?></h3>
                    <ol class="bannercalc-help-steps">
                        <li>
                            <strong><?php esc_html_e( 'Enable the plugin', 'bannercalc' ); ?></strong>
                            <p><?php
                                printf(
                                    /* translators: %s: link to settings page */
                                    esc_html__( 'Go to %s and tick "Enable BannerCalc".', 'bannercalc' ),
                                    '<a href="' . esc_url( admin_url( 'admin.php?page=bannercalc-settings' ) ) . '">' . esc_html__( 'BannerCalc → Settings', 'bannercalc' ) . '</a>'
                                );
                            ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Create WooCommerce attributes', 'bannercalc' ); ?></strong>
                            <p><?php
                                printf(
                                    /* translators: %s: link to WooCommerce attributes */
                                    esc_html__( 'Add product attributes (e.g., Material, Hemming, Orientation) in %s. Add terms for each attribute.', 'bannercalc' ),
                                    '<a href="' . esc_url( admin_url( 'edit.php?post_type=product&page=product_attributes' ) ) . '">' . esc_html__( 'Products → Attributes', 'bannercalc' ) . '</a>'
                                );
                            ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Configure a product category', 'bannercalc' ); ?></strong>
                            <p><?php
                                printf(
                                    /* translators: %s: link to category defaults page */
                                    esc_html__( 'Go to %s, select a category, enable BannerCalc, and configure sizing, rates, attributes, and presets.', 'bannercalc' ),
                                    '<a href="' . esc_url( admin_url( 'admin.php?page=bannercalc-categories' ) ) . '">' . esc_html__( 'BannerCalc → Category Defaults', 'bannercalc' ) . '</a>'
                                );
                            ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Assign products to the category', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'Create WooCommerce Simple products and place them in a BannerCalc-enabled category. The configurator will appear automatically on their product pages.', 'bannercalc' ); ?></p>
                        </li>
                    </ol>

                    <div class="bannercalc-help-callout bannercalc-help-callout-tip">
                        <span class="dashicons dashicons-lightbulb"></span>
                        <div>
                            <strong><?php esc_html_e( 'How it works', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'Configuration flows from Category Defaults → Product Overrides. Set your defaults at the category level, then optionally override specific settings on individual products.', 'bannercalc' ); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 SECTION 2: Global Settings
            ============================================================ -->
            <section id="global-settings" class="bannercalc-help-section">
                <div class="bannercalc-content-card">
                    <h2 class="bannercalc-section-heading">
                        <span class="bannercalc-section-num">2</span>
                        <?php esc_html_e( 'Global Settings', 'bannercalc' ); ?>
                    </h2>

                    <p><?php
                        printf(
                            /* translators: %s: link to settings page */
                            esc_html__( 'Configure plugin-wide defaults at %s.', 'bannercalc' ),
                            '<a href="' . esc_url( admin_url( 'admin.php?page=bannercalc-settings' ) ) . '">' . esc_html__( 'BannerCalc → Settings', 'bannercalc' ) . '</a>'
                        );
                    ?></p>

                    <div class="bannercalc-help-table-wrap">
                        <table class="bannercalc-help-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Setting', 'bannercalc' ); ?></th>
                                    <th><?php esc_html_e( 'Description', 'bannercalc' ); ?></th>
                                    <th><?php esc_html_e( 'Default', 'bannercalc' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code><?php esc_html_e( 'Enable BannerCalc', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Master switch — disabling this stops the configurator from appearing on any product.', 'bannercalc' ); ?></td>
                                    <td><code><?php esc_html_e( 'On', 'bannercalc' ); ?></code></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'Currency Symbol', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Displayed in the price breakdown and cart. Should match your WooCommerce currency.', 'bannercalc' ); ?></td>
                                    <td><code>£</code></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'Default Unit', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'The measurement unit selected by default when a customer opens the configurator.', 'bannercalc' ); ?></td>
                                    <td><code><?php esc_html_e( 'Feet (ft)', 'bannercalc' ); ?></code></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'Available Units', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Which measurement units appear in the unit selector. Choose any combination of mm, cm, inches, feet, metres.', 'bannercalc' ); ?></td>
                                    <td><code><?php esc_html_e( 'All enabled', 'bannercalc' ); ?></code></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'Price Decimals', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Number of decimal places shown in the price display.', 'bannercalc' ); ?></td>
                                    <td><code>2</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3><?php esc_html_e( 'Service Types (Delivery Speed)', 'bannercalc' ); ?></h3>
                    <p><?php esc_html_e( 'Service types let you charge a percentage markup for faster production/delivery. These are configured globally and apply to all categories.', 'bannercalc' ); ?></p>

                    <div class="bannercalc-help-table-wrap">
                        <table class="bannercalc-help-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Field', 'bannercalc' ); ?></th>
                                    <th><?php esc_html_e( 'Description', 'bannercalc' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code><?php esc_html_e( 'Label', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Display name shown to customers (e.g., "Standard Delivery", "Urgent — 24 Hours").', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'Markup %', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Percentage added to the calculated price. Set 0 for Standard, 15 for 48hr, 25 for 24hr, etc.', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'Default', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Radio button — which service type is pre-selected when the configurator loads.', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'WC Shipping Method', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Map each service type to a WooCommerce shipping method. When mapped, only the assigned shipping method will appear at checkout for that selection.', 'bannercalc' ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bannercalc-help-callout bannercalc-help-callout-tip">
                        <span class="dashicons dashicons-lightbulb"></span>
                        <div>
                            <strong><?php esc_html_e( 'Shipping Integration Tip', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'To use shipping mapping, first configure your shipping methods in WooCommerce → Settings → Shipping. Then return here to map each service type to the appropriate method. This way, selecting "Urgent — 24 Hours" will automatically select the correct shipping at checkout.', 'bannercalc' ); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 SECTION 3: Category Configuration
            ============================================================ -->
            <section id="category-config" class="bannercalc-help-section">
                <div class="bannercalc-content-card">
                    <h2 class="bannercalc-section-heading">
                        <span class="bannercalc-section-num">3</span>
                        <?php esc_html_e( 'Category Configuration', 'bannercalc' ); ?>
                    </h2>

                    <p><?php
                        printf(
                            /* translators: %s: link to category defaults page */
                            esc_html__( 'Category defaults are managed at %s. Select a parent category from the left panel, then configure its settings in the right panel.', 'bannercalc' ),
                            '<a href="' . esc_url( admin_url( 'admin.php?page=bannercalc-categories' ) ) . '">' . esc_html__( 'BannerCalc → Category Defaults', 'bannercalc' ) . '</a>'
                        );
                    ?></p>

                    <h3><?php esc_html_e( 'Configuration Sections', 'bannercalc' ); ?></h3>
                    <div class="bannercalc-help-features-grid">
                        <div class="bannercalc-help-feature-card">
                            <div class="bannercalc-help-feature-icon"><span class="dashicons dashicons-admin-settings"></span></div>
                            <h4><?php esc_html_e( 'General', 'bannercalc' ); ?></h4>
                            <p><?php esc_html_e( 'Enable/disable BannerCalc for this category, set sizing mode, and configure dimension constraints.', 'bannercalc' ); ?></p>
                        </div>
                        <div class="bannercalc-help-feature-card">
                            <div class="bannercalc-help-feature-icon"><span class="dashicons dashicons-money-alt"></span></div>
                            <h4><?php esc_html_e( 'Pricing', 'bannercalc' ); ?></h4>
                            <p><?php esc_html_e( 'Set area rate (per sq ft / sq m), minimum charge, and whether pricing is area-based or fixed.', 'bannercalc' ); ?></p>
                        </div>
                        <div class="bannercalc-help-feature-card">
                            <div class="bannercalc-help-feature-icon"><span class="dashicons dashicons-image-crop"></span></div>
                            <h4><?php esc_html_e( 'Preset Sizes', 'bannercalc' ); ?></h4>
                            <p><?php esc_html_e( 'Define popular size presets with optional fixed prices. Import UK standard sizes or add custom entries.', 'bannercalc' ); ?></p>
                        </div>
                        <div class="bannercalc-help-feature-card">
                            <div class="bannercalc-help-feature-icon"><span class="dashicons dashicons-tag"></span></div>
                            <h4><?php esc_html_e( 'Attributes', 'bannercalc' ); ?></h4>
                            <p><?php esc_html_e( 'Choose which WooCommerce attributes to show, set display styles (pills/dropdown/toggle), and configure per-option pricing.', 'bannercalc' ); ?></p>
                        </div>
                        <div class="bannercalc-help-feature-card">
                            <div class="bannercalc-help-feature-icon"><span class="dashicons dashicons-cart"></span></div>
                            <h4><?php esc_html_e( 'Quantity', 'bannercalc' ); ?></h4>
                            <p><?php esc_html_e( 'Set quantity input mode (free-type or bundles), minimum order quantity, default quantity, and bundle tiers.', 'bannercalc' ); ?></p>
                        </div>
                        <div class="bannercalc-help-feature-card">
                            <div class="bannercalc-help-feature-icon"><span class="dashicons dashicons-clipboard"></span></div>
                            <h4><?php esc_html_e( 'Dimension Limits', 'bannercalc' ); ?></h4>
                            <p><?php esc_html_e( 'Set min/max width and height constraints in the default measurement unit.', 'bannercalc' ); ?></p>
                        </div>
                    </div>

                    <div class="bannercalc-help-callout bannercalc-help-callout-info">
                        <span class="dashicons dashicons-info"></span>
                        <div>
                            <strong><?php esc_html_e( 'Inheritance Model', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'Only top-level (parent) categories are shown. Products in child categories inherit the parent category\'s configuration. Product-level overrides can further customise individual products.', 'bannercalc' ); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 SECTION 4: Sizing Modes
            ============================================================ -->
            <section id="sizing-modes" class="bannercalc-help-section">
                <div class="bannercalc-content-card">
                    <h2 class="bannercalc-section-heading">
                        <span class="bannercalc-section-num">4</span>
                        <?php esc_html_e( 'Sizing Modes', 'bannercalc' ); ?>
                    </h2>

                    <p><?php esc_html_e( 'The sizing mode controls how customers specify the size of their product. This is set per category.', 'bannercalc' ); ?></p>

                    <div class="bannercalc-help-table-wrap">
                        <table class="bannercalc-help-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Mode', 'bannercalc' ); ?></th>
                                    <th><?php esc_html_e( 'Behaviour', 'bannercalc' ); ?></th>
                                    <th><?php esc_html_e( 'Best For', 'bannercalc' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code><?php esc_html_e( 'width-height', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Customer enters both width and height. Price is calculated from the area. A unit selector lets them switch between measurement units.', 'bannercalc' ); ?></td>
                                    <td><?php esc_html_e( 'Banners, signs, canvases — any product where both dimensions vary.', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'width-only', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Customer enters width only. Height is either fixed or derived from presets. Useful for products with a fixed height.', 'bannercalc' ); ?></td>
                                    <td><?php esc_html_e( 'Roller banners, vehicle wraps with fixed heights.', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'preset-only', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Customer selects from predefined size options only. No custom dimensions. Each preset can have a fixed price.', 'bannercalc' ); ?></td>
                                    <td><?php esc_html_e( 'Business cards, stickers, standard sizes.', 'bannercalc' ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bannercalc-help-callout bannercalc-help-callout-tip">
                        <span class="dashicons dashicons-lightbulb"></span>
                        <div>
                            <strong><?php esc_html_e( 'Dimension Constraints', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'For width-height and width-only modes, set min/max dimension values to prevent impractical sizes. Values are specified in the default unit configured under Global Settings.', 'bannercalc' ); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 SECTION 5: Preset Sizes
            ============================================================ -->
            <section id="preset-sizes" class="bannercalc-help-section">
                <div class="bannercalc-content-card">
                    <h2 class="bannercalc-section-heading">
                        <span class="bannercalc-section-num">5</span>
                        <?php esc_html_e( 'Preset Sizes', 'bannercalc' ); ?>
                    </h2>

                    <p><?php esc_html_e( 'Presets are pre-defined size options shown as selectable cards on the product page. They speed up the purchasing process for common sizes.', 'bannercalc' ); ?></p>

                    <h3><?php esc_html_e( 'Preset Fields', 'bannercalc' ); ?></h3>
                    <div class="bannercalc-help-table-wrap">
                        <table class="bannercalc-help-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Field', 'bannercalc' ); ?></th>
                                    <th><?php esc_html_e( 'Description', 'bannercalc' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code><?php esc_html_e( 'Label', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Display name (e.g., "A1 Poster", "6ft × 3ft Banner").', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'Width / Height', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Dimensions in the default unit. These populate the size inputs when selected.', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'Fixed Price', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Optional. If set, this price is used instead of calculating from area × rate. Leave blank to use area-based pricing.', 'bannercalc' ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3><?php esc_html_e( 'Import UK Standard Sizes', 'bannercalc' ); ?></h3>
                    <p><?php esc_html_e( 'Use the "Import UK Sizes" button on the Category Defaults page to auto-populate popular UK banner and print sizes. The imported list includes common banner sizes (2×1ft, 3×2ft, 6×3ft, 8×3ft, 10×3ft, etc.) with dimensions already set. You can edit, delete, or add more presets after importing.', 'bannercalc' ); ?></p>

                    <div class="bannercalc-help-callout bannercalc-help-callout-info">
                        <span class="dashicons dashicons-info"></span>
                        <div>
                            <strong><?php esc_html_e( 'Price Range Display', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'When presets have fixed prices, the product page and shop archive cards display a "From £X.XX" price range based on the lowest preset price. This gives customers an instant price indication before they configure.', 'bannercalc' ); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 SECTION 6: Attributes & Pricing
            ============================================================ -->
            <section id="attributes" class="bannercalc-help-section">
                <div class="bannercalc-content-card">
                    <h2 class="bannercalc-section-heading">
                        <span class="bannercalc-section-num">6</span>
                        <?php esc_html_e( 'Attributes & Pricing', 'bannercalc' ); ?>
                    </h2>

                    <p><?php esc_html_e( 'BannerCalc uses WooCommerce\'s built-in product attributes as a data source. You configure which attributes to include and how each option affects pricing.', 'bannercalc' ); ?></p>

                    <h3><?php esc_html_e( 'Setting Up Attributes', 'bannercalc' ); ?></h3>
                    <ol class="bannercalc-help-steps">
                        <li>
                            <strong><?php esc_html_e( 'Create attributes in WooCommerce', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'Go to Products → Attributes. Create attributes like "Material", "Hemming", "Orientation", "Eyelets". Add terms for each.', 'bannercalc' ); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Enable attributes per category', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'In Category Defaults, check the attributes you want to appear on the configurator for that category.', 'bannercalc' ); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Configure display style', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'For each attribute, choose how it appears on the frontend: Pills (badge buttons), Dropdown (select menu), or Toggle (on/off switch for 2-option attributes).', 'bannercalc' ); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Set pricing per option', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'Each attribute term can have a pricing modifier. Choose the pricing type and set the value.', 'bannercalc' ); ?></p>
                        </li>
                    </ol>

                    <h3><?php esc_html_e( 'Attribute Pricing Types', 'bannercalc' ); ?></h3>
                    <div class="bannercalc-help-table-wrap">
                        <table class="bannercalc-help-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Type', 'bannercalc' ); ?></th>
                                    <th><?php esc_html_e( 'Description', 'bannercalc' ); ?></th>
                                    <th><?php esc_html_e( 'Example', 'bannercalc' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code><?php esc_html_e( 'none', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'No price adjustment. The option is purely descriptive.', 'bannercalc' ); ?></td>
                                    <td><?php esc_html_e( 'Orientation: Portrait / Landscape', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'flat', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Adds a fixed amount to the total price.', 'bannercalc' ); ?></td>
                                    <td><?php esc_html_e( 'Hemming: +£2.50 flat', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'percent', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Adds a percentage of the base price (area × rate).', 'bannercalc' ); ?></td>
                                    <td><?php esc_html_e( 'Premium Vinyl: +20%', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'per_sqft', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Adds a per-square-foot charge. Scales with product size.', 'bannercalc' ); ?></td>
                                    <td><?php esc_html_e( 'Lamination: +£0.50/sqft', 'bannercalc' ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bannercalc-help-callout bannercalc-help-callout-tip">
                        <span class="dashicons dashicons-lightbulb"></span>
                        <div>
                            <strong><?php esc_html_e( 'Default Values', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'You can set a default value for each attribute. This term will be pre-selected when the configurator loads. You can also mark an attribute as "required" to prevent customers from proceeding without a selection.', 'bannercalc' ); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 SECTION 7: Quantity & Bundles
            ============================================================ -->
            <section id="quantity-bundles" class="bannercalc-help-section">
                <div class="bannercalc-content-card">
                    <h2 class="bannercalc-section-heading">
                        <span class="bannercalc-section-num">7</span>
                        <?php esc_html_e( 'Quantity & Bundles', 'bannercalc' ); ?>
                    </h2>

                    <p><?php esc_html_e( 'Control how customers specify the quantity they want to order. This is configured per category.', 'bannercalc' ); ?></p>

                    <h3><?php esc_html_e( 'Quantity Modes', 'bannercalc' ); ?></h3>
                    <div class="bannercalc-help-table-wrap">
                        <table class="bannercalc-help-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Mode', 'bannercalc' ); ?></th>
                                    <th><?php esc_html_e( 'Behaviour', 'bannercalc' ); ?></th>
                                    <th><?php esc_html_e( 'Best For', 'bannercalc' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code><?php esc_html_e( 'input', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Standard quantity input — customers type any number above the minimum. The live price updates as they change the quantity.', 'bannercalc' ); ?></td>
                                    <td><?php esc_html_e( 'General products, banners, signs.', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'bundles', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Customers choose from predefined quantity tiers displayed as clickable pills (e.g., 25, 50, 100, 250, 500). The quantity is set to the selected bundle amount.', 'bannercalc' ); ?></td>
                                    <td><?php esc_html_e( 'Business cards, stickers, flyers — products sold in fixed bundles.', 'bannercalc' ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3><?php esc_html_e( 'Quantity Settings', 'bannercalc' ); ?></h3>
                    <div class="bannercalc-help-table-wrap">
                        <table class="bannercalc-help-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Field', 'bannercalc' ); ?></th>
                                    <th><?php esc_html_e( 'Description', 'bannercalc' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code><?php esc_html_e( 'Minimum Order Quantity', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'The lowest quantity a customer can add to cart. Cart validation enforces this. Default: 1.', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'Default Quantity', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Pre-filled quantity when the product page loads. Should be ≥ minimum. Default: 1.', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'Bundle Quantities', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Comma-separated list of tier amounts (e.g., "25, 50, 100, 250, 500"). Only visible when mode is set to "bundles".', 'bannercalc' ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bannercalc-help-callout bannercalc-help-callout-info">
                        <span class="dashicons dashicons-info"></span>
                        <div>
                            <strong><?php esc_html_e( 'Live Price × Quantity', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'The price display updates in real-time as customers change quantity. It shows both the unit price and the total (unit price × quantity), so customers always see the cost per item and the full order total.', 'bannercalc' ); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 SECTION 8: Delivery & Shipping
            ============================================================ -->
            <section id="delivery-shipping" class="bannercalc-help-section">
                <div class="bannercalc-content-card">
                    <h2 class="bannercalc-section-heading">
                        <span class="bannercalc-section-num">8</span>
                        <?php esc_html_e( 'Delivery & Shipping Integration', 'bannercalc' ); ?>
                    </h2>

                    <p><?php esc_html_e( 'BannerCalc integrates the delivery speed selection with WooCommerce\'s shipping system so the correct shipping method is applied automatically at checkout.', 'bannercalc' ); ?></p>

                    <h3><?php esc_html_e( 'How It Works', 'bannercalc' ); ?></h3>
                    <ol class="bannercalc-help-steps">
                        <li>
                            <strong><?php esc_html_e( 'Set up WooCommerce shipping', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'Configure shipping zones and methods in WooCommerce → Settings → Shipping. Create methods for each delivery speed (e.g., "Standard Shipping", "Express 48hr", "Next Day").', 'bannercalc' ); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Map service types to shipping methods', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'In BannerCalc → Settings → Service Types, use the "WC Shipping Method" dropdown for each tier to link it to the matching WooCommerce shipping method.', 'bannercalc' ); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Automatic checkout filtering', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'When a customer selects a delivery speed (e.g., "Urgent — 24 Hours"), only the mapped WooCommerce shipping method appears at checkout. Other shipping options are hidden.', 'bannercalc' ); ?></p>
                        </li>
                    </ol>

                    <div class="bannercalc-help-callout bannercalc-help-callout-warning">
                        <span class="dashicons dashicons-warning"></span>
                        <div>
                            <strong><?php esc_html_e( 'Important', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'Shipping method mapping is optional. If you leave the WC Shipping Method dropdown as "— None —", all shipping methods will remain available at checkout for that service type. Only mapped selections filter the checkout shipping options.', 'bannercalc' ); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 SECTION 9: Design Service
            ============================================================ -->
            <section id="design-service" class="bannercalc-help-section">
                <div class="bannercalc-content-card">
                    <h2 class="bannercalc-section-heading">
                        <span class="bannercalc-section-num">9</span>
                        <?php esc_html_e( 'Design Service', 'bannercalc' ); ?>
                    </h2>

                    <p><?php esc_html_e( 'Offer a professional design service add-on that customers can opt into during product configuration.', 'bannercalc' ); ?></p>

                    <div class="bannercalc-help-table-wrap">
                        <table class="bannercalc-help-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Setting', 'bannercalc' ); ?></th>
                                    <th><?php esc_html_e( 'Description', 'bannercalc' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code><?php esc_html_e( 'Enable', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Show/hide the design service toggle on the configurator.', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'Label', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Display name (default: "Professional Design Service").', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'Price', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Fixed price added to the order total when selected (default: £14.99).', 'bannercalc' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code><?php esc_html_e( 'Description', 'bannercalc' ); ?></code></td>
                                    <td><?php esc_html_e( 'Optional help text shown below the toggle to explain what the service includes.', 'bannercalc' ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p><?php esc_html_e( 'When enabled, the design service appears as a clearly labelled card with a toggle switch. The price is added as a flat fee to the calculated total and shown as a separate line in the price breakdown.', 'bannercalc' ); ?></p>
                </div>
            </section>

            <!-- ============================================================
                 SECTION 10: Product Overrides
            ============================================================ -->
            <section id="product-overrides" class="bannercalc-help-section">
                <div class="bannercalc-content-card">
                    <h2 class="bannercalc-section-heading">
                        <span class="bannercalc-section-num">10</span>
                        <?php esc_html_e( 'Product Overrides', 'bannercalc' ); ?>
                    </h2>

                    <p><?php esc_html_e( 'Individual products inherit their category\'s BannerCalc config, but you can override specific settings on a per-product basis.', 'bannercalc' ); ?></p>

                    <h3><?php esc_html_e( 'How to Override', 'bannercalc' ); ?></h3>
                    <ol class="bannercalc-help-steps">
                        <li>
                            <strong><?php esc_html_e( 'Edit a WooCommerce product', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'Go to Products → Edit a product that belongs to a BannerCalc-enabled category.', 'bannercalc' ); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Find the BannerCalc metabox', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'Scroll down to the "BannerCalc Configuration" metabox. It shows the inherited category settings.', 'bannercalc' ); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e( 'Enable overrides', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'Tick "Override category defaults" to unlock the editable fields. Only the fields you change will override — everything else stays inherited.', 'bannercalc' ); ?></p>
                        </li>
                    </ol>

                    <h3><?php esc_html_e( 'What Can Be Overridden', 'bannercalc' ); ?></h3>
                    <ul class="bannercalc-help-list">
                        <li><?php esc_html_e( 'Sizing mode (width-height, width-only, preset-only)', 'bannercalc' ); ?></li>
                        <li><?php esc_html_e( 'Area rate, minimum charge', 'bannercalc' ); ?></li>
                        <li><?php esc_html_e( 'Dimension constraints (min/max width/height)', 'bannercalc' ); ?></li>
                        <li><?php esc_html_e( 'Available units', 'bannercalc' ); ?></li>
                        <li><?php esc_html_e( 'Attribute pricing (per-option price adjustments)', 'bannercalc' ); ?></li>
                        <li><?php esc_html_e( 'Attribute visibility (disable specific attributes for this product)', 'bannercalc' ); ?></li>
                        <li><?php esc_html_e( 'Preset sizes (completely replace the category presets)', 'bannercalc' ); ?></li>
                        <li><?php esc_html_e( 'Quantity mode, minimum, default, and bundle tiers', 'bannercalc' ); ?></li>
                    </ul>

                    <div class="bannercalc-help-callout bannercalc-help-callout-tip">
                        <span class="dashicons dashicons-lightbulb"></span>
                        <div>
                            <strong><?php esc_html_e( 'Deep Merge', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'Product overrides are deep-merged with category defaults. For scalar fields (rate, mode, min charge), the product value wins. For attributes, pricing modifiers are merged per-option. Preset sizes are replaced entirely if overridden.', 'bannercalc' ); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 SECTION 11: Price Calculation
            ============================================================ -->
            <section id="price-calculation" class="bannercalc-help-section">
                <div class="bannercalc-content-card">
                    <h2 class="bannercalc-section-heading">
                        <span class="bannercalc-section-num">11</span>
                        <?php esc_html_e( 'Price Calculation', 'bannercalc' ); ?>
                    </h2>

                    <p><?php esc_html_e( 'BannerCalc uses a multi-step pricing formula. The frontend JavaScript calculates a live preview; the server recalculates the authoritative price when items are added to the cart.', 'bannercalc' ); ?></p>

                    <h3><?php esc_html_e( 'Calculation Steps', 'bannercalc' ); ?></h3>
                    <div class="bannercalc-help-formula">
                        <div class="bannercalc-formula-step">
                            <span class="bannercalc-formula-num">1</span>
                            <div>
                                <strong><?php esc_html_e( 'Base Price', 'bannercalc' ); ?></strong>
                                <p><?php esc_html_e( 'Area (sq ft) × Rate per sq ft. If a preset with a fixed price is selected, the fixed price is used instead.', 'bannercalc' ); ?></p>
                                <code class="bannercalc-formula-code"><?php esc_html_e( 'base = area_sqft × rate_per_sqft', 'bannercalc' ); ?></code>
                            </div>
                        </div>
                        <div class="bannercalc-formula-step">
                            <span class="bannercalc-formula-num">2</span>
                            <div>
                                <strong><?php esc_html_e( 'Minimum Charge', 'bannercalc' ); ?></strong>
                                <p><?php esc_html_e( 'If the base price is below the category/product minimum charge, it\'s raised to the minimum.', 'bannercalc' ); ?></p>
                                <code class="bannercalc-formula-code"><?php esc_html_e( 'base = max(base, minimum_charge)', 'bannercalc' ); ?></code>
                            </div>
                        </div>
                        <div class="bannercalc-formula-step">
                            <span class="bannercalc-formula-num">3</span>
                            <div>
                                <strong><?php esc_html_e( 'Attribute Add-ons', 'bannercalc' ); ?></strong>
                                <p><?php esc_html_e( 'Each selected attribute\'s pricing modifier is applied: flat = +£X, percent = +X% of base, per_sqft = +£X × area.', 'bannercalc' ); ?></p>
                                <code class="bannercalc-formula-code"><?php esc_html_e( 'subtotal = base + Σ(attribute add-ons)', 'bannercalc' ); ?></code>
                            </div>
                        </div>
                        <div class="bannercalc-formula-step">
                            <span class="bannercalc-formula-num">4</span>
                            <div>
                                <strong><?php esc_html_e( 'Service Markup', 'bannercalc' ); ?></strong>
                                <p><?php esc_html_e( 'The selected delivery speed markup percentage is applied to the subtotal.', 'bannercalc' ); ?></p>
                                <code class="bannercalc-formula-code"><?php esc_html_e( 'total = subtotal × (1 + service_markup / 100)', 'bannercalc' ); ?></code>
                            </div>
                        </div>
                        <div class="bannercalc-formula-step">
                            <span class="bannercalc-formula-num">5</span>
                            <div>
                                <strong><?php esc_html_e( 'Design Service', 'bannercalc' ); ?></strong>
                                <p><?php esc_html_e( 'If enabled and selected, the fixed design service fee is added.', 'bannercalc' ); ?></p>
                                <code class="bannercalc-formula-code"><?php esc_html_e( 'total = total + design_service_fee', 'bannercalc' ); ?></code>
                            </div>
                        </div>
                        <div class="bannercalc-formula-step">
                            <span class="bannercalc-formula-num">6</span>
                            <div>
                                <strong><?php esc_html_e( 'Quantity', 'bannercalc' ); ?></strong>
                                <p><?php esc_html_e( 'The per-unit price is multiplied by the selected quantity to get the cart line total.', 'bannercalc' ); ?></p>
                                <code class="bannercalc-formula-code"><?php esc_html_e( 'line_total = total × quantity', 'bannercalc' ); ?></code>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 SECTION 12: Cart & Checkout
            ============================================================ -->
            <section id="cart-checkout" class="bannercalc-help-section">
                <div class="bannercalc-content-card">
                    <h2 class="bannercalc-section-heading">
                        <span class="bannercalc-section-num">12</span>
                        <?php esc_html_e( 'Cart & Checkout', 'bannercalc' ); ?>
                    </h2>

                    <p><?php esc_html_e( 'When a customer adds a configured product to the cart, BannerCalc stores all configuration data as cart item meta and recalculates the price server-side.', 'bannercalc' ); ?></p>

                    <h3><?php esc_html_e( 'Cart Behaviour', 'bannercalc' ); ?></h3>
                    <ul class="bannercalc-help-list">
                        <li><strong><?php esc_html_e( 'Price recalculation:', 'bannercalc' ); ?></strong> <?php esc_html_e( 'The server-side PricingEngine recalculates the price on every add-to-cart. The frontend JS price is never trusted — it\'s a preview only.', 'bannercalc' ); ?></li>
                        <li><strong><?php esc_html_e( 'Cart item meta:', 'bannercalc' ); ?></strong> <?php esc_html_e( 'All configuration details (dimensions, unit, attributes, service type, design service) are stored as cart item metadata and displayed in the cart/checkout.', 'bannercalc' ); ?></li>
                        <li><strong><?php esc_html_e( 'Minimum quantity validation:', 'bannercalc' ); ?></strong> <?php esc_html_e( 'If a category has a minimum order quantity set, the cart validates and shows an error if the quantity is too low.', 'bannercalc' ); ?></li>
                        <li><strong><?php esc_html_e( 'Shipping filtering:', 'bannercalc' ); ?></strong> <?php esc_html_e( 'If the selected service type is mapped to a WooCommerce shipping method, only that method appears at checkout.', 'bannercalc' ); ?></li>
                    </ul>

                    <h3><?php esc_html_e( 'Order Item Meta', 'bannercalc' ); ?></h3>
                    <p><?php esc_html_e( 'After checkout, all BannerCalc data is copied to the WooCommerce order item meta. This appears in the order details in wp-admin and in order confirmation emails — giving full visibility into what was ordered.', 'bannercalc' ); ?></p>

                    <div class="bannercalc-help-callout bannercalc-help-callout-info">
                        <span class="dashicons dashicons-info"></span>
                        <div>
                            <strong><?php esc_html_e( 'HPOS Compatible', 'bannercalc' ); ?></strong>
                            <p><?php esc_html_e( 'BannerCalc declares compatibility with WooCommerce High-Performance Order Storage (HPOS). Order data works seamlessly with both legacy and HPOS storage backends.', 'bannercalc' ); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 SECTION 13: Troubleshooting
            ============================================================ -->
            <section id="troubleshooting" class="bannercalc-help-section">
                <div class="bannercalc-content-card">
                    <h2 class="bannercalc-section-heading">
                        <span class="bannercalc-section-num">13</span>
                        <?php esc_html_e( 'Troubleshooting', 'bannercalc' ); ?>
                    </h2>

                    <div class="bannercalc-help-faq">
                        <div class="bannercalc-faq-item">
                            <h3 class="bannercalc-faq-question">
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                                <?php esc_html_e( 'The configurator doesn\'t appear on product pages', 'bannercalc' ); ?>
                            </h3>
                            <div class="bannercalc-faq-answer">
                                <ul>
                                    <li><?php esc_html_e( 'Verify BannerCalc is enabled globally (Settings → Enable BannerCalc).', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'Check the product\'s parent category has BannerCalc enabled in Category Defaults.', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'Ensure the product is a Simple product type, not Variable.', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'Clear any page or object caches.', 'bannercalc' ); ?></li>
                                </ul>
                            </div>
                        </div>

                        <div class="bannercalc-faq-item">
                            <h3 class="bannercalc-faq-question">
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                                <?php esc_html_e( 'Prices show as £0.00 or are incorrect', 'bannercalc' ); ?>
                            </h3>
                            <div class="bannercalc-faq-answer">
                                <ul>
                                    <li><?php esc_html_e( 'Ensure an area rate (rate per sq ft) is configured for the category or product.', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'If using presets with fixed prices, verify each preset has a price entered.', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'Check browser console (F12) for JavaScript errors that might prevent calculation.', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'Confirm WooCommerce currency matches the BannerCalc currency symbol setting.', 'bannercalc' ); ?></li>
                                </ul>
                            </div>
                        </div>

                        <div class="bannercalc-faq-item">
                            <h3 class="bannercalc-faq-question">
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                                <?php esc_html_e( 'Attributes don\'t appear on the configurator', 'bannercalc' ); ?>
                            </h3>
                            <div class="bannercalc-faq-answer">
                                <ul>
                                    <li><?php esc_html_e( 'Attributes must be created in WooCommerce → Products → Attributes first, with terms added.', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'In Category Defaults, ensure each desired attribute is enabled (checked) for the category.', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'Verify the attribute has at least one term assigned.', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'If using product overrides, check that the attribute isn\'t disabled at the product level.', 'bannercalc' ); ?></li>
                                </ul>
                            </div>
                        </div>

                        <div class="bannercalc-faq-item">
                            <h3 class="bannercalc-faq-question">
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                                <?php esc_html_e( 'Shipping methods aren\'t being filtered at checkout', 'bannercalc' ); ?>
                            </h3>
                            <div class="bannercalc-faq-answer">
                                <ul>
                                    <li><?php esc_html_e( 'Check that you\'ve mapped service types to WooCommerce shipping methods in Settings.', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'Verify the shipping method exists in the correct WooCommerce shipping zone for the customer\'s address.', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'If the cart contains non-BannerCalc products, shipping filtering may be disabled as a safeguard.', 'bannercalc' ); ?></li>
                                </ul>
                            </div>
                        </div>

                        <div class="bannercalc-faq-item">
                            <h3 class="bannercalc-faq-question">
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                                <?php esc_html_e( 'Bundle quantity pills don\'t appear', 'bannercalc' ); ?>
                            </h3>
                            <div class="bannercalc-faq-answer">
                                <ul>
                                    <li><?php esc_html_e( 'Set the Quantity Mode to "bundles" in Category Defaults.', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'Enter bundle quantities as a comma-separated list (e.g., "25, 50, 100, 250, 500").', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'Ensure the category config is saved after changes.', 'bannercalc' ); ?></li>
                                </ul>
                            </div>
                        </div>

                        <div class="bannercalc-faq-item">
                            <h3 class="bannercalc-faq-question">
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                                <?php esc_html_e( 'Product page shows wrong price range', 'bannercalc' ); ?>
                            </h3>
                            <div class="bannercalc-faq-answer">
                                <ul>
                                    <li><?php esc_html_e( 'Price ranges are derived from preset sizes with fixed prices. Ensure presets have prices set.', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'If no presets have fixed prices, the default WooCommerce price display is used.', 'bannercalc' ); ?></li>
                                    <li><?php esc_html_e( 'Check that the product\'s category has presets configured and saved.', 'bannercalc' ); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <div class="bannercalc-admin-footer">
                <span class="bannercalc-version-badge"><?php echo esc_html( 'v' . BANNERCALC_VERSION ); ?></span>
                <span class="bannercalc-footer-text"><?php esc_html_e( 'BannerCalc — Product Configurator & Pricing Engine', 'bannercalc' ); ?></span>
            </div>

        </div><!-- .bannercalc-help-content -->

    </div><!-- .bannercalc-help-layout -->

</div><!-- .bannercalc-admin-wrap -->

<script>
(function() {
    'use strict';

    var tocLinks = document.querySelectorAll('.bannercalc-toc-link');
    var sections = document.querySelectorAll('.bannercalc-help-section');

    // Smooth scroll on click.
    tocLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('href').substring(1);
            var target = document.getElementById(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            // Update active state.
            tocLinks.forEach(function(l) { l.classList.remove('active'); });
            this.classList.add('active');
        });
    });

    // Active TOC on scroll.
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var id = entry.target.id;
                tocLinks.forEach(function(l) { l.classList.remove('active'); });
                var activeLink = document.querySelector('.bannercalc-toc-link[data-section="' + id + '"]');
                if (activeLink) {
                    activeLink.classList.add('active');
                }
            }
        });
    }, {
        rootMargin: '-80px 0px -60% 0px',
        threshold: 0
    });

    sections.forEach(function(section) {
        observer.observe(section);
    });

    // Toggle FAQ answers.
    document.querySelectorAll('.bannercalc-faq-question').forEach(function(q) {
        q.addEventListener('click', function() {
            var item = this.closest('.bannercalc-faq-item');
            item.classList.toggle('open');
        });
    });
})();
</script>
