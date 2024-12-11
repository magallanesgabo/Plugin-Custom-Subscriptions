<?php
class PRODUSubscriptionTypeTaxonomy {

    public function __construct() { }

    /**
     * Register the METADATA Taxonomy.
     */
    public static function init() {
        if ( !function_exists( 'register_taxonomy' ) ) {
            return FALSE;
        }

        register_taxonomy('subscription-type', array('produ-subscription'), array(
            'hierarchical'          => FALSE,
            'labels'                => self::get_labels(),
            'show_ui'               => TRUE,
            'show_in_quick_edit'    => FALSE,
            'meta_box_cb'           => FALSE,
            'show_in_rest'          => TRUE,
            'show_admin_column'     => TRUE,
            'query_var'             => TRUE,
            'rewrite'               => array('slug' => 'subscription-type'),
        ));

        self::add_type_terms();
    }

    /**
     * Return config labels for Taxonomy
     * @static
     */
    private static function get_labels() {
        return array(
            'name'                  => _x('Tipo de plan', 'Post type general name'),
            'singular_name'         => _x('Tipo de plan', 'Post type singular name'),
            'menu_name'             => _x('Tipos de planes', 'Admin Menu text'),
            'name_admin_bar'        => _x('Tipo', 'Add New on Toolbar'),
            'add_new'               => __('Agregar nuevo'),
            'add_new_item'          => __('Agregar nuevo tipo'),
            'new_item'              => __('Nuevo tipo'),
            'edit_item'             => __('Editar tipo'),
            'view_item'             => __('Ver tipo'),
            'all_items'             => __('Todos los tipos'),
            'parent_item'           => NULL,
            'parent_item_colon'     => NULL,
            'search_items'          => __('Buscar tipo'),
            'not_found'             => __('Tipo no encontrado.'),
            'not_found_in_trash'    => __('Tipo no encontrado en la papelera.')
        );
    }

    /**
	 * Add type terms.
	 */
	public static function add_type_terms() {
		if ( empty( term_exists( 'Individual', 'subscription-type' ) ) ) {
			wp_insert_term( 'Individual', 'subscription-type', array( 'slug' => 'individual' ) );
		}

		if ( empty( term_exists( 'Corporativa', 'subscription-type' ) ) ) {
			wp_insert_term( 'Corporativa', 'subscription-type', array( 'slug' => 'corporativa' ) );
		}
	}
}