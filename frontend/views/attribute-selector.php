<?php
/**
 * Attribute selector template — renders a single attribute's button group.
 *
 * This file is included inside a loop, once per attribute.
 *
 * @package BannerCalc
 * @var string $taxonomy     The attribute taxonomy (e.g. 'pa_hemming').
 * @var array  $attr_config  Pricing config for this attribute.
 * @var array  $terms        All terms for this attribute.
 * @var string $currency     Currency symbol.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$pricing_values = $attr_config['values'] ?? [];
$default_value  = $attr_config['default_value'] ?? '';
$required       = ! empty( $attr_config['required'] );
$attr_label     = wc_attribute_label( $taxonomy );
?>

<div class="bannercalc-attribute"
     data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
     data-required="<?php echo $required ? '1' : '0'; ?>">

    <h4 class="bannercalc-attribute-label">
        <?php echo esc_html( $attr_label ); ?>
        <?php if ( $required ) : ?>
            <span class="bannercalc-required">*</span>
        <?php endif; ?>
    </h4>

    <div class="bannercalc-attribute-options">
        <?php foreach ( $terms as $term ) :
            $slug      = $term['slug'];
            $modifier  = (float) ( $pricing_values[ $slug ] ?? 0 );
            $is_active = ( $slug === $default_value );
        ?>
            <button type="button"
                    class="bannercalc-attr-btn <?php echo $is_active ? 'active' : ''; ?>"
                    data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
                    data-slug="<?php echo esc_attr( $slug ); ?>"
                    data-modifier="<?php echo esc_attr( $modifier ); ?>">
                <span class="bannercalc-attr-name"><?php echo esc_html( $term['name'] ); ?></span>
                <?php if ( $modifier > 0 ) : ?>
                    <span class="bannercalc-attr-price">+<?php echo esc_html( $currency . number_format( $modifier, 2 ) ); ?></span>
                <?php endif; ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Hidden input to submit selected value -->
    <input type="hidden"
           name="bannercalc[attributes][<?php echo esc_attr( $taxonomy ); ?>]"
           value="<?php echo esc_attr( $default_value ); ?>"
           class="bannercalc-attr-input"
           data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" />
</div>
