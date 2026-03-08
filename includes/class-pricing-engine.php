<?php
/**
 * Pricing Engine — all price calculation logic.
 *
 * @package BannerCalc
 */

namespace BannerCalc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PricingEngine {

    /**
     * @var UnitConverter
     */
    private UnitConverter $units;

    public function __construct( UnitConverter $units ) {
        $this->units = $units;
    }

    /**
     * Calculate the full price for a product configuration.
     *
     * @param array $config              Resolved product config.
     * @param float $width_m             Width in metres.
     * @param float $height_m            Height in metres.
     * @param array $selected_attributes [ 'pa_hemming' => 'yes', ... ]
     * @param array|null $preset         Preset size data if a preset was selected.
     * @return array {
     *     area_sqft: float,
     *     area_sqm: float,
     *     base_price: float,
     *     addons: array,
     *     addons_total: float,
     *     calculated_price: float,
     * }
     */
    public function calculate( array $config, float $width_m, float $height_m, array $selected_attributes, ?array $preset = null, string $service_type = 'standard', bool $design_service = false ): array {
        // Calculate area.
        $area_sqft = $this->units->area_sqft( $width_m, $height_m );
        $area_sqm  = $this->units->area_sqm( $width_m, $height_m );

        // Base price from area × rate.
        $rate       = (float) ( $config['area_rate_sqft'] ?? 0 );
        $base_price = $area_sqft * $rate;

        // Apply minimum charge.
        $min_charge = (float) ( $config['minimum_charge'] ?? 0 );
        if ( $base_price < $min_charge ) {
            $base_price = $min_charge;
        }

        // Price override for preset sizes.
        if ( $preset && isset( $preset['price'] ) && $preset['price'] !== null ) {
            $base_price = (float) $preset['price'];
        }

        // Calculate add-ons.
        $addons       = [];
        $addons_total = 0.0;

        $attribute_pricing = $config['attribute_pricing'] ?? [];

        foreach ( $selected_attributes as $taxonomy => $term_slug ) {
            if ( ! isset( $attribute_pricing[ $taxonomy ] ) ) {
                continue;
            }

            $attr_config  = $attribute_pricing[ $taxonomy ];
            $pricing_type = $attr_config['pricing_type'] ?? 'fixed';
            $values       = $attr_config['values'] ?? [];
            $modifier     = (float) ( $values[ $term_slug ] ?? 0 );

            $addon_price = match ( $pricing_type ) {
                'per_sqft'   => $modifier * $area_sqft,
                'percentage' => $base_price * ( $modifier / 100 ),
                default      => $modifier, // 'fixed'
            };

            $addons[ $taxonomy ] = [
                'slug'         => $term_slug,
                'pricing_type' => $pricing_type,
                'modifier'     => $modifier,
                'price'        => round( $addon_price, 2 ),
            ];

            $addons_total += $addon_price;
        }

        // Design service add-on (added to addons_total so it's subject to service markup).
        $design_service_price = 0.0;
        if ( $design_service ) {
            $global_settings      = \BannerCalc\Plugin::get_settings();
            $ds_config            = $global_settings['design_service'] ?? [];
            $design_service_price = ( ! empty( $ds_config['enabled'] ) ) ? (float) ( $ds_config['price'] ?? 0 ) : 0.0;
            $addons_total        += $design_service_price;
        }

        // Service type markup.
        $service_markup_pct = 0.0;
        $service_markup_amt = 0.0;

        if ( $service_type !== 'standard' ) {
            $global_settings = $global_settings ?? \BannerCalc\Plugin::get_settings();
            $service_types   = $global_settings['service_types'] ?? [];
            foreach ( $service_types as $st ) {
                if ( ( $st['slug'] ?? '' ) === $service_type ) {
                    $service_markup_pct = (float) ( $st['markup'] ?? 0 );
                    break;
                }
            }
            if ( $service_markup_pct > 0 ) {
                $service_markup_amt = ( $base_price + $addons_total ) * ( $service_markup_pct / 100 );
            }
        }

        $calculated_price = $base_price + $addons_total + $service_markup_amt;

        return [
            'area_sqft'           => round( $area_sqft, 4 ),
            'area_sqm'            => round( $area_sqm, 4 ),
            'base_price'          => round( $base_price, 2 ),
            'addons'              => $addons,
            'addons_total'        => round( $addons_total, 2 ),
            'design_service'      => $design_service,
            'design_service_price' => round( $design_service_price, 2 ),
            'service_type'        => $service_type,
            'service_markup_pct'  => round( $service_markup_pct, 2 ),
            'service_markup_amt'  => round( $service_markup_amt, 2 ),
            'calculated_price'    => round( $calculated_price, 2 ),
        ];
    }

    /**
     * Validate dimensions against config constraints.
     *
     * @param array $config
     * @param float $width_m
     * @param float $height_m
     * @return array List of validation error strings. Empty = valid.
     */
    public function validate_dimensions( array $config, float $width_m, float $height_m ): array {
        $errors = [];

        if ( $width_m <= 0 || $height_m <= 0 ) {
            $errors[] = __( 'Width and height must be positive values.', 'bannercalc' );
            return $errors;
        }

        $min_w = (float) ( $config['min_width_m'] ?? 0 );
        $min_h = (float) ( $config['min_height_m'] ?? 0 );
        $max_w = (float) ( $config['max_width_m'] ?? PHP_FLOAT_MAX );
        $max_h = (float) ( $config['max_height_m'] ?? PHP_FLOAT_MAX );

        if ( $width_m < $min_w ) {
            $errors[] = sprintf( __( 'Width is below the minimum of %s metres.', 'bannercalc' ), $min_w );
        }
        if ( $height_m < $min_h ) {
            $errors[] = sprintf( __( 'Height is below the minimum of %s metres.', 'bannercalc' ), $min_h );
        }
        if ( $width_m > $max_w ) {
            $errors[] = sprintf( __( 'Width exceeds the maximum of %s metres.', 'bannercalc' ), $max_w );
        }
        if ( $height_m > $max_h ) {
            $errors[] = sprintf( __( 'Height exceeds the maximum of %s metres.', 'bannercalc' ), $max_h );
        }

        return $errors;
    }
}
