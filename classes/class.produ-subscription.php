<?php

use Dompdf\Dompdf;
use Dompdf\Options;

// require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'classes/class.produ-payment.php';
// require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'classes/class.produ-member.php';
require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'inc/subscription-helper.php';
require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'classes/class.produ-subscriber-list.php';
require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'classes/taxonomies/class.produ-subscription.type-taxonomy.php';
require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'classes/taxonomies/class.produ-subscription.plan-taxonomy.php';
require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'classes/taxonomies/class.produ-subscription.payment-method-taxonomy.php';
require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'classes/class.produ-emails-notifications.php';

class PRODUSubscription {
    private static $initiated = FALSE;

    public function __construct() {
        add_action('init', array(&$this, 'init'));
        add_action('init', array('PRODUSubscriptionTypeTaxonomy', 'init'));
        add_action('init', array('PRODUSubscriptionPlanTaxonomy', 'init'));
        add_action('init', array('PRODUSubscriptionPaymentMethodTaxonomy', 'init'));

        add_action('admin_menu', array(&$this, 'subscription_submenu'));
        add_action('add_meta_boxes', array(&$this, 'susbcription_extra_metaboxes'));
        add_action('admin_enqueue_scripts', array(&$this, 'add_assets'));
        add_action('admin_init', array(&$this, 'process_registration_form'));
        add_action('wp_ajax_get_data_suscriptor',  array(&$this, 'get_data_suscriptor') );
        add_action('wp_ajax_nopriv_get_data_suscriptor',  array(&$this, 'get_data_suscriptor'));
        add_action('wp_ajax_get_data_plan',  array(&$this, 'get_data_plan') );
        add_action('wp_ajax_nopriv_get_data_plan',  array(&$this, 'get_data_plan'));
        add_filter('post_row_actions', array(&$this, 'set_subscription_actions'), 10, 2);
        add_filter('manage_produ-subscription_posts_columns', array(&$this, 'manage_edit_subs_columns'));
        add_action('manage_produ-subscription_posts_custom_column', array(&$this, 'manage_edit_subs_column'), 10, 2);
        add_filter('get_edit_post_link', array(&$this, 'custom_edit_post_link'), 10, 3);
        add_filter('acf/fields/taxonomy/query/name=subscriptions_sub_plan',array( $this, 'add_args_tax_plan' ), 10, 3);
        
        add_action('wp_ajax_generate_invoice_pdf', array(&$this, 'generate_invoice_pdf'));
        add_action('admin_enqueue_scripts',  array(&$this, 'produ_subscription_dashboard_assets'), 1 ); 
        add_action('admin_init', array(&$this, 'replace_dashboard'));
        add_action('rest_api_init', array(&$this, 'register_rest_routes'));

        $this->load_email_notifications();
    }
    
    /**
     * Load the class of email notifications
     */
    private function load_email_notifications() {
        $emails = new PRODUSubscriptionEmailsNotifications();
    }

    public static function init() {
        if (!self::$initiated) {
            self::init_hooks();
        }
    }

    /**
     * Initializes WordPress hooks
     */
    private static function init_hooks() {
        self::$initiated = TRUE;
        self::register_cpt();
    }

    /**
     * registering custom post type
     * @static
     */
    private static function register_cpt() {
        $args = array(
            'label'                 => _('Suscripciones PRODU'),
            'labels'                => PRODUSubscription::get_labels(),
            'public'                => TRUE,
            'exclude_from_search'   => TRUE,
            'supports'              => array(''),
            'hierarchical'          => FALSE,
            'taxonomies'            => array(''),
            'rewrite'               => array('slug' => 'susbcription', 'with_front' => FALSE),
            'capability_type'       => 'post',
            'publicly_queryable'    => FALSE,
            'menu_icon'             => 'dashicons-groups',
            'menu_position'         => 20,
            'show_in_menu'          => FALSE
        );

        register_post_type('produ-subscription', $args);
    }
    
