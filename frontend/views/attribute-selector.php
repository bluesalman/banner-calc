<?php
/**
 * Attribute selector template — renders a single attribute's UI.
 *
 * Uses styled dropdowns for eyelets and pole pockets (many options),
 * compact pills for all other attributes.
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

// Determine whether to use dropdown (for complex attributes with many options).
$dropdown_attrs = [ 'pa_eyelets', 'pa_pole-pockets' ];
$use_dropdown   = in_array( $taxonomy, $dropdown_attrs, true ) || count( $terms ) > 8;
?>

<div class="bannercalc-attribute bannercalc-attr--<?php echo esc_attr( sanitize_html_class( $taxonomy ) ); ?>"
     data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
     data-required="<?php echo $required ? '1' : '0'; ?>"
     data-display="<?php echo esc_attr( $use_dropdown ? 'dropdown' : 'pills' ); ?>">

    <div class="bannercalc-attr-header">
        <span class="bannercalc-attr-label">
            <?php echo esc_html( $attr_label ); ?>
            <?php if ( $required ) : ?>
                <span class="bannercalc-required">*</span>
            <?php endif; ?>
        </span>
        <?php if ( ! $use_dropdown ) : ?>
            <span class="bannercalc-attr-selected" id="bannercalc-selected-<?php echo esc_attr( $taxonomy ); ?>">
                <?php
                if ( $default_value ) {
                    $def_term = get_term_by( 'slug', $default_value, $taxonomy );
                    echo $def_term ? esc_html( $def_term->name ) : '';
                }
                ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if ( $use_dropdown ) : ?>
        <!-- Styled dropdown for complex attributes -->
        <div class="bannercalc-attr-dropdown-wrap">
            <select class="bannercalc-attr-dropdown"
                    data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>">
                <option value=""><?php echo esc_html( sprintf( __( 'Select %s…', 'bannercalc' ), $attr_label ) ); ?></option>
                <?php foreach ( $terms as $term ) :
                    $slug     = $term['slug'];
                    $modifier = (float) ( $pricing_values[ $slug ] ?? 0 );
                    $price_hint = '';
                    if ( $modifier > 0 ) {
                        $price_hint = ' (+' . $currency . number_format( $modifier, 2 ) . ')';
                    }
                ?>
                    <option value="<?php echo esc_attr( $slug ); ?>"
                            data-modifier="<?php echo esc_attr( $modifier ); ?>"
                            <?php selected( $slug, $default_value ); ?>>
                        <?php echo esc_html( $term['name'] . $price_hint ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php else : ?>
        <!-- Compact pill buttons -->
        <div class="bannercalc-attr-pills">
            <?php foreach ( $terms as $term ) :
                $slug      = $term['slug'];
                $modifier  = (float) ( $pricing_values[ $slug ] ?? 0 );
                $is_active = ( $slug === $default_value );
            ?>
                <button type="button"
                        class="bannercalc-pill <?php echo $is_active ? 'active' : ''; ?>"
                        data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
                        data-slug="<?php echo esc_attr( $slug ); ?>"
                        data-modifier="<?php echo esc_attr( $modifier ); ?>">
                    <?php echo esc_html( $term['name'] ); ?>
                    <?php if ( $modifier > 0 ) : ?>
                        <span class="bannercalc-pill-price">+<?php echo esc_html( $currency . number_format( $modifier, 2 ) ); ?></span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Hidden input to submit selected value -->
    <input type="hidden"
           name="bannercalc[attributes][<?php echo esc_attr( $taxonomy ); ?>]"
           value="<?php echo esc_attr( $default_value ); ?>"
           class="bannercalc-attr-input"
           data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" />
</div>
