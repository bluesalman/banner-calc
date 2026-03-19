<?php
/**
 * Google Product Feed — generates a supplemental product feed for GMC.
 *
 * Outputs each BannerCalc preset size as a separate product entry in the
 * Google Products XML format, with item_group_id grouping. This enables
 * Google Merchant Center to treat each size as a product variant.
 *
 * Feed URL: ?feed=bannercalc-google
 * Or with pretty permalink: /feed/bannercalc-google/
 *
 * Also hooks into "Google for WooCommerce" (Google Listings & Ads) plugin
 * to modify per-product data during sync.
 *
 * @package BannerCalc
 */

namespace BannerCalc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GoogleProductFeed {

    public function __construct() {
        // Register the custom feed endpoint.
        add_action( 'init', [ $this, 'register_feed' ] );

        // Hook into Google for WooCommerce (Google Listings & Ads) if active.
        add_action( 'plugins_loaded', [ $this, 'hook_google_for_woocommerce' ], 30 );
    }

    /**
     * Register the custom feed.
     */
    public function register_feed(): void {
        add_feed( 'bannercalc-google', [ $this, 'render_feed' ] );
    }

    /**
     * Hook into Google for WooCommerce to modify product data during sync.
     */
    public function hook_google_for_woocommerce(): void {
        // Only proceed if Google for WooCommerce is active.
        if ( ! defined( 'WC_GLA_VERSION' ) ) {
            return;
        }

        // Modify product attribute values before sync.
        add_filter( 'woocommerce_gla_product_attribute_values', [ $this, 'modify_gla_product_attributes' ], 10, 3 );

        // Mark BannerCalc products as needing special handling.
        add_filter( 'woocommerce_gla_product_is_ready_to_sync', [ $this, 'gla_product_is_ready' ], 10, 2 );
    }

    /**
     * Modify Google Listings & Ads product attributes.
     *
     * Sets the item_group_id and adjusts the price to reflect the lowest preset size price.
     * Google for WooCommerce creates one MC product per WC product — for full variant support
     * the supplemental feed should be used.
     *
     * @param array       $attributes Attribute values keyed by attribute ID.
     * @param \WC_Product $product    WooCommerce product.
     * @param string      $adapter_class The adapter class name.
     * @return array
     */
    public function modify_gla_product_attributes( array $attributes, $product, $adapter_class = '' ): array {
        if ( ! $product instanceof \WC_Product ) {
            return $attributes;
        }

        $plugin = Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return $attributes;
        }

        $config  = $plugin->get_product_config( $product->get_id() );
        $presets = $config['preset_sizes'] ?? [];

        if ( empty( $presets ) ) {
            return $attributes;
        }

        $rate      = (float) ( $config['area_rate_sqft'] ?? 0 );
        $min_charge = (float) ( $config['minimum_charge'] ?? 0 );

        // Calculate prices for all presets.
        $prices = [];
        foreach ( $presets as $preset ) {
            $prices[] = $this->calculate_preset_price( $preset, $rate, $min_charge );
        }

        $min_price = min( $prices );
        $max_price = max( $prices );

        // Set item_group_id so GMC knows variants belong together.
        $attributes['itemGroupId'] = 'bannercalc-' . $product->get_id();

        // Set price range (lowest preset price as the base).
        $attributes['price'] = [
            'value'    => number_format( $min_price, 2, '.', '' ),
            'currency' => get_woocommerce_currency(),
        ];

        // Build a size string from all presets for the product listing.
        $size_labels = array_column( $presets, 'label' );
        if ( ! empty( $size_labels ) ) {
            $attributes['sizes'] = $size_labels;
        }