    /**
     * Return config labels for CPT
     * @static
     */
    private static function get_labels() {
        return array(
            'name'                  => _x('Suscripciones PRODU', 'Nombre general del CPT'),
            'singular_name'         => _x('Suscripción', 'Nombre singular del CPT'),
            'menu_name'             => __('Suscripciones PRODU'),
            'all_items'             => __('Suscripciones'),
            'add_new'               => __('Nueva suscripción'),
            'add_new_item'          => __('Agregar Nueva Suscripción'),
            'edit_item'             => __('Editar Suscripción'),
            'new_item'              => __('Nueva Suscripción'),
            'view_item'             => __('Ver Suscripción'),
            'view_items'            => __('Ver Todas las Suscripciones'),
            'search_items'          => __('Buscar Suscripciones'),
            'not_found'             => __('No se encontraron suscripciones'),
            'not_found_in_trash'    => __('No se encontraron suscripciones en la papelera'),
            'parent_item_colon'     => __('Suscripción Padre:'),
            'archives'              => __('Archivo de Suscripciones'),
            'attributes'            => __('Atributos de la Suscripción'),
            'insert_into_item'      => __('Insertar en la Suscripción'),
            'uploaded_to_this_item' => __('Cargado en esta Suscripción'),
            'filter_items_list'     => __('Filtrar lista de suscripciones'),
            'items_list_navigation' => __('Navegación de lista de suscripciones'),
            'items_list'            => __('Lista de Suscripciones'),
        );
    }
    
    /**
     * Flushing of the WordPress rewrite rules
     * @static
     */
    public static function flush_rewrite() {
        if (!function_exists('flush_rewrite_rules')) {
            return false;
        }
        
        flush_rewrite_rules(false);
    }
    
    public function subscription_submenu() {
        global $submenu;
        
        add_menu_page(
            'Suscripciones PRODU',
            'Suscripciones PRODU',
            'manage_options',
            'subscription-dashboard',
            'PRODUSubscription::show_generic_page',
            'dashicons-groups',    
            20
        );
        
        add_submenu_page(
            'subscription-dashboard',
            'Dashboard',
            'Dashboard',
            'read_private_posts',
            'subscription-dashboard',
            'PRODUSubscription::show_generic_page',
        );
        
        add_submenu_page(
            'subscription-dashboard',  
            'Suscripciones',           
            'Suscripciones',     
            'manage_options',          
            'edit.php?post_type=produ-subscription',  
            NULL                        
        );
    
        $taxonomies = get_object_taxonomies('produ-subscription', 'objects');
        foreach ($taxonomies as $taxonomy_slug => $taxonomy) {
            add_submenu_page(
                'subscription-dashboard',    
                $taxonomy->labels->name,     
                $taxonomy->labels->name,    
                'manage_options',          
                'edit-tags.php?taxonomy=' . $taxonomy_slug . '&post_type=produ-subscription'
            );
        }    

        add_submenu_page(
            'subscription-dashboard',
            'Suscriptores',
            'Suscriptores',
            'read_private_posts',
            'subscription-member',
            'PRODUSubscription::render_subscriber_page'
        );


        add_submenu_page(
            'subscription-dashboard',
            'Nuevo Suscriptor',
            'Nuevo Suscriptor',
            'read_private_posts',
            'subscription-new-member',
            'PRODUSubscription::render_subscriber_form',
        );
    }

    /**
     * Enqueue extra styles and scripts.
     *
     * @static $hook_suffix The current admin page.
     */
    public function add_assets($hook_suffix) {
        global $typenow;
        $version = '1.6.0';
        if ('produ-subscription' !== $typenow && 'subscription-payment' !== $typenow) {
            return;
        }

        if (isset($_GET['post_type']) && ($_GET['post_type'] === 'produ-subscription' || $_GET['post_type'] === 'subscription-payment') && is_admin()) {
            wp_enqueue_style(
                'produ-subscription-admin-css',
                esc_url(plugins_url('produ-subscription/assets/css/admin.css', '')),
                array(),
                $version,
                'all'
            );

            wp_enqueue_script(
                'produ-subscription-scripts',
                plugins_url('produ-subscription/assets/js/produ-subscription-scripts.js'),
                array('jquery'),
                $version,
                FALSE
            );

        // Si no hay $post, intenta obtenerlo manualmente
        if (!isset($post) && isset($_GET['post'])) {
            $post = get_post($_GET['post']);
        }

        // Verifica que $post esté definido y luego pásalo a JavaScript
        if (isset($post)) {
            wp_localize_script('produ-subscription-scripts', 'generateInvoice', array(
                'postId' => $post->ID,
                'ajaxurl' => admin_url('admin-ajax.php'),
            ));
        }

            $variable_to_js = [
                'nonce'     => wp_create_nonce('acf_nonce'),
                'ajaxurl'   => admin_url('admin-ajax.php'),
                'logo'      => get_template_directory_uri().'/assets/images/PRODU35LOGO.png'
            ];
            wp_localize_script('produ-subscription-scripts', 'scriptVars', $variable_to_js);
        }
    }

