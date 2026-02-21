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
