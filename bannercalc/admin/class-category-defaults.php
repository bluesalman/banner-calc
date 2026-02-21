<?php
/**
 * Category Defaults — manages BannerCalc config on product categories.
 *
 * @package BannerCalc
 */

namespace BannerCalc\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CategoryDefaults {

    public function __construct() {
        // Add fields to product category edit screen.
        add_action( 'product_cat_edit_form_fields', [ $this, 'render_category_fields' ], 20 );
        add_action( 'edited_product_cat', [ $this, 'save_category_fields' ] );

        // AJAX for saving category config from settings page.
        add_action( 'wp_ajax_bannercalc_save_category_config', [ $this, 'ajax_save_category_config' ] );
    }

    /**
     * Render BannerCalc fields on the category edit screen.
     *
     * @param \WP_Term $term
     */
    public function render_category_fields( \WP_Term $term ): void {
        $config = get_term_meta( $term->term_id, '_bannercalc_config', true );

        if ( ! is_array( $config ) ) {
            $config = [];
        }

        include BANNERCALC_PLUGIN_DIR . 'admin/views/category-defaults.php';
    }

    /**
     * Save BannerCalc category config.
     *
     * @param int $term_id
     */
    public function save_category_fields( int $term_id ): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        if ( empty( $_POST['bannercalc_category'] ) ) { // phpcs:ignore
            return;
        }

        // TODO: Phase C — sanitize and save category config.
        $raw_config = $_POST['bannercalc_category']; // phpcs:ignore
        $config     = $this->sanitize_category_config( $raw_config );

        update_term_meta( $term_id, '_bannercalc_config', $config );
    }

    /**
     * AJAX: Save category config from the unified settings page.
     */
    public function ajax_save_category_config(): void {
        check_ajax_referer( 'bannercalc_admin', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ] );
        }

        // TODO: Phase C — implement AJAX save.
        wp_send_json_success();
    }

    /**
     * Sanitize category config array.
     *
     * @param array $raw
     * @return array
     */
    private function sanitize_category_config( array $raw ): array {
        $config = [];

        $config['enabled']      = ! empty( $raw['enabled'] );
        $config['sizing_mode']  = sanitize_text_field( $raw['sizing_mode'] ?? 'preset_and_custom' );
        $config['default_unit'] = sanitize_text_field( $raw['default_unit'] ?? 'ft' );

        $config['area_rate_sqft'] = (float) ( $raw['area_rate_sqft'] ?? 0 );
        $config['area_rate_sqm']  = (float) ( $raw['area_rate_sqm'] ?? 0 );
        $config['min_width_m']    = (float) ( $raw['min_width_m'] ?? 0 );
        $config['min_height_m']   = (float) ( $raw['min_height_m'] ?? 0 );
        $config['max_width_m']    = (float) ( $raw['max_width_m'] ?? 0 );
        $config['max_height_m']   = (float) ( $raw['max_height_m'] ?? 0 );
        $config['minimum_charge'] = (float) ( $raw['minimum_charge'] ?? 0 );

        $allowed_units = [ 'mm', 'cm', 'inch', 'ft', 'm' ];
        $config['available_units'] = array_values(
            array_intersect( (array) ( $raw['available_units'] ?? [] ), $allowed_units )
        );

        $config['enabled_attributes']  = array_map( 'sanitize_text_field', (array) ( $raw['enabled_attributes'] ?? [] ) );
        $config['attribute_pricing']   = $raw['attribute_pricing'] ?? [];  // TODO: deep sanitize in Phase C.
        $config['preset_sizes']        = $raw['preset_sizes'] ?? [];      // TODO: deep sanitize in Phase C.

        return $config;
    }
}
