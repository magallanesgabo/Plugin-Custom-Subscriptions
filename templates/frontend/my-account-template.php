<?php
/*
Template Name: Subscriber's account template
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( !is_user_logged_in() ) {
    wp_redirect( home_url().'/membrecia/login/' );
    exit;
}

SubscriptionHelper::subscriptionGetHeader( 'subscribers-header' );
?>
<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <a class="navbar-brand" href="#">
                <img style="height: 100%;"  alt="Brand" src="<?php echo get_template_directory_uri().'/assets/images/PRODU35LOGO.png'; ?>">
            </a>
        </div>
    </div>
</nav>
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <div class="jumbotron">
                <h1>MI PRODU</h1>
                <a class="btn btn-info" title="Cerrar sesión" href="<?php echo wp_logout_url( home_url().'?logout=true' ); ?>">Salir</a>
            </div>
            <?php
            $current_user = wp_get_current_user();

            if ( $current_user->exists() && current_user_can('subscriber') ) : ?>
            <?php
                echo "<h2>Bienvenido <span class=\"text-primary\">$current_user->first_name</span></h2>";

                $fields = get_fields( 'user_'.$current_user->ID);
                $fields = get_user_meta( $current_user->ID);

                $subscription = SubscriptionHelper::getSubscriptionByUserMeta( $current_user->ID );
                $plan = SubscriptionHelper::getPlanByUserMeta( $current_user->ID );
                print '<br><br><span class="alert alert-success">Plan <b>'.$plan->name.'</b>';
                #ACA validar que el plan sea corporativo tanto para el propietario como el beneficiario
                if (isset($fields['_wp_user_subscription_related_subscription_id'][0]) && $fields['_wp_user_subscription_related_subscription_id'][0]) {
                    $plan1 = SubscriptionHelper::getPlanBySubscriptionId($fields['_wp_user_subscription_related_subscription_id'][0]);
                    print ', con beneficios de Plan <b>'.$plan1->name.'</b>';
                }
                print '.</span><br><br>';
                $planFields = get_fields( "term_$plan->term_id" );
                $nunMembers = $planFields['plans_plan_num_users'];
                $counter = 0;
                $corporate = SubscriptionHelper::isCorporate($subscription->ID);
                if ( $subscription ) :?>
                    <?php if ($corporate) : ?>
                        <br>
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <h2>Invitaciones</h2><br>
                                <form class="form">
                                    <input type="hidden" name="post_id" id="post_id" value="<?php echo $subscription->ID; ?>">
                                    <?php $invitations = PRODUSubscriptionInvitation::get_pending_invitations_by_susbcription( $subscription->ID );
                                    $subscriptionFields = get_fields( $subscription->ID );
                                    $beneficiaries = SubscriptionHelper::getBeneficiariesInSubscription( $subscription->ID );
                                    if (count( $beneficiaries ) > 0 ) {
                                        $memberCounter = (int) $nunMembers - (int) count( $beneficiaries );
                                        --$memberCounter;
                                        foreach ( $subscriptionFields['subscriptions_sub_beneficiaries'] as $beneficiary ) {
                                            #ACA hay que obtener los email de los beneficiarios y validarlo contra la tabla intermedia para ver si aceptó o no la invitación ?>
                                            <?php ++$counter; ?>
                                            <?php $member = get_userdata( $beneficiary['subscriptions_sub_user'] ); ?>
                                            <div class="form-group" style="overflow: hidden;" data-div="<?php echo $memberCounter; ?>">
                                                <label class="col-lg-1"><?php echo $counter; ?></label>
                                                <div class="col-lg-8">
                                                    <input name="beneficiaries[]" type="hidden" value="<?php echo $beneficiary['subscriptions_sub_user']; ?>">
                                                    <input class="form-control" type="text" value="<?php echo $member->user_email; ?>" readonly>
                                                </div>
                                                <div class="col-lg-3" data-place="actions">
                                                    <button data-style="zoom-in" data-size="xs" class="btn btn-danger ladda-button" data-action="remove" data-member="<?php echo $beneficiary['subscriptions_sub_user']; ?>" type="button">Quitar</button>
                                                </div>
                                            </div>
                                    <?php }
                                    } else {
                                        $memberCounter = (int) $nunMembers;
                                        --$memberCounter;
                                    }

                                    if ( $invitations ) {
                                        $memberCounter = $memberCounter - (int) count( $invitations );
                                        foreach ( $invitations as $invitation ) { ?>
                                            <?php ++$counter; ?>
                                            <div class="form-group" style="overflow: hidden;" data-div="<?php echo $memberCounter; ?>">
                                                <label class="col-lg-1"><?php echo $counter; ?></label>
                                                <div class="col-lg-8">
                                                    <input class="form-control" name="invitations[]" type="text" placeholder="Ingrese email" value="<?php echo $invitation->email; ?>" readonly>
                                                </div>
                                                <div class="col-lg-3" data-place="actions">
                                                    <button data-style="zoom-in" data-size="xs" class="btn btn-primary ladda-button" data-action="re-send" data-token="<?php echo $invitation->token; ?>" type="button">Re-enviar</button>
                                                    <button data-style="zoom-in" data-size="xs" class="btn btn-danger ladda-button" data-action="cancel" data-token="<?php echo $invitation->token; ?>" type="button">Cancelar</button>
                                                </div>
                                            </div>
                                        <?php }
                                    }

                                    for ($i = 0; $i < $memberCounter; $i++) { ?>
                                        <?php ++$counter; ?>
                                        <div class="form-group" style="overflow: hidden;" data-div="<?php echo $memberCounter; ?>">
                                            <label class="col-lg-1"><?php echo $counter; ?></label>
                                            <div class="col-lg-8">
                                                <input class="form-control" name="invitations[]" type="email" placeholder="Ingrese email">
                                            </div>
                                            <div class="col-lg-3" data-place="actions">
                                                <button data-style="zoom-in" data-size="xs" class="btn btn-success ladda-button" data-action="send" type="button">Enviar</button>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
            <?php
                echo '<br><div class="panel panel-default"><div class="panel-body">';
                echo "<h2>Mis newsletters</h2><br>";
                if (isset($plan1)) {
                    $newsletters = SubscriptionHelper::getNewslettersFromPlan( $plan1->term_id );
                    $preferences = SubscriptionHelper::getSubscriptionPreferences( $fields['_wp_user_subscription_related_subscription_id'][0] , $current_user->ID );
                } else {
                    $newsletters = SubscriptionHelper::getNewslettersFromPlan( $plan->term_id );
                    $preferences = SubscriptionHelper::getSubscriptionPreferences( $subscription->ID , $current_user->ID );
                }

                $selected = SubscriptionHelper::checkPreferences( $newsletters, $preferences );
                $merge = array_merge( $selected['new'], $selected['current'] );

                foreach ( $merge as &$value ) {
                    $value['title'] = get_term($value['newsletter_wpid'])->name;
                    $check = ( isset($value['local_status']) &&  $value['local_status'] === 'subscribed' ) ?
                        '<i style="font-size: 20px; cursor:pointer;" class="_my_prerence_check fa-light fa-circle-check text-success "></i>' :
                        '<i style="font-size: 20px; cursor:pointer;" class="_my_prerence_check fa-light fa-circle-xmark text-danger"></i>';
                    $checked = ( isset($value['local_status']) &&  $value['local_status'] === 'subscribed' ) ? 'checked' : '';
                    echo '<div style="line-height:30px;"><input style="display: none;" '.$checked.' value="'.$value['newsletter_wpid'].'" name="preferences[]" type="checkbox">'.$check.' '.$value['title'].'</div>';
                }
                echo '<button data-style="expand-right" data-size="xs" id="save-preferences" type="button" class="btn btn-primary pull-right ladda-button">Actualizar</button></div></div>'

            ?>
            <div class="panel panel-default">
                <div class="panel-body">
                    <h2>Datos personales</h2>
                    <br>
                    <form class="form-horizontal" id="personal-data-form">
                        <div class="form-group">
                            <label class="col-lg-2">Nombre </label>
                            <div class="col-lg-9">
                                <input name="subscriptor_first_name" class="form-control" type="text" value="<?php echo $current_user->first_name; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2">Apellido </label>
                            <div class="col-lg-9">
                                <input name="subscriptor_last_name" class="form-control" type="text" value="<?php echo $current_user->last_name; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2">Email </label>
                            <div class="col-lg-9">
                                <input name="subscriptor_email" class="form-control" type="email" value="<?php echo $current_user->user_email; ?>">
                            </div>
                        </div>
                        <br>
                        <button data-style="expand-right" data-size="xs" id="save-personal-data" type="button" class="btn btn-primary pull-right ladda-button">Actualizar</button>
                    </form>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-body">
                    <h2>Contraseña</h2>
                    <br>
                    <form class="form-horizontal">
                        <div class="form-group">
                            <label class="col-lg-2">Contraseña </label>
                            <div class="col-lg-9">
                                <div class="input-group">
                                    <input name="subscriptor_password" class="form-control" type="password" value="" placeholder="Vacío no modifica">
                                    <span class="input-group-btn">
                                        <button id="show-password" class="btn btn-primary" type="button"><i class="fa-solid fa-eye-slash"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <br>
                        <button data-style="expand-right" data-size="xs" id="save-password" type="button" class="btn btn-primary pull-right ladda-button">Actualizar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php SubscriptionHelper::subscriptionGetFooter( 'subscribers-footer' );