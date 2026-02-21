<?php
/**
 * Preset Seeder — UK Popular Sizes reference data.
 *
 * Maps WooCommerce product category slugs to their most popular sizes.
 * Used by the "Import Popular Sizes" AJAX action on the Category Defaults page.
 *
 * @package BannerCalc
 */

namespace BannerCalc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PresetSeeder {

    /**
     * Register AJAX handlers.
     */
    public static function register(): void {
        add_action( 'wp_ajax_bannercalc_import_presets', [ __CLASS__, 'ajax_import_presets' ] );
        add_action( 'wp_ajax_bannercalc_get_seed_data',  [ __CLASS__, 'ajax_get_seed_data' ] );
    }

    /**
     * AJAX: Return seed data for a given category ID.
     */
    public static function ajax_get_seed_data(): void {
        check_ajax_referer( 'bannercalc_admin', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Permission denied.' );
        }

        $cat_id   = absint( $_POST['cat_id'] ?? 0 );
        $cat_term = get_term( $cat_id, 'product_cat' );

        if ( ! $cat_term || is_wp_error( $cat_term ) ) {
            wp_send_json_error( 'Category not found.' );
        }

        $slug  = $cat_term->slug;
        $sizes = self::get_sizes_for_slug( $slug );

        if ( empty( $sizes ) ) {
            wp_send_json_error( 'No reference sizes found for category "' . $cat_term->name . '". You can add sizes manually.' );
        }

        wp_send_json_success( [
            'category' => $cat_term->name,
            'slug'     => $slug,
            'count'    => count( $sizes ),
            'sizes'    => $sizes,
        ] );
    }

    /**
     * Try to match a category slug to seed data.
     *
     * Uses substring matching so "vinyl-banners", "pvc-banners", "vinyl-pvc-banners" all match.
     *
     * @param string $slug
     * @return array
     */
    public static function get_sizes_for_slug( string $slug ): array {
        $all = self::get_all_seed_data();

        // Exact match first.
        if ( isset( $all[ $slug ] ) ) {
            return $all[ $slug ];
        }

        // Keyword-based fuzzy match.
        $keyword_map = [
            'vinyl'     => 'vinyl-banners',
            'pvc'       => 'vinyl-banners',
            'mesh'      => 'mesh-banners',
            'roller'    => 'roller-banners',
            'roll-up'   => 'roller-banners',
            'pull-up'   => 'roller-banners',
            'backdrop'  => 'backdrop-stands',
            'pop-up'    => 'backdrop-stands',
            'poster'    => 'posters',
            'sticker'   => 'stickers-labels',
            'label'     => 'stickers-labels',
            'flyer'     => 'flyers-leaflets',
            'leaflet'   => 'flyers-leaflets',
        ];

        foreach ( $keyword_map as $keyword => $data_key ) {
            if ( str_contains( $slug, $keyword ) ) {
                return $all[ $data_key ] ?? [];
            }
        }

        return [];
    }

