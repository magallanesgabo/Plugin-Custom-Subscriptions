<?php
/*
Template Name: Subscriber's register template
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( is_user_logged_in() ) {
    wp_redirect( home_url() );
    exit;
}

if ( isset( $_POST['submit_login'] ) && isset( $_POST['register_form_nonce'] ) && wp_verify_nonce( $_POST['register_form_nonce'], 'user_register' ) ) {
    $username = sanitize_user( $_POST['username'] );
    $email = sanitize_email( $_POST['email'] );
    $password = $_POST['password'];

    if ( username_exists( $username ) ) {
        echo 'El nombre de usuario ya existe.';
    } elseif ( ! is_email( $email ) ) {
        echo 'El correo electrónico no es válido.';
    } elseif ( email_exists( $email ) ) {
        echo 'El correo electrónico ya está registrado.';
    } else {
        $userId = wp_create_user( $username, $password, $email );

        if ( ! is_wp_error( $userId ) ) {
            $registerError = 'Registro completado exitosamente. Por favor, inicie sesión.';
            # Inicio de sesión automático
            $creds = array(
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => TRUE
            );

            $user = wp_signon( $creds, FALSE );

            if ( ! is_wp_error( $user ) ) {
                wp_set_current_user( $user->ID );
                wp_set_auth_cookie( $user->ID );

                SubscriptionHelper::createDefaultSubscription( $user->ID );

                if ( isset( $_GET['redirect_to'] ) ) {
                    $redirect_url = esc_url_raw( $_GET['redirect_to'] );
                    wp_redirect( $redirect_url );
                    exit;
                }
                wp_redirect( home_url( ) );
                exit;
            } else {
                $registerError = 'Error al iniciar sesión automáticamente.';
            }
        } else {
            $registerError = 'Hubo un error al registrar el usuario.';
        }
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
                    <h1>Registro de suscriptor</h1>
                    <div class="form-group">
                        <input <?php if ( isset( $_POST['username'] ) ) : ?>value="<?php echo $_POST['username']; ?>"<?php endif; ?> name="username" id="username" class="form-control" placeholder="Usuario" required>
                    </div>
                    <div class="form-group">
                        <input <?php if ( isset( $_POST['email'] ) ) : ?>value="<?php echo $_POST['email']; ?>"<?php endif; ?> name="email" id="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <?php wp_nonce_field( 'user_register', 'register_form_nonce' ); ?>
                        <input <?php if ( isset( $_POST['password'] ) ) : ?>value="<?php echo $_POST['password']; ?>"<?php endif; ?> name="password" id="password" type="password" class="form-control" placeholder="Contraseña" required>
                    </div>
                    <input name="submit_login" type="submit" class="btn btn-entrar" value="Registrar">
                </form>
                <?php if ( isset( $registerError ) ) : ?>
                    <p class="login-error"><?php echo $registerError; ?></p>
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
