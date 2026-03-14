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

        // Force WooCommerce shipping selection based on delivery speed.
        add_filter( 'woocommerce_package_rates', [ $this, 'filter_shipping_rates' ], 100, 2 );

        // Enforce minimum quantity on add-to-cart.
        add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_min_quantity' ], 10, 3 );

        // Set default quantity on product page.
        add_filter( 'woocommerce_quantity_input_args', [ $this, 'set_quantity_defaults' ], 10, 2 );
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

        // Sanitize service type, fulfilment mode, and design service.
        $service_type   = sanitize_text_field( $raw['service_type'] ?? 'standard' );
        $fulfilment_mode = in_array( $raw['fulfilment_mode'] ?? '', [ 'collection', 'delivery' ], true )
            ? $raw['fulfilment_mode']
            : 'delivery';
        $design_service = ! empty( $raw['design_service'] );

        // Server-side price calculation (prevents client manipulation).
        $result = $this->pricing->calculate( $config, $width_m, $height_m, $attrs, $preset, $service_type, $design_service );

        // Product price always includes the service markup (production speed surcharge).
        // WC shipping handles the actual delivery cost separately at checkout.
        $settings          = Plugin::get_settings();
        $shipping_map      = $settings['shipping_method_map'] ?? [];
        $price_for_cart    = $result['calculated_price'];

        // Build human-readable size label.
        $unit_abbr  = [ 'mm' => 'mm', 'cm' => 'cm', 'inch' => 'in', 'ft' => 'ft', 'm' => 'm' ];
        $abbr       = $unit_abbr[ $unit ] ?? $unit;
        $size_label = $width_raw . $abbr . ' × ' . $height_raw . $abbr;

        if ( $sizing_mode === 'preset' && $preset ) {
            $size_label = $preset['label'] ?? $size_label;
        }

        $cart_item_data['bannercalc'] = [
            'sizing_mode'          => $sizing_mode,
            'preset_slug'          => $preset_slug,
            'unit'                 => $unit,
            'width_raw'            => $width_raw,
            'height_raw'           => $height_raw,
            'width_m'              => $width_m,
            'height_m'             => $height_m,
            'size_label'           => $size_label,
            'area_sqft'            => $result['area_sqft'],
            'selected_attrs'       => $attrs,
            'base_price'           => $result['base_price'],
            'addons_total'         => $result['addons_total'],
            'design_service'       => $result['design_service'],
            'design_service_price' => $result['design_service_price'],
            'service_type'         => $result['service_type'],
            'service_markup_pct'   => $result['service_markup_pct'],
            'service_markup_amt'   => $result['service_markup_amt'],
            'calculated_price'     => $price_for_cart,
            'full_price_with_markup' => $result['calculated_price'],
            'shipping_method'      => $shipping_map[ $service_type ] ?? '',
            'fulfilment_mode'      => $fulfilment_mode,
        ];

        // Invalidate WC shipping rate cache so collection/delivery takes effect immediately.
        WC()->session->set( 'shipping_for_package_0', false );

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

        // Design service.
        if ( ! empty( $bc['design_service'] ) ) {
            $item_data[] = [
                'key'   => __( 'Design Service', 'bannercalc' ),
                'value' => __( 'Professional Design', 'bannercalc' ) . ' (+£' . number_format( (float) ( $bc['design_service_price'] ?? 0 ), 2 ) . ')',
            ];
        }

        // Fulfilment mode.
        if ( ! empty( $bc['fulfilment_mode'] ) ) {
            $item_data[] = [
                'key'   => __( 'Fulfilment', 'bannercalc' ),
                'value' => $bc['fulfilment_mode'] === 'collection'
                    ? __( 'Collection (Free)', 'bannercalc' )
                    : __( 'Delivery', 'bannercalc' ),
            ];
        }

        // Service type.
        if ( ! empty( $bc['service_type'] ) && $bc['service_type'] !== 'standard' ) {
            $settings      = \BannerCalc\Plugin::get_settings();
            $service_types = $settings['service_types'] ?? [];
            $st_label      = $bc['service_type'];
            foreach ( $service_types as $st ) {
                if ( ( $st['slug'] ?? '' ) === $bc['service_type'] ) {
                    $st_label = $st['label'] . ' (+' . (int) $st['markup'] . '%)';
                    break;
                }
            }
            $item_data[] = [
                'key'   => __( 'Delivery Speed', 'bannercalc' ),
                'value' => $st_label,
            ];
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

            if ( ! empty( $bc['fulfilment_mode'] ) ) {
                echo esc_html__( 'Fulfilment: ', 'bannercalc' ) . esc_html( ucfirst( $bc['fulfilment_mode'] ) ) . "\n";
            }
            if ( ! empty( $bc['design_service'] ) ) {
                echo esc_html__( 'Design Service: Professional Design (+£', 'bannercalc' ) . number_format( (float) ( $bc['design_service_price'] ?? 0 ), 2 ) . ")\n";
            }
            if ( ! empty( $bc['service_type'] ) && $bc['service_type'] !== 'standard' ) {
                echo esc_html__( 'Delivery Speed: ', 'bannercalc' ) . esc_html( $bc['service_type'] ) . " (+" . (int) ( $bc['service_markup_pct'] ?? 0 ) . "%)\n";
            }
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

            if ( ! empty( $bc['fulfilment_mode'] ) ) {
                echo esc_html__( 'Fulfilment: ', 'bannercalc' ) . esc_html( ucfirst( $bc['fulfilment_mode'] ) ) . '<br/>';
            }
            if ( ! empty( $bc['design_service'] ) ) {
                echo esc_html__( 'Design Service: Professional Design (+£', 'bannercalc' ) . number_format( (float) ( $bc['design_service_price'] ?? 0 ), 2 ) . ')<br/>';
            }
            if ( ! empty( $bc['service_type'] ) && $bc['service_type'] !== 'standard' ) {
                $settings      = \BannerCalc\Plugin::get_settings();
                $service_types = $settings['service_types'] ?? [];
                $st_display    = $bc['service_type'];
                foreach ( $service_types as $st ) {
                    if ( ( $st['slug'] ?? '' ) === $bc['service_type'] ) {
                        $st_display = $st['label'] . ' (+' . (int) $st['markup'] . '%)';
                        break;
                    }
                }
                echo esc_html__( 'Delivery Speed: ', 'bannercalc' ) . esc_html( $st_display ) . '<br/>';
            }

            echo '</div>';
        }
    }

    /**
     * Filter WooCommerce shipping rates based on selected delivery speed.
     *
     * If any BannerCalc cart item has a mapped shipping_method, only show that
     * method (and hide the others) so the customer gets the correct shipping rate.
     *
     * @param array $rates  Available shipping rates.
     * @param array $package Shipping package.
     * @return array
     */
    public function filter_shipping_rates( array $rates, array $package ): array {
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $rates;
        }

        $cart = WC()->cart;
        if ( ! $cart ) {
            return $rates;
        }

        // Find the most urgent (highest priority) shipping method requested by BannerCalc items.
        $settings      = Plugin::get_settings();
        $service_types = $settings['service_types'] ?? [];
        $shipping_map  = $settings['shipping_method_map'] ?? [];

        $required_method = '';
        $highest_urgency = -1;

        foreach ( $cart->get_cart() as $cart_item ) {
            if ( empty( $cart_item['bannercalc']['service_type'] ) ) {
                continue;
            }

            $service_slug   = $cart_item['bannercalc']['service_type'];
            $mapped_method  = $shipping_map[ $service_slug ] ?? '';

            if ( empty( $mapped_method ) ) {
                continue;
            }

            // Urgency = index in service_types (higher index = more urgent).
            $urgency = 0;
            foreach ( $service_types as $idx => $st ) {
                if ( ( $st['slug'] ?? '' ) === $service_slug ) {
                    $urgency = $idx;
                    break;
                }
            }

            if ( $urgency > $highest_urgency ) {
                $highest_urgency = $urgency;
                $required_method = $mapped_method;
            }
        }

        // Check if any BannerCalc item is set to Collection fulfilment.
        $is_collection = false;
        foreach ( $cart->get_cart() as $cart_item ) {
            if ( ! empty( $cart_item['bannercalc']['fulfilment_mode'] ) && $cart_item['bannercalc']['fulfilment_mode'] === 'collection' ) {
                $is_collection = true;
                break;
            }
        }

        // Collection: zero out all shipping rates (WC block-based Local Pickup is separate).
        if ( $is_collection ) {
            if ( ! empty( $rates ) ) {
                $first_key  = array_key_first( $rates );
                $first_rate = $rates[ $first_key ];
                $first_rate->set_cost( 0 );
                $first_rate->set_label( __( 'Collection (Free)', 'bannercalc' ) );
                return [ $first_key => $first_rate ];
            }
        }

        // If we have a required shipping method, filter rates to only show it.
        // Override the WC rate cost with the shipping_cost from our service type settings
        // so urgency pricing is controlled in one place (BannerCalc settings, not WC).
        if ( ! empty( $required_method ) && isset( $rates[ $required_method ] ) ) {
            $rate = $rates[ $required_method ];

            // Find the matching service type and apply our shipping_cost value.
            foreach ( $service_types as $st ) {
                $st_method = $shipping_map[ $st['slug'] ?? '' ] ?? '';
                if ( $st_method === $required_method ) {
                    $rate->set_cost( (float) ( $st['shipping_cost'] ?? $rate->get_cost() ) );
                    break;
                }
            }

            return [ $required_method => $rate ];
        }

        return $rates;
    }

    /**
     * Validate minimum quantity on add-to-cart.
     *
     * @param bool $passed  Whether validation passed so far.
     * @param int  $product_id
     * @param int  $quantity
     * @return bool
     */
    public function validate_min_quantity( bool $passed, int $product_id, int $quantity ): bool {
        $plugin = Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product_id ) ) {
            return $passed;
        }

        $config       = $plugin->get_product_config( $product_id );
        $min_quantity = (int) ( $config['min_quantity'] ?? 0 );

        if ( $min_quantity > 0 && $quantity < $min_quantity ) {
            wc_add_notice(
                sprintf(
                    __( 'The minimum order quantity for this product is %d.', 'bannercalc' ),
                    $min_quantity
                ),
                'error'
            );
            return false;
        }

        return $passed;
    }

    /**
     * Set default / minimum quantity for BannerCalc products on the product page.
     *
     * @param array       $args    Quantity input arguments.
     * @param \WC_Product $product
     * @return array
     */
    public function set_quantity_defaults( array $args, \WC_Product $product ): array {
        $plugin = Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return $args;
        }

        $config       = $plugin->get_product_config( $product->get_id() );
        $min_quantity = max( 1, (int) ( $config['min_quantity'] ?? 0 ) );
        $default_qty  = max( $min_quantity, (int) ( $config['default_quantity'] ?? 1 ) );

        $args['min_value']   = $min_quantity;
        $args['input_value'] = $default_qty;

        return $args;
    }
}
