<?php
/**
 * Admin view — Google Merchant Center settings page.
 *
 * @package BannerCalc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings      = \BannerCalc\Plugin::get_settings();
$feed_meta     = \BannerCalc\GoogleProductFeed::get_feed_meta();
$feed_url      = home_url( '?feed=bannercalc-google' );
$generated_ts  = (int) ( $feed_meta['generated'] ?? 0 );
$product_count = (int) ( $feed_meta['products'] ?? 0 );
$variant_count = (int) ( $feed_meta['variants'] ?? 0 );
$feed_size     = (int) ( $feed_meta['size'] ?? 0 );
?>
<div class="bannercalc-admin-wrap">
    <div class="bannercalc-cmyk-bar" aria-hidden="true"></div>

    <div class="bannercalc-page-header">
        <h1><?php esc_html_e( 'Google Merchant Center', 'bannercalc' ); ?></h1>
        <p class="bannercalc-page-subtitle">Supplemental product feed for Google Merchant Center — one listing per preset size with correct pricing and deep links.</p>
    </div>

    <!-- Feed URL -->
    <div class="bannercalc-content-card">
        <h2>Feed URL</h2>
        <p>Add this URL as a <strong>supplemental feed</strong> in Google Merchant Center. Format: Google RSS (XML).</p>
        <div class="bannercalc-gmc-feed-url-row">
            <input type="text" id="bannercalc-feed-url" class="bannercalc-gmc-feed-url" value="<?php echo esc_url( $feed_url ); ?>" readonly />
            <button type="button" class="bannercalc-btn bannercalc-btn-secondary" id="bannercalc-copy-feed-url">
                <span class="dashicons dashicons-clipboard"></span> Copy
            </button>
        </div>
    </div>

    <!-- Feed Status -->
    <div class="bannercalc-content-card">
        <h2>Feed Status</h2>
        <div class="bannercalc-gmc-status-grid">
            <div class="bannercalc-gmc-status-item">
                <span class="bannercalc-gmc-status-label">Last Generated</span>
                <span class="bannercalc-gmc-status-value" id="bannercalc-feed-generated">
                    <?php if ( $generated_ts ) : ?>
                        <?php echo esc_html( wp_date( 'j M Y, H:i:s', $generated_ts ) ); ?>
                    <?php else : ?>
                        <em>Never</em>
                    <?php endif; ?>
                </span>
            </div>
            <div class="bannercalc-gmc-status-item">
                <span class="bannercalc-gmc-status-label">Products</span>
                <span class="bannercalc-gmc-status-value" id="bannercalc-feed-products"><?php echo esc_html( $product_count ); ?></span>
            </div>
            <div class="bannercalc-gmc-status-item">
                <span class="bannercalc-gmc-status-label">Size Variants</span>
                <span class="bannercalc-gmc-status-value" id="bannercalc-feed-variants"><?php echo esc_html( $variant_count ); ?></span>
            </div>
            <div class="bannercalc-gmc-status-item">
                <span class="bannercalc-gmc-status-label">Feed Size</span>
                <span class="bannercalc-gmc-status-value" id="bannercalc-feed-size">
                    <?php echo $feed_size ? esc_html( size_format( $feed_size ) ) : '—'; ?>
                </span>
            </div>
        </div>

        <div class="bannercalc-gmc-regenerate-row">
            <button type="button" class="bannercalc-btn bannercalc-btn-primary" id="bannercalc-regenerate-feed">
                <span class="dashicons dashicons-update"></span> Regenerate Feed
            </button>
            <span class="spinner" id="bannercalc-regenerate-spinner"></span>
            <span class="bannercalc-gmc-regenerate-msg" id="bannercalc-regenerate-msg"></span>
        </div>
    </div>

    <!-- Default Settings -->
    <div class="bannercalc-content-card">
        <h2>Feed Defaults</h2>
        <p>Fallback values used when individual products don't have brand or category data set.</p>

        <form method="post" action="options.php">
            <?php settings_fields( 'bannercalc_settings_group' ); ?>

            <?php
            // Carry forward all existing settings as hidden fields so they aren't lost.
            $preserve_keys = [
                'enabled', 'currency_symbol', 'default_unit', 'price_display_decimals',
                'shipping_method', 'shipping_cost', 'collection_enabled',
            ];
            foreach ( $preserve_keys as $key ) {
                if ( isset( $settings[ $key ] ) ) {
                    $val = $settings[ $key ];
                    if ( is_bool( $val ) ) {
                        $val = $val ? '1' : '0';
                    }
                    printf(
                        '<input type="hidden" name="bannercalc_settings[%s]" value="%s" />',
                        esc_attr( $key ),
                        esc_attr( $val )
                    );
                }
            }

            // Preserve arrays (available_units, service_types, design_service).
            if ( ! empty( $settings['available_units'] ) && is_array( $settings['available_units'] ) ) {
                foreach ( $settings['available_units'] as $unit ) {
                    printf( '<input type="hidden" name="bannercalc_settings[available_units][]" value="%s" />', esc_attr( $unit ) );
                }
            }
            if ( ! empty( $settings['service_types'] ) && is_array( $settings['service_types'] ) ) {
                foreach ( $settings['service_types'] as $i => $st ) {
                    foreach ( $st as $k => $v ) {
                        if ( is_bool( $v ) ) {
                            $v = $v ? '1' : '0';
                        }
                        printf(
                            '<input type="hidden" name="bannercalc_settings[service_types][%d][%s]" value="%s" />',
                            (int) $i,
                            esc_attr( $k ),
                            esc_attr( $v )
                        );
                    }
                }
            }
            if ( ! empty( $settings['design_service'] ) && is_array( $settings['design_service'] ) ) {
                foreach ( $settings['design_service'] as $k => $v ) {
                    if ( is_bool( $v ) ) {
                        $v = $v ? '1' : '0';
                    }
                    printf(
                        '<input type="hidden" name="bannercalc_settings[design_service][%s]" value="%s" />',
                        esc_attr( $k ),
                        esc_attr( $v )
                    );
                }
            }
            ?>

            <table class="form-table bannercalc-form-table">
                <tr>
                    <th scope="row"><label for="gmc-default-brand">Default Brand</label></th>
                    <td>
                        <input type="text" id="gmc-default-brand" name="bannercalc_settings[gmc_default_brand]"
                               value="<?php echo esc_attr( $settings['gmc_default_brand'] ?? '' ); ?>"
                               class="regular-text" placeholder="e.g. Banner Printing Ltd" />
                        <p class="description">Used when no brand taxonomy or meta is set on a product.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gmc-default-category">Default Google Category</label></th>
                    <td>
                        <input type="text" id="gmc-default-category" name="bannercalc_settings[gmc_default_category]"
                               value="<?php echo esc_attr( $settings['gmc_default_category'] ?? '' ); ?>"
                               class="regular-text" placeholder="e.g. Signage > Banners" />
                        <p class="description">
                            Google product taxonomy category. See
                            <a href="https://www.google.com/basepages/producttype/taxonomy-with-ids.en-GB.txt" target="_blank" rel="noopener">
                                full taxonomy list</a>.
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button( 'Save Defaults' ); ?>
        </form>
    </div>

    <!-- Setup Guide -->
    <div class="bannercalc-content-card">
        <h2>Setup Guide</h2>
        <div class="bannercalc-gmc-guide">
            <details>
                <summary><strong>1. Add the supplemental feed in Google Merchant Center</strong></summary>
                <ol>
                    <li>Go to <strong>Products → Feeds</strong> in your Merchant Center account.</li>
                    <li>Under <strong>Supplemental feeds</strong>, click <strong>Add supplemental feed</strong>.</li>
                    <li>Name it (e.g. "BannerCalc Sizes") and choose <strong>Scheduled fetch</strong>.</li>
                    <li>Paste the feed URL shown above. Set frequency to <strong>Daily</strong>.</li>
                    <li>Link it to your primary feed so the supplemental data merges in.</li>
                </ol>
            </details>

            <details>
                <summary><strong>2. How the feed works</strong></summary>
                <ul>
                    <li>Each BannerCalc product with preset sizes generates <strong>one feed entry per size</strong>.</li>
                    <li>Entries share an <code>item_group_id</code> so Google treats them as size variants.</li>
                    <li>Each entry links to the product page with <code>?attribute_size=...</code> to pre-select that size.</li>
                    <li>Prices are calculated from the area rate and preset dimensions at feed generation time.</li>
                </ul>
            </details>

            <details>
                <summary><strong>3. Google for WooCommerce integration</strong></summary>
                <ul>
                    <li>BannerCalc products with preset sizes are <strong>excluded from Google for WooCommerce sync</strong> automatically.</li>
                    <li>This prevents the £0.01 placeholder price issue in GMC.</li>
                    <li>Fixed-price products (without presets) continue to sync through GLA normally.</li>
                    <li>For best results, use GLA for fixed-price products and this supplemental feed for preset-size products.</li>
                </ul>
            </details>

            <details>
                <summary><strong>4. Cache & regeneration</strong></summary>
                <ul>
                    <li>The feed is <strong>statically cached</strong> and served instantly without querying products on every request.</li>
                    <li>The cache automatically rebuilds when you save a product, update category config, or change plugin settings.</li>
                    <li>Use the <strong>Regenerate Feed</strong> button above to manually rebuild at any time.</li>
                    <li>A scheduled rebuild occurs ~30 seconds after changes to coalesce multiple saves.</li>
                </ul>
            </details>
        </div>
    </div>
</div>
