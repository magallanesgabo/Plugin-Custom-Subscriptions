<?php
require_once PRODUSUBSCRIPTION__PLUGIN_DIR.'classes/class.produ-invitation.php';
require_once PRODUSUBSCRIPTION__PLUGIN_DIR.'inc/login-functions.php';

class PRODUSubscriber {
    private static $initiated = FALSE;
    private static $invitationInstance;

    public function __construct() {
        add_action( 'init', array( &$this, 'init' ) );
        add_action( 'wp_enqueue_scripts', array( &$this, 'add_membership_assets' ) );
        add_action( 'wp_ajax_send_invitation', array( &$this, 'send_invitation' ) );
        add_action( 'wp_ajax_resend_invitation', array( &$this, 'resend_invitation' ) );
        add_action( 'wp_ajax_cancel_invitation', array( &$this, 'cancel_invitation' ) );
        add_action( 'wp_ajax_remove_member', array( &$this, 'remove_member' ) );
        add_action( 'wp_ajax_validate_plan', array( &$this, 'validate_plan' ) );
        add_action( 'wp_ajax_set_newsletter_preferences', array( &$this, 'set_newsletter_preferences' ) );
        add_action( 'wp_ajax_set_personal_data', array( &$this, 'set_personal_data' ) );
        add_action( 'wp_ajax_set_login_password', array( &$this, 'set_login_password' ) );
        add_action( 'wp_ajax_subscription_user_registration', array( &$this, 'subscription_user_registration_ajax') );
        add_action( 'wp_ajax_nopriv_subscription_user_registration', array( &$this, 'subscription_user_registration_ajax') );

        self::$invitationInstance = new PRODUSubscriptionInvitation( );
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
    }

    public function add_membership_assets() {
        wp_enqueue_style( 'produ-subscriber', esc_url( PRODUSUBSCRIPTION__PLUGIN_URL.'assets/css/frontend/subscriber.css' ), [], PRODUSUBSCRIPTION_VERSION );
        wp_enqueue_style( 'produ-subscriber-sweetalert2', esc_url( PRODUSUBSCRIPTION__PLUGIN_URL.'assets/css/sweetalert2.min.css' ), [], PRODUSUBSCRIPTION_VERSION, 'all' );
        wp_enqueue_style( 'produ-subscriber-ladda', esc_url( PRODUSUBSCRIPTION__PLUGIN_URL.'assets/css/ladda-themeless.min.css' ), [], PRODUSUBSCRIPTION_VERSION, 'all' );

        wp_enqueue_script( 'bootstrap', esc_url( get_template_directory_uri().'/assets/js/bootstrap.min.js' ), ['jquery'], PRODUSUBSCRIPTION_VERSION, TRUE );
        wp_enqueue_script( 'popper', esc_url( get_template_directory_uri().'/assets/js/popper.min.js' ), ['jquery'], PRODUSUBSCRIPTION_VERSION, TRUE );
        wp_enqueue_script( 'slick', esc_url( get_template_directory_uri().'/assets/js/slick.min.js' ), ['jquery'], PRODUSUBSCRIPTION_VERSION, TRUE );
        wp_enqueue_script( 'wow', esc_url( get_template_directory_uri().'/assets/js/wow.js' ), ['jquery'], PRODUSUBSCRIPTION_VERSION, TRUE );
        wp_enqueue_script( 'produ-subscriber', esc_url( PRODUSUBSCRIPTION__PLUGIN_URL.'assets/js/frontend/subscriber.js' ), ['jquery'], PRODUSUBSCRIPTION_VERSION, TRUE );
        wp_enqueue_script( 'produ-subscriber-sweetalert2', esc_url( PRODUSUBSCRIPTION__PLUGIN_URL.'assets/js/sweetalert2.all.min.js.js' ), ['jquery', 'acf-input'], PRODUSUBSCRIPTION_VERSION, TRUE );
        wp_enqueue_script( 'produ-subscriber-ladda-spin', esc_url( PRODUSUBSCRIPTION__PLUGIN_URL.'assets/js/spin.min.js' ), ['jquery', 'acf-input'], PRODUSUBSCRIPTION_VERSION, TRUE );
        wp_enqueue_script( 'produ-subscriber-ladda', esc_url( PRODUSUBSCRIPTION__PLUGIN_URL.'assets/js/ladda.min.js' ), ['jquery', 'acf-input'], PRODUSUBSCRIPTION_VERSION, TRUE );

        $varToJS = [
            'nonce'     => wp_create_nonce( 'acf_nonce' ),
            'ajaxurl'   => admin_url( 'admin-ajax.php' ),
            'error'     => 'Error inesperado, recargue e intente nuevamente.'
        ];
        wp_localize_script( 'produ-subscriber', 'subscriberVars', $varToJS );
    }

