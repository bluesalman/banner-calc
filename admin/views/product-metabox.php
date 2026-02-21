<?php
/**
 * Product metabox HTML template.
 *
 * @package BannerCalc
 * @var \WP_Post $post            The product post.
 * @var array    $product_config  Per-product override config.
 * @var array    $resolved_config Resolved (merged) config.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_enabled   = ! empty( $resolved_config['enabled'] );
$has_override = ! empty( $product_config['override_enabled'] );
?>
<div class="bannercalc-metabox">
    <div class="bannercalc-cmyk-bar" style="margin-bottom: 14px;"><span></span><span></span><span></span><span></span></div>
    <?php if ( ! $is_enabled ) : ?>
        <p class="description">
            <?php esc_html_e( 'BannerCalc is not enabled for this product\'s category. Enable it on the category edit screen first.', 'bannercalc' ); ?>
        </p>
    <?php else : ?>
        <div class="bannercalc-inherited-config">
            <strong><?php esc_html_e( 'Category Config:', 'bannercalc' ); ?></strong>
            <?php
            printf(
                /* translators: %s: sizing mode */
                esc_html__( 'Sizing: %s', 'bannercalc' ),
                esc_html( $resolved_config['sizing_mode'] ?? 'N/A' )
            );
            echo ' | ';
            printf(
                /* translators: %s: rate */
                esc_html__( 'Rate: %s/sqft', 'bannercalc' ),
                esc_html( $resolved_config['area_rate_sqft'] ?? '0' )
            );
            ?>
        </div>

        <label class="bannercalc-override-label">
            <input type="checkbox"
                   name="bannercalc_product[override_enabled]"
                   value="1"
                   id="bannercalc-override-toggle"
                   <?php checked( $has_override ); ?> />
            <strong><?php esc_html_e( 'Override category defaults for this product', 'bannercalc' ); ?></strong>
        </label>

        <div id="bannercalc-override-fields" style="<?php echo $has_override ? '' : 'display:none;'; ?> margin-top: 12px;">
            <p class="description" style="margin-bottom: 10px;">
                <?php esc_html_e( 'Only fill in the fields you want to override. Empty fields will inherit from the category.', 'bannercalc' ); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Sizing Mode', 'bannercalc' ); ?></label></th>
                    <td>
                        <select name="bannercalc_product[sizing_mode]">
                            <option value=""><?php esc_html_e( '— Inherit from category —', 'bannercalc' ); ?></option>
                            <option value="preset_only" <?php selected( $product_config['sizing_mode'] ?? '', 'preset_only' ); ?>>
                                <?php esc_html_e( 'Preset Only', 'bannercalc' ); ?>
                            </option>
                            <option value="custom_only" <?php selected( $product_config['sizing_mode'] ?? '', 'custom_only' ); ?>>
                                <?php esc_html_e( 'Custom Only', 'bannercalc' ); ?>
                            </option>
                            <option value="preset_and_custom" <?php selected( $product_config['sizing_mode'] ?? '', 'preset_and_custom' ); ?>>
                                <?php esc_html_e( 'Preset + Custom', 'bannercalc' ); ?>
                            </option>
                            <option value="none" <?php selected( $product_config['sizing_mode'] ?? '', 'none' ); ?>>
                                <?php esc_html_e( 'None', 'bannercalc' ); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Area Rate (per sqft)', 'bannercalc' ); ?></label></th>
                    <td>
                        <input type="number"
                               name="bannercalc_product[area_rate_sqft]"
                               value="<?php echo esc_attr( $product_config['area_rate_sqft'] ?? '' ); ?>"
                               step="0.01"
                               min="0"
                               class="small-text"
                               placeholder="<?php echo esc_attr( $resolved_config['area_rate_sqft'] ?? '' ); ?>" />
                        <span class="description">
                            <?php printf( esc_html__( 'Category default: %s', 'bannercalc' ), esc_html( $resolved_config['area_rate_sqft'] ?? 'N/A' ) ); ?>
                        </span>
                    </td>
                </tr>
            </table>

            <p class="description">
                <?php esc_html_e( 'More override options (disabled attributes, per-term pricing, preset sizes) will be available in a future update.', 'bannercalc' ); ?>
            </p>
        </div>

        <script>
            jQuery(function($) {
                $('#bannercalc-override-toggle').on('change', function() {
                    $('#bannercalc-override-fields').toggle(this.checked);
                });
            });
        </script>
    <?php endif; ?>
</div>
