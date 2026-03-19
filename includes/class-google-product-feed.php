<?php
/**
 * Google Product Feed — cached supplemental feed for Google Merchant Center.
 *
 * Generates one <item> per BannerCalc preset size and caches the XML.
 * Feed is regenerated only when product / config data changes or
 * manually via the GMC settings page.
 *
 * Feed URL: ?feed=bannercalc-google
 *
 * Also hooks into "Google for WooCommerce" (Google Listings & Ads) to
 * exclude range-priced BannerCalc products from GLA sync (the
 * supplemental feed handles those instead).
 *
 * @package BannerCalc
 */

namespace BannerCalc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GoogleProductFeed {

    /** Option key for cached XML. */
    const CACHE_KEY = 'bannercalc_google_feed_xml';

    /** Option key for feed metadata (timestamp, counts). */
    const META_KEY = 'bannercalc_google_feed_meta';

    public function __construct() {
        // Register the custom feed endpoint.
        add_action( 'init', [ $this, 'register_feed' ] );

        // Hook into Google for WooCommerce (Google Listings & Ads) if active.
        add_action( 'plugins_loaded', [ $this, 'hook_google_for_woocommerce' ], 30 );

        // Invalidation hooks — schedule rebuild when data changes.
        add_action( 'save_post_product', [ $this, 'schedule_rebuild' ] );
        add_action( 'woocommerce_product_set_stock_status', [ $this, 'schedule_rebuild' ] );
        add_action( 'updated_post_meta', [ $this, 'on_post_meta_updated' ], 10, 3 );
        add_action( 'updated_term_meta', [ $this, 'on_term_meta_updated' ], 10, 3 );
        add_action( 'update_option_bannercalc_settings', [ $this, 'schedule_rebuild' ] );

        // Deferred rebuild action (fired by wp_schedule_single_event).
        add_action( 'bannercalc_rebuild_google_feed', [ $this, 'regenerate' ] );

        // AJAX handler for manual rebuild from admin.
        add_action( 'wp_ajax_bannercalc_regenerate_feed', [ $this, 'ajax_regenerate' ] );
    }

    /* =================================================================
       Feed Endpoint
    ================================================================= */

    public function register_feed(): void {
        add_feed( 'bannercalc-google', [ $this, 'render_feed' ] );
    }

    /**
     * Serve the cached feed XML. If no cache exists, build it first.
     */
    public function render_feed(): void {
        header( 'Content-Type: application/xml; charset=UTF-8' );

        $xml = get_option( self::CACHE_KEY, '' );

        if ( empty( $xml ) ) {
            $xml = $this->build_feed_xml();
            $this->store_cache( $xml );
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped XML
        echo $xml;
        exit;
    }

    /* =================================================================
       Google for WooCommerce — Exclude range-priced products
    ================================================================= */

    public function hook_google_for_woocommerce(): void {
        if ( ! defined( 'WC_GLA_VERSION' ) ) {
            return;
        }

        // Exclude BannerCalc range-priced products from GLA sync.
        add_filter( 'woocommerce_gla_product_is_ready_to_sync', [ $this, 'gla_product_is_ready' ], 10, 2 );
    }

    /**
     * Exclude BannerCalc products that use preset sizes from GLA sync.
     * These are handled by the supplemental feed instead.
     * Fixed-price products (no presets) are left untouched for GLA.
     */
    public function gla_product_is_ready( bool $ready, $product ): bool {
        if ( ! $product instanceof \WC_Product ) {
            return $ready;
        }

        $plugin = Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $product->get_id() ) ) {
            return $ready;
        }

        $config  = $plugin->get_product_config( $product->get_id() );
        $presets = $config['preset_sizes'] ?? [];

        // If product has preset sizes → exclude from GLA (our feed handles it).
        if ( ! empty( $presets ) ) {
            return false;
        }

