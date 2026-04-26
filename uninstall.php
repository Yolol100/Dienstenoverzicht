<?php
/**
 * Uninstall handler for Dienstenoverzicht Plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = [
    'dienstenoverzicht_title',
    'dienstenoverzicht_items_per_page',
    'dienstenoverzicht_show_prices',
    'dienstenoverzicht_gratis_advies_url',
    'dienstenoverzicht_summary_font_size',
    'dienstenoverzicht_button_font_size',
    'dienstenoverzicht_title_font_size',
    'dienstenoverzicht_prijs_font_size',
    'dienstenoverzicht_title_color',
    'dienstenoverzicht_prijs_color',
    'dienstenoverzicht_button_color',
    'dienstenoverzicht_button_hover_color',
];

foreach ( $options as $option ) {
    delete_option( $option );
}