    /**
     * Full reference data — UK Popular Banner & Print Sizes.
     *
     * Each entry: label, width (display), height (display), unit, description, popularity (1–5).
     * Price is left null (auto-calculated from area rate).
     *
     * @return array<string, array<array>>
     */
    public static function get_all_seed_data(): array {
        return [

            // ──────────────────────────────────────────────
            // 1. Vinyl (PVC) Banners — primary unit: ft
            // ──────────────────────────────────────────────
            'vinyl-banners' => [
                [ 'label' => '3ft × 2ft',  'width' => 3,  'height' => 2, 'unit' => 'ft',  'description' => 'Small event banners, market stalls',            'popularity' => 5 ],
                [ 'label' => '4ft × 2ft',  'width' => 4,  'height' => 2, 'unit' => 'ft',  'description' => 'Shop fronts, promotions',                        'popularity' => 5 ],
                [ 'label' => '4ft × 3ft',  'width' => 4,  'height' => 3, 'unit' => 'ft',  'description' => 'Trade shows, exhibitions',                       'popularity' => 4 ],
                [ 'label' => '6ft × 2ft',  'width' => 6,  'height' => 2, 'unit' => 'ft',  'description' => 'Outdoor events, fences',                         'popularity' => 5 ],
                [ 'label' => '6ft × 3ft',  'width' => 6,  'height' => 3, 'unit' => 'ft',  'description' => 'Building wraps, large events',                   'popularity' => 4 ],
                [ 'label' => '8ft × 2ft',  'width' => 8,  'height' => 2, 'unit' => 'ft',  'description' => 'Street banners, shop fascias',                   'popularity' => 4 ],
                [ 'label' => '8ft × 3ft',  'width' => 8,  'height' => 3, 'unit' => 'ft',  'description' => 'Construction hoarding, festivals',               'popularity' => 4 ],
                [ 'label' => '8ft × 4ft',  'width' => 8,  'height' => 4, 'unit' => 'ft',  'description' => 'Stage backdrops, large displays',                'popularity' => 3 ],
                [ 'label' => '10ft × 3ft', 'width' => 10, 'height' => 3, 'unit' => 'ft',  'description' => 'Warehouse sales, grand openings',                'popularity' => 3 ],
                [ 'label' => '12ft × 3ft', 'width' => 12, 'height' => 3, 'unit' => 'ft',  'description' => 'Building frontage, sport events',                'popularity' => 3 ],
            ],

            // ──────────────────────────────────────────────
            // 2. Mesh Banners — primary unit: ft
            // ──────────────────────────────────────────────
            'mesh-banners' => [
                [ 'label' => '4ft × 2ft',  'width' => 4,  'height' => 2, 'unit' => 'ft',  'description' => 'Fence banners, scaffolding',                     'popularity' => 4 ],
                [ 'label' => '6ft × 3ft',  'width' => 6,  'height' => 3, 'unit' => 'ft',  'description' => 'Construction sites, scaffolding',                'popularity' => 5 ],
                [ 'label' => '8ft × 3ft',  'width' => 8,  'height' => 3, 'unit' => 'ft',  'description' => 'Building wraps, hoardings',                      'popularity' => 5 ],
                [ 'label' => '8ft × 4ft',  'width' => 8,  'height' => 4, 'unit' => 'ft',  'description' => 'Large scaffolding wraps',                        'popularity' => 4 ],
                [ 'label' => '10ft × 4ft', 'width' => 10, 'height' => 4, 'unit' => 'ft',  'description' => 'Stadium banners, large events',                  'popularity' => 3 ],
                [ 'label' => '12ft × 4ft', 'width' => 12, 'height' => 4, 'unit' => 'ft',  'description' => 'Full building wraps',                            'popularity' => 3 ],
                [ 'label' => '16ft × 5ft', 'width' => 16, 'height' => 5, 'unit' => 'ft',  'description' => 'Large-scale outdoor advertising',                'popularity' => 3 ],
                [ 'label' => '20ft × 6ft', 'width' => 20, 'height' => 6, 'unit' => 'ft',  'description' => 'Major construction projects',                    'popularity' => 2 ],
            ],

            // ──────────────────────────────────────────────
            // 3. Roller Banners (Pull-Up / Roll-Up) — primary unit: mm
            // ──────────────────────────────────────────────
            'roller-banners' => [
                [ 'label' => '600 × 1600mm (Mini)',         'width' => 600,  'height' => 1600, 'unit' => 'mm', 'description' => 'Reception desks, tabletop displays',          'popularity' => 3 ],
                [ 'label' => '800 × 2000mm (Standard)',     'width' => 800,  'height' => 2000, 'unit' => 'mm', 'description' => 'Most popular all-purpose size',               'popularity' => 5 ],
                [ 'label' => '850 × 2000mm (Standard)',     'width' => 850,  'height' => 2000, 'unit' => 'mm', 'description' => 'Trade shows, exhibitions, retail',            'popularity' => 5 ],
                [ 'label' => '1000 × 2000mm (Wide)',        'width' => 1000, 'height' => 2000, 'unit' => 'mm', 'description' => 'High-impact displays, conferences',           'popularity' => 4 ],
                [ 'label' => '1200 × 2000mm (Extra Wide)',  'width' => 1200, 'height' => 2000, 'unit' => 'mm', 'description' => 'Premium exhibition spaces',                   'popularity' => 3 ],
                [ 'label' => '1500 × 2000mm (Extra Wide)',  'width' => 1500, 'height' => 2000, 'unit' => 'mm', 'description' => 'Stage backdrops, large events',               'popularity' => 2 ],
                [ 'label' => '2000 × 2000mm (Double-Sided)', 'width' => 2000, 'height' => 2000, 'unit' => 'mm', 'description' => 'Island displays, central areas',            'popularity' => 2 ],
            ],

            // ──────────────────────────────────────────────
            // 4. Backdrop Stands — primary unit: mm
            // ──────────────────────────────────────────────
            'backdrop-stands' => [
                [ 'label' => '2000 × 2000mm (Pop-Up)',  'width' => 2000, 'height' => 2000, 'unit' => 'mm', 'description' => 'Small photo backdrops, interviews',             'popularity' => 3 ],
                [ 'label' => '2400 × 2000mm (Pop-Up)',  'width' => 2400, 'height' => 2000, 'unit' => 'mm', 'description' => 'Standard photo walls, press walls',             'popularity' => 5 ],
                [ 'label' => '3000 × 2000mm (Pop-Up)',  'width' => 3000, 'height' => 2000, 'unit' => 'mm', 'description' => 'Step & repeat, red carpet events',              'popularity' => 5 ],
                [ 'label' => '3000 × 2250mm (Pop-Up)',  'width' => 3000, 'height' => 2250, 'unit' => 'mm', 'description' => 'Large events, exhibition stands',               'popularity' => 4 ],
                [ 'label' => '4000 × 2250mm (Pop-Up)',  'width' => 4000, 'height' => 2250, 'unit' => 'mm', 'description' => 'Trade shows, large exhibitions',                'popularity' => 3 ],
                [ 'label' => '5000 × 2250mm (Pop-Up)',  'width' => 5000, 'height' => 2250, 'unit' => 'mm', 'description' => 'Conference stages, wide backdrops',             'popularity' => 2 ],
                [ 'label' => '2000 × 2000mm (Fabric)',  'width' => 2000, 'height' => 2000, 'unit' => 'mm', 'description' => 'Portable displays, retail',                     'popularity' => 4 ],
                [ 'label' => '3000 × 2250mm (Fabric)',  'width' => 3000, 'height' => 2250, 'unit' => 'mm', 'description' => 'Premium exhibitions, stages',                   'popularity' => 4 ],
            ],

            // ──────────────────────────────────────────────
            // 5. Posters — primary unit: mm (ISO A-series)
            // ──────────────────────────────────────────────
            'posters' => [
                [ 'label' => 'A5 (148 × 210mm)',       'width' => 148,  'height' => 210,  'unit' => 'mm', 'description' => 'Flyer-size posters, handouts',                   'popularity' => 3 ],
                [ 'label' => 'A4 (210 × 297mm)',       'width' => 210,  'height' => 297,  'unit' => 'mm', 'description' => 'Notice boards, indoor displays',                 'popularity' => 4 ],
                [ 'label' => 'A3 (297 × 420mm)',       'width' => 297,  'height' => 420,  'unit' => 'mm', 'description' => 'Window posters, small retail',                   'popularity' => 5 ],
                [ 'label' => 'A2 (420 × 594mm)',       'width' => 420,  'height' => 594,  'unit' => 'mm', 'description' => 'Event posters, cinema, retail',                  'popularity' => 5 ],
                [ 'label' => 'A1 (594 × 841mm)',       'width' => 594,  'height' => 841,  'unit' => 'mm', 'description' => 'Billboard posters, exhibitions',                 'popularity' => 4 ],
                [ 'label' => 'A0 (841 × 1189mm)',      'width' => 841,  'height' => 1189, 'unit' => 'mm', 'description' => 'Large displays, presentations',                  'popularity' => 3 ],
                [ 'label' => '20″ × 30″ (508 × 762mm)', 'width' => 20,  'height' => 30,  'unit' => 'inch', 'description' => 'US-style movie posters',                       'popularity' => 3 ],
                [ 'label' => '40″ × 30″ UK Quad',      'width' => 40,   'height' => 30,  'unit' => 'inch', 'description' => 'UK Quad cinema posters',                       'popularity' => 3 ],
            ],

            // ──────────────────────────────────────────────
            // 6. Stickers & Labels — primary unit: mm
            // ──────────────────────────────────────────────
            'stickers-labels' => [
                [ 'label' => 'Circle 25mm',             'width' => 25,   'height' => 25,   'unit' => 'mm', 'description' => 'Product labels, seals',                          'popularity' => 5 ],
                [ 'label' => 'Circle 37mm',             'width' => 37,   'height' => 37,   'unit' => 'mm', 'description' => 'Promotional stickers, badges',                   'popularity' => 5 ],
                [ 'label' => 'Circle 51mm',             'width' => 51,   'height' => 51,   'unit' => 'mm', 'description' => 'Branding, packaging',                            'popularity' => 4 ],
                [ 'label' => 'Circle 76mm',             'width' => 76,   'height' => 76,   'unit' => 'mm', 'description' => 'Bumper stickers, laptop',                        'popularity' => 4 ],
                [ 'label' => 'Square 50 × 50mm',       'width' => 50,   'height' => 50,   'unit' => 'mm', 'description' => 'Logo stickers, branding',                        'popularity' => 5 ],
                [ 'label' => 'Square 75 × 75mm',       'width' => 75,   'height' => 75,   'unit' => 'mm', 'description' => 'Product labels, promotions',                     'popularity' => 4 ],
                [ 'label' => 'Rectangle 50 × 25mm',    'width' => 50,   'height' => 25,   'unit' => 'mm', 'description' => 'Address labels, tags',                           'popularity' => 4 ],
                [ 'label' => 'Rectangle 90 × 50mm',    'width' => 90,   'height' => 50,   'unit' => 'mm', 'description' => 'Business card size labels',                      'popularity' => 4 ],
                [ 'label' => 'Rectangle 100 × 70mm',   'width' => 100,  'height' => 70,   'unit' => 'mm', 'description' => 'Packaging labels, wine labels',                  'popularity' => 3 ],
                [ 'label' => 'A6 Rectangle (148 × 105mm)', 'width' => 148, 'height' => 105, 'unit' => 'mm', 'description' => 'Bumper stickers, window stickers',             'popularity' => 3 ],
            ],

            // ──────────────────────────────────────────────
            // 7. Flyers & Leaflets — primary unit: mm
            // ──────────────────────────────────────────────
            'flyers-leaflets' => [
                [ 'label' => 'A7 (74 × 105mm)',         'width' => 74,   'height' => 105,  'unit' => 'mm', 'description' => 'Mini flyers, coupons, tickets',                  'popularity' => 3 ],
                [ 'label' => 'DL (99 × 210mm)',         'width' => 99,   'height' => 210,  'unit' => 'mm', 'description' => 'Rack cards, leaflets, menus',                    'popularity' => 5 ],
                [ 'label' => 'A6 (105 × 148mm)',        'width' => 105,  'height' => 148,  'unit' => 'mm', 'description' => 'Postcard flyers, invitations',                   'popularity' => 4 ],
                [ 'label' => 'A5 (148 × 210mm)',        'width' => 148,  'height' => 210,  'unit' => 'mm', 'description' => 'Most popular flyer size in UK',                  'popularity' => 5 ],
                [ 'label' => 'A4 (210 × 297mm)',        'width' => 210,  'height' => 297,  'unit' => 'mm', 'description' => 'Informational flyers, menus',                    'popularity' => 5 ],
                [ 'label' => 'A3 (297 × 420mm)',        'width' => 297,  'height' => 420,  'unit' => 'mm', 'description' => 'Folded brochures, large flyers',                 'popularity' => 3 ],
                [ 'label' => 'Square 148mm',            'width' => 148,  'height' => 148,  'unit' => 'mm', 'description' => 'Social media style, modern look',                'popularity' => 3 ],
                [ 'label' => 'Square 210mm',            'width' => 210,  'height' => 210,  'unit' => 'mm', 'description' => 'Premium brochures, lookbooks',                   'popularity' => 2 ],
            ],
        ];
    }
}
