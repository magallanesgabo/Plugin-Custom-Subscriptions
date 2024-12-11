<?php
/**
 * Class PRODUSubscriptionEmailsNotifications
 * 
 * This class handles email notifications for the PRODU Subscription plugin.
 * It manages email content through an options page and provides methods to trigger notifications
 * for subscription-related events such as new subscriptions, invoice generation, and subscription expiration.
 */
class PRODUSubscriptionEmailsNotifications {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_email_settings_submenu'));
    }

    /**
     * Adds the email settings submenu under the 'produ-subscription' custom post type menu.
     */
    public function add_email_settings_submenu() {
        add_submenu_page(
            'subscription-dashboard',
            'Subscription Emails',
            'Subscription Emails',
            'manage_options',
            'produ-subscription-emails',
            array($this, 'render_email_settings_page')
        );
    }

    /**
     * Renders the email settings page and handles saving and sending emails
     */
    public function render_email_settings_page() {
        if (isset($_POST['submit'])) {
            if (isset($_POST['produ_subscription_email_nonce']) && wp_verify_nonce($_POST['produ_subscription_email_nonce'], 'produ_subscription_email_settings')) {
                update_option('produ_subscription_email_welcome', wp_kses_post($_POST['produ_subscription_email_welcome']));
                update_option('produ_subscription_email_restore_password', wp_kses_post($_POST['produ_subscription_email_restore_password']));
                update_option('produ_subscription_email_content_3', wp_kses_post($_POST['produ_subscription_email_content_3']));
            }
        }

        if (isset($_POST['send_email'])) {
            if (isset($_POST['produ_subscription_email_nonce']) && wp_verify_nonce($_POST['produ_subscription_email_nonce'], 'produ_subscription_email_settings')) {
                $email_to = sanitize_email($_POST['email_to']);
                $email_content = wp_kses_post($_POST['email_content']);

                $user_info = get_user_by('email', $email_to);

                if ($user_info) {
                    $email_content = str_replace('[user_name]', $user_info->display_name, $email_content);
                    $email_content = str_replace('[date]', date('Y-m-d'), $email_content);
                    $email_content = str_replace('[time]', date('H:i:s'), $email_content);
                    $email_content = str_replace('[user_email]', $user_info->user_email, $email_content);
                    $email_content = str_replace('[user_subscription]', 'Basic Plan', $email_content);

                    wp_mail($email_to, 'Notificación de Suscripción', $email_content);
                    echo '<div class="notice notice-success"><p>Correo enviado exitosamente a ' . esc_html($email_to) . '.</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>No se encontró un usuario con ese correo electrónico.</p></div>';
                }
            }
        }

        $content_1 = get_option('produ_subscription_email_welcome', '');
        $content_2 = get_option('produ_subscription_email_restore_password', '');
        $content_3 = get_option('produ_subscription_email_content_3', '');

        ?>
        <div class="wrap">
            <h1>Configuración de Correos</h1>
            <form method="post" action="" style="margin-left: 30px;">
                <?php wp_nonce_field('produ_subscription_email_settings', 'produ_subscription_email_nonce'); ?>
                <h2>Email: Bienvenida</h2>
                <div style="width: 60%;">
                    <button type="button" class="insert-shortcode button" data-shortcode="[user_name]">[user_name]</button>
                    <button type="button" class="insert-shortcode button" data-shortcode="[date]">[date]</button>
                    <button type="button" class="insert-shortcode button" data-shortcode="[time]">[time]</button>
                    <button type="button" class="insert-shortcode button" data-shortcode="[user_email]">[user_email]</button>
                    <button type="button" class="insert-shortcode button" data-shortcode="[user_subscription]">[user_subscription]</button>
                    <?php wp_editor($content_1, 'produ_subscription_email_welcome'); ?>
                </div>
                
                <h2>Email: Restablecer Contraseña</h2>
                <div style="width: 60%;">
                    <button type="button" class="insert-shortcode button" data-shortcode="[user_name]">[user_name]</button>
                    <button type="button" class="insert-shortcode button" data-shortcode="[date]">[date]</button>
                    <button type="button" class="insert-shortcode button" data-shortcode="[time]">[time]</button>
                    <button type="button" class="insert-shortcode button" data-shortcode="[user_email]">[user_email]</button>
                    <button type="button" class="insert-shortcode button" data-shortcode="[user_subscription]">[user_subscription]</button>
                    <?php wp_editor($content_2, 'produ_subscription_email_restore_password'); ?>
                </div>

                <h2>Email: Factura - Recibo de pago</h2>
                <div style="width: 60%;">
                    <button type="button" class="insert-shortcode button" data-shortcode="[user_name]">[user_name]</button>
                    <button type="button" class="insert-shortcode button" data-shortcode="[date]">[date]</button>
                    <button type="button" class="insert-shortcode button" data-shortcode="[time]">[time]</button>
                    <button type="button" class="insert-shortcode button" data-shortcode="[user_email]">[user_email]</button>
                    <button type="button" class="insert-shortcode button" data-shortcode="[user_subscription]">[user_subscription]</button>
                    <?php wp_editor($content_3, 'produ_subscription_email_content_3'); ?>
                </div>

                <p>
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Guardar Cambios">
                </p>
            </form>

            <h2>Enviar Correo de Prueba</h2>
            <form method="post" action="" style="margin-left: 30px;">
                <?php wp_nonce_field('produ_subscription_email_settings', 'produ_subscription_email_nonce'); ?>
                <div>
                    <label for="email_to">Enviar a:</label>
                    <input type="email" id="email_to" name="email_to" required>
                </div>
                <div style="width: 60%;">
                    <?php wp_editor('', 'email_content'); ?>
                </div>
                <p>
                    <input type="submit" name="send_email" id="send_email" class="button button-primary" value="Enviar Correo">
                </p>
            </form>
        </div>

        <style>
            textarea#produ_subscription_email_welcome, 
            textarea#produ_subscription_email_restore_password, 
            textarea#produ_subscription_email_content_3 {
                max-height: 300px;
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                $('.insert-shortcode').on('click', function() {
                    var shortcode = $(this).data('shortcode');
                    var editor = $(this).closest('div').find('textarea').attr('id');
                    window.send_to_editor(shortcode);
                });
            });
        </script>
        <?php
    }
}
