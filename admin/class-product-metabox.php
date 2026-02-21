<?php
/**
 * Product Metabox — per-product BannerCalc configuration on product edit screen.
 *
 * @package BannerCalc
 */

namespace BannerCalc\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ProductMetabox {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'register_metabox' ] );
        add_action( 'save_post_product', [ $this, 'save_metabox' ] );
    }

    /**
     * Register the BannerCalc metabox on the product edit screen.
     */
    public function register_metabox(): void {
        add_meta_box(
            'bannercalc-product-config',
            __( 'BannerCalc Configuration', 'bannercalc' ),
            [ $this, 'render_metabox' ],
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Render the metabox content.
     *
     * @param \WP_Post $post
     */
    public function render_metabox( \WP_Post $post ): void {
        wp_nonce_field( 'bannercalc_product_metabox', 'bannercalc_product_nonce' );

        $product_config = get_post_meta( $post->ID, '_bannercalc_product_config', true );

        if ( ! is_array( $product_config ) ) {
            $product_config = [];
        }

        // Get resolved config to show inherited values.
        $plugin          = \BannerCalc\Plugin::instance();
        $resolved_config = $plugin->get_product_config( $post->ID );

        include BANNERCALC_PLUGIN_DIR . 'admin/views/product-metabox.php';
    }

    /**
     * Save metabox data.
     *
     * @param int $post_id
     */
    public function save_metabox( int $post_id ): void {
        // Verify nonce.
        if ( ! isset( $_POST['bannercalc_product_nonce'] ) ||
             ! wp_verify_nonce( $_POST['bannercalc_product_nonce'], 'bannercalc_product_metabox' ) ) { // phpcs:ignore
            return;
        }

        // Check autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( empty( $_POST['bannercalc_product'] ) ) { // phpcs:ignore
            delete_post_meta( $post_id, '_bannercalc_product_config' );
            return;
        }

        // TODO: Phase D — sanitize and save product override config.
        $raw    = $_POST['bannercalc_product']; // phpcs:ignore
        $config = $this->sanitize_product_config( $raw );

        update_post_meta( $post_id, '_bannercalc_product_config', $config );
    }

    /**
     * Sanitize product config override.
     *
     * @param array $raw
     * @return array
     */
    private function sanitize_product_config( array $raw ): array {
        $config = [];

        $config['override_enabled'] = ! empty( $raw['override_enabled'] );

        if ( ! $config['override_enabled'] ) {
            return $config;
        }

        // Only store fields that are being overridden.
        if ( isset( $raw['sizing_mode'] ) ) {
            $config['sizing_mode'] = sanitize_text_field( $raw['sizing_mode'] );
        }
        if ( isset( $raw['area_rate_sqft'] ) ) {
            $config['area_rate_sqft'] = (float) $raw['area_rate_sqft'];
        }
        if ( ! empty( $raw['disabled_attributes'] ) ) {
            $config['disabled_attributes'] = array_map( 'sanitize_text_field', (array) $raw['disabled_attributes'] );
        }
        if ( ! empty( $raw['attribute_pricing_overrides'] ) ) {
            $config['attribute_pricing_overrides'] = $raw['attribute_pricing_overrides']; // TODO: deep sanitize.
        }
        if ( isset( $raw['preset_sizes'] ) ) {
            $config['preset_sizes'] = $raw['preset_sizes']; // TODO: deep sanitize.
        }

        return $config;
    }
}
