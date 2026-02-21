<?php
/**
 * Attribute selector template — renders a single attribute's UI.
 *
 * Display modes:
 *  - Toggle switch: for yes/no attributes (cable ties, etc.) with exactly 2 terms.
 *  - Styled dropdown: for eyelets, pole pockets, or attributes with 8+ terms.
 *  - Compact pills: for all other attributes.
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

// Icon mapping — dashicons class + brand colour per attribute.
$attr_icon_map = [
    'pa_hemming'      => [ 'dashicons-image-crop',        'var(--bp-cyan)' ],
    'pa_packaging'    => [ 'dashicons-portfolio',          'var(--bp-orange)' ],
    'pa_finish'       => [ 'dashicons-admin-appearance',   'var(--bp-magenta)' ],
    'pa_cable-ties'   => [ 'dashicons-admin-links',        'var(--bp-cyan-dark)' ],
    'pa_eyelets'      => [ 'dashicons-visibility',         'var(--bp-green)' ],
    'pa_pole-pockets' => [ 'dashicons-align-center',       'var(--bp-magenta)' ],
];
$icon_entry = $attr_icon_map[ $taxonomy ] ?? null;

// Determine display mode.
$toggle_attrs   = [ 'pa_cable-ties' ];
$dropdown_attrs = [ 'pa_eyelets', 'pa_pole-pockets' ];

// Auto-detect yes/no attributes: exactly 2 terms with common boolean slugs.
$bool_slugs   = [ 'yes', 'no', 'none', 'included', 'with', 'without' ];
$is_bool_attr = ( count( $terms ) === 2 && (
    in_array( $taxonomy, $toggle_attrs, true ) ||
    count( array_intersect( array_column( $terms, 'slug' ), $bool_slugs ) ) >= 1
) );

if ( $is_bool_attr ) {
    $display_mode = 'toggle';
} elseif ( in_array( $taxonomy, $dropdown_attrs, true ) || count( $terms ) > 8 ) {
    $display_mode = 'dropdown';
} else {
    $display_mode = 'pills';
}

// For toggle: determine which term is the "on" value and which is "off".
if ( $display_mode === 'toggle' ) {
    $on_term  = null;
    $off_term = null;
    $off_slugs = [ 'no', 'none', 'without' ];
    foreach ( $terms as $t ) {
        if ( in_array( $t['slug'], $off_slugs, true ) ) {
            $off_term = $t;
        } else {
            $on_term = $t;
        }
    }
    // Fallback: first term = off, second = on.
    if ( ! $on_term || ! $off_term ) {
        $off_term = $terms[0];
        $on_term  = $terms[1] ?? $terms[0];
    }
    $on_modifier  = (float) ( $pricing_values[ $on_term['slug'] ] ?? 0 );
    $is_checked   = ( $default_value === $on_term['slug'] );
}
?>

<div class="bannercalc-attribute bannercalc-attr--<?php echo esc_attr( sanitize_html_class( $taxonomy ) ); ?>"
     data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
     data-required="<?php echo $required ? '1' : '0'; ?>"
     data-display="<?php echo esc_attr( $display_mode ); ?>">

    <?php if ( $display_mode === 'toggle' ) : ?>
        <!-- Toggle switch for yes/no attributes -->
        <div class="bannercalc-toggle-switch">
            <div class="bannercalc-attr-header" style="margin-bottom:0;">
                <span class="bannercalc-attr-label">
                    <?php if ( $icon_entry ) : ?>
                        <span class="bannercalc-attr-icon dashicons <?php echo esc_attr( $icon_entry[0] ); ?>" style="color:<?php echo esc_attr( $icon_entry[1] ); ?>;"></span>
                    <?php endif; ?>
                    <?php echo esc_html( $attr_label ); ?>
                    <?php if ( $required ) : ?>
                        <span class="bannercalc-required">*</span>
                    <?php endif; ?>
                </span>
            </div>
            <label class="bannercalc-switch">
                <input type="checkbox"
                       class="bannercalc-toggle-input"
                       data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
                       data-on-slug="<?php echo esc_attr( $on_term['slug'] ); ?>"
                       data-off-slug="<?php echo esc_attr( $off_term['slug'] ); ?>"
                       data-on-modifier="<?php echo esc_attr( $on_modifier ); ?>"
                       <?php checked( $is_checked ); ?> />
                <span class="bannercalc-switch-slider"></span>
            </label>
            <span class="bannercalc-switch-label <?php echo $is_checked ? 'is-on' : ''; ?>">
                <?php echo $is_checked ? esc_html( $on_term['name'] ) : esc_html( $off_term['name'] ); ?>
            </span>
            <?php if ( $on_modifier > 0 ) : ?>
                <span class="bannercalc-switch-price">(+<?php echo esc_html( $currency . number_format( $on_modifier, 2 ) ); ?>)</span>
            <?php endif; ?>
        </div>

    <?php else : ?>
        <div class="bannercalc-attr-header">
            <span class="bannercalc-attr-label">
                <?php if ( $icon_entry ) : ?>
                    <span class="bannercalc-attr-icon dashicons <?php echo esc_attr( $icon_entry[0] ); ?>" style="color:<?php echo esc_attr( $icon_entry[1] ); ?>;"></span>
                <?php endif; ?>
                <?php echo esc_html( $attr_label ); ?>
                <?php if ( $required ) : ?>
                    <span class="bannercalc-required">*</span>
                <?php endif; ?>
            </span>
            <?php if ( $display_mode === 'pills' ) : ?>
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

        <?php if ( $display_mode === 'dropdown' ) : ?>
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
    <?php endif; ?>

    <!-- Hidden input to submit selected value -->
    <input type="hidden"
           name="bannercalc[attributes][<?php echo esc_attr( $taxonomy ); ?>]"
           value="<?php echo esc_attr( $display_mode === 'toggle' ? ( $is_checked ? $on_term['slug'] : $off_term['slug'] ) : $default_value ); ?>"
           class="bannercalc-attr-input"
           data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" />
</div>
