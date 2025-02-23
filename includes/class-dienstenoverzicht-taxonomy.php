<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkom directe toegang.
}

/**
 * Class Dienstenoverzicht_Taxonomy
 *
 * Registreert en beheert de 'dienstenoverzicht_categorie' taxonomie voor het 'diensten' berichttype.
 *
 * @package Dienstenoverzicht
 */
class Dienstenoverzicht_Taxonomy {

    /**
     * Slug voor de taxonomie.
     */
    const TAXONOMY_SLUG = 'dienstenoverzicht_categorie';

    /**
     * Geassocieerd berichttype.
     */
    const POST_TYPE = 'diensten';

    /**
     * Tekst domein voor vertalingen.
     */
    const TEXT_DOMAIN = 'dienstenoverzicht';

    /**
     * Initialiseer de taxonomie registratie.
     *
     * Deze methode wordt aangeroepen om de taxonomie te registreren via WordPress hooks.
     *
     * @return void
     */
    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'register_taxonomy' ], 0 );
    }

    /**
     * Registreer de aangepaste taxonomie.
     *
     * Deze methode registreert de taxonomie met de opgegeven labels en argumenten.
     *
     * @return void
     */
    public static function register_taxonomy(): void {
        $labels = self::get_taxonomy_labels();
        $args   = self::get_taxonomy_args( $labels );

        register_taxonomy(
            self::TAXONOMY_SLUG,
            [ self::POST_TYPE ],
            $args
        );
    }

    /**
     * Haal de labels voor de taxonomie op.
     *
     * Deze methode definieert de labels die in de WordPress admin interface worden weergegeven.
     *
     * @return array Labels voor de taxonomie.
     */
    private static function get_taxonomy_labels(): array {
        return [
            'name'                       => _x( 'Categorieën', 'taxonomy general name', self::TEXT_DOMAIN ),
            'singular_name'              => _x( 'Categorie', 'taxonomy singular name', self::TEXT_DOMAIN ),
            'search_items'               => __( 'Zoek Categorieën', self::TEXT_DOMAIN ),
            'popular_items'              => __( 'Populaire Categorieën', self::TEXT_DOMAIN ),
            'all_items'                  => __( 'Alle Categorieën', self::TEXT_DOMAIN ),
            'parent_item'                => __( 'Bovenliggende Categorie', self::TEXT_DOMAIN ),
            'parent_item_colon'          => __( 'Bovenliggende Categorie:', self::TEXT_DOMAIN ),
            'edit_item'                  => __( 'Bewerk Categorie', self::TEXT_DOMAIN ),
            'update_item'                => __( 'Werk Categorie Bij', self::TEXT_DOMAIN ),
            'add_new_item'               => __( 'Voeg Nieuwe Categorie Toe', self::TEXT_DOMAIN ),
            'new_item_name'              => __( 'Nieuwe Categorie Naam', self::TEXT_DOMAIN ),
            'separate_items_with_commas' => __( 'Scheid categorieën met komma\'s', self::TEXT_DOMAIN ),
            'add_or_remove_items'        => __( 'Voeg categorieën toe of verwijder ze', self::TEXT_DOMAIN ),
            'choose_from_most_used'      => __( 'Kies uit de meest gebruikte categorieën', self::TEXT_DOMAIN ),
            'not_found'                  => __( 'Geen categorieën gevonden.', self::TEXT_DOMAIN ),
            'menu_name'                  => __( 'Categorieën', self::TEXT_DOMAIN ),
        ];
    }

    /**
     * Haal de argumenten voor de taxonomie registratie op.
     *
     * Deze methode definieert de eigenschappen en instellingen voor de taxonomie.
     *
     * @param array $labels Labels voor de taxonomie.
     * @return array Argumenten voor de taxonomie registratie.
     */
    private static function get_taxonomy_args( array $labels ): array {
        return [
            'hierarchical'      => true, // Zorgt voor een hiërarchische structuur (zoals categorieën).
            'labels'            => $labels, // Gebruik de opgehaalde labels.
            'show_ui'           => true, // Toon de taxonomie UI in de admin.
            'show_admin_column' => true, // Voeg de taxonomie toe aan de admin kolommen.
            'show_in_rest'      => true, // Ondersteuning voor de REST API.
            'query_var'         => true, // Schakel query variabele in voor de taxonomie.
            'rewrite'           => [
                'slug'         => 'categorie', // Stel de slug in voor de taxonomie.
                'with_front'   => true,        // Gebruik de front base in de permalink.
                'hierarchical' => true,        // Maak de permalink hiërarchisch.
            ],
            'capabilities'      => [
                'manage_terms' => 'manage_categories', // Vermogen om termen te beheren.
                'edit_terms'   => 'manage_categories', // Vermogen om termen te bewerken.
                'delete_terms' => 'manage_categories', // Vermogen om termen te verwijderen.
                'assign_terms' => 'edit_posts',       // Vermogen om termen toe te wijzen.
            ],
            'show_in_quick_edit' => true, // Ondersteuning voor snel bewerken.
        ];
    }
}

// Initialiseer de taxonomie registratie.
Dienstenoverzicht_Taxonomy::init();