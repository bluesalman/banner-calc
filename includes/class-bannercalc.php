<?php
/**
 * Core Plugin class — singleton that wires all hooks.
 *
 * @package BannerCalc
 */

namespace BannerCalc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {

    /**
     * Singleton instance.
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Admin coordinator.
     *
     * @var Admin|null
     */
    public ?Admin $admin = null;

    /**
     * Frontend coordinator.
     *
     * @var Frontend|null
     */
    public ?Frontend $frontend = null;

    /**
     * Pricing engine.
     *
     * @var PricingEngine
     */
    public PricingEngine $pricing;

    /**
     * Cart handler.
     *
     * @var CartHandler
     */
    public CartHandler $cart;

    /**
     * Unit converter.
     *
     * @var UnitConverter
     */
    public UnitConverter $units;

    /**
     * Attribute manager.
     *
     * @var AttributeManager
     */
    public AttributeManager $attributes;

    /**
     * Get singleton instance.
     */
    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor — private, use instance().
     */
    private function __construct() {
        $this->units      = new UnitConverter();
        $this->attributes = new AttributeManager();
        $this->pricing    = new PricingEngine( $this->units );
        $this->cart       = new CartHandler( $this->pricing );

        if ( is_admin() ) {
            $this->admin = new Admin();
        }

        if ( ! is_admin() || wp_doing_ajax() ) {
            $this->frontend = new Frontend();
        }

        $this->register_hooks();

        // Preset seeder AJAX (admin only).
        PresetSeeder::register();
    }

    /**
     * Register global hooks.
     */
    private function register_hooks(): void {
        // AJAX endpoints.
        add_action( 'wp_ajax_bannercalc_get_config', [ $this, 'ajax_get_config' ] );
        add_action( 'wp_ajax_nopriv_bannercalc_get_config', [ $this, 'ajax_get_config' ] );
    }

    /**
     * AJAX: Return resolved product config as JSON.
     */
    public function ajax_get_config(): void {
        $product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;

        if ( ! $product_id ) {
            wp_send_json_error( [ 'message' => 'Missing product_id' ] );
        }

        $config = $this->get_product_config( $product_id );
        wp_send_json_success( $config );
    }

    /**
     * Get the resolved (merged) config for a product.
     *
     * Merges category defaults with per-product overrides.
     *
     * @param int $product_id
     * @return array
     */
    public function get_product_config( int $product_id ): array {
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return [];
        }

        // Get config from the top-level parent category.
        // Walk up the hierarchy so child categories inherit from their parent.
        $category_ids = $product->get_category_ids();
        $cat_config   = [];

        foreach ( $category_ids as $cat_id ) {
            // Find the top-level parent for this category.
            $root_id   = $cat_id;
            $ancestors = get_ancestors( $cat_id, 'product_cat', 'taxonomy' );
            if ( ! empty( $ancestors ) ) {
                $root_id = end( $ancestors ); // last element is the top-level parent
            }

            $config = get_term_meta( $root_id, '_bannercalc_config', true );
            if ( ! empty( $config ) && ! empty( $config['enabled'] ) ) {
                $cat_config = $config;
                break;
            }
        }

        // Get per-product override.
        $product_override = get_post_meta( $product_id, '_bannercalc_product_config', true );

        if ( empty( $product_override ) || empty( $product_override['override_enabled'] ) ) {
            return $cat_config;
        }

