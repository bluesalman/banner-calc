<?php
/**
 * Plugin Name: BannerCalc
 * Plugin URI:  https://github.com/your-repo/bannercalc
 * Description: Product Configurator & Pricing Engine for WooCommerce. Replaces variation-based configuration with a unified pricing engine using area-based calculations and attribute modifiers.
 * Version:     1.0.0
 * Author:      BannerCalc
 * Author URI:  https://github.com/your-repo/bannercalc
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bannercalc
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Plugin constants.
 */
define( 'BANNERCALC_VERSION', '1.0.0' );
define( 'BANNERCALC_PLUGIN_FILE', __FILE__ );
define( 'BANNERCALC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BANNERCALC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BANNERCALC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Activation hook.
 */
function bannercalc_activate() {
    // Set default options if they don't exist.
    if ( false === get_option( 'bannercalc_settings' ) ) {
        update_option( 'bannercalc_settings', [
            'enabled'                => true,
            'currency_symbol'        => '£',
            'default_unit'           => 'ft',
            'available_units'        => [ 'mm', 'cm', 'inch', 'ft', 'm' ],
            'price_display_decimals' => 2,
        ] );
    }

    // Flush rewrite rules.
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'bannercalc_activate' );

/**
 * Deactivation hook.
 */
function bannercalc_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'bannercalc_deactivate' );

/**
 * Check for WooCommerce dependency before initialising.
 */
function bannercalc_check_dependencies() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__( 'BannerCalc requires WooCommerce to be installed and active.', 'bannercalc' );
            echo '</p></div>';
        } );
        return false;
    }
    return true;
}

/**
 * Initialise the plugin.
 */
function bannercalc_init() {
    if ( ! bannercalc_check_dependencies() ) {
        return;
    }

    // Load core classes.
    require_once BANNERCALC_PLUGIN_DIR . 'includes/class-bannercalc.php';
    require_once BANNERCALC_PLUGIN_DIR . 'includes/class-unit-converter.php';
    require_once BANNERCALC_PLUGIN_DIR . 'includes/class-attribute-manager.php';
    require_once BANNERCALC_PLUGIN_DIR . 'includes/class-pricing-engine.php';
    require_once BANNERCALC_PLUGIN_DIR . 'includes/class-cart-handler.php';

    // Load admin classes.
    if ( is_admin() ) {
        require_once BANNERCALC_PLUGIN_DIR . 'includes/class-admin.php';
        require_once BANNERCALC_PLUGIN_DIR . 'admin/class-settings-page.php';
        require_once BANNERCALC_PLUGIN_DIR . 'admin/class-category-defaults.php';
        require_once BANNERCALC_PLUGIN_DIR . 'admin/class-product-metabox.php';
    }

    // Load frontend classes.
    if ( ! is_admin() || wp_doing_ajax() ) {
        require_once BANNERCALC_PLUGIN_DIR . 'includes/class-frontend.php';
        require_once BANNERCALC_PLUGIN_DIR . 'frontend/class-product-display.php';
    }

    // Boot the plugin.
    BannerCalc\Plugin::instance();
}
add_action( 'plugins_loaded', 'bannercalc_init', 20 );

/**
 * Declare WooCommerce HPOS compatibility.
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );
