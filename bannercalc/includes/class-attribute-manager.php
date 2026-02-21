<?php
/**
 * Attribute Manager — reads WooCommerce attribute taxonomies.
 *
 * @package BannerCalc
 */

namespace BannerCalc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AttributeManager {

    /**
     * Get all registered WooCommerce product attributes.
     *
     * @return array Array of attribute objects keyed by taxonomy slug.
     */
    public function get_all_attributes(): array {
        $attributes = wc_get_attribute_taxonomies();
        $result     = [];

        foreach ( $attributes as $attribute ) {
            $taxonomy            = wc_attribute_taxonomy_name( $attribute->attribute_name );
            $result[ $taxonomy ] = [
                'id'       => (int) $attribute->attribute_id,
                'name'     => $attribute->attribute_label,
                'slug'     => $attribute->attribute_name,
                'taxonomy' => $taxonomy,
                'type'     => $attribute->attribute_type,
            ];
        }

        return $result;
    }

    /**
     * Get all terms for a specific attribute taxonomy.
     *
     * @param string $taxonomy e.g. 'pa_size'.
     * @return array Array of term data.
     */
    public function get_attribute_terms( string $taxonomy ): array {
        $terms = get_terms( [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'orderby'    => 'menu_order',
            'order'      => 'ASC',
        ] );

        if ( is_wp_error( $terms ) ) {
            return [];
        }

        $result = [];
        foreach ( $terms as $term ) {
            $result[] = [
                'term_id' => $term->term_id,
                'name'    => $term->name,
                'slug'    => $term->slug,
            ];
        }

        return $result;
    }

    /**
     * Get terms assigned to a specific product for a specific attribute.
     *
     * @param int    $product_id
     * @param string $taxonomy
     * @return array
     */
    public function get_product_attribute_terms( int $product_id, string $taxonomy ): array {
        $terms = wp_get_post_terms( $product_id, $taxonomy );

        if ( is_wp_error( $terms ) ) {
            return [];
        }

        $result = [];
        foreach ( $terms as $term ) {
            $result[] = [
                'term_id' => $term->term_id,
                'name'    => $term->name,
                'slug'    => $term->slug,
            ];
        }

        return $result;
    }

    /**
     * Validate that a term slug exists in a taxonomy.
     *
     * @param string $slug
     * @param string $taxonomy
     * @return bool
     */
    public function term_exists( string $slug, string $taxonomy ): bool {
        $term = get_term_by( 'slug', $slug, $taxonomy );
        return $term !== false;
    }

    /**
     * Get human-readable label for a term slug in a taxonomy.
     *
     * @param string $slug
     * @param string $taxonomy
     * @return string
     */
    public function get_term_label( string $slug, string $taxonomy ): string {
        $term = get_term_by( 'slug', $slug, $taxonomy );
        return $term ? $term->name : $slug;
    }
}
