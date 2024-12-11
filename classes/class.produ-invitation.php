<?php

class PRODUSubscriptionInvitation {
    private static $initiated = FALSE;
    private static $invitationTableName;

    public function __construct() {
        global $wpdb;

        add_action( 'init', array( &$this, 'init' ) );
        add_shortcode ( 'accept_invitation', array( &$this, 'handle_invitation_acceptance' ) );
        self::$invitationTableName = $wpdb->prefix.'subscription_invitations';
    }

    public static function init() {
        if ( !self::$initiated ) {
            self::init_class();
        }
    }

    /**
     * Initializes class
     */
    private static function init_class() {
        self::$initiated = TRUE;

        $tableCreated = get_option( 'subscription_invitations_table_created', '0' );
        if ( $tableCreated === '0' ) {
            self::create_subscription_invitations_table();
            update_option( 'subscription_invitations_table_created', '1' );
        }
    }

    /**
     * Flushing of the WordPress rewrite rules
     * @static
     */
    private static function flush_rewrite() {
        if ( !function_exists( 'flush_rewrite_rules' ) ) {
            return FALSE;
        }
        flush_rewrite_rules( FALSE );
    }

    /**
     * Subscription invitations table
     * @static
     */
    public static function create_subscription_invitations_table() {
        global $wpdb;

        $table = self::$invitationTableName;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `subscription_id` int(10) UNSIGNED NOT NULL,
            `plan_id` int(10) UNSIGNED NOT NULL,
            `user_id` int(10) UNSIGNED NOT NULL,
            `email` varchar(260) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
            `token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
            `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL 'pending',
            `created_at` datetime NOT NULL,
            `updated_at` datetime  NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
            PRIMARY KEY (id)
        ) ENGINE=InnoDB $charsetCollate;";

        $result = $wpdb->query( $sql );

