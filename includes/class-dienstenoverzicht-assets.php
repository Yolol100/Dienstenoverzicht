<?php
/**
 * Front-end asset handling.
 *
 * @package Dienstenoverzicht
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conditionally enqueues front-end assets.
 */
final class Dienstenoverzicht_Assets {
	/**
	 * Registers hooks.
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_frontend_assets' ] );
	}

	/**
	 * Enqueues shortcode assets only where needed.
	 */
	public static function enqueue_frontend_assets(): void {
		if ( is_admin() || ! self::should_enqueue_assets() ) {
			return;
		}

		wp_enqueue_style(
			'dienstenoverzicht-style',
			DIENSTENOVERZICHT_PLUGIN_URL . 'css/dienstenoverzicht-style.css',
			[],
			DIENSTENOVERZICHT_VERSION
		);

		wp_enqueue_script(
			'dienstenoverzicht-script',
			DIENSTENOVERZICHT_PLUGIN_URL . 'js/dienstenoverzicht-script.js',
			[],
			DIENSTENOVERZICHT_VERSION,
			true
		);

		wp_localize_script(
			'dienstenoverzicht-script',
			'dienstenoverzichtSettings',
			[
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'dienstenoverzicht_filter' ),
				'noResultsText'     => __( 'Geen diensten gevonden.', 'dienstenoverzicht' ),
				'errorText'         => __( 'Er ging iets mis bij het filteren.', 'dienstenoverzicht' ),
				'searchPlaceholder' => __( 'Zoek diensten...', 'dienstenoverzicht' ),
				'searchLabel'       => __( 'Zoek diensten', 'dienstenoverzicht' ),
			]
		);
	}

	/**
	 * Checks whether the current request needs front-end assets.
	 */
	private static function should_enqueue_assets(): bool {
		if ( is_singular( Dienstenoverzicht_CPT::POST_TYPE ) || is_post_type_archive( Dienstenoverzicht_CPT::POST_TYPE ) ) {
			return true;
		}

		$post = get_post();

		return $post instanceof WP_Post && has_shortcode( $post->post_content, 'dienstenoverzicht' );
	}
}
