<?php
/**
 * Size selector template — preset sizes + custom size input.
 *
 * @package BannerCalc
 * @var string $sizing_mode
 * @var string $default_unit
 * @var array  $available_units
 * @var array  $preset_sizes
 * @var string $currency
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$show_presets = in_array( $sizing_mode, [ 'preset_only', 'preset_and_custom' ], true );
$show_custom  = in_array( $sizing_mode, [ 'custom_only', 'preset_and_custom' ], true );
?>

<div class="bannercalc-section-overline"><?php esc_html_e( 'SIZE', 'bannercalc' ); ?></div>
<h3 class="bannercalc-section-title"><?php esc_html_e( 'Select Size', 'bannercalc' ); ?></h3>

<?php if ( $show_presets && $show_custom ) : ?>
    <!-- Mode toggle -->
    <div class="bannercalc-size-toggle">
        <button type="button" class="bannercalc-toggle-btn active" data-mode="preset">
            <?php esc_html_e( 'Popular Sizes', 'bannercalc' ); ?>
        </button>
        <button type="button" class="bannercalc-toggle-btn" data-mode="custom">
            <?php esc_html_e( 'Custom Size', 'bannercalc' ); ?>
        </button>
    </div>
<?php endif; ?>

<?php if ( $show_presets ) : ?>
    <!-- Preset sizes grid -->
    <div class="bannercalc-preset-sizes" id="bannercalc-presets" <?php echo ( $show_presets && $show_custom ) ? '' : ''; ?>>
        <div class="bannercalc-preset-grid">
            <?php foreach ( $preset_sizes as $preset ) : ?>
                <button type="button"
                        class="bannercalc-preset-card"
                        data-slug="<?php echo esc_attr( $preset['slug'] ); ?>"
                        data-width-m="<?php echo esc_attr( $preset['width_m'] ); ?>"
                        data-height-m="<?php echo esc_attr( $preset['height_m'] ); ?>"
                        data-price="<?php echo esc_attr( $preset['price'] ?? '' ); ?>">
                    <span class="bannercalc-preset-label"><?php echo esc_html( $preset['label'] ); ?></span>
                    <span class="bannercalc-preset-price"></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ( $show_custom ) : ?>
    <!-- Custom size input -->
    <div class="bannercalc-custom-size" id="bannercalc-custom" <?php echo ( $show_presets && $show_custom ) ? 'style="display:none;"' : ''; ?>>

        <?php include BANNERCALC_PLUGIN_DIR . 'frontend/views/unit-selector.php'; ?>

        <div class="bannercalc-dimension-inputs">
            <div class="bannercalc-dimension-field">
                <label for="bannercalc-width"><?php esc_html_e( 'Width', 'bannercalc' ); ?></label>
                <div class="bannercalc-input-group">
                    <input type="number"
                           id="bannercalc-width"
                           class="bannercalc-dim-input"
                           step="0.01"
                           min="0.01"
                           placeholder="0.00"
                           data-dimension="width" />
                    <span class="bannercalc-unit-label" id="bannercalc-width-unit"><?php echo esc_html( $default_unit ); ?></span>
                </div>
            </div>

            <span class="bannercalc-dimension-separator">&times;</span>

            <div class="bannercalc-dimension-field">
                <label for="bannercalc-height"><?php esc_html_e( 'Height', 'bannercalc' ); ?></label>
                <div class="bannercalc-input-group">
                    <input type="number"
                           id="bannercalc-height"
                           class="bannercalc-dim-input"
                           step="0.01"
                           min="0.01"
                           placeholder="0.00"
                           data-dimension="height" />
                    <span class="bannercalc-unit-label" id="bannercalc-height-unit"><?php echo esc_html( $default_unit ); ?></span>
                </div>
            </div>
        </div>

        <div class="bannercalc-constraints" id="bannercalc-constraints">
            <!-- Populated by JS with min/max in selected unit -->
        </div>

        <div class="bannercalc-area-display" id="bannercalc-area">
            <!-- Populated by JS with calculated area -->
        </div>
    </div>
<?php endif; ?>
