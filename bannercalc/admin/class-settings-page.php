<?php
/**
 * Admin Settings Page — registers WooCommerce submenu page.
 *
 * @package BannerCalc
 */

namespace BannerCalc\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SettingsPage {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Register the admin menu page under WooCommerce.
     */
    public function register_menu(): void {
        add_submenu_page(
            'woocommerce',
            __( 'BannerCalc Settings', 'bannercalc' ),
            __( 'BannerCalc', 'bannercalc' ),
            'manage_woocommerce',
            'bannercalc-settings',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Register settings for the options API.
     */
    public function register_settings(): void {
        register_setting( 'bannercalc_settings_group', 'bannercalc_settings', [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize_settings' ],
            'default'           => [],
        ] );
    }

    /**
     * Sanitize settings on save.
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings( array $input ): array {
        $sanitized = [];

        $sanitized['enabled']                = ! empty( $input['enabled'] );
        $sanitized['currency_symbol']        = sanitize_text_field( $input['currency_symbol'] ?? '£' );
        $sanitized['default_unit']           = sanitize_text_field( $input['default_unit'] ?? 'ft' );
        $sanitized['price_display_decimals'] = absint( $input['price_display_decimals'] ?? 2 );

        // Available units — intersect with allowed values.
        $allowed_units = [ 'mm', 'cm', 'inch', 'ft', 'm' ];
        $submitted     = $input['available_units'] ?? [];
        $sanitized['available_units'] = array_values( array_intersect( (array) $submitted, $allowed_units ) );

        if ( empty( $sanitized['available_units'] ) ) {
            $sanitized['available_units'] = [ 'ft' ];
        }

        return $sanitized;
    }

    /**
     * Render the settings page.
     */
    public function render_page(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        include BANNERCALC_PLUGIN_DIR . 'admin/views/settings-page.php';
    }
}
