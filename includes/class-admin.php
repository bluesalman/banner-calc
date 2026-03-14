<?php
/**
 * Admin coordinator — loads admin components and enqueues assets.
 *
 * @package BannerCalc
 */

namespace BannerCalc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {

    /**
     * @var \BannerCalc\Admin\SettingsPage
     */
    public \BannerCalc\Admin\SettingsPage $settings_page;

    /**
     * @var \BannerCalc\Admin\CategoryDefaults
     */
    public \BannerCalc\Admin\CategoryDefaults $category_defaults;

    /**
     * @var \BannerCalc\Admin\ProductMetabox
     */
    public \BannerCalc\Admin\ProductMetabox $product_metabox;

    public function __construct() {
        $this->settings_page     = new \BannerCalc\Admin\SettingsPage();
        $this->category_defaults = new \BannerCalc\Admin\CategoryDefaults();
        $this->product_metabox   = new \BannerCalc\Admin\ProductMetabox();

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Enqueue admin CSS and JS.
     *
     * @param string $hook_suffix
     */
    public function enqueue_assets( string $hook_suffix ): void {
        // Only load on relevant pages.
        $screen = get_current_screen();

        $is_bannercalc_page = (
            str_contains( $hook_suffix, 'bannercalc' )
            || ( $screen && $screen->id === 'product' )
            || ( $screen && $screen->id === 'shop_order' )
            || ( $screen && str_contains( $screen->id, 'edit-product_cat' ) )
        );

        if ( ! $is_bannercalc_page ) {
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
            'bannercalc-admin',
            BANNERCALC_PLUGIN_URL . 'admin/css/admin.css',
            [ 'bannercalc-google-fonts' ],
            BANNERCALC_VERSION
        );

        wp_enqueue_script(
            'bannercalc-admin',
            BANNERCALC_PLUGIN_URL . 'admin/js/admin.js',
            [ 'jquery', 'wp-util' ],
            BANNERCALC_VERSION,
            true
        );

        wp_localize_script( 'bannercalc-admin', 'bannercalcAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'bannercalc_admin' ),
        ] );
    }
}
