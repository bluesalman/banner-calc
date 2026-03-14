<?php
/**
 * Settings page HTML template.
 *
 * @package BannerCalc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings  = \BannerCalc\Plugin::get_settings();
$all_units = \BannerCalc\UnitConverter::get_unit_labels();
?>
<div class="wrap bannercalc-admin-wrap bannercalc-settings">

    <!-- CMYK Brand Bar -->
    <div class="bannercalc-cmyk-bar"><span></span><span></span><span></span><span></span></div>

    <!-- Page Header -->
    <div class="bannercalc-page-header">
        <div class="bannercalc-admin-overline"><?php esc_html_e( 'CONFIGURATION', 'bannercalc' ); ?></div>
        <h1 class="bannercalc-page-title"><?php esc_html_e( 'Global Settings', 'bannercalc' ); ?></h1>
        <p class="bannercalc-page-desc"><?php esc_html_e( 'Configure global defaults for the BannerCalc pricing engine.', 'bannercalc' ); ?></p>
    </div>

    <?php settings_errors( 'bannercalc_settings_group' ); ?>

    <div class="bannercalc-content-card">
    <form method="post" action="options.php">
        <?php settings_fields( 'bannercalc_settings_group' ); ?>

        <div class="bannercalc-section-block">
            <h2 class="bannercalc-section-heading"><?php esc_html_e( 'General', 'bannercalc' ); ?></h2>

            <table class="form-table bannercalc-form-table">
                <tr>
                    <th scope="row">
                        <label for="bannercalc_enabled"><?php esc_html_e( 'Enable BannerCalc', 'bannercalc' ); ?></label>
                    </th>
                    <td>
                        <label class="bannercalc-toggle-label">
                            <input type="checkbox"
                                   id="bannercalc_enabled"
                                   name="bannercalc_settings[enabled]"
                                   value="1"
                                   <?php checked( $settings['enabled'] ); ?> />
                            <?php esc_html_e( 'Enable the BannerCalc pricing engine globally', 'bannercalc' ); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="bannercalc_currency"><?php esc_html_e( 'Currency Symbol', 'bannercalc' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="bannercalc_currency"
                               name="bannercalc_settings[currency_symbol]"
                               value="<?php echo esc_attr( $settings['currency_symbol'] ); ?>"
                               class="small-text" />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="bannercalc_default_unit"><?php esc_html_e( 'Default Unit', 'bannercalc' ); ?></label>
                    </th>
                    <td>
                        <select id="bannercalc_default_unit" name="bannercalc_settings[default_unit]" class="bannercalc-select">
                            <?php foreach ( $all_units as $unit_key => $unit_label ) : ?>
                                <option value="<?php echo esc_attr( $unit_key ); ?>"
                                        <?php selected( $settings['default_unit'], $unit_key ); ?>>
                                    <?php echo esc_html( $unit_label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Available Units', 'bannercalc' ); ?></th>
                    <td>
                        <?php foreach ( $all_units as $unit_key => $unit_label ) : ?>
                            <label class="bannercalc-checkbox-label">
                                <input type="checkbox"
                                       name="bannercalc_settings[available_units][]"
                                       value="<?php echo esc_attr( $unit_key ); ?>"
                                       <?php checked( in_array( $unit_key, $settings['available_units'], true ) ); ?> />
                                <?php echo esc_html( $unit_label ); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="bannercalc_decimals"><?php esc_html_e( 'Price Decimals', 'bannercalc' ); ?></label>
                    </th>
                    <td>
                        <input type="number"
                               id="bannercalc_decimals"
                               name="bannercalc_settings[price_display_decimals]"
                               value="<?php echo esc_attr( $settings['price_display_decimals'] ); ?>"
                               class="small-text"
                               min="0"
                               max="4" />
                    </td>
                </tr>
            </table>
        </div>

        <div class="bannercalc-section-block">
            <h2 class="bannercalc-section-heading"><?php esc_html_e( 'Service Types / Delivery Speed', 'bannercalc' ); ?></h2>
            <p class="bannercalc-field-hint" style="margin-bottom:12px;">
                <?php esc_html_e( 'Configure delivery speed tiers. Each tier maps to a WooCommerce shipping method. When a customer selects a delivery speed, the corresponding WC shipping method is applied in the cart.', 'bannercalc' ); ?>
            </p>

            <!-- Collection toggle -->
            <table class="form-table bannercalc-form-table" style="margin-bottom:20px;">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Enable Collection', 'bannercalc' ); ?></th>
                    <td>
                        <label class="bannercalc-toggle-label">
                            <input type="checkbox"
                                   name="bannercalc_settings[collection_enabled]"
                                   value="1"
                                   <?php checked( ! empty( $settings['collection_enabled'] ) ); ?> />
                            <?php esc_html_e( 'Show a Collection / Shipping toggle on the product page. Collection uses WooCommerce Local Pickup (free for standard, urgent markups still apply).', 'bannercalc' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <?php
            $service_types = $settings['service_types'] ?? [
                [ 'slug' => 'standard',  'label' => 'Standard Delivery',    'markup' => 0,  'default' => true,  'shipping_method' => '' ],
                [ 'slug' => 'urgent-48', 'label' => 'Urgent — 48 Hours', 'markup' => 15, 'default' => false, 'shipping_method' => '' ],
                [ 'slug' => 'urgent-24', 'label' => 'Urgent — 24 Hours', 'markup' => 25, 'default' => false, 'shipping_method' => '' ],
            ];

            // Get available WC shipping methods for mapping dropdown.
            $wc_shipping_methods = [];
            if ( class_exists( 'WC_Shipping_Zones' ) ) {
                $zones = \WC_Shipping_Zones::get_zones();
                // Also include zone 0 (rest of the world).
                $zone_zero = new \WC_Shipping_Zone( 0 );
                $zones[0]  = [
                    'zone_id'         => 0,
                    'zone_name'       => $zone_zero->get_zone_name(),
                    'shipping_methods' => $zone_zero->get_shipping_methods(),
                ];
                foreach ( $zones as $zone ) {
                    $zone_name = $zone['zone_name'] ?? 'Zone';
                    $methods   = $zone['shipping_methods'] ?? [];
                    foreach ( $methods as $method ) {
                        $instance_id = $method->get_instance_id();
                        $method_id   = $method->id . ':' . $instance_id;
                        $wc_shipping_methods[ $method_id ] = $zone_name . ' — ' . $method->get_title();
                    }
                }
            }
            $default_slug = 'standard';
            foreach ( $service_types as $st ) {
                if ( ! empty( $st['default'] ) ) {
                    $default_slug = $st['slug'];
                    break;
                }
            }
            ?>
            <table class="form-table bannercalc-form-table">
                <?php foreach ( $service_types as $si => $st ) : ?>
                <tr>
                    <th scope="row" style="width:50px;">
                        <label>
                            <input type="radio"
                                   name="bannercalc_settings[service_types_default]"
                                   value="<?php echo esc_attr( $st['slug'] ); ?>"
                                   <?php checked( $default_slug, $st['slug'] ); ?> />
                            <?php esc_html_e( 'Default', 'bannercalc' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="hidden" name="bannercalc_settings[service_types][<?php echo $si; ?>][slug]"
                               value="<?php echo esc_attr( $st['slug'] ); ?>" />
                        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                            <label style="font-size:12px;">
                                <?php esc_html_e( 'Label:', 'bannercalc' ); ?>
                                <input type="text"
                                       name="bannercalc_settings[service_types][<?php echo $si; ?>][label]"
                                       value="<?php echo esc_attr( $st['label'] ); ?>"
                                       class="regular-text" style="width:220px;" />
                            </label>
                            <label style="font-size:12px;">
                                <?php esc_html_e( 'Markup %:', 'bannercalc' ); ?>
                                <input type="number"
                                       name="bannercalc_settings[service_types][<?php echo $si; ?>][markup]"
                                       value="<?php echo esc_attr( $st['markup'] ); ?>"
                                       step="0.1" min="0" max="100" class="small-text" style="width:70px;" />
                            </label>
                            <label style="font-size:12px;">
                                <?php esc_html_e( 'Shipping £:', 'bannercalc' ); ?>
                                <input type="number"
                                       name="bannercalc_settings[service_types][<?php echo $si; ?>][shipping_cost]"
                                       value="<?php echo esc_attr( $st['shipping_cost'] ?? 0 ); ?>"
                                       step="0.01" min="0" class="small-text" style="width:90px;" />
                            </label>
                            <label style="font-size:12px;">
                                <?php esc_html_e( 'WC Shipping Method:', 'bannercalc' ); ?>
                                <select name="bannercalc_settings[service_types][<?php echo $si; ?>][shipping_method]" style="min-width:200px;">
                                    <option value=""><?php esc_html_e( '— None (use markup only) —', 'bannercalc' ); ?></option>
                                    <?php foreach ( $wc_shipping_methods as $method_id => $method_label ) : ?>
                                        <option value="<?php echo esc_attr( $method_id ); ?>"
                                                <?php selected( $st['shipping_method'] ?? '', $method_id ); ?>>
                                            <?php echo esc_html( $method_label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="bannercalc-section-block">
            <h2 class="bannercalc-section-heading"><?php esc_html_e( 'Professional Design Service', 'bannercalc' ); ?></h2>
            <?php
            $design_service = $settings['design_service'] ?? [
                'enabled' => true, 'label' => 'Professional Design Service', 'price' => 14.99, 'description' => '',
            ];
            ?>
            <table class="form-table bannercalc-form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Enable', 'bannercalc' ); ?></th>
                    <td>
                        <label class="bannercalc-toggle-label">
                            <input type="checkbox"
                                   name="bannercalc_settings[design_service][enabled]"
                                   value="1"
                                   <?php checked( ! empty( $design_service['enabled'] ) ); ?> />
                            <?php esc_html_e( 'Offer professional design service to customers', 'bannercalc' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Label', 'bannercalc' ); ?></th>
                    <td>
                        <input type="text"
                               name="bannercalc_settings[design_service][label]"
                               value="<?php echo esc_attr( $design_service['label'] ); ?>"
                               class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Price (£)', 'bannercalc' ); ?></th>
                    <td>
                        <input type="number"
                               name="bannercalc_settings[design_service][price]"
                               value="<?php echo esc_attr( $design_service['price'] ); ?>"
                               step="0.01" min="0" class="small-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Description', 'bannercalc' ); ?></th>
                    <td>
                        <input type="text"
                               name="bannercalc_settings[design_service][description]"
                               value="<?php echo esc_attr( $design_service['description'] ); ?>"
                               class="regular-text" />
                    </td>
                </tr>
            </table>
        </div>

        <div class="bannercalc-form-actions">
            <?php submit_button( __( 'Save Settings', 'bannercalc' ), 'bannercalc-btn-primary', 'submit', false ); ?>
        </div>
    </form>
    </div><!-- /.bannercalc-content-card -->

    <!-- Version Footer -->
    <div class="bannercalc-admin-footer">
        <span class="bannercalc-version-badge">v<?php echo esc_html( BANNERCALC_VERSION ); ?></span>
        <span class="bannercalc-footer-text"><?php esc_html_e( 'BannerCalc — Product Configurator & Pricing Engine', 'bannercalc' ); ?></span>
    </div>
</div>
