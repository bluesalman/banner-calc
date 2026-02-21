<?php
/**
 * Dashboard page HTML template.
 *
 * Overview of plugin status, quick links, and category summary.
 *
 * @package BannerCalc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings   = \BannerCalc\Plugin::get_settings();
$is_enabled = ! empty( $settings['enabled'] );

// Gather category stats.
$categories = get_terms( [
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
] );

$total_cats    = 0;
$enabled_cats  = 0;
$cat_summaries = [];

if ( ! is_wp_error( $categories ) ) {
    $total_cats = count( $categories );
    foreach ( $categories as $cat ) {
        $cat_config = get_term_meta( $cat->term_id, '_bannercalc_config', true );
        if ( ! empty( $cat_config['enabled'] ) ) {
            $enabled_cats++;
            $cat_summaries[] = $cat->name;
        }
    }
}
?>
<div class="wrap bannercalc-admin-wrap bannercalc-dashboard">

    <!-- CMYK Brand Bar -->
    <div class="bannercalc-cmyk-bar"><span></span><span></span><span></span><span></span></div>

    <!-- Page Header -->
    <div class="bannercalc-page-header">
        <div class="bannercalc-admin-overline"><?php esc_html_e( 'DASHBOARD', 'bannercalc' ); ?></div>
        <h1 class="bannercalc-page-title"><?php esc_html_e( 'BannerCalc', 'bannercalc' ); ?></h1>
        <p class="bannercalc-page-desc"><?php esc_html_e( 'Product Configurator & Pricing Engine for WooCommerce', 'bannercalc' ); ?></p>
    </div>

    <!-- Status Cards Row -->
    <div class="bannercalc-cards-row">

        <!-- Plugin Status -->
        <div class="bannercalc-card">
            <div class="bannercalc-card-icon">
                <?php if ( $is_enabled ) : ?>
                    <span class="bannercalc-status-dot bannercalc-status-active"></span>
                <?php else : ?>
                    <span class="bannercalc-status-dot bannercalc-status-inactive"></span>
                <?php endif; ?>
            </div>
            <div class="bannercalc-card-body">
                <div class="bannercalc-card-label"><?php esc_html_e( 'Plugin Status', 'bannercalc' ); ?></div>
                <div class="bannercalc-card-value <?php echo $is_enabled ? 'bannercalc-text-success' : 'bannercalc-text-muted'; ?>">
                    <?php echo $is_enabled ? esc_html__( 'Active', 'bannercalc' ) : esc_html__( 'Disabled', 'bannercalc' ); ?>
                </div>
            </div>
        </div>

        <!-- Category Count -->
        <div class="bannercalc-card">
            <div class="bannercalc-card-icon">
                <span class="dashicons dashicons-category"></span>
            </div>
            <div class="bannercalc-card-body">
                <div class="bannercalc-card-label"><?php esc_html_e( 'Configured Categories', 'bannercalc' ); ?></div>
                <div class="bannercalc-card-value">
                    <?php
                    printf(
                        /* translators: %1$d: enabled categories, %2$d: total categories */
                        esc_html__( '%1$d of %2$d', 'bannercalc' ),
                        $enabled_cats,
                        $total_cats
                    );
                    ?>
                </div>
            </div>
        </div>

        <!-- Default Unit -->
        <div class="bannercalc-card">
            <div class="bannercalc-card-icon">
                <span class="dashicons dashicons-editor-expand"></span>
            </div>
            <div class="bannercalc-card-body">
                <div class="bannercalc-card-label"><?php esc_html_e( 'Default Unit', 'bannercalc' ); ?></div>
                <div class="bannercalc-card-value">
                    <?php echo esc_html( strtoupper( $settings['default_unit'] ?? 'ft' ) ); ?>
                </div>
            </div>
        </div>

        <!-- Currency -->
        <div class="bannercalc-card">
            <div class="bannercalc-card-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="bannercalc-card-body">
                <div class="bannercalc-card-label"><?php esc_html_e( 'Currency', 'bannercalc' ); ?></div>
                <div class="bannercalc-card-value">
                    <?php echo esc_html( $settings['currency_symbol'] ?? '£' ); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="bannercalc-section-block">
        <h2 class="bannercalc-section-heading"><?php esc_html_e( 'Quick Links', 'bannercalc' ); ?></h2>
        <div class="bannercalc-quick-links">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=bannercalc-settings' ) ); ?>" class="bannercalc-quick-link">
                <span class="dashicons dashicons-admin-settings"></span>
                <span><?php esc_html_e( 'Global Settings', 'bannercalc' ); ?></span>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=bannercalc-categories' ) ); ?>" class="bannercalc-quick-link">
                <span class="dashicons dashicons-category"></span>
                <span><?php esc_html_e( 'Category Defaults', 'bannercalc' ); ?></span>
            </a>
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="bannercalc-quick-link">
                <span class="dashicons dashicons-products"></span>
                <span><?php esc_html_e( 'Products', 'bannercalc' ); ?></span>
            </a>
        </div>
    </div>

    <!-- Enabled Categories Summary -->
    <?php if ( $enabled_cats > 0 ) : ?>
    <div class="bannercalc-section-block">
        <h2 class="bannercalc-section-heading"><?php esc_html_e( 'Active Categories', 'bannercalc' ); ?></h2>
        <div class="bannercalc-tag-list">
            <?php foreach ( $cat_summaries as $cat_name ) : ?>
                <span class="bannercalc-tag"><?php echo esc_html( $cat_name ); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif ( $total_cats > 0 ) : ?>
    <div class="bannercalc-section-block">
        <div class="bannercalc-notice bannercalc-notice-info">
            <span class="dashicons dashicons-info"></span>
            <?php
            printf(
                /* translators: %s: link to category defaults page */
                esc_html__( 'No categories configured yet. %s to get started.', 'bannercalc' ),
                '<a href="' . esc_url( admin_url( 'admin.php?page=bannercalc-categories' ) ) . '">' . esc_html__( 'Configure Category Defaults', 'bannercalc' ) . '</a>'
            );
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Version Footer -->
    <div class="bannercalc-admin-footer">
        <span class="bannercalc-version-badge">v<?php echo esc_html( BANNERCALC_VERSION ); ?></span>
        <span class="bannercalc-footer-text"><?php esc_html_e( 'BannerCalc — Product Configurator & Pricing Engine', 'bannercalc' ); ?></span>
    </div>
</div>
