<?php
/**
 * Unit Converter — handles all measurement conversions.
 *
 * All conversions go through metres as the canonical unit.
 *
 * @package BannerCalc
 */

namespace BannerCalc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UnitConverter {

    /**
     * Conversion factors: unit → metres.
     *
     * @var array<string, float>
     */
    public const TO_METRES = [
        'mm'   => 0.001,
        'cm'   => 0.01,
        'inch' => 0.0254,
        'ft'   => 0.3048,
        'm'    => 1.0,
    ];

    /**
     * Square feet per square metre.
     */
    public const SQFT_PER_SQM = 10.7639;

    /**
     * Convert a value from one unit to metres.
     *
     * @param float  $value
     * @param string $from_unit
     * @return float
     */
    public function to_metres( float $value, string $from_unit ): float {
        $factor = self::TO_METRES[ $from_unit ] ?? 1.0;
        return $value * $factor;
    }

    /**
     * Convert a value from metres to a target unit.
     *
     * @param float  $value_m  Value in metres.
     * @param string $to_unit  Target unit.
     * @return float
     */
    public function from_metres( float $value_m, string $to_unit ): float {
        $factor = self::TO_METRES[ $to_unit ] ?? 1.0;
        return $value_m / $factor;
    }

    /**
     * Convert a value from one unit to another.
     *
     * @param float  $value
     * @param string $from_unit
     * @param string $to_unit
     * @return float
     */
    public function convert( float $value, string $from_unit, string $to_unit ): float {
        if ( $from_unit === $to_unit ) {
            return $value;
        }
        $metres = $this->to_metres( $value, $from_unit );
        return $this->from_metres( $metres, $to_unit );
    }

    /**
     * Calculate area in square feet from dimensions in metres.
     *
     * @param float $width_m
     * @param float $height_m
     * @return float
     */
    public function area_sqft( float $width_m, float $height_m ): float {
        return ( $width_m * $height_m ) * self::SQFT_PER_SQM;
    }

    /**
     * Calculate area in square metres from dimensions in metres.
     *
     * @param float $width_m
     * @param float $height_m
     * @return float
     */
    public function area_sqm( float $width_m, float $height_m ): float {
        return $width_m * $height_m;
    }

    /**
     * Get all supported units.
     *
     * @return array<string, string>
     */
    public static function get_unit_labels(): array {
        return [
            'mm'   => __( 'Millimetres (mm)', 'bannercalc' ),
            'cm'   => __( 'Centimetres (cm)', 'bannercalc' ),
            'inch' => __( 'Inches (in)', 'bannercalc' ),
            'ft'   => __( 'Feet (ft)', 'bannercalc' ),
            'm'    => __( 'Metres (m)', 'bannercalc' ),
        ];
    }

    /**
     * Get short label for a unit.
     *
     * @param string $unit
     * @return string
     */
    public static function get_unit_abbr( string $unit ): string {
        $abbrs = [
            'mm'   => 'mm',
            'cm'   => 'cm',
            'inch' => 'in',
            'ft'   => 'ft',
            'm'    => 'm',
        ];
        return $abbrs[ $unit ] ?? $unit;
    }

    /**
     * Validate that a unit string is supported.
     *
     * @param string $unit
     * @return bool
     */
    public static function is_valid_unit( string $unit ): bool {
        return isset( self::TO_METRES[ $unit ] );
    }
}
