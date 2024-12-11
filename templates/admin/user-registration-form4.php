<?php acf_form_head(); ?>
<?php
    $user_id = esc_sql($_GET['member_id']);
    $user_data = get_userdata($user_id);
    $title = $user_id?'Actualizar suscriptor':'Nuevo suscriptor';
    $user_fields = get_fields('user_' . $user_id);
?>
<div class="wrap" id="create-sub-container">
    <h1 class="wp-heading-inline"><?php echo $title; ?></h1>
    <div id="poststuff">
        <form method="post" action="" style="margin-top: 20px;">
            <input type="hidden" name="custom_registration_nonce" value="<?php echo wp_create_nonce('custom-registration-nonce'); ?>">
            <input type="hidden" name="action" value="custom_registration_form">
            <div class="postbox acf-postbox">
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle">Datos usuario</h2>
                </div>
                <div class="acf-fields acf-form-fields -top">
                    <div class="acf-field acf-field-text -c0" style="width: 50%; min-height: 87px;" data-width="50">
                        <div class="acf-label">
                            <label for="username">Username (nickname) *</label>
                        </div>
                        <div class="acf-input">
                            <div class="acf-input-wrap">
                                <input <?php if ($user_id) : ?>readonly<?php endif; ?> name="username" type="text" id="username" value="<?php echo $user_data->data->user_login; ?>" aria-required="true" autocapitalize="none" autocorrect="off" autocomplete="off" maxlength="60" required>
                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="acf-field acf-field-text" style="width: 50%; min-height: 87px;" data-width="50">
                        <div class="acf-label">
                            <label for="email">Correo Electrónico *</label>
                        </div>
                        <div class="acf-input">
                            <div class="acf-input-wrap">
                                <input name="email" type="email" id="email" value="<?php echo $user_data->data->user_email; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="acf-field acf-field-text -c0" style="width: 50%; min-height: 87px;" data-width="50">
                        <div class="acf-label">
                            <label for="first_name">Nombre *</label>
                        </div>
                        <div class="acf-input">
                            <div class="acf-input-wrap">
                                <input name="first_name" type="text" id="first_name" value="<?php echo $user_data->first_name; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="acf-field acf-field-text" style="width: 50%; min-height: 87px;" data-width="50">
                        <div class="acf-label">
                            <label for="last_name">Apellidos *</label>
                        </div>
                        <div class="acf-input">
                            <div class="acf-input-wrap">
                                <input name="last_name" type="text" id="last_name" value="<?php echo $user_data->last_name; ?>">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="postbox acf-postbox">
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle">Datos complementarios</h2>
                </div>
                <?php
                    acf_form(array(
                        'form'          => TRUE,
                        'field_groups'  => array('group_650cb35a55e69', 'group_650cb8c074d62'),
                        'post_id'       => 'new_post',
                        'new_user'      => TRUE
                    ));
                    acf_enqueue_scripts();
                ?>
            </div>
        </form>
    </div>
</div>
<?php acf_enqueue_scripts(); ?>