        if ( $result === FALSE ) {
            add_flash_notice(
                __( "<b>$table falló al crearse, deberá crearla manualmente.</b>" ),
                "warning",
                TRUE
            );
        } else {
            add_flash_notice(
                __( "<b>$table creada con éxito.</b>" ),
                "success",
                TRUE
            );
        }
    }

    public static function add_invitation( $subscriptionId, $email ) {
        global $wpdb;

        $table = self::$invitationTableName;
        $today = current_time( 'mysql' );
        $email = trim( $email );
        $planId = get_field( 'subscriptions_sub_plan', $subscriptionId );
        $userId = get_field( 'subscriptions_sub_owner', $subscriptionId );

        $token = sanitize_text_field( wp_generate_password( 80, FALSE ) );
        $wpdb->insert(
            $table,
            [
                'subscription_id'   => $subscriptionId,
                'plan_id'           => $planId,
                'user_id'           => $userId,
                'email'             => $email,
                'token'             => $token,
                'status'            => 'pending',
                'created_at'        => $today,
                'updated_at'        => $today
            ]
        );

        #Correo para beneficiario
        $envUrl = sanitize_title( PRODUSUBSCRIPTION_ENV_NAME );
        $invitationUrl = add_query_arg( array(
            'email' => $email,
            'token' => $token,
        ), home_url( "/$envUrl/invitacion/" ) );

        $message = "Haz clic en el siguiente enlace para aceptar la invitación: \n\n".$invitationUrl;
        mail( $email, 'Invitación', $message );

        #ACA enviar correo a propietario

        return $token;
    }

    public static function send_invitation_by_email( $invitation ) {
        global $wpdb;

        #Correo para beneficiario
        $envUrl = sanitize_title( PRODUSUBSCRIPTION_ENV_NAME );
        $invitationUrl = add_query_arg( array(
            'email' => $invitation->email,
            'token' => $invitation->token,
        ), home_url( "/$envUrl/invitacion/" ) );

        $message = "Haz clic en el siguiente enlace para aceptar la invitación: \n\n".$invitationUrl;
        $send = mail( $invitation->email, 'Invitación', $message );
        return $send;
    }

    public static function cancel_invitation( $token ) {
        global $wpdb;

        $wpdb->delete( self::$invitationTableName, [ 'token' => $token ] );
    }

    public static function cancel_invitation_by_email( $email, $subscriptionId ) {
        global $wpdb;

        $wpdb->delete( self::$invitationTableName, [ 'email' => $email, 'subscription_id' => $subscriptionId ] );
    }

    public static function accept_invitation( $token, $userId, $email ) {
        global $wpdb;

        $table = self::$invitationTableName;
        $invitation = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE token = %s AND email = %s AND status = 'pending'", $token, $email ) );
        $userId = get_current_user_id();

        if ( $invitation ) {
            $wpdb->update(
                $table,
                [ 'status'  => 'accepted' ],
                [ 'id'      => $invitation->id ]
            );

            update_user_meta( $userId, '_wp_user_subscription_related_plan_id', $invitation->subscription_id );
            update_user_meta( $userId, '_wp_user_subscription_related_subscription_id', $invitation->subscription_id );

            #Se actualizan los beneficiarios en suscripción
            $members = get_field( 'subscriptions_sub_beneficiaries', $invitation->subscription_id );
            if ( ! is_array( $members ) || count( $members ) <= 0 ) {
                $members = [];
            }

            $members[] = [ 'subscriptions_sub_user' => $userId, 'subscriptions_sub_check' => TRUE ];
            update_field( 'subscriptions_sub_beneficiaries', $members, $invitation->subscription_id );

            #Seteamos preferencias en DB y suscribimos a Mailchimp
            $userSubscription = SubscriptionHelper::getSubscriptionByUserMeta( $userId );
            if ( $userSubscription !== NULL ) {
                $response = SubscriptionHelper::superSetPreferences( $invitation->subscription_id, $userSubscription->ID, $userId );
            } else {
                $response = SubscriptionHelper::superSetPreferences( $invitation->subscription_id, $invitation->subscription_id, $userId, TRUE );
            }

            return 'Invitation accepted successfully.';
        } else {
            #ACA validar error, token no válido o no existe registro
            return 'Error';
        }
    }

    public static function handle_invitation_acceptance() {
        global $wp;

        if ( isset($_GET['token']) && isset($_GET['email']) ) {
            $email = trim( $_GET['email'] );
            $token = sanitize_text_field( $_GET['token'] );
            $userId = get_current_user_id();

            if ( $userId ) {
                $currentUser = wp_get_current_user();
                $userEmail = $currentUser->user_email;
                if ( $userEmail === $_GET['email'] ) {
                    $status = self::accept_invitation( $token, $userId, $email );
                    echo $status;
                } else {
                    echo 'Su correo no corresponde.';
                }
            } else {
                $envUrl = sanitize_title( PRODUSUBSCRIPTION_ENV_NAME );
                $currentUrl = home_url( add_query_arg( NULL, NULL ) );
                $registrationUrl = home_url( '/'.$envUrl.'/login/' );
                $redirectUrl = add_query_arg( 'redirect_to', urlencode( $currentUrl ), $registrationUrl );
                echo 'You must log in to accept the invitation. <a href="'.$redirectUrl.'">Login</a>';
            }
        } else {
            echo 'Invalid token.';
        }
    }

    public static function get_invitations_by_susbcription( $subscriptionId ) {
        global $wpdb;

        $table = self::$invitationTableName;
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE subscription_id = %d ORDER BY id ASC;", $subscriptionId ) );
        return $rows;
    }

    public static function get_pending_invitations_by_susbcription( $subscriptionId ) {
        global $wpdb;

        $table = self::$invitationTableName;
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE subscription_id = %d AND status = %s ORDER BY id ASC;", $subscriptionId, 'pending' ) );
        return $rows;
    }

    public static function get_invitation_by_email( $subscriptionId, $email ) {
        global $wpdb;

        $table = self::$invitationTableName;
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE subscription_id = %d AND email = %s LIMIT 1;", $subscriptionId, $email ) );
        return $row;
    }

    public static function get_pending_invitation_by_email( $subscriptionId, $email ) {
        global $wpdb;

        $table = self::$invitationTableName;
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE subscription_id = %d AND email = %s AND status = %s LIMIT 1;", $subscriptionId, $email, 'pending' ) );
        return $row;
    }

    public static function get_invitation_by_token( $token ) {
        global $wpdb;

        $table = self::$invitationTableName;
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE token = %s LIMIT 1;", $token ) );
        return $row;
    }
}