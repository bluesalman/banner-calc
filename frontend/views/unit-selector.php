<?php
/**
 * Unit selector buttons template.
 *
 * @package BannerCalc
 * @var string $default_unit
 * @var array  $available_units
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$unit_abbrs = [
    'mm'   => 'mm',
    'cm'   => 'cm',
    'inch' => 'in',
    'ft'   => 'ft',
    'm'    => 'm',
];
?>

<div class="bannercalc-unit-selector">
    <?php foreach ( $available_units as $unit ) :
        $is_default = ( $unit === $default_unit );
    ?>
        <button type="button"
                class="bannercalc-unit-btn <?php echo $is_default ? 'active' : ''; ?>"
                data-unit="<?php echo esc_attr( $unit ); ?>">
            <?php echo esc_html( $unit_abbrs[ $unit ] ?? $unit ); ?>
        </button>
    <?php endforeach; ?>
</div>
