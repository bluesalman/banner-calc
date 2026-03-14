<?php
/**
 * Admin Settings Page — registers top-level menu, submenu pages, and global settings.
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
     * Get the SVG icon for the admin menu (banner/flag shape).
     *
     * @return string Base64-encoded SVG data URI.
     */
    private function get_menu_icon(): string {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">'
             . '<rect x="2" y="2" width="3" height="16" rx="1" fill="currentColor" opacity="0.9"/>'
             . '<path d="M5 3h11.5c.83 0 1.1 1.08.4 1.6L13 7.5l3.9 2.9c.7.52.43 1.6-.4 1.6H5V3z" fill="currentColor"/>'
             . '</svg>';

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        return 'data:image/svg+xml;base64,' . base64_encode( $svg );
    }

    /**
     * Register the top-level admin menu and submenu pages.
     */
    public function register_menu(): void {
        // Top-level menu.
        add_menu_page(
            __( 'BannerCalc', 'bannercalc' ),
            __( 'BannerCalc', 'bannercalc' ),
            'manage_woocommerce',
            'bannercalc',
            [ $this, 'render_dashboard' ],
            $this->get_menu_icon(),
            56 // After WooCommerce.
        );

        // Submenu: Dashboard (replaces auto-generated first submenu).
        add_submenu_page(
            'bannercalc',
            __( 'Dashboard — BannerCalc', 'bannercalc' ),
            __( 'Dashboard', 'bannercalc' ),
            'manage_woocommerce',
            'bannercalc',
            [ $this, 'render_dashboard' ]
        );

        // Submenu: Settings.
        add_submenu_page(
            'bannercalc',
            __( 'Settings — BannerCalc', 'bannercalc' ),
            __( 'Settings', 'bannercalc' ),
            'manage_woocommerce',
            'bannercalc-settings',
            [ $this, 'render_settings' ]
        );

        // Submenu: Category Defaults.
        add_submenu_page(
            'bannercalc',
            __( 'Category Defaults — BannerCalc', 'bannercalc' ),
            __( 'Category Defaults', 'bannercalc' ),
            'manage_woocommerce',
            'bannercalc-categories',
            [ $this, 'render_categories' ]
        );

        // Submenu: Help & Documentation.
        add_submenu_page(
            'bannercalc',
            __( 'Help — BannerCalc', 'bannercalc' ),
            __( 'Help', 'bannercalc' ),
            'manage_woocommerce',
            'bannercalc-help',
            [ $this, 'render_help' ]
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

        // Service types.
        $service_types = [];
        $raw_st = $input['service_types'] ?? [];
        if ( is_array( $raw_st ) ) {
            foreach ( $raw_st as $st ) {
                $label = sanitize_text_field( $st['label'] ?? '' );
                $slug  = sanitize_text_field( $st['slug'] ?? '' );
                // Auto-generate slug from label if empty.
                if ( empty( $slug ) && ! empty( $label ) ) {
                    $slug = sanitize_title( $label );
                }
                if ( empty( $slug ) || empty( $label ) ) {
                    continue; // Skip rows with no label.
                }
                $service_types[] = [
                    'slug'    => $slug,
                    'label'   => $label,
                    'markup'  => max( 0, (float) ( $st['markup'] ?? 0 ) ),
                    'default' => ! empty( $st['default'] ),
                ];
            }
        }
        if ( ! empty( $service_types ) ) {
            // Apply default radio selection.
            $default_radio = sanitize_text_field( $input['service_types_default'] ?? 'standard' );
            foreach ( $service_types as &$st_item ) {
                $st_item['default'] = ( $st_item['slug'] === $default_radio );
            }
            unset( $st_item );
            $sanitized['service_types'] = $service_types;
        }

        // Shipping: one WC method + manual cost (applies to all delivery service types).
        $sanitized['shipping_method'] = sanitize_text_field( $input['shipping_method'] ?? '' );
        $sanitized['shipping_cost']   = max( 0, (float) ( $input['shipping_cost'] ?? 0 ) );

        // Collection / local pickup toggle.
        $sanitized['collection_enabled'] = ! empty( $input['collection_enabled'] );

        // Design service.
        $sanitized['design_service'] = [
            'enabled'     => ! empty( $input['design_service']['enabled'] ),
            'label'       => sanitize_text_field( $input['design_service']['label'] ?? 'Professional Design Service' ),
            'price'       => max( 0, (float) ( $input['design_service']['price'] ?? 14.99 ) ),
            'description' => sanitize_text_field( $input['design_service']['description'] ?? '' ),
        ];

        return $sanitized;
    }

    /**
     * Render the Dashboard page.
     */
    public function render_dashboard(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        include BANNERCALC_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render the Settings page.
     */
    public function render_settings(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        include BANNERCALC_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Render the Category Defaults page.
     */
    public function render_categories(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        include BANNERCALC_PLUGIN_DIR . 'admin/views/categories-page.php';
    }

    /**
     * Render the Help & Documentation page.
     */
    public function render_help(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        include BANNERCALC_PLUGIN_DIR . 'admin/views/help-page.php';
    }
}
