<?php
/**
 * Settings page HTML template.
 *
 * @package BannerCalc
 * @var array $this — SettingsPage instance (unused, template loaded via include).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = \BannerCalc\Plugin::get_settings();
$all_units = \BannerCalc\UnitConverter::get_unit_labels();
?>
<div class="wrap bannercalc-settings">
    <div class="bannercalc-cmyk-bar" style="margin-bottom: 20px;"><span></span><span></span><span></span><span></span></div>
    <div class="bannercalc-admin-overline"><?php esc_html_e( 'CONFIGURATION', 'bannercalc' ); ?></div>
    <div class="bannercalc-admin-header">
        <h1><?php esc_html_e( 'BannerCalc Settings', 'bannercalc' ); ?></h1>
    </div>

    <?php settings_errors( 'bannercalc_settings_group' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'bannercalc_settings_group' ); ?>

        <h2><?php esc_html_e( 'General Settings', 'bannercalc' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="bannercalc_enabled"><?php esc_html_e( 'Enable BannerCalc', 'bannercalc' ); ?></label>
                </th>
                <td>
                    <label>
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
                    <select id="bannercalc_default_unit" name="bannercalc_settings[default_unit]">
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
                        <label style="display: block; margin-bottom: 4px;">
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

        <hr />

        <h2><?php esc_html_e( 'Category Configuration', 'bannercalc' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Configure BannerCalc pricing and attributes per product category. Edit each product category to set its BannerCalc defaults.', 'bannercalc' ); ?>
        </p>

        <?php
        // List categories with BannerCalc config.
        $categories = get_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ] );

        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) :
        ?>
            <table class="widefat striped" style="max-width: 600px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Category', 'bannercalc' ); ?></th>
                        <th><?php esc_html_e( 'BannerCalc Enabled', 'bannercalc' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'bannercalc' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $categories as $cat ) :
                        $cat_config = get_term_meta( $cat->term_id, '_bannercalc_config', true );
                        $is_enabled = ! empty( $cat_config['enabled'] );
                        $edit_url   = get_edit_term_link( $cat->term_id, 'product_cat' );
                    ?>
                        <tr>
                            <td><?php echo esc_html( $cat->name ); ?></td>
                            <td>
                                <?php if ( $is_enabled ) : ?>
                                    <span style="color: #39B54A; font-weight: 600;">&#10003; <?php esc_html_e( 'Yes', 'bannercalc' ); ?></span>
                                <?php else : ?>
                                    <span style="color: #8892A0;">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( $edit_url ); ?>">
                                    <?php esc_html_e( 'Configure', 'bannercalc' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php submit_button(); ?>
    </form>
</div>
