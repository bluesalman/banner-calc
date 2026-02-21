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
        // Only process if BannerCalc data was submitted.
        if ( empty( $_POST['bannercalc'] ) ) { // phpcs:ignore
            return $cart_item_data;
        }

        // TODO: Phase G — validate and construct bannercalc cart data from POST.
        // Stub: pass through for now.

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

        // TODO: Phase G — format cart display data.

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

        // TODO: Phase G — render order item meta display.
    }
}
