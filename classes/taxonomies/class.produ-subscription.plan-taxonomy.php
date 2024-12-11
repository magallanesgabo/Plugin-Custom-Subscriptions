<?php
class PRODUSubscriptionPlanTaxonomy {

    public function __construct() { }

    /**
     * Register the METADATA Taxonomy.
     */
    public static function init() {
        if (!function_exists('register_taxonomy')) {
            return FALSE;
        }

        register_taxonomy('subscription-plan', array('produ-subscription'), array(
            'hierarchical'          => FALSE,
            'labels'                => self::get_labels(),
            'show_ui'               => TRUE,
            'show_in_quick_edit'    => FALSE,
            'meta_box_cb'           => FALSE,
            'show_in_rest'          => TRUE,
            'show_admin_column'     => TRUE,
            'query_var'             => TRUE,
            'rewrite'               => array('slug' => 'subscription-plan'),
        ));

        // self::add_plans();
    }

    /**
     * Return config labels for Taxonomy
     * @static
     */
    private static function get_labels() {
        return array(
            'name'                       => _x('Planes', 'Nombre general de la taxonomía'),
            'singular_name'              => _x('Plan', 'Nombre singular de la taxonomía'),
            'search_items'               => __('Buscar Planes'),
            'popular_items'              => __('Planes Populares'),
            'all_items'                  => __('Todos los Planes'),
            'parent_item'                => NULL,
            'parent_item_colon'          => NULL,
            'edit_item'                  => __('Editar Plan'),
            'update_item'                => __('Actualizar Plan'),
            'add_new_item'               => __('Agregar Nuevo Plan'),
            'new_item_name'              => __('Nuevo Nombre del Plan'),
            'separate_items_with_commas' => __('Separar Planes con comas'),
            'add_or_remove_items'        => __('Agregar o Quitar Planes'),
            'choose_from_most_used'      => __('Seleccionar de los Planes más usados'),
            'not_found'                  => __('No se encontraron Planes'),
            'menu_name'                  => __('Planes'),
        );
    }

    /**
	 * Add plan types terms.
	 */
	public static function add_plans() {
		if ( empty( term_exists( 'test2', 'subscription-plan' ) ) ) {
			$result = wp_insert_term( 'test2', 'subscription-plan', array( 'slug' => 'test2', 'description' => 'esto es desc' ) );
		}
	}

    public static function custom_taxonomy_tinymce() {
        $screen = get_current_screen();
        if (strpos($screen->taxonomy, 'subscription-plan') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('tinymce');
            add_action('admin_footer', array('PRODUSubscriptionPlanTaxonomy', 'initialize_tinymce'));
        }
    }

    public static function initialize_tinymce() { ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                tinyMCE.init({
                    selector: 'textarea#description,textarea#tag-description',
                    mode : "none",
                    toolbar: 'undo redo | bold italic',
                    menubar: false,
                    branding: false,
                    entity_encoding: 'raw',
                    forced_root_block: false,
                    convert_newlines_to_brs: false,
                    height: 300,
                    wpautop: true,
                });
            });
        </script>
    <?php }

    public static function save_custom_taxonomy_description($term_id) {
        if (isset($_POST['description'])) {
            $description = wp_kses_post($_POST['description']);
            update_term_meta($term_id, 'description', $description);
        }
    }
}