    public function produ_subscription_dashboard_assets() {
        $page = $_GET['page'] ?? '';

        if (($page === 'subscription-dashboard') && is_admin() || get_current_screen()->base === 'dashboard') {
        $version = '1.0.3';

        wp_enqueue_style(
            'produ-subscription-dashboard',
            esc_url(plugins_url('produ-subscription/templates/admin/dashboard/css/dashboard.css', '')),
            array(),
            $version,
            'all'
        );
    
        wp_enqueue_script(
            'chartjs',
            plugins_url('produ-subscription/templates/admin/dashboard/js/chart.js'), 
            array(),
            '3.9.1',
            false 
        );

        wp_enqueue_script(
            'chartjs-plugin-zoom.min',
            plugins_url('produ-subscription/templates/admin/dashboard/js/chartjs-plugin-zoom.min.js'), 
            array(),
            '3.9.1',
            false 
        );

        wp_enqueue_script(
            'hammer.min',
            plugins_url('produ-subscription/templates/admin/dashboard/js/hammer.min.js'), 
            array(),
            '3.9.1',
            false 
        );
    
        wp_enqueue_script(
            'produ-subscription-line-chart',
            plugins_url('produ-subscription/templates/admin/dashboard/js/line-chart.js'),
            array('chartjs'), 
            $version,
            true 
        );

        wp_localize_script('produ-subscription-line-chart', 'siteData', array(
            'baseUrl' => esc_url(home_url())
        ));
    
        wp_enqueue_script(
            'produ-subscription-doughnut-chart',
            plugins_url('produ-subscription/templates/admin/dashboard/js/doughnut-chart.js'),
            array('chartjs'), 
            $version,
            true 
        );
        }
    }

    /**   
     * Function that replaces the default WordPress dashboard for users with 'administrator' or 'editor-suscripciones' roles   
     */
    public function replace_dashboard() {
        if ( current_user_can( 'administrator' ) || current_user_can( 'editor-suscripciones' ) ) {
            add_action( 'in_admin_header', array( $this, 'add_custom_dashboard_above_metaboxes' ), 1 );
        }
    }
    /**
     * Function that adds suscription dashboard above the metaboxes
     */
    public function add_custom_dashboard_above_metaboxes() {
        if ( is_admin() && function_exists('get_current_screen') && get_current_screen()->base === 'dashboard' ) {
            echo '<div class="wrap" style="margin-bottom: 20px;">';
            require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'templates/admin/dashboard/dashboard.php';
            echo '</div>';
        }
    }

    public function susbcription_extra_metaboxes() {
        global $post;

        $current_screen = get_current_screen();
        $screen_id = $current_screen ? $current_screen->id : '';
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';

        if ($post_type === 'produ-subscription' || $post->post_type = 'produ-subscription') {
            add_meta_box(
                'subs_owner_fields_metabox',
                'Datos del propietario de la suscripción',
                'PRODUSubscription::show_generic_page_pro',
                'produ-subscription',
                'normal',
                'default'
            );

            add_meta_box(
                'subs_plan_fields_metabox',
                'Plan',
                'PRODUSubscription::show_generic_page_plan',
                'produ-subscription',
                'normal',
                'default'
            );

            add_meta_box(
                'meta_box_factura',
                'Generar Factura',          
                'PRODUSubscription::meta_box_invoice', 
                'produ-subscription',       
                'side',                     
                'default'                      
            );
        }
    }

    public function custom_edit_post_link($url, $post_id, $context) {
        $post_type = get_post_type($post_id);
        if ($post_type === 'produ-subscription') {
            $url = add_query_arg('post_type', 'produ-subscription', $url);
        }
        return $url;
    }

