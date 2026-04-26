<?php
/**
 * Taxonomy registration.
 *
 * @package Dienstenoverzicht
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the service category taxonomy.
 */
final class Dienstenoverzicht_Taxonomy {
	public const TAXONOMY = 'dienstenoverzicht_categorie';
	private const TEXT_DOMAIN = 'dienstenoverzicht';

	/**
	 * Registers the taxonomy.
	 */
	public static function register_taxonomy(): void {
		$labels = [
			'name'              => _x( 'Categorieën', 'taxonomy general name', self::TEXT_DOMAIN ),
			'singular_name'     => _x( 'Categorie', 'taxonomy singular name', self::TEXT_DOMAIN ),
			'search_items'      => __( 'Categorieën zoeken', self::TEXT_DOMAIN ),
			'all_items'         => __( 'Alle categorieën', self::TEXT_DOMAIN ),
			'parent_item'       => __( 'Hoofdcategorie', self::TEXT_DOMAIN ),
			'parent_item_colon' => __( 'Hoofdcategorie:', self::TEXT_DOMAIN ),
			'edit_item'         => __( 'Categorie bewerken', self::TEXT_DOMAIN ),
			'update_item'       => __( 'Categorie bijwerken', self::TEXT_DOMAIN ),
			'add_new_item'      => __( 'Nieuwe categorie toevoegen', self::TEXT_DOMAIN ),
			'new_item_name'     => __( 'Nieuwe categorienaam', self::TEXT_DOMAIN ),
			'menu_name'         => __( 'Categorieën', self::TEXT_DOMAIN ),
		];

		register_taxonomy(
			self::TAXONOMY,
			[ Dienstenoverzicht_CPT::POST_TYPE ],
			[
				'hierarchical'      => true,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => [ 'slug' => 'diensten-categorie' ],
				'show_in_rest'      => true,
			]
		);
	}
}
