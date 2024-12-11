<?php
/*
Template Name: Subscriber's lost password template
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// if ( is_user_logged_in() ) {
//     wp_redirect( home_url() );
//     exit;
// }

if ( isset( $_POST['submit_lostpassword'] ) && isset( $_POST['lostpassword_form_nonce'] ) && wp_verify_nonce( $_POST['lostpassword_form_nonce'], 'user_lostpassword' ) ) {
    $email = sanitize_email( $_POST['email'] );

    if ( ! is_email( $email ) || ! email_exists( $email ) ) {
        $postMessageError = 'El correo electrónico no es válido o no está registrado.';
    } else {
        # Enviar enlace de restablecimiento de contraseña
        $envUrl = sanitize_title( PRODUSUBSCRIPTION_ENV_NAME );
        $user = get_user_by( 'email', $email );
        $key = get_password_reset_key( $user );
        $resetUrl = add_query_arg( array(
            'action' => 'rp',
            'key'    => $key,
            'login'  => rawurlencode( $user->user_login )
        ), home_url( "/$envUrl/cambiar-contrasena/" ) );

        # Enviar correo electrónico
        $message = "Haz clic en el siguiente enlace para restablecer tu contraseña: \n\n".$resetUrl;
        mail( $email, 'Restablecimiento de Contraseña', $message );

        $postMessageSuccess = 'Se ha enviado un enlace de restablecimiento de contraseña a tu correo electrónico.';
    }
}
SubscriptionHelper::subscriptionGetHeader( 'subscribers-header' ); ?>

<div class="section-login">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-5 sec-purple">
                <form action="" method="post">
                    <a href="<?php echo home_url(); ?>"><i class="fa fa-arrow-left"></i> Regresar</a>
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/PRODU35LOGO.png" alt="">
                    <h1>Recuperar contraseña</h1>
                    <div class="form-group">
                        <?php wp_nonce_field( 'user_lostpassword', 'lostpassword_form_nonce' ); ?>
                        <input <?php if ( isset( $_POST['email'] ) ) : ?>value="<?php echo $_POST['email']; ?>"<?php endif; ?> name="email" id="email" class="form-control" placeholder="Email" required>
                    </div>
                    <input name="submit_lostpassword" type="submit" class="btn btn-entrar" value="Enviar Enlace de Restablecimiento">
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