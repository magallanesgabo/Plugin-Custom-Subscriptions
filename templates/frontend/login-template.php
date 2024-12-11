<?php
/*
Template Name: Subscriber's login template
*/
?>
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( is_user_logged_in() ) {
    wp_redirect( home_url() );
    exit;
}

if ( isset( $_POST['submit_login'] ) && isset( $_POST['login_form_nonce'] ) && wp_verify_nonce( $_POST['login_form_nonce'], 'user_login' )  ) {
    $username = sanitize_user( $_POST['username'] );
    $password = $_POST['password'];

    $user = get_user_by( 'login', $username );
    if (!$user) {
        $user = get_user_by( 'email', $username );
    }

    if ( $user && !is_wp_error( $user ) ) {
        $loginAllowed = get_user_meta( $user->ID, '_wp_user_subscription_login_enabled', TRUE );

        if ( $loginAllowed === '1' ) {
            $user = wp_signon( array( 'user_login' => $username, 'user_password' => $password ), FALSE );

            if ( !is_wp_error( $user ) ) {
                if ( isset( $_GET['redirect_to'] ) ) {
                    $redirect_url = esc_url_raw( $_GET['redirect_to'] );
                    wp_redirect( $redirect_url );
                    exit;
                }
                wp_redirect( home_url( ) );
                exit;
            } else {
                $loginError = 'Usuario y/o contraseña incorrectos. Si el problema persiste, ponte en contacto con nosotros a <a class="email" href="mailto:suscri@produ.com">suscri@produ.com</a>.';
            }
        } else {
            $loginError = 'Al parecer hay un problema con tu suscripción, si crees que es un error, ponte en contacto con nosotros a <a class="email" href="mailto:suscri@produ.com">suscri@produ.com</a>.';
        }
    } else {
        $loginError = 'Usuario y/o contraseña incorrectos. Si el problema persiste, ponte en contacto con nosotros a <a class="email" href="mailto:suscri@produ.com">suscri@produ.com</a>.';
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
                    <h1>Iniciar Sesión</h1>
                    <div class="form-group">
                        <input <?php if ( isset( $_POST['username'] ) ) : ?>value="<?php echo $_POST['username']; ?>"<?php endif; ?> name="username" id="username" class="form-control" placeholder="Usuario" required>
                    </div>
                    <div class="form-group">
                        <?php wp_nonce_field( 'user_login', 'login_form_nonce' ); ?>
                        <input <?php if ( isset( $_POST['password'] ) ) : ?>value="<?php echo $_POST['password']; ?>"<?php endif; ?> name="password" id="password" type="password" class="form-control" placeholder="Contraseña" required>
                    </div>
                    <input name="submit_login" type="submit" class="btn btn-entrar" value="Iniciar sesión">
                    <?php
                        $envUrl = sanitize_title( PRODUSUBSCRIPTION_ENV_NAME );
                        $redirectRegisterUrl = home_url('/'.$envUrl.'/registro/');
                        $redirectRecoveryUrl = home_url('/'.$envUrl.'/olvide-mi-contrasena/');
                        if ( isset( $_GET['redirect_to'] ) ) {
                            $redirectToUrl = esc_url_raw( $_GET['redirect_to'] );
                            $registrationUrl = home_url('/'.$envUrl.'/registro/');
                            $redirectRegisterUrl = add_query_arg( 'redirect_to', urlencode($redirectToUrl), $registrationUrl );

                            $recoveryUrl = home_url('/'.$envUrl.'/olvide-mi-contrasena/');
                            $redirectRecoveryUrl = add_query_arg( 'redirect_to', urlencode($redirectToUrl), $recoveryUrl );
                        }
                    ?>
                    <div class="register-div">¿Aún no tienes cuenta? <a href="<?php echo $redirectRegisterUrl; ?>">Regístrate</a></div>
                    <div class="register-div">¿Olvidó su contraseña? <a href="<?php echo $redirectRecoveryUrl; ?>">Recuperar contraseña</a></div>
                </form>
                <?php if ( isset( $loginError ) ) : ?>
                    <p class="login-error"><?php echo $loginError; ?></p>
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