        return $attributes;
    }

    /**
     * Ensure BannerCalc products are marked ready to sync.
     *
     * @param bool        $ready   Whether the product is ready.
     * @param \WC_Product $product WooCommerce product.
     * @return bool
     */
    public function gla_product_is_ready( bool $ready, $product ): bool {
        if ( $ready ) {
            return $ready;
        }

        if ( ! $product instanceof \WC_Product ) {
            return $ready;
        }

        $plugin = Plugin::instance();
        if ( $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return true;
        }

        return $ready;
    }

    /**
     * Render the supplemental Google Products XML feed.
     *
     * This feed outputs one <item> per preset size per BannerCalc-enabled product.
     * Google Merchant Center can consume this as a supplemental feed alongside
     * (or instead of) the main Google for WooCommerce sync.
     *
     * Feed URL: /feed/bannercalc-google/ or ?feed=bannercalc-google
     */
    public function render_feed(): void {
        // Set proper XML content type.
        header( 'Content-Type: application/xml; charset=UTF-8' );

        $settings  = Plugin::get_settings();
        $site_name = get_bloginfo( 'name' );
        $site_url  = home_url( '/' );
        $currency  = get_woocommerce_currency();

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        ?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
<title><?php echo esc_html( $site_name ); ?> — BannerCalc Product Sizes</title>
<link><?php echo esc_url( $site_url ); ?></link>
<description>Product size variants generated by BannerCalc</description>
<?php
        // Query all BannerCalc-enabled products.
        $products = $this->get_bannercalc_products();

        foreach ( $products as $product ) {
            $config  = Plugin::instance()->get_product_config( $product->get_id() );
            $presets = $config['preset_sizes'] ?? [];

            if ( empty( $presets ) ) {
                continue;
            }

            $rate       = (float) ( $config['area_rate_sqft'] ?? 0 );
            $min_charge = (float) ( $config['minimum_charge'] ?? 0 );
            $permalink  = get_permalink( $product->get_id() );
            $image_id   = $product->get_image_id();
            $image_url  = $image_id ? wp_get_attachment_url( $image_id ) : '';
            $categories = $this->get_product_google_category( $product );
            $brand      = $this->get_product_brand( $product );
            $description = wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() );
            // Limit description length for feed.
            if ( mb_strlen( $description ) > 5000 ) {
                $description = mb_substr( $description, 0, 4997 ) . '...';
            }

            $product_name  = $product->get_name();
            $product_id    = $product->get_id();
            $product_sku   = $product->get_sku();
            $group_id      = $product_sku ?: ( 'bannercalc-' . $product_id );
            $stock_status  = $product->is_in_stock() ? 'in_stock' : 'out_of_stock';

            foreach ( $presets as $preset ) {
                $price      = $this->calculate_preset_price( $preset, $rate, $min_charge );
                $size_param = \BannerCalc\Frontend\ProductDisplay::get_size_url_param( $preset );
                $size_url   = add_query_arg( 'attribute_size', $size_param, $permalink );
                $size_label = $preset['label'] ?? $size_param;
                $preset_slug = $preset['slug'] ?? sanitize_title( $size_label );

                // Build unique ID per variant: SKU-sizeslug or productid-sizeslug.
                $variant_id = ( $product_sku ?: $product_id ) . '-' . $preset_slug;

                // MPN per variant.
                $mpn = $this->get_product_mpn( $product, $preset );
                ?>
<item>
<g:id><?php echo esc_html( $variant_id ); ?></g:id>
<g:item_group_id><?php echo esc_html( $group_id ); ?></g:item_group_id>
<title><?php echo esc_html( $product_name . ' - ' . $size_label ); ?></title>
<link><?php echo esc_url( $size_url ); ?></link>
<description><?php echo esc_html( $description ); ?></description>
<g:price><?php echo esc_html( number_format( $price, 2, '.', '' ) . ' ' . $currency ); ?></g:price>
<g:availability><?php echo esc_html( $stock_status ); ?></g:availability>
<g:condition>new</g:condition>
<?php if ( $image_url ) : ?>
<g:image_link><?php echo esc_url( $image_url ); ?></g:image_link>
<?php endif; ?>
<?php if ( $brand ) : ?>
<g:brand><?php echo esc_html( $brand ); ?></g:brand>
<?php endif; ?>
<?php if ( $mpn ) : ?>
<g:mpn><?php echo esc_html( $mpn ); ?></g:mpn>
<?php endif; ?>
<g:size><?php echo esc_html( $size_label ); ?></g:size>
<?php if ( $categories ) : ?>
<g:google_product_category><?php echo esc_html( $categories ); ?></g:google_product_category>
<?php endif; ?>
<g:product_type><?php echo esc_html( $this->get_product_type_path( $product ) ); ?></g:product_type>
</item>
<?php
            }
        }
        ?>
