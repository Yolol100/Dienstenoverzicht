<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkom directe toegang.
}

if ( ! class_exists( 'Dienstenoverzicht_Assets' ) ) {

    /**
     * Class Dienstenoverzicht_Assets
     *
     * Beheert het inladen van CSS en JavaScript bestanden voor de Dienstenoverzicht plugin.
     *
     * @package Dienstenoverzicht
     */
    final class Dienstenoverzicht_Assets {

        // =================================================
        // SINGLETON PATTERN IMPLEMENTATION
        // =================================================

        /**
         * Singleton instance.
         *
         * @var Dienstenoverzicht_Assets|null
         */
        private static ?Dienstenoverzicht_Assets $instance = null;

        /**
         * Haal de singleton instantie op.
         *
         * @return Dienstenoverzicht_Assets
         */
        public static function get_instance(): Dienstenoverzicht_Assets {
            if ( self::$instance === null ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Private constructor om meerdere instanties te voorkomen.
         */
        private function __construct() {
            $this->init_hooks();
        }

        /**
         * Initialiseer de assets functionaliteit.
         *
         * Deze methode koppelt de enqueue_assets methode aan de wp_enqueue_scripts actie.
         *
         * @return void
         */
        private function init_hooks(): void {
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        }

        // =================================================
        // CONSTANTEN
        // =================================================

        /**
         * Versie nummer van de assets.
         *
         * @var string
         */
        private const VERSION = '1.0.1';

        /**
         * Tekstdomein voor vertalingen.
         *
         * @var string
         */
        private const TEXT_DOMAIN = 'dienstenoverzicht';

        // =================================================
        // ASSETS ENQUEUEN
        // =================================================

        /**
         * Enqueue styles en scripts indien nodig.
         *
         * @return void
         */
        public function enqueue_assets(): void {
            if ( $this->should_enqueue_assets() ) {
                $this->enqueue_styles();
                $this->enqueue_scripts();
            }
        }

        /**
         * Bepaal of de assets moeten worden ingeladen.
         *
         * @return bool True als assets moeten worden ingeladen, anders false.
         */
        private function should_enqueue_assets(): bool {
            if ( is_admin() ) {
                return false; // Assets niet laden in de admin omgeving.
            }

            $post = get_post(); // Haal de huidige post op.
            if ( ! $post ) {
                return false; // Als er geen post is, laad de assets niet.
            }

            return is_singular( 'diensten' ) || is_post_type_archive( 'diensten' ) || has_shortcode( $post->post_content, 'dienstenoverzicht' );
        }

        /**
         * Enqueue de CSS bestanden.
         *
         * @return void
         */
        private function enqueue_styles(): void {
            $plugin_url = plugin_dir_url( __FILE__ );

            // Enqueue de hoofd CSS.
            wp_enqueue_style(
                'dienstenoverzicht-style',
                esc_url( $plugin_url . 'css/dienstenoverzicht-style.css' ),
                [],
                self::VERSION
            );

            // Enqueue Font Awesome vanaf CDN, controleer of het al is ingeladen.
            if ( ! wp_style_is( 'font-awesome', 'enqueued' ) ) {
                wp_enqueue_style(
                    'font-awesome',
                    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css',
                    [],
                    '6.0.0-beta3'
                );
            }
        }

        /**
         * Enqueue de JavaScript bestanden.
         *
         * @return void
         */
        private function enqueue_scripts(): void {
            $plugin_url = plugin_dir_url( __FILE__ );

            // Enqueue het hoofd script.
            wp_enqueue_script(
                'dienstenoverzicht-script',
                esc_url( $plugin_url . 'js/dienstenoverzicht-script.js' ),
                [ 'jquery' ],
                self::VERSION,
                true
            );

            // Localize het script voor AJAX en beveiliging.
            wp_localize_script(
                'dienstenoverzicht-script',
                'dienstenoverzicht',
                [
                    'ajaxurl'  => admin_url( 'admin-ajax.php' ),
                    'security' => wp_create_nonce( 'dienstenoverzicht_nonce' ),
                ]
            );
        }

    }

    /**
     * Initialiseer de Dienstenoverzicht_Assets klasse.
     */
    Dienstenoverzicht_Assets::get_instance();

}