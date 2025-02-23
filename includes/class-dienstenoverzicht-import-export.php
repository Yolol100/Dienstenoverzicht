<?php

namespace Dienstenoverzicht\ImportExport;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Voorkom directe toegang.
}

if ( ! class_exists( 'Dienstenoverzicht_ImportExport' ) ) {

    /**
     * Class Dienstenoverzicht_ImportExport
     *
     * Beheert de import- en exportfunctionaliteit voor de Dienstenoverzicht plugin.
     *
     * @package Dienstenoverzicht\ImportExport
     */
    final class Dienstenoverzicht_ImportExport {

        /**
         * Versienummer van de assets.
         *
         * @var string
         */
        private const VERSION = '1.0.0';

        /**
         * Tekstdomein voor vertalingen.
         *
         * @var string
         */
        private const TEXT_DOMAIN = 'dienstenoverzicht';

        /**
         * Initialiseer de import/export functionaliteit.
         *
         * Deze methode koppelt de nodige hooks.
         *
         * @return void
         */
        public static function init(): void {
            add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_custom_styles' ] );
            add_action( 'admin_menu', [ self::class, 'add_menu_items' ] );
            add_action( 'admin_post_export_single_dienst', [ self::class, 'export_single_dienst' ] );
            add_action( 'admin_post_import_diensten', [ self::class, 'handle_file_upload' ] );
            add_action( 'admin_post_export_selected_diensten', [ self::class, 'export_selected_diensten' ] );
        }

        /**
         * Enqueue custom admin styles.
         *
         * @param string $hook_suffix Huidige admin page hook.
         *
         * @return void
         */
        public static function enqueue_admin_custom_styles( string $hook_suffix ): void {
            $screen = get_current_screen();
            if ( $screen && 'toplevel_page_diensten-export-import' === $screen->id ) {
                wp_enqueue_style(
                    'custom-admin-style',
                    plugin_dir_url( __FILE__ ) . '../css/dienstenoverzicht-admin-style.css',
                    [],
                    self::VERSION
                );
            }
        }

        /**
         * Voeg submenu items toe voor export/import functionaliteit.
         *
         * @return void
         */
        public static function add_menu_items(): void {
            add_submenu_page(
                'edit.php?post_type=diensten',
                __( 'Diensten Exporteren/Importeren', self::TEXT_DOMAIN ),
                __( 'Export/Import', self::TEXT_DOMAIN ),
                'manage_options',
                'diensten-export-import',
                [ self::class, 'render_export_import_page' ]
            );
        }

        /**
         * Exporteer geselecteerde diensten naar een CSV-bestand.
         *
         * @return void
         */
        public static function export_selected_diensten(): void {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Je hebt geen toestemming om deze actie uit te voeren.', self::TEXT_DOMAIN ) );
            }

            if ( isset( $_POST['action'], $_POST['post_ids'] ) && 'export_selected_diensten' === $_POST['action'] && ! empty( $_POST['post_ids'] ) ) {
                check_admin_referer( 'export_diensten_nonce', 'export_diensten_nonce_field' );

                $post_ids = array_map( 'intval', $_POST['post_ids'] );
                $args     = [
                    'post_type'      => 'diensten',
                    'post__in'       => $post_ids,
                    'posts_per_page' => -1,
                    'post_status'    => 'publish',
                ];
                $posts = get_posts( $args );

                $filename = 'diensten_export_selected_' . date( 'Ymd' ) . '.csv';
                header( 'Content-Type: text/csv' );
                header( 'Content-Disposition: attachment; filename=' . $filename );

                $output = fopen( 'php://output', 'w' );
                fputcsv( $output, [ 'ID', 'Title', 'Basis Pakket Prijzen', 'Uitgebreid Pakket Prijzen', 'Content', 'Summary' ] );

                foreach ( $posts as $post ) {
                    $basis_prijs       = get_post_meta( $post->ID, '_basispakket_prijzen', true );
                    $uitgebreid_prijs  = get_post_meta( $post->ID, '_uitgebreid_pakket_prijzen', true );
                    $summary           = get_post_meta( $post->ID, '_samenvatting', true );
                    fputcsv( $output, [ $post->ID, $post->post_title, $basis_prijs, $uitgebreid_prijs, $post->post_content, $summary ] );
                }

                fclose( $output );

                // Stuur een e-mail naar de huidige gebruiker na export
                $current_user  = wp_get_current_user();
                $email_message = sprintf(
                    esc_html__( 'De geselecteerde diensten zijn succesvol geëxporteerd naar %s.', self::TEXT_DOMAIN ),
                    esc_html( $filename )
                );
                wp_mail( $current_user->user_email, esc_html__( 'Diensten Export Succesvol', self::TEXT_DOMAIN ), $email_message );

                exit;
            }
        }

        /**
         * Exporteer een enkele dienst naar een CSV-bestand.
         *
         * @return void
         */
        public static function export_single_dienst(): void {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Je hebt geen toestemming om deze actie uit te voeren.', self::TEXT_DOMAIN ) );
            }

            if ( isset( $_GET['post_id'] ) && is_numeric( $_GET['post_id'] ) ) {
                $post_id = intval( $_GET['post_id'] );
                $post    = get_post( $post_id );

                if ( $post && 'diensten' === $post->post_type ) {
                    $basis_prijs       = get_post_meta( $post->ID, '_basispakket_prijzen', true );
                    $uitgebreid_prijs  = get_post_meta( $post->ID, '_uitgebreid_pakket_prijzen', true );
                    $summary           = get_post_meta( $post->ID, '_samenvatting', true );

                    $filename = 'dienst_export_' . $post_id . '_' . date( 'Ymd' ) . '.csv';
                    header( 'Content-Type: text/csv' );
                    header( 'Content-Disposition: attachment; filename=' . $filename );

                    $output = fopen( 'php://output', 'w' );
                    fputcsv( $output, [ 'ID', 'Title', 'Basis Pakket Prijzen', 'Uitgebreid Pakket Prijzen', 'Content', 'Summary' ] );
                    fputcsv( $output, [ $post->ID, $post->post_title, $basis_prijs, $uitgebreid_prijs, $post->post_content, $summary ] );
                    fclose( $output );

                    exit;
                } else {
                    wp_die( esc_html__( 'Ongeldige dienst ID.', self::TEXT_DOMAIN ) );
                }
            } else {
                wp_die( esc_html__( 'Geen dienst ID opgegeven.', self::TEXT_DOMAIN ) );
            }
        }

        /**
         * Render de tabel met diensten voor exportselectie.
         *
         * @return void
         */
        public static function render_diensten_table(): void {
            $args  = [
                'post_type'      => 'diensten',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ];
            $posts = get_posts( $args );

            if ( empty( $posts ) ) {
                echo '<p class="no-diensten-message-dienstenoverzicht">' . esc_html__( 'Er zijn geen diensten om weer te geven.', self::TEXT_DOMAIN ) . '</p>';
                return;
            }

            echo '<form id="diensten-export-form-dienstenoverzicht" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
            echo '<input type="text" id="diensten-search" placeholder="' . esc_attr__( 'Zoek diensten...', self::TEXT_DOMAIN ) . '" onkeyup="filterDienstenTable()">';
            echo '<table class="widefat fixed dienstenoverzicht-table-dienstenoverzicht">';
            echo '<thead>';
            echo '<tr>';
            echo '<th><input type="checkbox" id="select-all-dienstenoverzicht" /></th>';
            echo '<th>ID</th>';
            echo '<th>' . esc_html__( 'Title', self::TEXT_DOMAIN ) . '</th>';
            echo '<th>' . esc_html__( 'Basis Pakket Prijzen', self::TEXT_DOMAIN ) . '</th>';
            echo '<th>' . esc_html__( 'Uitgebreid Pakket Prijzen', self::TEXT_DOMAIN ) . '</th>';
            echo '<th>' . esc_html__( 'Acties', self::TEXT_DOMAIN ) . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            foreach ( $posts as $post ) {
                $basis_prijs      = get_post_meta( $post->ID, '_basispakket_prijzen', true );
                $uitgebreid_prijs = get_post_meta( $post->ID, '_uitgebreid_pakket_prijzen', true );
                echo '<tr>';
                echo '<td><input type="checkbox" name="post_ids[]" value="' . esc_attr( $post->ID ) . '" class="dienst-checkbox" /></td>';
                echo '<td>' . esc_html( $post->ID ) . '</td>';
                echo '<td class="dienst-title">' . esc_html( $post->post_title ) . '</td>';
                echo '<td>' . esc_html( $basis_prijs ) . '</td>';
                echo '<td>' . esc_html( $uitgebreid_prijs ) . '</td>';
                echo '<td><a href="' . esc_url( admin_url( 'admin-post.php?action=export_single_dienst&post_id=' . $post->ID ) ) . '" class="button button-primary-dienstenoverzicht">' . esc_html__( 'Exporteer', self::TEXT_DOMAIN ) . '</a></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '<input type="hidden" name="action" value="export_selected_diensten" />';
            wp_nonce_field( 'export_diensten_nonce', 'export_diensten_nonce_field' );
            echo '<input type="submit" id="export-selected-button-dienstenoverzicht" class="button button-primary-dienstenoverzicht" value="' . esc_attr__( 'Exporteren naar CSV', self::TEXT_DOMAIN ) . '" disabled />';
            echo '<div id="export-progress-bar"><div id="export-progress">0%</div></div>';
            echo '</form>';
            ?>

            <!-- JavaScript voor filteren en selecteren -->
            <script type="text/javascript">
                function filterDienstenTable() {
                    var input = document.getElementById("diensten-search");
                    var filter = input.value.toLowerCase();
                    var table = document.querySelector(".dienstenoverzicht-table-dienstenoverzicht tbody");
                    var rows = table.getElementsByTagName("tr");

                    for (var i = 0; i < rows.length; i++) {
                        var titleCell = rows[i].getElementsByClassName("dienst-title")[0];
                        if (titleCell) {
                            var txtValue = titleCell.textContent || titleCell.innerText;
                            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                                rows[i].style.display = "";
                            } else {
                                rows[i].style.display = "none";
                            }
                        }
                    }
                }

                document.getElementById('select-all-dienstenoverzicht').addEventListener('click', function() {
                    var checkboxes = document.querySelectorAll('input[name="post_ids[]"]');
                    for (var checkbox of checkboxes) {
                        checkbox.checked = this.checked;
                    }
                    updateExportButtonState();
                });

                var form = document.getElementById('diensten-export-form-dienstenoverzicht');
                var exportButton = document.getElementById('export-selected-button-dienstenoverzicht');
                var checkboxes = document.querySelectorAll('.dienst-checkbox');
                var progressBar = document.getElementById('export-progress-bar');
                var progress = document.getElementById('export-progress');

                function updateExportButtonState() {
                    exportButton.disabled = !Array.from(checkboxes).some(function(checkbox) {
                        return checkbox.checked;
                    });
                }

                checkboxes.forEach(function(checkbox) {
                    checkbox.addEventListener('change', updateExportButtonState);
                });

                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    progressBar.style.display = 'block';
                    progress.style.width = '0%';
                    progress.innerText = '0%';

                    var width = 0;
                    var interval = setInterval(function() {
                        width += 10;
                        progress.style.width = width + '%';
                        progress.innerText = width + '%';
                        if (width >= 100) {
                            clearInterval(interval);
                            form.submit();
                        }
                    }, 200);
                });
            </script>

            <!-- CSS-stijlen -->
            <style>
                /* Admin pagina stijlen */
                .dienstenoverzicht-page-dienstenoverzicht {
                    font-family: Arial, sans-serif;
                }

                .export-import-tabs {
                    border-bottom: 1px solid #ccc;
                    margin-bottom: 20px;
                }

                .export-import-tabs ul {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                    display: flex;
                    justify-content: flex-start;
                }

                .export-import-tabs ul li {
                    margin-right: 10px;
                }

                .export-import-tabs .nav-tab {
                    background: #f1f1f1;
                    border: 1px solid #ccc;
                    border-bottom: none;
                    padding: 10px 20px;
                    text-decoration: none;
                    font-size: 16px;
                    color: #0073aa;
                    border-radius: 4px 4px 0 0;
                    transition: background-color 0.3s ease;
                }

                .export-import-tabs .nav-tab-active {
                    background: #ffffff;
                    border-bottom: 1px solid #ffffff;
                    font-weight: bold;
                }

                .tab-content {
                    display: none;
                    background: #ffffff;
                    padding: 20px;
                    border: 1px solid #ccc;
                    border-radius: 0 4px 4px 4px;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                }

                .tab-content h2 {
                    margin-top: 0;
                    font-size: 1.5em;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                    margin-bottom: 20px;
                }

                #diensten-search {
                    width: 100%;
                    padding: 10px;
                    margin-bottom: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    box-sizing: border-box;
                }

                .dienstenoverzicht-table-dienstenoverzicht {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }

                .dienstenoverzicht-table-dienstenoverzicht th,
                .dienstenoverzicht-table-dienstenoverzicht td {
                    padding: 12px;
                    border-bottom: 1px solid #eee;
                    vertical-align: middle;
                }

                .dienstenoverzicht-table-dienstenoverzicht th input[type="checkbox"],
                .dienstenoverzicht-table-dienstenoverzicht td input[type="checkbox"] {
                    margin: 0;
                    vertical-align: middle;
                }

                .dienstenoverzicht-table-dienstenoverzicht th {
                    background: #f9f9f9;
                    font-weight: bold;
                }

                .dienstenoverzicht-table-dienstenoverzicht tr:hover {
                    background: #f1f1f1;
                }

                .button-primary-dienstenoverzicht,
                .button-secondary-dienstenoverzicht {
                    background-color: #0073aa;
                    border: none;
                    color: #fff;
                    padding: 10px 20px;
                    text-align: center;
                    text-decoration: none;
                    display: inline-block;
                    font-size: 16px;
                    margin: 4px 2px;
                    cursor: pointer;
                    border-radius: 4px;
                    transition: background-color 0.3s ease;
                }

                .button-primary-dienstenoverzicht:hover,
                .button-secondary-dienstenoverzicht:hover {
                    background-color: #005a87;
                }

                .button-secondary-dienstenoverzicht {
                    background-color: #555;
                }

                .notice-dienstenoverzicht {
                    margin: 20px 0;
                    padding: 10px;
                    border-left: 4px solid #0073aa;
                    background-color: #f1f1f1;
                    border-radius: 4px;
                }

                .notice-warning.notice-dienstenoverzicht {
                    border-left-color: #ffba00;
                }

                .notice-success.notice-dienstenoverzicht {
                    border-left-color: #28a745;
                }

                #export-progress-bar {
                    display: none;
                    width: 100%;
                    background-color: #e0e0e0;
                    border-radius: 8px;
                    overflow: hidden;
                    margin-top: 20px;
                }

                #export-progress {
                    height: 20px;
                    width: 0%;
                    background: linear-gradient(90deg, #0073aa, #00a0d2);
                    border-radius: 8px;
                    text-align: center;
                    color: white;
                    font-weight: bold;
                    line-height: 20px;
                    transition: width 0.4s ease-in-out;
                }
            </style>

            <!-- Tab functionaliteit -->
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    var tabs = document.querySelectorAll('.export-import-tabs .nav-tab');
                    var tabContents = document.querySelectorAll('.tab-content');

                    tabs.forEach(function(tab) {
                        tab.addEventListener('click', function(e) {
                            e.preventDefault();
                            tabs.forEach(function(t) { t.classList.remove('nav-tab-active'); });
                            tabContents.forEach(function(content) { content.style.display = 'none'; });

                            tab.classList.add('nav-tab-active');
                            document.querySelector(tab.getAttribute('href')).style.display = 'block';
                        });
                    });

                    document.querySelector('.nav-tab-active').click(); // Open de default tab
                });
            </script>
            <?php
        }

        /**
         * Verwerk het uploaden van bestanden en importeer diensten vanuit een CSV-bestand.
         *
         * @param array $file Het geüploade bestand array.
         *
         * @return void
         */
        public static function handle_file_upload( array $file ): void {
            if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
                wp_die( esc_html__( 'Geen bestand geüpload.', self::TEXT_DOMAIN ) );
            }

            $file_type = wp_check_filetype( $file['name'] );
            if ( 'csv' !== $file_type['ext'] ) {
                wp_die( esc_html__( 'Alleen CSV-bestanden zijn toegestaan.', self::TEXT_DOMAIN ) );
            }

            $file_content = file_get_contents( $file['tmp_name'] );
            $lines        = explode( PHP_EOL, $file_content );
            $header       = str_getcsv( array_shift( $lines ) );

            if ( $header !== [ 'ID', 'Title', 'Basis Pakket Prijzen', 'Uitgebreid Pakket Prijzen', 'Content', 'Summary' ] ) {
                wp_die( esc_html__( 'Ongeldig CSV-bestand.', self::TEXT_DOMAIN ) );
            }

            $imported = 0;
            foreach ( $lines as $line ) {
                $data = str_getcsv( $line );
                if ( 6 === count( $data ) ) {
                    $post_id          = intval( $data[0] );
                    $post_title       = sanitize_text_field( $data[1] );
                    $basis_prijs      = sanitize_text_field( $data[2] );
                    $uitgebreid_prijs = sanitize_text_field( $data[3] );
                    $content          = wp_kses_post( $data[4] );
                    $summary          = sanitize_text_field( $data[5] );

                    $post_data = [
                        'ID'           => $post_id,
                        'post_title'   => $post_title,
                        'post_content' => $content,
                        'post_excerpt' => $summary,
                        'post_status'  => 'publish',
                    ];

                    $new_post_id = wp_insert_post( $post_data, true );
                    if ( ! is_wp_error( $new_post_id ) ) {
                        update_post_meta( $new_post_id, '_basispakket_prijzen', $basis_prijs );
                        update_post_meta( $new_post_id, '_uitgebreid_pakket_prijzen', $uitgebreid_prijs );
                        update_post_meta( $new_post_id, '_samenvatting', $summary );
                        $imported++;
                    }
                }
            }

            wp_redirect( add_query_arg( 'imported', 'true', admin_url( 'edit.php?post_type=diensten&page=diensten-export-import' ) ) );
            exit;
        }

        /**
         * Render de export/import pagina.
         *
         * @return void
         */
        public static function render_export_import_page(): void {
            ?>
            <div class="wrap dienstenoverzicht-page-dienstenoverzicht">
                <?php if ( isset( $_GET['imported'] ) && 'true' === $_GET['imported'] ) : ?>
                    <div class="notice notice-success is-dismissible notice-dienstenoverzicht">
                        <p><?php esc_html_e( 'De import is succesvol voltooid.', self::TEXT_DOMAIN ); ?></p>
                    </div>
                <?php endif; ?>

                <h1 class="export-import-title-dienstenoverzicht"><?php esc_html_e( 'Exporteren en Importeren van Diensten', self::TEXT_DOMAIN ); ?></h1>

                <h2 class="export-import-tabs">
                    <ul>
                        <li><a href="#export" class="nav-tab nav-tab-active"><?php esc_html_e( 'Exporteren', self::TEXT_DOMAIN ); ?></a></li>
                        <li><a href="#import" class="nav-tab"><?php esc_html_e( 'Importeren', self::TEXT_DOMAIN ); ?></a></li>
                    </ul>
                </h2>

                <div id="export" class="tab-content" style="display: block;">
                    <?php self::render_diensten_table(); ?>
                </div>

                <div id="import" class="tab-content">
                    <h2 class="import-section-title-dienstenoverzicht"><?php esc_html_e( 'Importeer Diensten', self::TEXT_DOMAIN ); ?></h2>
                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'import_diensten_nonce', 'import_diensten_nonce_field' ); ?>
                        <input type="hidden" name="action" value="import_diensten" />
                        <input type="file" name="import_file" accept=".csv" required />
                        <input type="submit" class="button button-primary-dienstenoverzicht" value="<?php esc_attr_e( 'Importeer Diensten', self::TEXT_DOMAIN ); ?>" />
                    </form>
                </div>

            </div>
            <?php
        }

    }

    /**
     * Initialiseer de Dienstenoverzicht_ImportExport klasse.
     */
    Dienstenoverzicht_ImportExport::init();

}
?>