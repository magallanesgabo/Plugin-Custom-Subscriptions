<?php
/*
Template Name: Subscriber's reset password template
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// if ( is_user_logged_in() ) {
//     wp_redirect( home_url() );
//     exit;
// }

if ( isset( $_GET['action'] ) && $_GET['action'] == 'rp' && isset( $_GET['key'] ) && isset( $_GET['login'] ) ) {
    $key = sanitize_text_field( $_GET['key'] );
    $login = sanitize_text_field( $_GET['login'] );
    $user = check_password_reset_key( $key, $login );

    if ( is_wp_error( $user ) ) {
        $postMessageError = 'El enlace de restablecimiento de contraseña es inválido o ha expirado.';
    }

    if ( isset( $_POST['submit_changepassword'] ) && isset( $_POST['changepassword_form_nonce'] ) && wp_verify_nonce( $_POST['changepassword_form_nonce'], 'user_changepassword' ) ) {
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if ( $newPassword !== $confirmPassword ) {
            $postMessageError = 'Las contraseñas no coinciden.';
        } elseif ( strlen( $newPassword ) < 6 ) {
            $postMessageError = 'La nueva contraseña debe tener al menos 6 caracteres.';
        } else {
            # Restablecer la contraseña
            $envUrl = sanitize_title( PRODUSUBSCRIPTION_ENV_NAME );
            reset_password( $user, $newPassword );
            $postMessageSuccess = 'La contraseña ha sido restablecida exitosamente. Puedes iniciar sesión con tu nueva contraseña.';
            wp_redirect( home_url( "/$envUrl/login/" ) );
            exit;
        }
    }
} else $postMessageError = 'El enlace de restablecimiento de contraseña es inválido o ha expirado.';

SubscriptionHelper::subscriptionGetHeader( 'subscribers-header' ); ?>

<div class="section-login">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-5 sec-purple">
                <form action="" method="post">
                    <a href="<?php echo home_url(); ?>"><i class="fa fa-arrow-left"></i> Regresar</a>
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/PRODU35LOGO.png" alt="">
                    <h1>Cambiar contraseña</h1>
                    <div class="form-group">
                        <input <?php if ( isset( $_POST['new_password'] ) ) : ?>value="<?php echo $_POST['new_password']; ?>"<?php endif; ?> name="new_password" id="new_password" type="password" class="form-control" placeholder="Contraseña" required>
                    </div>
                    <div class="form-group">
                        <input <?php if ( isset( $_POST['confirm_password'] ) ) : ?>value="<?php echo $_POST['confirm_password']; ?>"<?php endif; ?> name="confirm_password" id="confirm_password" type="password" class="form-control" placeholder="Repetir contraseña" required>
                    </div>
                    <?php wp_nonce_field( 'user_changepassword', 'changepassword_form_nonce' ); ?>
                    <input name="submit_changepassword" type="submit" class="btn btn-entrar" value="Restablecer">
                </form>
                <?php if ( isset( $postMessageError ) ) : ?>
                    <p class="error-message"><?php echo $postMessageError; ?></p>
                <?php endif; ?>
                <?php if ( isset( $postMessageSuccess ) ) : ?>
                    <p class="success-message"><?php echo $postMessageSuccess; ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-7 sec-white">
                <h2>Las noticias de<br> la industria en <br><strong> un solo lugar</strong></h2>
                <h6>Te brindamos el contenido más exclusivo y veraz de la Industria en Iberoamérica</h6>
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/lap.png" alt="">
            </div>
        </div>
    </div>
</div>

<?php SubscriptionHelper::subscriptionGetFooter( 'subscribers-footer' );