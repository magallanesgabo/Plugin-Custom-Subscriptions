<?php
/*
Template Name: Subscriber's - User Subscription Form
*/

get_header();

global $wpdb;

$query = $wpdb->prepare("
    SELECT t.term_id, t.name, t.slug, tt.description
    FROM {$wpdb->terms} t
    INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
    WHERE tt.taxonomy = %s
    ORDER BY t.name ASC
", 'subscription-plan');

$terms = $wpdb->get_results($query);

$grouped_terms = [];

if (!empty($terms)) {
    foreach ($terms as $term) {
        $plan_type = get_field('plans_plan_type', 'term_' . $term->term_id);

        if ($plan_type) {
            $plan_type_name = get_term($plan_type)->name;
            $grouped_terms[$plan_type_name][] = $term;
        }
    }
}
?>

<?php $site_url = get_site_url(); ?>

<style>
    .center {
        text-align: center;
    }

    .cards-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .card-sus {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        max-width: 300px;
        border: 1px solid #000;
        padding: 10px;
        margin: 10px;
    }

    .section {
        margin: 30px 0px 10px;
    }

    .pay-box {
        width: fit-content;
        height: 50px;
        border: 1px solid #000;
        margin: 10px;
        padding: 5px 20px;
        text-align: center;
        display: flex;
        align-items: center;
    }

    .card-content {
        flex-grow: 1;
    }

    .card-footer {
        margin-top: auto;
    }

    .transferencia,
    .thanks {
        text-align: center;
    }

    .btn {
        border: 1px solid;
    }
</style>


<h1 class="section">Suscripcion</h1>

<?php if (!empty($grouped_terms)): ?>
    <?php foreach ($grouped_terms as $plan_type_name => $terms_group): ?>
        <h2 class="center" style="margin-top: 30px;"><?php echo esc_html($plan_type_name); ?></h2>
        <hr>

        <div class="cards-container">
            <?php foreach ($terms_group as $term): ?>
                <?php
                $plan_price = get_field('plans_plan_price', 'term_' . $term->term_id);
                $plan_newsletters = get_field('plans_plan_benefits_plans_plan_newsletter', 'term_' . $term->term_id);
                $plan_users = get_field('plans_plan_num_users', 'term_' . $term->term_id);
                $plan_duration = get_field('plans_plan_duration', 'term_' . $term->term_id);
                ?>

                <div class="card-sus">
                    <div class="card-content">
                        <h2><?php echo esc_html($term->name); ?></h2>
                        <p><?php echo esc_html($plan_duration); ?></p>
                        <p><?php echo '$ ' . esc_html($plan_price); ?></p>
                        <p><?php echo esc_html($term->description); ?></p>
                        <h3>Incluye plan</h3>
                        <p>desc incluye plan</p>
                        <hr>
                        <h3>Newsletters</h3>
                        <?php if (!empty($plan_newsletters) && is_array($plan_newsletters)): ?>
                            <ul>
                                <?php foreach ($plan_newsletters as $newsletter): ?>
                                    <li><?php echo esc_html($newsletter); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No newsletters found.</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <button id="plan-button" plan-id="<?php echo esc_html($term->term_id); ?>">Obtener</button>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>

    <?php endforeach; ?>
<?php else: ?>
    <p>No se encontraron planes.</p>
<?php endif; ?>


<div class="section">
    <h1>Formulario de Registro</h1>
</div>

<!-- Formulario de registro -->
<form id="register_form" style="display: flex; flex-direction: column; width: 300px;">
    <input type="hidden" name="plan_id" id="plan_id">

    <label for="username">Nombre de usuario (obligatorio)</label>
    <input type="text" name="username" id="username" required>

    <label for="email">Correo electrónico (obligatorio)</label>
    <input type="email" name="email" id="email" required>

    <button type="submit" id="submit_button">Registrar</button>
</form>

<div id="register_response"></div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#plan-button').on('click', function() {
            var planId = $(this).attr('plan-id');
            $('#plan_id').val(planId);
        });

        $('#register_form').on('submit', function(e) {
            e.preventDefault();

            var username = $('#username').val();
            var email = $('#email').val();
            var planId = $('#plan_id').val();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'custom_user_registration',
                    username: username,
                    email: email,
                    plan_id: planId,
                    security: '<?php echo wp_create_nonce('custom_user_registration_nonce'); ?>'
                },
                success: function(response) {
                    $('#register_response').html(response);
                    if (response.includes('Usuario registrado exitosamente')) {
                        $('#register_form')[0].reset();
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error: ' + error);
                    console.log('Status: ' + status);
                    console.dir(xhr);
                }
            });
        });
    });
