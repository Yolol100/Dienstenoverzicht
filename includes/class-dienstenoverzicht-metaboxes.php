<?php

namespace Dienstenoverzicht\Metaboxes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkom directe toegang.
}

if ( ! class_exists( 'Dienstenoverzicht_Metaboxes' ) ) {

/**
 * Class Dienstenoverzicht_Metaboxes
 *
 * Beheert de metaboxen voor het 'diensten' post type.
 *
 * @package Dienstenoverzicht\Metaboxes
 */
final class Dienstenoverzicht_Metaboxes {

    /**
     * Slug voor de nonce actie.
     *
     * @var string
     */
    private const NONCE_ACTION = 'dienstenoverzicht_save_metabox_data';

    /**
     * Slug voor de nonce veldnaam.
     *
     * @var string
     */
    private const NONCE_FIELD = 'dienstenoverzicht_nonce';

    /**
     * Versie nummer van de scripts en styles.
     *
     * @var string
     */
    private const VERSION = '1.0.0';

    /**
     * Initializeer de metaboxen en hooks.
     *
     * Deze methode koppelt de metaboxen en opslagfunctionaliteit aan de WordPress hooks.
     *
     * @return void
     */
    public static function init(): void {
        add_action( 'add_meta_boxes', [ self::class, 'add_metaboxes' ] );
        add_action( 'save_post', [ self::class, 'save_metabox_data' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_scripts' ] );
    }

    /**
     * Registreer metaboxes voor het 'diensten' post type.
     *
     * @return void
     */
    public static function add_metaboxes(): void {
        add_meta_box(
            'dienstenoverzicht_details',
            __( 'Dienstenoverzicht Details', self::get_text_domain() ),
            [ self::class, 'details_metabox' ],
            self::get_post_type(),
            'normal',
            'high'
        );
    }

    /**
     * Toon de inhoud van de metabox.
     *
     * @param \WP_Post $post Het post object.
     *
     * @return void
     */
    public static function details_metabox( \WP_Post $post ): void {
        // Voeg een nonce veld toe voor verificatie.
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

        // Haal bestaande waarden op uit de database.
        $basispakket_prijzen        = get_post_meta( $post->ID, '_basispakket_prijzen', true );
        $uitgebreid_pakket_prijzen  = get_post_meta( $post->ID, '_uitgebreid_pakket_prijzen', true );
        $samenvatting               = get_post_meta( $post->ID, '_samenvatting', true );

        ?>
        <div class="dienstenoverzicht-metabox">
            <div class="meta-box-field">
                <label for="basispakket_prijzen"><?php esc_html_e( 'Basispakket:', self::get_text_domain() ); ?></label>
                <textarea id="basispakket_prijzen" name="basispakket_prijzen" rows="5" class="widefat"><?php echo esc_textarea( $basispakket_prijzen ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Voer hier de prijzen in voor het basispakket.', self::get_text_domain() ); ?></p>
            </div>

            <div class="meta-box-field">
                <label for="uitgebreid_pakket_prijzen"><?php esc_html_e( 'Uitgebreid Pakket:', self::get_text_domain() ); ?></label>
                <textarea id="uitgebreid_pakket_prijzen" name="uitgebreid_pakket_prijzen" rows="5" class="widefat"><?php echo esc_textarea( $uitgebreid_pakket_prijzen ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Voer hier de prijzen in voor het uitgebreide pakket.', self::get_text_domain() ); ?></p>
            </div>

            <div class="meta-box-field">
                <label for="samenvatting"><?php esc_html_e( 'Samenvatting:', self::get_text_domain() ); ?></label>
                <textarea id="samenvatting" name="samenvatting" rows="5" class="widefat"><?php echo esc_textarea( $samenvatting ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Voer hier een korte samenvatting in.', self::get_text_domain() ); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Sla de metabox data op wanneer de post wordt opgeslagen.
     *
     * @param int $post_id De ID van de post die wordt opgeslagen.
     *
     * @return void
     */
    public static function save_metabox_data( int $post_id ): void {
        // Controleer of het opslaan moet worden afgebroken.
        if ( self::should_abort( $post_id ) ) {
            return;
        }

        // Haal en sanitize de meta velden.
        $basispakket_prijzen        = isset( $_POST['basispakket_prijzen'] ) ? sanitize_textarea_field( wp_unslash( $_POST['basispakket_prijzen'] ) ) : '';
        $uitgebreid_pakket_prijzen  = isset( $_POST['uitgebreid_pakket_prijzen'] ) ? sanitize_textarea_field( wp_unslash( $_POST['uitgebreid_pakket_prijzen'] ) ) : '';
        $samenvatting               = isset( $_POST['samenvatting'] ) ? sanitize_textarea_field( wp_unslash( $_POST['samenvatting'] ) ) : '';

        // Update de post meta.
        update_post_meta( $post_id, '_basispakket_prijzen', $basispakket_prijzen );
        update_post_meta( $post_id, '_uitgebreid_pakket_prijzen', $uitgebreid_pakket_prijzen );
        update_post_meta( $post_id, '_samenvatting', $samenvatting );
    }

    /**
     * Bepaal of het opslaanproces moet worden afgebroken.
     *
     * @param int $post_id De ID van de post die wordt opgeslagen.
     *
     * @return bool True als het opslaan moet worden afgebroken, anders false.
     */
    private static function should_abort( int $post_id ): bool {
        // Controleer voor autosaves.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return true;
        }

        // Controleer of de nonce is ingesteld.
        if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
            return true;
        }

        // Verifieer de nonce.
        if ( ! wp_verify_nonce( $_POST[ self::NONCE_FIELD ], self::NONCE_ACTION ) ) {
            return true;
        }

        // Controleer de gebruikersrechten.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return true;
        }

        return false;
    }

    /**
     * Enqueue scripts en styles voor de admin pagina.
     *
     * @return void
     */
    public static function enqueue_admin_scripts(): void {
        $screen = get_current_screen();
        if ( $screen && 'post' === $screen->base && 'diensten' === $screen->post_type ) {
            // Gebruik de plugin URL constante gedefinieerd in het hoofdplugin bestand
            $plugin_url = DIENSTENOVERZICHT_PLUGIN_URL;

            // Registreer en enqueue scripts.
            wp_enqueue_script(
                'dienstenoverzicht-admin-script',
                $plugin_url . 'js/dienstenoverzicht-script.js',
                [ 'jquery' ],
                self::VERSION,
                true
            );

            // Registreer en enqueue styles.
            wp_enqueue_style(
                'dienstenoverzicht-admin-style',
                $plugin_url . 'css/dienstenoverzicht-admin-style.css',
                [],
                self::VERSION
            );

            // Verwijder de inline CSS omdat deze nu in het CSS-bestand staat.
            // wp_add_inline_style( 'dienstenoverzicht-admin-style', $custom_css );
        }
    }

    /**
     * Haal de tekstdomein op.
     *
     * @return string Tekstdomein.
     */
    private static function get_text_domain(): string {
        return 'dienstenoverzicht';
    }

    /**
     * Haal het post type op.
     *
     * @return string Post type slug.
     */
    private static function get_post_type(): string {
        return 'diensten';
    }
}

/**
 * Initialiseer de Dienstenoverzicht_Metaboxes klasse.
 */
Dienstenoverzicht_Metaboxes::init();

}
?>