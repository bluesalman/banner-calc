<?php
/**
 * Frontend coordinator — loads frontend components and enqueues assets.
 *
 * @package BannerCalc
 */

namespace BannerCalc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Frontend {

    /**
     * @var \BannerCalc\Frontend\ProductDisplay
     */
    public \BannerCalc\Frontend\ProductDisplay $product_display;

    public function __construct() {
        $this->product_display = new \BannerCalc\Frontend\ProductDisplay();

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Enqueue frontend CSS and JS on single product pages.
     */
    public function enqueue_assets(): void {
        if ( ! is_product() ) {
            return;
        }

        global $post;
        $plugin = Plugin::instance();

        if ( ! $plugin->is_enabled_for_product( $post->ID ) ) {
            return;
        }

        // Brand fonts: Outfit (display), Plus Jakarta Sans (body), IBM Plex Mono (mono).
        wp_enqueue_style(
            'bannercalc-google-fonts',
            'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap',
            [],
            null
        );

        wp_enqueue_style(
            'bannercalc-frontend',
            BANNERCALC_PLUGIN_URL . 'frontend/css/frontend.css',
            [ 'bannercalc-google-fonts' ],
            BANNERCALC_VERSION
        );

        wp_enqueue_script(
            'bannercalc-frontend',
            BANNERCALC_PLUGIN_URL . 'frontend/js/frontend.js',
            [ 'jquery' ],
            BANNERCALC_VERSION,
            true
        );

        $settings = Plugin::get_settings();

        wp_localize_script( 'bannercalc-frontend', 'bannercalcFrontend', [
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'bannercalc_frontend' ),
            'productId'      => $post->ID,
            'currencySymbol' => $settings['currency_symbol'],
            'decimals'       => $settings['price_display_decimals'],
        ] );
    }
}