        // Merge: product overrides win.
        return $this->merge_config( $cat_config, $product_override );
    }

    /**
     * Check if BannerCalc is enabled for a given product.
     *
     * @param int $product_id
     * @return bool
     */
    public function is_enabled_for_product( int $product_id ): bool {
        $config = $this->get_product_config( $product_id );
        return ! empty( $config ) && ! empty( $config['enabled'] );
    }

    /**
     * Get plugin global settings.
     *
     * @return array
     */
    public static function get_settings(): array {
        $defaults = [
            'enabled'                => true,
            'currency_symbol'        => '£',
            'default_unit'           => 'ft',
            'available_units'        => [ 'mm', 'cm', 'inch', 'ft', 'm' ],
            'price_display_decimals' => 2,
            'service_types'          => [
                [
                    'slug'            => 'standard',
                    'label'           => 'Standard Delivery',
                    'markup'          => 0,
                    'default'         => true,
                    'shipping_method' => '',
                ],
                [
                    'slug'            => 'urgent-48',
                    'label'           => 'Urgent — 48 Hours',
                    'markup'          => 15,
                    'default'         => false,
                    'shipping_method' => '',
                ],
                [
                    'slug'            => 'urgent-24',
                    'label'           => 'Urgent — 24 Hours',
                    'markup'          => 25,
                    'default'         => false,
                    'shipping_method' => '',
                ],
            ],
            'design_service'         => [
                'enabled'     => true,
                'label'       => 'Professional Design Service',
                'price'       => 14.99,
                'description' => 'Our team will create a professional design for your banner',
            ],
            'shipping_method_map'    => [],
            'collection_enabled'     => false,
        ];

        $settings = get_option( 'bannercalc_settings', [] );

        return wp_parse_args( $settings, $defaults );
    }

    /**
     * Deep merge category config with product overrides.
     *
     * @param array $base     Category defaults.
     * @param array $override Product overrides.
     * @return array
     */
    private function merge_config( array $base, array $override ): array {
        $merged = $base;

        // Simple scalar overrides.
        $scalar_keys = [
            'sizing_mode',
            'default_unit',
            'area_rate_sqft',
            'area_rate_sqm',
            'min_width_m',
            'min_height_m',
            'max_width_m',
            'max_height_m',
            'minimum_charge',
            'quantity_mode',
            'min_quantity',
            'default_quantity',
        ];

        foreach ( $scalar_keys as $key ) {
            if ( isset( $override[ $key ] ) ) {
                $merged[ $key ] = $override[ $key ];
            }
        }

        // Handle available units override.
        if ( isset( $override['available_units'] ) ) {
            $merged['available_units'] = $override['available_units'];
        }

        // Handle disabled attributes.
        if ( ! empty( $override['disabled_attributes'] ) && ! empty( $merged['enabled_attributes'] ) ) {
            $merged['enabled_attributes'] = array_diff(
                $merged['enabled_attributes'],
                $override['disabled_attributes']
            );
        }

        // Handle attribute pricing overrides.
        if ( ! empty( $override['attribute_pricing_overrides'] ) && ! empty( $merged['attribute_pricing'] ) ) {
            foreach ( $override['attribute_pricing_overrides'] as $attr => $attr_override ) {
                if ( isset( $merged['attribute_pricing'][ $attr ] ) ) {
                    if ( isset( $attr_override['values'] ) ) {
                        $merged['attribute_pricing'][ $attr ]['values'] = array_merge(
                            $merged['attribute_pricing'][ $attr ]['values'],
                            $attr_override['values']
                        );
                    }
                    if ( isset( $attr_override['pricing_type'] ) ) {
                        $merged['attribute_pricing'][ $attr ]['pricing_type'] = $attr_override['pricing_type'];
                    }
                    if ( isset( $attr_override['default_value'] ) ) {
                        $merged['attribute_pricing'][ $attr ]['default_value'] = $attr_override['default_value'];
                    }
                    if ( isset( $attr_override['required'] ) ) {
                        $merged['attribute_pricing'][ $attr ]['required'] = $attr_override['required'];
                    }
                }
            }
        }

        // Handle preset sizes override.
        if ( isset( $override['preset_sizes'] ) ) {
            $merged['preset_sizes'] = $override['preset_sizes'];
        }

        // Handle quantity bundles override.
        if ( isset( $override['quantity_bundles'] ) ) {
            $merged['quantity_bundles'] = $override['quantity_bundles'];
        }

        return $merged;
    }
}
