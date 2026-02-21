<?php
/**
 * Uninstall BannerCalc
 *
 * Fired when the plugin is uninstalled (deleted).
 * Cleans up all plugin data from the database.
 *
 * @package BannerCalc
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete global settings.
delete_option( 'bannercalc_settings' );

// Delete category-level configs.
$categories = get_terms( [
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'fields'     => 'ids',
] );

if ( ! is_wp_error( $categories ) ) {
    foreach ( $categories as $cat_id ) {
        delete_term_meta( $cat_id, '_bannercalc_config' );
    }
}

// Delete product-level configs.
global $wpdb;
$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => '_bannercalc_product_config' ] ); // phpcs:ignore

// Delete order item meta.
$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_bannercalc_data'" ); // phpcs:ignore