    public static function render_subscriber_form() {
        $user_id = isset($_GET['member_id'])?esc_sql($_GET['member_id']):'';
        $acf_user_id = $user_id?'user_'.$user_id:'new_post';
        $user_data = get_userdata($user_id);
        $title = $user_id?'Actualizar suscriptor':'Nuevo suscriptor';
        $user_fields = get_fields('user_' . $user_id);

        if (isset($_GET['message'])) {
            if ($_GET['message'] == 'success') {
                echo '<div class="updated"><p>Usuario actualizado con éxito.</p></div>';
            } elseif ($_GET['message'] == 'error') {
                echo '<div class="error"><p>Error al registrar el usuario.</p></div>';
            }
        }
    ?>
        <div class="wrap" id="create-sub-container">
            <h1 class="wp-heading-inline"><?php echo $title; ?></h1>
            <div id="poststuff">
                <form method="post" action="">
                    <input type="hidden" name="custom_registration_nonce" value="<?php echo wp_create_nonce('custom-registration-nonce'); ?>">

                    <div class="postbox acf-postbox">
                        <div class="postbox-header">
                            <h2 class="hndle ui-sortable-handle">Datos usuario</h2>
                        </div>
                        <div class="acf-fields acf-form-fields -top">
                            <div class="acf-field acf-field-text -c0" style="width: 50%; min-height: 87px;" data-width="50">
                                <div class="acf-label">
                                    <label for="username">Username (nickname) *</label>
                                </div>
                                <div class="acf-input">
                                    <div class="acf-input-wrap">
                                        <input <?php if ($acf_user_id !== 'new_post') : ?>readonly<?php endif; ?> name="username" type="text" id="username" value="<?php echo isset($user_data->data->user_login)?$user_data->data->user_login:''; ?>" aria-required="true" autocapitalize="none" autocorrect="off" autocomplete="off" maxlength="60" required>
                                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="acf-field acf-field-text" style="width: 50%; min-height: 87px;" data-width="50">
                                <div class="acf-label">
                                    <label for="email">Correo Electrónico *</label>
                                </div>
                                <div class="acf-input">
                                    <div class="acf-input-wrap">
                                        <input name="email" type="email" id="email" value="<?php echo isset($user_data->data->user_email)?$user_data->data->user_email:''; ?>" aria-required="true" required>
                                    </div>
                                </div>
                            </div>

                            <div class="acf-field acf-field-text -c0" style="width: 50%; min-height: 87px;" data-width="50">
                                <div class="acf-label">
                                    <label for="first_name">Nombre *</label>
                                </div>
                                <div class="acf-input">
                                    <div class="acf-input-wrap">
                                        <input name="first_name" type="text" id="first_name" value="<?php echo isset($user_data->first_name)?$user_data->first_name:''; ?>" required aria-required="true">
                                    </div>
                                </div>
                            </div>

                            <div class="acf-field acf-field-text" style="width: 50%; min-height: 87px;" data-width="50">
                                <div class="acf-label">
                                    <label for="last_name">Apellidos *</label>
                                </div>
                                <div class="acf-input">
                                    <div class="acf-input-wrap">
                                        <input name="last_name" type="text" id="last_name" value="<?php echo isset($user_data->last_name)?$user_data->last_name:''; ?>" required aria-required="true">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="postbox acf-postbox">
                        <div class="postbox-header">
                            <h2 class="hndle ui-sortable-handle">Datos complementarios</h2>
                        </div>
                        <?php
                            acf_form(array(
                                'form'                  => TRUE,
                                'field_groups'          => array('group_650cb35a55e69', 'group_650cb8c074d62'),
                                'post_id'               => $acf_user_id,
                                'new_user'              => TRUE,
                                'submit_value'          => $title,
                                'html_submit_button'    => '<input type="submit" name="createusersub" id="createusersub" class="acf-button button button-primary button-large" value="%s" />',
                            ));
                            acf_enqueue_scripts();
                        ?>

                    </div>
                    <!-- <p class="submit">
                        <input type="submit" name="createusersub" id="createusersub" class="button button-primary" value="<?php //echo $title; ?>">
                    </p> -->
                </form>
            </div>
        </div>
    <?php }

