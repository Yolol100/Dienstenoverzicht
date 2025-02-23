<?php

namespace Dienstenoverzicht\I18n;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkom directe toegang.
}

if ( ! class_exists( 'Dienstenoverzicht_I18n' ) ) {

    /**
     * Class Dienstenoverzicht_I18n
     *
     * Verantwoordelijk voor het laden van de tekstdomein voor internationale ondersteuning.
     *
     * @package Dienstenoverzicht\I18n
     */
    final class Dienstenoverzicht_I18n {

        /**
         * Slug voor de tekstdomein.
         *
         * @var string
         */
        private const TEXT_DOMAIN = 'dienstenoverzicht';

        /**
         * Directory waar de vertaalbestanden zich bevinden.
         *
         * @var string
         */
        private const LANGUAGES_DIR = 'languages';

        /**
         * Initializeer de i18n functionaliteit.
         *
         * Deze methode koppelt de load_textdomain methode aan de 'plugins_loaded' actie.
         *
         * @return void
         */
        public static function init(): void {
            add_action( 'plugins_loaded', [ __CLASS__, 'load_textdomain' ] );
        }

        /**
         * Laad de tekstdomein van de plugin voor vertaling.
         *
         * Deze methode laadt de tekstdomein van de plugin zodat vertalingen vanuit de 'languages' directory beschikbaar zijn.
         * De functie maakt gebruik van WordPress' `load_plugin_textdomain` om de plugin vertaalbaar te maken.
         *
         * @return void
         */
        public static function load_textdomain(): void {
            // Bepaal de directory waar de vertaalbestanden zich bevinden.
            $plugin_dir = dirname( plugin_basename( __FILE__ ) ) . '/' . self::LANGUAGES_DIR;

            // Laad de tekstdomein voor vertalingen.
            $loaded = load_plugin_textdomain( self::TEXT_DOMAIN, false, $plugin_dir );

            // Optioneel: Log of handel het falen af als de tekstdomein niet geladen kon worden.
            if ( ! $loaded ) {
                // Je kunt hier een fout loggen of een notice triggeren.
                error_log( 'Kon de tekstdomein voor de Dienstenoverzicht plugin niet laden.' );
            }
        }
    }

    /**
     * Class Dienstenoverzicht_Example
     *
     * Voorbeeld van een klasse met vertaalbare strings.
     *
     * @package Dienstenoverzicht\I18n
     */
    final class Dienstenoverzicht_Example {

        /**
         * Tekstdomein voor vertalingen.
         *
         * @var string
         */
        private const TEXT_DOMAIN = Dienstenoverzicht_I18n::TEXT_DOMAIN;

        /**
         * Toon een voorbeeld titel en paragraaf.
         *
         * @return void
         */
        public function display_example(): void {
            echo '<h2>' . esc_html__( 'Welkom bij Dienstenoverzicht', self::TEXT_DOMAIN ) . '</h2>';
            echo '<p>' . esc_html__( 'Dit is een voorbeeldtekst die kan worden vertaald.', self::TEXT_DOMAIN ) . '</p>';
        }

        /**
         * Een andere functie met een vertaalbare boodschap.
         *
         * @return void
         */
        public function some_other_function(): void {
            $message = __( 'Een andere tekst die vertaald moet worden.', self::TEXT_DOMAIN );
            echo '<p>' . esc_html( $message ) . '</p>';
        }
    }

    /**
     * Class Dienstenoverzicht_Widget
     *
     * Voorbeeld van een widget klasse met vertaalbare strings.
     *
     * @package Dienstenoverzicht\I18n
     */
    final class Dienstenoverzicht_Widget {

        /**
         * Tekstdomein voor vertalingen.
         *
         * @var string
         */
        private const TEXT_DOMAIN = Dienstenoverzicht_I18n::TEXT_DOMAIN;

        /**
         * Toon de widget output.
         *
         * @return void
         */
        public function widget_output(): void {
            echo '<div class="dienstenoverzicht-widget">';
            echo '<h3>' . esc_html__( 'Onze Diensten', self::TEXT_DOMAIN ) . '</h3>';
            echo '<p>' . esc_html__( 'Bekijk hier ons dienstenoverzicht.', self::TEXT_DOMAIN ) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Initialiseer de Dienstenoverzicht_I18n klasse.
     */
    Dienstenoverzicht_I18n::init();
}