</channel>
</rss>
<?php
        exit;
    }

    /**
     * Get all WooCommerce products with BannerCalc enabled.
     *
     * @return \WC_Product[]
     */
    private function get_bannercalc_products(): array {
        $plugin = Plugin::instance();
        $products = [];

        // Get all product categories with BannerCalc enabled.
        $enabled_cats = $this->get_enabled_category_ids();

        if ( empty( $enabled_cats ) ) {
            return $products;
        }

        // Query products in those categories.
        $args = [
            'status'   => 'publish',
            'limit'    => -1,
            'category' => [],
            'return'   => 'objects',
        ];

        // Use tax_query for category filtering.
        $query_args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $enabled_cats,
                    'include_children' => true,
                ],
            ],
        ];

        $post_ids = get_posts( $query_args );

        foreach ( $post_ids as $post_id ) {
            $product = wc_get_product( $post_id );
            if ( $product && $plugin->is_enabled_for_product( $product->get_id() ) ) {
                $products[] = $product;
            }
        }

        return $products;
    }

    /**
     * Get all category IDs that have BannerCalc enabled.
     *
     * @return int[]
     */
    private function get_enabled_category_ids(): array {
        $categories = get_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'fields'     => 'ids',
        ] );

        if ( is_wp_error( $categories ) ) {
            return [];
        }

        $enabled = [];
        foreach ( $categories as $cat_id ) {
            $config = get_term_meta( $cat_id, '_bannercalc_config', true );
            if ( ! empty( $config ) && ! empty( $config['enabled'] ) ) {
                $enabled[] = (int) $cat_id;
            }
        }

        return $enabled;
    }

    /**
     * Calculate the price for a single preset size.
     *
     * @param array $preset    Preset data.
     * @param float $rate      Area rate per sqft.
     * @param float $min_charge Minimum charge.
     * @return float
     */
    private function calculate_preset_price( array $preset, float $rate, float $min_charge ): float {
        if ( isset( $preset['price'] ) && $preset['price'] !== null && $preset['price'] !== '' ) {
            return (float) $preset['price'];
        }

        $w    = (float) ( $preset['width_m'] ?? 0 );
        $h    = (float) ( $preset['height_m'] ?? 0 );
        $sqft = $w * $h * UnitConverter::SQFT_PER_SQM;
        $p    = $sqft * $rate;

        if ( $p < $min_charge ) {
            $p = $min_charge;
        }

        return $p;
    }

    /**
     * Get the Google product category for a product.
     *
     * Tries: custom meta → Yoast GMC category → empty.
     *
     * @param \WC_Product $product
     * @return string
     */
    private function get_product_google_category( \WC_Product $product ): string {
        // Check for a custom Google product category in meta.
        $gpc = get_post_meta( $product->get_id(), '_wpseo_global_identifier_values', true );
        if ( ! empty( $gpc ) && is_array( $gpc ) && ! empty( $gpc['google_product_category'] ) ) {
            return $gpc['google_product_category'];
        }

        // Check Google for WooCommerce category mapping.
        $gla_cat = get_post_meta( $product->get_id(), '_wc_gla_google_category', true );
        if ( ! empty( $gla_cat ) ) {
            return $gla_cat;
        }

        return '';
    }

    /**
     * Get the brand for a product.
     *
     * @param \WC_Product $product
     * @return string
     */
    private function get_product_brand( \WC_Product $product ): string {
        // Check brand taxonomy (common plugins: WooCommerce Brands, YITH Brands).
        $brand_taxonomies = [ 'product_brand', 'pwb-brand', 'yith_product_brand' ];
        foreach ( $brand_taxonomies as $tax ) {
            if ( taxonomy_exists( $tax ) ) {
                $terms = get_the_terms( $product->get_id(), $tax );
                if ( $terms && ! is_wp_error( $terms ) ) {
                    return $terms[0]->name;
                }
            }
        }

        // Check custom meta.
        $brand = get_post_meta( $product->get_id(), '_brand', true );
        if ( $brand ) {
            return $brand;
        }

        // Check Google for WooCommerce brand attribute.
        $gla_brand = get_post_meta( $product->get_id(), '_wc_gla_brand', true );
        if ( $gla_brand ) {
            return $gla_brand;
        }

        // Fallback to site name.
        return get_bloginfo( 'name' );
    }

    /**
     * Generate an MPN for a size variant.
     *
     * Format: {SKU or product ID}-{widthMM}x{heightMM}
     * This matches the pattern shown in the GMC screenshot (e.g. PP-015-1200mmx2000mm).
     *
     * @param \WC_Product $product
     * @param array       $preset
     * @return string
     */
    private function get_product_mpn( \WC_Product $product, array $preset ): string {
        $base = $product->get_sku() ?: ( 'BC-' . $product->get_id() );

        // Build size suffix in mm (matching GMC pattern).
        $w_mm = round( (float) ( $preset['width_m'] ?? 0 ) * 1000 );
        $h_mm = round( (float) ( $preset['height_m'] ?? 0 ) * 1000 );

        return $base . '-' . $w_mm . 'mmx' . $h_mm . 'mm';
    }

    /**
     * Get the product category path (breadcrumb style) for product_type field.
     *
     * @param \WC_Product $product
     * @return string
     */
    private function get_product_type_path( \WC_Product $product ): string {
        $category_ids = $product->get_category_ids();
        if ( empty( $category_ids ) ) {
            return '';
        }

        // Use the first category and build the full path.
        $cat_id    = $category_ids[0];
        $ancestors = get_ancestors( $cat_id, 'product_cat', 'taxonomy' );
        $ancestors = array_reverse( $ancestors );
        $ancestors[] = $cat_id;

        $parts = [];
        foreach ( $ancestors as $ancestor_id ) {
            $term = get_term( $ancestor_id, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                $parts[] = $term->name;
            }
        }

        return implode( ' > ', $parts );
    }
}
