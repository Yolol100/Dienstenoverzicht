<?php

namespace Dienstenoverzicht\CPT;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkom directe toegang.
}

if ( ! class_exists( 'Dienstenoverzicht_CPT' ) ) {

    /**
     * Class Dienstenoverzicht_CPT
     *
     * Beheert de registratie van het Custom Post Type voor 'diensten'.
     *
     * @package Dienstenoverzicht\CPT
     */
    final class Dienstenoverzicht_CPT {

        /**
         * Slug voor het Custom Post Type.
         *
         * @var string
         */
        private const POST_TYPE = 'diensten';

        /**
         * Tekstdomein voor vertalingen.
         *
         * @var string
         */
        private const TEXT_DOMAIN = 'dienstenoverzicht';

        /**
         * Initialiseer de CPT functionaliteit.
         *
         * Deze methode koppelt de register_post_type methode aan de 'init' actie.
         *
         * @return void
         */
        public static function init(): void {
            add_action( 'init', [ __CLASS__, 'register_post_type' ] );
        }

        /**
         * Registreer het 'diensten' Custom Post Type.
         *
         * Deze functie is gekoppeld aan de 'init' actie en handelt de registratie
         * van het Custom Post Type af, inclusief labels, argumenten en compatibiliteit met WPML en Polylang.
         *
         * @return void
         */
        public static function register_post_type(): void {
            // Haal labels en argumenten op voor het Custom Post Type.
            $labels = self::get_post_type_labels();
            $args    = self::get_post_type_args( $labels );

            // Registreer het Custom Post Type.
            register_post_type( self::POST_TYPE, $args );

            // Compatibiliteit met WPML en Polylang.
            self::register_wpml_polylang_compatibility();
        }

        /**
         * Registreer compatibiliteit met WPML en Polylang.
         *
         * Deze functie zorgt ervoor dat het Custom Post Type compatibel is met meertalige plugins zoals WPML en Polylang.
         *
         * @return void
         */
        private static function register_wpml_polylang_compatibility(): void {
            // Registreer strings voor Polylang indien beschikbaar.
            if ( function_exists( 'pll_register_string' ) ) {
                pll_register_string( 'diensten', 'Diensten', self::TEXT_DOMAIN );
            }

            // Registreer het post type voor WPML indien beschikbaar.
            if ( function_exists( 'wpml_register_post_type_action' ) ) {
                do_action( 'wpml_register_post_type_action', self::POST_TYPE );
            }
        }

        /**
         * Haal de labels op voor het 'diensten' Custom Post Type.
         *
         * @return array Labels voor het Custom Post Type.
         */
        private static function get_post_type_labels(): array {
            return [
                'name'                  => _x( 'Diensten', 'Post type general name', self::TEXT_DOMAIN ),
                'singular_name'         => _x( 'Dienst', 'Post type singular name', self::TEXT_DOMAIN ),
                'menu_name'             => _x( 'Diensten', 'Admin Menu text', self::TEXT_DOMAIN ),
                'name_admin_bar'        => _x( 'Dienst', 'Add New on Toolbar', self::TEXT_DOMAIN ),
                'add_new'               => __( 'Nieuwe dienst', self::TEXT_DOMAIN ),
                'add_new_item'          => __( 'Nieuwe dienst toevoegen', self::TEXT_DOMAIN ),
                'new_item'              => __( 'Nieuwe dienst', self::TEXT_DOMAIN ),
                'edit_item'             => __( 'Dienst bewerken', self::TEXT_DOMAIN ),
                'view_item'             => __( 'Dienst bekijken', self::TEXT_DOMAIN ),
                'all_items'             => __( 'Alle diensten', self::TEXT_DOMAIN ),
                'search_items'          => __( 'Zoek diensten', self::TEXT_DOMAIN ),
                'parent_item_colon'     => __( 'Bovenliggende dienst:', self::TEXT_DOMAIN ),
                'not_found'             => __( 'Geen diensten gevonden.', self::TEXT_DOMAIN ),
                'not_found_in_trash'    => __( 'Geen diensten gevonden in prullenbak.', self::TEXT_DOMAIN ),
                'featured_image'        => _x( 'Uitgelichte afbeelding', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', self::TEXT_DOMAIN ),
                'set_featured_image'    => _x( 'Stel uitgelichte afbeelding in', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', self::TEXT_DOMAIN ),
                'remove_featured_image' => _x( 'Verwijder uitgelichte afbeelding', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', self::TEXT_DOMAIN ),
                'use_featured_image'    => _x( 'Gebruik als uitgelichte afbeelding', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', self::TEXT_DOMAIN ),
                'archives'              => _x( 'Diensten archieven', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', self::TEXT_DOMAIN ),
                'insert_into_item'      => _x( 'Invoegen in dienst', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', self::TEXT_DOMAIN ),
                'uploaded_to_this_item' => _x( 'Geüpload naar deze dienst', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', self::TEXT_DOMAIN ),
                'filter_items_list'     => _x( 'Filter diensten lijst', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”. Added in 4.4', self::TEXT_DOMAIN ),
                'items_list_navigation' => _x( 'Diensten lijst navigatie', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”. Added in 4.4', self::TEXT_DOMAIN ),
                'items_list'            => _x( 'Diensten lijst', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”. Added in 4.4', self::TEXT_DOMAIN ),
            ];
        }

        /**
         * Haal de argumenten op voor het registreren van het 'diensten' Custom Post Type.
         *
         * @param array $labels Labels voor het Custom Post Type.
         * @return array Argumenten voor het registreren van het Custom Post Type.
         */
        private static function get_post_type_args( array $labels ): array {
            return [
                'labels'             => $labels,
                'public'             => true,
                'publicly_queryable' => true,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'rewrite'            => [
                    'slug'         => 'diensten',
                    'with_front'   => true,
                    'hierarchical' => true,
                ],
                'capability_type'    => 'post',
                'has_archive'        => true,
                'hierarchical'       => false,
                'menu_position'      => 20, // Stel een specifieke positie in voor het menu-item.
                'supports'           => [ 'title', 'editor', 'thumbnail' ],
                'menu_icon'          => 'dashicons-clipboard',
                'show_in_rest'       => true, // Voegt ondersteuning toe voor de Gutenberg editor.
                'show_in_nav_menus'  => true, // Toont het Custom Post Type in navigatiemenu's.
                'exclude_from_search'=> false, // Inclusief in zoekresultaten.
                'taxonomies'         => [ 'dienstenoverzicht_categorie' ], // Koppeling met de custom taxonomy.
            ];
        }

    }

    /**
     * Initialiseer de Dienstenoverzicht_CPT klasse.
     */
    Dienstenoverzicht_CPT::init();
}