    public function send_invitation() {
        if ( isset(  $_POST['nonce'] ) && !wp_verify_nonce( $_POST['nonce'], 'acf_nonce' ) ) {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, recargue e intente nuevamente.',
            ]);
            wp_die();
        }

        $email = sanitize_email( $_POST['email'] );

        #Suscripción activa
        $loggedUser = get_current_user_id();
        $subscriptionId = '';
        $subscription = SubscriptionHelper::getSubscriptionByUserMeta( $loggedUser );
        if ( $subscription ) {
            $subscriptionId = $subscription->ID;
        }

        if ( $email && $subscriptionId ) {
            $row = self::$invitationInstance::get_pending_invitation_by_email( $subscriptionId, $email );
            if ( !$row ) {
                $token = self::$invitationInstance::add_invitation( $subscriptionId, $email );
                echo json_encode([
                    'status'    => 'success',
                    'message'   => 'Invitación enviada.',
                    'token'     => $token
                ]);
            } else {
                echo json_encode([
                    'status'    => 'error',
                    'message'   => 'Ya existe una invitación con ese correo.',
                    'row'       => $exists
                ]);
            }
        } else {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, no se pudo continuar con el proceso.',
            ]);
        }
        wp_die();
    }

    public function resend_invitation() {
        if ( isset(  $_POST['nonce'] ) && !wp_verify_nonce( $_POST['nonce'], 'acf_nonce' ) ) {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, recargue e intente nuevamente.',
            ]);
            wp_die();
        }

        $token = sanitize_text_field( $_POST['token'] );

        if ( $token ) {
            $exists = self::$invitationInstance::get_invitation_by_token( $token );
            if ( $exists ) {
                #Reenviar email a beneficiario
                $send = self::$invitationInstance::send_invitation_by_email( $exists );
                if ( $send ) {
                    echo json_encode([
                        'status'    => 'success',
                        'message'   => 'Invitación enviada.',
                    ]);
                } else {
                    echo json_encode([
                        'status'    => 'error',
                        'message'   => 'No se pudo reenviar invitación.',
                    ]);
                }
            } else {
                echo json_encode([
                    'status'    => 'error',
                    'message'   => 'No se pudo reenviar invitación.',
                ]);
            }
        } else {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, no se pudo continuar con el proceso.',
            ]);
        }
        wp_die();
    }

    public function cancel_invitation() {
        if ( isset(  $_POST['nonce'] ) && !wp_verify_nonce( $_POST['nonce'], 'acf_nonce' ) ) {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, recargue e intente nuevamente.',
            ]);
            wp_die();
        }

        $token = sanitize_text_field( $_POST['token'] );

        if ( $token ) {
            self::$invitationInstance::cancel_invitation( $token );
            echo json_encode([
                'status'    => 'success',
                'message'   => 'Invitación cancelada.',
            ]);
        } else {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, no se pudo continuar con el proceso.',
            ]);
        }
        wp_die();
    }

    public function remove_member() {
        if ( isset(  $_POST['nonce'] ) && !wp_verify_nonce( $_POST['nonce'], 'acf_nonce' ) ) {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, recargue e intente nuevamente.',
            ]);
            wp_die();
        }

        $deletedMemberId = (int) sanitize_text_field( $_POST['member_id'] );

        #Suscripción activa
        $loggedUser = get_current_user_id();
        $subscriptionId = '';
        $subscription = SubscriptionHelper::getSubscriptionByUserMeta( $loggedUser );
        if ( $subscription ) {
            $subscriptionId = $subscription->ID;
        }

        $user = get_user_by( 'id', $deletedMemberId );

        if ( $user &&  $subscriptionId ) {
            #Eliminamos invitación en tabla
            self::$invitationInstance::cancel_invitation_by_email( $user->user_email, $subscriptionId );

            #Se actualizan los beneficiarios en suscripción
            $upadatedMembers = [];
            $members = get_field( 'subscriptions_sub_beneficiaries', $subscriptionId );
            if ( is_countable( $members ) && count( $members ) > 0 ) {
                foreach ( $members as $member ) {
                    if ( (int) $member['subscriptions_sub_user'] !== $deletedMemberId ) {
                        $upadatedMembers[] = $member;
                    }
                }
            }

            #Actualizamos miembros en suscripción
            update_field( 'subscriptions_sub_beneficiaries', $upadatedMembers, $subscriptionId );

            #Se eliminan los metas asociación de cuenta de usuario.
            update_user_meta( $deletedMemberId, '_wp_user_subscription_related_plan_id', '' );
            update_user_meta( $deletedMemberId, '_wp_user_subscription_related_subscription_id', '' );

            #Preferencia de su plan suscrito
            $userSubscription = SubscriptionHelper::getSubscriptionByUserMeta( $deletedMemberId );

            #Eliminamos las preferencias anteriores, en caso de recuperarse el status beneficiario no cargar las previas configuraciones
            $deletePreferences = SubscriptionHelper::deletePreferencesFromDb( $subscriptionId, $deletedMemberId );

            #Registramos preferencias nuevas en db y Mailchimp
            if ( $userSubscription !== NULL ) {
                $result = SubscriptionHelper::superSetPreferences( $userSubscription->ID, $subscriptionId, $deletedMemberId );
            } else {
                $result = SubscriptionHelper::superSetPreferences( $subscriptionId, $subscriptionId, $deletedMemberId, TRUE );
            }

            echo json_encode([
                'status'    => 'success',
                'message'   => 'Miembro eliminado.',
                'result'    => $result
            ]);
        } else {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, no se pudo continuar con el proceso.',
            ]);
        }
        wp_die();
    }

    public function validate_plan() {
        if ( isset(  $_POST['nonce'] ) && !wp_verify_nonce( $_POST['nonce'], 'acf_nonce' ) ) {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, recargue e intente nuevamente.',
            ]);
            wp_die();
        }

        #Suscripción activa
        $loggedUser = get_current_user_id();
        $subscriptionId = '';
        $subscription = SubscriptionHelper::getSubscriptionByUserMeta( $loggedUser );
        if ( $subscription ) {
            $subscriptionId = $subscription->ID;
        }

        $planId = sanitize_text_field( $_POST['plan_id'] );

        if ( $subscriptionId && $planId ) {
            $currentPlan = SubscriptionHelper::getPlanBySubscriptionId( $subscriptionId );
            $qtyUsersCurrentPlan = get_field( 'plans_plan_num_users', "term_$currentPlan->term_id" );
            $qtyUsersNewPlan = get_field( 'plans_plan_num_users', "term_$planId" );
            echo json_encode([
                'status'    => 'success',
                'message'   => 'Mensaje success.',
                'qtyCP'     => $qtyUsersCurrentPlan,
                'qtyNP'     => $qtyUsersNewPlan
            ]);
        } else {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, no se pudo continuar con el proceso.',
            ]);
        }
        wp_die();
    }

    public function set_newsletter_preferences() {
        if ( isset(  $_POST['nonce'] ) && !wp_verify_nonce( $_POST['nonce'], 'acf_nonce' ) ) {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, recargue e intente nuevamente.',
            ]);
            wp_die();
        }

        $userId = get_current_user_id();
        $subscription = SubscriptionHelper::getRelatedOrOwnSubscription( $userId );
        $subscriptionId = $subscription->ID;
        $postPreferences = $_POST['preferences'];

        if ( $subscriptionId && $postPreferences ) {
            foreach ( $postPreferences as &$postPreference ) {
                $postPreference['newsletter_wpid'] = (int) $postPreference['newsletter_wpid'];
                $postPreference['lists'] = SubscriptionHelper::getMailchimpListId( $postPreference['newsletter_wpid'] );
                $postPreference['status'] = '';
            }

            #Procesar $postPreferences contra mailchimp
            $messages = SubscriptionHelper::saveMailchimpPreferences( $postPreferences, $userId );

            #Registrar las nuevas preferencias de beneficiario
            $update = SubscriptionHelper::setPreferences( $subscriptionId, $postPreferences, $userId );

            if ( $update !== FALSE ) {
                echo json_encode([
                    'status'    => 'success',
                    'message'   => 'Se registraron las preferencias.',
                    'mailchimp' => $messages,
                ]);
            } else {
                echo json_encode([
                    'status'    => 'error',
                    'message'   => 'Ocurrió un error, no se pudieron registrar las preferencias.',
                ]);
            }
        } else {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'No se pudo obtener datos de suscripción.',
            ]);
        }
        wp_die();
    }

    public function set_personal_data() {
        $MailChimp = new \DrewM\MailChimp\MailChimp( MAILCHIMP_API_KEY );

        if ( isset(  $_POST['nonce'] ) && !wp_verify_nonce( $_POST['nonce'], 'acf_nonce' ) ) {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, recargue e intente nuevamente.',
            ]);
            wp_die();
        }

        $current_user = wp_get_current_user();
        $firstName = sanitize_text_field( $_POST['subscriptor_first_name'] );
        $lastName = sanitize_text_field( $_POST['subscriptor_last_name'] );
        $email = sanitize_email( $_POST['subscriptor_email'] );

        $message = 'Datos personales actualizados.';
        $flagName = FALSE;
        $flagEmail = FALSE;
        $error = FALSE;

        $userId = get_current_user_id();
        $subscription = SubscriptionHelper::getRelatedOrOwnSubscription( $current_user->ID );
        $planId = get_field( 'subscriptions_sub_plan', $subscription->ID );
        $mailchimpLists = SubscriptionHelper::getUniqueMailchimpListIdsByPlan( $planId );

        if ( $current_user ) {
            if ( $firstName !== $current_user->first_name ) {
                $flagName = TRUE;
            }

            if ( $lastName !== $current_user->last_name ) {
                $flagName = TRUE;
            }

            if ( $email !== $current_user->user_email ) {
                $flagEmail = TRUE;
            }

            if ( $flagName || $flagEmail ) {
                $updatedUser = array(
                    'ID'            => $current_user->ID,
                    'first_name'    => $firstName,
                    'last_name'     => $lastName,
                    'display_name'  => "$firstName $lastName",
                    'user_email'    => $email,
                );
                $result = wp_update_user( $updatedUser );

                if ( is_wp_error( $result ) ) {
                    $message = 'Falló al actualizar los datos.';
                    $error = TRUE;
                }

                # Actualiza el miembro en la lista
                if ( !$error && is_countable( $mailchimpLists ) && count( $mailchimpLists ) > 0 ) {
                    $subscriberHash = $MailChimp->subscriberHash( $current_user->user_email );
                    foreach ( $mailchimpLists as $listId ) {
                        $result = $MailChimp->patch("lists/$listId/members/$subscriberHash", [
                            'email_address' => $email,
                            'merge_fields'  => [
                                'FNAME' => $firstName,
                                'LNAME' => $lastName
                            ]
                        ]);
                    }
                }
            }

            echo json_encode([
                'status'    => $error ? 'error' : 'success',
                'message'   => $message,
                'aca'=>$result
            ]);
        } else {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, recargue e intente nuevamente.',
            ]);
        }
        wp_die();
    }

    public function set_login_password() {
        if ( isset(  $_POST['nonce'] ) && !wp_verify_nonce( $_POST['nonce'], 'acf_nonce' ) ) {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, recargue e intente nuevamente.',
            ]);
            wp_die();
        }

        $current_user = wp_get_current_user();
        $password = esc_attr( $_POST['password'] );

        if ( $current_user && $password ) {
            #Si viene contraseña, se actualiza
            wp_set_password( $password, $current_user->ID );

            echo json_encode([
                'status'    => 'success',
                'message'   => 'Se actualizó la contraseña',
            ]);
        } else {
            echo json_encode([
                'status'    => 'error',
                'message'   => 'Error inesperado, recargue e intente nuevamente.',
            ]);
        }
        wp_die();
    }

    // public function update_subscriber_email( $currentEmail, $newEmail ) {
    //     if ( isset(  $_POST['nonce'] ) && !wp_verify_nonce( $_POST['nonce'], 'acf_nonce' ) ) {
    //         echo json_encode([
    //             'status'    => 'error',
    //             'message'   => 'Error inesperado, recargue e intente nuevamente.',
    //         ]);
    //         wp_die();
    //     }

    //     #Suscripción activa
    //     $loggedUser = get_current_user_id();
    //     $subscriptionId = '';
    //     $subscription = SubscriptionHelper::getSubscriptionByUserMeta( $loggedUser );
    //     if ( $subscription ) {
    //         $subscriptionId = $subscription->ID;
    //     }

    //     echo json_encode([
    //         'status'    => 'success',
    //         'message'   => 'Email cambiado.',
    //     ]);
    //     wp_die();
    // }

    function subscription_user_registration_ajax() {
        // Verificar el nonce para seguridad
        check_ajax_referer('custom_user_registration_nonce', 'security');
    
        // Sanitizar y validar los datos del formulario
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $plan_id = intval($_POST['plan_id']); // Asegurarse de que el plan_id sea un entero
    
        // Verificar si el nombre de usuario ya existe
        if (username_exists($username)) {
            echo '<p style="color: red;">Error: El nombre de usuario ya está registrado.</p>';
            wp_die(); // Detener la ejecución
        }
    
        // Verificar si el correo electrónico ya existe
        if (email_exists($email)) {
            echo '<p style="color: red;">Error: El correo electrónico ya está registrado.</p>';
            wp_die();
        }
    
        // Generar una contraseña segura automáticamente
        $password = wp_generate_password();
    
        // Crear el usuario con el rol de "Suscriptor"
        $user_id = wp_create_user($username, $password, $email);
        if (!is_wp_error($user_id)) {
            // Asignar el rol de "Suscriptor" al usuario recién creado
            $user = new WP_User($user_id);
            $user->set_role('subscriber');
    
            // Obtener los custom fields del plan seleccionado
            $plan_type = get_field('plans_plan_type', 'term_' . $plan_id);
            $plan_num_users = get_field('plans_plan_num_users', 'term_' . $plan_id);
            $plan_duration = get_field('plans_plan_duration', 'term_' . $plan_id);
            $plan_price = get_field('plans_plan_price', 'term_' . $plan_id);
    
            // Crear una nueva entrada en el CPT 'produ-subscription'
            $subscription_id = wp_insert_post(array(
                'post_title'  => 'Suscripción de ' . $username,
                'post_type'   => 'produ-subscription',
                'post_status' => 'publish',
                'post_author' => $user_id
            ));
    
            if (!is_wp_error($subscription_id)) {
                // Obtener la fecha actual
                $start_date = current_time('Y-m-d');
                $end_date = date('Y-m-d', strtotime("+1 month"));
    
                // Actualizar los custom fields en el CPT
                update_field('subscriptions_sub_type', $plan_type, $subscription_id);
                update_field('subscriptions_sub_user', $user_id, $subscription_id);
                update_field('subscriptions_sub_owner', $user_id, $subscription_id);
                update_field('subscriptions_sub_status', 'inactiva', $subscription_id);
                update_field('subscriptions_sub_begin_date', $start_date, $subscription_id);
                update_field('subscriptions_sub_end_date', $end_date, $subscription_id);
                update_field('plans_plan_duration', $plan_duration, $subscription_id);
                update_field('payments_plan_amount', $plan_price, $subscription_id);
                update_field('payments_amount', $plan_price, $subscription_id);
                update_field('payments_date', $start_date, $subscription_id);
                update_field('plans_plan_num_users', $plan_num_users, $subscription_id);
    
                // Enviar un correo electrónico de bienvenida al usuario
                $subject = 'Bienvenido a nuestro sitio web';
                $message = "Hola $username,\n\nGracias por registrarte en nuestro sitio. Aquí están tus detalles de inicio de sesión:\n\nNombre de usuario: $username\nContraseña: $password\n\nPuedes iniciar sesión en: " . wp_login_url();
                wp_mail($email, $subject, $message);
    
                echo '<p style="color: green;">Usuario registrado exitosamente. Por favor, revisa tu correo electrónico para la contraseña.</p>';
            } else {
                echo '<p style="color: red;">Error: No se pudo crear la suscripción.</p>';
            }
        } else {
            echo '<p style="color: red;">Error: No se pudo crear el usuario.</p>';
        }
    
        wp_die(); // Finalizar la solicitud AJAX
    }
}