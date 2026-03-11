<?php
/**
 * Price display block template.
 *
 * @package BannerCalc
 * @var string $currency
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="bannercalc-price-block" id="bannercalc-price-block">
    <div class="bannercalc-price-placeholder" id="bannercalc-price-placeholder">
        <?php esc_html_e( 'Select a size to see price', 'bannercalc' ); ?>
    </div>
    <div class="bannercalc-price-details" id="bannercalc-price-details" style="display: none;">
        <div class="bannercalc-price-row bannercalc-price-base">
            <span class="bannercalc-price-label" id="bannercalc-base-label"></span>
            <span class="bannercalc-price-value" id="bannercalc-base-value"></span>
        </div>
        <div class="bannercalc-price-row bannercalc-price-addons" id="bannercalc-addons-row" style="display: none;">
            <span class="bannercalc-price-label"><?php esc_html_e( '+ Add-ons:', 'bannercalc' ); ?></span>
            <span class="bannercalc-price-value" id="bannercalc-addons-value"></span>
        </div>
        <div class="bannercalc-price-row bannercalc-price-design" id="bannercalc-design-row" style="display: none;">
            <span class="bannercalc-price-label" id="bannercalc-design-label"><?php esc_html_e( '+ Professional Design:', 'bannercalc' ); ?></span>
            <span class="bannercalc-price-value" id="bannercalc-design-value"></span>
        </div>
        <div class="bannercalc-price-row bannercalc-price-service" id="bannercalc-service-row" style="display: none;">
            <span class="bannercalc-price-label" id="bannercalc-service-label"></span>
            <span class="bannercalc-price-value" id="bannercalc-service-value"></span>
        </div>
        <div class="bannercalc-price-row bannercalc-price-qty" id="bannercalc-qty-row" style="display: none;">
            <span class="bannercalc-price-label" id="bannercalc-qty-label"></span>
            <span class="bannercalc-price-value bannercalc-price-value--muted" id="bannercalc-qty-unit-price"></span>
        </div>
        <div class="bannercalc-price-row bannercalc-price-total">
            <span class="bannercalc-price-label"><?php esc_html_e( 'Estimated Price', 'bannercalc' ); ?></span>
            <span class="bannercalc-price-value" id="bannercalc-total-value"></span>
        </div>
    </div>
</div>
