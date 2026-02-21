<?php
/**
 * Cart Handler — WooCommerce cart, checkout, and order integration.
 *
 * @package BannerCalc
 */

namespace BannerCalc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CartHandler {

    /**
     * @var PricingEngine
     */
    private PricingEngine $pricing;

    public function __construct( PricingEngine $pricing ) {
        $this->pricing = $pricing;
        $this->register_hooks();
    }

    /**
     * Register WooCommerce hooks.
     */
    private function register_hooks(): void {
        // Inject BannerCalc data into cart item.
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_item_data' ], 10, 3 );

        // Override product price in cart.
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'set_cart_item_prices' ], 20 );

        // Display config summary in cart.
        add_filter( 'woocommerce_get_item_data', [ $this, 'display_cart_item_data' ], 10, 2 );

        // Persist data to order item meta.
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'save_order_item_meta' ], 10, 4 );

        // Display config in order emails and order detail.
        add_action( 'woocommerce_order_item_meta_end', [ $this, 'display_order_item_meta' ], 10, 4 );
    }

    /**
     * Add BannerCalc data to cart item.
     *
     * @param array $cart_item_data
     * @param int   $product_id
     * @param int   $variation_id
     * @return array
     */
    public function add_cart_item_data( array $cart_item_data, int $product_id, int $variation_id ): array {
        if ( empty( $_POST['bannercalc'] ) ) { // phpcs:ignore
            return $cart_item_data;
        }

        $raw    = $_POST['bannercalc']; // phpcs:ignore
        $plugin = Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product_id ) ) {
            return $cart_item_data;
        }

        $config = $plugin->get_product_config( $product_id );

        // Sanitize submitted data.
        $sizing_mode = sanitize_text_field( $raw['sizing_mode'] ?? 'custom' );
        $preset_slug = sanitize_text_field( $raw['preset_slug'] ?? '' );
        $unit        = sanitize_text_field( $raw['unit'] ?? 'ft' );
        $width_raw   = (float) ( $raw['width_raw'] ?? 0 );
        $height_raw  = (float) ( $raw['height_raw'] ?? 0 );

        // Sanitize selected attributes.
        $attrs = [];
        if ( ! empty( $raw['attributes'] ) && is_array( $raw['attributes'] ) ) {
            foreach ( $raw['attributes'] as $tax => $slug ) {
                $attrs[ sanitize_text_field( $tax ) ] = sanitize_text_field( $slug );
            }
        }

        // Convert raw dimensions to metres for server-side pricing.
        $factor    = UnitConverter::TO_METRES[ $unit ] ?? 1.0;
        $width_m   = $width_raw * $factor;
        $height_m  = $height_raw * $factor;

        // Match preset if applicable.
        $preset = null;
        if ( $sizing_mode === 'preset' && $preset_slug ) {
            $presets = $config['preset_sizes'] ?? [];
            foreach ( $presets as $p ) {
                if ( ( $p['slug'] ?? '' ) === $preset_slug ) {
                    $preset   = $p;
                    $width_m  = (float) ( $p['width_m'] ?? $width_m );
                    $height_m = (float) ( $p['height_m'] ?? $height_m );
                    break;
                }
            }
        }

        // Server-side price calculation (prevents client manipulation).
        $result = $this->pricing->calculate( $config, $width_m, $height_m, $attrs, $preset );

        // Build human-readable size label.
        $unit_abbr  = [ 'mm' => 'mm', 'cm' => 'cm', 'inch' => 'in', 'ft' => 'ft', 'm' => 'm' ];
        $abbr       = $unit_abbr[ $unit ] ?? $unit;
        $size_label = $width_raw . $abbr . ' × ' . $height_raw . $abbr;

        if ( $sizing_mode === 'preset' && $preset ) {
            $size_label = $preset['label'] ?? $size_label;
        }

        $cart_item_data['bannercalc'] = [
            'sizing_mode'      => $sizing_mode,
            'preset_slug'      => $preset_slug,
            'unit'             => $unit,
            'width_raw'        => $width_raw,
            'height_raw'       => $height_raw,
            'width_m'          => $width_m,
            'height_m'         => $height_m,
            'size_label'       => $size_label,
            'area_sqft'        => $result['area_sqft'],
            'selected_attrs'   => $attrs,
            'base_price'       => $result['base_price'],
            'addons_total'     => $result['addons_total'],
            'calculated_price' => $result['calculated_price'],
        ];

        return $cart_item_data;
    }

    /**
     * Override product price with calculated price.
     *
     * @param \WC_Cart $cart
     */
    public function set_cart_item_prices( \WC_Cart $cart ): void {
        if ( is_admin() && ! wp_doing_ajax() ) {
            return;
        }

        foreach ( $cart->get_cart() as $cart_item ) {
            if ( ! empty( $cart_item['bannercalc']['calculated_price'] ) ) {
                $cart_item['data']->set_price( $cart_item['bannercalc']['calculated_price'] );
            }
        }
    }

    /**
     * Display BannerCalc config summary in cart / checkout.
     *
     * @param array $item_data
     * @param array $cart_item
     * @return array
     */
    public function display_cart_item_data( array $item_data, array $cart_item ): array {
        if ( empty( $cart_item['bannercalc'] ) ) {
            return $item_data;
        }

        $bc = $cart_item['bannercalc'];

        $item_data[] = [
            'key'   => __( 'Size', 'bannercalc' ),
            'value' => $bc['size_label'] ?? '',
        ];

        $item_data[] = [
            'key'   => __( 'Area', 'bannercalc' ),
            'value' => ( $bc['area_sqft'] ?? 0 ) . ' sqft',
        ];

        if ( ! empty( $bc['selected_attrs'] ) ) {
            foreach ( $bc['selected_attrs'] as $tax => $slug ) {
                $label = wc_attribute_label( $tax );
                $term  = get_term_by( 'slug', $slug, $tax );
                $value = $term ? $term->name : $slug;
                $item_data[] = [
                    'key'   => $label,
                    'value' => $value,
                ];
            }
        }

        return $item_data;
    }

    /**
     * Persist BannerCalc data to order item meta.
     *
     * @param \WC_Order_Item_Product $item
     * @param string                 $cart_item_key
     * @param array                  $values
     * @param \WC_Order              $order
     */
    public function save_order_item_meta( \WC_Order_Item_Product $item, string $cart_item_key, array $values, \WC_Order $order ): void {
        if ( ! empty( $values['bannercalc'] ) ) {
            $item->add_meta_data( '_bannercalc_data', $values['bannercalc'], true );
        }
    }

    /**
     * Display BannerCalc data on order detail / emails.
     *
     * @param int            $item_id
     * @param \WC_Order_Item $item
     * @param \WC_Order      $order
     * @param bool           $plain_text
     */
    public function display_order_item_meta( int $item_id, \WC_Order_Item $item, \WC_Order $order, bool $plain_text ): void {
        $data = $item->get_meta( '_bannercalc_data' );

        if ( empty( $data ) ) {
            return;
        }

        $bc = $data;

        if ( $plain_text ) {
            echo "\n" . esc_html__( 'BannerCalc:', 'bannercalc' ) . "\n";
            echo esc_html__( 'Size: ', 'bannercalc' ) . esc_html( $bc['size_label'] ?? '' ) . "\n";
            echo esc_html__( 'Area: ', 'bannercalc' ) . esc_html( $bc['area_sqft'] ?? 0 ) . " sqft\n";
        } else {
            echo '<div class="bannercalc-order-meta" style="margin-top:8px;font-size:12px;color:#555;">';
            echo '<strong>' . esc_html__( 'BannerCalc:', 'bannercalc' ) . '</strong><br/>';
            echo esc_html__( 'Size: ', 'bannercalc' ) . esc_html( $bc['size_label'] ?? '' ) . '<br/>';
            echo esc_html__( 'Area: ', 'bannercalc' ) . esc_html( $bc['area_sqft'] ?? 0 ) . ' sqft<br/>';

            if ( ! empty( $bc['selected_attrs'] ) ) {
                foreach ( $bc['selected_attrs'] as $tax => $slug ) {
                    $label = wc_attribute_label( $tax );
                    $term  = get_term_by( 'slug', $slug, $tax );
                    $value = $term ? $term->name : $slug;
                    echo esc_html( $label ) . ': ' . esc_html( $value ) . '<br/>';
                }
            }

            echo '</div>';
        }
    }
}