    public function process_registration_form() {
        if (is_admin()
            && isset($_POST['createusersub'])
            && current_user_can('create_users')
            && isset($_POST['custom_registration_nonce'])
            && wp_verify_nonce($_POST['custom_registration_nonce'], 'custom-registration-nonce') ) {

            $username = sanitize_user($_POST['username']);
            $email = sanitize_email($_POST['email']);
            $password = wp_generate_password();
            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);

            if (isset($_POST['user_id']) && $_POST['user_id']) {
                $user_id = $_POST['user_id'];
                $userdata = array(
                    'ID'            => $user_id,
                    'user_email'    => $email,
                    'first_name'    => $first_name,
                    'last_name'     => $last_name,
                );

                $updated_user = wp_update_user($userdata);

                if (! is_wp_error($updated_user)) {
                    if (isset($_POST['acf'])) {

                        foreach ($_POST['acf'] as $key => $value) {
                            update_field($key, $value, 'user_'.$user_id);
                        }

                        $data = [
                            'plan_id' => $_POST['acf']['field_663d42316cb30'],
                        ];

                        $active = SubscriptionHelper::get_subscription_by_user($user_id);

                        #Si no tiene suscripción se crea una
                        if ($active === NULL) {
                            $subscription_id = $this->create_subscription($user_id, $data);
                            if ($subscription_id !== FALSE) {
                                $redirect_url = add_query_arg('message', 'success', $_SERVER['REQUEST_URI']);
                                wp_redirect($redirect_url);
                                exit;
                            } else {
                                $redirect_url = add_query_arg('message', 'error', $_SERVER['REQUEST_URI']);
                                wp_redirect($redirect_url);
                                exit;
                            }
                        }
                    }

                    $redirect_url = add_query_arg('message', 'success', $_SERVER['REQUEST_URI']);
                    wp_redirect($redirect_url);
                    exit;
                } else {
                    $redirect_url = add_query_arg('message', 'error', $_SERVER['REQUEST_URI']);
                    wp_redirect($redirect_url);
                    exit;
                }
            } else {
                $user_data = array(
                    'user_login'    => $username,
                    'user_email'    => $email,
                    'user_pass'     => $password,
                    'first_name'    => $first_name,
                    'last_name'     => $last_name,
                    'user_url'      => '',
                    'description'   => '',
                    'role'          => 'subscriber'
                );

                $user_id = wp_insert_user( $user_data );
                if (! is_wp_error($user_id)) {
                    if (isset($_POST['acf'])) {
                        foreach ($_POST['acf'] as $key => $value) {
                            update_field($key, $value, 'user_' . $user_id);
                        }
                    }

                    $data = [
                        'plan_id' => $_POST['acf']['field_663d42316cb30'],
                    ];

                    $subscription_id = $this->create_subscription($user_id, $data);

                    if ($subscription_id !== FALSE) {
                        $redirect_url = add_query_arg('message', 'success', $_SERVER['REQUEST_URI']);
                        wp_redirect($redirect_url);
                        exit;
                    } else {
                        $redirect_url = add_query_arg('message', 'error', $_SERVER['REQUEST_URI']);
                        wp_redirect($redirect_url);
                        exit;
                    }
                } else {
                    $redirect_url = add_query_arg('message', 'error', $_SERVER['REQUEST_URI']);
                    wp_redirect($redirect_url);
                    exit;
                }
            }
        }
    }

    public static function create_subscription($user_id, $data) {
        $data_user = get_userdata($user_id);

        if ($data_user) {
            # ACF Field y metadatos usuario
            $fields = get_fields('user_'.$user->WpID);
            // $metas = get_user_meta( $user->WpID);

            $title = "$data_user->first_name $data_user->last_name";
            $new_post = array(
                'post_title'    => $title,
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'produ-subscription',
                'post_date'     => current_time('mysql'),
            );

            $post_id = wp_insert_post($new_post);

            if ($post_id) {
                #Validar plan, si es free, pago queda como pagado
                $default = get_term_by('slug', 'default', 'subscription-plan');
                $status = 'activa';
                $plan = get_term($data['plan_id'], 'subscription-plan');
                $gratis = get_term_by('slug', 'gratis', 'subscription-payment-method');

                if ( $plan && !is_wp_error($plan) ) {
                    $selected_plan = get_fields('term_'.$data['plan_id']);

                    $duration = 0;
                    switch ($selected_plan['plans_plan_duration']) {
                        case 'ilimitado':
                            $duration = -1;
                            break;
                        case 'anual':
                            $duration = 365;
                            break;
                        case 'mensual':
                            $duration = 30;
                            break;
                        case 'trimestral':
                            $duration = 90;
                            break;
                    }

                    $today = new DateTime(current_time('mysql'));
                    $begin = $today->format('Y-m-d');
                    $end = '';
                    if ($duration !== -1) {
                        $today->modify('+'.$duration.' days');
                        $end = $today->format('Y-m-d');
                    }


                    if ($plan->slug === 'default') {
                        $method = $gratis->term_id;
                        $status_payment = 'aprobado';
                        $status = 'activa';
                    } else {
                        $method = FALSE;
                        $status_payment = 'pendiente';
                        $status = 'inactiva';
                    }

                    #Suscripción
                    update_field('subscriptions_sub_type', $selected_plan['plans_plan_type'], $post_id);
                    update_field('subscriptions_sub_plan',  $data['plan_id'], $post_id);
                    update_field('subscriptions_sub_owner', $user_id, $post_id);
                    update_field('subscriptions_sub_beneficiaries', FALSE, $post_id);
                    update_field('subscriptions_sub_begin_date', $begin, $post_id);
                    update_field('subscriptions_sub_end_date', $end, $post_id);
                    update_field('subscriptions_sub_status', $status, $post_id);
                    update_post_meta('subscriptions_sub_grace_period', 0, $post_id);

                    #Facturación
                    update_field('billing_name', $title, $post_id);
                    update_field('billing_email', $data_user->data->user_email, $post_id);
                    update_field('billing_phone', $fields['phone'], $post_id);
                    update_field('billing_company', $fields['subscriber_company'], $post_id);
                    update_field('billing_address', $fields['address'], $post_id);

                    #Pago
                    update_field('payments_method', $method, $post_id);
                    update_field('payments_plan_price', $selected_plan['plans_plan_price'], $post_id);
                    update_field('payments_amount', $selected_plan['plans_plan_price'], $post_id);
                    update_field('payments_date', $begin, $post_id);
                    update_field('payments_status', $status_payment, $post_id);
                    update_field('payments_description', FALSE, $post_id);
                    update_field('payments_bank', FALSE, $post_id);
                    update_field('payments_card', FALSE, $post_id);

                    #Se registran datos extras para usuario
                    update_user_meta($user_id, '_wp_user_subscription_initial_plan_id', $_POST['acf']['field_663d42316cb30']);
                    update_user_meta($user_id, '_wp_user_subscription_plan_id', $_POST['acf']['field_663d42316cb30']);
                    update_user_meta($user_id, '_wp_user_subscription_initial_subscription_id', $post_id);
                    update_user_meta($user_id, '_wp_user_subscription_subscription_id', $post_id);
                    update_user_meta($user_id, '_wp_user_subscription_member_since', date('Y-m-d'));
                    update_user_meta($user_id, '_wp_user_subscription_login_enabled', TRUE);

                    # Esta dato prevalece sobre _wp_user_subscription_login_enabled, se debe actualizar cuando se cambie el estado de una suscripción
                    # Activa, vencida _wp_user_subscription_enabled es TRUE
                    # Inactiva _wp_user_subscription_enabled es FALSE
                    update_user_meta($user_id, '_wp_user_subscription_enabled', TRUE);

                    update_user_meta($user_id, '_wp_user_subscription_last_access', '0000-00-00');
                    update_user_meta($user_id, '_wp_user_subscription_last_access_from_ip', '');

                    if (isset($_POST['send_user_notification']) && $_POST['send_user_notification']) {
                        // wp_new_user_notification($user_id, NULL, 'user');
                    }
                }
                return $post_id;
            }
            return FALSE;
        }
        return FALSE;
    }

    public static function render_subscriber_page() {
        if (isset($_GET['member_id']) && $_GET['member_id']) {
            self::render_subscriber_form();
        } else {
            $new_susbcriber_link = admin_url('subscription-dashboard&page=subscription-new-member');
            $subscribersTable = new PRODUSubscriberList();
            $subscribersTable->prepare_items();
            echo '<div class="wrap">';
            echo '<h1 class="wp-heading-inline">Suscriptores</h1>';
            echo '<a href="'.$new_susbcriber_link.'" class="page-title-action">Nuevo suscriptor</a>';
            echo '<div style="float: right; margin-bottom: 5px;">';
            echo '<form id="posts-filter" method="get">';
            echo '<input type="hidden" name="post_type" value="produ-subscription" />';
            echo '<input type="hidden" name="page" value="subscription-member" />';
            $subscribersTable->search_box('Buscar suscriptores', 'subscriber_search');
            echo '</form>';
            echo '</div>';
            $subscribersTable->display();
            echo '</div>';
        }
    }

    public static function show_generic_page($html_id = 'default_data') {
        echo '<div class="wrap"><div id="'.$html_id.'">'.$html_id.'</div></div>';
        $template = require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'templates/admin/dashboard/dashboard.php';
        echo $template;
    }

    public static function show_generic_page_pro() {
        echo '<div class="wrap" id="datos"><table width="100%"><tr><td><label>Nombre:</label><input type="text" readonly id="pro_name" class="form-control " style="width:100%;"></td><td><label>Email:</label><input type="text" readonly id="email_pro" class="form-control " style="width:100%;"></td></tr><tr><td colspan="2"><label>Dirección:</label><input type="text" readonly id="pro_address" class="form-control" style="width:100%;"></td></tr></table></div>';
    }

    public static function show_generic_page_plan() {
        echo '<div class="wrap" id="datos"><table width="100%"><tr><td><label>Nombre:</label><input type="text" readonly id="plan_name" class="form-control " style="width:100%;"></td><td><label>Duración:</label><input type="text" readonly id="plan_duration" class="form-control " style="width:100%;"></td></tr><tr><td><label>Precio:</label><input type="text" readonly id="plan_price" class="form-control" style="width:100%;"></td><td><label>Nro Usuarios:</label><input type="text" readonly id="plan_users" class="form-control" style="width:100%;"></td></tr></table></div>';
    }

    public static function meta_box_invoice($post) {
        echo '<div class="loader-container">';
        echo '<button type="button" class="button button-primary button-large" id="generate-invoice" style="margin-right: 10px;">Generar Factura</button>';
        echo '<div class="loader" style="display:none;"></div>';
        echo '</div>';
    }    

    function generate_invoice_pdf() {
        if ( !isset($_POST['post_id']) || !is_admin() ) {
            wp_send_json_error('Invalid request');
        }
    
        $post_id = intval($_POST['post_id']);
        $plan_id = get_field('subscriptions_sub_plan', $post_id);
        $plan = $plan_id ? get_term($plan_id)->name : 'Plan no encontrado';
        $billing_name = get_field('billing_name', $post_id);
        $billing_email = get_field('billing_email', $post_id);
        $billing_company = get_field('billing_company', $post_id);
        $payments_plan_amount = get_field('payments_plan_amount', $post_id);
        $payments_amount = get_field('payments_amount', $post_id);
        $payments_date = get_field('payments_date', $post_id);
        $subscriptions_sub_begin_date = get_field('subscriptions_sub_begin_date', $post_id);
        $subscriptions_sub_end_date = get_field('subscriptions_sub_end_date', $post_id);
        $payments_status = get_field('payments_status', $post_id);
    
        // Formatea los valores para asegurarse de que siempre tengan dos decimales
        $payments_plan_amount = number_format((float)$payments_plan_amount, 2, '.', '');
        $payments_amount = number_format((float)$payments_amount, 2, '.', '');
    
        $html = '';
    
        ob_start();
        if (file_exists(PRODUSUBSCRIPTION__PLUGIN_DIR . 'templates/invoice-template/invoice-template.php')) {
            require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'templates/invoice-template/invoice-template.php';
        }
        $html = ob_get_clean();
    
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
    
        $dompdf->loadHtml($html);
    
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        $upload_dir = wp_upload_dir();
    
        $current_date = date('Y-m-d');
        $current_month = date('Y-m');
    
        $invoice_dir = $upload_dir['basedir'] . "/facturas/{$current_month}";
    
        if (!file_exists($invoice_dir)) {
            wp_mkdir_p($invoice_dir);
        }
    
        $pdf_filename = "factura_{$post_id}_{$current_date}.pdf";
        $pdf_path = $invoice_dir . "/" . $pdf_filename;
    
        file_put_contents($pdf_path, $dompdf->output());
    
        $pdf_url = $upload_dir['baseurl'] . "/facturas/{$current_month}/" . $pdf_filename;
    
        wp_send_json_success(['pdf_url' => $pdf_url]);
    }

    public static function set_subscription_actions($actions, $post) {
        if ( $post->post_type == "produ-subscription" ) {
            unset($actions['inline hide-if-no-js']);
            unset($actions['trash']);
            unset($actions['view']);

            $url = admin_url('post.php?post='.$post->ID);
            $rel_link = add_query_arg(array('post_type' => 'produ-subscription', 'action' => 'edit'), $url);
            $actions['edit'] = "<a href='".$rel_link."'>".__('Edit')."</a>";
        }

        return $actions;
    }

    public function manage_edit_subs_columns($columns) {
        $columns = array(
            'cb'                                    => ('cb'),
            'postID'                                => __('ID'),
            'title'                                 => __('Propietario'),
            'email'                                 => __('Email'),
            'taxonomy-subscription-type'            => 'Tipo de Plan',
            'taxonomy-subscription-plan'            => 'Plan',
            'taxonomy-subscription-payment-method'  => 'Forma de pago',
            'status'                                => 'Estado',
            'period'                                => 'Período',
            // 'date'                                  => __('Date')
        );
        return $columns;
    }

    public function manage_edit_subs_column($column, $post_id) {
        $today = new DateTime(current_time('mysql'));
        $begin = get_field('subscriptions_sub_begin_date', $post_id);
        $end = get_field('subscriptions_sub_end_date', $post_id);
        if ($end) {
            $end_date = DateTime::createFromFormat('d/m/Y', $end)->format('Y-m-d');
            $today->modify('+30 days');
        }
        switch ($column) {
            case 'postID':
                printf('<b>%s</b>', $post_id);
                break;
            case 'email':
                $owner = get_field('subscriptions_sub_owner', $post_id);
                $user = get_userdata($owner);
                printf('%s', $user->data->user_email);
                break;
            case 'status':
                $status = get_field('subscriptions_sub_status', $post_id);
                switch ($status) {
                    case 'activa':
                        $status = '<span title="Activa" class="dashicons dashicons-yes icons-status active"></span>';
                        if (isset($end_date) && $today == $end_date) {
                            $status = '<span title="Activa, por vencer" class="dashicons dashicons-update icons-status update"></span>';
                        }
                        break;
                    case 'inactiva':
                        $status = '<span title="Inactiva" class="dashicons dashicons-no icons-status inactive"></span>';
                        break;
                    case 'vencida':
                        $status = '<span title="Vencida" class="dashicons dashicons-clock icons-status defeated"></span>';
                        break;
                }
                printf('%s', $status);
                break;
            case 'period':
                printf('%s - %s', $begin, $end);
                break;
        }
    }

    public function add_args_tax_plan( $args, $field, $post_id ) {
     
        $contacto = $_POST['seleccion'];

        // Ajusta la consulta según el valor de tu campo personalizado
        $args['meta_query'] = array(
            array(
                'key'     => 'plans_plan_type',
                'value'   => $contacto,
                'compare' => '=',
            ),
        );

        return $args;
        //  print_r( $args); exit();
	}


    public function get_data_suscriptor() {
        $post_id = $_POST['idsuscriptor'];

        $user = get_userdata($post_id);
        $datos=get_fields('user_'.$post_id);
        $user_data['nickname'] = '';
        $user_data['name'] = '';
        $user_data['address'] = '';
        $user_data['email'] = '';

        if (isset($user->user_login)) {
            $user_data['nickname'] = $user->user_login;
        }
        
        if (isset($user->display_name)) {
            $user_data['name']=$user->display_name;
        }
        
        if (isset($datos['address'])) {
            $user_data['address']=$datos['address'];
        }

        if (isset($user->user_email)) {
            $user_data['email']=$user->user_email;
        }
        
        
  
        echo json_encode(array('respuesta'=>$user_data));
        wp_die(); // Esto es requerido para terminar inmediatamente y retornar una respuesta adecuada
    
    }


    public function get_data_plan() {
        
        $post_id = $_POST['idplan'];
        $datos=get_term($post_id,'subscription-plan');
        $term_fields= get_fields('term_'.$post_id);
        
        $plan_data['name'] = '';
        $plan_data['duration'] = '';
        $plan_data['price'] = '';
        $plan_data['num_users'] = '';

        // Obtener la fecha y hora actual en formato MySQL
        $fecha_actual = current_time('mysql');

        // Crear un objeto DateTime a partir de la fecha actual
        $fecha = new DateTime($fecha_actual);

        

        

        
        // Formatear la fecha al formato MySQL
        //$fecha_end= $fecha->format('Y-m-d H:i:s');
        

        if (isset($datos->name)) {
            $plan_data['name'] = $datos->name;
        }
        
        if (isset($term_fields['plans_plan_duration'])) {
            $plan_data['duration']=$term_fields['plans_plan_duration'];


            if(strtoupper($term_fields['plans_plan_duration']) == 'MENSUAL') {
                // Sumar 30 días a la fecha
                $fecha->modify('+30 days');
            }elseif(strtoupper($term_fields['plans_plan_duration']) == 'ANUAL') {
                $fecha->modify('+365 days');
            }elseif(strtoupper($term_fields['plans_plan_duration']) == 'TRIMESTRAL') {
                $fecha->modify('+90 days');
            }
        }
        
        if (isset($term_fields['plans_plan_price'])) {
            $plan_data['price']=$term_fields['plans_plan_price'];
        }

        if (isset($term_fields['plans_plan_num_users'])) {
            $plan_data['num_users']=$term_fields['plans_plan_num_users'];
        }
        $fecha_end= $fecha->format('Y-m-d H:i:s');
        
        $plan_data['fecha_end']=$fecha_end;

        echo json_encode(array('respuesta'=>$plan_data));
        wp_die(); // Esto es requerido para terminar inmediatamente y retornar una respuesta adecuada
    
    }

    public function register_rest_routes() {
        register_rest_route('produ/v1', '/subscriptions-stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_subscriptions_stats'),
            'permission_callback' => '__return_true'
        ));
    }
    
    public function get_subscriptions_stats() {
        $args = array(
            'orderby' => 'registered',
            'order'   => 'ASC',
            'fields'  => array('user_registered'),
        );
    
        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
    
        if (empty($users)) {
            return new WP_REST_Response(array('message' => 'No users found'), 404);
        }
    
        $date_counts = array();
    
        foreach ($users as $user) {
            $date = date('Y-m-d', strtotime($user->user_registered)); 
    
            if (isset($date_counts[$date])) {
                $date_counts[$date]++;
            } else {
                $date_counts[$date] = 1;
            }
        }
    
        $data = array();
        foreach ($date_counts as $date => $count) {
            $data[] = array(
                'date'  => $date,
                'count' => $count
            );
        }
    
        return new WP_REST_Response($data, 200);
    }
}