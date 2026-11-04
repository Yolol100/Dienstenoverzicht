<?php
/**
 * Isolated WordPress shortcode and DOM contract checks.
 *
 * Run with WP-CLI in a disposable database; fixture content is removed.
 */

$post_id = wp_insert_post( array(
    'post_type'   => Dienstenoverzicht_CPT::POST_TYPE,
    'post_title'  => 'Shortcode CI service',
    'post_status' => 'publish',
), true );
if ( is_wp_error( $post_id ) || ! $post_id ) {
    throw new RuntimeException( 'Failed to create isolated service.' );
}

$term = wp_insert_term( 'Shortcode CI category', Dienstenoverzicht_Taxonomy::TAXONOMY );
if ( is_wp_error( $term ) ) {
    throw new RuntimeException( 'Could not create isolated category.' );
}
$term_id = (int) $term['term_id'];
try {
    $assigned = wp_set_object_terms( $post_id, $term_id, Dienstenoverzicht_Taxonomy::TAXONOMY );
    if ( is_wp_error( $assigned ) ) {
        throw new RuntimeException( 'Could not categorize isolated service.' );
    }

    $markup = do_shortcode( '[dienstenoverzicht][dienstenoverzicht category="' . $term_id . '"]' );
    if ( substr_count( $markup, 'class="dienstenoverzicht-container"' ) !== 2 ) {
        throw new RuntimeException( 'Two independent shortcode instances were not rendered.' );
    }
    if ( substr_count( $markup, 'Shortcode CI service' ) < 2 ) {
        throw new RuntimeException( 'Published service is not visible in both instances.' );
    }

    if ( ! preg_match_all( '/<select\s+id="([^"]+)"/', $markup, $selects ) || count( $selects[1] ) !== 2 ) {
        throw new RuntimeException( 'Both instances must have a category selector.' );
    }
    if ( count( array_unique( $selects[1] ) ) !== 2 ) {
        throw new RuntimeException( 'Duplicate category control IDs on one page.' );
    }
    if ( ! preg_match_all( '/<div\s+id="([^"]+)"\s+class="dienstenoverzicht"/', $markup, $results ) || count( $results[1] ) !== 2 ) {
        throw new RuntimeException( 'Both instances must have a results landmark.' );
    }
    if ( count( array_unique( $results[1] ) ) !== 2 ) {
        throw new RuntimeException( 'Duplicate shortcode result IDs on one page.' );
    }
    foreach ( $selects[1] as $id ) {
        if ( strpos( $markup, 'for="' . $id . '"' ) === false ) {
            throw new RuntimeException( 'Category label does not target its own control.' );
        }
    }
    echo "Dienstenoverzicht multi-shortcode and category labels: OK\n";
} finally {
    wp_delete_post( $post_id, true );
    wp_delete_term( $term_id, Dienstenoverzicht_Taxonomy::TAXONOMY );
}
