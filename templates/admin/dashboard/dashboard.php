<?php require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'inc/subscription-helper.php' ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@1.2.0/dist/chartjs-plugin-zoom.min.js"></script>

<body>
    <div class="suscription-dashboard-body" <?php if (get_current_screen()->base === 'dashboard') echo 'style="padding: unset;"'; ?>>
        <?php
        global $wpdb;

        $current_user = wp_get_current_user();

        $nickname = $current_user->nickname;
        $first_name = $current_user->first_name;
        $last_name = $current_user->last_name;

        $first_name = ucwords(strtolower($first_name));
        $last_name = ucwords(strtolower($last_name));

        if (!empty($first_name) && !empty($last_name)) {
            $welcome_message = "Bienvenido, " . $first_name . " " . $last_name;
        } else {
            $welcome_message = "Bienvenido, " . $nickname;
        }

        $all_subscriptions = count(get_users(array('role' => 'subscriber')));

        $statuses = ['pendiente', 'vencida', 'activa', 'inactiva'];
        $totales = [];

        foreach ($statuses as $status) {
            $totales[$status] = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
                    'subscriptions_sub_status',
                    $status
                )
            );
        }

        $pendientes = $totales['pendiente'];
        $vencidas = $totales['vencida'];
        $activas = $totales['activa'];
        $inactivas = $totales['inactiva'];

        ?>
        <h1 style="margin: 4px 0 23px;font-size: 1.5em;"><?php echo $welcome_message; ?></h1>
        <!-- <hr class="welcome-dash-hr"> -->
        <div style="display: flex;gap: 15px;width: 100%;flex-wrap: wrap;">
            <div class="sus-info-card" style="width: 73%;">
                <p class="card-title">Todas las Suscripciones</p>
                <canvas id="canvas" width="400" height="100" aria-label="Hello ARIA World" role="img"></canvas>
                <div class="sub-cards-container">
                    <div class="sub-cards-info">
                        <h4>Total Suscriptores</h4>
                        <h3><?php echo number_format($all_subscriptions, 0, '', '.') ?></h3>
                    </div>
                    <div class="sub-cards-info">
                        <h4>Pendiente por renovar</h4>
                        <h3><?php echo number_format($pendientes, 0, '', '.') ?></h3>
                    </div>
                    <div class="sub-cards-info">
                        <h4>Suscripciones Activas</h4>
                        <h3><?php echo number_format($activas, 0, '', '.') ?></h3>
                    </div>
                    <div class="sub-cards-info" style="border: unset;">
                        <h4>Suscripciones Inactivas</h4>
                        <h3><?php echo number_format($inactivas, 0, '', '.') ?></h3>
                    </div>
                </div>
            </div>
            <div class="sus-info-card chart-container" style="width: 21%;">
                <p class="card-title" style="padding: 0;">Suscritos por Planes</p>
                <canvas id="doughnut-chart" width="400" height="400"></canvas>
                <div class="chart-legend" id="chart-legend"></div>
            </div>
        </div>
        <div style="margin: 15px 0px 0px; display: flex; gap:15px; margin-bottom: 50px;">
            <div class="sus-info-card" style="width: 460px;height: fit-content;padding: 15px 5px;">
                <p class="card-title" style="margin: 0px 0px 12px;">Suscriptores recientes</p>
                <?php
                $args = array(
                    'role'         => 'subscriber',
                    'meta_key'     => '_wp_user_subscription_status',
                    'orderby'      => 'user_registered',
                    'order'        => 'DESC',
                    'number'       => 10,
                );

                $user_query = new WP_User_Query($args);

                if (!empty($user_query->get_results())) {
                ?>
                    <div style="padding: 0px 20px; margin-top: 10px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Plan</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($user_query->get_results() as $user) {
                                    $user_id = $user->ID;
                                    $user_name = $user->display_name;

                                    $plan_term = SubscriptionHelper::getPlanByUserMeta($user_id);
                                    $subscription_plan = $plan_term ? $plan_term->name : 'No aplica';

                                    $status_raw = get_user_meta($user_id, '_wp_user_subscription_status', true);
                                    $status_label = '';
                                    switch ($status_raw) {
                                        case '1':
                                            $status_label = 'Activa';
                                            break;
                                        case '2':
                                            $status_label = 'Inactiva';
                                            break;
                                        case '3':
                                            $status_label = 'Suspendida';
                                            break;
                                        case '4':
                                            $status_label = 'Vencida';
                                            break;
                                        default:
                                            $status_label = 'No aplica';
                                            break;
                                    }


                                    $plan_class = '';
                                    switch ($subscription_plan) {
                                        case 'VIP':
                                            $plan_class = 'vip';
                                            break;
                                        case 'Mercados Lite':
                                            $plan_class = 'mercados-lite';
                                            break;
                                        case 'Mercados Global':
                                            $plan_class = 'mercados-global';
                                            break;
                                        case 'VIP PRO':
                                            $plan_class = 'vip-pro';
                                            break;
                                        default:
                                            $plan_class = '';
                                            break;
                                    }

                                ?>
                                    <tr>
                                        <td class="id"><?php echo esc_html($user_id); ?></td>
                                        <td class="nombre"><a class="list-name" href="#"><?php echo esc_html($user_name); ?></a></td>
                                        <td>
                                            <div class="planes <?php echo esc_attr($plan_class); ?>"><?php echo esc_html($subscription_plan); ?></div>
                                        </td>
                                        <td><?php echo esc_html($status_label); ?></td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php
                } else {
                    echo '<p>No hay suscriptores activos recientes.</p>';
                }
                ?>
            </div>

            <div class="sus-info-card" style="width: 460px;height: fit-content;padding: 15px 5px;">
                <!-- <p class="card-title" style="margin: 0px 0px 12px;">Suscriptores pendientes de pago</p> -->
                <!-- <hr class="suscription-dashboard"> -->
                <!-- <div style="padding: 0px 20px;margin: 10px 0px;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Plan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="id">251951</td>
                                <td class="nombre"><a class="list-name" href="#">Juan Pérez</a></td>
                                <td>
                                    <div class="planes vip">VIP</div>
                                </td>
                            </tr>
                            <tr>
                                <td class="id">232546</td>
                                <td class="nombre"><a class="list-name" href="#">María González</a></td>
                                <td>
                                    <div class="planes mercados-lite">Mercados Lite</div>
                                </td>
                            </tr>
                            <tr>
                                <td class="id">300959</td>
                                <td class="nombre"><a class="list-name" href="#">Carlos Ramírez</a></td>
                                <td>
                                    <div class="planes vip-pro">VIP PRO</div>
                                </td>
                            </tr>
                            <tr>
                                <td class="id">344581</td>
                                <td class="nombre"><a class="list-name" href="#">Carlos Ramírez</a></td>
                                <td>
                                    <div class="planes mercados-global">Mercados Global</div>
                                </td>
                            </tr>
                            <tr>
                                <td class="id">377582</td>
                                <td class="nombre"><a class="list-name" href="#">Carlos Ramírez</a></td>
                                <td>
                                    <div class="planes vip">VIP</div>
                                </td>
                            </tr>
                            <tr>
                                <td class="id">251951</td>
                                <td class="nombre"><a class="list-name" href="#">Juan Pérez</a></td>
                                <td>
                                    <div class="planes vip">VIP</div>
                                </td>
                            </tr>
                            <tr>
                                <td class="id">232546</td>
                                <td class="nombre"><a class="list-name" href="#">María González</a></td>
                                <td>
                                    <div class="planes mercados-lite">Mercados Lite</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div> -->

                <p class="card-title" style="margin: 0px 0px 12px;">Suscriptores con estado Inactiva o Pendiente</p>
                <?php
                $allowed_statuses = array('2', '5'); // 2 = Inactiva, 5 = Pendiente

                $args = array(
                    'role'         => 'subscriber',
                    'meta_key'     => '_wp_user_subscription_status',
                    'meta_value'   => $allowed_statuses,
                    'meta_compare' => 'IN',
                    'orderby'      => 'user_registered',
                    'order'        => 'DESC',
                    'number'       => 10,
                );

                $user_query = new WP_User_Query($args);

                $plans = array('VIP', 'Mercados Lite', 'Mercados Global', 'VIP PRO');

                if (!empty($user_query->get_results())) {
                ?>
                    <div style="padding: 0px 20px; margin-top: 10px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Plan Asignado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($user_query->get_results() as $user) {
                                    $user_id = $user->ID;
                                    $user_name = $user->display_name;

                                    $random_plan = $plans[array_rand($plans)];

                                    $plan_class = '';
                                    switch ($random_plan) {
                                        case 'VIP':
                                            $plan_class = 'vip';
                                            break;
                                        case 'Mercados Lite':
                                            $plan_class = 'mercados-lite';
                                            break;
                                        case 'Mercados Global':
                                            $plan_class = 'mercados-global';
                                            break;
                                        case 'VIP PRO':
                                            $plan_class = 'vip-pro';
                                            break;
                                        default:
                                            $plan_class = '';
                                            break;
                                    }

                                ?>
                                    <tr>
                                        <td class="id"><?php echo esc_html($user_id); ?></td>
                                        <td class="nombre"><a class="list-name" href="#"><?php echo esc_html($user_name); ?></a></td>
                                        <td>
                                            <div class="planes <?php echo esc_attr($plan_class); ?>"><?php echo esc_html($random_plan); ?></div>
                                        </td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php
                } else {
                    echo '<p>No hay suscriptores inactivos o pendientes recientes.</p>';
                }
                ?>

            </div>
        </div>
    </div>
</body>