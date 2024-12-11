<?php
class PRODUSubscriptionPaymentMethodTaxonomy {
    /**
     * Register the METADATA Taxonomy.
     */
    public static function init() {
        if ( !function_exists( 'register_taxonomy' ) ) {
            return FALSE;
        }

        register_taxonomy('subscription-payment-method', array('produ-subscription'), array(
            'hierarchical'          => FALSE,
            'labels'                => self::get_labels(),
            'show_ui'               => TRUE,
            'show_in_quick_edit'    => FALSE,
            'meta_box_cb'           => FALSE,
            'show_in_rest'          => TRUE,
            'show_admin_column'     => TRUE,
            'query_var'             => TRUE,
            'rewrite'               => array('slug' => 'subscription-payment-method'),
        ));

        self::add_payment_terms();
    }

    /**
     * Return config labels for Taxonomy
     * @static
     */
    private static function get_labels() {
        return array(
            'name'                  => _x('Forma de pago', 'Post type general name'),
            'singular_name'         => _x('Forma de pago', 'Post type singular name'),
            'menu_name'             => _x('Formas de pago', 'Admin Menu text'),
            'name_admin_bar'        => _x('Forma de pago', 'Add New on Toolbar'),
            'add_new'               => __('Agregar nueva'),
            'add_new_item'          => __('Agregar nueva forma de pago'),
            'new_item'              => __('Nueva forma de pago'),
            'edit_item'             => __('Editar forma de pago'),
            'view_item'             => __('Ver forma de pago'),
            'all_items'             => __('Todos las formas de pago'),
            'parent_item'           => NULL,
            'parent_item_colon'     => NULL,
            'search_items'          => __('Buscar forma de pago'),
            'not_found'             => __('Forma de pago no encontrada.'),
            'not_found_in_trash'    => __('Forma de pago no encontrada en la papelera.')
        );
    }

    /**
	 * Add payment method terms.
	 */
	public static function add_payment_terms() {
		if ( empty( term_exists( 'Gratis', 'subscription-payment-method' ) ) ) {
			wp_insert_term( 'Gratis', 'subscription-payment-method', array( 'slug' => 'gratis' ) );
		}

		if ( empty( term_exists( 'Amazon Pay', 'subscription-payment-method' ) ) ) {
			wp_insert_term( 'Amazon Pay', 'subscription-payment-method', array( 'slug' => 'amazon-pay' ) );
		}

        if ( empty( term_exists( 'Paypal', 'subscription-payment-method' ) ) ) {
			wp_insert_term( 'Paypal', 'subscription-payment-method', array( 'slug' => 'paypal' ) );
		}

        if ( empty( term_exists( 'Transferencia Interbancaria', 'subscription-payment-method' ) ) ) {
			wp_insert_term( 'Transferencia Interbancaria', 'subscription-payment-method', array( 'slug' => 'transferencia-interbancaria' ) );
		}

        if ( empty( term_exists( 'Cheque', 'subscription-payment-method' ) ) ) {
			wp_insert_term( 'Cheque', 'subscription-payment-method', array( 'slug' => 'cheque' ) );
		}

        if ( empty( term_exists( 'Tarjeta de crédito/débito', 'subscription-payment-method' ) ) ) {
			wp_insert_term( 'Tarjeta de crédito/débito', 'subscription-payment-method', array( 'slug' => 'tarjeta-credito-debito' ) );
		}
	}
}