<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkom directe toegang.
}

if ( ! class_exists( 'Dienstenoverzicht_Shortcode' ) ) :

    /**
     * Class Dienstenoverzicht_Shortcode
     *
     * Registreert en beheert de shortcode voor het weergeven van diensten.
     *
     * @package Dienstenoverzicht
     */
    class Dienstenoverzicht_Shortcode {

        /**
         * Constructor.
         *
         * Registreert de shortcode en de asset enqueueing.
         */
        public function __construct() {
            // Register de shortcode.
            add_shortcode( 'dienstenoverzicht', [ $this, 'render_shortcode' ] );
            // Enqueue assets.
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        }

        /**
         * Haalt en saneert de plugin-opties.
         *
         * @return array De gesaneerde opties.
         */
        protected function get_options() {
            return [
                'title'               => get_option( 'dienstenoverzicht_title', __( 'Onze Diensten', 'dienstenoverzicht' ) ),
                'items_per_page'      => absint( get_option( 'dienstenoverzicht_items_per_page', 10 ) ),
                'show_prices'         => boolval( get_option( 'dienstenoverzicht_show_prices', 1 ) ),
                'button_font_size'    => sanitize_text_field( get_option( 'dienstenoverzicht_button_font_size', '14px' ) ),
                'title_font_size'     => sanitize_text_field( get_option( 'dienstenoverzicht_title_font_size', '16px' ) ),
                'summary_font_size'   => sanitize_text_field( get_option( 'dienstenoverzicht_summary_font_size', '14px' ) ),
                'title_color'         => sanitize_hex_color( get_option( 'dienstenoverzicht_title_color', '#000000' ) ),
                'prijs_color'         => sanitize_hex_color( get_option( 'dienstenoverzicht_prijs_color', '#000000' ) ),
                'button_color'        => sanitize_hex_color( get_option( 'dienstenoverzicht_button_color', '#0073aa' ) ),
                'button_hover_color'  => sanitize_hex_color( get_option( 'dienstenoverzicht_button_hover_color', '#005a87' ) ),
                'gratis_advies_url'   => esc_url( get_option( 'dienstenoverzicht_gratis_advies_url', '' ) ),
                'prijs_font_size'     => sanitize_text_field( get_option( 'dienstenoverzicht_prijs_font_size', '14px' ) ),
            ];
        }

        /**
         * Render de shortcode output.
         *
         * @return string De output van de shortcode.
         */
        public function render_shortcode() {
            $options = $this->get_options();

            // Begin met de container.
            $output  = '<div class="dienstenoverzicht-container">';
            $output .= $this->render_header( $options['title'], $options['title_font_size'], $options['title_color'] );
            $output .= '<div class="dienstenoverzicht">';

            // Query voor 'diensten' posts.
            $args = [
                'post_type'      => 'diensten',
                'posts_per_page' => $options['items_per_page'],
                'orderby'        => 'date',
                'order'          => 'ASC',
                'no_found_rows'  => true, // Optimaliseer query door geen totale aantal rijen op te halen.
            ];

            $diensten_query = new WP_Query( $args );

            if ( $diensten_query->have_posts() ) {
                while ( $diensten_query->have_posts() ) {
                    $diensten_query->the_post();

                    $output .= $this->render_dienst( get_the_ID(), $options );
                }
                wp_reset_postdata();
            } else {
                // Geen diensten gevonden.
                $output .= '<p>' . esc_html__( 'Geen diensten gevonden.', 'dienstenoverzicht' ) . '</p>';
            }

            $output .= '</div>'; // .dienstenoverzicht
            $output .= '</div>'; // .dienstenoverzicht-container

            return $output;
        }

        /**
         * Render de header van het dienstenoverzicht.
         *
         * @param string $title           De titel van het overzicht.
         * @param string $title_font_size De lettergrootte van de titel.
         * @param string $title_color     De kleur van de titel.
         *
         * @return string De HTML output van de header.
         */
        protected function render_header( $title, $title_font_size, $title_color ) {
            $output = '<div class="dienstenoverzicht-header">';

            // Voeg de titel toe indien ingesteld.
            if ( ! empty( $title ) ) {
                $output .= sprintf(
                    '<h2 class="dienstenoverzicht-title" style="font-size:%1$s; color:%2$s;">%3$s</h2>',
                    esc_attr( $title_font_size ),
                    esc_attr( $title_color ),
                    esc_html( $title )
                );
            }

            $output .= '</div>'; // .dienstenoverzicht-header

            return $output;
        }

        /**
         * Render een enkele dienst.
         *
         * @param int   $post_id Het ID van de dienst.
         * @param array $options De array met opties.
         *
         * @return string De HTML output van de dienst.
         */
        protected function render_dienst( $post_id, $options ) {
            // Extract options
            extract( $options );

            // Haal meta gegevens op.
            $basispakket_prijzen       = get_post_meta( $post_id, '_basispakket_prijzen', true );
            $uitgebreid_pakket_prijzen = get_post_meta( $post_id, '_uitgebreid_pakket_prijzen', true );
            $samenvatting              = get_post_meta( $post_id, '_samenvatting', true );

            $output = '<div class="dienst">';

            // Voeg de featured image toe indien beschikbaar.
            if ( has_post_thumbnail( $post_id ) ) {
                $output .= '<div class="dienst-image">' . get_the_post_thumbnail( $post_id, 'full' ) . '</div>';
            }

            $output .= '<div class="dienst-content">';

            // Dienst titel.
            $output .= sprintf(
                '<h3 class="dienst-title" style="font-size:%1$s; color:%2$s;">%3$s</h3>',
                esc_attr( $title_font_size ),
                esc_attr( $title_color ),
                esc_html( get_the_title( $post_id ) )
            );

            // Dienst samenvatting.
            $output .= sprintf(
                '<p class="dienst-summary" style="font-size:%1$s;">%2$s</p>',
                esc_attr( $summary_font_size ),
                esc_html( $samenvatting )
            );

            // Prijzen weergeven indien ingeschakeld.
            if ( $show_prices && ( ! empty( $basispakket_prijzen ) || ! empty( $uitgebreid_pakket_prijzen ) ) ) {
                $output .= '<div class="dienst-pricing">';
                $output .= '<div class="prijzen-container">';

                // Basispakket prijs.
                if ( ! empty( $basispakket_prijzen ) ) {
                    $output .= '<div class="prijs-item">';
                    $output .= sprintf(
                        '<span class="prijs-title" style="font-size:%1$s; color:%2$s;">%3$s</span>',
                        esc_attr( $prijs_font_size ),
                        esc_attr( $prijs_color ),
                        __( 'Basis:', 'dienstenoverzicht' )
                    );
                    $output .= sprintf(
                        '<span class="prijs-value" style="font-size:%1$s; color:%2$s;">%3$s</span>',
                        esc_attr( $prijs_font_size ),
                        esc_attr( $prijs_color ),
                        esc_html( $basispakket_prijzen )
                    );
                    $output .= '</div>'; // .prijs-item
                }

                // Uitgebreid pakket prijs.
                if ( ! empty( $uitgebreid_pakket_prijzen ) ) {
                    $output .= '<div class="prijs-item">';
                    $output .= sprintf(
                        '<span class="prijs-title" style="font-size:%1$s; color:%2$s;">%3$s</span>',
                        esc_attr( $prijs_font_size ),
                        esc_attr( $prijs_color ),
                        __( 'Uitgebreid:', 'dienstenoverzicht' )
                    );
                    $output .= sprintf(
                        '<span class="prijs-value" style="font-size:%1$s; color:%2$s;">%3$s</span>',
                        esc_attr( $prijs_font_size ),
                        esc_attr( $prijs_color ),
                        esc_html( $uitgebreid_pakket_prijzen )
                    );
                    $output .= '</div>'; // .prijs-item
                }

                $output .= '</div>'; // .prijzen-container
                $output .= '</div>'; // .dienst-pricing
            }

            // Gratis advies knop.
            if ( ! empty( $gratis_advies_url ) ) {
                $output .= sprintf(
                    '<a href="%1$s" class="button gratis-advies-button" style="font-size:%2$s; background-color:%3$s; color: #fff;" onmouseover="this.style.backgroundColor=\'%4$s\'" onmouseout="this.style.backgroundColor=\'%3$s\'"><i class="fas fa-handshake"></i> %5$s</a>',
                    esc_url( $gratis_advies_url ),
                    esc_attr( $button_font_size ),
                    esc_attr( $button_color ),
                    esc_attr( $button_hover_color ),
                    esc_html__( 'Gratis advies', 'dienstenoverzicht' )
                );
            }

            $output .= '</div>'; // .dienst-content
            $output .= '</div>'; // .dienst

            return $output;
        }

        /**
         * Enqueue assets (CSS & JS).
         *
         * @return void
         */
        public function enqueue_assets() {
            // Registreer de CSS.
            wp_register_style(
                'dienstenoverzicht-style',
                DIENSTENOVERZICHT_PLUGIN_URL . 'css/dienstenoverzicht-style.css',
                [],
                '2.3.1' // Verhoog de versie bij elke wijziging
            );

            // Registreer de JS.
            wp_register_script(
                'dienstenoverzicht-script',
                DIENSTENOVERZICHT_PLUGIN_URL . 'js/dienstenoverzicht-script.js',
                [ 'jquery' ],
                '1.0.0',
                true
            );

            // Enqueue de styles en scripts indien niet in de admin.
            if ( ! is_admin() ) {
                wp_enqueue_style( 'dienstenoverzicht-style' );
                wp_enqueue_script( 'dienstenoverzicht-script' );
            }
        }

    }

    // Initialiseer de shortcode klasse.
    new Dienstenoverzicht_Shortcode();

endif;
?>