        return $ready;
    }

    /* =================================================================
       Cache Invalidation
    ================================================================= */

    /**
     * Schedule a deferred feed rebuild (coalesces multiple saves).
     */
    public function schedule_rebuild(): void {
        if ( get_transient( 'bannercalc_feed_rebuild_scheduled' ) ) {
            return;
        }

        set_transient( 'bannercalc_feed_rebuild_scheduled', 1, 120 );
        wp_schedule_single_event( time() + 30, 'bannercalc_rebuild_google_feed' );
    }

    /**
     * Rebuild when BannerCalc product config meta is updated.
     */
    public function on_post_meta_updated( $meta_id, $object_id, $meta_key ): void {
        if ( '_bannercalc_product_config' === $meta_key ) {
            $this->schedule_rebuild();
        }
    }

    /**
     * Rebuild when BannerCalc category config meta is updated.
     */
    public function on_term_meta_updated( $meta_id, $object_id, $meta_key ): void {
        if ( '_bannercalc_config' === $meta_key ) {
            $this->schedule_rebuild();
        }
    }

    /* =================================================================
       Feed Generation
    ================================================================= */

    /**
     * Regenerate the feed XML and store it. Returns metadata.
     *
     * @return array{products: int, variants: int, generated: int}
     */
    public function regenerate(): array {
        delete_transient( 'bannercalc_feed_rebuild_scheduled' );
        $xml = $this->build_feed_xml();
        return $this->store_cache( $xml );
    }

    /**
     * AJAX handler — manual regenerate from GMC settings page.
     */
    public function ajax_regenerate(): void {
        check_ajax_referer( 'bannercalc_admin', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Unauthorized', 403 );
        }

        $meta = $this->regenerate();
        wp_send_json_success( $meta );
    }

    /**
     * Build the complete feed XML string.
     */
    private function build_feed_xml(): string {
        $settings   = Plugin::get_settings();
        $site_name  = get_bloginfo( 'name' );
        $site_url   = home_url( '/' );
        $currency   = get_woocommerce_currency();
        $gmc_brand  = ! empty( $settings['gmc_default_brand'] )
            ? $settings['gmc_default_brand']
            : '';
        $gmc_cat    = ! empty( $settings['gmc_default_category'] )
            ? $settings['gmc_default_category']
            : '';

        $products = $this->get_bannercalc_products();

        ob_start();
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        ?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
<title><?php echo esc_html( $site_name ); ?> — BannerCalc Product Sizes</title>
<link><?php echo esc_url( $site_url ); ?></link>
<description>Product size variants generated by BannerCalc</description>
<?php
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
            $categories = $this->get_product_google_category( $product, $gmc_cat );
            $brand = $this->get_product_brand( $product, $gmc_brand );
            $description = wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() );
            if ( mb_strlen( $description ) > 5000 ) {
                $description = mb_substr( $description, 0, 4997 ) . '...';
            }

            $product_name  = $product->get_name();
            $product_id    = $product->get_id();
            $product_sku   = $product->get_sku();
            $group_id      = $product_sku ?: ( 'bannercalc-' . $product_id );
            $stock_status  = $product->is_in_stock() ? 'in_stock' : 'out_of_stock';

            foreach ( $presets as $preset ) {
                $price       = $this->calculate_preset_price( $preset, $rate, $min_charge );
                $size_param  = \BannerCalc\Frontend\ProductDisplay::get_size_url_param( $preset );
                $size_url    = add_query_arg( 'attribute_size', $size_param, $permalink );
                $size_label  = $preset['label'] ?? $size_param;
                $preset_slug = $preset['slug'] ?? sanitize_title( $size_label );
                $variant_id  = ( $product_sku ?: $product_id ) . '-' . $preset_slug;
                $mpn         = $this->get_product_mpn( $product, $preset );
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
        return ob_get_clean();
    }

    /**
     * Store cached XML and metadata.
     *
     * @return array{products: int, variants: int, generated: int, size: int}
     */
    private function store_cache( string $xml ): array {
        update_option( self::CACHE_KEY, $xml, false );

        // Count products and variants from the XML.
        $variant_count = substr_count( $xml, '<g:id>' );
        $group_ids     = [];
        if ( preg_match_all( '/<g:item_group_id>([^<]+)</', $xml, $matches ) ) {
            $group_ids = array_unique( $matches[1] );
        }

        $meta = [
            'products'  => count( $group_ids ),
            'variants'  => $variant_count,
            'generated' => time(),
            'size'      => strlen( $xml ),
        ];

        update_option( self::META_KEY, $meta, false );
        return $meta;
    }

    /**
     * Get feed metadata (for admin display).
     *
     * @return array{products: int, variants: int, generated: int, size: int}
     */
    public static function get_feed_meta(): array {
        return (array) get_option( self::META_KEY, [
            'products'  => 0,
            'variants'  => 0,
            'generated' => 0,
            'size'      => 0,
        ] );
    }

    /* =================================================================
       Product Queries
    ================================================================= */

    private function get_bannercalc_products(): array {
        $plugin   = Plugin::instance();
        $products = [];

        $enabled_cats = $this->get_enabled_category_ids();

        if ( empty( $enabled_cats ) ) {
            return $products;
        }

        $query_args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [
                [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => $enabled_cats,
                    'include_children' => true,
                ],
            ],
        ];

        $post_ids = get_posts( $query_args );

        foreach ( $post_ids as $post_id ) {
            $product = wc_get_product( $post_id );
            if ( $product && $plugin->is_enabled_for_product( $product->get_id() ) ) {
                $config  = $plugin->get_product_config( $product->get_id() );
                $presets = $config['preset_sizes'] ?? [];
                // Only include products with preset sizes (range-priced).
                if ( ! empty( $presets ) ) {
                    $products[] = $product;
                }
            }
        }

        return $products;
    }

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

    /* =================================================================
       Price & Attribute Helpers
    ================================================================= */

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
     * Resolve the Google product category for a product.
     *
     * Priority: per-product meta → BannerCalc category config → GMC default setting.
     */
    private function get_product_google_category( \WC_Product $product, string $default = '' ): string {
        // 1. Per-product: Yoast SEO meta.
        $gpc = get_post_meta( $product->get_id(), '_wpseo_global_identifier_values', true );
        if ( ! empty( $gpc ) && is_array( $gpc ) && ! empty( $gpc['google_product_category'] ) ) {
            return $gpc['google_product_category'];
        }

        // 2. Per-product: Google for WooCommerce meta.
        $gla_cat = get_post_meta( $product->get_id(), '_wc_gla_google_category', true );
        if ( ! empty( $gla_cat ) ) {
            return $gla_cat;
        }

        // 3. Per-category: BannerCalc category config.
        $cat_ids = $product->get_category_ids();
        foreach ( $cat_ids as $cat_id ) {
            $cat_config = get_term_meta( $cat_id, '_bannercalc_config', true );
            if ( ! empty( $cat_config['google_product_category'] ) ) {
                return $cat_config['google_product_category'];
            }
        }

        // 4. Global default from GMC settings page.
        return $default;
    }

    /**
     * @param string $default_brand Fallback brand from settings.
     */
    private function get_product_brand( \WC_Product $product, string $default_brand = '' ): string {
        $brand_taxonomies = [ 'product_brand', 'pwb-brand', 'yith_product_brand' ];
        foreach ( $brand_taxonomies as $tax ) {
            if ( taxonomy_exists( $tax ) ) {
                $terms = get_the_terms( $product->get_id(), $tax );
                if ( $terms && ! is_wp_error( $terms ) ) {
                    return $terms[0]->name;
                }
            }
        }

        $brand = get_post_meta( $product->get_id(), '_brand', true );
        if ( $brand ) {
            return $brand;
        }

        $gla_brand = get_post_meta( $product->get_id(), '_wc_gla_brand', true );
        if ( $gla_brand ) {
            return $gla_brand;
        }

        if ( $default_brand ) {
            return $default_brand;
        }

        return get_bloginfo( 'name' );
    }

    private function get_product_mpn( \WC_Product $product, array $preset ): string {
        $base = $product->get_sku() ?: ( 'BC-' . $product->get_id() );
        $w_mm = round( (float) ( $preset['width_m'] ?? 0 ) * 1000 );
        $h_mm = round( (float) ( $preset['height_m'] ?? 0 ) * 1000 );

        return $base . '-' . $w_mm . 'mmx' . $h_mm . 'mm';
    }

    private function get_product_type_path( \WC_Product $product ): string {
        $category_ids = $product->get_category_ids();
        if ( empty( $category_ids ) ) {
            return '';
        }

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