</script>

<div class="section" style="display: flex;">
    <div class="pay-box">
        <button id="transferencia-btn">Transferencia</button>
    </div>
    <div class="pay-box">
        <form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
            <!-- Identificador de la cuenta de PayPal -->
            <input type="hidden" name="business" value="TU_CORREO_PAYPAL_SANDBOX">

            <!-- Tipo de acción: Compra inmediata -->
            <input type="hidden" name="cmd" value="_xclick">

            <!-- Descripción del artículo -->
            <input type="hidden" name="item_name" value="Nombre del artículo o servicio">

            <!-- Número de orden -->
            <input type="hidden" name="item_number" value="<?php echo uniqid(); ?>">

            <!-- Precio del artículo -->
            <input type="hidden" name="amount" value="10.00">

            <!-- Moneda -->
            <input type="hidden" name="currency_code" value="USD">

            <!-- URL de retorno después del pago -->
            <input type="hidden" name="return" value="<?php echo get_site_url(); ?>/payment-confirmation">

            <!-- URL de cancelación -->
            <input type="hidden" name="cancel_return" value="<?php echo get_site_url(); ?>/payment-cancelled">

            <!-- URL de notificación IPN -->
            <input type="hidden" name="notify_url" value="<?php echo get_site_url(); ?>/paypal-ipn">

            <!-- Imagen del botón de PayPal -->
            <input type="image" src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/checkout-logo-large.png"
                name="submit" alt="Pay with PayPal">
        </form>

    </div>
    <div class="pay-box">
        <div id="AmazonPayButton"></div>

        <script src="https://static-na.payments-amazon.com/OffAmazonPayments/us/js/Widgets.js"></script>

        <script type="text/javascript">
            new OffAmazonPayments.Button("AmazonPayButton", "M6QK1VB6AY4FQ", {
                type: "PwA",
                color: "Gold",
                size: "small",
                sandbox: true, // Habilita el modo sandbox para pruebas
                authorization: function() {
                    loginWithAmazon.authorize({
                        scope: "payments:widget",
                        popup: true
                    }, function(response) {
                        if (response.error) {
                            console.log("Error de autorización: ", response.error);
                        } else {
                            // Usa la URL del sitio de WordPress para redirigir
                            window.location.href = "<?php echo $site_url; ?>/?access_token=" + response.access_token;
                        }
                    });
                },
                onError: function(error) {
                    console.log("Error: ", error.getErrorMessage());
                }
            });
        </script>


    </div>
</div>
</div>

<div id="transferencia" class="transferencia" style="display: none;">
    <hr>
    <h4>Para continuar con el proceso de suscripcion realiza una transferencia de acuerdo a las siguientes instrucciones.
        Luego notificalo a <strong>suscri@produ.com</strong>
    </h4>
    <h5><strong>OCEAN BANK</strong></h5>
    <h5>Direccion: 780 NW 42 nd Avenue Miami, FL-33126 USA</h5>
    <h5>Nombre de la cuenta: Produccion y Distribucion Corp</h5>
    <h5>Número: 050528078805</h5>
    <h5>ABA: 066011392</h5>
    <h5>SWIFT: OCBKUS3M</h5>
</div>

<script>
    jQuery(document).ready(function($) {
        $('#transferencia-btn').click(function() {
            $('#transferencia').show();
        });

        $('#paypal-btn, #amazon-btn').click(function() {
            $('#transferencia').hide();
        });
    });
</script>
<hr>
<div class="thanks">
    <h1>Notificacion de Pago</h1>
    <div class="fin-proceso">
        <h3 class="ng-binding">Gracias por interesarte en nuestro plan LARGE ¡Disfrútalo!</h3>
        <h2>Hemos enviado a tu correo electrónico <br> las instrucciones para realizar el pago y un enlace donde podrás notificarlo.</h2>
        <h4>¡Gracias por preferirnos!</h4>
        <br>

        <div class="row">
            <div class="col-md-offset-1 col-md-6"><a href="/" class="btn btn-plan">Regresar a nuestros planes de Suscripción</a></div>
            <div class="col-md-4"><a href="https://produ.com/" class="btn btn-plan">Ir a PRODU.COM</a></div>
        </div>
        <br>

        <h3>No olvides tener a mano tu nombre de usuario y clave para acceder</h3>
    </div>
</div>

<?php get_footer();
