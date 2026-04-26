<?php
/**
 * Custom post type registration.
 *
 * @package Dienstenoverzicht
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the service post type.
 */
final class Dienstenoverzicht_CPT {
	public const POST_TYPE = 'diensten';
	private const TEXT_DOMAIN = 'dienstenoverzicht';

	/**
	 * Registers the custom post type.
	 */
	public static function register_post_type(): void {
		$labels = [
			'name'                  => _x( 'Diensten', 'post type general name', self::TEXT_DOMAIN ),
			'singular_name'         => _x( 'Dienst', 'post type singular name', self::TEXT_DOMAIN ),
			'menu_name'             => _x( 'Diensten', 'admin menu', self::TEXT_DOMAIN ),
			'name_admin_bar'        => _x( 'Dienst', 'add new on admin bar', self::TEXT_DOMAIN ),
			'add_new'               => __( 'Nieuwe dienst', self::TEXT_DOMAIN ),
			'add_new_item'          => __( 'Nieuwe dienst toevoegen', self::TEXT_DOMAIN ),
			'new_item'              => __( 'Nieuwe dienst', self::TEXT_DOMAIN ),
			'edit_item'             => __( 'Dienst bewerken', self::TEXT_DOMAIN ),
			'view_item'             => __( 'Dienst bekijken', self::TEXT_DOMAIN ),
			'view_items'            => __( 'Diensten bekijken', self::TEXT_DOMAIN ),
			'all_items'             => __( 'Alle diensten', self::TEXT_DOMAIN ),
			'search_items'          => __( 'Zoek diensten', self::TEXT_DOMAIN ),
			'not_found'             => __( 'Geen diensten gevonden.', self::TEXT_DOMAIN ),
			'not_found_in_trash'    => __( 'Geen diensten gevonden in prullenbak.', self::TEXT_DOMAIN ),
			'featured_image'        => __( 'Uitgelichte afbeelding', self::TEXT_DOMAIN ),
			'set_featured_image'    => __( 'Stel uitgelichte afbeelding in', self::TEXT_DOMAIN ),
			'remove_featured_image' => __( 'Verwijder uitgelichte afbeelding', self::TEXT_DOMAIN ),
			'use_featured_image'    => __( 'Gebruik als uitgelichte afbeelding', self::TEXT_DOMAIN ),
		];

		register_post_type(
			self::POST_TYPE,
			[
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => [ 'slug' => 'diensten' ],
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => 20,
				'menu_icon'          => 'dashicons-clipboard',
				'supports'           => [ 'title', 'editor', 'thumbnail' ],
				'show_in_rest'       => true,
				'show_in_nav_menus'  => true,
				'exclude_from_search' => false,
				'taxonomies'         => [ Dienstenoverzicht_Taxonomy::TAXONOMY ],
			]
		);
	}
}
